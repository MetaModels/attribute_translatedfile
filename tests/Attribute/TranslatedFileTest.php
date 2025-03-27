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
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test\Attribute;

use Contao\CoreBundle\Framework\Adapter;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile;
use MetaModels\Helper\TableManipulator;
use MetaModels\Helper\ToolboxFile;
use MetaModels\IMetaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class GeoProtection.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile
 */
class TranslatedFileTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn('mm_test');

        $metaModel
            ->expects(self::any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects(self::any())
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @param array $methods The method names to mock.
     *
     * @return MockObject|Connection
     */
    private function mockConnection($methods = [])
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(\array_merge($methods, ['getDatabasePlatform']))
            ->getMock();

        $platform = $this
            ->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMockForAbstractClass();
        $connection->method('getDatabasePlatform')->willReturn($platform);

        return $connection;
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

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $text = new TranslatedFile(
            $this->mockMetaModel('en', 'en'),
            [],
            $this->mockConnection(),
            $this->mockToolboxFile(),
            $this->mockStringUtil(),
            $this->mockValidator(),
            $this->mockFileRepository(),
            $this->mockConfig()
        );
        self::assertInstanceOf(TranslatedFile::class, $text);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSearchForFileName()
    {
        $metaModel   = $this->mockMetaModel('mm_test', 'en');
        $connection  = $this->mockConnection(['createQueryBuilder']);

        $result1 = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAllAssociative'])
            ->getMock();
        $result1
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn(
                [
                    [
                        'pid'  => StringUtil::uuidToBin('b4a3201a-bef2-153c-85ae-66930f01feda'),
                        'uuid' => StringUtil::uuidToBin('e68feb56-339b-1eb2-a675-7a5107362e40'),
                    ],
                    [
                        'pid'  => StringUtil::uuidToBin('b4a3201a-bef2-153c-85ae-66930f01feda'),
                        'uuid' => StringUtil::uuidToBin('6e38171a-47c3-1e91-83b4-b759ede063be'),
                    ],
                    [
                        'pid'  => StringUtil::uuidToBin('314f23ae-30ce-11bb-bbd3-2009656507f7'),
                        'uuid' => StringUtil::uuidToBin('0e9e4236-2468-1bfa-89f8-ca45602bec2a'),
                    ],
                ]
            );

        $builder1 = $this
            ->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->onlyMethods(['executeQuery', 'expr'])
            ->getMock();

        $builder1->expects(self::once())->method('expr')->willReturn(new ExpressionBuilder($connection));
        $builder1
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result1);

        $result2 = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchFirstColumn'])
            ->getMock();
        $result2
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn([1, 2, 3, 4, 5]);

        $builder2 = $this
            ->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->onlyMethods(['executeQuery', 'expr'])
            ->getMock();

        $builder2->expects(self::once())->method('expr')->willReturn(new ExpressionBuilder($connection));
        $builder2
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result2);

        $connection
            ->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($builder1, $builder2);

        $file = new TranslatedFile(
            $metaModel,
            [
                'id'            => 1,
                'file_multiple' => false
            ],
            $connection,
            $this->mockToolboxFile(),
            $this->mockStringUtil(),
            $this->mockValidator(),
            $this->mockFileRepository(),
            $this->mockConfig()
        );

        self::assertSame(['1', '2', '3', '4', '5'], $file->searchFor('*test?value'));

        self::assertSame(
            'SELECT f.uuid, f.pid FROM tl_files f WHERE f.name LIKE :value',
            $builder1->getSQL()
        );
        self::assertSame(['value' => '%test_value'], $builder1->getParameters());

        self::assertSame(
            'SELECT t.item_id FROM tl_metamodel_translatedlongblob t WHERE ' .
            '(t.att_id = :attributeId)' .
            ' AND (' .
            '(t.value LIKE :value_0)' .
            ' OR (t.value LIKE :value_1)' .
            ' OR (t.value LIKE :value_2)' .
            ' OR (t.value LIKE :value_3)' .
            ' OR (t.value LIKE :value_4)' .
            ')',
            $builder2->getSQL()
        );
        self::assertSame(
            [
                'attributeId' => 1,
                'value_0'     => '%' . StringUtil::uuidToBin('b4a3201a-bef2-153c-85ae-66930f01feda') . '%',
                'value_1'     => '%' . StringUtil::uuidToBin('e68feb56-339b-1eb2-a675-7a5107362e40') . '%',
                'value_2'     => '%' . StringUtil::uuidToBin('6e38171a-47c3-1e91-83b4-b759ede063be') . '%',
                'value_3'     => '%' . StringUtil::uuidToBin('314f23ae-30ce-11bb-bbd3-2009656507f7') . '%',
                'value_4'     => '%' . StringUtil::uuidToBin('0e9e4236-2468-1bfa-89f8-ca45602bec2a') . '%',
            ],
            $builder2->getParameters()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSearchForUuid()
    {
        $metaModel   = $this->mockMetaModel('mm_test', 'en');
        $connection  = $this->mockConnection(['createQueryBuilder']);

        $result1 = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAllAssociative'])
            ->getMock();
        $result1
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn(
                [
                    [
                        'pid'  => StringUtil::uuidToBin('b4a3201a-bef2-153c-85ae-66930f01feda'),
                        'uuid' => StringUtil::uuidToBin('e68feb56-339b-1eb2-a675-7a5107362e40'),
                    ],
                ]
            );

        $builder1 = $this
            ->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->onlyMethods(['executeQuery', 'expr'])
            ->getMock();

        $builder1->expects(self::once())->method('expr')->willReturn(new ExpressionBuilder($connection));
        $builder1
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result1);

        $result2 = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchFirstColumn'])
            ->getMock();
        $result2
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn([1, 2, 3, 4, 5]);

        $builder2 = $this
            ->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->onlyMethods(['executeQuery', 'expr'])
            ->getMock();

        $builder2->expects(self::once())->method('expr')->willReturn(new ExpressionBuilder($connection));
        $builder2
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result2);

        $connection
            ->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($builder1, $builder2);

        $file = new TranslatedFile(
            $metaModel,
            [
                'id'            => 1,
                'file_multiple' => false
            ],
            $connection,
            $this->mockToolboxFile(),
            $this->mockStringUtil(),
            $this->mockValidator(),
            $this->mockFileRepository(),
            $this->mockConfig()
        );

        self::assertSame(['1', '2', '3', '4', '5'], $file->searchFor('*test?value'));

        self::assertSame(
            'SELECT f.uuid, f.pid FROM tl_files f WHERE f.name LIKE :value',
            $builder1->getSQL()
        );
        self::assertSame(['value' => '%test_value'], $builder1->getParameters());

        self::assertSame(
            'SELECT t.item_id FROM tl_metamodel_translatedlongblob t WHERE ' .
            '(t.att_id = :attributeId)' .
            ' AND (' .
            '(t.value LIKE :value_0)' .
            ' OR (t.value LIKE :value_1)' .
            ')',
            $builder2->getSQL()
        );
        self::assertSame(
            [
                'attributeId' => 1,
                'value_0'     => '%' . StringUtil::uuidToBin('b4a3201a-bef2-153c-85ae-66930f01feda') . '%',
                'value_1'     => '%' . StringUtil::uuidToBin('e68feb56-339b-1eb2-a675-7a5107362e40') . '%',
            ],
            $builder2->getParameters()
        );
    }
}
