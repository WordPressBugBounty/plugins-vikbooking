<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Builds a browser notification data object for a new operator message.
 * 
 * @since 	1.18.0 (J) - 1.8.0 (WP)
 */
final class VBONotificationDataOperatormessage extends VBONotificationAdapter
{
	/**
	 * The type of the scheduled notification.
	 * 
	 * @var 	string
	 */
	protected $_notification_type = 'operatormessage';

	/**
	 * Public method to be called after setting the operator message
	 * record data properties as "reserved" (starting with "_").
	 * Needed to set "public" properties for the notification
	 * data object to serve as display data for instant dispatch.
	 * 
	 * @return 	bool 	True if display data were built.
	 */
	public function buildDisplayData()
	{
		// make sure the sender id is set
		$id_sender = $this->get('_id_sender');
		if (empty($id_sender)) {
			$id_sender = $this->get('id_sender');
		}
		if (empty($id_sender)) {
			// useless to proceed if no sender ID set
			return false;
		}

		// access any kind of object-property set
		$props = $this->getProperties($public = false);

		// convert "reserved" keys into "public" ones
		foreach ($props as $prop => $val) {
			if ('_' == substr($prop, 0, 1)) {
				$props[substr($prop, 1)] = $val;
				unset($props[$prop]);
			}
		}

		// get the notification displayer for "operatormessage"
		$displayer = VBONotificationBuilder::getInstance($props)->getDisplayer($this->_notification_type);
		if (!$displayer) {
			return false;
		}

		// build notification display data
		try {
			$display_data = $displayer->getData();
			if (!$display_data) {
				throw new Exception('Error building the notification display data', 500);
			}
		} catch (Exception $e) {
			return false;
		}

		// set display data "public" properties
		foreach ($display_data as $prop_name => $prop_val) {
			$this->set($prop_name, $prop_val);
		}

		return true;
	}

	/**
	 * This kind of notification will be returned inclusive of 
	 * display data for dispatching. It won't need to be built.
	 * 
	 * @return 	null 	Always null for never building this notification data.
	 */
	protected function generateBuildUrl()
	{
		return null;
	}
}
