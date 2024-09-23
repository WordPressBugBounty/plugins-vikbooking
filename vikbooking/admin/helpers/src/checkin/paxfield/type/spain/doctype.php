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
 * Defines the handler for a pax field of type "spain_doctype".
 * 
 * @since 	1.15.2 (J) - 1.5.5 (WP)
 */
final class VBOCheckinPaxfieldTypeSpainDoctype extends VBOCheckinPaxfieldType
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

		// build list of doc types
		$doctypes_opt = '';
		foreach ($this->loadDocumenti() as $doc_code => $doc_type) {
			$doctypes_opt .= '<option value="' . $doc_code . '"' . ($doc_code == $value ? ' selected="selected"' : '') . '>' . $doc_type . '</option>' . "\n";
		}

		// compose HTML content for the field
		$field_html = <<<HTML
<select id="$field_id" data-gind="$guest_number" class="$pax_field_class" name="$name">
	<option></option>
	$doctypes_opt
</select>
HTML;

		// append select2 JS script for rendering the field
		$field_html .= <<<HTML
<script>
	jQuery(function() {

		jQuery("#$field_id").select2({
			width: "100%",
			placeholder: "Tipo Documento",
			allowClear: true
		});

	});
</script>
HTML;

		// return the necessary HTML string to display the field
		return $field_html;
	}

	/**
	 * Helper method that takes advantage of the collector class own method.
	 *
	 * @return 	array
	 */
	private function loadDocumenti()
	{
		// call the same method on the collector instance
		$documenti = $this->callCollector(__FUNCTION__);

		return is_array($documenti) ? $documenti : array();
	}
}
