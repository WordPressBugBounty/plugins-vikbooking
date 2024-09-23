<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Fixer for VikBooking cron job reminders.
 *
 * @since 1.5.10
 */
trait VBOCronFixerReminder
{
	/**
	 * Fixes the old flag_char structure used by all the cron jobs that act as reminders.
	 * 
	 * @return  void
	 */
	protected function normalizeDeprecatedFlag()
	{
		// make sure this trait has been attached only to a cron job instance
		if (!$this instanceof VBOCronJob)
		{
			throw new Exception('This tracker can be attached only to a VBOCronJob instance', 500);
		}

		$data = $this->getData();

		// check whether the first element of the array is another array
		if (is_array(reset($data->flag_char)))
		{
			$tmp = [];

			// convert a multi-dimension array into a linear list of bookings
			foreach ($data->flag_char as $date => $map)
			{
				// register the keys (booking IDs) within the new array
				$tmp = array_merge($tmp, array_keys($map));
			}

			// overwrite previous cache with the new one (get rid of duplicates)
			$data->flag_char = array_unique($tmp);
		}
	}
}
