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

namespace MetaModels\AttributeTranslatedFileBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;

/**
 * The build data definition listener.
 */
class BuildDataDefinitionListener
{
    /**
     * This handles all translated file attributes and
     * clones the visible conditions to reflect those of the translated file attribute.
     *
     * @param BuildDataDefinitionEvent $event The event being processed.
     *
     * @return void
     */
    public static function buildDataDefinition(BuildDataDefinitionEvent $event)
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
}
