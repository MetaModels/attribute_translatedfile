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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFileOrder;
use PHPUnit\Framework\TestCase;
use MetaModels\IMetaModel;

/**
 * Unit tests to test class GeoProtection.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFileOrder
 */
class TranslatedFileOrderTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return \MetaModels\IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn('mm_unittest');

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

    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
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
        $text = new TranslatedFileOrder($this->mockMetaModel('en', 'en'), [], $this->mockConnection());
        self::assertInstanceOf(TranslatedFileOrder::class, $text);
    }
}
