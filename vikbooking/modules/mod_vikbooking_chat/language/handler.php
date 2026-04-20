<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_chat
 * @author      E4J s.r.l
 * @copyright   Copyright (C) 2026 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking Chat widget languages.
 *
 * @since 	1.0
 */
class Mod_VikBooking_ChatLanguageHandler implements JLanguageHandler
{
	/**
	 * Checks if exists a translation for the given string.
	 *
	 * @param 	string 	$string  The string to translate.
	 *
	 * @return 	string 	The translated string, otherwise null.
	 */
	public function translate($string)
	{
		$result = null;

		/**
		 * Translations go here.
		 * @tip Use 'TRANSLATORS:' comment to attach a description of the language.
		 */

		switch ($string)
		{
			/**
			 * Name, Description and Parameters
			 */

			case 'MOD_VIKBOOKING_CHAT':
				$result = __('VikBooking Chat', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_DESC':
				$result = __('Allows users to start or resume a chat session with an AI agent or administrators.', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_TITLE_FIELD_LABEL':
				$result = __('Heading Title', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_GREETINGS_TITLE_FIELD_LABEL':
				$result = __('Greetings Title', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_GREETINGS_SUBTITLE_FIELD_LABEL':
				$result = __('Greetings Subtitle', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_FAQ_SHORT_FIELD_LABEL':
				$result = __('Question Summary', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_FAQ_SHORT_FIELD_DESC':
				$result = __('A short summary of the frequent question.', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_FAQ_LONG_FIELD_LABEL':
				$result = __('Question Text', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_FAQ_LONG_FIELD_DESC':
				$result = __('The actual question that will be submitted.', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_BACKGROUND_FIELD_LABEL':
				$result = __('Background Color', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_COLOR_FIELD_LABEL':
				$result = __('Text Color', 'vikbooking');
				break;

			case 'COM_MENUS_APPEARANCE_FIELDSET_LABEL':
				$result = __('Appearance', 'vikbooking');
				break;

			case 'COM_MENUS_FAQ_FIELDSET_LABEL':
				$result = __('Frequent Questions', 'vikbooking');
				break;

			/**
			 * Site
			 */

			case 'MOD_VIKBOOKING_CHAT_TITLE':
				$result = __('Chat with us', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_GREETINGS_TITLE':
				$result = __('Hello 👋', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_GREETINGS_SUBTITLE':
				$result = __('How can I help you today?', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_SESSION_INTRO':
				$result = __('Before we begin, tell us a little about yourself.', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_SESSION_NAME_LABEL':
				$result = __('Name', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_SESSION_NAME_DESC':
				$result = __('Tell us how you prefer to be addressed.', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_SESSION_MAIL_LABEL':
				$result = __('Email', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_SESSION_MAIL_DESC':
				$result = __('The email address is only used to notify messages that are sent after you leave the page or close the browser. You are not required to provide an email address.', 'vikbooking');
				break;

			case 'MOD_VIKBOOKING_CHAT_SESSION_START_BTN':
				$result = __('Start Session', 'vikbooking');
				break;
		}

		return $result;
	}
}
