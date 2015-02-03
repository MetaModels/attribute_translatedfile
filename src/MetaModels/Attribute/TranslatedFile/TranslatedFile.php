<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage AttributeTranslatedFile
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     cogizz <info@cogizz.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     David Greminger <david.greminger@1up.io>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedFile;

use MetaModels\Attribute\TranslatedReference;
use MetaModels\Helper\ToolboxFile;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling translated file fields.
 *
 * @package     MetaModels
 * @subpackage  AttributeText
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
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
     * {@inheritdoc}
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings = null)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

        $objToolbox = new ToolboxFile();

        $objToolbox->setBaseLanguage($this->getMetaModel()->getActiveLanguage());

        $objToolbox->setFallbackLanguage($this->getMetaModel()->getFallbackLanguage());

        $objToolbox->setLightboxId($this->getMetaModel()->getTableName().'.'.$objSettings->id.'.'.$arrRowData['id']);

        if (strlen($this->get('file_validFileTypes'))) {
            $objToolbox->setAcceptedExtensions($this->get('file_validFileTypes'));
        }

        $objToolbox->setShowImages($objSettings->get('file_showImage'));

        if ($objSettings->get('file_imageSize')) {
            $objToolbox->setResizeImages($objSettings->get('file_imageSize'));
        }

        if ($arrRowData[$this->getColName()]) {
            if (isset($arrRowData[$this->getColName()]['value'])) {
                foreach ($arrRowData[$this->getColName()]['value'] as $strFile) {
                    $objToolbox->addPathById($strFile);
                }
            } elseif (is_array($arrRowData[$this->getColName()])) {
                foreach ($arrRowData[$this->getColName()] as $strFile) {
                    $objToolbox->addPathById($strFile);
                }
            } else {
                $objToolbox->addPathById($arrRowData[$this->getColName()]);
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
            'file_filePicker',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType']          = 'fileTree';
        $arrFieldDef['eval']['files']      = true;
        $arrFieldDef['eval']['fieldType']  = $this->get('file_multiple') ? 'checkbox' : 'radio';
        $arrFieldDef['eval']['multiple']   = $this->get('file_multiple') ? true : false;
        $arrFieldDef['eval']['extensions'] = $this->getAllowedDownloadTypes();

        if ($this->get('file_customFiletree')) {
            if (strlen($this->get('file_uploadFolder'))) {
                // Set root path of file chooser depending on contao version.
                $objFile = null;
                // Contao 3.1.x use the numeric values.
                if (is_numeric($this->get('file_uploadFolder'))) {
                    $objFile = \FilesModel::findByPk($this->get('file_uploadFolder'));
                } elseif (strlen($this->get('file_uploadFolder')) == 16) {
                    $objFile = \FilesModel::findByUuid($this->get('file_uploadFolder'));
                }

                // Check if we have a file.
                if ($objFile != null) {
                    $arrFieldDef['eval']['path'] = $objFile->path;
                } else {
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

        // Set all options for the file picker.
        if ($this->get('file_filePicker') && !$this->get('file_multiple')) {
            $arrFieldDef['inputType']         = 'text';
            $arrFieldDef['eval']['tl_class'] .= ' wizard';
            $arrFieldDef['wizard']            = array(
                array('TableMetaModelsAttributeTranslatedFile', 'filePicker'),
            );
        }

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (!$this->get('file_filePicker')) {
            return deserialize($varValue['value']);
        }
        $strValue = is_array($varValue['value']) ? $varValue['value'][0] : $varValue['value'];

        $objToolbox = new ToolboxFile();

        return $objToolbox->convertValueToPath($strValue);
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        if ($this->get('file_filePicker')) {
            $objFile  = \Dbafs::addResource($varValue);
            $varValue = $objFile->id;
        }

        return array(
            'tstamp' => time(),
            'value' => $varValue,
            'att_id' => $this->get('id'),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetValues($arrValue, $intId, $strLangCode)
    {
        if (is_array($arrValue['value']) && count($arrValue['value']) != 0) {
            $arrReturn = array(
                'tstamp'   => time(),
                'value'    => serialize($arrValue['value']),
                'att_id'   => $this->get('id'),
                'langcode' => $strLangCode,
                'item_id'  => $intId,
            );
        } elseif (!is_array($arrValue['value']) && strlen($arrValue['value']) != 0) {
            $arrReturn = array(
                'tstamp'   => time(),
                'value'    => $arrValue['value'],
                'att_id'   => $this->get('id'),
                'langcode' => $strLangCode,
                'item_id'  => $intId,
            );
        } else {
            $arrReturn = array(
                'tstamp'   => time(),
                'value'    => null,
                'att_id'   => $this->get('id'),
                'langcode' => $strLangCode,
                'item_id'  => $intId,
            );
        }

        return $arrReturn;
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $arrValues = parent::getTranslatedDataFor($arrIds, $strLangCode);

        foreach ($arrValues as $intId => $arrValue) {
            $arrValue['value']          = deserialize($arrValue['value'], true);
            $arrValues[$intId]['value'] = array();

            foreach ((array) $arrValue['value'] as $mixFiles) {
                $arrValues[$intId]['path'][]  = \FilesModel::findByPk($mixFiles)->path;
                $arrValues[$intId]['value'][] = \String::binToUuid($mixFiles);
            }
        }

        return $arrValues;
    }

    /**
     * Returns the METAMODELS_SYSTEM_COLUMNS (replacement for super globals access).
     *
     * @return array METAMODELS_SYSTEM_COLUMNS
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getAllowedDownloadTypes()
    {
        return $GLOBALS['TL_CONFIG']['allowedDownload'];
    }
}
