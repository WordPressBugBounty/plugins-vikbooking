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
 * Implements the mailer interface for the Wordpress platform.
 * 
 * @since 1.5
 */
class VBOPlatformOrgWordpressMailer implements VBOPlatformMailerInterface
{
	/**
	 * Sends an e-mail through the pre-installed mailing system.
	 * 
	 * @param 	VBOMailWrapper  $mail  The e-mail encapsulation.
	 * 
	 * @return 	boolean         True on success, false otherwise.
	 */
	public function send(VBOMailWrapper $mail)
	{
		// sends through PHP mailer
		$service = new VBOMailServicePhpmailer();

		// prepare email content
		$this->prepare($mail);

		// send the e-mail
		return $service->send($mail);
	}

	/**
	 * Prepares the email content for the current platform.
	 * 
	 * @since 	1.15.2 (J) - 1.5.5 (WP)
	 */
	public function prepare(VBOMailWrapper $mail)
	{
		// get mail full content and replace wrapper symbols
		$mail_content = VBOMailParser::checkWrapperSymbols($mail->getContent());

		// parse conditional text rules (properties should be set by who calls this method)
		VikBooking::getConditionalRulesInstance()->parseTokens($mail_content);

		// interpretes shortcodes contained within the full text
		$mail_content = do_shortcode($mail_content);

		// set manipulated content
		$mail->setContent($mail_content);

		// return the prepared email content
		return $mail_content;
	}
}
