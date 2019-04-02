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
use Contao\CoreBundle\Framework\Adapter;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Query\QueryBuilder;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\AttributeFileBundle\Doctrine\DBAL\Platforms\Keywords\NotSupportedKeywordList;
use MetaModels\Helper\ToolboxFile;
use MetaModels\IMetaModel;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling translated file fields.
 */
class TranslatedFile extends TranslatedReference
{
    /**
     * The toolbox for file.
     *
     * @var ToolboxFile|null
     */
    private $toolboxFile;

    /**
     * The string util.
     *
     * @var Adapter|StringUtil|null
     */
    private $stringUtil;

    /**
     * The validator.
     *
     * @var Adapter|Validator|null
     */
    private $validator;

    /**
     * The repository for files.
     *
     * @var Adapter|FilesModel|null
     */
    private $fileRepository;

    /**
     * The contao configurations.
     *
     * @var Adapter|Config|null
     */
    private $config;

    /**
     * The platform reserved keyword list.
     *
     * @var KeywordList
     */
    private $platformReservedWord;

    /**
     * {@inheritDoc}
     *
     * @param ToolboxFile|null        $toolboxFile    The toolbox for file.
     * @param Adapter|StringUtil|null $stringUtil     The string util.
     * @param Adapter|Validator|null  $validator      The validator.
     * @param Adapter|FilesModel|null $fileRepository The repository for files.
     * @param Adapter|Config|null     $config         The contao configurations.
     */
    public function __construct(
        IMetaModel $objMetaModel,
        $arrData = [],
        Connection $connection = null,
        ToolboxFile $toolboxFile = null,
        Adapter $stringUtil = null,
        Adapter $validator = null,
        Adapter $fileRepository = null,
        Adapter $config = null
    ) {
        parent::__construct($objMetaModel, $arrData, $connection);

        if (null === $toolboxFile) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'Toolbox file is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $toolboxFile = System::getContainer()->get('metamodels.attribute_file.toolbox.file');
        }

        if (null === $stringUtil) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'String util file is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $stringUtil = System::getContainer()->get('contao.framework')->getAdapter(StringUtil::class);
        }

        if (null === $validator) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'Validator is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $validator = System::getContainer()->get('contao.framework')->getAdapter(Validator::class);
        }

        if (null === $fileRepository) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'File repository is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $fileRepository = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class);
        }

        if (null === $config) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'Config is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $config = System::getContainer()->get('contao.framework')->getAdapter(Config::class);
        }

        $this->toolboxFile    = $toolboxFile;
        $this->stringUtil     = $stringUtil;
        $this->validator      = $validator;
        $this->fileRepository = $fileRepository;
        $this->config         = $config;
    }

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
     *
     * @deprecated This is deprecated since 2.1 and where removed in 3.0.
     *             Implement your own replacement for this.
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
            ->andWhere($builder->expr()->eq($this->quoteReservedWord('att_id'), ':attributeID'))
            ->setParameter(':attributeID', $this->get('id'));

        if (!empty($mixIds)) {
            if (\is_array($mixIds)) {
                $builder
                    ->andWhere($builder->expr()->in($this->quoteReservedWord('item_id'), ':itemIDs'))
                    ->setParameter('itemIDs', \array_map('intval', $mixIds), Connection::PARAM_INT_ARRAY);
            } else {
                $builder
                    ->andWhere($builder->expr()->eq($this->quoteReservedWord('item_id'), ':itemID'))
                    ->setParameter('itemID', $mixIds);
            }
        }

        if (!empty($mixLangCode)) {
            if (\is_array($mixLangCode)) {
                $builder
                    ->andWhere($builder->expr()->in($this->quoteReservedWord('langcode'), ':langcodes'))
                    ->setParameter('langcodes', \array_map('strval', $mixLangCode), Connection::PARAM_STR_ARRAY);
            } else {
                $builder
                    ->andWhere($builder->expr()->eq($this->quoteReservedWord('langcode'), ':langcode'))
                    ->setParameter('langcode', $mixLangCode);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException If no binary in value throw invalid exception.
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

        $toolbox = clone $this->toolboxFile;
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

            if ($this->validator->isStringUuid($this->get('file_uploadFolder'))
                || $this->validator->isBinaryUuid($this->get('file_uploadFolder'))
            ) {
                $file = $this->fileRepository->findByUuid($this->get('file_uploadFolder'));
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
        $fieldDefinition['eval']['extensions'] = $this->config->get('allowedDownload');
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
            $this->quoteReservedWord('tstamp') => \time(),
            $this->quoteReservedWord('value')  => ToolboxFile::convertUuidsOrPathsToMetaModels((array) $varValue),
            $this->quoteReservedWord('att_id') => $this->get('id')
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
        // First off determine those to be updated and those to be inserted.
        $valueIds    = \array_keys($arrValues);
        $existingIds = \array_keys($this->getTranslatedDataFor($valueIds, $strLangCode));
        $newIds      = \array_diff($valueIds, $existingIds);

        // Update existing values - delete if empty.
        foreach ($existingIds as $existingId) {
            $builder = $this->connection->createQueryBuilder();

            if ($arrValues[$existingId]['value']['bin'][0]) {
                $setValues = $this->getSetValues($arrValues[$existingId], $existingId, $strLangCode);
                $builder->update($this->quoteReservedWord($this->getValueTable()));
                foreach ($setValues as $setValueKey => $setValue) {
                    $builder->set($this->quoteReservedWord($setValueKey), ':' . $setValueKey);
                    $builder->setParameter(':' . $setValueKey, $setValue);
                }
            } else {
                $builder->delete($this->quoteReservedWord($this->getValueTable()));
            }
            
            $this->addWhere($builder, $existingId, $strLangCode);
            $builder->execute();
        }

        // Insert the new values - if not empty.
        foreach ($newIds as $newId) {
            if (!$arrValues[$newId]['value']['bin'][0]) {
                continue;
            }

            $setValues = [];
            foreach ($this->getSetValues($arrValues[$newId], $newId, $strLangCode) as $setValueKey => $setValue) {
                $setValues[$this->quoteReservedWord($setValueKey)] = $setValue;
            }

            $this->connection->insert($this->quoteReservedWord($this->getValueTable()), $setValues);
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
                $this->stringUtil->deserialize($value['value'], true)
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
