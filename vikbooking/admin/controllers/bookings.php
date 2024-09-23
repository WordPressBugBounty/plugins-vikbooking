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
		$dbo->execute();

		if ($dbo->getNumRows()) {
			$customers = $dbo->loadAssocList();
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
			VBOHttpDocument::getInstance()->close(500, 'Could not find the involved reservations');
		}

		// get room reservation records
		$rooms_one = VikBooking::loadOrdersRoomsData($bid_one);
		$rooms_two = VikBooking::loadOrdersRoomsData($bid_two);
		if (!$rooms_one || !$rooms_two) {
			VBOHttpDocument::getInstance()->close(500, 'Could not find the involved room reservation records');
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
}
