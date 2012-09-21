<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Table tl_metamodel_attribute 
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedfile extends _complexattribute_'] = array
(
        '+advanced' => array('file_showImage', 'file_customFiletree', 'file_multiple'),
	'+backenddisplay'	=> array('-width50'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metasubpalettes']['file_customFiletree'] = array
(
	'file_uploadFolder', 'file_validFileTypes', 'file_filesOnly'
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_customFiletree'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_customFiletree'],
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr')
);


$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_multiple'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_multiple'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'clr')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_uploadFolder'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_uploadFolder'],
	'exclude'                 => true,
	'inputType'               => 'fileTree',
	'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_validFileTypes'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_validFileTypes'],
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['file_filesOnly'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['file_filesOnly'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 m12')
);

?>