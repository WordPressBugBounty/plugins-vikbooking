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
 * Defines the handler for a pax field of type "italy_comune".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeItalyComune extends VBOCheckinPaxfieldType
{
	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		if (substr($this->field->getKey(), -2) == '_s' && $this->field->getGuestNumber() > 1) {
			// this is rather "comune di residenza" and we are parsing the Nth guest
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
<select id="$field_id" data-gind="$guest_number" class="$pax_field_class" name="$name" onchange="vboComuniProvinceChange('$field_id', '$name');">
	<option></option>
</select>
HTML;

		// make sure to append the heavy JSON list (and JS functions) only once
		if (!$this->checkLoadedJSONComuniProvince()) {
			// update registry flag
			$this->checkLoadedJSONComuniProvince(1);

			// get values to display in the dropdown
			$com_prov = $this->loadComuniProvince();

			// build JSON list
			$json_comuni = [];
			foreach ($com_prov['comuni'] as $com_code => $com_data) {
				// make sure the name of the comune does not contain parentheses
				$com_data['name'] = str_replace(array('(', ')'), '', $com_data['name']);
				// build comune data
				$comune = [
					'id'   => $com_code,
					'text' => $com_data['name'] . " ({$com_data['province']})",
				];
				if ($value == $com_code) {
					// ignore the selected status as that's set via JS
					// $comune['selected'] = true;
				}
				$json_comuni[] = $comune;
			}
			unset($com_prov);
			$json_comuni = json_encode($json_comuni);

			// append JSON-encoded object
			$field_html .= <<<HTML
<script>
	var vbo_comuni_province_json = $json_comuni;

	function vboComuniProvinceChange(field_id, field_name) {
		var sel_prov = '';
		var com_prov = jQuery('#' + field_id).find('option:selected').text();
		if (com_prov) {
			var match_prov = com_prov.match(/\(([^)]+)\)/);
			if (match_prov) {
				sel_prov = match_prov[1];
			}
		}
		var prov_field_name = field_name.replace('comune_', 'province_');
		jQuery('input[name="' + prov_field_name + '"]').val(sel_prov);
	}
</script>
HTML;
		}

		/**
		 * Check whether the status of this field should be set to an existing value.
		 * Since the data of the select tag is populated via JS by providing a JSON-encoded
		 * array, we need to set the current value for this field and select tag via JS.
		 * We encode in JSON format the current value, if not empty, so that no JS errors
		 * can occur, nor could we break expressions or syntaxes with unescaped new lines.
		 */
		$set_value  = (!empty($value) ? 1 : 0);
		$json_value = json_encode(($set_value ? [$value] : []));

		// append select2 JS script for rendering the field
		$field_html .= <<<HTML
<script>
	jQuery(function() {

		jQuery("#$field_id").select2({
			data: vbo_comuni_province_json,
			width: "100%",
			placeholder: "Solo se stato = Italia",
			allowClear: true
		});

		setTimeout(function() {
			if ($set_value) {
				var prev_val_json = $json_value;
				jQuery("#$field_id").val(prev_val_json[0]).trigger('change');
			}
		}, 800);
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
	private function loadComuniProvince()
	{
		// call the same method on the collector instance
		$com_prov = $this->callCollector(__FUNCTION__);

		return is_array($com_prov) ? $com_prov : array();
	}

	/**
	 * Helper method to cache a flag for loading heavy JSON data only once.
	 * 
	 * @param 	int 	$set 	the optional flag status to set.
	 * 
	 * @return 	int 	a boolean integer indicating the flag status.
	 */
	private function checkLoadedJSONComuniProvince($set = 0)
	{
		// try to access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			if ($set) {
				// update registry flag and return previous value
				return $collect_registry->set('comuni_province_json', $set);
			}

			// return the loaded flag status
			return $collect_registry->get('comuni_province_json', 0);
		}

		// definitely not loaded
		return 0;
	}
}
