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
 * @author     Sven Baumann <baumann.sv@gmail.com>
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
use Contao\Validator;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\Helper\ToolboxFile;

/**
 * Attribute type factory for translated combined values attributes.
 */
class AttributeTypeFactory implements IAttributeTypeFactory
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;
    /**
     * The toolbox for file.
     *
     * @var ToolboxFile
     */
    private $toolboxFile;

    /**
     * The string util.
     *
     * @var Adapter|StringUtil
     */
    private $stringUtil;

    /**
     * The validator.
     *
     * @var Adapter|Validator
     */
    private $validator;

    /**
     * The repository for files.
     *
     * @var Adapter|FilesModel
     */
    private $fileRepository;

    /**
     * The contao configurations.
     *
     * @var Adapter|Config
     */
    private $config;

    /**
     * Create a new instance.
     *
     * @param Connection         $connection     Database connection.
     * @param ToolboxFile        $toolboxFile    The toolbox for file.
     * @param Adapter|StringUtil $stringUtil     The string util.
     * @param Adapter|Validator  $validator      The validator.
     * @param Adapter|FilesModel $fileRepository The repository for files.
     * @param Adapter|Config     $config         The contao configurations.
     */
    public function __construct(
        Connection $connection,
        ToolboxFile $toolboxFile,
        Adapter $stringUtil,
        Adapter $validator,
        Adapter $fileRepository,
        Adapter $config
    ) {
        $this->connection     = $connection;
        $this->toolboxFile    = $toolboxFile;
        $this->stringUtil     = $stringUtil;
        $this->validator      = $validator;
        $this->fileRepository = $fileRepository;
        $this->config         = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeName()
    {
        return 'translatedfile';
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeIcon()
    {
        return 'bundles/metamodelsattributetranslatedfile/file.png';
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new TranslatedFile(
            $metaModel,
            $information,
            $this->connection,
            $this->toolboxFile,
            $this->stringUtil,
            $this->validator,
            $this->fileRepository,
            $this->config
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isTranslatedType()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isSimpleType()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isComplexType()
    {
        return true;
    }
}
