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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test\DependencyInjection;

use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeOrderTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedFileBundle\DependencyInjection\MetaModelsAttributeTranslatedFileExtension;
use MetaModels\AttributeTranslatedFileBundle\EventListener\BuildAttributeListener;
use MetaModels\AttributeTranslatedFileBundle\EventListener\BuildDataDefinitionListener;
use MetaModels\AttributeTranslatedFileBundle\EventListener\DcGeneral\Table\Attribute\RemoveTypeOptions;
use MetaModels\AttributeTranslatedFileBundle\EventListener\DcGeneral\Table\FilterSetting\RemoveAttIdOptions;
use MetaModels\AttributeTranslatedFileBundle\EventListener\Factory\AddAttributeInformation;
use MetaModels\AttributeTranslatedFileBundle\EventListener\ImageSizeOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 */
class MetaModelsAttributeTranslatedFileExtensionTest extends TestCase
{
    /**
     * Test that extension can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $extension = new MetaModelsAttributeTranslatedFileExtension();

        $this->assertInstanceOf(MetaModelsAttributeTranslatedFileExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    /**
     * Test that the services are loaded.
     *
     * @return void
     */
    public function testFactoryIsRegistered()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $container
            ->expects($this->exactly(8))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'metamodels.attribute_translatedfile.factory',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeTypeFactory::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('metamodels.attribute_factory'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile_order.factory',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeOrderTypeFactory::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('metamodels.attribute_factory'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile.event_listener_factory.add_attribute_information',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AddAttributeInformation::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile.event_listener.build_data_definition',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(BuildDataDefinitionListener::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile.event_listener.build_attribute',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(BuildAttributeListener::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile.event_listener.image_size_options',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(ImageSizeOptions::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile.event_listener.remove_type_options',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(RemoveTypeOptions::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ],
                [
                    'metamodels.attribute_translatedfile.event_listener.remove_att_id_options',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(RemoveAttIdOptions::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));

                            return true;
                        }
                    )
                ]
            );

        $extension = new MetaModelsAttributeTranslatedFileExtension();
        $extension->load([], $container);
    }
}
