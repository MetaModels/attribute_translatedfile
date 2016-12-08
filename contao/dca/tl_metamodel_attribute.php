<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2016 The MetaModels team.
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
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0
 * @filesource
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

/**
 * Add palette configuration.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedfile extends _complexattribute_'] = array(
    '+advanced' => array('file_customFiletree', 'file_multiple'),
    '+display'  => array('-width50'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metasubpalettes']['file_customFiletree'] = array(
    'file_uploadFolder',
    'file_validFileTypes',
    'file_filesOnly',
);

/**
 * Add data provider.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['dca_config']['data_provider']['tl_metamodel_translatedlongblob'] =
    array
    (
        'source' => 'tl_metamodel_translatedlongblob'
    );

/**
 * Add child condition.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['dca_config']['childCondition'][] = array
(
    'from'   => 'tl_metamodel_attribute',
    'to'     => 'tl_metamodel_translatedlongblob',
    'setOn'  => array
    (
        array
        (
            'to_field'   => 'att_id',
            'from_field' => 'id',
        ),
    ),
    'filter' => array
    (
        array
        (
            'local'     => 'att_id',
            'remote'    => 'id',
            'operation' => '=',
        ),
    )
);

/**
 * Add field configuration.
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_customFiletree'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_customFiletree'],
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_multiple'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_multiple'],
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_uploadFolder'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_uploadFolder'],
    'exclude'   => true,
    'inputType' => 'fileTree',
    'eval'      => array('fieldType' => 'radio', 'tl_class' => 'clr'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_validFileTypes'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_validFileTypes'],
    'inputType' => 'text',
    'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_filesOnly'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_filesOnly'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50 m12'),
);
