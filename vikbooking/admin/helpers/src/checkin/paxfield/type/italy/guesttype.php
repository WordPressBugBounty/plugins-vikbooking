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
 * Defines the handler for a pax field of type "italy_guesttype".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeItalyGuesttype extends VBOCheckinPaxfieldType
{
	/**
	 * The choices available in case of a single guest.
	 * 
	 * @var 	array
	 */
	private $single_guest = array(
		'16' => 'Ospite Singolo',
	);

	/**
	 * The choices available for the first multi-guest.
	 * 
	 * @var 	array
	 */
	private $first_multi_guest = array(
		'17' => 'Capo Famiglia',
		'18' => 'Capo Gruppo',
	);

	/**
	 * The choices available for the Nth multi-guest.
	 * 
	 * @var 	array
	 */
	private $nth_multi_guest = array(
		'19' => 'Familiare',
		'20' => 'Membro Gruppo',
	);

	/**
	 * The container of this field should have a precise class.
	 * 
	 * @var 	string
	 */
	protected $container_class_attr = 'vbo-checkinfield-guesttype-wrap';

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

		// get the number of adults and children
		list($adults, $children) = $this->field->getRoomGuests();

		// compose HTML content for the field
		$field_html = '';
		$field_html .= "<select id=\"$field_id\" data-gind=\"$guest_number\" class=\"$pax_field_class\" name=\"$name\">\n";

		if (($adults + $children) === 1) {
			// with just one guest adult the value will be "ospite singolo"
			$opt_val = key($this->single_guest);
			$opt_lbl = $this->single_guest[$opt_val];
			$field_html .= "\t<option value=\"$opt_val\">{$opt_lbl}</option>\n";
		} else {
			// we've got more than one guest
			$field_html .= "\t<option></option>\n";
			if ($guest_number === 1) {
				// this is the first traveller of the room
				foreach ($this->first_multi_guest as $code => $gtype) {
					$opt_selected = $value == $code ? ' selected="selected"' : '';
					$field_html .= "\t<option value=\"{$code}\"{$opt_selected}>{$gtype}</option>\n";
				}
			} else {
				// this is the Nth traveller of the room
				foreach ($this->nth_multi_guest as $code => $gtype) {
					$opt_selected = $value == $code ? ' selected="selected"' : '';
					$field_html .= "\t<option value=\"{$code}\"{$opt_selected}>{$gtype}</option>\n";
				}
			}
		}

		$field_html .= "</select>\n";

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
