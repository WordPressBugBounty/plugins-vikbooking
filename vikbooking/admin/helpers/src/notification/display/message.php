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
 * Browser notification displayer handler for a new guest message.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationDisplayMessage extends JObject implements VBONotificationDisplayer
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

		// customer picture
		$customer_pic = $this->get('pic', '');

		// channel logo
		$channel_logo = $this->get('channel_logo', '');

		// the notification icon
		$notif_icon = '';
		if (!empty($customer_pic)) {
			$notif_icon = strpos($customer_pic, 'http') === 0 ? $customer_pic : VBO_SITE_URI . 'resources/uploads/' . $customer_pic;
		} elseif (!empty($channel_logo)) {
			$notif_icon = $channel_logo;
		} else {
			$notif_icon = $this->getIconUrl();
		}

		// compose notification title
		$conv_subject = $this->get('subject', '');
		$guest_name = $this->get('first_name', '') . ' ' . $this->get('last_name', '');
		$notif_title = $guest_name . (!empty($conv_subject) ? (' - ' . $conv_subject) : '');

		// compose the notification data to display
		$notif_data = new stdClass;
		$notif_data->title 	 = $notif_title;
		$notif_data->message = $this->get('content', '');
		$notif_data->icon 	 = $notif_icon;
		$notif_data->onclick = 'VBOCore.handleDisplayWidgetNotification';
		$notif_data->gotourl = VBOFactory::getPlatform()->getUri()->admin("index.php?option=com_vikbooking&task=editorder&cid[]={$booking_id}", false);

		// set additional properties to the notification payload related to the guest message
		$notif_data->widget_id 	 = 'guest_messages';
		$notif_data->channel 	 = $this->get('channel', null);
		$notif_data->id_order 	 = $booking_id;
		$notif_data->id_customer = $this->get('id_customer', null);
		$notif_data->id_message  = $this->get('id_message', null);
		$notif_data->id_thread 	 = $this->get('id_thread', null);
		// set additional data options to ensure the conversation will be found
		$notif_data->_options  = [
			'_web' => 1,
			'bid'  => $booking_id,
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
