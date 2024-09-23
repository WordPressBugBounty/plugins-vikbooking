<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Declares the methods that a browser notification builder-data-object should provide.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
interface VBONotificationData
{
	/**
	 * Returns the type of notification.
	 * 
	 * @return 	string    the notification type.
	 */
	public function getType();

	/**
	 * Returns the scheduling date time string.
	 * 
	 * @return 	string 	the notification scheduling time.
	 */
	public function getDateTime();

	/**
	 * Tells whether the scheduling time should be ignored.
	 * 
	 * @return 	bool 	true for ignoring the time.
	 */
	public function getNoTime();

	/**
	 * Gathers the options for dispatching the notification.
	 * 
	 * @return 	array 	associative list of notification options.
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function getOptions();

	/**
	 * Returns the list of notification object properties.
	 * 
	 * @return 	object    the notification data object.
	 */
	public function toDataObject();
}
