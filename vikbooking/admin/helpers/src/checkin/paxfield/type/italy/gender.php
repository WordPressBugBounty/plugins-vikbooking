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
 * Defines the handler for a pax field of type "italy_gender".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeItalyGender extends VBOCheckinPaxfieldType
{
	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		// load select assets
		$this->loadSelectAssets();

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

		// build lang defs
		$male_ldef 	 = JText::translate('VBOCUSTGENDERM');
		$female_ldef = JText::translate('VBOCUSTGENDERF');

		// default statuses
		$male_selected 	 = ((int)$value === 1 || !strcasecmp($value, 'M') ? ' selected="selected"' : '');
		$female_selected = ((int)$value === 2 || !strcasecmp($value, 'F') ? ' selected="selected"' : '');

		// compose HTML content for the field
		$field_html = <<<HTML
<select id="$field_id" data-gind="$guest_number" class="$pax_field_class" name="$name">
	<option></option>
	<option value="1"$male_selected>$male_ldef</option>
	<option value="2"$female_selected>$female_ldef</option>
</select>
HTML;

		// append select2 JS script for rendering the field
		$field_html .= <<<HTML
<script>
	jQuery(function() {

		jQuery("#$field_id").select2({
			width: "100%",
			placeholder: "Seleziona",
			allowClear: true
		});

	});
</script>
HTML;

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
