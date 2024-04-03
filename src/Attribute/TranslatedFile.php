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
 * @author     Oliver Willmes <info@oliverwillmes.de>
 * @copyright  2012-2024 The MetaModels team.
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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use MetaModels\Attribute\ITranslated;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\Helper\ToolboxFile;
use MetaModels\IMetaModel;
use MetaModels\Render\Template;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function is_array;
use function serialize;
use function sprintf;
use function time;
use function trigger_error;
use function trim;

/**
 * This is the MetaModelAttribute class for handling translated file fields.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TranslatedFile extends TranslatedReference
{
    /**
     * The toolbox for file.
     *
     * @var ToolboxFile
     */
    private ToolboxFile $toolboxFile;

    /**
     * The string util.
     *
     * @var Adapter<StringUtil>
     */
    private Adapter $stringUtil;

    /**
     * The validator.
     *
     * @var Adapter<Validator>
     */
    private Adapter $validator;

    /**
     * The repository for files.
     *
     * @var Adapter<FilesModel>
     */
    private Adapter $fileRepository;

    /**
     * The contao configurations.
     *
     * @var Adapter<Config>
     */
    private Adapter $config;

    /**
     * Create a new instance.
     *
     * @param ToolboxFile|null $toolboxFile    The toolbox for file.
     * @param Adapter|null     $stringUtil     The string util.
     * @param Adapter|null     $validator      The validator.
     * @param Adapter|null     $fileRepository The repository for files.
     * @param Adapter|null     $config         The contao configurations.
     */
    public function __construct(
        IMetaModel $objMetaModel,
        array $arrData = [],
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
            @trigger_error(
                'Toolbox file is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $toolboxFile = System::getContainer()->get('metamodels.attribute_file.toolbox.file');
            assert($toolboxFile instanceof ToolboxFile);
        }
        $this->toolboxFile = $toolboxFile;

        if (null === $stringUtil) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'String util file is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $stringUtil = System::getContainer()->get('contao.framework')?->getAdapter(StringUtil::class);
            assert($stringUtil instanceof Adapter);
        }
        $this->stringUtil = $stringUtil;

        if (null === $validator) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Validator is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $validator = System::getContainer()->get('contao.framework')?->getAdapter(Validator::class);
            assert($validator instanceof Adapter);
        }
        $this->validator = $validator;

        if (null === $fileRepository) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'File repository is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $fileRepository = System::getContainer()->get('contao.framework')?->getAdapter(FilesModel::class);
            assert($fileRepository instanceof Adapter);
        }
        $this->fileRepository = $fileRepository;

        if (null === $config) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Config is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $config = System::getContainer()->get('contao.framework')?->getAdapter(Config::class);
            assert($config instanceof Adapter);
        }
        $this->config = $config;
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
     * @param string|string[]      $mixLangCode The language code/s to use, optional.
     *
     * @return array
     *
     * @deprecated This is deprecated since 2.1 and where removed in 3.0.
     *             Implement your own replacement for this.
     */
    protected function getWhere($mixIds, $mixLangCode = '')
    {
        $procedure  = 't.att_id=?';
        $parameters = [$this->get('id')];

        if (null !== $mixIds) {
            if (is_array($mixIds)) {
                $procedure .= ' AND t.item_id IN (' . $this->parameterMask($mixIds) . ')';
                $parameters = array_merge($parameters, $mixIds);
            } else {
                $procedure   .= ' AND t.item_id=?';
                $parameters[] = $mixIds;
            }
        }

        if (is_array($mixLangCode)) {
            $procedure .= ' AND t.langcode IN (' . $this->parameterMask($mixLangCode) . ')';
            $parameters = array_merge($parameters, $mixLangCode);
        } else {
            $procedure   .= ' AND t.langcode=?';
            $parameters[] = $mixLangCode;
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
     * @param string|string[]|null $mixIds      One, none or many ids to use.
     * @param string               $table       The table.
     * @param string|string[]      $mixLangCode The language code/s to use, optional.
     *
     * @return void
     */
    private function addWhere(
        QueryBuilder $builder,
        array|string|null $mixIds,
        string $table,
        array|string $mixLangCode = ''
    ): void {
        $builder
            ->andWhere($builder->expr()->eq($table . '.att_id', ':attributeID'))
            ->setParameter('attributeID', $this->get('id'));

        if (null !== $mixIds) {
            if (is_array($mixIds)) {
                $builder
                    ->andWhere($builder->expr()->in($table . '.item_id', ':itemIDs'))
                    ->setParameter('itemIDs', array_map('intval', $mixIds), ArrayParameterType::INTEGER);
            } else {
                $builder
                    ->andWhere($builder->expr()->eq($table . '.item_id', ':itemID'))
                    ->setParameter('itemID', $mixIds);
            }
        }

        if (is_array($mixLangCode)) {
            $builder
                ->andWhere($builder->expr()->in($table . '.langcode', ':langcodes'))
                ->setParameter('langcodes', array_map('strval', $mixLangCode), ArrayParameterType::STRING);
        } else {
            $builder
                ->andWhere($builder->expr()->eq($table . '.langcode', ':langcode'))
                ->setParameter('langcode', $mixLangCode);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException If no binary in value throw invalid exception.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

        /** @var array{
         *    bin: list<string>,
         *    value: list<string>,
         *    path: list<string>,
         *    meta: list<string>,
         *    bin_sorted?: list<string>,
         *    value_sorted?: list<string>,
         *    path_sorted?: list<string>,
         *    meta_sorted?: list<string>
         *  }|null $value */
        $value = $arrRowData[$this->getColName()]['value'] ?? null;

        if (null === $value) {
            $value = ['bin' => [], 'value' => [], 'path' => [], 'meta' => []];
        }

        // No data and show image, check placeholder.
        if ([] === $value['bin']) {
            if (null === ($placeholder = $objSettings->get('file_placeholder'))) {
                $objTemplate->files = [];
                $objTemplate->src   = [];

                return;
            }

            $value['bin'][]   = $placeholder;
            $value['value'][] = StringUtil::binToUuid($placeholder);
        }

        $toolbox = clone $this->toolboxFile;

        /** @psalm-suppress DeprecatedMethod */
        $toolbox
            ->setBaseLanguage($this->getMetaModel()->getActiveLanguage())
            ->setFallbackLanguage($this->getMetaModel()->getFallbackLanguage())
            ->setLightboxId(
                sprintf(
                    '%s.%s.%s',
                    $this->getMetaModel()->getTableName(),
                    (string) ($objSettings->get('id') ?? ''),
                    (string) ($arrRowData['id'] ?? '0')
                )
            )
            ->setShowImages((bool) $objSettings->get('file_showImage'));

        if (($types = trim($this->get('file_validFileTypes')))) {
            $toolbox->setAcceptedExtensions($types);
        }

        if (is_array($imageSize = $objSettings->get('file_imageSize'))) {
            $toolbox->setResizeImages($imageSize);
        }

        foreach ($value['value'] ?? [] as $strFile) {
            $toolbox->addPathById($strFile);
        }

        $data = [];
        $toolbox->withDownloadKeys(
            (bool) $objSettings->get('file_showLink') && (bool) $objSettings->get('file_protectedDownload')
        );
        $toolbox->resolveFiles();

        if ('manual' !== $objSettings->get('file_sortBy')) {
            $data = $toolbox->sortFiles(($objSettings->get('file_sortBy') ?? ''));
        }
        if ('manual' === $objSettings->get('file_sortBy')) {
            $data = $toolbox->sortFiles(
                ($objSettings->get('file_sortBy') ?? ''),
                ($arrRowData[$this->getColName()]['value_sorting']['bin'] ?? [])
            );
        }

        $objTemplate->files = $data['files'];
        $objTemplate->src   = $data['source'];
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
                'file_widgetMode',
                'mandatory',
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

            if ($this->validator->isUuid($this->get('file_uploadFolder') ?? '')) {
                $file = $this->fileRepository->findByUuid($this->get('file_uploadFolder') ?? '');
            }

            // Check if we have a file.
            if (null !== $file) {
                $fieldDefinition['eval']['path'] = $file->path;
            } else {
                // Fallback.
                $fieldDefinition['eval']['path'] = $this->get('file_uploadFolder');
            }
        }

        if (null !== $this->get('file_validFileTypes')) {
            $fieldDefinition['eval']['extensions'] = $this->get('file_validFileTypes');
        }

        switch ($this->get('file_filesOnly')) {
            case '1':
                // Files only.
                $fieldDefinition['eval']['filesOnly'] = true;
                break;
            case '2':
                // Folders only.
                $fieldDefinition['eval']['files'] = false;
                break;
            default:
                // Files and folders possible.
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

        if (
            ('normal' !== $widgetMode)
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
            'tstamp' => time(),
            'value'  => ToolboxFile::convertUuidsOrPathsToMetaModels((array) $varValue),
            'att_id' => $this->get('id')
        ];
    }

    /**
     * Take the native data and serialize it for the database.
     *
     * @param mixed $mixValues The data to serialize.
     *
     * @return string|null An serialized array with binary data or a binary data.
     */
    private function convert(mixed $mixValues): ?string
    {
        $data = ToolboxFile::convertValuesToDatabase($mixValues);

        // Check single file or multiple file.
        return (bool) $this->get('file_multiple')
            ? serialize($data)
            : ($data[0] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetValues($arrValue, $intId, $strLangCode)
    {
        return [
            'tstamp'   => time(),
            'value'    => $this->convert($arrValue['value']),
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
        $valueIds    = array_keys($arrValues);
        $existingIds = array_keys($this->getTranslatedDataFor($valueIds, $strLangCode));
        $newIds      = array_diff($valueIds, $existingIds);

        // Update existing values - delete if empty.
        $builder = $this->connection->createQueryBuilder();
        foreach ($existingIds as $existingId) {
            $value = $arrValues[$existingId];
            if (array_key_exists('value', $value) && ((bool) ($value['value']['bin'][0] ?? false))) {
                $builder->update($this->getValueTable());
                foreach ($this->getSetValues($value, $existingId, $strLangCode) as $setValueKey => $setValue) {
                    $builder->set($this->getValueTable() . '.' . $setValueKey, ':' . $setValueKey);
                    $builder->setParameter($setValueKey, $setValue);
                }
            } else {
                $builder->delete($this->getValueTable());
            }

            $this->addWhere($builder, $existingId, $this->getValueTable(), $strLangCode);
            $builder->executeQuery();
        }

        // Insert the new values - if not empty.
        $builder = $this->connection->createQueryBuilder();
        $builder->insert($this->getValueTable());

        foreach ($newIds as $newId) {
            $value = $arrValues[$newId];
            if (!(array_key_exists('value', $value) && ((bool) ($value['value']['bin'][0] ?? false)))) {
                continue;
            }

            foreach ($this->getSetValues($value, $newId, $strLangCode) as $setValueKey => $setValue) {
                $builder->setValue($this->getValueTable() . '.' . $setValueKey, ':' . $setValueKey);
                $builder->setParameter($setValueKey, $setValue);
            }
            $builder->executeQuery();
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
            assert($orderAttribute instanceof ITranslated);

            $sortedValues = $orderAttribute->getTranslatedDataFor($arrIds, $strLangCode);
            foreach ($values as $valueId => $value) {
                $values[$valueId]['value_sorting'] = $sortedValues[$valueId]['value_sorting'];
            }
        }

        return $values;
    }
}
