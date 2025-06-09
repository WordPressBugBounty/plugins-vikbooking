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
 * VikBooking bookings controller.
 *
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingControllerBookings extends JControllerAdmin
{
	/**
	 * AJAX endpoint to search for an extra service name.
	 * 
	 * @return 	void
	 */
	public function search_service()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();

		$service_name = VikRequest::getString('service_name', '', 'request');
		$max_results  = VikRequest::getInt('max_results', 10, 'request');

		$sql_term = $dbo->quote("%{$service_name}%");
		$sql_clause = !empty($service_name) ? 'LIKE ' . $sql_term : 'IS NOT NULL';

		$q = "SELECT `or`.`idorder`, `or`.`idroom`, `or`.`adults`, `or`.`children`, `or`.`extracosts`, `o`.`days` AS `nights`, `o`.`ts`, `r`.`name` AS `room_name`
			FROM `#__vikbooking_ordersrooms` AS `or`
			LEFT JOIN `#__vikbooking_orders` AS `o` ON `or`.`idorder`=`o`.`id`
			LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id`
			WHERE `or`.`extracosts` {$sql_clause}
			ORDER BY `or`.`idorder` DESC";
		$dbo->setQuery($q, 0, $max_results);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no results
			VBOHttpDocument::getInstance()->json([]);
		}

		$results = $dbo->loadAssocList();

		$matching_services = [];

		foreach ($results as $k => $result) {
			$extra_services = json_decode($result['extracosts'], true);
			if (empty($extra_services)) {
				continue;
			}
			foreach ($extra_services as $extra_service) {
				if (empty($service_name) || stristr($extra_service['name'], $service_name) !== false || stristr($service_name, $extra_service['name']) !== false) {
					// matching service found
					$matching_service = $result;
					unset($matching_service['extracosts']);
					$matching_service['service'] = $extra_service;
					$matching_service['service']['format_cost'] = VikBooking::getCurrencySymb() . ' ' . VikBooking::numberFormat($extra_service['cost']);
					$matching_service['format_dt'] = VikBooking::formatDateTs($result['ts']);
					// push result
					$matching_services[] = $matching_service;
					if (count($matching_services) >= $max_results) {
						break 2;
					}
				}
			}
		}

		// output the JSON encoded list of matching results found
		VBOHttpDocument::getInstance()->json($matching_services);
	}

	/**
	 * AJAX endpoint to count the number of uses for various coupon codes.
	 * 
	 * @return 	void
	 */
	public function coupons_use_count()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();

		$coupon_codes = VikRequest::getVar('coupon_codes', array());

		$use_counts = [];

		foreach ($coupon_codes as $coupon_code) {
			$q = "SELECT COUNT(*) FROM `#__vikbooking_orders` WHERE `coupon` LIKE " . $dbo->quote("%;{$coupon_code}");
			$dbo->setQuery($q);
			$dbo->execute();
			$use_counts[] = [
				'code'  => $coupon_code,
				'count' => (int)$dbo->loadResult(),
			];
		}

		// output the JSON encoded list of coupon use counts
		VBOHttpDocument::getInstance()->json($use_counts);
	}

	/**
	 * AJAX endpoint to dynamically search for customers. Compatible with select2.
	 * 
	 * @return 	void
	 */
	public function customers_search()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();

		$term = VikRequest::getString('term', '', 'request');

		$response = [
			'results' => [],
			'pagination' => [
				'more' => false,
			],
		];

		if (empty($term)) {
			// output the JSON object with no results
			VBOHttpDocument::getInstance()->json($response);
		}

		$sql_term = $dbo->quote("%{$term}%");

		$q = "SELECT `c`.`id`, `c`.`first_name`, `c`.`last_name`, `c`.`country`, 
			(SELECT COUNT(*) FROM `#__vikbooking_customers_orders` AS `co` WHERE `co`.`idcustomer`=`c`.`id`) AS `tot_bookings` 
			FROM `#__vikbooking_customers` AS `c` 
			WHERE CONCAT_WS(' ', `c`.`first_name`, `c`.`last_name`) LIKE {$sql_term} 
			OR `email` LIKE {$sql_term} 
			ORDER BY `c`.`first_name` ASC, `c`.`last_name` ASC;";
		$dbo->setQuery($q);
		$customers = $dbo->loadAssocList();

		if ($customers) {
			foreach ($customers as $k => $customer) {
				$customers[$k]['text'] = trim($customer['first_name'] . ' ' . $customer['last_name']) . ' (' . $customer['tot_bookings'] . ')';
			}
			// push results found
			$response['results'] = $customers;
		}

		// output the JSON encoded object with results found
		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * AJAX endpoint to dynamically search for rooms. Compatible with select2.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function rooms_search()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$term = $app->input->getString('term', '');

		$response = [
			'results' => [],
			'pagination' => [
				'more' => false,
			],
		];

		if (empty($term)) {
			// output the JSON object with no results
			VBOHttpDocument::getInstance($app)->json($response);
		}

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select([
					$dbo->qn('id'),
					$dbo->qn('name', 'text'),
					$dbo->qn('img'),
				])
				->from($dbo->qn('#__vikbooking_rooms'))
				->where($dbo->qn('name') . ' LIKE ' . $dbo->q("%{$term}%"))
				->order($dbo->qn('avail') . ' DESC')
				->order($dbo->qn('name') . ' ASC')
		);

		// set results found
		$response['results'] = $dbo->loadAssocList();

		// load and map mini thumbnails
		$mini_thumbnails = VBORoomHelper::getInstance()->loadMiniThumbnails($response['results']);
		$response['results'] = array_map(function($room) use ($mini_thumbnails) {
			if ($mini_thumbnails[$room['id']] ?? '') {
				// set mini thumbnail URL
				$room['img'] = $mini_thumbnails[$room['id']];
			} else {
				unset($room['img']);
			}
			return $room;
		}, $response['results']);

		// output the JSON encoded object with results found
		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * AJAX endpoint to dynamically search for bookings. Compatible with select2.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function bookings_search()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$booking_key = trim($app->input->getString('term', ''));
		$booking_status = array_values(array_filter((array) $app->input->get('status', [], 'array')));

		$response = [
			'results' => [],
			'pagination' => [
				'more' => false,
			],
		];

		if (empty($booking_key)) {
			// output the JSON object with no results
			VBOHttpDocument::getInstance($app)->json($response);
		}

		// attempt to detect a booking ID
		$booking_id = 0;
		if (preg_match("/^[0-9]+$/", $booking_key)) {
			// only numbers should be a booking ID
			$booking_id = $booking_key;
		} elseif (preg_match('/^(?=.*?\d)(?=.*?[A-Z])[A-Z\d]+$/', $booking_key)) {
			/**
			 * Matched both numbers and upper-case letters, so it has to be an OTA booking ID, not a customer name.
			 * Regex breakdown:
			 * beginning of string
			 * lookahead for at least one digit
			 * lookahead for at least one upper-case letter
			 * match one or more upper-case letters or digits
			 * end of string
			 */
			$booking_id = $booking_key;
		}

		// start the query
		$q = $dbo->getQuery(true)
			->select([
				$dbo->qn('o.id'),
				$dbo->qn('o.custdata'),
				$dbo->qn('o.days'),
				$dbo->qn('o.status'),
				$dbo->qn('o.checkin'),
				$dbo->qn('o.checkout'),
				$dbo->qn('o.idorderota'),
				$dbo->qn('o.channel'),
				$dbo->qn('c.first_name'),
				$dbo->qn('c.last_name'),
				$dbo->qn('c.pic'),
			])
			->from($dbo->qn('#__vikbooking_orders', 'o'))
			->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'))
			->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'))
			->where($dbo->qn('o.closure') . ' = 0');

		if ($booking_status) {
			// filter by booking status
			if (count($booking_status) === 1) {
				// single booking status
				$q->where($dbo->qn('o.status') . ' = ' . $dbo->q($booking_status[0]));
			} else {
				// multiple booking statuses
				$q->where($dbo->qn('o.status') . ' IN (' . implode(', ', array_map([$dbo, 'q'], $booking_status)) . ')');
			}
		}

		if (!empty($booking_id)) {
			// search by booking ID or OTA booking ID only
			if (preg_match("/^[0-9]+$/", (string) $booking_id)) {
            	// only numbers could be both website and OTA
				$q->andWhere([
					$dbo->qn('o.id') . ' = ' . (int) $booking_id,
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($booking_id),
				], $glue = 'OR');
			} else {
				// alphanumeric IDs can only belong to an OTA reservation
            	$q->where($dbo->qn('o.idorderota') . ' = ' . $dbo->q($booking_id));
			}
		} else {
			// search by different values
			if (stripos($booking_key, 'id:') === 0) {
				// search by ID or OTA ID
				$seek_parts = explode('id:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->andWhere([
					$dbo->qn('o.id') . ' = ' . $dbo->q($seek_value),
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($seek_value),
				], $glue = 'OR');
			} elseif (stripos($booking_key, 'otaid:') === 0) {
				// search by OTA Booking ID
				$seek_parts = explode('otaid:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->where($dbo->qn('o.idorderota') . ' = ' . $dbo->q($seek_value));
			} elseif (stripos($booking_key, 'coupon:') === 0) {
				// search by coupon code
				$seek_parts = explode('coupon:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->where($dbo->qn('o.coupon') . ' LIKE ' . $dbo->q("%{$seek_value}%"));
			} elseif (stripos($booking_key, 'name:') === 0) {
				// search by customer nominative
				$seek_parts = explode('name:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->where('CONCAT_WS(\' \', ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') LIKE ' . $dbo->q("%{$seek_value}%"));
			} elseif (strpos($booking_key, '@') !== false) {
				// search by customer email
				$q->where($dbo->qn('o.custmail') . ' = ' . $dbo->q($booking_key));
			} elseif (strpos($booking_key, '+') === 0) {
				// search by customer phone
				$q->where($dbo->qn('o.phone') . ' = ' . $dbo->q($booking_key));
			} else {
				// seek for various values
				if (preg_match("/^[a-z\s]+$/i", (string) $booking_key)) {
					// when only letters (or spaces) look only for the customer name
					$q->where('CONCAT_WS(\' \', ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') LIKE ' . $dbo->q("%{$booking_key}%"));
				} else {
					// look for both customer name and booking ID
					$q->andWhere([
						'CONCAT_WS(\' \', ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') LIKE ' . $dbo->q("%{$booking_key}%"),
						$dbo->qn('o.id') . ' = ' . $dbo->q($booking_key),
						$dbo->qn('o.idorderota') . ' = ' . $dbo->q($booking_key),
					], $glue = 'OR');
				}
			}
		}

		// order by most recent bookings
		$q->order($dbo->qn('id') . ' DESC');

		$dbo->setQuery($q);

		// set results found
		$response['results'] = $dbo->loadAssocList();

		// default icon for website reservations
		$source_def_icon_cls = VikBookingIcons::i('hotel');

		// map the results with the required properties
		$response['results'] = array_map(function($booking) use ($source_def_icon_cls) {
			// build "text" property
			$text = $booking['id'];
			if (!empty($booking['first_name'])) {
				// use customer nominative when available
				$text = trim($booking['first_name'] . ' ' . $booking['last_name']);
			} elseif (!empty($booking['custdata'])) {
				$text = VikBooking::getFirstCustDataField($booking['custdata']);
			}
			$booking['text'] = $text;

			// build "img" property
			if (!empty($booking['pic'])) {
				// use guest profile picture
				$booking['img'] = strpos($booking['pic'], 'http') === 0 ? $booking['pic'] : VBO_SITE_URI . 'resources/uploads/' . $booking['pic'];
			} elseif (!empty($booking['channel'])) {
				// use channel logo
				$ch_logo_obj = VikBooking::getVcmChannelsLogo($booking['channel'], true);
				$booking['img'] = is_object($ch_logo_obj) ? $ch_logo_obj->getTinyLogoURL() : '';
			}

			if (empty($booking['img'])) {
				// always set an empty string
				$booking['img'] = '';
				// set the default icon class
				$booking['icon_class'] = $source_def_icon_cls;
			}

			// return the mapped booking element
			return $booking;
		}, $response['results']);

		// output the JSON encoded object with results found
		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * AJAX endpoint to dynamically search for customers and build elements. Compatible with select2.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function customer_elements_search()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$search_key = trim($app->input->getString('term', ''));

		$response = [
			'results' => [],
			'pagination' => [
				'more' => false,
			],
		];

		if (empty($search_key)) {
			// output the JSON object with no results
			VBOHttpDocument::getInstance($app)->json($response);
		}

		// start the query
		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikbooking_customers'))
			->where(1);

		if (preg_match('/^[a-z0-9\.\-\_]+\@[a-z0-9\.\-\_]+\.[a-z0-9\.\-\_]+$/i', $search_key)) {
			// full email address detected
			$q->where($dbo->qn('email') . ' = ' . $dbo->q($search_key));
		} else {
			// search by different values
			$seek_clauses = [];

			// search by nominative
			$seek_clauses[] = 'CONCAT_WS(" ", ' . $dbo->qn('first_name') . ', ' . $dbo->qn('last_name') . ') LIKE ' . $dbo->q('%' . $search_key . '%');

			// search by company name
			$seek_clauses[] = $dbo->qn('company') . ' LIKE ' . $dbo->q('%' . $search_key . '%');

			if (preg_match('/^\+?[0-9\s]+$/i', $search_key)) {
				// search by phone number
				$seek_clauses[] = $dbo->qn('phone') . ' = ' . $dbo->q($search_key);
			}

			if (strpos($search_key, '@') !== false) {
				// search by email address
				$seek_clauses[] = $dbo->qn('email') . ' LIKE ' . $dbo->q('%' . $search_key . '%');
			}

			if (preg_match('/[0-9]+/', $search_key)) {
				// search by company VAT number
				$seek_clauses[] = $dbo->qn('vat') . ' = ' . $dbo->q($search_key);

				// search by PIN code
				$seek_clauses[] = $dbo->qn('pin') . ' = ' . $dbo->q($search_key);
			}

			// set multiple search clauses
			$q->andWhere($seek_clauses, 'OR');
		}

		// order by customer nominative
		$q->order($dbo->qn('first_name') . ' ASC');
		$q->order($dbo->qn('last_name') . ' ASC');

		$dbo->setQuery($q);

		// set results found
		$response['results'] = $dbo->loadAssocList();

		// default icon for customers
		$source_def_icon_cls = VikBookingIcons::i('user');

		// map the results with the required properties
		$response['results'] = array_map(function($customer) use ($source_def_icon_cls) {
			// build "text" property
			$customer['text'] = trim($customer['first_name'] . ' ' . $customer['last_name']);

			// build "img" property
			if (!empty($customer['pic'])) {
				// use customer profile picture
				$customer['img'] = strpos($customer['pic'], 'http') === 0 ? $customer['pic'] : VBO_SITE_URI . 'resources/uploads/' . $customer['pic'];
			} elseif (!empty($customer['country']) && is_file(implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'countries', $customer['country'] . '.png']))) {
				// use customer country flag
				$customer['img'] = VBO_ADMIN_URI . 'resources/countries/' . $customer['country'] . '.png';
				$customer['img_title'] = $customer['country'];
			}

			if (empty($customer['img'])) {
				// always set an empty string
				$customer['img'] = '';
				// set the default icon class
				$customer['icon_class'] = $source_def_icon_cls;
			}

			// handle custom fields
			if (!empty($customer['cfields'])) {
				$custom_fields = (array) json_decode($customer['cfields'], true);
				if ($custom_fields) {
					$customer['cfields'] = $custom_fields;
				}
			}
			if (!is_array($customer['cfields']) || !$customer['cfields']) {
				// ensure this is a null value
				$customer['cfields'] = null;
			}

			// return the mapped customer element
			return $customer;
		}, $response['results']);

		// output the JSON encoded object with results found
		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * Regular task to update the status of a cancelled booking to pending (stand-by).
	 * 
	 * @return 	void
	 */
	public function set_to_pending()
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid = $app->input->getInt('bid', 0);

		if (!JSession::checkToken() && !JSession::checkToken('get')) {
			$app->enqueueMessage(JText::translate('JINVALID_TOKEN'), 'error');
			$app->redirect('index.php?option=com_vikbooking&task=editorder&cid[]=' . $bid);
			$app->close();
		}

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . $bid;
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			$app->enqueueMessage('Booking not found', 'error');
			$app->redirect('index.php?option=com_vikbooking&task=orders');
			$app->close();
		}

		$booking = $dbo->loadAssoc();
		if ($booking['status'] != 'cancelled') {
			$app->enqueueMessage('Booking status must be -Cancelled-', 'error');
			$app->redirect('index.php?option=com_vikbooking&task=editorder&cid[]=' . $booking['id']);
			$app->close();
		}

		$q = "UPDATE `#__vikbooking_orders` SET `status`='standby' WHERE `id`=" . $booking['id'];
		$dbo->setQuery($q);
		$dbo->execute();

		$app->enqueueMessage(JText::translate('JLIB_APPLICATION_SAVE_SUCCESS'));
		$app->redirect('index.php?option=com_vikbooking&task=editorder&cid[]=' . $booking['id']);
		$app->close();
	}

	/**
	 * AJAX endpoint to assign a room index to a room booking record.
	 * 
	 * @return 	void
	 */
	public function set_room_booking_subunit()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid = $app->input->getInt('bid', 0);
		$rid = $app->input->getInt('rid', 0);
		$orkey = $app->input->getInt('orkey', 0);
		$rindex = $app->input->getInt('rindex', 0);

		if (empty($bid) || empty($rid)) {
			VBOHttpDocument::getInstance()->close(500, 'Missing request values');
		}

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . $bid;
		$dbo->setQuery($q, 0, 1);
		$booking = $dbo->loadAssoc();
		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, 'Booking not found');
		}

		$booking_rooms = VikBooking::loadOrdersRoomsData($booking['id']);
		if (!$booking_rooms) {
			VBOHttpDocument::getInstance()->close(500, 'No rooms booking found');
		}

		if (!isset($booking_rooms[$orkey]) || $booking_rooms[$orkey]['idroom'] != $rid) {
			VBOHttpDocument::getInstance()->close(500, 'Invalid room booking record');
		}

		// update room record
		$room_record = new stdClass;
		$room_record->id = $booking_rooms[$orkey]['id'];
		$room_record->roomindex = $rindex;

		$dbo->updateObject('#__vikbooking_ordersrooms', $room_record, 'id');

		// build list of affected nights
		$nights_list_ymd   = [];
		$from_checkin_info = getdate($booking['checkin']);
		for ($n = 0; $n < $booking['days']; $n++) {
			// push affected night
			$nights_list_ymd[] = date('Y-m-d', mktime(0, 0, 0, $from_checkin_info['mon'], ($from_checkin_info['mday'] + $n), $from_checkin_info['year']));
		}

		// build return values
		$response = [
			'bid' 	 => $booking['id'],
			'rid' 	 => $booking_rooms[$orkey]['idroom'],
			'rindex' => $rindex,
			'from' 	 => date('Y-m-d', $booking['checkin']),
			'to' 	 => date('Y-m-d', $booking['checkout']),
			'nights' => $nights_list_ymd,
		];

		// output the JSON encoded object
		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * AJAX endpoint to swap one sub-unit index with another for the same room ID and dates.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.2 (J) - 1.6.2 (WP)
	 */
	public function swap_room_subunits()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid_one   = $app->input->getInt('bid_one', 0);
		$bid_two   = $app->input->getInt('bid_two', 0);
		$rid 	   = $app->input->getInt('rid', 0);
		$index_one = $app->input->getInt('index_one', 0);
		$index_two = $app->input->getInt('index_two', 0);
		$checkin   = $app->input->getString('checkin', '');

		if (!$bid_one || !$bid_two || !$rid || !$index_one || !$index_two) {
			VBOHttpDocument::getInstance()->close(500, 'Missing request values');
		}

		// collect the booking information
		$booking_one = VikBooking::getBookingInfoFromID($bid_one);
		$booking_two = VikBooking::getBookingInfoFromID($bid_two);
		if (!$booking_one || !$booking_two) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the involved reservations');
		}

		// get room reservation records
		$rooms_one = VikBooking::loadOrdersRoomsData($bid_one);
		$rooms_two = VikBooking::loadOrdersRoomsData($bid_two);
		if (!$rooms_one || !$rooms_two) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the involved room reservation records');
		}

		// find the record IDs involved and room name
		$update_id_one = null;
		$update_id_two = null;
		$room_name 	   = '';

		foreach ($rooms_one as $room_one) {
			if ($room_one['idroom'] == $rid && $room_one['roomindex'] == $index_one) {
				$update_id_one = $room_one['id'];
				$room_name = $room_one['room_name'];
				break;
			}
		}

		foreach ($rooms_two as $room_two) {
			if ($room_two['idroom'] == $rid && $room_two['roomindex'] == $index_two) {
				$update_id_two = $room_two['id'];
				$room_name = $room_two['room_name'];
				break;
			}
		}

		if (!$update_id_one || !$update_id_two) {
			VBOHttpDocument::getInstance()->close(500, 'Could not find the involved room reservation record IDs');
		}

		// swap first room record
		$q = $dbo->getQuery(true);

		$q->update($dbo->qn('#__vikbooking_ordersrooms'))
			->set($dbo->qn('roomindex') . ' = ' . $index_two)
			->where($dbo->qn('id') . ' = ' . (int)$update_id_one);

		$dbo->setQuery($q);
		$dbo->execute();

		$result = (bool)$dbo->getAffectedRows();

		// swap second room record
		$q = $dbo->getQuery(true);

		$q->update($dbo->qn('#__vikbooking_ordersrooms'))
			->set($dbo->qn('roomindex') . ' = ' . $index_one)
			->where($dbo->qn('id') . ' = ' . (int)$update_id_two);

		$dbo->setQuery($q);
		$dbo->execute();

		$result = $result || (bool)$dbo->getAffectedRows();

		if (!$result) {
			VBOHttpDocument::getInstance()->close(500, 'No records were updated for the involved room reservation IDs');
		}

		// update history records
		$user = JFactory::getUser();
		VikBooking::getBookingHistoryInstance()->setBid($booking_one['id'])->store('MB', JText::sprintf('VBO_SWAP_ROOMS_LOG', $room_name, $index_one, $index_two) . " ({$user->name})");
		if ($booking_one['id'] != $booking_two['id']) {
			VikBooking::getBookingHistoryInstance()->setBid($booking_two['id'])->store('MB', JText::sprintf('VBO_SWAP_ROOMS_LOG', $room_name, $index_two, $index_one) . " ({$user->name})");
		}

		// output the JSON encoded response object
		VBOHttpDocument::getInstance()->json([
			'swap_from' => $index_one,
			'swap_to' => $index_two,
		]);
	}

	/**
	 * AJAX endpoint to remove the type flag (i.e. "overbooking") from a booking ID.
	 * 
	 * @return 	void
	 */
	public function delete_type_flag()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid  = $app->input->getUInt('bid', 0);
		$flag = $app->input->getString('flag', '');

		if (!$bid) {
			VBOHttpDocument::getInstance()->close(404, JText::translate('VBPEDITBUSYONE'));
		}

		$q = $dbo->getQuery(true);

		$q->update($dbo->qn('#__vikbooking_orders'))
			->set($dbo->qn('type') . ' = ' . $dbo->q(''))
			->where($dbo->qn('id') . ' = ' . $bid);

		$dbo->setQuery($q);
		$dbo->execute();

		if (!(bool)$dbo->getAffectedRows()) {
			VBOHttpDocument::getInstance()->close(500, 'Could not update the booking record');
		}

		if (!strcasecmp($flag, 'overbooking')) {
			// update history records
			$user = JFactory::getUser();
			VikBooking::getBookingHistoryInstance($bid)->store('OB', JText::translate('VBO_OVERBOOKING_FLAG_REMOVED') . " ({$user->name})");
		}

		VBOHttpDocument::getInstance()->json([$bid => 'ok']);
	}

	/**
	 * AJAX endpoint to set the AI options related to automatic guest review for a booking.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function set_ai_auto_guest_review_opt()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid = $app->input->getInt('bid', 0);
		$opt = $app->input->get('opt', [], 'array');

		if (!$bid || !$opt) {
			VBOHttpDocument::getInstance()->close(500, 'Missing request values');
		}

		// ensure the booking exists
		$booking = VikBooking::getBookingInfoFromID($bid);
		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the involved reservation');
		}

		// get AI options for this booking
		$booking_ai_opts = (array) VBOFactory::getConfig()->getArray('ai_auto_guest_review_opt_' . $booking['id'], []);

		// update the requested options
		foreach ($opt as $param => $val) {
			if (is_bool($val) || is_numeric($val)) {
				$val = (int) $val;
			}
			// set new option value
			$booking_ai_opts[$param] = $val;
		}

		// update AI options for this booking
		VBOFactory::getConfig()->set('ai_auto_guest_review_opt_' . $booking['id'], $booking_ai_opts);

		// return the new preferences
		VBOHttpDocument::getInstance()->json($booking_ai_opts);
	}

	/**
	 * AJAX endpoint to register a new taking (payment).
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function add_taking()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid    = $app->input->getInt('bid', 0);
		$amount = $app->input->getFloat('amount', 0);
		$payid  = $app->input->getInt('payid', 0);
		$descr  = $app->input->getString('descr', '');

		if (!$bid || !$amount || $amount < 0) {
			VBOHttpDocument::getInstance()->close(500, 'Missing or invalid request values.');
		}

		// ensure the booking exists
		$booking = VikBooking::getBookingInfoFromID($bid);
		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the involved reservation.');
		}

		$new_tot_paid = $booking['totpaid'] + $amount;

		// update booking record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_orders'))
				->set($dbo->qn('totpaid') . ' = ' . $dbo->q($new_tot_paid))
				->where($dbo->qn('id') . ' = ' . (int) $booking['id'])
		);
		$dbo->execute();

		// update booking history
		$extra_data = new stdClass;
		$extra_data->register_new   = 1;
		$extra_data->amount_paid    = $amount;
		$extra_data->payment_method = $descr;
		if (!empty($payid)) {
			$pay_info = VikBooking::getPayment($payid);
			if ($pay_info) {
				$extra_data->payment_method = $pay_info['name'];
			}
		}
		VikBooking::getBookingHistoryInstance($booking['id'])
			->setExtraData($extra_data)
			->store(
				'PU',
				JText::sprintf('VBOPREVAMOUNTPAID', VikBooking::numberFormat($booking['totpaid']) . (!empty($extra_data->payment_method) ? ' (' . $extra_data->payment_method . ')' : ''))
			);

		// process completed
		VBOHttpDocument::getInstance()->json([
			'url'  => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=' . $booking['id'], false),
		]);
	}

	/**
	 * AJAX endpoint to update a taking (payment) and related history record.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function update_taking()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid    = $app->input->getInt('bid', 0);
		$hid    = $app->input->getInt('hid', 0);
		$amount = $app->input->getFloat('amount', 0);
		$descr  = $app->input->getString('descr', '');
		$htype  = $app->input->getString('htype', 'PU');

		if (!$bid || !$hid || !$amount || $amount < 0) {
			VBOHttpDocument::getInstance()->close(500, 'Missing or invalid request values.');
		}

		// ensure the booking exists
		$booking = VikBooking::getBookingInfoFromID($bid);
		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the involved reservation.');
		}

		// access the current history record
		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikbooking_orderhistory'))
			->where($dbo->qn('id') . ' = ' . (int) $hid)
			->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
			->where($dbo->qn('type') . ' = ' . $dbo->q($htype));
		$dbo->setQuery($q, 0, 1);
		$history = $dbo->loadAssoc();

		if (!$history) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the history record to update.');
		}

		// get previous amount paid
		$history_data = (object) json_decode(($history['data'] ?: '{}'));
		$prev_amount_paid = $history_data->amount_paid ?? 0;

		// calculate new amount paid
		if ($prev_amount_paid > $amount) {
			$new_tot_paid = $booking['totpaid'] - ($prev_amount_paid - $amount);
		} else {
			$new_tot_paid = $booking['totpaid'] + ($amount - $prev_amount_paid);
		}

		// update booking record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_orders'))
				->set($dbo->qn('totpaid') . ' = ' . $dbo->q($new_tot_paid))
				->where($dbo->qn('id') . ' = ' . (int) $booking['id'])
		);
		$dbo->execute();

		// get currently logged user
		$user  = JFactory::getUser();
		$uname = $user->name;

		// update history extra data
		$history_data->register_new   = 1;
		$history_data->updated        = JFactory::getDate()->toSql();
		$history_data->updated_by     = $uname;
		$history_data->amount_paid    = $amount;
		$history_data->payment_method = $descr;

		// set new record description
		$new_descr = trim($history['descr'] . "\n* " . JText::sprintf('VBO_MODIFIED_ON_SMT', JFactory::getDate()->toSql(true) . ' (' . $uname . ')'));

		// update history record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_orderhistory'))
				->set($dbo->qn('descr') . ' = ' . $dbo->q($new_descr))
				->set($dbo->qn('totpaid') . ' = ' . $dbo->q($new_tot_paid))
				->set($dbo->qn('data') . ' = ' . $dbo->q(json_encode($history_data)))
				->where($dbo->qn('id') . ' = ' . (int) $history['id'])
		);
		$dbo->execute();

		// process completed
		VBOHttpDocument::getInstance()->json([
			'url'  => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=' . $booking['id'], false),
		]);
	}

	/**
	 * AJAX endpoint to delete a booking history event.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function delete_history_record()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$bid   = $app->input->getInt('bid', 0);
		$hid   = $app->input->getInt('hid', 0);
		$htype = $app->input->getString('htype', 'PU');

		if (!$bid || !$hid) {
			VBOHttpDocument::getInstance()->close(500, 'Missing or invalid request values.');
		}

		// ensure the booking exists
		$booking = VikBooking::getBookingInfoFromID($bid);
		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the involved reservation.');
		}

		// access the current history record
		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikbooking_orderhistory'))
			->where($dbo->qn('id') . ' = ' . (int) $hid)
			->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
			->where($dbo->qn('type') . ' = ' . $dbo->q($htype));
		$dbo->setQuery($q, 0, 1);
		$history = $dbo->loadAssoc();

		if (!$history) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find the history record to update.');
		}

		// get previous amount paid
		$history_data = (object) json_decode(($history['data'] ?: '{}'));
		$prev_amount_paid = $history_data->amount_paid ?? 0;

		// calculate new amount paid
		$new_tot_paid = $booking['totpaid'];
		if ($prev_amount_paid) {
			$new_tot_paid = $booking['totpaid'] - $prev_amount_paid;
		}

		// update booking record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_orders'))
				->set($dbo->qn('totpaid') . ' = ' . $dbo->q($new_tot_paid))
				->where($dbo->qn('id') . ' = ' . (int) $booking['id'])
		);
		$dbo->execute();

		// delete history record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->delete($dbo->qn('#__vikbooking_orderhistory'))
				->where($dbo->qn('id') . ' = ' . (int) $hid)
				->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
				->where($dbo->qn('type') . ' = ' . $dbo->q($htype))
		);

		$dbo->execute();
		$aff_rows = $dbo->getAffectedRows();

		// process completed
		VBOHttpDocument::getInstance()->json([
			'rows' => $aff_rows,
			'url'  => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=' . $booking['id'], false),
		]);
	}
}
