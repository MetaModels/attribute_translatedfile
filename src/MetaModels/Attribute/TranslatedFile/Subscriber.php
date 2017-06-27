<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedFile
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_file/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedFile;

use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\DefaultProperty;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MetaModels\DcGeneral\AttributeTranslatedFileDefinition;
use MetaModels\DcGeneral\DataDefinition\IMetaModelDataDefinition;
use MetaModels\DcGeneral\Events\BaseSubscriber;
use MetaModels\DcGeneral\Events\MetaModel\BuildAttributeEvent;

/**
 * Subscriber integrates translated file attribute related listeners.
 */
class Subscriber extends BaseSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                BuildAttributeEvent::NAME,
                array($this, 'buildAttribute')
            )
            ->addListener(
                BuildDataDefinitionEvent::NAME,
                array($this, 'buildDataDefinition'),
                // Ensure to be after MetaModels\DcGeneral\Dca\Builder\Builder::PRIORITY (currently 50).
                0
            );
    }

    /**
     * This builds the dc-general property information for the virtual translated file order attribute.
     *
     * @param BuildAttributeEvent $event The event being processed.
     *
     * @return void
     */
    public function buildAttribute(BuildAttributeEvent $event)
    {
        $attribute = $event->getAttribute();

        if (!($attribute instanceof TranslatedFile)
            || !$attribute->get('file_multiple')
        ) {
            return;
        }

        $container  = $event->getContainer();
        $properties = $container->getPropertiesDefinition();
        $name       = $attribute->getColName();

        if ($properties->hasProperty($name . '__sort')) {
            $this->addAttributeToDefinition($container, $name);

            $properties->getProperty($name . '__sort')->setWidgetType('fileTreeOrder');

            return;
        }

        $properties->addProperty($property = new DefaultProperty($name . '__sort'));
        $property->setWidgetType('fileTreeOrder');

        $this->addAttributeToDefinition($container, $name);
    }

    /**
     * This handles all translated file attributes and
     * clones the visible conditions to reflect those of the translated file attribute.
     *
     * @param BuildDataDefinitionEvent $event The event being processed.
     *
     * @return void
     */
    public function buildDataDefinition(BuildDataDefinitionEvent $event)
    {
        $container = $event->getContainer();
        if (!$container->hasDefinition('metamodels.translatedfile-attributes')) {
            return;
        }
        // All properties...
        foreach ($container->getDefinition('metamodels.translatedfile-attributes')->get() as $propertyName) {
            // ... in all palettes ...
            foreach ($container->getPalettesDefinition()->getPalettes() as $palette) {
                // ... in any legend ...
                foreach ($palette->getLegends() as $legend) {
                    // ... of the searched name ...
                    if (($legend->hasProperty($propertyName))
                        && ($container->getPropertiesDefinition()->hasProperty($propertyName . '__sort'))
                    ) {
                        // ... must have the order field as companion, visible only when the real property is.
                        $file = $legend->getProperty($propertyName);

                        $legend->addProperty($order = new Property($propertyName . '__sort'), $file);

                        $order->setEditableCondition($file->getEditableCondition());
                        $order->setVisibleCondition($file->getVisibleCondition());
                    }
                }
            }
        }
    }

    /**
     * Add attribute to metamodels translated file attributes definition.
     *
     * @param IMetaModelDataDefinition $container The metamodel data definition.
     *
     * @param string                   $name      The attribute name.
     *
     * @return void
     */
    protected function addAttributeToDefinition(IMetaModelDataDefinition $container, $name)
    {
        if (!$container->hasDefinition('metamodels.translatedfile-attributes')) {
            $container->setDefinition('metamodels.translatedfile-attributes', new AttributeTranslatedFileDefinition());
        }

        $container->getDefinition('metamodels.translatedfile-attributes')->add($name);
    }
}
