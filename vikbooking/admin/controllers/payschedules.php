<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking payment schedules controller.
 *
 * @since 	1.16.10 (J) - 1.6.10 (WP)
 */
class VikBookingControllerPayschedules extends JControllerAdmin
{
	/**
	 * AJAX endpoint to save a new payment schedule.
	 * 
	 * @return 	void
	 */
	public function save()
	{
		$app = JFactory::getApplication();

		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$bid    = $app->input->getInt('bid');
		$amount = $app->input->getFloat('amount');
		$dt     = $app->input->getString('dt');
		$time   = $app->input->getString('time', '00:00');

		if (!$bid || !$amount || !$dt || !$time) {
			VBOHttpDocument::getInstance()->close(400, JText::translate('VBO_PLEASE_FILL_FIELDS'));
		}

		$booking = VikBooking::getBookingInfoFromID($bid);
		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, JText::translate('VBO_NO_RECORDS_FOUND'));
		}

		// current date object
		$now_dt = JFactory::getdate('now');

		// access the time with hours and minutes
		$time_parts = explode(':', $time);
		$time_hours = (int) $time_parts[0];
		$time_minutes = (int) ($time_parts[1] ?? 0);

		// build date timestamp and string
		$dt_ts = VikBooking::getDateTimestamp($dt, $time_hours, $time_minutes, 0);
		$dt_military = date('Y-m-d H:i:s', $dt_ts);
		$for_dt_obj  = JFactory::getDate($dt_military);

		if ($now_dt > $for_dt_obj) {
			// payment date cannot be in the past
			VBOHttpDocument::getInstance()->close(400, 'Payment collection date and time must be in the future (current date and time is ' . $now_dt->format('Y-m-d H:i:s') . ')');
		}

		$user = JFactory::getUser();
		$created_by = $user->name;

		// prepare record to be saved
		$pay_schedule = new stdClass;
		$pay_schedule->idorder    = $booking['id'];
		$pay_schedule->fordt      = $for_dt_obj->toSql();
		$pay_schedule->amount     = $amount;
		$pay_schedule->status     = 0;
		$pay_schedule->created_on = $now_dt->toSql();
		$pay_schedule->created_by = $created_by;

		// access the model
		$model = VBOModelPayschedules::getInstance();

		if (!$model->save($pay_schedule)) {
			VBOHttpDocument::getInstance()->close(500, 'Could not store the payment schedule record');
		}

		// reload all active schedules for this booking
		$active_payschedules = $model->getItems([
			'idorder' => [
				'value' => $booking['id'],
			],
		]);

		// output the JSON encoded list of active payment schedules for this booking
		VBOHttpDocument::getInstance()->json($active_payschedules);
	}

	/**
	 * AJAX endpoint to delete an existing payment schedule.
	 * 
	 * @return 	void
	 */
	public function delete()
	{
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();

		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$bid    = $app->input->getInt('bid');
		$schedule_id = $app->input->getInt('schedule_id');

		if (!$bid || !$schedule_id) {
			VBOHttpDocument::getInstance()->close(400, 'Missing data to delete the record');
		}

		// delete the requested record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->delete($dbo->qn('#__vikbooking_payschedules'))
				->where($dbo->qn('id') . ' = ' . $schedule_id)
				->where($dbo->qn('idorder') . ' = ' . $bid)
		);

		$dbo->execute();

		// access the model
		$model = VBOModelPayschedules::getInstance();

		// reload all schedules for this booking
		$active_payschedules = $model->getItems([
			'idorder' => [
				'value' => $bid,
			],
		]);

		// output the JSON encoded list of active payment schedules for this booking
		VBOHttpDocument::getInstance()->json($active_payschedules);
	}
}
