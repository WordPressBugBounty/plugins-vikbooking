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
 * Defines the handler for a pax field of type "country".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeCountry extends VBOCheckinPaxfieldType
{
	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		// get the field unique ID
		$field_id = $this->getFieldIdAttr();

		// get field name attribute
		$name = $this->getFieldNameAttr();

		// get the field value attribute
		$value = $this->getFieldValueAttr();

		// compose HTML content for the field
		$field_html = VikBooking::getCountriesSelect($name, $this->getCountries(), $value, ' ');

		// make sure to set the ID attribute to get the label working
		if (strpos($field_html, 'id=') === false) {
			$field_html = str_replace('<select ', '<select id="' . $field_id . '" ', $field_html);
		}

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
