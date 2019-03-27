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

namespace MetaModels\Attribute\TranslatedFile;

use MetaModels\Attribute\IInternal;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\Helper\ToolboxFile;

/**
 * This is the MetaModelAttribute class for handling translated file fields.
 */
class TranslatedFileOrder extends TranslatedReference implements IInternal
{
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

        if (!$this->get('file_multiple')) {
            return isset($varValue['value_sorting']['bin'][0]) ? $varValue['value_sorting']['bin'][0] : null;
        }

        return $varValue['value_sorting']['bin'];
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        return [
            'tstamp'        => \time(),
            'value_sorting' => ToolboxFile::convertUuidsOrPathsToMetaModels((array) $varValue),
            'att_id'        => \substr($this->get('id'), 0, -\strlen('__sort'))
        ];
    }

    /**
     * Take the native data and serialize it for the database.
     *
     * @param mixed $mixValues The data to serialize.
     *
     * @return string An serialized array with binary data or a binary data.
     */
    private function convert($mixValues)
    {
        $data = ToolboxFile::convertValuesToDatabase($mixValues);

        // Check single file or multiple file.
        if ($this->get('file_multiple')) {
            return \serialize($data);
        }

        return isset($data[0]) ? $data[0] : null;
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
        $database = $this->getMetaModel()->getServiceContainer()->getDatabase();
        // First off determine those to be updated and those to be inserted.
        $existingIds = \array_keys($this->getTranslatedDataFor(\array_keys($values), $langCode));

        $queryUpdate = 'UPDATE ' . $this->getValueTable() . ' %s';
        foreach ($existingIds as $existingId) {
            if (!isset($values[$existingId]['value_sorting']['bin'][0])
                || !\count(($setValues = $this->getSetValues($values[$existingId], $existingId, $langCode)))
            ) {
                continue;
            }

            $whereParts = $this->getWhere($existingId, $langCode);
            $database->prepare($queryUpdate . ($whereParts ? ' WHERE ' . $whereParts['procedure'] : ''))
                ->set($setValues)
                ->execute(($whereParts ? $whereParts['params'] : null));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $arrValues = parent::getTranslatedDataFor($arrIds, $strLangCode);

        foreach ($arrValues as $intId => $arrValue) {
            $arrValues[$intId]['value_sorting'] = ToolboxFile::convertUuidsOrPathsToMetaModels(
                \deserialize($arrValue['value_sorting'], true)
            );
        }

        return $arrValues;
    }

    /**
     * Build a where clause for the given id(s) and language code.
     *
     * @param string[]|string|null $mixIds      One, none or many ids to use.
     * @param string|string[]      $mixLangCode The language code/s to use, optional.
     *
     * @return array
     */
    private function getWhere($mixIds, $mixLangCode = '')
    {
        $procedure  = 'att_id=?';
        $parameters = [$this->get('id')];

        if (!empty($mixIds)) {
            if (\is_array($mixIds)) {
                $procedure .= ' AND item_id IN (' . $this->parameterMask($mixIds) . ')';
                $parameters = \array_merge($parameters, $mixIds);
            } else {
                $procedure   .= ' AND item_id=?';
                $parameters[] = $mixIds;
            }
        }

        if (!empty($mixLangCode)) {
            if (\is_array($mixLangCode)) {
                $procedure .= ' AND langcode IN (' . $this->parameterMask($mixLangCode) . ')';
                $parameters = \array_merge($parameters, $mixLangCode);
            } else {
                $procedure   .= ' AND langcode=?';
                $parameters[] = $mixLangCode;
            }
        }

        return [
            'procedure' => $procedure,
            'params'    => $parameters
        ];
    }
}
