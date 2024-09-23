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
 * Browser notifications scheduler handler.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationScheduler
{
	/**
	 * @var  mixed
	 */
	private $data = null;

	/**
	 * @var  JDocument
	 */
	private $doc = null;

	/**
	 * Proxy to immediately access the object.
	 * 
	 * @param 	mixed 		$data 		optional data to set.
	 * @param 	JDocument 	$doc 	optional document object.
	 * 
	 * @return 	self
	 */
	public static function getInstance($data = null, $doc = null)
	{
		return new static($data, $doc);
	}

	/**
	 * Class constructor.
	 * 
	 * @param 	mixed 	$data
	 */
	public function __construct($data = null, $doc = null)
	{
		// bind data
		$this->data = $data;

		// set the document object
		if (!$doc) {
			$doc = JFactory::getDocument();
		}
		$this->doc = $doc;
	}

	/**
	 * Given a reminder record object, composes the notification
	 * data object to schedule a browser notification.
	 * 
	 * @param 	object 	$reminder 			the reminder record object.
	 * @param 	int 	$max_future_hours 	optional value to check if the reminder
	 * 										is too far ahead in the future.
	 * 
	 * @return 	null|object
	 */
	public function getReminderDataObject($reminder, $max_future_hours = 0)
	{
		if (!is_object($reminder) || empty($reminder->id) || empty($reminder->duedate)) {
			return null;
		}

		if ($max_future_hours > 0) {
			// this reminder requires a validation of the future time
			$now_info = getdate();
			$next_hours = ($now_info['hours'] + $max_future_hours);
			$max_future_ts = mktime($next_hours, $now_info['minutes'], 0, $now_info['mon'], $now_info['mday'], $now_info['year']);
			$scheduled_ts = strtotime($reminder->duedate);
			if ($scheduled_ts > $max_future_ts) {
				// no need to schedule a notification for this reminder as it's too far ahead
				return null;
			}
		}

		// get a new notification data object
		$notification = VBONotificationDataReminder::getInstance();

		// immediately parse the reminder record to check its validity and importance
		$notification->parseReminderImportance($reminder);

		// set data
		$notification->setReminderId($reminder->id);
		$notification->setDateTime($reminder->duedate);
		if (!$reminder->usetime) {
			$notification->setNoTime(true);
		}

		return $notification->toDataObject();
	}

	/**
	 * Builds a notification display data object for a recent history
	 * event involving a reservation. This kind of notification will
	 * be actually dispatched immediately with no scheduling, and so
	 * so it won't provide a build-URL, it will provide display data.
	 * 
	 * @param 	object 	$h_event 	history record object to parse.
	 * 
	 * @return 	null|object 
	 */
	public function getHistoryDataObject($h_event)
	{
		if (!is_object($h_event) || empty($h_event->idorder)) {
			return null;
		}

		// get a new notification data object
		$notification = VBONotificationDataHistory::getInstance();

		// inject all record properties as "reserved" properties
		foreach ($h_event as $prop => $val) {
			// properties starting with an underscore are "reserved"
			$notification->set("_{$prop}", $val);
		}

		// the notification will be dispatched immediately
		$notification->setDateTime('now');

		// let the notification build the display data
		$notification->buildDisplayData();

		// return the notification data-object
		return $notification->toDataObject();
	}

	/**
	 * Given a list of recent worthy-of-notification history events,
	 * builds a list of notification data objects of type "history".
	 * Such notifications will be actually dispatched immediately,
	 * with no needs to schedule a notification timer. Moreover,
	 * these notifications will contain immediately the necessary
	 * display data without needing to build them through a callback.
	 * 
	 * @param 	array 	$events 	list of recent history record objects.
	 * 
	 * @return 	array 				list of browser notification display data objects.
	 */
	public function buildHistoryDataObjects($events = [])
	{
		if (!is_array($events) || !$events) {
			return [];
		}

		// container of notification display data objects
		$data_objects = [];

		foreach ($events as $history) {
			$data_object = $this->getHistoryDataObject($history);
			if ($data_object) {
				$data_objects[] = $data_object;
			}
		}

		return $data_objects;
	}

	/**
	 * Builds a notification display data object for a recent guest
	 * review involving a reservation. This kind of notification will
	 * be actually dispatched immediately with no scheduling, and so
	 * so it won't provide a build-URL, it will provide display data.
	 * 
	 * @param 	object 	$review 	guest activity record object to parse.
	 * 
	 * @return 	null|object 
	 */
	public function getReviewDataObject($review)
	{
		if (!is_object($review) || empty($review->id_review) || empty($review->idorder)) {
			return null;
		}

		// get a new notification data object
		$notification = VBONotificationDataReview::getInstance();

		// inject all record properties as "reserved" properties
		foreach ($review as $prop => $val) {
			// properties starting with an underscore are "reserved"
			$notification->set("_{$prop}", $val);
		}

		// the notification will be dispatched immediately
		$notification->setDateTime('now');

		// let the notification build the display data
		$notification->buildDisplayData();

		// return the notification data-object
		return $notification->toDataObject();
	}

	/**
	 * Given a list of recent guest activity records, builds a list
	 * of notification data objects of type "review".
	 * Such notifications will be actually dispatched immediately,
	 * with no needs to schedule a notification timer. Moreover,
	 * these notifications will contain immediately the necessary
	 * display data without needing to build them through a callback.
	 * 
	 * @param 	array 	$reviews 	list of recent guest activity record objects.
	 * 
	 * @return 	array 				list of browser notification display data objects.
	 */
	public function buildReviewDataObjects($reviews = [])
	{
		if (!is_array($reviews) || !$reviews) {
			return [];
		}

		// container of notification display data objects
		$data_objects = [];

		foreach ($reviews as $review) {
			$data_object = $this->getReviewDataObject($review);
			if ($data_object) {
				$data_objects[] = $data_object;
			}
		}

		return $data_objects;
	}

	/**
	 * Builds a notification display data object for a recent guest
	 * message involving a reservation. This kind of notification will
	 * be actually dispatched immediately with no scheduling, and so
	 * so it won't provide a build-URL, it will provide display data.
	 * 
	 * @param 	object 	$message 	guest activity record object to parse.
	 * 
	 * @return 	null|object 
	 */
	public function getGuestMessageDataObject($message)
	{
		if (!is_object($message) || empty($message->idorder)) {
			return null;
		}

		// get a new notification data object
		$notification = VBONotificationDataMessage::getInstance();

		// inject all record properties as "reserved" properties
		foreach ($message as $prop => $val) {
			// properties starting with an underscore are "reserved"
			$notification->set("_{$prop}", $val);
		}

		// the notification will be dispatched immediately
		$notification->setDateTime('now');

		// let the notification build the display data
		$notification->buildDisplayData();

		// return the notification data-object
		return $notification->toDataObject();
	}

	/**
	 * Given a list of recent guest activity records, builds a list
	 * of notification data objects of type (guest) "message".
	 * Such notifications will be actually dispatched immediately,
	 * with no needs to schedule a notification timer. Moreover,
	 * these notifications will contain immediately the necessary
	 * display data without needing to build them through a callback.
	 * 
	 * @param 	array 	$messages 	list of recent guest activity record objects.
	 * 
	 * @return 	array 				list of browser notification display data objects.
	 */
	public function buildGuestMessageDataObjects($messages = [])
	{
		if (!is_array($messages) || !$messages) {
			return [];
		}

		// container of notification display data objects
		$data_objects = [];

		foreach ($messages as $message) {
			$data_object = $this->getGuestMessageDataObject($message);
			if ($data_object) {
				$data_objects[] = $data_object;
			}
		}

		return $data_objects;
	}

	/**
	 * Enqueues a list of notification objects for the overdue reminders.
	 * This should be called upon a regular page loading, not during AJAX
	 * requests, as it attachs a script declaration to the document.
	 * 
	 * @param 	array 	$reminders 	list of reminder record objects.
	 * 
	 * @return 	void
	 */
	public function enqueueReminders(array $reminders)
	{
		// build the queue of reminders to be scheduled
		$queue = [];

		foreach ($reminders as $reminder) {
			if (empty($reminder->duedate)) {
				continue;
			}

			// compose reminder data object
			$notification = $this->getReminderDataObject($reminder);
			if ($notification) {
				// push reminder to queue
				$queue[] = $notification;
			}
		}

		if (!$queue) {
			return;
		}

		$js_queue = json_encode($queue);

		$this->doc->addScriptDeclaration(
<<<JS
jQuery(function() {
	setTimeout(() => {
		VBOCore.enqueueNotifications($js_queue);
	}, 300);
});
JS
		);
	}

	/**
	 * Registers a list of widgets watching data.
	 * 
	 * @param 	array  $watch_data  associative list of widgets
	 * 								and preloaded watch data.
	 * 
	 * @return 	void
	 */
	public function registerWatchData($watch_data = [])
	{
		if (!is_array($watch_data) || !$watch_data) {
			return;
		}

		$js_watch_data = json_encode($watch_data);

		$this->doc->addScriptDeclaration(
<<<JS
jQuery(function() {
	setTimeout(() => {
		VBOCore.registerWatchData($js_watch_data);
	}, 300);
});
JS
		);
	}
}
