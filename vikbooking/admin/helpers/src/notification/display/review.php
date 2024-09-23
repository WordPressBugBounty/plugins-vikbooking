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
 * Browser notification displayer handler for a booking review left by a guest.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationDisplayReview extends JObject implements VBONotificationDisplayer
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
		$review_id 	= (int)$this->get('id_review', 0);
		if (empty($booking_id) || empty($review_id)) {
			return null;
		}

		// guest avatar and channel logo
		$guest_avatar = $this->get('guest_avatar', '');
		$channel_logo = $this->get('channel_logo', '');

		// the notification icon
		$notif_icon = '';
		if (!empty($guest_avatar)) {
			$notif_icon = $guest_avatar;
		} elseif (!empty($channel_logo)) {
			$notif_icon = $channel_logo;
		} else {
			$notif_icon = $this->getIconUrl();
		}

		// compose notification title
		$guest_name = $this->get('customer_name', '');
		if (empty($guest_name)) {
			$guest_name = $this->get('first_name', '') . ' ' . $this->get('last_name', '');
		}
		$notif_title = $guest_name . ' - ' . JText::translate('VBOSEEGUESTREVIEW');

		// compose the notification data to display
		$notif_data = new stdClass;
		$notif_data->title 	 = $notif_title;
		$notif_data->message = $this->get('score', '') . ' ' . $this->get('content', '');
		$notif_data->icon 	 = $notif_icon;
		$notif_data->onclick = 'VBOCore.handleGoto';
		$notif_data->gotourl = VBOFactory::getPlatform()->getUri()->admin("index.php?option=com_vikbooking&task=editorder&cid[]={$booking_id}", false);

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
