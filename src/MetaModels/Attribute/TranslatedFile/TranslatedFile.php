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
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedFile;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\DcGeneral\Events\TranslatedFileWizardHandler;
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

        // Set all options for the file picker.
        if (version_compare(VERSION, '3.3', '<') && $this->get('file_filePicker') && !$this->get('file_multiple')) {
            $arrFieldDef['inputType']         = 'text';
            $arrFieldDef['eval']['tl_class'] .= ' wizard';

            $dispatcher = $this->getMetaModel()->getServiceContainer()->getEventDispatcher();
            $dispatcher->addListener(
                ManipulateWidgetEvent::NAME,
                array(new TranslatedFileWizardHandler($this->getMetaModel(), $this->getColName()), 'getWizard')
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
}
