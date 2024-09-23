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
 * Browser notification displayer handler for a booking history event.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationDisplayHistory extends JObject implements VBONotificationDisplayer
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
	 * the notification in the browser.
	 * 
	 * @return 	null|object 	the notification display data payload.
	 * 
	 * @throws 	Exception
	 */
	public function getData()
	{
		$booking_id = (int)$this->get('idorder', 0);
		if (empty($booking_id)) {
			return null;
		}

		// history event type
		$ev_type = $this->get('type', '');

		// booking source (channel)
		$channel_source = $this->get('channel', '');

		// customer picture
		$customer_pic = $this->get('pic', '');

		// the notification icon
		$notif_icon = '';
		if (!empty($channel_source)) {
			$ch_logo_obj = VikBooking::getVcmChannelsLogo($channel_source, true);
			$notif_icon  = is_object($ch_logo_obj) ? $ch_logo_obj->getSmallLogoURL() : '';
		} elseif (!empty($customer_pic)) {
			$notif_icon = strpos($customer_pic, 'http') === 0 ? $customer_pic : VBO_SITE_URI . 'resources/uploads/' . $customer_pic;
		}

		if (empty($notif_icon)) {
			$notif_icon = $this->getIconUrl();
		}

		// get history helper
		$history_obj = VikBooking::getBookingHistoryInstance();

		// compose notification title
		$notif_title = $history_obj->validType($ev_type, true);

		// compose the notification data to display
		$notif_data = new stdClass;
		$notif_data->title 	 = $notif_title;
		$notif_data->message = $this->get('descr', '');
		$notif_data->icon 	 = $notif_icon;
		$notif_data->onclick = 'VBOCore.handleDisplayWidgetNotification';
		$notif_data->gotourl = VBOFactory::getPlatform()->getUri()->admin("index.php?option=com_vikbooking&task=editorder&cid[]={$booking_id}", false);

		// set additional properties to the (Web, not Push) notification payload
		$notif_data->widget_id = 'booking_details';
		// data options for a Web notification should NOT set a "type" or this will be overridden
		$notif_data->_options  = [
			'_web' 	  => 1,
			'title'   => $notif_data->title,
			'message' => $notif_data->message,
			'bid' 	  => $booking_id,
		];

		return $notif_data;
	}

	/**
	 * Returns the URL to the default icon for the history
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
