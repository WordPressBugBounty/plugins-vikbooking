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
 * Browser notification displayer handler for reminders.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationDisplayReminder extends JObject implements VBONotificationDisplayer
{
	/**
	 * Proxy for immediately getting the object and bind data.
	 * 
	 * @param 	array|object 	$data 	the notification payload data to bind.
	 */
	public static function getInstance($data = null)
	{
		return new static($data);
	}

	/**
	 * Composes an object with the necessary properties to display
	 * the notification in the browser. In case of errors while
	 * retrieving the reminder, an Exception will be thrown.
	 * 
	 * @return 	null|object 	the notification display data payload.
	 * 
	 * @throws 	Exception
	 */
	public function getData()
	{
		$reminder_id = $this->get('id');
		if (empty($reminder_id)) {
			throw new Exception('Empty notification reminder id to display', 500);
		}

		$helper = VBORemindersHelper::getInstance();

		$reminder = $helper->getReminder($reminder_id);
		if (!$reminder) {
			throw new Exception('Notification reminder id not found', 404);
		}

		/**
		 * Flag the reminder as displayed.
		 * 
		 * @since 	1.16.5 (J) - 1.6.5 (WP)
		 */
		$helper->setDisplayed($reminder->id);

		// compose the notification data to display
		$notif_data = new stdClass;
		$notif_data->title 	 = $reminder->title;
		$notif_data->message = empty($reminder->descr) ? $reminder->title : $reminder->descr;
		$notif_data->icon 	 = $this->getIconUrl();

		// check if click handler should be attached
		if (!empty($reminder->idorder)) {
			// register click event callback data
			$notif_data->onclick = 'VBOCore.handleGoto';
			$notif_data->gotourl = VBOFactory::getPlatform()->getUri()->admin("index.php?option=com_vikbooking&task=editorder&cid[]={$reminder->idorder}", false);
			if (is_object($reminder->payload) && !empty($reminder->payload->airbnb_host_guest_review)) {
				// append callback to trigger the host-to-guest review
				$notif_data->gotourl .= '&notif_action=airbnb_host_guest_review';
				// attempt to get the proper channel logo
				$vcm_logos = VikBooking::getVcmChannelsLogo('airbnb', $get_istance = true);
				if ($vcm_logos) {
					$channel_logo = $vcm_logos->getSmallLogoURL();
					if (!empty($channel_logo)) {
						$notif_data->icon = $channel_logo;
					}
				}
			} else {
				// if no actions, overwrite click handler to display the notification within the booking details modal widget
				$notif_data->onclick = 'VBOCore.handleDisplayWidgetNotification';
				// set additional properties to the (Web, not Push) notification payload
				$notif_data->widget_id = 'booking_details';
				// data options for a Web notification should NOT set a "type" or this will be overridden
				$notif_data->_options  = [
					'_web' 	  => 1,
					'title'   => $notif_data->title,
					'message' => $notif_data->message,
					'bid' 	  => $reminder->idorder,
				];
			}
		} else {
			$notif_data->onclick = null;
		}

		return $notif_data;
	}

	/**
	 * Returns the URL to the default icon for the reminders
	 * browser notifications. Custom logos are preferred.
	 * 
	 * @return 	string|null
	 */
	private function getIconUrl()
	{
		$config = VBOFactory::getConfig();

		// back-end custom logo
		$use_logo = $config->get('backlogo');
		if (empty($use_logo) || !strcasecmp($use_logo, 'vikbooking.png')) {
			// fallback to company (site) logo
			$use_logo = $config->get('sitelogo');
		}

		if (!empty($use_logo) && strcasecmp($use_logo, 'vikbooking.png')) {
			// uploaded logo found
			$use_logo = VBO_ADMIN_URI . 'resources/' . $use_logo;
		} else {
			$use_logo = null;
		}

		return $use_logo;
	}
}
