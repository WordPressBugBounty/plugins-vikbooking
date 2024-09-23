<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.mvc.models.form');

/**
 * VikBooking plugin Shortcodes model.
 *
 * @since 	1.0
 * @see 	JModelForm
 */
class VikBookingModelShortcodes extends JModelForm
{
	/**
	 * Grabs all shortcode records and custom related information.
	 * 
	 * @param 	string 	$columns 	optional list of columns to fetch.
	 * @param 	bool 	$full 		whether the column to fetch should not be empty.
	 * 
	 * @return 	array 				list of records fetched, if any.
	 * 
	 * @since 	1.5.10 	added second argument $full to filter empty post_id records.
	 */
	public function all($columns = '*', $full = false)
	{
		$dbo = JFactory::getDbo();

		$records = [];
		$fetch   = $columns;

		if ($columns != '*')
		{
			if (is_string($columns))
			{
				$columns = $dbo->qn($columns);
			}
			else
			{
				$columns = implode(', ', array_map(array($dbo, 'qn'), $columns));
			}
		}

		$q = "SELECT {$columns} FROM `#__vikbooking_wpshortcodes` ORDER BY `id` ASC";

		$dbo->setQuery($q);
		$dbo->execute();

		if (!$dbo->getNumRows())
		{
			return [];
		}

		$records = $dbo->loadObjectList();

		if ($full && is_string($fetch) && $fetch != '*' && strpos($fetch, ',') === false)
		{
			// filter the records by non-empty column
			$records = array_filter($records, function($record) use ($fetch)
			{
				return isset($record->{$fetch}) && !empty($record->{$fetch});
			});

			// make sure to reset the array keys
			$records = array_values($records);
		}

		return $records;
	}

	/**
	 * Finds the best post_id for the passed view(s).
	 * Rather than using all() to get the first post_id,
	 * with this method we can get the exact post_id for
	 * the type of shortcode passed along with the views.
	 * 
	 * @param 	mixed 	$views 	string or array of strings
	 * @param 	string 	$lang 	the language attribute to look for
	 *
	 * @return 	mixed 	integer for the post_id, null otherwise
	 * 
	 * @uses 	all()
	 * @since 	1.0.14
	 */
	public function best($views, $lang = null)
	{
		if (empty($views))
		{
			return null;
		}

		if (is_scalar($views))
		{
			// always convert the views to look for into an array
			$views = array($views);
		}

		// get all shortcodes of any type
		$shortcodes = $this->all();

		// current language
		$lang = is_null($lang) ? JFactory::getLanguage()->getTag() : $lang;

		if (!count($shortcodes))
		{
			return null;
		}

		// loop through the shortcodes and look for the requested views
		do
		{
			// the view to look for
			$reqview = array_shift($views);

			// highest score counter
			$last_count = 0;
			
			foreach ($shortcodes as $k => $v)
			{
				// ignore shortcodes not linked to a valid page
				if (empty($v->post_id))
				{
					continue;
				}

				// start the score counter
				$count = 0;

				if (!strcasecmp($v->type, $reqview))
				{
					// view found, increment best score
					$count++;

					if ($lang == $v->lang) {
						// same language, increment best score
						$count++;
					}
				}

				if ($count > $last_count)
				{
					// update counters
					$last_count = $count;
					$code_index = $k;
				}
			}

			if ($last_count > 0)
			{
				// best view found, return this post_id
				return $shortcodes[$code_index]->post_id;
			}

		} while (count($views));

		// return the first post_id available in the shortcodes
		return $shortcodes[0]->post_id;
	}

	/**
	 * Method to get a table object.
	 *
	 * @param   string  $name     The table name.
	 * @param   string  $prefix   The class prefix.
	 * @param   array   $options  Configuration array for table.
	 *
	 * @return  JTable  A table object.
	 *
	 * @since   1.4.4
	 */
	public function getTable($name = '', $prefix = 'JTable', $options = array())
	{
		if (!$name)
		{
			$name 	= 'shortcode';
			$prefix = 'VBOTable';
		}

		return parent::getTable($name, $prefix, $options);
	}
}
