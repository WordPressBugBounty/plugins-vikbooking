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
 * Declares the method(s) that a browser notification displayer-data-object should provide.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
interface VBONotificationDisplayer
{
	/**
	 * Composes an object with the necessary properties to display
	 * the notification in the browser.
	 * 
	 * @return 	null|object 	the notification display data payload.
	 * 
	 * @throws 	Exception
	 */
	public function getData();
}
