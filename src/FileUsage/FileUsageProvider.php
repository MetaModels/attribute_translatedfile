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
 * @package    MetaModels
 * @subpackage AttributeTranslatedFile
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_file/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\FileUsage;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\StringUtil;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use InspiredMinds\ContaoFileUsage\Provider\FileUsageProviderInterface;
use InspiredMinds\ContaoFileUsage\Result\ResultInterface;
use InspiredMinds\ContaoFileUsage\Result\ResultsCollection;
use MetaModels\AttributeTranslatedFileBundle\Attribute\TranslatedFile;
use MetaModels\CoreBundle\FileUsage\MetaModelsTranslatedMultipleResult;
use MetaModels\CoreBundle\FileUsage\MetaModelsTranslatedSingleResult;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use MetaModels\ITranslatedMetaModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This class supports the Contao extension 'file usage'.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class FileUsageProvider implements FileUsageProviderInterface
{
    private string $refererId = '';

    public function __construct(
        private readonly IFactory $factory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly string $csrfTokenName,
    ) {
    }

    public function find(): ResultsCollection
    {
        $this->refererId = $this->requestStack->getCurrentRequest()?->attributes->get('_contao_referer_id') ?? '';

        $collection = new ResultsCollection();
        foreach ($this->factory->collectNames() as $tableName) {
            $collection->mergeCollection($this->processTable($tableName));
        }

        return $collection;
    }

    private function processTable(string $table): ResultsCollection
    {
        $collection = new ResultsCollection();
        $metaModel  = $this->factory->getMetaModel($table);
        assert($metaModel instanceof IMetaModel);

        $attributes = [];
        foreach ($metaModel->getAttributes() as $attribute) {
            if (!$attribute instanceof TranslatedFile) {
                continue;
            }
            $attributes[] = $attribute;
        }

        $allIds = $metaModel->getIdsFromFilter($metaModel->getEmptyFilter());
        foreach ($this->getLanguagesForMetaModel($metaModel) as $language) {
            foreach ($attributes as $attribute) {
                $allData       = $attribute->getTranslatedDataFor($allIds, $language);
                $attributeName = $attribute->getColName();
                if ($attribute->get('file_multiple')) {
                    foreach ($allData as $itemId => $selectedFiles) {
                        if ([] === $selectedFiles) {
                            continue;
                        }
                        $collection->mergeCollection(
                            $this->addMultipleFileReferences(
                                $selectedFiles['value']['value'],
                                $table,
                                $attributeName,
                                $itemId,
                                $language
                            )
                        );
                    }
                    continue;
                }

                foreach ($allData as $itemId => $selectedFiles) {
                    if ([] === $selectedFiles) {
                        continue;
                    }
                    $collection->addResult(
                        $selectedFiles['value']['value'][0],
                        $this->createFileResult($table, $attributeName, $itemId, $language, false)
                    );
                }
            }
        }

        return $collection;
    }

    /** @return iterable<int, string> */
    private function getLanguagesForMetaModel(IMetaModel $metaModel): iterable
    {
        if ($metaModel instanceof ITranslatedMetaModel) {
            foreach ($metaModel->getLanguages() as $language) {
                yield $language;
            }
            return;
        }
        /**
         * @psalm-suppress DeprecatedMethod
         * @psalm-suppress TooManyArguments
         */
        if ($metaModel->isTranslated(false)) {
            foreach ($metaModel->getAvailableLanguages() ?? [] as $language) {
                yield $language;
            }
        }
    }

    private function addMultipleFileReferences(
        array $fileUuids,
        string $tableName,
        string $attributeName,
        string $itemId,
        string $language,
    ): ResultsCollection {
        $collection = new ResultsCollection();
        foreach ($fileUuids as $uuid) {
            $collection->addResult(
                $uuid,
                $this->createFileResult($tableName, $attributeName, $itemId, $language, true)
            );
            // Also add children, if the reference is a folder.
            $file = FilesModel::findByUuid($uuid);
            if (null !== $file && 'folder' === $file->type) {
                $files = FilesModel::findByPid($uuid);
                if (null === $files) {
                    continue;
                }
                assert($files instanceof Collection);
                foreach ($files as $child) {
                    $collection->addResult(
                        StringUtil::binToUuid($child->uuid),
                        $this->createFileResult($tableName, $attributeName, $itemId, $language, true)
                    );
                }
            }
        }

        return $collection;
    }

    private function createFileResult(
        string $tableName,
        string $attributeName,
        string $itemId,
        string $language,
        bool $isMultiple
    ): ResultInterface {
        if ($isMultiple) {
            return new MetaModelsTranslatedMultipleResult(
                $tableName,
                $attributeName,
                $itemId,
                $language,
                $this->urlGenerator->generate(
                    'metamodels.metamodel',
                    [
                        'tableName' => $tableName,
                        'act'       => 'edit',
                        'id'        => ModelId::fromValues($tableName, $itemId)->getSerialized(),
                        'language'  => $language,
                        'ref'       => $this->refererId,
                        'rt'        => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
                    ]
                )
            );
        }

        return new MetaModelsTranslatedSingleResult(
            $tableName,
            $attributeName,
            $itemId,
            $language,
            $this->urlGenerator->generate(
                'metamodels.metamodel',
                [
                    'tableName' => $tableName,
                    'act'       => 'edit',
                    'id'        => ModelId::fromValues($tableName, $itemId)->getSerialized(),
                    'language'  => $language,
                    'ref'       => $this->refererId,
                    'rt'        => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
                ]
            )
        );
    }
}
