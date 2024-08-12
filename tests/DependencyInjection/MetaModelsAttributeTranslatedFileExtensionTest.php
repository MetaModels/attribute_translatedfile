<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2024 The MetaModels team.
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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
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
use MetaModels\AttributeTranslatedFileBundle\EventListener\DcGeneral\Table\DcaSetting\FileWidgetModeOptions;
use MetaModels\AttributeTranslatedFileBundle\EventListener\DcGeneral\Table\FilterSetting\RemoveAttIdOptions;
use MetaModels\AttributeTranslatedFileBundle\EventListener\Factory\AddAttributeInformation;
use MetaModels\AttributeTranslatedFileBundle\EventListener\ImageSizeOptionsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\DependencyInjection\MetaModelsAttributeTranslatedFileExtension
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class MetaModelsAttributeTranslatedFileExtensionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $extension = new MetaModelsAttributeTranslatedFileExtension();

        self::assertInstanceOf(MetaModelsAttributeTranslatedFileExtension::class, $extension);
        self::assertInstanceOf(ExtensionInterface::class, $extension);
    }

    public function testFactoryIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new MetaModelsAttributeTranslatedFileExtension();
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.factory'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.factory');
        self::assertCount(1, $definition->getTag('metamodels.attribute_factory'));

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile_order.factory'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile_order.factory');
        self::assertCount(1, $definition->getTag('metamodels.attribute_factory'));
        // phpcs:disable
        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.event_listener_factory.add_attribute_information'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.event_listener_factory.add_attribute_information');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.event_listener.build_data_definition'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.event_listener.build_data_definition');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.event_listener.build_attribute'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.event_listener.build_attribute');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.event_listener.image_size_options'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.event_listener.image_size_options');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.event_listener.remove_type_options'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.event_listener.remove_type_options');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));

        self::assertTrue($container->hasDefinition('metamodels.attribute_translatedfile.event_listener.remove_att_id_options'));
        $definition = $container->getDefinition('metamodels.attribute_translatedfile.event_listener.remove_att_id_options');
        self::assertCount(1, $definition->getTag('kernel.event_listener'));
        // phpcs:enable
        self::assertTrue($container->hasDefinition(FileWidgetModeOptions::class));
        $definition = $container->getDefinition(FileWidgetModeOptions::class);
        self::assertCount(1, $definition->getTag('kernel.event_listener'));
    }
}
