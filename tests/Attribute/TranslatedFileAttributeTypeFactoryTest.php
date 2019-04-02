<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2019 The MetaModels team.
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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test\Attribute;

use MetaModels\Attribute\Events\CollectMetaModelAttributeInformationEvent;
use MetaModels\Attribute\IAttribute;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeOrderTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFileOrder;
use MetaModels\AttributeTranslatedFileBundle\EventListener\AddAttributeInformation;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeOrderTypeFactory
 * @covers \MetaModels\AttributeTranslatedFileBundle\EventListener\AddAttributeInformation
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
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($tableName));

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->will($this->returnValue($language));

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->will($this->returnValue($fallbackLanguage));

        $metaModel
            ->expects($this->any())
            ->method('addAttribute')
            ->will(
                $this->returnCallback(
                    function (IAttribute $objAttribute) use ($metaModel, &$mockAttributes) {
                        $mockAttributes[$objAttribute->getColName()] = $objAttribute;

                        return $metaModel;
                    }
                )
            );

        $metaModel
            ->expects($this->any())
            ->method('hasAttribute')
            ->will(
                $this->returnCallback(
                    function ($strAttributeName) use (&$mockAttributes) {
                        return array_key_exists($strAttributeName, $mockAttributes);
                    }
                )
            );

        $metaModel
            ->expects($this->any())
            ->method('getAttribute')
            ->will(
                $this->returnCallback(
                    function ($strAttributeName) use (&$mockAttributes) {
                        return array_key_exists($strAttributeName, $mockAttributes)
                            ? $mockAttributes[$strAttributeName]
                            : null;
                    }
                )
            );

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockAttributeFactory($connection)
    {
        $factory = $this->getMockForAbstractClass('\MetaModels\Attribute\IAttributeFactory');

        $factories = [
            'translatedfile'     => new AttributeTypeFactory($connection),
            'translatedfilesort' => new AttributeOrderTypeFactory($connection)
        ];

        $factory
            ->expects($this->any())
            ->method('getTypeFactory')
            ->will(
                $this->returnCallback(
                    function ($typeFactory) use ($factories) {
                        return isset($factories[(string) $typeFactory]) ? $factories[(string) $typeFactory] : null;
                    }
                )
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
        return [new AttributeTypeFactory($this->mockConnection())];
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

        $this->assertTrue($metaModel->hasAttribute('foo'));
        $this->assertInstanceOf(TranslatedFile::class, $metaModel->getAttribute('foo'));
        $this->assertFalse($metaModel->hasAttribute('foo__sort'));
        $this->assertNull($metaModel->getAttribute('foo_sort'));
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

        $this->assertTrue($metaModel->hasAttribute('foo'));
        $this->assertInstanceOf(TranslatedFile::class, $metaModel->getAttribute('foo'));
        $this->assertTrue($metaModel->hasAttribute('foo__sort'));
        $this->assertInstanceOf(TranslatedFileOrder::class, $metaModel->getAttribute('foo__sort'));
    }
}
