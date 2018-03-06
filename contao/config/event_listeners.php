<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * @package     MetaModels
 * @subpackage  AttributeTranslatedFile
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Sven Baumann <baumann.sv@gmail.com>
 * @copyright   2012-2018 The MetaModels team.
 * @license     https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 * @filesource
 */

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\Attribute\TranslatedFile\AttributeTypeFactory;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use MetaModels\Events\Attribute\TranslatedFile\ImageSizeOptions;
use MetaModels\MetaModelsEvents;

return array
(
    MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE => array(
        function (CreateAttributeFactoryEvent $event) {
            $factory = $event->getFactory();
            $factory->addTypeFactory(new AttributeTypeFactory());
        }
    ),

    GetPropertyOptionsEvent::NAME => array(
        array(new ImageSizeOptions(), 'getPropertyOptions')
    )
);
