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
	 * @override 		this driver requires children to be registered.
	 * 
	 * @param 	bool 	$precheckin 	true if requested for front-end pre check-in.
	 * 
	 * @return 	bool    true to also register the children.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP) added $precheckin argument.
	 * @since 	1.17.6 (J) - 1.7.6 (WP) children are always registered.
	 */
	public function registerChildren($precheckin = false)
	{
		// children are registered during both back-end and pre-check-in
		return true;
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
			'guest_type' => JText::translate('VBO_GUEST_TYPE'),
			'first_name' => JText::translate('VBCUSTOMERFIRSTNAME'),
			'last_name'  => JText::translate('VBCUSTOMERLASTNAME'),
			'gender' 	 => JText::translate('VBOCUSTGENDER'),
			'date_birth' => JText::translate('ORDER_DBIRTH'),
			'country_b'  => JText::translate('VBO_BIRTH_STATE'),
			'comune_b' 	 => JText::translate('VBO_BIRTH_MUNICIPALITY'),
			'province_b' => JText::translate('VBO_STATE_PROVINCE'),
			'zip_b' 	 => JText::translate('ORDER_ZIP'),
			'country_s'  => JText::translate('VBO_STAY_STATE'),
			'comune_s' 	 => JText::translate('VBO_STAY_MUNICIPALITY'),
			'province_s' => JText::translate('VBO_STATE_PROVINCE'),
			'zip_s' 	 => JText::translate('ORDER_ZIP'),
			'country_c'  => JText::translate('VBOCUSTNATIONALITY'),
			'doctype'  	 => JText::translate('VBOCUSTDOCTYPE'),
			'docnum' 	 => JText::translate('VBOCUSTDOCNUM'),
			'docplace' 	 => JText::translate('VBO_ID_ISSUE_PLACE'),
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
	 * @inheritDoc
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	public function listPrecheckinFields(array $def_fields)
	{
		// use the same fields for the back-end guests registration
		$labels = $this->getLabels();
		$attributes = $this->getAttributes();

		// for pre-checkin we keep any default field of type "file" for uploading IDs
		foreach (($def_fields[1] ?? []) as $field_key => $field_type) {
			if (!is_string($field_type)) {
				// not looking for a list of options
				continue;
			}
			if (!strcasecmp($field_type, 'file') && ($def_fields[0][$field_key] ?? null)) {
				// append this pax field of type "file" for uploading IDs
				$labels[$field_key] = $def_fields[0][$field_key];
				$attributes[$field_key] = $field_type;
			}
		}

		// return the list of pre-checkin pax fields
		return [$labels, $attributes];
	}

	/**
	 * @inheritDoc
	 * @override
	 * 
	 * @since 	1.17.7 (J) - 1.7.7 (WP)
	 */
	public function validateRegistrationFields(array $booking, array $booking_rooms, array $data, bool $precheckin = true)
	{
		if (!$precheckin) {
			// no validation needed during back-end registration
			return;
		}

		// get all field labels
		$labels = $this->getLabels();

		// list of mandatory fields
		$mandatory_gfields = [
			'guest_type',
			'first_name',
			'last_name',
			'gender',
			'date_birth',
			'country_b',
			'country_c',
		];

		// list of mandatory fields for the main guest
		$mandatory_main_gfields = [
			'doctype',
			'docnum',
			'docplace',
		];

		// list of mandatory fields for any guest in case the country of birth ("country_b") is Italy
		$mandatory_italy_gfields = [
			'comune_b',
			'province_b',
		];

		// iterate over all rooms booked
		foreach ($booking_rooms as $index => $booking_room) {
			if (!is_array(($data[$index] ?? null)) || !$data[$index]) {
				throw new Exception(sprintf('Missing guests registration data for room #%d.', ($index + 1)), 500);
			}

			// count expected room registration guests data
			$room_adults = $booking_room['adults'] ?? 1;
			$room_children = $booking_room['children'] ?? 0;
			$room_guests = $this->registerChildren($precheckin) ? ($room_adults + $room_children) : $room_adults;

			if ($room_guests > count($data[$index])) {
				throw new Exception(sprintf('Please fill the information for all guests (%d) for room #%d.', $room_guests, ($index + 1)), 500);
			}

			// scan room guests for the expected room guest registration data
			for ($g = 1; $g <= $room_guests; $g++) {
				if (!is_array(($data[$index][$g] ?? null))) {
					throw new Exception(sprintf('Please fill the information for the guests #%d for room #%d.', $g, ($index + 1)), 500);
				}

				// build guest mandatory fields
				$mand_fields = $mandatory_gfields;

				if ($g === 1) {
					// merge main guest-level mandatory fields for validation
					$mand_fields = array_merge($mand_fields, $mandatory_main_gfields);
				}

				// check if we need to validate additional fields for this guest in case it's Italian
				if (!empty($data[$index][$g]['country_b']) && is_scalar($data[$index][$g]['country_b'])) {
					if ($data[$index][$g]['country_b'] == '100000100' || !strcasecmp(substr((string) $data[$index][$g]['country_b'], 0, 2), 'IT')) {
						// the guest is Italian, merge mandatory fields for Italian guests
						$mand_fields = array_merge($mand_fields, $mandatory_italy_gfields);
					}
				}

				// ensure no mandatory field is empty
				foreach ($mand_fields as $mand_gfield) {
					if (!is_string($data[$index][$g][$mand_gfield] ?? null) || !strlen($data[$index][$g][$mand_gfield])) {
						throw new Exception(JText::sprintf('VBO_MISSING_GFIELD_REGISTRATION', ($labels[$mand_gfield] ?? $mand_gfield)), 500);
					}
				}
			}
		}

		// map of guest-type codes with equal last name
		$guest_type_codes_map = [
			// main guest "Head of Family" expects other guests with equal last name to be "Family Member"
			17 => 19,
			// main guest "Head of Group" expects other guests with equal last name to be "Group Member"
			18 => 20,
		];

		// fields are sufficient, prevent transmission errors with invalid guest-type codes
		foreach ($booking_rooms as $index => $booking_room) {
			// count expected room registration guests data
			$room_adults = $booking_room['adults'] ?? 1;
			$room_children = $booking_room['children'] ?? 0;
			$room_guests = $this->registerChildren($precheckin) ? ($room_adults + $room_children) : $room_adults;

			if ($room_guests < 2) {
				// no validation needed over guest-type code
				continue;
			}

			// identify the main guest type code and last name
			$main_guest_type = (int) ($data[$index][1]['guest_type'] ?? 0);
			$main_guest_lname = $data[$index][1]['last_name'] ?? '';
			if (!in_array($main_guest_type, [17, 18]) || empty($main_guest_lname)) {
				// expected 17 or 18, respectively for "Head of Family" or "Head of Group"
				continue;
			}

			// scan room guests by excluding the main one
			for ($g = 2; $g <= $room_guests; $g++) {
				if ($main_guest_lname == ($data[$index][$g]['last_name'] ?? '')) {
					// this guest has got the same last name as the main one
					if ($guest_type_codes_map[$main_guest_type] != ($data[$index][$g]['guest_type'] ?? 0)) {
						// expected additional guest type code differs from the selected value
						throw new Exception(sprintf('The guest #%d for room #%d has got the same last name as the main guest, but their guest-type codes do not match.', $g, ($index + 1)), 500);
					}
				}
			}
		}
	}

	/**
	 * Parses the file Nazioni.csv and returns an associative
	 * array with the code and name of the "Nazione" (country).
	 * 
	 * Public and internal method, not part of the interface.
	 * Useful for all the implementor fields that need it.
	 * 
	 * @param 	bool 	$normalize 	Whether to normalize the country names into localised values.
	 *
	 * @return 	array
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)  added argument $normalize and related behaviour.
	 */
	public function loadNazioni($normalize = true)
	{
		// always try access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			$prev_country_codes = $collect_registry->get('nazioni');
			if (is_array($prev_country_codes) && $prev_country_codes) {
				// another field of this collection must have loaded already the "Nazioni"
				return $prev_country_codes;
			}
		}

		// container
		$country_codes = [];

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

			// remove unwanted chars
			$v[0] = trim($v[0]);

			if (!isset($country_codes[$v[0]])) {
				$country_codes[$v[0]] = [];
			}

			// push values
			$country_codes[$v[0]]['name'] = trim($v[1]);
			$country_codes[$v[0]]['three_code'] = trim($v[2]);
		}

		/**
		 * Apply translations based on country 3-char ISO code, if not in Italian or denied.
		 * 
		 * @since 	1.17.2 (J) - 1.7.2 (WP)
		 */
		if ($normalize && strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2)) !== 'it') {
			// get the translated countries list
			$tn_countries = VikBooking::getCountriesArray();

			$country_codes = array_map(function($country_vals) use ($tn_countries) {
				if (!($tn_countries[$country_vals['three_code']] ?? null)) {
					// unknown country
					return $country_vals;
				}

				// replace country name
				$country_vals['name'] = $tn_countries[$country_vals['three_code']]['country_name'];

				// return the modified country values array
				return $country_vals;
			}, $country_codes);
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
			if (is_array($prev_comprov_codes) && $prev_comprov_codes) {
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
	 * Public and internal method, not part of the interface.
	 * Useful for all the implementor fields that need it.
	 * 
	 * @param 	bool 	$normalize 	Whether to display values relevant to non-italian guests.
	 *
	 * @return 	array
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)  added argument $normalize and related behaviour.
	 */
	public function loadDocumenti($normalize = false)
	{
		// always try access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			$prev_documenti = $collect_registry->get('documenti');
			if (is_array($prev_documenti) && $prev_documenti) {
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

		if ($normalize === true) {
			// we expect these values to be displayed to non-italian guests
			$known_documents = [
				'PASOR' => JText::translate('VBO_PASSPORT'),
				'PATEN' => JText::translate('VBO_DRIVING_LICENSE'),
				'IDENT' => JText::translate('VBO_IDENTITY_DOCUMENT'),
			];

			// filter documents by foreign types
			$documenti = array_filter($documenti, function($doc_type) use ($known_documents) {
				return isset($known_documents[$doc_type]);
			}, ARRAY_FILTER_USE_KEY);

			// normalize known document names
			$documenti = array_merge($documenti, $known_documents);
		}

		// try to cache the documenti codes for other fields that may need them later
		if ($collect_registry) {
			// cache array for other fields
			$collect_registry->set('documenti', $documenti);
		}

		return $documenti;
	}
}
