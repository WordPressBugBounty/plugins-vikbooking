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
 * Declares all the event dispatcher methods that may differ between every supported platform.
 * 
 * @since 1.5.10
 */
interface VBOPlatformDispatcherInterface
{
	/**
	 * Triggers the specified event by passing the given argument.
	 * No return value is expected here.
	 * 
	 * @param   string  $event  The event to trigger.
	 * @param   array   $args   The event arguments.
	 * 
	 * @return  void
	 */
	public function trigger($event, array $args = []);

	/**
	 * Triggers the specified event by passing the given argument.
	 * At least a return value is expected here.
	 * 
	 * @param   string  $event  The event to trigger.
	 * @param   array   $args   The event arguments.
	 * 
	 * @return  array   A list of returned values.
	 */
	public function filter($event, array $args = []);

	/**
	 * Attaches a new listerner to the specified event. Whenever the event
	 * is fired, the attached handler will be invoked.
	 * 
	 * @param   string    $event    The event to observe.
	 * @param   callable  $handler  The callback to invoke.
	 * 
	 * @return  void
	 * 
	 * @since   1.18.15 (J) - 1.8.15 (WP)
	 */
	public function observe(string $event, $handler);
}
