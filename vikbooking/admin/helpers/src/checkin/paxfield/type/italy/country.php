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
 * Defines the handler for a pax field of type "italy_country".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeItalyCountry extends VBOCheckinPaxfieldType
{
	/**
	 * The code for "Italia", needed to trigger events.
	 * 
	 * @var 	string
	 */
	private $italy_code = '100000100';

	/**
	 * The code for "Estero" province, needed to apply events.
	 * 
	 * @var 	string
	 */
	private $estero_prov_code = 'ES';

	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		if ($this->field->getGuestNumber() > 1) {
			// we are parsing the Nth guest
			if (substr($this->field->getKey(), -2) == '_s') {
				// this is rather "nazione di residenza", so it's only for the main guest
				return '';
			}
			if (!strcasecmp($this->field->getKey(), 'docplace')) {
				// this field is "luogo di rilascio documento", so it's only for the main guest
				return '';
			}
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

		// check whether we are parsing a particular type of field
		$is_docplace = (!strcasecmp($this->field->getKey(), 'docplace'));

		// compose HTML content for the field
		$field_html = <<<HTML
<select id="$field_id" data-gind="$guest_number" class="$pax_field_class" name="$name" onchange="vboNazioniChange(this.value, '$name');">
	<option></option>
</select>
HTML;

		// make sure to append the heavy JSON list (and JS functions) only once
		if (!$is_docplace && !$this->checkLoadedJSONNazioni()) {
			// update registry flag
			$this->checkLoadedJSONNazioni(1);

			// build JSON list
			$json_nazioni = [];
			foreach ($this->loadNazioni() as $country_code => $country_data) {
				// build nazione data
				$nazione = [
					'id'   => $country_code,
					'text' => $country_data['name'],
				];
				if ($value == $country_code) {
					// ignore the selected status as that's set via JS
					// $nazione['selected'] = true;
				}
				$json_nazioni[] = $nazione;
			}
			$json_nazioni = json_encode($json_nazioni);

			// append JSON-encoded object
			$field_html .= <<<HTML
<script>
	var vbo_nazioni_json = $json_nazioni;

	function vboNazioniChange(code, field_name) {
		if (field_name.indexOf('docplace') >= 0) {
			return false;
		}
		var com_field_name = field_name.replace('country_', 'comune_');
		var com_field_elem = jQuery('select[name="' + com_field_name + '"]');
		if (code == "$this->italy_code") {
			com_field_elem.prop('disabled', false).trigger('change');
		} else {
			com_field_elem.prop('disabled', true).val('').trigger('change');
			if (code && code.length) {
				setTimeout(function() {
					var prov_field_name = com_field_name.replace('comune_', 'province_');
					jQuery('input[name="' + prov_field_name + '"]').val("$this->estero_prov_code");
				}, 300);
			}
		}
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
			data: vbo_nazioni_json,
			width: "100%",
			placeholder: "Seleziona stato",
			allowClear: true
		});

		setTimeout(function() {
			if ($set_value) {
				var prev_val_json = $json_value;
				jQuery("#$field_id").val(prev_val_json[0]).trigger('change');
			}
		}, 700);
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
	private function loadNazioni()
	{
		// call the same method on the collector instance
		$nazioni = $this->callCollector(__FUNCTION__);

		return is_array($nazioni) ? $nazioni : array();
	}

	/**
	 * Helper method to cache a flag for loading heavy JSON data only once.
	 * 
	 * @param 	int 	$set 	the optional flag status to set.
	 * 
	 * @return 	int 	a boolean integer indicating the flag status.
	 */
	private function checkLoadedJSONNazioni($set = 0)
	{
		// try to access the registry instance data
		$collect_registry = VBOCheckinPax::getInstanceData();

		// check if the registry instance of this collection type has cached values
		if ($collect_registry) {
			if ($set) {
				// update registry flag and return previous value
				return $collect_registry->set('nazioni_json', $set);
			}

			// return the loaded flag status
			return $collect_registry->get('nazioni_json', 0);
		}

		// definitely not loaded
		return 0;
	}
}
