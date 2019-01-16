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
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metapalettes']['translatedfile extends default'] = array(
    '+advanced' => array('file_sortBy', 'file_showLink', 'file_showImage'),
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metasubpalettes']['file_showImage'] = array(
    'file_imageSize',
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['file_sortBy'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['file_sortBy'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array('name_asc', 'name_desc', 'date_asc', 'date_desc', 'meta', 'random'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting'],
    'eval'                    => array(
        'tl_class'            => 'w50',
        'chosen'              => true,
    ),
    'sql'                     => 'varchar(32) NOT NULL default \'\''
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['file_showLink'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['file_showLink'],
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class' => 'w50 m12'),
    'sql'                     => 'char(1) NOT NULL default \'\''
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['file_showImage'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['file_showImage'],
    'inputType'               => 'checkbox',
    'eval'                    => array(
        'submitOnChange'      => true,
        'tl_class'            => 'clr',
    ),
    'sql'                     => 'char(1) NOT NULL default \'\''
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['file_imageSize'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['file_imageSize'],
    'exclude'                 => true,
    'inputType'               => 'imageSize',
    'options'                 => $GLOBALS['TL_CROP'],
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => array(
        'rgxp'                => 'digit',
        'includeBlankOption'  => true,
        'nospace'             => true,
        'helpwizard'          => true,
        'tl_class'            => 'w50',
    ),
    'sql'                     => 'varchar(255) NOT NULL default \'\''
);
