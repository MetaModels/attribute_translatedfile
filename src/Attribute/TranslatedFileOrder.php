<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedFile
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Attribute;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Query\QueryBuilder;
use MetaModels\Attribute\IInternal;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\AttributeFileBundle\Doctrine\DBAL\Platforms\Keywords\NotSupportedKeywordList;
use MetaModels\Helper\ToolboxFile;

/**
 * This is the MetaModelAttribute class for handling translated file fields.
 */
class TranslatedFileOrder extends TranslatedReference implements IInternal
{
    /**
     * The platform reserved keyword list.
     *
     * @var KeywordList
     */
    private $platformReservedWord;

    /**
     * {@inheritdoc}
     */
    protected function getValueTable()
    {
        return 'tl_metamodel_translatedlongblob';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            [
                'file_multiple',
                'file_customFiletree',
                'file_uploadFolder',
                'file_validFileTypes',
                'file_filesOnly',
                'file_widgetMode'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (empty($varValue) || empty($varValue['value_sorting'])) {
            return null;
        }

        return $this->get('file_multiple')
            ? $varValue['value_sorting']['bin']
            : ($varValue['value_sorting']['bin'][0] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        $sortingValue = ToolboxFile::convertUuidsOrPathsToMetaModels((array) $varValue);
        return [
            $this->quoteReservedWord('tstamp')        => \time(),
            $this->quoteReservedWord('value_sorting') => $sortingValue,
            $this->quoteReservedWord('att_id')        => \substr($this->get('id'), 0, -\strlen('__sort'))
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        $metaModel = $this->getMetaModel();

        $mainColumnName = \substr($this->getColName(), 0, -\strlen('__sort'));
        if (\in_array($key, ['id', 'file_multiple']) && $metaModel->hasAttribute($mainColumnName)) {
            $mainAttribute = $metaModel->getAttribute($mainColumnName);

            return $mainAttribute->get($key);
        }

        return null;
    }

    /**
     * Take the native data and serialize it for the database.
     *
     * @param mixed $values The data to serialize.
     *
     * @return string An serialized array with binary data or a binary data.
     */
    private function convert($values)
    {
        $data = ToolboxFile::convertValuesToDatabase($values);

        // Check single file or multiple file.
        if ($this->get('file_multiple')) {
            return \serialize($data);
        }

        return ($data[0] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetValues($arrValue, $intId, $strLangCode)
    {
        if (empty($arrValue)) {
            return [];
        }

        return [
            'tstamp'        => \time(),
            'value_sorting' => $this->convert($arrValue['value_sorting'])
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslatedDataFor($values, $langCode)
    {
        // First off determine those to be updated and those to be inserted.
        $existingIds = \array_keys($this->getTranslatedDataFor(\array_keys($values), $langCode));

        foreach ($existingIds as $existingId) {
            if (!isset($values[$existingId]['value_sorting']['bin'][0])
                || !\count(($setValues = $this->getSetValues($values[$existingId], $existingId, $langCode)))
            ) {
                continue;
            }

            $builder = $this->connection->createQueryBuilder();
            $builder->update($this->quoteReservedWord($this->getValueTable()));
            foreach ($setValues as $setValueKey => $setValue) {
                $builder->set($this->quoteReservedWord($setValueKey), ':' . $setValueKey);
                $builder->setParameter(':' . $setValueKey, $setValue);
            }

            $this->addWhere($builder, $existingId, $langCode);
            $builder->execute();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $values = parent::getTranslatedDataFor($arrIds, $strLangCode);
        foreach ($values as $valueId => $value) {
            $values[$valueId]['value_sorting'] = ToolboxFile::convertUuidsOrPathsToMetaModels(
                StringUtil::deserialize($value['value_sorting'], true)
            );
        }

        return $values;
    }

    /**
     * Add a where clause for the given id(s) and language code to the query builder.
     *
     * @param QueryBuilder         $builder     The query builder.
     * @param string[]|string|null $mixIds      One, none or many ids to use.
     * @param string|string[]      $mixLangCode The language code/s to use, optional.
     *
     * @return void
     */
    private function addWhere(QueryBuilder $builder, $mixIds, $mixLangCode = ''): void
    {
        $builder
            ->andWhere($builder->expr()->eq('att_id', ':attributeID'))
            ->setParameter(':attributeID', $this->get('id'));

        if (!empty($mixLangCode)) {
            if (\is_array($mixLangCode)) {
                $builder
                    ->andWhere($builder->expr()->in('langcode', ':langcodes'))
                    ->setParameter('langcodes', \array_map('strval', $mixLangCode), Connection::PARAM_STR_ARRAY);
            } else {
                $builder
                    ->andWhere($builder->expr()->eq('langcode', ':langcode'))
                    ->setParameter('langcode', $mixLangCode);
            }
        }

        if (!empty($mixIds)) {
            if (\is_array($mixIds)) {
                $builder
                    ->andWhere($builder->expr()->in('item_id', ':itemIDs'))
                    ->setParameter('itemIDs', \array_map('intval', $mixIds), Connection::PARAM_INT_ARRAY);
            } else {
                $builder
                    ->andWhere($builder->expr()->eq('item_id', ':itemID'))
                    ->setParameter('itemID', $mixIds);
            }
        }
    }

    /**
     * Quote the reserved platform key word.
     *
     * @param string $word The key word.
     *
     * @return string
     */
    private function quoteReservedWord(string $word): string
    {
        if (null === $this->platformReservedWord) {
            try {
                $this->platformReservedWord = $this->connection->getDatabasePlatform()->getReservedKeywordsList();
            } catch (DBALException $exception) {
                // Add the not support key word list, if the platform has not a list of keywords.
                $this->platformReservedWord = new NotSupportedKeywordList();
            }
        }

        if (false === $this->platformReservedWord->isKeyword($word)) {
            return $word;
        }

        return $this->connection->quoteIdentifier($word);
    }
}
