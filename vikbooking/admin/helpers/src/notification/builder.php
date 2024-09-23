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
 * Browser notification display-data-builder handler.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBONotificationBuilder extends JObject
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
	 * Returns an instance of the appropriate notification displayer object.
	 * 
	 * @param 	string 		$type 	optional displayer type to force.
	 * 
	 * @return 	null|object
	 */
	public function getDisplayer($type = null)
	{
		// get the type of notification
		$notif_type = !empty($type) ? $type : (string)$this->get('type');
		if (empty($notif_type)) {
			return null;
		}

		// compose the notification type class
		$base_notif_class = 'VBONotificationDisplay';
		$type_class = ucwords(str_replace(array('-', '_'), ' ', $notif_type));
		$type_class = preg_replace("/[^a-zA-Z0-9]/", '', $type_class);

		$notif_class_name = $base_notif_class . $type_class;

		if (!class_exists($notif_class_name)) {
			return null;
		}

		// return an instance of the apposite displayer by re-binding the payload
		return $notif_class_name::getInstance($this->getProperties());
	}
}
