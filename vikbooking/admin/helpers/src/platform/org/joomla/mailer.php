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
 * Implements the mailer interface for the Joomla platform.
 * 
 * @since 1.15
 */
class VBOPlatformOrgJoomlaMailer implements VBOPlatformMailerInterface
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
		// init table content
		$content = JTable::getInstance('content');
		$content->text = VBOMailParser::checkWrapperSymbols($mail->getContent());

		// parse conditional text rules (properties should be set by who calls this method)
		$mail_content = $content->text;
		VikBooking::getConditionalRulesInstance()->parseTokens($mail_content);
		$content->text = $mail_content;

		// init params array
		$params = [];

		// import content plugins
		JPluginHelper::importPlugin('content');
		
		$app = JFactory::getApplication();

		/**
		 * This is the first stage in preparing content for output and is the
		 * most common point for content orientated plugins to do their work.
		 * Since the article and related parameters are passed by reference,
		 * event handlers can modify them prior to display.
		 *
		 * @param 	string   $context   The context of the content being
		 * 								passed to the plugin.
		 * @param 	JTable   &$article  A reference to the article that is
		 * 								being rendered by the view.
		 * @param 	mixed    &$params   A reference to an associative array 
		 * 								of relevant parameters.
		 * @param 	integer  $page      An integer that determines the "page"
		 * 								of the content that is to be generated.
		 *
		 * @return 	void
		 *
		 * @since 	1.5
		 */
		$app->triggerEvent('onContentPrepare', array('com_vikbooking', &$content, &$params, 0));

		// update e-mail contents
		$mail->setContent($content->text);

		// return the prepared email content
		return $content->text;
	}
}
