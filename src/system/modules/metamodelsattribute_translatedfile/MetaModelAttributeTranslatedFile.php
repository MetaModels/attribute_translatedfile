<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeText
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright   CyberSpectrum
 * @license     private
 * @filesource
 */

/**
 * This is the MetaModelAttribute class for handling translated long text fields.
 *
 * @package     MetaModels
 * @subpackage  AttributeText
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class MetaModelAttributeTranslatedFile
extends MetaModelAttributeComplex
implements IMetaModelAttributeTranslated
{
        protected $arrMeta = array();
	protected $arrAux = array();
	protected $arrProcessed = array();
	protected $auxDate = array();
	protected $multiSRC = array();
        
        
        /////////////////////////////////////////////////////////
	// Helper
	/////////////////////////////////////////////////////////
        
        /**
	 * Parse the meta.txt file of a folder. This is an altered version and differs from the
	 * Contao core funtion as it also checks the fallback language.
	 *
	 * @param string $strPath the path where to look for the meta.txt
	 *
	 * @return void
	 */
	protected function parseMetaFile($strPath)
	{
		if (in_array($strPath, $this->arrProcessed))
		{
			return;
		}

		$strFile = $strPath . '/meta_' . $this->getMetaModel()->getActiveLanguage() . '.txt';

		if (!file_exists(TL_ROOT . '/' . $strFile))
		{
			$strFile = $strPath . '/meta_' . $this->getMetaModel()->getFallbackLanguage() . '.txt';

			if (!file_exists(TL_ROOT . '/' . $strFile))
			{
				$strFile = $strPath . '/meta.txt';

				if (!file_exists(TL_ROOT . '/' . $strFile))
				{
					return;
				}
			}
		}

		$strBuffer = file_get_contents(TL_ROOT . '/' . $strFile);
		$strBuffer = utf8_convert_encoding($strBuffer, $GLOBALS['TL_CONFIG']['characterSet']);
		$arrBuffer = array_filter(trimsplit('[\n\r]+', $strBuffer));

		foreach ($arrBuffer as $v)
		{
			list($strLabel, $strValue) = array_map('trim', explode('=', $v, 2));
			$this->arrMeta[$strPath][$strLabel] = array_map('trim', explode('|', $strValue));
			$this->arrAux[] = $strPath . '/' . $strLabel;
		}
		$this->arrProcessed[] = $strPath;
	}

	protected function renderFile($strFile, $objSettings, $strId)
	{
		if (!file_exists(TL_ROOT . '/' . $strFile))
		{
			return;
		}

		$allowedDownload = trimsplit(',', strtolower($GLOBALS['TL_CONFIG']['allowedDownload']));
		if (strlen($this->get('file_validFileTypes')))
		{
			$extensions = trimsplit(',', strtolower($this->get('file_validFileTypes')));
			$allowedDownload = array_intersect($allowedDownload, $extensions);
		}

		$objFile = new File($strFile);
		// check if we want to show as image or if the file is allowed for download.
		if (!(in_array($objFile->extension, $allowedDownload)) || $showImage)
		{
			return;
		}


		// TODO: maybe we want to provide a better option to send the files here as in Catalog v2.0 but for the moment this is the best position.
		// send the file to browser if download is requested.
		if ((!$objSettings->file_showImage) && (Input::getInstance()->get('file') == $strFile))
		{
			MetaModelController::sendFileToBrowser($strFile);
		}

		$arrMeta = array();

		$showImage = $objFile->isGdImage && $objSettings->file_showImage;

		$this->parseMetaFile(dirname($strFile), true);

		$arrMeta =$this->arrMeta[dirname($strFile)][$objFile->basename];

		$strBasename = strlen($arrMeta[0]) ? $arrMeta[0] : specialchars($objFile->basename);
		$strAltText = (strlen($arrMeta[0]) ? $arrMeta[0] : ucfirst(str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename))));

		$this->auxDate[] = $objFile->mtime;

		$strIcon = 'system/themes/' . MetaModelController::getTheme() . '/images/' . $objFile->icon;
		$arrSource = array
		(
			'file'	=> $strFile,
			'mtime'	=> $objFile->mtime,
			'alt'	=> $strAltText,
			'caption' => (strlen($arrMeta[2]) ? $arrMeta[2] : ''),
			'title' => $strBasename,
			'metafile' => $arrMeta,
			'icon' => $strIcon,
			'size' => $objFile->filesize,
			'sizetext' => sprintf('(%s)', MetaModelController::getReadableSize($objFile->filesize, 2)),
			'url' => Environment::getInstance()->request . (($GLOBALS['TL_CONFIG']['disableAlias'] || !$GLOBALS['TL_CONFIG']['rewriteURL']
&& count($_GET) || strlen($_GET['page'])) ? '&amp;' : '?'). 'file=' . MetaModelController::urlEncode($strFile)
		);

		// images
		if ($objFile->isGdImage)
		{
			$intWidth = $objSettings->file_imageSize[0] ? $objSettings->file_imageSize[0] : '';
			$intHeight = $objSettings->file_imageSize[1] ? $objSettings->file_imageSize[1] : '';
			$strMode = $objSettings->file_imageSize[2] ? $objSettings->file_imageSize[2] : '';

			if ($showImage)
			{
				$strSrc = MetaModelController::getImage(MetaModelController::urlEncode($strFile), $intWidth, $intHeight, $strMode);
			} else {
				$strSrc = $strFile;
			}
			$arrSource['src'] = $strSrc;

			$size = getimagesize(TL_ROOT . '/' . urldecode($strSrc));
			$arrSource['lb'] = 'lb'.$strId;
			$arrSource['w'] = $size[0];
			$arrSource['h'] = $size[1];
			$arrSource['wh'] = $size[3];
		}

		return $arrSource;
	}
        
        /////////////////////////////////////////////////////////
	// interface IMetaModelAttribute
	/////////////////////////////////////////////////////////

	public function getAttributeSettingNames()
	{
		return array_merge(parent::getAttributeSettingNames(), array(
			'file_multiple',
			'file_customFiletree',
			'file_uploadFolder',
			'file_validFileTypes',
			'file_filesOnly',
		));
	}

	public function getFieldDefinition()
	{
		$arrFieldDef=parent::getFieldDefinition();
		$arrFieldDef['inputType'] = 'fileTree';

		$arrFieldDef['eval']['files'] = true;

		$arrFieldDef['eval']['fieldType'] = $this->get('file_multiple') ? 'checkbox' : 'radio';

		$arrFieldDef['eval']['extensions'] = $GLOBALS['TL_CONFIG']['allowedDownload'];

		if ($this->get('file_customFiletree'))
		{
			if (strlen($this->get('file_uploadFolder')))
			{
				$arrFieldDef['eval']['path'] = $this->get('file_uploadFolder');
			}
			if (strlen($this->get('file_validFileTypes')))
			{
				$arrFieldDef['eval']['extensions'] = $this->get('file_validFileTypes');
			}
			if (strlen($this->get('file_filesOnly')))
			{
				$arrFieldDef['eval']['filesOnly'] = true;
			}
		}
		return $arrFieldDef;
	}

	public function valueToWidget($varValue)
	{
		return $varValue['value'];
	}

	public function getDataFor($arrIds)
	{
		$strActiveLanguage = $this->getMetaModel()->getActiveLanguage();
		$strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();

		$arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

		// second round, fetch fallback languages if not all items could be resolved.
		if ((count($arrReturn) < count($arrIds)) && ($strActiveLanguage != $strFallbackLanguage))
		{
			$arrFallbackIds = array();
			foreach ($arrIds as $intId)
			{
				if (empty($arrReturn[$intId]))
				{
					$arrFallbackIds[] = $intId;
				}
			}

			if ($arrFallbackIds)
			{
				$arrFallbackData = $this->getTranslatedDataFor($arrFallbackIds, $strFallbackLanguage);
				// cannot use array_merge here as it would renumber the keys.
				foreach ($arrFallbackData as $intId => $arrValue)
				{
					$arrReturn[$intId] = $arrValue;
				}
			}
		}
		return $arrReturn;
	}
        
        

	public function setDataFor($arrValues)
	{
		foreach ($this->getMetaModel()->getAvailableLanguages() as $strLangCode)
		{
			$this->setTranslatedDataFor($arrValues, $strLangCode);
		}
	}

	public function unsetDataFor($arrIds)
	{
		foreach ($this->getMetaModel()->getAvailableLanguages() as $strLangCode)
		{
			$this->unsetValueFor($arrIds, $strLangCode);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * Fetch filter options from foreign table.
	 *
	 */
	public function getFilterOptions($arrIds = array())
	{
		$objDB = Database::getInstance();

		if ($arrIds)
		{
			$strWhereIds = ' AND item_id IN (' . implode(',', $arrIds) . ')';
		}

		$objValue = $objDB->prepare('SELECT * FROM tl_metamodel_translatedlongtext WHERE att_id=? AND langcode=? ' . $strWhereIds)
				->execute($this->get('id'), $this->getMetaModel()->getActiveLanguage());

		$arrReturn = array();
		while ($objValue->next())
		{
			$arrReturn[$objValue->value] = $objValue->value;
		}
		return $arrReturn;
	}
        
        /**
	 * when rendered via a template, this returns the values to be stored in the template.
	 */
	protected function prepareTemplate(MetaModelTemplate $objTemplate, $arrRowData, $objSettings = null)
	{
		parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

		$strId = $this->getMetaModel()->getTableName() . '.' . $arrRowData['id'];

		$this->auxDate = array();

		$arrFiles = array();

		foreach ((array)$arrRowData[$this->getColName()] as $strFile)
		{
			if (is_file(TL_ROOT . '/' . $strFile))
			{
				$arrFiles[] = $strFile;
				$arrSource[] = $this->renderFile($strFile, $objSettings, $strId);
			}
			else if (is_dir(TL_ROOT . '/' . $strFile))
			{
				// Folders
				$arrSubFiles = scan(TL_ROOT . '/' . $strFile);
				foreach ($arrSubFiles as $strSubfile)
				{
					if (is_file(TL_ROOT . '/' . $strFile . '/' . $strSubfile))
					{
						$arrFiles[] = $strFile . '/' . $strSubfile;
						$arrSource[] = $this->renderFile($strFile . '/' . $strSubfile, $objSettings, $strId);
					}
				}
			}
		}

		$files = array();
		$source = array();
		$values = array();

		switch ($objSettings->file_sortBy)
		{
			default:
			case 'name_asc':
				uksort($arrFiles, 'basename_natcasecmp');
				break;

			case 'name_desc':
				uksort($arrFiles, 'basename_natcasercmp');
				break;

			case 'date_asc':
				array_multisort($arrFiles, SORT_NUMERIC, $this->auxDate, SORT_ASC);
				break;

			case 'date_desc':
				array_multisort($arrFiles, SORT_NUMERIC, $this->auxDate, SORT_DESC);
				break;

			case 'meta':
				foreach ($this->arrAux as $aux)
				{
					$k = array_search($aux, $arrFiles);
					if ($k !== false)
					{
						$files[] = $arrFiles[$k];
						$source[] = $arrSource[$k];
					}
				}
				break;

			case 'random':
				$keys = array_keys($arrFiles);
				shuffle($keys);
				foreach($keys as $key)
				{
					$files[$key] = $arrFiles[$key];
				}
				$arrFiles = $files;
				break;
		}
		if ($objSettings->file_sortBy != 'meta')
		{
			// re-sort the values
			foreach($arrFiles as $k=>$v)
			{
				$files[] = $arrFiles[$k];
				$source[] = $arrSource[$k];
			}
		}

		// add the classes now the values have been sorted.
		$countFiles = count($source);
		foreach($source as $k=>$v)
		{
			$source[$k]['class'] = (($k == 0) ? ' first' : '')
				. (($k == ($countFiles -1 )) ? ' last' : '')
				. ((($k % 2) == 0) ? ' even' : ' odd');
		}

		$objTemplate->files	= $files;
		$objTemplate->src 	= $source;

		$this->arrMeta = array();
		$this->arrAux = array();
		$this->arrProcessed = array();
		$this->auxDate = array();
		$this->multiSRC = array();
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttributeTranslated
	/////////////////////////////////////////////////////////////////

	public function setTranslatedDataFor($arrValues, $strLangCode)
	{
		$objDB = Database::getInstance();
		// first off determine those to be updated and those to be inserted.
		$arrIds = array_keys($arrValues);
		$arrExisting = array_keys($this->getTranslatedDataFor($arrIds, $strLangCode));
		$arrNewIds = array_diff($arrIds, $arrExisting);

		// now update...
		foreach ($arrExisting as $intId)
		{
			$objDB->prepare('UPDATE tl_metamodel_translatedlongblob SET value=?, tstamp=? WHERE att_id=? AND langcode=? AND item_id=?')
				  ->execute($arrValues[$intId], time(), $this->get('id'), $strLangCode, $intId);
		}
		// ...and insert
		foreach ($arrNewIds as $intId)
		{
			$objDB->prepare('INSERT INTO tl_metamodel_translatedlongblob %s')
				  ->set(array(
				  'tstamp' => time(),
				  'value' => $arrValues[$intId],
				  'att_id' => $this->get('id'),
				  'langcode' => $strLangCode,
				  'item_id' => $intId,
				  ))
				  ->execute();
		}
	}

	/**
	 * Get values for the given items in a certain language.
	 */
	public function getTranslatedDataFor($arrIds, $strLangCode)
	{
		$objDB = Database::getInstance();
		$objValue = $objDB->prepare('SELECT * FROM tl_metamodel_translatedlongblob WHERE att_id=? AND langcode=? AND item_id IN (' . implode(',', $arrIds) . ')')
				->execute($this->get('id'), $strLangCode);
		$arrReturn = array();
		while ($objValue->next())
		{
			$arrReturn[$objValue->item_id] = $objValue->row();
		}
		return $arrReturn;
	}

	/**
	 * Remove values for items in a certain lanugage.
	 */
	public function unsetValueFor($arrIds, $strLangCode)
	{
		$objValue = $objDB->prepare('DELETE FROM tl_metamodel_translatedlongblob WHERE att_id=? AND langcode=? AND item_id IN (' . implode(',', $arrIds) . ')')
				->execute($this->get('id'), $strLangCode);
	}
}

?>