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
 * @filesource
 */

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\Attribute\Events\CollectMetaModelAttributeInformationEvent;
use MetaModels\Attribute\TranslatedFile\AttributeOrderTypeFactory;
use MetaModels\Attribute\TranslatedFile\AttributeTypeFactory;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use MetaModels\Attribute\TranslatedFile\Subscriber;
use MetaModels\Events\Attribute\TranslatedFile\AddAttributeInformation;
use MetaModels\Events\MetaModelsBootEvent;
use MetaModels\Events\Attribute\TranslatedFile\ImageSizeOptions;
use MetaModels\MetaModelsEvents;

return [
    MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE => [
        function (CreateAttributeFactoryEvent $event) {
            $factory = $event->getFactory();
            $factory->addTypeFactory(new AttributeTypeFactory());
            $factory->addTypeFactory(new AttributeOrderTypeFactory());
        }
    ],
    CollectMetaModelAttributeInformationEvent::NAME => [
        [[new AddAttributeInformation(), 'addInformation'], -1]
    ],
    MetaModelsEvents::SUBSYSTEM_BOOT_BACKEND   => [
        function (MetaModelsBootEvent $event) {
            new Subscriber($event->getServiceContainer());
        }
    ],
    GetPropertyOptionsEvent::NAME => [
        [new ImageSizeOptions(), 'getPropertyOptions']
    ]
];
