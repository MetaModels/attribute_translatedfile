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
 * @author     Stefan heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     cogizz <info@cogizz.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Attribute;

use Contao\Config;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\Validator;
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

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException If no binary in value throw invalid exception.
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

        $toolbox = new ToolboxFile();
        $toolbox
            ->setBaseLanguage($this->getMetaModel()->getActiveLanguage())
            ->setFallbackLanguage($this->getMetaModel()->getFallbackLanguage())
            ->setLightboxId(
                \sprintf(
                    '%s.%s.%s',
                    $this->getMetaModel()->getTableName(),
                    $objSettings->get('id'),
                    $arrRowData['id']
                )
            )
            ->setShowImages($objSettings->get('file_showImage'));

        if (($types = \trim($this->get('file_validFileTypes')))) {
            $toolbox->setAcceptedExtensions($types);
        }

        if ($objSettings->get('file_imageSize')) {
            $toolbox->setResizeImages($objSettings->get('file_imageSize'));
        }

        if ($arrRowData[$this->getColName()]) {
            if (!isset($arrRowData[$this->getColName()]['value']['bin'])) {
                throw new \InvalidArgumentException('No binary in value.');
            }

            foreach ($arrRowData[$this->getColName()]['value']['bin'] as $strFile) {
                $toolbox->addPathById($strFile);
            }
        }

        $arrData = [];
        $toolbox->resolveFiles();
        if ('manual' !== $objSettings->get('file_sortBy')) {
            $arrData = $toolbox->sortFiles($objSettings->get('file_sortBy'));
        }
        if ('manual' === $objSettings->get('file_sortBy')) {
            $arrData = $toolbox->sortFiles(
                $objSettings->get('file_sortBy'),
                ($arrRowData[$this->getColName()]['value_sorting']['bin'] ?? [])
            );
        }

        $objTemplate->files = $arrData['files'];
        $objTemplate->src   = $arrData['source'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
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
     * Manipulate the field definition for custom file trees.
     *
     * @param array $fieldDefinition The field definition to manipulate.
     *
     * @return void
     */
    private function handleCustomFileTree(&$fieldDefinition)
    {
        if ($this->get('file_uploadFolder')) {
            // Set root path of file chooser depending on contao version.
            $file = null;

            if (Validator::isStringUuid($this->get('file_uploadFolder'))
                || Validator::isBinaryUuid($this->get('file_uploadFolder'))
            ) {
                $file = FilesModel::findByUuid($this->get('file_uploadFolder'));
            }

            // Check if we have a file.
            if (null !== $file) {
                $fieldDefinition['eval']['path'] = $file->path;
            } else {
                // Fallback.
                $fieldDefinition['eval']['path'] = $this->get('file_uploadFolder');
            }
        }

        if ($this->get('file_validFileTypes')) {
            $fieldDefinition['eval']['extensions'] = $this->get('file_validFileTypes');
        }

        if ($this->get('file_filesOnly')) {
            $fieldDefinition['eval']['filesOnly'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $fieldDefinition = parent::getFieldDefinition($arrOverrides);

        $fieldDefinition['inputType']          = 'fileTree';
        $fieldDefinition['eval']['files']      = true;
        $fieldDefinition['eval']['extensions'] = Config::get('allowedDownload');
        $fieldDefinition['eval']['multiple']   = (bool) $this->get('file_multiple');

        $widgetMode = $this->getOverrideValue('file_widgetMode', $arrOverrides);

        if (('normal' !== $widgetMode)
            && ((bool) $this->get('file_multiple'))
        ) {
            $fieldDefinition['eval']['orderField'] = $this->getColName() . '__sort';
        }

        $fieldDefinition['eval']['isDownloads'] = ('downloads' === $widgetMode);
        $fieldDefinition['eval']['isGallery']   = ('gallery' === $widgetMode);

        if ($this->get('file_multiple')) {
            $fieldDefinition['eval']['fieldType'] = 'checkbox';
        } else {
            $fieldDefinition['eval']['fieldType'] = 'radio';
        }

        if ($this->get('file_customFiletree')) {
            $this->handleCustomFileTree($fieldDefinition);
        }

        return $fieldDefinition;
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (empty($varValue) || empty($varValue['value'])) {
            return null;
        }

        return $this->get('file_multiple')
                ? $varValue['value']['bin']
                    : ($varValue['value']['bin'][0] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        return [
            'tstamp' => \time(),
            'value'  => ToolboxFile::convertUuidsOrPathsToMetaModels((array) $varValue),
            'att_id' => $this->get('id')
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
        return $this->get('file_multiple')
                ? \serialize($data)
                    : ($data[0] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetValues($arrValue, $intId, $strLangCode)
    {
        return [
            'tstamp'   => \time(),
            'value'    => (!empty($arrValue)) ? $this->convert($arrValue['value']) : null,
            'att_id'   => $this->get('id'),
            'langcode' => $strLangCode,
            'item_id'  => $intId
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        $database = $this->getMetaModel()->getServiceContainer()->getDatabase();
        // First off determine those to be updated and those to be inserted.
        $valueIds    = \array_keys($arrValues);
        $existingIds = \array_keys($this->getTranslatedDataFor($valueIds, $strLangCode));
        $newIds      = \array_diff($valueIds, $existingIds);

        // Update existing values - delete if empty.
        $queryUpdate = 'UPDATE ' . $this->getValueTable() . ' %s';
        $queryDelete = 'DELETE FROM ' . $this->getValueTable();

        foreach ($existingIds as $existingId) {
            $whereParts = $this->getWhere($existingId, $strLangCode);

            if ($arrValues[$existingId]['value']['bin'][0]) {
                $database->prepare($queryUpdate . ($whereParts ? ' WHERE ' . $whereParts['procedure'] : ''))
                    ->set($this->getSetValues($arrValues[$existingId], $existingId, $strLangCode))
                    ->execute(($whereParts ? $whereParts['params'] : null));
            } else {
                $database->prepare($queryDelete . ($whereParts ? ' WHERE ' . $whereParts['procedure'] : ''))
                    ->execute(($whereParts ? $whereParts['params'] : null));
            }
        }

        // Insert the new values - if not empty.
        $queryInsert = 'INSERT INTO ' . $this->getValueTable() . ' %s';
        foreach ($newIds as $newId) {
            if (!$arrValues[$newId]['value']['bin'][0]) {
                continue;
            }

            $database->prepare($queryInsert)
                ->set($this->getSetValues($arrValues[$newId], $newId, $strLangCode))
                ->execute();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $metaModel = $this->getMetaModel();

        $values = parent::getTranslatedDataFor($arrIds, $strLangCode);
        foreach ($values as $valueId => $value) {
            $values[$valueId]['value'] = ToolboxFile::convertUuidsOrPathsToMetaModels(
                StringUtil::deserialize($value['value'], true)
            );
        }

        if ($metaModel->hasAttribute($this->getColName() . '__sort')) {
            $orderAttribute = $metaModel->getAttribute($this->getColName() . '__sort');

            $sortedValues = $orderAttribute->getTranslatedDataFor($arrIds, $strLangCode);
            foreach ($values as $valueId => $value) {
                $values[$valueId]['value_sorting'] = $sortedValues[$valueId]['value_sorting'];
            }
        }

        return $values;
    }
}
