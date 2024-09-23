<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Global translator class for VikBooking contents.
 */
class VikBookingTranslator
{
	/**
	 * @var string
	 */
	public $current_lang;

	/**
	 * @var string
	 */
	public $default_lang;

	/**
	 * @var int
	 */
	public $lim = 5;

	/**
	 * @var int
	 */
	public $lim0 = 0;

	/**
	 * @var string
	 */
	public $navigation = '';

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var string
	 */
	private $xml = '';

	/**
	 * @var array
	 */
	private $all_langs = [];

	/**
	 * @var object
	 */
	private $dbo;

	/**
	 * @var string
	 */
	private $translations_path_file;

	/**
	 * @var array
	 */
	private $translations_buffer = [];

	/**
	 * @var 	string
	 * 
	 * @since 	1.16.6 (J) - 1.6.6 (WP)
	 */
	private $keysearch = '';

	/**
	 * @var 	array
	 * 
	 * @since 	1.16.6 (J) - 1.6.6 (WP)
	 */
	private $cached_cols = [];

	/**
	 * @var string
	 */
	public static $force_tolang = null;

	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		$app = JFactory::getApplication();

		$this->current_lang = $this->getCurrentLang();
		$this->default_lang = $this->getDefaultLang();
		$this->lim = $app->input->getInt('limit', 5);
		$this->lim0 = $app->input->getInt('limitstart', 0);
		$this->dbo = JFactory::getDbo();
		$this->translations_path_file = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR . 'translations.xml';
	}

	/**
	 * Gets the language for the current execution.
	 * 
	 * @return 	string
	 */
	public function getCurrentLang()
	{
		if (!$this->current_lang) {
			$this->current_lang = JFactory::getLanguage()->getTag();
		}

		return $this->current_lang;
	}

	/**
	 * Gets the default language for the requested section.
	 * 
	 * @param 	string 	$section 	either site or administrator.
	 * 
	 * @return 	string
	 */
	public function getDefaultLang($section = 'site')
	{
		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	import the JComponentHelper class
			 */
			jimport('joomla.application.component.helper');
		}

		if (!$this->default_lang && $section == 'site') {
			$this->default_lang = JComponentHelper::getParams('com_languages')->get($section);
		}

		if ($section == 'site') {
			return $this->default_lang;
		}

		return JComponentHelper::getParams('com_languages')->get($section);
	}

	/**
	 * Returns a list of translation INI files.
	 * 
	 * @return 	array
	 */
	public function getIniFiles()
	{
		// Keys = Lang Def composed as VBINIEXPL.strtoupper(Key)
		// Values = Paths to INI Files

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	nothing to return
			 */
			return [];
		}

		return [
			'com_vikbooking_front' 			  => ['path' => JPATH_SITE . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'en-GB' . DIRECTORY_SEPARATOR . 'en-GB.com_vikbooking.ini'],
			'com_vikbooking_admin' 			  => ['path' => JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'en-GB' . DIRECTORY_SEPARATOR . 'en-GB.com_vikbooking.ini'],
			'com_vikbooking_admin_sys' 		  => ['path' => JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'en-GB' . DIRECTORY_SEPARATOR . 'en-GB.com_vikbooking.sys.ini'],
			'mod_vikbooking_search' 		  => ['path' => JPATH_SITE . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'en-GB' . DIRECTORY_SEPARATOR . 'en-GB.mod_vikbooking_search.ini'],
			'mod_vikbooking_horizontalsearch' => ['path' => JPATH_SITE . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'en-GB' . DIRECTORY_SEPARATOR . 'en-GB.mod_vikbooking_horizontalsearch.ini'],
		];
	}

	/**
	 * Builds a list of known languages.
	 * 
	 * @return 	array
	 */
	public function getLanguagesList()
	{
		$langs = [];
		$known_langs = VikBooking::getVboApplication()->getKnownLanguages();

		foreach ($known_langs as $ltag => $ldet) {
			if ($ltag == $this->default_lang) {
				$langs = [$ltag => $ldet] + $langs;
			} else {
				$langs[$ltag] = $ldet;
			}
		}

		$this->all_langs = $langs;

		return $this->all_langs;
	}

	/**
	 * Returns a list of known language tags.
	 * 
	 * @return 	array
	 */
	public function getLanguagesTags()
	{
		return array_keys($this->all_langs);
	}

	/**
	 * Replaces the prefix from a given table name.
	 * 
	 * @param 	string 	$str 	the prefixed name.
	 * 
	 * @return 	string
	 */
	public function replacePrefix($str)
	{
		return $this->dbo->replacePrefix($str);
	}

	/**
	 * Helper method that makes sure the table name starts with the prefix placeholder.
	 * In order to avoid issues with queries containing the prefix placeholder ("#__"),
	 * the name of the table for the translated record is removed from the placeholder prefix.
	 * 
	 * @param 	string 	$table_name 	the table name to adjust.
	 * 
	 * @return 	string 	the adjust table name with the prefix placeholder.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function adjustTablePrefix($table_name)
	{
		if (empty($table_name) || !is_string($table_name)) {
			// nothing to do with the given value
			return $table_name;
		}

		if (preg_match("/^#__/", $table_name)) {
			// default prefix placeholder found at the beginning of the string
			return $table_name;
		}

		if (strpos($table_name, 'vikbooking_') === false) {
			// nothing to do with this table name
			return $table_name;
		}

		// make the table name start with the prefix placeholder
		$table_nm_parts = explode('vikbooking_', $table_name);
		$table_nm_parts[0] = '#__';

		return implode('vikbooking_', $table_nm_parts);
	}

	/**
	 * Fixer method for BC. The old structure was storing the table names for
	 * the translated records without the default prefix placeholder ('#__'),
	 * but this is invalid for the backup features. This method converts all
	 * table names for the translated records so that they will contain the
	 * default prefix placeholder at the beginning of the string.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function normalizeTnTableNames()
	{
		$query = $this->dbo->getQuery(true);

		$query->select($this->dbo->qn('t.id'));
		$query->select($this->dbo->qn('t.table'));

		$query->from($this->dbo->qn('#__vikbooking_translations', 't'));

		$this->dbo->setQuery($query);
		$translations = $this->dbo->loadObjectList();

		if (!$translations) {
			// no translation records found
			return false;
		}

		foreach ($translations as $tn_record) {
			// normalize table name with prefix
			$tn_record->table = $this->adjustTablePrefix($tn_record->table);

			// update record on db
			$this->dbo->updateObject('#__vikbooking_translations', $tn_record, 'id');
		}

		return true;
	}

	/**
	 * Builds an associative list of translation tables.
	 * 
	 * @return 	mixed 	array or false.
	 */
	public function getTranslationTables()
	{
		$xml = $this->getTranslationsXML();
		if ($xml === false) {
			return false;
		}
		$tables = [];
		foreach ($xml->Translation as $translation) {
			$attr = $translation->attributes();
			$tables[(string)$attr->table] = JText::translate((string)$attr->name);
		}

		return $tables;
	}

	/**
	 * Returns the translated name of the table given the prefix
	 * 
	 * @param 	string 	$table
	 */
	public function getTranslationTableName($table)
	{
		$xml = $this->getTranslationsXML();
		$table_name = '';
		foreach ($xml->Translation as $translation) {
			$attr = $translation->attributes();
			if ((string)$attr->table == $table) {
				return JText::translate((string)$attr->name);
			}
		}
		return $table_name;
	}

	/**
	 * Returns an array with the XML Columns of the given table
	 * 
	 * @param 	string 	$table
	 * 
	 * @return 	array
	 */
	public function getTableColumns($table)
	{
		if (isset($this->cached_cols[$table])) {
			return $this->cached_cols[$table];
		}

		$xml  = $this->getTranslationsXML();
		$cols = [];
		foreach ($xml->Translation as $translation) {
			$attr = $translation->attributes();
			if ((string)$attr->table == $table) {
				foreach ($translation->Column as $column) {
					$col_attr = $column->attributes();
					if (!property_exists($col_attr, 'name')) {
						continue;
					}
					$ind = (string)$col_attr->name;
					$cols[$ind]['jlang'] = JText::translate((string)$column);
					foreach ($col_attr as $key => $val) {
						$cols[$ind][(string)$key] = (string)$val;
					}
				}
			}
		}

		// cache table columns
		$this->cached_cols[$table] = $cols;

		return $cols;
	}

	/**
	 * Gets the name of the reference column for the given table.
	 * 
	 * @param 	string 	$table 	the name of the table.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.16.6 (J) - 1.6.6 (WP)
	 */
	public function getTableReferenceColumn($table)
	{
		foreach ($this->getTableColumns($table) as $col_name => $col_values) {
			if (isset($col_values['reference'])) {
				return $col_name;
			}
		}

		return '';
	}

	/**
	 * Returns the db column marked as reference, of the record. Ex. the name of the Room in this record
	 * 
	 * @param 	array 	$cols
	 * @param 	array 	$record
	 */
	public function getRecordReferenceName($cols, $record)
	{
		foreach ($cols as $key => $values) {
			if (array_key_exists('reference', $values)) {
				if (array_key_exists($key, $record)) {
					return $record[$key];
				}
			}
		}
		// if not found, not present or empty, return first value of the record
		return $record[key($record)];
	}

	/**
	 * Returns the current records for the default language and this table
	 * 
	 * @param 	string 	$table 	the name of the table.
	 * @param 	array 	$cols 	array containing the db fields to fetch,
	 * 							result of array_keys($this->getTableColumns()).
	 * 
	 * @return 	array
	 */
	public function getTableDefaultDbValues($table, $cols = [])
	{
		$def_vals = [];

		if (!$cols) {
			$cols = array_keys($this->getTableColumns($table));
			if (!$cols) {
				$this->setError("Table $table has no Columns.");
			}
		}

		if ($cols) {
			$cols = array_map([$this->dbo, 'qn'], $cols);

			$reference_column = $this->getTableReferenceColumn($table);

			$q = $this->dbo->getQuery(true)
				->select('SQL_CALC_FOUND_ROWS ' . $this->dbo->qn('id'))
				->select($cols)
				->from($this->dbo->qn($table))
				->order($this->dbo->qn(($reference_column ?: 'id')) . ' ASC');

			if (!empty($this->keysearch) && $reference_column) {
				$q->where($this->dbo->qn($reference_column) . ' LIKE ' . $this->dbo->q("%{$this->keysearch}%"));
			}

			$this->dbo->setQuery($q, $this->lim0, $this->lim);
			$records = $this->dbo->loadAssocList();

			if ($records) {
				$this->dbo->setQuery('SELECT FOUND_ROWS();');
				$this->setPagination($this->dbo->loadResult());
				foreach ($records as $record) {
					$ref_id = $record['id'];
					unset($record['id']);
					$def_vals[$ref_id] = $record;
				}
			} else {
				$this->setError("Table " . $this->getTranslationTableName($table) . " has no Records.");
			}
		}

		return $def_vals;
	}

	/**
	 * Sets the pagination HTML value for the current
	 * translation list, by using the Joomla native functions.
	 * 
	 * @param 	int 	$tot_rows
	 */
	private function setPagination($tot_rows)
	{
		jimport('joomla.html.pagination');
		$pageNav = new JPagination($tot_rows, $this->lim0, $this->lim);
		$this->navigation = $pageNav->getListFooter();
	}

	/**
	 * Returns the current pagination HTML value.
	 */
	public function getPagination()
	{
		return $this->navigation;
	}

	/**
	 * Returns the translated records for this table and language
	 * 
	 * @param 	string 	$table
	 * @param 	string 	$lang
	 * 
	 * @return 	array
	 */
	public function getTranslatedTable($table, $lang)
	{
		$translated = [];

		$q = $this->dbo->getQuery(true);

		$q->select($this->dbo->qn('t') . '.*')
			->from($this->dbo->qn('#__vikbooking_translations', 't'))
			->where($this->dbo->qn('t.table') . ' = ' . $this->dbo->q($this->adjustTablePrefix($table)))
			->where($this->dbo->qn('t.lang') . ' = ' . $this->dbo->q($lang))
			->order($this->dbo->qn('t.reference_id') . ' ASC');

		$this->dbo->setQuery($q);

		foreach ($this->dbo->loadAssocList() as $record) {
			$record['content'] = json_decode($record['content'], true);
			$translated[$record['reference_id']] = $record;
		}

		return $translated;
	}

	/**
	 * Main function to translate contents saved in the database.
	 * 
	 * @param 	array 	$content 	The array taken from the database with the default values to be translated, passed as reference.
	 * @param 	string 	$table 		The name of the table containing the translation values, should match with the XML table name.
	 * @param 	array 	$alias_keys Key-Value pairs where Key is the ALIAS used and Value is the original field name. Opposite instead for the ID (reference_id).
	 * 								The key 'id' is always treated differently than the other keys. Correct usage: array('id' => 'idroom', 'room_name' => 'name')
	 * @param 	array 	$ids 		The reference_IDs to be translated, the IDs of the records. Taken from the content array if empty array passed.
	 * @param 	string 	$lang 		Force the translation to a specific language tag like it-IT.
	 *
	 * @return  array 				The initial array with translated values (if applicable).
	 */
	public function translateContents(&$content, $table, $alias_keys = array(), $ids = array(), $lang = null)
	{
		$to_lang = is_null($lang) ? $this->current_lang : $lang;
		$to_lang = !is_null(self::$force_tolang) ? self::$force_tolang : $to_lang;

		// multilang may be disabled
		if (!$this->allowMultiLanguage()) {
			return $content;
		}

		// check that requested lang is not the default lang
		if ($to_lang == $this->default_lang || !$content) {
			return $content;
		}

		// get all translatable columns of this table
		$cols = $this->getTableColumns($table);

		// get the reference ids to be translated
		if (!count($ids)) {
			$ids = $this->getReferencesFromContents($content, $alias_keys);
		}

		// load translations buffer for this table or set the var to an empty array
		$translated = $this->getTranslationsBuffer($table, $ids, $to_lang);

		if (!count($translated)) {
			// load translations from db
			$q = "SELECT * FROM `#__vikbooking_translations` WHERE `table`=" . $this->dbo->quote($this->adjustTablePrefix($table)) . " AND `lang`=" . $this->dbo->quote($to_lang) . (count($ids) ? " AND `reference_id` IN (" . implode(",", $ids) . ")" : "") . ";";
			$this->dbo->setQuery($q);
			$records = $this->dbo->loadAssocList();
			foreach ($records as $record) {
				$record['content'] = json_decode($record['content'], true);
				if (is_array($record['content']) && $record['content']) {
					$translated[$record['reference_id']] = $record['content'];
				}
			}
		}

		if (count($translated)) {
			// set translations buffer
			$this->translations_buffer[$table][$to_lang] = $translated;

			// fetch reference_id to be translated and replace default lang values
			$reference_key = array_key_exists('id', $alias_keys) ? $alias_keys['id'] : 'id';
			foreach ($content as $ckey => $cvals) {
				$reference_id = 0;
				if (is_array($cvals)) {
					foreach ($cvals as $subckey => $subcvals) {
						if ($subckey == $reference_key) {
							$reference_id = (int)$subcvals;
							break;
						}
					}
					$content[$ckey] = $this->translateArrayValues($cvals, $cols, $reference_id, $alias_keys, $translated);
				} elseif ($ckey == $reference_key) {
					$reference_id = (int)$cvals;
					$content = $this->translateArrayValues($content, $cols, $reference_id, $alias_keys, $translated);
					break;
				}
			}
		}

		return $content;
	}

	/**
	 * Compares the array to be translated with the translation and replaces the array values if not empty.
	 * 
	 * @param 	array 	$content 	default lang values to be translated.
	 * @param 	array 	$alias_keys Key_Values pairs where Key is the ALIAS used and Value is the original
	 * 								field name. Opposite instead for the ID (reference_id).
	 * 
	 * @return 	array
	 */
	private function getReferencesFromContents($content, $alias_keys)
	{
		$references = [];
		$reference_key = array_key_exists('id', $alias_keys) ? $alias_keys['id'] : 'id';

		if (!is_array($content) || !$content) {
			return [];
		}

		foreach ($content as $ckey => $cvals) {
			if (is_array($cvals)) {
				foreach ($cvals as $subckey => $subcvals) {
					if ($subckey == $reference_key) {
						$references[] = (int)$subcvals;
						break;
					}
				}
			} elseif ($ckey == $reference_key) {
				$references[] = (int)$cvals;
				break;
			}
		}

		if ($references) {
			$references = array_unique($references);
		}

		return $references;
	}

	/**
	 * Check whether these reference IDs were already fetched from the db for this table
	 * 
	 * @param 	string 	$table
	 * @param 	array 	$ids
	 * @param 	string 	$lang
	 * 
	 * @return 	array
	 */
	private function getTranslationsBuffer($table, $ids, $lang)
	{
		if (!count($this->translations_buffer) || !isset($this->translations_buffer[$table]) || !isset($this->translations_buffer[$table][$lang])) {
			return array();
		}

		$missing = false;
		foreach ($ids as $id) {
			if (!isset($this->translations_buffer[$table][$lang][$id])) {
				$missing = true;
				break;
			}
		}

		return $missing === false ? $this->translations_buffer[$table][$lang] : array();
	}

	/**
	 * Compares the array to be translated with the translation and replaces the array values if not empty.
	 * 
	 * @param array $content 		default lang values to be translated.
	 * @param array $cols  			the columns of this table.
	 * @param int 	$reference_id 	reference_id.
	 * @param array $alias_keys 	Key_Values pairs where Key is the ALIAS used and Value is the original field name. 
	 * 								Opposite instead for the ID (reference_id).
	 * @param array $translated 	translated.
	 */
	private function translateArrayValues($content, $cols, $reference_id, $alias_keys, $translated)
	{
		if (empty($reference_id)) {
			return $content;
		}

		if (!array_key_exists($reference_id, $translated)) {
			return $content;
		}

		foreach ($content as $key => $value) {
			$native_key = $key;
			if (count($alias_keys) > 0 && array_key_exists($key, $alias_keys) && $key != 'id') {
				$key = $alias_keys[$key];
			}
			if (!array_key_exists($key, $cols)) {
				continue;
			}
			if (array_key_exists($key, $translated[$reference_id]) && strlen($translated[$reference_id][$key]) > 0) {
				$type = $cols[$key]['type'];
				if ($type == 'json') {
					// only the translated and not empty keys will be taken from the translation 
					$tn_json = json_decode($translated[$reference_id][$key], true);
					$content_json = json_decode($value, true);
					$jkeys = !empty($cols[$key]['keys']) ? explode(',', $cols[$key]['keys']) : array();
					if (is_array($tn_json) && $tn_json && is_array($content_json) && $content_json) {
						foreach ($content_json as $jk => $jv) {
							if (array_key_exists($jk, $tn_json) && strlen($tn_json[$jk]) > 0) {
								$content_json[$jk] = $tn_json[$jk];
							}
						}
						$content[$native_key] = json_encode($content_json);
					}
				} else {
					// field is a text type or a text-derived one
					$content[$native_key] = $translated[$reference_id][$key];
				}
			}
		}

		return $content;
	}

	/**
	 * Sets and Returns the SimpleXML object for the translations
	 */
	public function getTranslationsXML()
	{
		if (!is_file($this->translations_path_file)) {
			$this->setError($this->translations_path_file . ' does not exist or is not readable');
			return false;
		}
		if (!function_exists('simplexml_load_file')) {
			$this->setError('Function simplexml_load_file is not available on the server.');
			return false;
		}
		if (is_object($this->xml)) {
			return $this->xml;
		}
		libxml_use_internal_errors(true);
		if (($xml = simplexml_load_file($this->translations_path_file)) === false) {
			$this->setError("Error reading XML:\n".$this->libxml_display_errors());
			return false;
		}
		$this->xml = $xml;
		return $xml;
	}

	/**
	 * Tells if the multi-language environment is enabled.
	 * 
	 * @return 	bool
	 */
	private function allowMultiLanguage()
	{
		return VikBooking::allowMultiLanguage();
	}

	/**
	 * Explanation of the XML error
	 * 
	 * @param 	$error
	 */
	public function libxml_display_error($error)
	{
		$return = "\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING :
				$return .= "Warning ".$error->code.": ";
				break;
			case LIBXML_ERR_ERROR :
				$return .= "Error ".$error->code.": ";
				break;
			case LIBXML_ERR_FATAL :
				$return .= "Fatal Error ".$error->code.": ";
				break;
		}
		$return .= trim($error->message);
		if ($error->file) {
			$return .= " in ".$error->file;
		}
		$return .= " on line ".$error->line."\n";
		return $return;
	}

	/**
	 * Get the XML errors occurred
	 */
	public function libxml_display_errors()
	{
		$errorstr = "";
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$errorstr .= $this->libxml_display_error($error);
		}
		libxml_clear_errors();
		return $errorstr;
	}

	/**
	 * Concatenates an error to the errors string.
	 * 
	 * @param 	string 	$str 	the error string to add.
	 * 
	 * @return 	void
	 */
	private function setError($str)
	{
		$this->error .= $str."\n";
	}

	/**
	 * Returns the current error string in HTML format.
	 * 
	 * @return 	string
	 */
	public function getError()
	{
		return nl2br(rtrim($this->error, "\n"));
	}

	/**
	 * Sets a key value to search/filter translations for.
	 * 
	 * @param 	string 	$key 	the term to search/filter.
	 * 
	 * @return 	self
	 */
	public function setKeySearch($key)
	{
		$this->keysearch = (string) $key;

		return $this;
	}
}
