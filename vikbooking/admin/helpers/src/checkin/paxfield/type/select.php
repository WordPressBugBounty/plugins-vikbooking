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
 * Defines the handler for a pax field of type "select".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeSelect extends VBOCheckinPaxfieldType
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

		// get the guest number
		$guest_number = $this->field->getGuestNumber();

		// get the field class attribute
		$pax_field_class = $this->getFieldClassAttr();

		// get field name attribute
		$name = $this->getFieldNameAttr();

		// get the field value attribute
		$value = $this->getFieldValueAttr();

		// get field type attribute (an array of options)
		$options = $this->field->getType();
		if (!is_array($options) || !count($options)) {
			// return an empty string if options are missing
			return '';
		}

		// compose HTML content for the field
		$field_html = '';
		$field_html .= "<select id=\"$field_id\" data-gind=\"$guest_number\" class=\"$pax_field_class\" name=\"$name\">\n";
		$field_html .= "\t<option></option>\n";
		foreach ($options as $pfa_key => $pfa_val) {
			$paxv_selected = ($value == $pfa_val || (!is_numeric($pfa_key) && $value == $pfa_key));
			$opt_selected = $paxv_selected ? ' selected="selected"' : '';
			$opt_val = !is_numeric($pfa_key) ? $pfa_key : $pfa_val;

			$field_html .= "\t<option value=\"{$opt_val}\"{$opt_selected}>{$pfa_val}</option>\n";
		}
		$field_html .= "</select>\n";

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
