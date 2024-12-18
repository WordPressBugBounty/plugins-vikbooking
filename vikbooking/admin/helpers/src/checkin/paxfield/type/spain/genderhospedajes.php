<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Defines the handler for a pax field of type "spain_genderhospedajes".
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
final class VBOCheckinPaxfieldTypeSpainGenderhospedajes extends VBOCheckinPaxfieldType
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

		/**
		 * We now support the gender types (sexo) for the "Hospedajes" integration.
		 */

		$male_ldef 	 = JText::translate('VBOCUSTGENDERM');
		$female_ldef = JText::translate('VBOCUSTGENDERF');
		$otro_ldef   = 'Otro';

		// default statuses
		$male_selected 	 = ((int) $value === 1 || !strcasecmp($value, 'H') ? ' selected="selected"' : '');
		$female_selected = ((int) $value === 2 || !strcasecmp($value, 'M') ? ' selected="selected"' : '');
		$otro_selected   = ((int) $value === -1 || !strcasecmp($value, 'O') ? ' selected="selected"' : '');

		// compose HTML content for the field
		$field_html = <<<HTML
<select id="$field_id" data-gind="$guest_number" class="$pax_field_class" name="$name">
	<option></option>
	<option value="H"$male_selected>$male_ldef</option>
	<option value="M"$female_selected>$female_ldef</option>
	<option value="O"$otro_selected>$otro_ldef</option>
</select>
HTML;

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
