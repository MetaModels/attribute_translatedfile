<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_translatedfile
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test\Attribute;

use Contao\CoreBundle\Framework\Adapter;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\Events\CollectMetaModelAttributeInformationEvent;
use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeOrderTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFileOrder;
use MetaModels\AttributeTranslatedFileBundle\EventListener\Factory\AddAttributeInformation;
use MetaModels\Helper\ToolboxFile;
use MetaModels\IMetaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use MetaModels\Attribute\IAttributeFactory;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeOrderTypeFactory
 * @covers \MetaModels\AttributeTranslatedFileBundle\EventListener\Factory\AddAttributeInformation
 */
class TranslatedFileAttributeTypeFactoryTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel      = $this->getMockForAbstractClass(IMetaModel::class);
        $mockAttributes = [];

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn($tableName);

        $metaModel
            ->expects(self::any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects(self::any())
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        $metaModel
            ->expects(self::any())
            ->method('addAttribute')
            ->willReturnCallback(
                function (IAttribute $objAttribute) use ($metaModel, &$mockAttributes) {
                    $mockAttributes[$objAttribute->getColName()] = $objAttribute;

                    return $metaModel;
                }
            );

        $metaModel
            ->expects(self::any())
            ->method('hasAttribute')
            ->willReturnCallback(
                function ($strAttributeName) use (&$mockAttributes) {
                    return array_key_exists($strAttributeName, $mockAttributes);
                }
            );

        $metaModel
            ->expects(self::any())
            ->method('getAttribute')
            ->willReturnCallback(
                function ($strAttributeName) use (&$mockAttributes) {
                    return $mockAttributes[$strAttributeName] ?? null;
                }
            );

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockToolboxFile()
    {
        return $this
            ->getMockBuilder(ToolboxFile::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockStringUtil()
    {
        return $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockValidator()
    {
        return $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockFileRepository()
    {
        return $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockConfig()
    {
        return $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockAttributeFactory($connection)
    {
        $factory = $this->getMockForAbstractClass(IAttributeFactory::class);

        $factories = [
            'translatedfile'     => new AttributeTypeFactory(
                $connection,
                $this->mockToolboxFile(),
                $this->mockStringUtil(),
                $this->mockValidator(),
                $this->mockFileRepository(),
                $this->mockConfig()
            ),
            'translatedfilesort' => new AttributeOrderTypeFactory($connection)
        ];

        $factory
            ->expects(self::any())
            ->method('getTypeFactory')
            ->willReturnCallback(
                function ($typeFactory) use ($factories) {
                    return $factories[(string) $typeFactory] ?? null;
                }
            );

        return $factory;
    }

    private function createEventDispatcher()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            CollectMetaModelAttributeInformationEvent::NAME,
            [new AddAttributeInformation(), 'addInformation']
        );

        return $dispatcher;
    }

    /**
     * Override the method to run the tests on the attribute factories to be tested.
     *
     * @return IAttributeTypeFactory[]
     */
    protected function getAttributeFactories()
    {
        $toolbox        = $this->createMock(ToolboxFile::class);
        $stringUtil     = $this->createMock(Adapter::class);
        $validator      = $this->createMock(Adapter::class);
        $fileRepository = $this->createMock(Adapter::class);
        $config         = $this->createMock(Adapter::class);

        return [new AttributeTypeFactory($this->mockConnection(), $toolbox, $stringUtil, $validator, $fileRepository, $config)];
    }

    /**
     * Test creation of an translated select.
     *
     * @return void
     */
    public function testCreateSelectOfNotSortableFile()
    {
        $metaModel  = $this->mockMetaModel('mm_test', 'de', 'en');
        $factory    = $this->mockAttributeFactory($this->mockConnection());
        $dispatcher = $this->createEventDispatcher();
        $event      = new CollectMetaModelAttributeInformationEvent($metaModel);
        $event->setAttributeInformation(
            ['foo' => ['type' => 'translatedfile', 'colname' => 'foo', 'file_multiple' => null]]
        );
        $dispatcher->dispatch($event::NAME, $event);

        foreach ($event->getAttributeInformation() as $name => $information) {
            if (null === ($typeFactory = $factory->getTypeFactory($information['type']))) {
                continue;
            }

            $metaModel->addAttribute($typeFactory->createInstance($information, $metaModel));
        }

        self::assertTrue($metaModel->hasAttribute('foo'));
        self::assertInstanceOf(TranslatedFile::class, $metaModel->getAttribute('foo'));
        self::assertFalse($metaModel->hasAttribute('foo__sort'));
        self::assertNull($metaModel->getAttribute('foo_sort'));
    }

    /**
     * Test creation of an translated select.
     *
     * @return void
     */
    public function testCreateSelectOfSortableFile()
    {
        $metaModel  = $this->mockMetaModel('mm_test', 'de', 'en');
        $factory    = $this->mockAttributeFactory($this->mockConnection());
        $dispatcher = $this->createEventDispatcher();
        $event      = new CollectMetaModelAttributeInformationEvent($metaModel);
        $event->setAttributeInformation(
            ['foo' => ['type' => 'translatedfile', 'colname' => 'foo', 'file_multiple' => '1']]
        );
        $dispatcher->dispatch($event::NAME, $event);

        foreach ($event->getAttributeInformation() as $name => $information) {
            if (null === ($typeFactory = $factory->getTypeFactory($information['type']))) {
                continue;
            }

            $metaModel->addAttribute($typeFactory->createInstance($information, $metaModel));
        }

        self::assertTrue($metaModel->hasAttribute('foo'));
        self::assertInstanceOf(TranslatedFile::class, $metaModel->getAttribute('foo'));
        self::assertTrue($metaModel->hasAttribute('foo__sort'));
        self::assertInstanceOf(TranslatedFileOrder::class, $metaModel->getAttribute('foo__sort'));
    }
}
