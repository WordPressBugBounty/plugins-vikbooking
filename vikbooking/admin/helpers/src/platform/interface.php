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
 * Declares all the helper methods that may differ between every supported platform.
 * 
 * @since 1.5
 */
interface VBOPlatformInterface
{
	/**
	 * Returns the URI helper instance.
	 *
	 * @return 	VBOPlatformUriInterface
	 */
	public function getUri();

	/**
	 * Returns the mail sender instance.
	 * 
	 * @return  VBOPlatformMailerInterface
	 */
	public function getMailer();

	/**
	 * Returns the event dispatcher instance.
	 * 
	 * @return  VBOPlatformDispatcherInterface
	 * 
	 * @since   1.5.10
	 */
	public function getDispatcher();
}
