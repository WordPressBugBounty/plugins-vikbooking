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
 * Defines the handler for a pax field of type "calendar".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPaxfieldTypeCalendar extends VBOCheckinPaxfieldType
{
	/**
	 * The container of this field should have a precise class.
	 * 
	 * @var 	string
	 */
	protected $container_class_attr = 'vbo-checkinfield-calendar-wrap';

	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	public function render()
	{
		// load calendar assets
		$this->loadCalendarAssets();

		// get the field unique ID
		$field_id = $this->getFieldIdAttr();

		// get the guest number
		$guest_number = $this->field->getGuestNumber();

		// get the field class attribute
		$pax_field_class = $this->getFieldClassAttr();
		// push an additional class name for the datepicker
		$all_field_class = explode(' ', $pax_field_class);
		$all_field_class[] = 'vbo-pax-field-datepicker';
		$pax_field_class = implode(' ', $all_field_class);

		// get field name attribute
		$name = $this->getFieldNameAttr();

		// get the field value attribute
		$value = htmlspecialchars($this->getFieldValueAttr());

		// get the FA calendar icon
		$cal_icon = VikBookingIcons::i('calendar');

		// compose HTML content for the field
		$field_html = <<<HTML
<input id="$field_id" type="text" autocomplete="off" data-gind="$guest_number" class="$pax_field_class" name="$name" value="$value" />
<i class="$cal_icon"></i>
HTML;

		// get values for datepicker
		$year_range_str = (date('Y') - 120) . ':' . date('Y');
		$juidf = $this->getDateFormat('jui');

		// append necessary JS script tag to render the calendar
		$field_html .= <<<HTML
<script>
jQuery(function() {
	jQuery("#$field_id").datepicker({
		dateFormat: "$juidf",
		changeMonth: true,
		changeYear: true,
		yearRange: "$year_range_str"
	});
});
</script>
HTML;

		// return the necessary HTML string to display the field
		return $field_html;
	}
}
