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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test;

use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile;
use PHPUnit\Framework\TestCase;

/**
 * This class tests if the deprecated autoloader works.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile
 * @covers \MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory
 */
class DeprecatedAutoloaderTest extends TestCase
{
    /**
     * TranslatedFilees of old classes to the new one.
     *
     * @var array
     */
    private static $classes = [
        'MetaModels\Attribute\TranslatedFile\TranslatedFile'       => TranslatedFile::class,
        'MetaModels\Attribute\TranslatedFile\AttributeTypeFactory' => AttributeTypeFactory::class
    ];

    /**
     * Provide the file class map.
     *
     * @return array
     */
    public function provideFileClassMap()
    {
        $values = [];

        foreach (static::$classes as $translatedFile => $class) {
            $values[] = [$translatedFile, $class];
        }

        return $values;
    }

    /**
     * Test if the deprecated classes are fileed to the new one.
     *
     * @param string $oldClass Old class name.
     * @param string $newClass New class name.
     *
     * @dataProvider provideFileClassMap
     */
    public function testDeprecatedClassesAreFileed($oldClass, $newClass)
    {
        self::assertTrue(class_exists($oldClass), sprintf('Class TranslatedFile "%s" is not found.', $oldClass));

        $oldClassReflection = new \ReflectionClass($oldClass);
        $newClassReflection = new \ReflectionClass($newClass);

        self::assertSame($newClassReflection->getFileName(), $oldClassReflection->getFileName());
    }
}
