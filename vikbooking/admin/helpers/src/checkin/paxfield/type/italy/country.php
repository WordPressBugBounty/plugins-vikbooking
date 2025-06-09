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

		// build select2 placeholder label
		$plch_lbl = $is_docplace ? addslashes(JText::translate('VBO_SELECT')) : addslashes(JText::translate('VBO_SELECT_COUNTRY'));

		if ($is_docplace && $this->checkLoadedJSONComuniProvince()) {
			// the list of Italian "comuni e province" was loaded by another field
			// so we try to merge the two JSON lists (countries + comuni-province)

			// build option group placeholders
			$italy_country_name = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2)) === 'it' ? 'Italia' : 'Italy';
			$it_cities_lbl = addslashes(JText::sprintf('VBO_ONLY_IF_COUNTRY_EQ', $italy_country_name));
			$fcountries_lbl = addslashes(JText::translate('VBO_FOREIGN_COUNTRIES'));

			// append select2 JS script for rendering the field
			$field_html .= <<<HTML
<script>
	jQuery(function() {

		jQuery("#$field_id").select2({
			data: [
				{
					text: "$fcountries_lbl",
					// detach "Italy" from the list of countries when comuni/province is available and append it as last option
					// do not remove it completely for backward compatibility and pre-selected values
					children: (typeof vbo_comuni_province_json !== 'undefined' && vbo_nazioni_json[0]?.id == '100000100' ? vbo_nazioni_json.toSpliced(0, 1).concat([vbo_nazioni_json[0]]) : vbo_nazioni_json),
				},
				{
					text: "$it_cities_lbl",
					children: (vbo_comuni_province_json || []),
				},
			],
			width: "100%",
			placeholder: "$plch_lbl",
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
		} else {
			// not a "docplace" field, but rather a list of countries alone

			// append select2 JS script for rendering the field
			$field_html .= <<<HTML
<script>
	jQuery(function() {

		jQuery("#$field_id").select2({
			data: vbo_nazioni_json,
			width: "100%",
			placeholder: "$plch_lbl",
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
		}

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

		return is_array($nazioni) ? $nazioni : [];
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

	/**
	 * Helper method to cache a flag for loading heavy JSON data only once.
	 * 
	 * @param 	int 	$set 	the optional flag status to set.
	 * 
	 * @return 	int 	a boolean integer indicating the flag status.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
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
