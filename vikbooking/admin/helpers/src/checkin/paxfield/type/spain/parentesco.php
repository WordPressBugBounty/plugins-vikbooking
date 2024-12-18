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
 * Defines the handler for a pax field of type "spain_parentesco".
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
final class VBOCheckinPaxfieldTypeSpainParentesco extends VBOCheckinPaxfieldType
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

		// get the guest type
		$guest_type = $this->field->getGuestType();

		// get the field class attribute
		$pax_field_class = $this->getFieldClassAttr();

		// get field name attribute
		$name = $this->getFieldNameAttr();

		// get the field value attribute
		$value = $this->getFieldValueAttr();

		// get the number of adults and children
		list($adults, $children) = $this->field->getRoomGuests();

		/**
		 * This pax field should be displayed if there is at least one child, only to adults.
		 * It will tell the relationship level of the adult against the child (i.e. "Dad or Mom").
		 */

		if (!$children || strcasecmp($guest_type, 'adult')) {
			// no children in the current room, or current guest type different than adult
			return '';
		}

		// compose HTML content for the field
		$field_html = '';
		$field_html .= "<select id=\"$field_id\" data-gind=\"$guest_number\" class=\"$pax_field_class\" name=\"$name\">\n";
		$field_html .= '<option></option>' . "\n";

		foreach ($this->loadChildrenRelationships() as $rel_key => $rel_value) {
			$field_html .= '<option value="' . htmlspecialchars($rel_key, ENT_QUOTES, 'UTF-8') . '"' . ($value == $rel_key ? ' selected="selected"' : '') . '>' . $rel_value . '</option>' . "\n";
		}

		$field_html .= '</select>';

		// return the necessary HTML string to display the field
		return $field_html;
	}

	/**
	 * Helper method that takes advantage of the collector class own method.
	 *
	 * @return 	array
	 */
	private function loadChildrenRelationships()
	{
		// call the same method on the collector instance
		$relationships = $this->callCollector(__FUNCTION__);

		return (array) $relationships;
	}
}
