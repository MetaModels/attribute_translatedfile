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
 * @package    MetaModels
 * @subpackage AttributeTranslatedFile
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_file/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\Properties\DefaultProperty;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile;
use MetaModels\AttributeTranslatedFileBundle\DcGeneral\AttributeTranslatedFileDefinition;
use MetaModels\DcGeneral\Events\MetaModel\BuildAttributeEvent;

/**
 * The build attribute listener.
 */
class BuildAttributeListener
{
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
        if (
            !($attribute instanceof TranslatedFile)
            || !$attribute->get('file_multiple')
        ) {
            return;
        }

        $container  = $event->getContainer();
        $properties = $container->getPropertiesDefinition();
        $name       = $attribute->getColName();
        $nameSort   = \sprintf('%s__sort', $name);

        if (!$properties->hasProperty($nameSort)) {
            $properties->addProperty(new DefaultProperty($nameSort));
        }

        $properties->getProperty($nameSort)
            ->setWidgetType('fileTreeOrder')
            ->setLabel($nameSort)
            ->setExtra(['tl_class' => 'hidden']);

        $this->addAttributeToDefinition($container, $name);
    }

    /**
     * Add attribute to metamodels translated file attributes definition.
     *
     * @param ContainerInterface $container The metamodel data definition.
     * @param string             $name      The attribute name.
     *
     * @return void
     */
    private function addAttributeToDefinition(ContainerInterface $container, string $name): void
    {
        if (!$container->hasDefinition('metamodels.translatedfile-attributes')) {
            $container->setDefinition('metamodels.translatedfile-attributes', new AttributeTranslatedFileDefinition());
        }

        $definition = $container->getDefinition('metamodels.translatedfile-attributes');
        assert($definition instanceof AttributeTranslatedFileDefinition);

        $definition->add($name);
    }
}
