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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\EventListener\DcGeneral\Table\FilterSetting;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;

/**
 * This class provide functions for remove type options, from the filter setting table.
 */
class RemoveAttIdOptions
{
    /**
     * Remove the internal sort attribute from the option list.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function removeOption(GetPropertyOptionsEvent $event)
    {
        $dataDefinition = $event->getEnvironment()->getDataDefinition();
        assert($dataDefinition instanceof ContainerInterface);
        if (
            ('attr_id' !== $event->getPropertyName())
            || ('tl_metamodel_filtersetting' !== $dataDefinition->getName())
        ) {
            return;
        }

        $options = $event->getOptions() ?? [];
        foreach ($options as $key => $name) {
            $sortKey = $key . '__sort';
            if (
                \array_key_exists($sortKey, $options)
                && ('[translatedfile]' === \substr($name, -\strlen('[translatedfile]')))
            ) {
                unset($options[$sortKey]);
            }
        }

        $event->setOptions($options);
    }
}
