<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_horizontalsearch
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

final class VikBookingWidgetHorizontalSearch
{
	/**
	 * Returns a JS string to define the array-variables containing
	 * the months and the week days.
	 * 
	 * @param 	string 	$format 	either long or 3char.
	 * @param 	mixed 	$module_id 	the ID of the module to write unique variables
	 * 
	 * @return 	string
	 * 
	 * @since 	1.1.0
	 */
	public static function getMonWdayScript($format = 'long', $module_id = 0)
	{
		$module_id = (string)$module_id;

		return 'var vboMapWdays'.$module_id.' = ["'.self::applySubstr(JText::translate('VBJQCALSUN'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALMON'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALTUE'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALWED'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALTHU'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALFRI'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALSAT'), $format).'"];
var vboMapMons'.$module_id.' = ["'.self::applySubstr(JText::translate('VBMONTHONE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTWO'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTHREE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHFOUR'), $format).'","'.self::applySubstr(JText::translate('VBMONTHFIVE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHSIX'), $format).'","'.self::applySubstr(JText::translate('VBMONTHSEVEN'), $format).'","'.self::applySubstr(JText::translate('VBMONTHEIGHT'), $format).'","'.self::applySubstr(JText::translate('VBMONTHNINE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTEN'), $format).'","'.self::applySubstr(JText::translate('VBMONTHELEVEN'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTWELVE'), $format).'"];';
	}

	/**
	 * Counts the maximum adults, children or guests as a sum from all rooms.
	 * 
	 * @param 	string 	$gtype 	the type of guest "adults", "children", "guests".
	 * 
	 * @return 	int 			the total number of guests for all rooms.
	 * 
	 * @since 	1.5.0
	 */
	public static function getMaxestGuests($gtype = 'adults')
	{
		$dbo = JFactory::getDbo();

		$sum_col = 'toadult';
		if (!strcasecmp($gtype, 'children')) {
			$sum_col = 'tochild';
		} elseif (!strcasecmp($gtype, 'guests')) {
			$sum_col = 'totpeople';
		}

		$q = "SELECT SUM(`{$sum_col}`) FROM `#__vikbooking_rooms` WHERE `avail`=1";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return (int)$dbo->loadResult();
		}

		return 1;
	}

	/**
	 * Fetches from the custom fields a list of basic fields
	 * for the request information (inquiry) form.
	 * 
	 * @return 	array 	the list of fieldset objects.
	 * 
	 * @since 	1.5.0
	 */
	public static function grabInquiryFields()
	{
		$vbo_tn = VikBooking::getTranslator();

		$dbo = JFactory::getDbo();

		// build the list of inquiry fields
		$inquiry_fields = array();

		// grab the first two nominative fields
		$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `isnominative`=1 ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 2);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// translate records
			$records = $dbo->loadAssocList();
			$vbo_tn->translateContents($records, '#__vikbooking_custfields');
			// prepare the fieldset object of type nominative to push
			$fieldset = new stdClass;
			$fieldset->type = 'nominative';
			$fieldset->required = 1;
			$fieldset->fields = json_decode(json_encode($records));
			// push fieldset
			array_push($inquiry_fields, $fieldset);
		}

		// grab the first email field
		$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `isemail`=1 ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// translate records
			$records = $dbo->loadAssocList();
			$vbo_tn->translateContents($records, '#__vikbooking_custfields');
			// prepare the fieldset object of type email to push
			$fieldset = new stdClass;
			$fieldset->type = 'email';
			$fieldset->required = 1;
			$fieldset->fields = json_decode(json_encode($records));
			// push fieldset
			array_push($inquiry_fields, $fieldset);
		}

		// grab the first phone field
		$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `isphone`=1 ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// translate records
			$records = $dbo->loadAssocList();
			$vbo_tn->translateContents($records, '#__vikbooking_custfields');
			// prepare the fieldset object of type phone to push
			$fieldset = new stdClass;
			$fieldset->type = 'phone';
			$fieldset->fields = json_decode(json_encode($records));
			// push fieldset
			array_push($inquiry_fields, $fieldset);
		}

		// grab the first country field
		$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `type`='country' ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// translate records
			$records = $dbo->loadAssocList();
			$vbo_tn->translateContents($records, '#__vikbooking_custfields');
			// prepare the fieldset object of type country to push
			$fieldset = new stdClass;
			$fieldset->type = 'country';
			$fieldset->fields = json_decode(json_encode($records));
			// push fieldset
			array_push($inquiry_fields, $fieldset);
		}

		// grab the first city field
		$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `flag`='city' ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// translate records
			$records = $dbo->loadAssocList();
			$vbo_tn->translateContents($records, '#__vikbooking_custfields');
			// prepare the fieldset object of type city to push
			$fieldset = new stdClass;
			$fieldset->type = 'city';
			$fieldset->fields = json_decode(json_encode($records));
			// push fieldset
			array_push($inquiry_fields, $fieldset);
		}

		// always push the message/special request field
		$fieldset = new stdClass;
		$fieldset->type = 'special_requests';
		$fieldset->required = 1;
		$fieldset->fields = array('textarea');
		// push fieldset
		array_push($inquiry_fields, $fieldset);

		// grab the first two mandatory checkbox fields
		$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `type`='checkbox' AND `required`=1 ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 2);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// translate records
			$records = $dbo->loadAssocList();
			$vbo_tn->translateContents($records, '#__vikbooking_custfields');
			// prepare the fieldset object of type checkbox to push
			$fieldset = new stdClass;
			$fieldset->type = 'checkbox';
			$fieldset->required = 1;
			$fieldset->fields = json_decode(json_encode($records));
			// push fieldset
			array_push($inquiry_fields, $fieldset);
		}

		// return the list of fieldsets
		return $inquiry_fields;
	}

	/**
	 * Evaluates the fieldset type and field data to return the type of input.
	 * 
	 * @param 	string 	$type 	the type of the fieldset to evaluate.
	 * @param 	mixed 	$field 	the current inquiry field, either an object or a string.
	 * 
	 * @return 	string 			the type identifier of the field to display.
	 * 
	 * @since 	1.5.0
	 */
	public static function parseFieldType($type, $field)
	{
		// accepted list of fields to render
		$valid_fields = array(
			'text',
			'textarea',
			'number',
			'email',
			'phone',
			'checkbox',
		);

		if (is_object($field)) {
			// we expect this to be a record from the custom fields
			if ($type == 'nominative') {
				return 'text';
			}
			if ($type == 'email') {
				return 'email';
			}
			if ($type == 'phone') {
				return 'phone';
			}
			if ($type == 'country') {
				return 'country';
			}
			if ($type == 'city') {
				return 'text';
			}
			if ($type == 'checkbox') {
				return 'checkbox';
			}
		}

		// this could be a string for building a textarea or fallback to text
		return in_array($field, $valid_fields) ? $field : 'text';
	}

	/**
	 * Evaluates the fieldset type and field data to return the label for the input.
	 * 
	 * @param 	string 	$type 	the type of the fieldset to evaluate.
	 * @param 	mixed 	$field 	the current inquiry field, either an object or a string.
	 * 
	 * @return 	string 			the label (title) for the field to display.
	 * 
	 * @since 	1.5.0
	 */
	public static function parseFieldLabel($type, $field)
	{
		if (is_object($field) && isset($field->name)) {
			$label = JText::translate($field->name);
			
			if (!empty($field->poplink)) {
				// try not to make the whole translation string a link
				$label_parts = explode(' ', $label);
				if (count($label_parts) > 4) {
					$label_out = implode(' ', array_slice($label_parts, 0, (count($label_parts) - 4))) . ' ';
					$label_in = implode(' ', array_slice($label_parts, -4));
				} else {
					// unable to make the linked part shorter
					$label_out = '';
					$label_in = $label;
				}
				// build final link tag
				$label = $label_out . '<a href="' . $field->poplink . '" target="_blank">' . $label_in . '</a>';
			}

			return $label;
		}

		if ($type == 'special_requests') {
			return JText::translate('INQ_NOTES_SPREQUESTS');
		}

		return ucwords(str_replace(array('_', '-'), ' ', $type));
	}

	/**
	 * Returns a string with the requested length.
	 * 
	 * @param 	string 	$text 			the text to apply the substr onto.
	 * @param 	string 	$format 		either long or 3char.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.1.0
	 */
	private static function applySubstr($text, $format)
	{
		$mb_supported = function_exists('mb_substr');

		if ($format == 'long') {
			return $text;
		}
		
		return $mb_supported ? mb_substr($text, 0, 3, 'UTF-8') : substr($text, 0, 3);
	}
}
