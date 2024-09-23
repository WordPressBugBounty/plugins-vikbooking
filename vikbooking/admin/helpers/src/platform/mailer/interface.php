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
 * Declares all the mailer helper methods that may differ between every supported platform.
 * 
 * @since 1.5
 */
interface VBOPlatformMailerInterface
{
	/**
	 * Sends an e-mail through the pre-installed mailing system.
	 * 
	 * @param 	VBOMailWrapper  $mail  The e-mail encapsulation.
	 * 
	 * @return 	boolean         True on success, false otherwise.
	 */
	public function send(VBOMailWrapper $mail);

	/**
	 * Prepares the email content for the current platform.
	 * 
	 * @param 	VBOMailWrapper  $mail  The e-mail encapsulation.
	 * 
	 * @return 	string          Alters the mail wrapper object and returns the content.
	 */
	public function prepare(VBOMailWrapper $mail);
}
