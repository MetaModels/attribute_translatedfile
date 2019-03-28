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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedFile;

use MetaModels\Attribute\IAttributeTypeFactory;

/**
 * Attribute type factory for file order attributes.
 */
class AttributeOrderTypeFactory implements IAttributeTypeFactory
{
    /**
     * {@inheritDoc}
     */
    public function getTypeName()
    {
        return 'translatedfilesort';
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeIcon()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new TranslatedFileOrder($metaModel, $information);
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
