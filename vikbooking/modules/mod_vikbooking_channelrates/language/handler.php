<?php

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking Channel Rates widget languages.
 *
 * @since 	1.0
 */
class Mod_VikBooking_ChannelratesLanguageHandler implements JLanguageHandler
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
			case 'MOD_VIKBOOKING_CHANNELRATES':
				$result = __('VikBooking Channel Rates', 'vikbooking');
				break;
			case 'MOD_VIKBOOKING_CHANNELRATES_DESC':
				$result = __('Rates comparison between the website and the channels connected with Vik Channel Manager.', 'vikbooking');
				break;
			case 'VBOMODCMRSELCHANNELS':
				$result = __('Select the Channels to Compare', 'vikbooking');
				break;
			case 'VBOMODCMRSELCHANNELSHELP':
				$result = __('The list of channels to display with the corresponding rates for the selected period. Only the channels for which you have mapped some rooms will be available. Channels based on iCal connections do not support rooms mapping. Please notice that the module reads the rates from the website and applies the rules for altering the rates for the channels. This means that you must have launched before a Bulk Action of type Rates Upload from Vik Channel Manager.', 'vikbooking');
				break;
			case 'VBOMODCMRDEFPRICE':
				$result = __('Default Type of Price', 'vikbooking');
				break;
			case 'VBOMODCMRDEFPRICEHELP':
				$result = __('Select the preferred type of price to use (if available) for displaying the rates. For example, if you have multiple rate plans for your rooms, like \'Free Cancellation\' and \'Non Refundable\', choose which rates you would like to display as starting from price. If empty, the system will always take the lowest rate available for each room.', 'vikbooking');
				break;
			case 'VBOMODCMRDEFPRICEEMPTY':
				$result = __('Lowest Rate Available', 'vikbooking');
				break;
			case 'VBOMODCMRDEFTAX':
				$result = __('Taxes', 'vikbooking');
				break;
			case 'VBOMODCMRDEFTAXHELP':
				$result = __('Choose whether the rates displayed should be before taxes or after taxes.', 'vikbooking');
				break;
			case 'VBOMODCMRDEFTAXEX':
				$result = __('Rates Before Tax (Excluded)', 'vikbooking');
				break;
			case 'VBOMODCMRDEFTAXIN':
				$result = __('Rates After Tax (Included)', 'vikbooking');
				break;
			case 'VBOMODCMRINTROTXT':
				$result = __('Intro Text', 'vikbooking');
				break;
			case 'VBOMODCMRINTROTXTHELP':
				$result = __('The text to be displayed above the search form. It should contain attractive words for marketing, like \'Best Available Rate by booking from our website\'.', 'vikbooking');
				break;
			case 'VBOMODCMRINTROTXTDEF':
				$result = __('Best Available Rate by booking from our website!', 'vikbooking');
				break;
			case 'VBOMODCMRMODSTYLE':
				$result = __('Layout Style', 'vikbooking');
				break;
			case 'VBOMODCMRMODSTYLEHELP':
				$result = __('Choose the style of the module you prefer. The style \'Flat\' will occupy space in the position of your template where the module is published. This style will display the module vertically together with the search form. The style \'Fixed\' will place the module at the bottom of your page, on top of any other content. With this style, the module will not move when the page scrolls because it has a fixed position.', 'vikbooking');
				break;
			case 'VBOMODCMRMODSTYLEFLATVERT':
				$result = __('Flat, Vertical', 'vikbooking');
				break;
			case 'VBOMODCMRMODSTYLEFLATFIXD':
				$result = __('Fixed', 'vikbooking');
				break;
			case 'COM_MODULES_CONTACTS_FIELDSET_LABEL':
				$result = __('Contact Buttons', 'vikbooking');
				break;
			case 'VBOMODCMRCEMAIL':
				$result = __('Email Contact', 'vikbooking');
				break;
			case 'VBOMODCMRCEMAILHELP':
				$result = __('If not empty, a button will be displayed for the users to contact you via email.', 'vikbooking');
				break;
			case 'VBOMODCMRCMESSENGER':
				$result = __('Messenger Contact', 'vikbooking');
				break;
			case 'VBOMODCMRCMESSENGERHELP':
				$result = __('If not empty, a button will be displayed for the users to contact you through Facebook Messenger. Enter here your Facebook vanity username. If you don’t have a username yet, you can use your numerical Facebook profile ID instead.', 'vikbooking');
				break;
			case 'VBOMODCMRCWHATSAPP':
				$result = __('Whatsapp Contact', 'vikbooking');
				break;
			case 'VBOMODCMRCWHATSAPPHELP':
				$result = __('If not empty, a button will be displayed for the users to contact you through Whatsapp. Enter your phone number without leading zeros, but with the plus sign before your country code. Ex. +5511999999999', 'vikbooking');
				break;
			case 'VBOMODCMRCPHONE':
				$result = __('Phone Contact', 'vikbooking');
				break;
			case 'VBOMODCMRCPHONEHELP':
				$result = __('If not empty, a button will be displayed for the users to call you through their mobile phone.', 'vikbooking');
				break;
			case 'VBOMODCMRREFRESHRATES':
				$result = __('Refresh Rates', 'vikbooking');
				break;
			case 'SEARCHD':
				$result = __('Book Now', 'vikbooking');
				break;
			case 'VBMCHECKIN':
				$result = __('Check-In Date', 'vikbooking');
				break;
			case 'VBMCHECKOUT':
				$result = __('Check-Out Date', 'vikbooking');
				break;
			case 'VBMFORMROOMSN':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBMFORMADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBMHORSGUESTS':
				$result = __('Guests', 'vikbooking');
				break;
			case 'VBMFORMNUMROOM':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBFORMCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBMJSTOTNIGHTS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBOMODCMRWEBSITERATE':
				$result = __('Our Best Rate', 'vikbooking');
				break;
			case 'VBJQCALDONE':
				$result = __('Done', 'vikbooking');
				break;
			case 'VBJQCALPREV':
				$result = __('Prev', 'vikbooking');
				break;
			case 'VBJQCALNEXT':
				$result = __('Next', 'vikbooking');
				break;
			case 'VBJQCALTODAY':
				$result = __('Today', 'vikbooking');
				break;
			case 'VBJQCALSUN':
				$result = __('Sunday', 'vikbooking');
				break;
			case 'VBJQCALMON':
				$result = __('Monday', 'vikbooking');
				break;
			case 'VBJQCALTUE':
				$result = __('Tuesday', 'vikbooking');
				break;
			case 'VBJQCALWED':
				$result = __('Wednesday', 'vikbooking');
				break;
			case 'VBJQCALTHU':
				$result = __('Thursday', 'vikbooking');
				break;
			case 'VBJQCALFRI':
				$result = __('Friday', 'vikbooking');
				break;
			case 'VBJQCALSAT':
				$result = __('Saturday', 'vikbooking');
				break;
			case 'VBJQCALWKHEADER':
				$result = __('Wk', 'vikbooking');
				break;
			case 'VBMONTHONE':
				$result = __('January', 'vikbooking');
				break;
			case 'VBMONTHTWO':
				$result = __('February', 'vikbooking');
				break;
			case 'VBMONTHTHREE':
				$result = __('March', 'vikbooking');
				break;
			case 'VBMONTHFOUR':
				$result = __('April', 'vikbooking');
				break;
			case 'VBMONTHFIVE':
				$result = __('May', 'vikbooking');
				break;
			case 'VBMONTHSIX':
				$result = __('June', 'vikbooking');
				break;
			case 'VBMONTHSEVEN':
				$result = __('July', 'vikbooking');
				break;
			case 'VBMONTHEIGHT':
				$result = __('August', 'vikbooking');
				break;
			case 'VBMONTHNINE':
				$result = __('September', 'vikbooking');
				break;
			case 'VBMONTHTEN':
				$result = __('October', 'vikbooking');
				break;
			case 'VBMONTHELEVEN':
				$result = __('November', 'vikbooking');
				break;
			case 'VBMONTHTWELVE':
				$result = __('December', 'vikbooking');
				break;
			case 'VBOMODCMRCUSTRMODPCENT':
				$result = __('Custom increase mode', 'vikbooking');
				break;
			case 'VBOMODCMRCUSTRMODPCENTHELP':
				$result = __('The website rates modification rules for the OTAs are taken automatically from what was set up on Vik Channel Manager. However, it is also possible to force the increase operator for the website rates to calculate the final costs on the OTAs. Choose between a percent increase operator or an absolute amount in your currency. For example, 15 % or EUR 20', 'vikbooking');
				break;
			case 'VBOMODCMRCUSTRMODPCENTP':
				$result = __('Percent %', 'vikbooking');
				break;
			case 'VBOMODCMRCUSTRMODPCENTF':
				$result = __('Absolute amount', 'vikbooking');
				break;
			case 'VBOMODCMRCUSTRMODVAL':
				$result = __('Custom increase value', 'vikbooking');
				break;
			case 'VBOMODCMRCUSTRMODVALHELP':
				$result = __('The value for increasing the website rates and to transmit them to the OTAs is taken automatically from what you have configured in Vik Channel Manager. However, you can force a custom increase value to be used for the calculation of the final costs for the OTAs by setting a value here greater than zero. Set the proper increase mode to use this value as a fixed or a percent amount.', 'vikbooking');
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
