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
 * Registry for admin widgets watch-data to check for new browser notifications.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationWatchdata extends JObject
{
	/**
	 * @var 	array
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	private $pushed_data = [];

	/**
	 * Proxy for immediately getting the object and bind data.
	 * 
	 * @param 	array|object 	$data 	the watch-data payload to bind.
	 */
	public static function getInstance($data = null)
	{
		return new static($data);
	}

	/**
	 * Check if the given activity (guest message) matches the pushed data information.
	 * If it does, it means a Push notification for the same event was clicked already.
	 * 
	 * @param 	object 	$activity 	the latest activity (guest message) found.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function matchPushedGuestMessage($activity)
	{
		if (!$this->pushed_data || !is_object($activity) || !isset($activity->idorder) || !isset($activity->content)) {
			return false;
		}

		foreach ($this->pushed_data as $pushed) {
			// make sure objects are converted into arrays
			$pushed = (array)$pushed;

			if (!$pushed || empty($pushed['id']) || empty($pushed['preview'])) {
				continue;
			}

			if ($pushed['id'] != $activity->idorder) {
				continue;
			}

			if (strpos($activity->content, $pushed['preview']) === 0) {
				// match found, the same guest message was clicked already from a Push notification
				return true;
			}
		}

		return false;
	}

	/**
	 * Filters a list of latest events worth a notification by looking at what
	 * Push notifications were dispatched already for some reservations.
	 * 
	 * @param 	array 	$events 	the latest worthy events found.
	 * 
	 * @return 	array 				the filtered latest events with keys reset.
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function filterPushedReservations(array $events)
	{
		if (!$this->pushed_data) {
			return $events;
		}

		foreach ($events as $k => $event) {
			if (!is_object($event) || !isset($event->idorder)) {
				continue;
			}

			foreach ($this->pushed_data as $pushed) {
				// make sure objects are converted into arrays
				$pushed = (array)$pushed;

				if (!$pushed || empty($pushed['id'])) {
					continue;
				}

				if ($pushed['id'] == $event->idorder) {
					// match found
					unset($events[$k]);
					continue 2;
				}

				if (isset($event->idorderota) && $pushed['id'] == $event->idorderota) {
					// match found
					unset($events[$k]);
					continue 2;
				}
			}
		}

		return array_values($events);
	}

	/**
	 * Gets the list of the Push notifications data clicked.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function getPushedData()
	{
		return $this->pushed_data;
	}

	/**
	 * Sets the information about the Push notifications data clicked.
	 * 
	 * @param 	array 	$data 	list of push notification payloads clicked.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function setPushedData(array $data)
	{
		$this->pushed_data = $data;

		return $this;
	}
}
