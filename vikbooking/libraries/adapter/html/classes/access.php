<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.html
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Extended Utility class for all HTML drawing classes.
 *
 * @since 10.1.16
 */
abstract class JHtmlAccess
{
	/**
	 * Displays a list of the available access view levels
	 *
	 * @param   string  $name      The form field name.
	 * @param   string  $selected  The name of the selected section.
	 * @param   string  $attribs   Additional attributes to add to the select field.
	 * @param   mixed   $params    True to add "All Sections" option or an array of options.
	 * @param   mixed   $id        The form field id or false if not used.
	 *
	 * @return  string  The required HTML for the SELECT tag.
	 */
	public static function level($name, $selected, $attribs = '', $params = true, $id = false)
	{
		$options = array();
		$options[] = JHtml::fetch('select.option', 1, JText::translate('JOPTION_ACCESS_PUBLIC'));
		$options[] = JHtml::fetch('select.option', 5, JText::translate('JOPTION_ACCESS_GUEST'));
		$options[] = JHtml::fetch('select.option', 2, JText::translate('JOPTION_ACCESS_REGISTERED'));
		$options[] = JHtml::fetch('select.option', 3, JText::translate('JOPTION_ACCESS_SPECIAL'));
		$options[] = JHtml::fetch('select.option', 6, JText::translate('JOPTION_ACCESS_SUPERUSER'));

		// if params is an array, push these options to the array
		if (is_array($params))
		{
			$options = array_merge($params, $options);
		}
		// if all levels is allowed, push it into the array.
		else if ($params)
		{
			array_unshift($options, JHtml::fetch('select.option', '', JText::translate('JOPTION_ACCESS_SHOW_ALL_LEVELS')));
		}

		// generate select tag
		return '<select name="' . $name . '"' . ($id ? ' id="' . $id . '"' : '') . ($attribs ? ' ' . $attribs : '') . '>'
			. JHtml::fetch('select.options', $options, 'value', 'text', $selected)
			. '</select>';
	}
}
