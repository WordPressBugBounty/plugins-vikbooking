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
 * Notification Center class handler.
 * 
 * @since 	1.16.8 (J) - 1.6.8 (WP)
 */
final class VBONotificationCenter
{
	/** @var  array */
	private static $count_cache = [];

	/** @var  bool */
	private static $lang_loaded = false;

	/** @var  int */
	private $last_found_notifications = 0;

	/**
	 * Class constructor.
	 */
	public function __construct()
	{}

	/**
	 * Returns a list of notification groups.
	 * 
	 * @param 	bool 	$global 	whether the "All" global group should be included.
	 * 
	 * @return 	array
	 */
	public function getGroups($global = true)
	{
		$dbo = JFactory::getDbo();

		$groups = [];

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('group'))
				->select('SUM((' . $dbo->qn('read') . ' + 1) % 2) AS ' . $dbo->qn('badge_count'))
				->from($dbo->qn('#__vikbooking_notifications'))
				->group($dbo->qn('group'))
				->order($dbo->qn('badge_count') . ' DESC')
		);

		foreach ($dbo->loadAssocList() as $group) {
			$name_key   = 'VBO_NOTIFS_GROUP_' . strtoupper($group['group']);
			$name_lang  = JText::translate($name_key);
			$group_name = $name_key != $name_lang ? $name_lang : ucwords(str_replace('_', ' ', strtolower($group['group'])));
			$groups[]   = [
				'id'   => $group['group'],
				'name' => $group_name,
				'badge_count' => $group['badge_count'],
			];
		}

		if ($global) {
			// prepend the "global" notifications group
			array_unshift($groups, [
				'id'   => '',
				'name' => JText::translate('VBNEWCOUPONEIGHT'),
				'badge_count' => array_sum(array_column($groups, 'badge_count')),
			]);
		}

		return $groups;
	}

	/**
	 * In order to avoid displaying a badge of "99+" unread notifications,
	 * we mark as read those notifications older than 14 days to give more
	 * dynamism to the whole Notifications Center and related badge counters.
	 * 
	 * @param 	int 	$age 	Age of notifications expressed in days.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	public function readOldNotifications($age = 14)
	{
		$session = JFactory::getSession();

		if ($session->get('vbo_nc_readold')) {
			// check was made already
			return;
		}

		// turn session flag on
		$session->set('vbo_nc_readold', 1);

		// ensure age is a valid value in days
		$age = abs((int) $age);
		$age = $age ?: 14;

		// build "old" date limit
		$date_limit = JFactory::getDate('now')->modify("-{$age} days")->toSql();

		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_notifications'))
				->set($dbo->qn('read') . ' = 1')
				->where($dbo->qn('createdon') . ' < ' . $dbo->q($date_limit))
		);

		$dbo->execute();
	}

	/**
	 * Counts the number of unread notifications.
	 * 
	 * @param 	string 	$group 	optional group identifier.
	 * 
	 * @return 	int
	 */
	public function countUnread(string $group = '')
	{
		$group_key = $group ?: 0;

		if (isset(static::$count_cache[$group_key])) {
			return static::$count_cache[$group_key];
		}

		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select('COUNT(*)')
			->from($dbo->qn('#__vikbooking_notifications'))
			->where($dbo->qn('read') . ' = 0');

		if ($group) {
			$q->where($dbo->qn('group') . ' = ' . $dbo->q($group));
		}

		$dbo->setQuery($q);
		$tot_unread = (int) $dbo->loadResult();

		static::$count_cache[$group_key] = $tot_unread;

		return static::$count_cache[$group_key];
	}

	/**
	 * Loads a list of notifications.
	 * 
	 * @param 	int 	$start 		query limit start.
	 * @param 	int 	$lim 		query limit count.
	 * @param 	array 	$filters 	optional list of column filters.
	 * @param 	int 	$min_id 	optional minimum notification ID.
	 */
	public function loadNotifications(int $start = 0, int $lim = null, array $filters = [], int $min_id = 0)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select('SQL_CALC_FOUND_ROWS *')
			->from($dbo->qn('#__vikbooking_notifications'));

		foreach ($filters as $column => $filter) {
			if (is_scalar($filter)) {
				$q->where($dbo->qn($column) . ' = ' . $dbo->q($filter));
			} else {
				foreach ($filter as $filter_data) {
					if (!is_array($filter_data) || !isset($filter_data['operand']) || !isset($filter_data['value'])) {
						continue;
					}
					$q->where($dbo->qn($column) . ' ' . $filter_data['operand'] . ' ' . $dbo->q($filter_data['value']));
				}
			}
		}

		if ($min_id && empty($filters['id'])) {
			$q->where($dbo->qn('id') . ' > ' . $min_id);
		}

		$q->order($dbo->qn('createdon') . ' DESC');
		$q->order($dbo->qn('read') . ' ASC');

		$dbo->setQuery($q, $start, $lim);
		$notifications = $dbo->loadObjectList();

		// count the total number of found rows
		$this->last_found_notifications = 0;
		if ($notifications) {
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select('FOUND_ROWS()')
			);
			$this->last_found_notifications = (int) $dbo->loadResult();
		}

		// grab the non-empty booking IDs from the list of objects
		$booking_ids = array_filter(array_column($notifications, 'idorder'));

		// load the customer names and profile pictures for all the involved booking IDs
		$customer_infos = [];
		if ($booking_ids) {
			$booking_ids = array_unique(array_map(function($id) {
				return (int) $id;
			}, $booking_ids));

			$dbo->setQuery(
				$dbo->getQuery(true)
					->select([
						$dbo->qn('co.idorder'),
						$dbo->qn('c.first_name'),
						$dbo->qn('c.last_name'),
						$dbo->qn('c.pic'),
					])
					->from($dbo->qn('#__vikbooking_customers_orders', 'co'))
					->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('co.idcustomer') . ' = ' . $dbo->qn('c.id'))
					->where($dbo->qn('co.idorder') . ' IN (' . implode(', ', $booking_ids) . ')')
			);

			$customer_infos = $dbo->loadObjectList();
		}

		// append the customer information properties to each notification and adjust properties
		foreach ($notifications as &$notification) {
			// check for call-to-action data
			if ($notification->cta_data) {
				$cta_data = json_decode($notification->cta_data);
				if (is_object($cta_data)) {
					// overwrite property with the decoded payload
					$notification->cta_data = $cta_data;
				}
			}

			// set default null values
			$notification->customer_name = null;
			$notification->customer_pic  = null;

			// parse all customer information objects
			foreach ($customer_infos as $customer_info) {
				if (!$notification->idorder || $customer_info->idorder != $notification->idorder) {
					// not the booking ID we want to look for
					continue;
				}

				if (!empty($customer_info->first_name) || !empty($customer_info->last_name)) {
					// populate customer name
					$notification->customer_name = trim($customer_info->first_name . ' ' . $customer_info->last_name);
				}

				if (!empty($customer_info->pic)) {
					// populate customer profile picture
					$notification->customer_pic = $customer_info->pic;
				}

				// go to the next notification
				break;
			}
		}

		// unset last reference
		unset($notification);

		// return the list of notification objects
		return $notifications;
	}

	/**
	 * Counts the last found notification rows.
	 * 
	 * @return 	int
	 * 
	 * @see 	loadNotifications()
	 */
	public function countFoundNotifications()
	{
		return $this->last_found_notifications;
	}

	/**
	 * Reads some or all notifications (if none given), and returns
	 * a list of groups involved for the given notification IDs.
	 * 
	 * @param 	array 	$notification_ids 	list of IDs or empty list.
	 * 
	 * @return 	array 						list of group identifiers involved.
	 */
	public function readNotifications(array $notification_ids)
	{
		$dbo = JFactory::getDbo();

		// sanitize the list
		$notification_ids = array_filter(array_map('intval', $notification_ids));

		// read the requested notifications
		$q = $dbo->getQuery(true)
			->update($dbo->qn('#__vikbooking_notifications'))
			->set($dbo->qn('read') . ' = 1');

		if ($notification_ids) {
			$q->where($dbo->qn('id') . ' IN (' . implode(', ', $notification_ids) . ')');
		}

		$dbo->setQuery($q);
		$dbo->execute();

		// get the groups involved
		$q = $dbo->getQuery(true)
			->select($dbo->qn('group'))
			->from($dbo->qn('#__vikbooking_notifications'))
			->group($dbo->qn('group'));

		if ($notification_ids) {
			$q->where($dbo->qn('id') . ' IN (' . implode(', ', $notification_ids) . ')');
		}

		$dbo->setQuery($q);

		// build a list of groups involved (if any notification is saved, hence was updated)
		$involved = [];
		foreach ($dbo->loadObjectList() as $record) {
			$involved[] = $record->group;
		}

		if ($involved) {
			// prepend the "global" notifications group as an empty value
			array_unshift($involved, 0);
		}

		return $involved;
	}

	/**
	 * Reads the notifications matching the given criterias. Useful for reading
	 * the notifications when opening a specific context, like a chat thread.
	 * 
	 * @param 	array 	$criteria 	associative list of column-value criterias.
	 * 
	 * @return 	int 				list of group identifiers involved.
	 */
	public function readMatchingNotifications(array $criteria)
	{
		if (!$criteria) {
			// do not proceed
			return 0;
		}

		$dbo = JFactory::getDbo();

		// start building the query for reading the matching notifications
		$q = $dbo->getQuery(true)
			->update($dbo->qn('#__vikbooking_notifications'))
			->set($dbo->qn('read') . ' = 1');

		foreach ($criteria as $col => $value) {
			$q->where($dbo->qn($col) . ' = ' . $dbo->q($value));
		}

		try {
			$dbo->setQuery($q);
			$dbo->execute();

			return (int) $dbo->getAffectedRows();
		} catch (Exception $e) {
			// silently catch any database error
		}

		return 0;
	}

	/**
	 * Processes a list of notification objects to store.
	 * 
	 * @param 	array 	$notifications 	list of notification objects.
	 * 
	 * @return 	array 	associative list of data stored.
	 * 
	 * @throws 	Exception
	 */
	public function store(array $notifications)
	{
		$result = [
			'new_notifications' => 0,
		];

		foreach ($notifications as $notification) {
			// wrap the notification array/object into a registry
			$notif_registry = new VBONotificationElements($notification);

			if ($this->storeNotification($notif_registry)) {
				// record was stored successfully
				$result['new_notifications']++;

				// parse the next one
				continue;
			}

			if ($notif_registry->getError()) {
				// abort only in case of error
				throw new Exception($notif_registry->getError(), $notif_registry->getErrorCode());
			}
		}

		return $result;
	}

	/**
	 * Parses and stores a notification registry, if compliant.
	 * 
	 * @param 	VBONotificationElements  $notification  the notification registry.
	 * 
	 * @return 	bool
	 */
	private function storeNotification(VBONotificationElements $notification)
	{
		if (!$notification->get('sender') || !$notification->get('type')) {
			$notification->setError('Notification sender and type are mandatory');
			return false;
		}

		if (!$notification->get('title') && !$notification->get('summary')) {
			$notification->setError('Either title or summary is mandatory');
			return false;
		}

		if (strcasecmp($notification->get('sender', ''), 'website')) {
			// ensure language definitions are loaded for non-website notifications
			$this->loadLanguageDefs();
		}

		// ensure the same notification does not exist already
		if ($this->signatureExists($notification->getSignature())) {
			// abort without raising any errors to skip a duplicate notification
			return false;
		}

		$dbo = JFactory::getDbo();

		// build notification record to store
		$record = new stdClass;

		$record->signature  = $notification->getSignature();
		$record->group 	    = $notification->getGroup();
		$record->type 	    = $notification->getType();
		$record->title 	    = $notification->getTitle();
		$record->summary    = $notification->getSummary();
		$record->cta_data   = $notification->getCallToActionData();
		$record->idorder    = $notification->getReservationID();
		$record->idorderota = $notification->getOTAReservationID();
		$record->channel    = $notification->getChannel();
		$record->createdon  = $notification->getDate();

		if (!$dbo->insertObject('#__vikbooking_notifications', $record, 'id')) {
			$notification->setError('Query failed when inserting the record');
			return false;
		}

		return true;
	}

	/**
	 * Parses a new guest message received through the Channel Manager from an OTA.
	 * 
	 * @param 	object 	$thread 	the thread record in VCM.
	 * @param 	object 	$message 	the message record in VCM.
	 * 
	 * @return 	array 	associative list of data stored.
	 * 
	 * @throws 	Exception
	 */
	public function parseNewGuestMessage($thread, $message)
	{
		if (!is_object($thread) || !is_object($message)) {
			throw new Exception('Invalid message object provided', 400);
		}

		// ensure language definitions are loaded
		$this->loadLanguageDefs();

		// build guest message notification payload
		$notification = [
			'sender'     => 'guests',
			'type'       => 'guest_message',
			'title'      => JText::sprintf('VBO_MESSAGE_FROM', $message->sender_name ?? 'guest'),
			'summary'    => $message->content ?? '',
			'idorder'    => $thread->idorder ?? null,
			'idorderota' => $thread->idorderota ?? null,
			'channel'    => $thread->channel ?? null,
		];

		// store the guest message notification(s)
		return $this->store([$notification]);
	}

	/**
	 * Checks if new notifications should be downloaded or generated, at most once per day.
	 * The method is usually invoked through the administrator section of VikBooking.
	 * 
	 * @return 	void
	 */
	public function downloadNotifications()
	{
		$session = JFactory::getSession();

		if ($session->get('vbo_nc_download')) {
			// check was made already
			return;
		}

		// turn session flag on
		$session->set('vbo_nc_download', 1);

		$config = VBOFactory::getConfig();

		$last_download = $config->get('nc_last_download', '');
		$today_dt      = date('Y-m-d');

		if ($last_download == $today_dt) {
			// check was made already
			return;
		}

		// turn db flag on
		$config->set('nc_last_download', $today_dt);

		// check if automatic notifications should be generated.
		if (!empty($last_download)) {
			$last_info   = getdate(strtotime($last_download));
			$prev_m_info = getdate(strtotime('-1 month'));
			if ($last_info['mon'] == $prev_m_info['mon'] && $last_info['year'] == $prev_m_info['year']) {
				// we are on a new month, generate a finance notification for the past month
				$this->generatePastMonthNotification($last_info);
			}
		}

		// check if the Channel Manager can download new notifications

		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			// channel manager not installed
			return;
		}

		if (!class_exists('VCMNotificationsHelper')) {
			// channel manager is outdated
			return;
		}

		// let the CM request to download new notifications, if any eligible channel is configured
		try {
			(new VCMNotificationsHelper)
				->downloadNotifications();
		} catch (Throwable $e) {
			// silently catch the error
		}
	}

	/**
	 * Checks if a notification exists from the given signature.
	 * 
	 * @param 	string 	$signature 	the notification elements signature.
	 * 
	 * @return 	bool
	 */
	private function signatureExists(string $signature)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('id'))
				->from($dbo->qn('#__vikbooking_notifications'))
				->where($dbo->qn('signature') . ' = ' . $dbo->q($signature))
		);

		if ($dbo->loadResult()) {
			return true;
		}

		return false;
	}

	/**
	 * Generates a financial notification for the stats of the past month.
	 * 
	 * @param 	array 	$month 	the past month date information.
	 * 
	 * @return 	void
	 */
	private function generatePastMonthNotification(array $month)
	{
		$months_map = [
			JText::translate('VBMONTHONE'),
			JText::translate('VBMONTHTWO'),
			JText::translate('VBMONTHTHREE'),
			JText::translate('VBMONTHFOUR'),
			JText::translate('VBMONTHFIVE'),
			JText::translate('VBMONTHSIX'),
			JText::translate('VBMONTHSEVEN'),
			JText::translate('VBMONTHEIGHT'),
			JText::translate('VBMONTHNINE'),
			JText::translate('VBMONTHTEN'),
			JText::translate('VBMONTHELEVEN'),
			JText::translate('VBMONTHTWELVE'),
		];

		// build past month finance notification payload
		$notification = [
			'sender'         => 'website',
			'type'           => 'info',
			'title'          => $months_map[$month['mon'] - 1] . ' ' . $month['year'],
			'summary'        => JText::translate('VBO_CHECK_HOW_WENT'),
			'label'          => JText::translate('VBO_W_FINANCE_TITLE'),
			'widget'         => 'finance',
			'widget_options' => [
				'fromdate' => date('Y-m-01', $month[0]),
				'todate'   => date('Y-m-t', $month[0]),
				'type'     => 'month',
			],
		];

		try {
			// store the notification(s)
			$this->store([$notification]);
		} catch (Throwable $e) {
			// silently catch the error
		}
	}

	/**
	 * Attempts to load the language definitions in case the client/CMS requires so.
	 * 
	 * @return 	void
	 */
	private function loadLanguageDefs()
	{
		if (static::$lang_loaded) {
			return;
		}

		// turn flag on
		static::$lang_loaded = true;

		$lang = JFactory::getLanguage();

		if (VBOPlatformDetection::isJoomla()) {
			$lang->load('com_vikbooking', JPATH_ADMINISTRATOR, $lang->getTag(), true);
			$lang->load('joomla', JPATH_ADMINISTRATOR, $lang->getTag(), true);
		} else {
			$lang->load('com_vikbooking', VIKBOOKING_LANG, $lang->getTag(), true);

			// make sure to register the language handler
			$lib_base_path = defined('VIKBOOKING_LIBRARIES') ? VIKBOOKING_LIBRARIES : '';
			if (!$lib_base_path && defined('VIKCHANNELMANAGER_LIBRARIES')) {
				$lib_base_path = str_replace('vikchannelmanager' . DIRECTORY_SEPARATOR . 'libraries', 'vikbooking' . DIRECTORY_SEPARATOR . 'libraries', VIKCHANNELMANAGER_LIBRARIES);
			}

			if ($lib_base_path) {
				$lang->attachHandler($lib_base_path . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'admin.php', 'vikbooking');
			}
		}
	}
}
