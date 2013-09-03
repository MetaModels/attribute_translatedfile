<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeTranslatedFile
 * @author      Stefan Heimes <cms@men-at-work.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Dca;

use DcGeneral\DataContainerInterface;
use MetaModels\Helper\ContaoController;

/**
 * Supplementary class for handling DCA information for translated file attributes.
 *
 * @package	   MetaModels
 * @subpackage AttributeTranslatedFile
 * @author     Stefan Heimes <cms@men-at-work.de>
 */
class AttributeTranslatedFile
{
	/**
	 * Return the file picker wizard
	 *
	 * @param \DcGeneral\DataContainerInterface $dc
	 *
	 * @return string
	 */
	public function filePicker(DataContainerInterface $dc)
	{
		$strField = 'ctrl_' . $dc->inputName . ((\Input::getInstance()->get('act') == 'editAll') ? '_' . $dc->id : '');
		return ' ' . ContaoController::getInstance()->generateImage('pickfile.gif', $GLOBALS['TL_LANG']['MSC']['filepicker'], 'style="vertical-align:top;cursor:pointer" onclick="Backend.pickFile(\'' . $strField . '\')"');
	}
}
