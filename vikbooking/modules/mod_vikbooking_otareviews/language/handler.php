<?php

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking OTA Reviews widget languages.
 *
 * @since 	1.0
 */
class Mod_VikBooking_OtareviewsLanguageHandler implements JLanguageHandler
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
			case 'MOD_VIKBOOKING_OTAREVIEWS':
				$result = __('VikBooking OTA Reviews', 'vikbooking');
				break;
			case 'MOD_VIKBOOKING_OTAREVIEWS_DESC':
			$result = __('Widget to show the reviews downloaded from the OTAs by the Channel Manager.', 'vikbooking');
				break;
			case 'VBOMODOTAREVCHOOSECH':
				$result = __('Channel Account', 'vikbooking');
				break;
			case 'VBOMODOTAREVCHOOSECHHELP':
				$result = __('Choose the channel-account combination for which you would like to display the review or the global score. Some data must have been downloaded from the channels in order to be able to display some contents.', 'vikbooking');
				break;
			case 'VBOMODOTAREVORSCORE':
				$result = __('Reviews or Score', 'vikbooking');
				break;
			case 'VBOMODOTAREVORSCOREHELP':
				$result = __('Choose whether to display the reviews or the global score for the selected channel-account.', 'vikbooking');
				break;
			case 'VBOMODOTAREVSHOWREVS':
				$result = __('Reviews', 'vikbooking');
				break;
			case 'VBOMODOTAREVSHOWSCORE':
				$result = __('Global Score', 'vikbooking');
				break;
			case 'VBOMODOTAREVSORTING':
				$result = __('Sorting', 'vikbooking');
				break;
			case 'VBOMODOTAREVSORTSCORE':
				$result = __('by score', 'vikbooking');
				break;
			case 'VBOMODOTAREVSORTDATE':
				$result = __('by date', 'vikbooking');
				break;
			case 'VBOMODOTAREVORDERING':
				$result = __('Ordering', 'vikbooking');
				break;
			case 'VBOMODOTAREVORDERASC':
				$result = __('Ascending', 'vikbooking');
				break;
			case 'VBOMODOTAREVORDERDESC':
				$result = __('Descending', 'vikbooking');
				break;
			case 'VBOMODOTAREVINTROTXT':
				$result = __('Intro Text', 'vikbooking');
				break;
			case 'VBOMODOTAREVINTROTXTHELP':
				$result = __('An optional introduction text that can be printed above the reviews or the global score.', 'vikbooking');
				break;
			case 'VBOMODOTAREVLIM':
				$result = __('Limit', 'vikbooking');
				break;
			case 'VBOMODOTAREVLIMHELP':
				$result = __('The maximum number of reviews to display.', 'vikbooking');
				break;
			case 'VBOMODREVSREVBASEDONTOT':
				$result = __('based on %d reviews', 'vikbooking');
				break;
			case 'VBOMODOTAREVCONTENT':
				$result = __('Contents Level', 'vikbooking');
				break;
			case 'VBOMODOTAREVCONTENTHELP':
				$result = __('Choose how many contents to display. The result may be different depending if you choose to display the reviews or the global score. Selecting maximum will display all the possible information available with the channel, while choosing compact will display only the minimum information.', 'vikbooking');
				break;
			case 'VBOMODOTAREVCONTENTSTAND':
				$result = __('Maximum', 'vikbooking');
				break;
			case 'VBOMODOTAREVCONTENTCOMPA':
				$result = __('Compact', 'vikbooking');
				break;
			case 'VBOMODOTAREVWEBSITE':
				$result = __('Website', 'vikbooking');
				break;

			/**
			 * Review categories
			 */
			case 'VBOGREVCOMFORT':
				$result = __('Comfort', 'vikbooking');
				break;
			case 'VBOGREVLOCATION':
				$result = __('Location', 'vikbooking');
				break;
			case 'VBOGREVCLEAN':
				$result = __('Clean', 'vikbooking');
				break;
			case 'VBOGREVVALUE':
				$result = __('Value for money', 'vikbooking');
				break;
			case 'VBOGREVSTAFF':
				$result = __('Staff', 'vikbooking');
				break;
			case 'VBOGREVFACILITIES':
				$result = __('Facilities', 'vikbooking');
				break;

			/**
			 * Layout and Page
			 */

			case 'JLAYOUT':
				$result = __('Layout', 'vikbooking');
				break;
			case 'JLAYOUT_DESC':
				$result = __('The layout of the module to use. The available layouts are contained within the <b>tmpl</b> folder of the module.', 'vikbooking');
				break;
			case 'JMENUITEM':
				$result = __('Page', 'vikbooking');
				break;
			case 'JMENUITEM_DESC':
				$result = __('Select a page to start the booking process. The page must use a VikBooking shortcode.', 'vikbooking');
				break;
		}

		return $result;
	}
}
