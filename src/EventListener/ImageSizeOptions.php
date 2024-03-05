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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Data\CollectionInterface;
use ContaoCommunityAlliance\DcGeneral\Data\DefaultDataProvider;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;

/**
 * Get the options for the image size.
 */
class ImageSizeOptions
{
    /**
     * Get property options for file image size in the render settings.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getPropertyOptions(GetPropertyOptionsEvent $event)
    {
        $dataDefinition = $event->getEnvironment()->getDataDefinition();
        assert($dataDefinition instanceof ContainerInterface);

        if (
            ('file_imageSize' !== $event->getPropertyName())
            || ('tl_metamodel_rendersetting' !== $dataDefinition->getName())
        ) {
            return;
        }

        if (!$sizes = $this->getThemeImageSizes()) {
            return;
        }

        $options = $event->getOptions();
        if (\is_array($options) && \array_key_exists('image_sizes', $options)) {
            $options['image_sizes'] = \array_replace($sizes, $options['image_sizes']);
            $event->setOptions($options);
        }
    }

    /**
     * Get the image sizes from the theme.
     *
     * @return array
     */
    private function getThemeImageSizes(): array
    {
        $dataProvider = new DefaultDataProvider();
        $dataProvider->setBaseConfig(['source' => 'tl_image_size']);

        $config = $dataProvider->getEmptyConfig();
        $config->setFields(['id', 'name', 'width', 'height']);
        $config->setSorting(['pid' => 'ASC', 'name' => 'ASC']);

        $collection = $dataProvider->fetchAll($config);
        assert($collection instanceof CollectionInterface);
        if (!$collection->count()) {
            return [];
        }

        $sizes = [];
        foreach ($collection as $model) {
            $sizes[$model->getProperty('id')] = \sprintf(
                '%s (%sx%s)',
                $model->getProperty('name'),
                $model->getProperty('width'),
                $model->getProperty('height')
            );
        }

        return $sizes;
    }
}
