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
 * Defines the handler for a pax field of type "italy_cap".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeItalyCap extends VBOCheckinPaxfieldType
{
	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		if (substr($this->field->getKey(), -2) == '_s' && $this->field->getGuestNumber() > 1) {
			// this is rather "cap di residenza" and we are parsing the Nth guest
			return '';
		}

		// get the field unique ID
		$field_id = $this->getFieldIdAttr();

		// get the guest number
		$guest_number = $this->field->getGuestNumber();

		// get the field class attribute
		$pax_field_class = $this->getFieldClassAttr();

		// get field name attribute
		$name = $this->getFieldNameAttr();

		// get the field value attribute
		$value = htmlspecialchars($this->getFieldValueAttr());

		// placeholder label
		$plch_lbl = '(' . htmlspecialchars(JText::translate('VBO_OPTIONAL')) . ')';

		// compose HTML content for the field
		$field_html = <<<HTML
<input id="$field_id" type="text" autocomplete="off" placeholder="$plch_lbl" data-gind="$guest_number" class="$pax_field_class" name="$name" value="$value" />
HTML;

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
