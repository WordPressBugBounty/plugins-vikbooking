<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * State/Province helper class.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VBOStateHelper
{
	/**
	 * Finds the state/province full name from the given state 2-char code and country.
	 * 
	 * @param 	string 	 $state_2_code 	the selected state/province.
	 * @param 	string 	 $country 		the selected country (probably 3-char code).
	 * @param 	boolean  $whole 		true to get the whole state record found.
	 * 
	 * @return 	string|array			the state/province full name found or whole record.
	 */
	public static function getFullName($state_2_code, $country = '', $whole = false)
	{
		$dbo = JFactory::getDbo();

		if (empty($state_2_code) || empty($country)) {
			return $state_2_code;
		}

		// find the ID of the given country
		$id_country = static::getCountryId($country);

		if (empty($id_country)) {
			return $state_2_code;
		}

		// make sure this province exists
		$q = "SELECT * FROM `#__vikbooking_states` WHERE `id_country`=" . $id_country . " AND `state_2_code`=" . $dbo->quote($state_2_code);
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no records found
			return $state_2_code;
		}

		$state_info = $dbo->loadAssoc();

		return $whole ? $state_info : $state_info['state_name'];
	}

	/**
	 * Attempts to find the country ID from the given identifier.
	 * 
	 * @param 	string 	$country 	country identifier (name or 2/3-char code).
	 * 
	 * @return 	int 				country ID found or null.
	 */
	public static function getCountryId($country)
	{
		$dbo = JFactory::getDbo();

		if (is_numeric($country)) {
			$q = "SELECT `id` FROM `#__vikbooking_countries` WHERE `id`=" . (int)$country;
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				// no records found for this country
				return null;
			}
			return $dbo->loadResult();
		}

		// find country ID by name or code
		$field_value = $country;
		$field_name  = $dbo->qn('country_name');
		if (strlen($country) == 3) {
			$field_name = $dbo->qn('country_3_code');
		} elseif (strlen($country) == 2) {
			$field_name = $dbo->qn('country_2_code');
		}

		$q = "SELECT `id` FROM `#__vikbooking_countries` WHERE {$field_name}=" . $dbo->quote($field_value);
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// country not found
			return null;
		}

		return $dbo->loadResult();
	}

	/**
	 * Returns all states for the given country ID.
	 * 
	 * @param 	int 	$id_country
	 * 
	 * @return 	array 	list of states found, if any.
	 */
	public static function getCountryStates($id_country)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_states` WHERE `id_country`=" . (int)$id_country;
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no records found for this country
			return [];
		}

		return $dbo->loadAssocList();
	}
}
