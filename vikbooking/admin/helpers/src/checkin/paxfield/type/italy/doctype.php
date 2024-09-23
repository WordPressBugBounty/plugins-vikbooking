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
 * Defines the handler for a pax field of type "italy_doctype".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeItalyDoctype extends VBOCheckinPaxfieldType
{
	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		if ($this->field->getGuestNumber() > 1) {
			// this field is only for the main guest, and we are parsing the Nth guest
			return '';
		}

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

		// compose HTML content for the field
		$field_html = <<<HTML
<select id="$field_id" data-gind="$guest_number" class="$pax_field_class" name="$name">
	<option></option>
</select>
HTML;

		// make sure to append the heavy JSON list only once
		if (!$this->checkLoadedJSONDocumenti()) {
			// update registry flag
			$this->checkLoadedJSONDocumenti(1);

			// build JSON list
			$json_documenti = [];
			foreach ($this->loadDocumenti() as $doc_code => $doc_type) {
				$documento = [
					'id'   => $doc_code,
					'text' => $doc_type,
				];
				if ($value == $doc_code && $this->field->getTotalRooms() < 2) {
					// ignore the selected status as that's set via JS (we've got more rooms)
					$documento['selected'] = true;
				}
				$json_documenti[] = $documento;
			}
			$json_documenti = json_encode($json_documenti);

			// append JSON-encoded object
			$field_html .= <<<HTML
<script>
	var vbo_documenti_json = $json_documenti;
</script>
HTML;
		}

		/**
		 * The data for this select tag is still rendered via JS, but
		 * this field can only be used once per room-party. However, in
		 * case of multiple rooms, we need to set this value properly.
		 */
		$set_value  = (!empty($value) ? 1 : 0);
		$json_value = json_encode(($set_value ? [$value] : []));

		// append select2 JS script for rendering the field
		$field_html .= <<<HTML
<script>
	jQuery(function() {

		jQuery("#$field_id").select2({
			data: vbo_documenti_json,
			width: "100%",
			placeholder: "Seleziona documento",
			allowClear: true
		});

		setTimeout(function() {
			if ($set_value) {
				var prev_val_json = $json_value;
				jQuery("#$field_id").val(prev_val_json[0]).trigger('change');
			}
		}, 600);

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

	/**
	 * Helper method to cache a flag for loading heavy JSON data only once.
	 * 
	 * @param 	int 	$set 	the optional flag status to set.
	 * 
	 * @return 	int 	a boolean integer indicating the flag status.
	 */
	private function checkLoadedJSONDocumenti($set = 0)
	{
		// try to access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			if ($set) {
				// update registry flag and return previous value
				return $collect_registry->set('documenti_json', $set);
			}

			// return the loaded flag status
			return $collect_registry->get('documenti_json', 0);
		}

		// definitely not loaded
		return 0;
	}
}
