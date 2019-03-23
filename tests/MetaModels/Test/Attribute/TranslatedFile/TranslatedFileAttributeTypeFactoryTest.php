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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Test\Attribute\TranslatedFile;

use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\Attribute\TranslatedFile\AttributeTypeFactory;
use MetaModels\IMetaModel;
use MetaModels\Test\Attribute\AttributeTypeFactoryTest;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\Attribute\TranslatedFile\AttributeTypeFactory
 */
class TranslatedFileAttributeTypeFactoryTest extends AttributeTypeFactoryTest
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
        $metaModel      = $this->getMockForAbstractClass('MetaModels\IMetaModel');
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
     * Override the method to run the tests on the attribute factories to be tested.
     *
     * @return IAttributeTypeFactory[]
     */
    protected function getAttributeFactories()
    {
        return array(new AttributeTypeFactory());
    }

    /**
     * Test creation of an translated select.
     *
     * @return void
     */
    public function testCreateSelectOfNotSortableFile()
    {
        $factory   = new AttributeTypeFactory();
        $attribute = $factory->createInstance(
            array('colname' => 'foo', 'file_multiple' => null),
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf('MetaModels\Attribute\TranslatedFile\TranslatedFile', $attribute);
        $this->assertFalse($attribute->getMetaModel()->hasAttribute('foo__sort'));
    }

    /**
     * Test creation of an translated select.
     *
     * @return void
     */
    public function testCreateSelectOfSortableFile()
    {
        $factory   = new AttributeTypeFactory();
        $attribute = $factory->createInstance(
            array('id' => 'foo', 'colname' => 'foo', 'file_multiple' => '1'),
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf('MetaModels\Attribute\TranslatedFile\TranslatedFile', $attribute);
        $this->assertTrue($attribute->getMetaModel()->hasAttribute('foo__sort'));
        $this->assertInstanceOf(
            'MetaModels\Attribute\TranslatedFile\TranslatedFileOrder',
            $attribute->getMetaModel()->getAttribute('foo__sort')
        );
    }
}
