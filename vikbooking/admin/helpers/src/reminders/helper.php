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
 * Helper class to handle reminders.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBORemindersHelper extends JObject
{
	/**
	 * Proxy to construct the object.
	 * 
	 * @param 	array|object  $data  optional data to bind.
	 * 
	 * @return 	self
	 */
	public static function getInstance($data = [])
	{
		return new static($data);
	}

	/**
	 * Loads records from the db table for the reminders.
	 * 
	 * @param 	array 	$fetch 		optional query fetch options.
	 * @param 	int 	$offset 	optional query limit start.
	 * @param 	int 	$length 	optional query limit length.
	 * 
	 * @return 	array 				list of record objects, if any.
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)	Query refactoring and support added
	 * 			for reminders not yet displayed. Implemented "important" flag
	 * 			for future usage in order to always display them until completed.
	 */
	public function loadReminders(array $fetch = [], $offset = 0, $length = 0)
	{
		$dbo = JFactory::getDbo();

		// build default fetch params
		$params = [
			'after' 	=> 'NOW',
			'before' 	=> null,
			'idorder' 	=> 0,
			'onlyorder' => 0,
			'completed' => 0,
			'expired' 	=> 0,
			'not_shown'	=> 0,
			'important' => 0,
			'missed' 	=> 0,
		];

		// merge fetch params
		$params = array_merge($params, $fetch);

		// build query
		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikbooking_reminders'));

		if ($params['missed'] && isset($params['after']) && $params['after'] != 'NOW') {
			/**
			 * Fetch important reminders missed (expired after a date) or any imminent one.
			 * (
			 * 	(`duedate` >= 'Y-m-d' AND `important` = 1)
			 * 	OR
			 * 	(`duedate` >= NOW() AND `important` = 0)
			 * )
			 */
			$where_clause = '((%1$s >= %2$s AND %3$s = 1) OR (%1$s >= NOW() AND %3$s = 0))' . "\n";
			$q->where(sprintf($where_clause, $dbo->qn('duedate'), $dbo->q($params['after']), $dbo->qn('important')));
		} else {
			// regular date intervals
			if (!$params['expired'] && !empty($params['after']) && is_string($params['after'])) {
				if (!strcasecmp($params['after'], 'NOW')) {
					$q->where($dbo->qn('duedate') . ' >= NOW()');
				} else {
					// datetime string is expected
					$q->where($dbo->qn('duedate') . ' >= ' . $dbo->q($params['after']));
				}
			}
		}

		if ($params['before']) {
			// set clause for max future date
			$q->where($dbo->qn('duedate') . ' <= ' . $dbo->q($params['before']));
		}

		if ($params['idorder']) {
			// exclude all reminders not for this booking
			$res_filter = [(int)$params['idorder']];
			if (!$params['onlyorder']) {
				// take the ones for no bookings or for this booking only
				array_unshift($res_filter, 0);
			}
			$q->where($dbo->qn('idorder') . ' IN (' . implode(', ', $res_filter) . ')');

			// set ordering to display reminders for this booking on top
			$q->order($dbo->qn('idorder') . ' DESC');
		}

		if (!$params['completed']) {
			$q->where($dbo->qn('completed') . ' = 0');
		}

		if ($params['not_shown']) {
			// exclude the reminders that were displayed
			$q->where($dbo->qn('displayed') . ' = 0');
		}

		if ($params['important']) {
			// fetch only those reminders with the "important" flag enabled
			$q->where($dbo->qn('important') . ' = ' . (int)$params['important']);
		}

		// set general ordering
		if ($params['expired']) {
			// order by the time difference from now as an absolute value
			$q->order('ABS(' . $dbo->qn('duedate') . ' - NOW()) ASC');
		} else {
			// due date ascending is useful when before/after date filters are given
			$q->order($dbo->qn('duedate') . ' ASC');
		}

		$dbo->setQuery($q, $offset, $length);
		$reminders = $dbo->loadObjectList();

		if (!$reminders) {
			return [];
		}

		// decode payload on all records, if needed
		foreach ($reminders as $k => $reminder) {
			if (!empty($reminder->payload)) {
				$reminders[$k]->payload = json_decode($reminder->payload);
			}
		}

		return $reminders;
	}

	/**
	 * Returns a list of imminent reminders that were not displayed yet.
	 * 
	 * @param 	int 	$length 	the maximum records to fetch.
	 * 
	 * @return 	array 				list of object records, if any.
	 */
	public function getImminents($length = 10)
	{
		// imminent reminders are meant to not expire in more than 1 day
		$now_info = getdate();
		$lim_max_date = date('Y-m-d H:i:s', mktime(23, 59, 59, $now_info['mon'], ($now_info['mday'] + 1), $now_info['year']));

		// we also grab what was not displayed (missed) of important within the last week
		$lim_min_date = date('Y-m-d H:i:s', mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] - 7), $now_info['year']));

		// build fetch instructions
		$fetch = [
			'after' 	=> $lim_min_date,
			'before' 	=> $lim_max_date,
			'idorder' 	=> 0,
			'completed' => 0,
			'expired' 	=> 0,
			'not_shown' => 1,
			'missed' 	=> 1,
		];

		return $this->loadReminders($fetch, 0, $length);
	}

	/**
	 * Gets a specific reminder by ID.
	 * 
	 * @param 	int 	$rid 	the record ID.
	 * 
	 * @return 	null|object
	 */
	public function getReminder($rid)
	{
		if (empty($rid)) {
			return null;
		}

		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikbooking_reminders'))
			->where($dbo->qn('id') . ' = ' . (int)$rid);

		$dbo->setQuery($q, 0, 1);
		$reminder = $dbo->loadObject();

		if (!$reminder) {
			return null;
		}

		if (!empty($reminder->payload)) {
			$reminder->payload = json_decode($reminder->payload);
		}

		return $reminder;
	}

	/**
	 * Toggles the "displayed" property for a given reminder ID.
	 * 
	 * @param 	int 	$id 		the reminder record ID.
	 * @param 	bool 	$displayed 	the status to set.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function setDisplayed($id, $displayed = true)
	{
		$record = new stdClass;

		$record->id 	   = (int)$id;
		$record->displayed = (int)$displayed;

		return $this->updateReminder($record);
	}

	/**
	 * Inserts a new reminder record object.
	 * 
	 * @param 	object 	$reminder 	the record object to insert.
	 * 
	 * @return 	bool
	 */
	public function saveReminder($reminder)
	{
		if (!is_object($reminder) || !count(get_object_vars($reminder))) {
			$this->setError('Empty or invalid argument');
			return false;
		}

		if (!empty($reminder->payload) && !is_scalar($reminder->payload)) {
			// make sure to JSON encode the payload property
			$reminder->payload = json_encode($reminder->payload);
		}

		$dbo = JFactory::getDbo();

		try {
			$dbo->insertObject('#__vikbooking_reminders', $reminder, 'id');
		} catch (Exception $e) {
			// do nothing
			$this->setError('The query to insert the record failed');
		}

		return (!empty($reminder->id));
	}

	/**
	 * Updates an existing reminder record object.
	 * 
	 * @param 	object 	$reminder 	the record object to update.
	 * 
	 * @return 	bool
	 */
	public function updateReminder($reminder)
	{
		if (!is_object($reminder) || !count(get_object_vars($reminder))) {
			$this->setError('Empty or invalid argument');
			return false;
		}

		if (empty($reminder->id)) {
			$this->setError('Empty reminder id');
			return false;
		}

		if (!empty($reminder->payload) && !is_scalar($reminder->payload)) {
			// make sure to JSON encode the payload property
			$reminder->payload = json_encode($reminder->payload);
		}

		$dbo = JFactory::getDbo();

		try {
			$res = $dbo->updateObject('#__vikbooking_reminders', $reminder, 'id');
		} catch (Exception $e) {
			$this->setError('The query to insert the record failed');
			$res = false;
		}

		return $res;
	}

	/**
	 * Deletes an existing reminder record.
	 * 
	 * @param 	int|object 	$reminder 	the record to remove.
	 * 
	 * @return 	bool
	 */
	public function deleteReminder($reminder)
	{
		if (!is_numeric($reminder) && !is_object($reminder)) {
			return false;
		}

		$reminder_id = null;

		if (is_object($reminder) && !empty($reminder->id)) {
			$reminder_id = (int)$reminder->id;
		} elseif (is_numeric($reminder)) {
			$reminder_id = (int)$reminder;
		}

		if (empty($reminder_id)) {
			return false;
		}

		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->delete($dbo->qn('#__vikbooking_reminders'))
			->where($dbo->qn('id') . ' = ' . $reminder_id);

		$dbo->setQuery($q);
		$dbo->execute();

		return ($dbo->getAffectedRows() > 0);
	}

	/**
	 * Removes the reminders with a due date in the past.
	 * 
	 * @return 	void
	 */
	public function removeExpired()
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->delete($dbo->qn('#__vikbooking_reminders'))
			->where($dbo->qn('duedate') . ' < NOW()');

		$dbo->setQuery($q);
		$dbo->execute();

		return;
	}

	/**
	 * Given two dates, compares the relative differences and returns the information.
	 * The language definitions are supposed to be loaded from the admin section.
	 * 
	 * @param 	string|DateTime 	$date_a 	the date to compare from.
	 * @param 	string|DateTime 	$date_b 	the date to compare against.
	 * 
	 * @return 	array 				false on failure, array with diff otherwise.
	 */
	public function relativeDatesDiff($date_a, $date_b = null)
	{
		if (is_string($date_a)) {
			$date_a = new DateTime($date_a);
		}

		if (!($date_a instanceof DateTime)) {
			$date_a = new DateTime();
		}

		if (is_string($date_b) || empty($date_b)) {
			// by default we compare against now
			if (empty($date_b)) {
				$date_b = new DateTime();
			} else {
				$date_b = new DateTime($date_b);
			}
		}

		if (!($date_b instanceof DateTime)) {
			$date_b = new DateTime();
		}

		// calculate close y-m-d dates
		$fromd_ymd = $date_a->format('Y-m-d');
		$today = date('Y-m-d');
		$yesterday = date('Y-m-d', strtotime('-1 day'));
		$tomorrow = date('Y-m-d', strtotime('+1 day'));

		// get the date interval object of differences
		$dt_interval = $date_a->diff($date_b);

		// compose the associative data to be returned
		$diff_data = [
			'past' 	  	=> ($date_a < $date_b),
			'sameday' 	=> ($fromd_ymd == $date_b->format('Y-m-d')),
			'today'   	=> ($fromd_ymd == $today),
			'yesterday' => ($fromd_ymd == $yesterday),
			'tomorrow' 	=> ($fromd_ymd == $tomorrow),
			'seconds' 	=> $dt_interval->s,
			'minutes' 	=> $dt_interval->i,
			'hours'   	=> $dt_interval->h,
			'days' 	  	=> $dt_interval->d,
			/**
			 * Rely on weeks only if less than a month for better precision.
			 * Weeks is the only value to not be calculated natively in DateInterval.
			 */
			'weeks'   	=> ($dt_interval->m > 0 ? 0 : floor($dt_interval->d / 7)),
			//
			'months'  	=> $dt_interval->m,
			'years'   	=> $dt_interval->y,
			// set the DateTime objects parsed
			'date_a' 	=> $date_a,
			'date_b' 	=> $date_b,
			// prepare the formatted relative difference string
			'relative' 	=> $fromd_ymd,
		];

		// build the relative difference string
		if ($diff_data['today']) {
			$diff_data['relative'] = JText::translate('VBTODAY');
		} elseif ($diff_data['yesterday']) {
			$diff_data['relative'] = JText::translate('VBOYESTERDAY');
		} elseif ($diff_data['tomorrow']) {
			$diff_data['relative'] = JText::translate('VBOTOMORROW');
		} elseif ($diff_data['years'] > 0) {
			// no translations available at the moment for the singular version of "year"
			$diff_num = $diff_data['years'] . ' ' . JText::translate('VBCONFIGSEARCHPMAXDATEYEARS');
			$diff_data['relative'] = JText::sprintf(($diff_data['past'] ? 'VBO_REL_EXP_PAST' : 'VBO_REL_EXP_FUTURE'), strtolower($diff_num));
		} elseif ($diff_data['months'] > 0) {
			$diff_num = $diff_data['months'] . ' ' . JText::translate(($diff_data['months'] > 1 ? 'VBCONFIGSEARCHPMAXDATEMONTHS' : 'VBPVIEWRESTRICTIONSTWO'));
			$diff_data['relative'] = JText::sprintf(($diff_data['past'] ? 'VBO_REL_EXP_PAST' : 'VBO_REL_EXP_FUTURE'), strtolower($diff_num));
		} elseif ($diff_data['weeks'] > 1) {
			// use weeks only if more than one for a better precision
			$diff_num = $diff_data['weeks'] . ' ' . JText::translate(($diff_data['weeks'] > 1 ? 'VBCONFIGSEARCHPMAXDATEWEEKS' : 'VBOWEEK'));
			$diff_data['relative'] = JText::sprintf(($diff_data['past'] ? 'VBO_REL_EXP_PAST' : 'VBO_REL_EXP_FUTURE'), strtolower($diff_num));
		} elseif ($diff_data['days'] > 0) {
			$diff_num = $diff_data['days'] . ' ' . JText::translate(($diff_data['days'] > 1 ? 'VBCONFIGSEARCHPMAXDATEDAYS' : 'VBODAY'));
			$diff_data['relative'] = JText::sprintf(($diff_data['past'] ? 'VBO_REL_EXP_PAST' : 'VBO_REL_EXP_FUTURE'), strtolower($diff_num));
		}

		return $diff_data;
	}

	/**
	 * Tells whether a specific booking ID has got a reminder assigned.
	 * 
	 * @param 	int 	$booking_id 	the reservation ID to check.
	 * @param 	array 	$payload 		optional payload to compare.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 * @since 	1.16.5 (J) - 1.6.5 (WP) added argument $payload.
	 */
	public function bookingHasReminder($booking_id, array $payload = [])
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true);

		$q->select('COUNT(1)')
			->from($dbo->qn('#__vikbooking_reminders'))
			->where($dbo->qn('idorder') . ' = ' . (int)$booking_id);

		if ($payload) {
			$q->where($dbo->qn('payload') . ' = ' . $dbo->q(json_encode($payload)));
		}

		$dbo->setQuery($q);

		return (bool)$dbo->loadResult();
	}

	/**
	 * Gathers a list of Airbnb reservations that may require a host-to-guest review.
	 * 
	 * @param 	int 	$lim_start_ts 	the checkout timestamp to use as limit.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 */
	public function gatherAirbnbReservationsCheckedOut($lim_start_ts = 0)
	{
		if (!$lim_start_ts) {
			// default to checkout two weeks ago
			$lim_start_ts = strtotime("-14 days", strtotime(date('Y-m-d')));
		}

		// maximum checkout date must be 14 days ahead from checkout
		$lim_end_ts = strtotime("+14 days", $lim_start_ts);

		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select($dbo->qn([
				'o.id',
				'o.status',
				'o.checkin',
				'o.checkout',
				'o.idorderota',
				'o.channel',
			]))
			->from($dbo->qn('#__vikbooking_orders', 'o'))
			->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'))
			->where($dbo->qn('o.checkout') . ' >= ' . $lim_start_ts)
			->where($dbo->qn('o.checkout') . ' <= ' . $lim_end_ts)
			->where($dbo->qn('o.channel') . ' LIKE ' . $dbo->q('airbnbapi%'));

		$dbo->setQuery($q);

		return $dbo->loadAssocList();
	}
}
