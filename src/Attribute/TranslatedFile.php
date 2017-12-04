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
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     cogizz <info@cogizz.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Attribute;

use MetaModels\Attribute\TranslatedReference;
use MetaModels\Helper\ToolboxFile;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling translated file fields.
 */
class TranslatedFile extends TranslatedReference
{
    /**
     * {@inheritdoc}
     */
    protected function getValueTable()
    {
        return 'tl_metamodel_translatedlongblob';
    }

    /**
     * Build a where clause for the given id(s) and language code.
     *
     * @param string[]|string|null $mixIds      One, none or many ids to use.
     *
     * @param string|string[]      $mixLangCode The language code/s to use, optional.
     *
     * @return array
     */
    protected function getWhere($mixIds, $mixLangCode = '')
    {
        $procedure  = 'att_id=?';
        $parameters = array($this->get('id'));

        if (!empty($mixIds)) {
            if (is_array($mixIds)) {
                $procedure .= ' AND item_id IN (' . $this->parameterMask($mixIds) . ')';
                $parameters = array_merge($parameters, $mixIds);
            } else {
                $procedure   .= ' AND item_id=?';
                $parameters[] = $mixIds;
            }
        }

        if (!empty($mixLangCode)) {
            if (is_array($mixLangCode)) {
                $procedure .= ' AND langcode IN (' . $this->parameterMask($mixLangCode) . ')';
                $parameters = array_merge($parameters, $mixLangCode);
            } else {
                $procedure   .= ' AND langcode=?';
                $parameters[] = $mixLangCode;
            }
        }

        return array(
            'procedure' => $procedure,
            'params'    => $parameters
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException If no binary in value throw invalid exception.
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

        $objToolbox = new ToolboxFile();

        $objToolbox->setBaseLanguage($this->getMetaModel()->getActiveLanguage());

        $objToolbox->setFallbackLanguage($this->getMetaModel()->getFallbackLanguage());

        $objToolbox->setLightboxId(
            sprintf(
                '%s.%s.%s',
                $this->getMetaModel()->getTableName(),
                $objSettings->get('id'),
                $arrRowData['id']
            )
        );

        if (strlen($types = trim($this->get('file_validFileTypes')))) {
            $objToolbox->setAcceptedExtensions($types);
        }

        $objToolbox->setShowImages($objSettings->get('file_showImage'));

        if ($objSettings->get('file_imageSize')) {
            $objToolbox->setResizeImages($objSettings->get('file_imageSize'));
        }

        if ($arrRowData[$this->getColName()]) {
            if (!isset($arrRowData[$this->getColName()]['value']['bin'])) {
                throw new \InvalidArgumentException('No binary in value.');
            }

            foreach ($arrRowData[$this->getColName()]['value']['bin'] as $strFile) {
                $objToolbox->addPathById($strFile);
            }
        }

        $objToolbox->resolveFiles();
        $arrData = $objToolbox->sortFiles($objSettings->get('file_sortBy'));

        $objTemplate->files = $arrData['files'];
        $objTemplate->src   = $arrData['source'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array(
            'file_multiple',
            'file_customFiletree',
            'file_uploadFolder',
            'file_validFileTypes',
            'file_filesOnly',
        ));
    }

    /**
     * Manipulate the field definition for custom file trees.
     *
     * @param array $arrFieldDef The field definition to manipulate.
     *
     * @return void
     */
    private function handleCustomFileTree(&$arrFieldDef)
    {
        if (strlen($this->get('file_uploadFolder'))) {
            // Set root path of file chooser depending on contao version.
            $objFile = null;

            if (\Validator::isStringUuid($this->get('file_uploadFolder'))
                || \Validator::isBinaryUuid($this->get('file_uploadFolder'))
            ) {
                $objFile = \FilesModel::findByUuid($this->get('file_uploadFolder'));
            }

            // Check if we have a file.
            if ($objFile != null) {
                $arrFieldDef['eval']['path'] = $objFile->path;
            } else {
                // Fallback.
                $arrFieldDef['eval']['path'] = $this->get('file_uploadFolder');
            }
        }

        if (strlen($this->get('file_validFileTypes'))) {
            $arrFieldDef['eval']['extensions'] = $this->get('file_validFileTypes');
        }

        if (strlen($this->get('file_filesOnly'))) {
            $arrFieldDef['eval']['filesOnly'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType']          = 'fileTree';
        $arrFieldDef['eval']['files']      = true;
        $arrFieldDef['eval']['extensions'] = \Config::get('allowedDownload');
        $arrFieldDef['eval']['multiple']   = (bool) $this->get('file_multiple');

        if ($this->get('file_multiple')) {
            $arrFieldDef['eval']['fieldType'] = 'checkbox';
        } else {
            $arrFieldDef['eval']['fieldType'] = 'radio';
        }

        if ($this->get('file_customFiletree')) {
            $this->handleCustomFileTree($arrFieldDef);
        }

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (empty($varValue) || empty($varValue['value'])) {
            return null;
        }

        if (!$this->get('file_multiple')) {
            return isset($varValue['value']['bin'][0]) ? $varValue['value']['bin'][0] : null;
        }

        return $varValue['value']['bin'];
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        return array(
            'tstamp' => time(),
            'value' => ToolboxFile::convertUuidsOrPathsToMetaModels((array) $varValue),
            'att_id' => $this->get('id'),
        );
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
            return serialize($data);
        }

        return isset($data[0]) ? $data[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetValues($arrValue, $intId, $strLangCode)
    {
        if (empty($arrValue)) {
            return array(
                'tstamp'   => time(),
                'value'    => null,
                'att_id'   => $this->get('id'),
                'langcode' => $strLangCode,
                'item_id'  => $intId,
            );
        }

        return array(
            'tstamp'   => time(),
            'value'    => $this->convert($arrValue['value']),
            'att_id'   => $this->get('id'),
            'langcode' => $strLangCode,
            'item_id'  => $intId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        $objDB = $this->getMetaModel()->getServiceContainer()->getDatabase();
        // First off determine those to be updated and those to be inserted.
        $arrIds      = array_keys($arrValues);
        $arrExisting = array_keys($this->getTranslatedDataFor($arrIds, $strLangCode));
        $arrNewIds   = array_diff($arrIds, $arrExisting);

        // Update existing values - delete if empty.
        $strQueryUpdate = 'UPDATE ' . $this->getValueTable() . ' %s';
        $strQueryDelete = 'DELETE FROM ' . $this->getValueTable();

        foreach ($arrExisting as $intId) {
            $arrWhere = $this->getWhere($intId, $strLangCode);

            if ($arrValues[$intId]['value']['bin'][0]) {
                $objDB->prepare($strQueryUpdate . ($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : ''))
                    ->set($this->getSetValues($arrValues[$intId], $intId, $strLangCode))
                    ->execute(($arrWhere ? $arrWhere['params'] : null));
            } else {
                $objDB->prepare($strQueryDelete . ($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : ''))
                    ->execute(($arrWhere ? $arrWhere['params'] : null));
            }
        }

        // Insert the new values - if not empty.
        $strQueryInsert = 'INSERT INTO ' . $this->getValueTable() . ' %s';
        foreach ($arrNewIds as $intId) {
            if (!$arrValues[$intId]['value']['bin'][0]) {
                continue;
            }

            $objDB->prepare($strQueryInsert)
                ->set($this->getSetValues($arrValues[$intId], $intId, $strLangCode))
                ->execute();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $arrValues = parent::getTranslatedDataFor($arrIds, $strLangCode);

        foreach ($arrValues as $intId => $arrValue) {
            $arrValues[$intId]['value'] = ToolboxFile::convertUuidsOrPathsToMetaModels(
                deserialize($arrValue['value'], true)
            );
        }

        return $arrValues;
    }
}
