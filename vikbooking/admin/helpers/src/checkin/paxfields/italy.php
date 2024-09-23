<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class to support custom pax fields data collection types for Italy.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldsItaly extends VBOCheckinAdapter
{
	/**
	 * The ID of this pax data collector class.
	 * 
	 * @var 	string
	 */
	protected $collector_id = 'italy';

	/**
	 * Returns the name of the current pax data driver.
	 * 
	 * @return 	string 	the name of this driver.
	 */
	public function getName()
	{
		return '"Italia"';
	}

	/**
	 * Tells whether children should be registered.
	 * 
	 * @override 		this driver requires children to be registered (back-end only).
	 * 
	 * @param 	bool 	$precheckin 	true if requested for front-end pre check-in.
	 * 
	 * @return 	bool    true to also register the children.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP) added $precheckin argument.
	 */
	public function registerChildren($precheckin = false)
	{
		// children are registered only during back-end guests check-in
		return $precheckin ? false : true;
	}

	/**
	 * Returns the list of field labels. The count and keys
	 * of the labels should match with the attributes.
	 * 
	 * @see 	All fields ending with "_b" stand for "birth",
	 * 			all fields ending with "_s" stand for "stay",
	 * 			all fields ending with "_c" stand for "citizenship".
	 * 			Keep the ending char intact as some fields act differently
	 * 			depending on the number and type of guest we are parsing.
	 * 
	 * @return 	array 	associative list of field labels.
	 */
	public function getLabels()
	{
		return [
			'guest_type' => 'Tipo alloggiato',
			'first_name' => JText::translate('VBCUSTOMERFIRSTNAME'),
			'last_name'  => JText::translate('VBCUSTOMERLASTNAME'),
			'gender' 	 => JText::translate('VBOCUSTGENDER'),
			'date_birth' => JText::translate('ORDER_DBIRTH'),
			'country_b'  => 'Stato di nascita',
			'comune_b' 	 => 'Comune di nascita',
			'province_b' => 'Provincia',
			'zip_b' 	 => JText::translate('ORDER_ZIP'),
			'country_s'  => 'Stato di residenza',
			'comune_s' 	 => 'Comune di residenza',
			'province_s' => 'Provincia',
			'zip_s' 	 => 'CAP (residenza)',
			'country_c'  => 'Cittadinanza',
			'doctype'  	 => 'Tipo documento',
			'docnum' 	 => JText::translate('VBCUSTOMERDOCNUM'),
			'docplace' 	 => 'Luogo di rilascio',
			'extranotes' => JText::translate('VBOGUESTEXTRANOTES'),
		];
	}

	/**
	 * Returns the list of field attributes. The count and keys
	 * of the attributes should match with the labels.
	 * 
	 * @see 	All fields ending with "_b" stand for "birth",
	 * 			all fields ending with "_s" stand for "stay",
	 * 			all fields ending with "_c" stand for "citizenship".
	 * 			Keep the ending char intact as some fields act differently
	 * 			depending on the number and type of guest we are parsing.
	 * 
	 * @return 	array 	associative list of field attributes.
	 */
	public function getAttributes()
	{
		return [
			'guest_type' => 'italy_guesttype',
			'first_name' => 'text',
			'last_name'  => 'text',
			'gender' 	 => 'italy_gender',
			'date_birth' => 'calendar',
			'country_b'  => 'italy_country',
			'comune_b'   => 'italy_comune',
			'province_b' => 'italy_province',
			'zip_b' 	 => 'italy_cap',
			'country_s'  => 'italy_country',
			'comune_s'   => 'italy_comune',
			'province_s' => 'italy_province',
			'zip_s' 	 => 'italy_cap',
			'country_c'  => 'italy_country',
			'doctype'  	 => 'italy_doctype',
			'docnum' 	 => 'italy_docnum',
			'docplace'   => 'italy_country',
			'extranotes' => 'textarea',
		];
	}

	/**
	 * Parses the file Nazioni.csv and returns an associative
	 * array with the code and name of the "Nazione" (country).
	 * 
	 * Public and internal method, not part of the interface.
	 * Useful for all the implementor fields that need it.
	 *
	 * @return 	array
	 */
	public function loadNazioni()
	{
		// always try access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			$prev_country_codes = $collect_registry->get('nazioni');
			if (is_array($prev_country_codes) && count($prev_country_codes)) {
				// another field of this collection must have loaded already the "Nazioni"
				return $prev_country_codes;
			}
		}

		// container
		$country_codes = array();

		$csv = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'Nazioni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			if (!isset($country_codes[$v[0]])) {
				$country_codes[$v[0]] = [];
			}

			// push values
			$country_codes[$v[0]]['name'] = $v[1];
			$country_codes[$v[0]]['three_code'] = $v[2];
		}

		// try to cache the country codes for other fields that may need them later
		if ($collect_registry) {
			// cache array for other fields
			$collect_registry->set('nazioni', $country_codes);
		}

		return $country_codes;
	}

	/**
	 * Parses the file Comuni.csv and returns two associative
	 * arrays: one for the Comuni and one for the Province.
	 * Every line of the CSV is composed of: Codice, Comune, Provincia.
	 * 
	 * Public and internal method, not part of the interface.
	 * Useful for all the implementor fields that need it.
	 *
	 * @return 	array
	 */
	public function loadComuniProvince()
	{
		// always try access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			$prev_comprov_codes = $collect_registry->get('comuni_province');
			if (is_array($prev_comprov_codes) && count($prev_comprov_codes)) {
				// another field of this collection must have loaded already the "Comuni"
				return $prev_comprov_codes;
			}
		}

		// container
		$comprov_codes = [
			'comuni'   => [],
			'province' => [],
		];

		$csv = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'Comuni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);
			$v[2] = trim($v[2]);

			if (!isset($comprov_codes['comuni'][$v[0]])) {
				$comprov_codes['comuni'][$v[0]] = [];
			}

			// push values
			$comprov_codes['comuni'][$v[0]]['name'] 	= $v[1];
			$comprov_codes['comuni'][$v[0]]['province'] = $v[2];
			$comprov_codes['province'][$v[2]] 			= $v[2];
		}

		// try to cache the comuni-province codes for other fields that may need them later
		if ($collect_registry) {
			// cache array for other fields
			$collect_registry->set('comuni_province', $comprov_codes);
		}

		return $comprov_codes;
	}

	/**
	 * Parses the file Documenti.csv and returns an associative
	 * array with the code and name of the Documento.
	 * Every line of the CSV is composed of: Codice, Documento.
	 *
	 * @return 	array
	 */
	public function loadDocumenti()
	{
		// always try access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			$prev_documenti = $collect_registry->get('documenti');
			if (is_array($prev_documenti) && count($prev_documenti)) {
				// another field of this collection must have loaded already the "Comuni"
				return $prev_documenti;
			}
		}

		// container
		$documenti = [];

		$csv = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'Documenti.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 2) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);

			// push values
			$documenti[$v[0]] = $v[1];
		}

		// try to cache the documenti codes for other fields that may need them later
		if ($collect_registry) {
			// cache array for other fields
			$collect_registry->set('documenti', $documenti);
		}

		return $documenti;
	}
}
