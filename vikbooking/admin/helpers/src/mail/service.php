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
 * Interface used to declare the common actions of a mailer.
 * 
 * @since 1.5
 */
interface VBOMailService
{
	/**
	 * Sends an e-mail through a specific mailing service.
	 * 
	 * @param 	VBOMailWrapper  $mail  The e-mail encapsulation.
	 * 
	 * @return 	boolean         True on success, false otherwise.
	 */
	public function send(VBOMailWrapper $mail);
}