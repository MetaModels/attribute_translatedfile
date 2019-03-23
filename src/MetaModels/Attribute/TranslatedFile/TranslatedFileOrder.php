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
            return [
                'tstamp'        => \time(),
                'value_sorting' => null,
                'att_id'        => \substr($this->get('id'), 0, -\strlen('__sort')),
                'langcode'      => $strLangCode,
                'item_id'       => $intId,
            ];
        }

        return [
            'tstamp'        => \time(),
            'value_sorting' => $this->convert($arrValue['value_sorting']),
            'att_id'        => \substr($this->get('id'), 0, -\strlen('__sort')),
            'langcode'      => $strLangCode,
            'item_id'       => $intId,
        ];
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
}
