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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeTranslatedFileBundle\EventListener\DcGeneral\Table\DcaSetting;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use Doctrine\DBAL\Connection;
use MetaModels\CoreBundle\EventListener\DcGeneral\Table\DcaSetting\AbstractListener;
use MetaModels\IFactory;

/**
 * Add the options for the file widget mode.
 */
class FileWidgetModeOptions extends AbstractListener
{
    /**
     * Invoke the event.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function __invoke(GetPropertyOptionsEvent $event): void
    {
        if (
            ('file_widgetMode' !== $event->getPropertyName())
            || (false === $this->wantToHandle($event))
            || (false === $this->isAttributeTranslatedFile($event))
        ) {
            return;
        }

        $this->addOptions($event);
    }

    /**
     * Add the options.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    private function addOptions(GetPropertyOptionsEvent $event): void
    {
        $addOptions = ['downloads', 'gallery'];

        $event->setOptions(\array_values(\array_unique(\array_merge($event->getOptions() ?? [], $addOptions))));
    }

    /**
     * If used attribute type of file.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return bool
     */
    private function isAttributeTranslatedFile(GetPropertyOptionsEvent $event): bool
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('t.type')
            ->from('tl_metamodel_attribute', 't')
            ->where($builder->expr()->eq('t.id', ':id'))
            ->setParameter('id', $event->getModel()->getProperty('attr_id'));

        $statement = $builder->executeQuery();
        if (0 === $statement->columnCount()) {
            return false;
        }

        $result = $statement->fetchAssociative();

        return 'translatedfile' === ($result['type'] ?? null);
    }
}
