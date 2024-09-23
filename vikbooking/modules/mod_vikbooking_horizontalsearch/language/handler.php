<?php

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking Search widget languages.
 *
 * @since 	1.0
 */
class Mod_VikBooking_HorizontalsearchLanguageHandler implements JLanguageHandler
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

			case 'MOD_VIKBOOKING_HORIZONTALSEARCH':
				$result = __('VikBooking Search Form', 'vikbooking');
				break;
			case 'MOD_VIKBOOKING_HORIZONTALSEARCH_DESC':
				$result = __('Horizontal Search Form to start booking rooms.', 'vikbooking');
				break;
			case 'TITLE':
				$result = __('Title', 'vikbooking');
				break;
			case 'DEFADULTS':
				$result = __('Default Adults', 'vikbooking');
				break;
			case 'BOOKNOW':
				$result = __('Book Now', 'vikbooking');
				break;
			case 'SHOWCAT':
				$result = __('Category Selection', 'vikbooking');
				break;
			case 'YES':
				$result = __('Enabled', 'vikbooking');
				break;
			case 'NO':
				$result = __('Disabled', 'vikbooking');
				break;
			case 'DISABLED':
				$result = __('-- Disabled --', 'vikbooking');
				break;
			case 'FORCESINGLEROOMSEARCH':
				$result = __('Force Specific Room', 'vikbooking');
				break;
			case 'FORCESINGLEROOMSEARCHHELP':
				$result = __('If not disabled, the search module will check the availability only for the selected room type. Keep it disabled if you want to search over the availability of any room types.', 'vikbooking');
				break;
			case 'FORCESINGLECATEGORYSEARCH':
				$result = __('Force Specific Category', 'vikbooking');
				break;
			case 'FORCESINGLECATEGORYSEARCHHELP':
				$result = __('If not disabled, the search module will check the availability only for the rooms belonging to the selected category. If disabled, the system will display a drop down menu for selecting the category filter (the parameter Show Categories must be enabled in this module or disabled for forcing the Category).', 'vikbooking');
				break;
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
			case 'SELDATESFORMAT':
				$result = __('Dates Layout', 'vikbooking');
				break;
			case 'SELDATESFORMATHELP':
				$result = __('Choose whether to display the selected dates in the format defined in Vik Booking, or to format them in a human-readable way, like Monday, 1 January, with a different layout. You can also choose to display a request information form.', 'vikbooking');
				break;
			case 'SELDATESFORMATDT':
				$result = __('Standard Datepicker', 'vikbooking');
				break;
			case 'SELDATESFORMATHU':
				$result = __('Human Readable', 'vikbooking');
				break;
			case 'MONDAYSLEN':
				$result = __('Months and Days Length', 'vikbooking');
				break;
			case 'MONDAYSLENHELP':
				$result = __('Choose whether to display the full months and week days, or their 3-char versions. Such as December or Dec, and Monday or Mon. This applies only if the Dates Layout parameter is set to Human Readable.', 'vikbooking');
				break;
			case 'MONDAYSLENLONG':
				$result = __('Full', 'vikbooking');
				break;
			case 'MONDAYSLEN3':
				$result = __('3-char', 'vikbooking');
				break;
			case 'HORIZSEARCHPICKDATE':
				$result = __('Select date', 'vikbooking');
				break;
			case 'VBMHORSGUESTS':
				$result = __('Guests', 'vikbooking');
				break;
			case 'SELDATESFORMAT_INQUIRY':
				$result = __('Inquiry (Request information)', 'vikbooking');
				break;
			case 'INQ_SEND_REQUESTS':
				$result = __('Send request', 'vikbooking');
				break;
			case 'INQ_CHECK_AVAILABILITY':
				$result = __('Check availability', 'vikbooking');
				break;
			case 'INQ_NOTES_SPREQUESTS':
				$result = __('Notes/Special requests', 'vikbooking');
				break;
			case 'INQ_OR_BOOK_ONLINE':
				$result = __('or book online', 'vikbooking');
				break;
			case 'VBO_PLEASE_FILL_FIELDS':
				$result = __('Please fill all the required fields', 'vikbooking');
				break;
			case 'VBO_PLEASE_SEL_DATES':
				$result = __('Please select the dates for your stay, even if you have flexible dates.', 'vikbooking');
				break;
			case 'VBO_THANKS_INQ_SUBMITTED':
				$result = __('Thank you! We have successfully received your request and will reply as soon as possible.', 'vikbooking');
				break;

			/**
			 * Front-end definitions
			 */
			case 'SEARCHD':
				$result = __('Search', 'vikbooking');
				break;
			case 'VBMCHECKIN':
				$result = __('Check-in date', 'vikbooking');
				break;
			case 'VBMCHECKOUT':
				$result = __('Check-out date', 'vikbooking');
				break;
			case 'VBMROOMCAT':
				$result = __('Category', 'vikbooking');
				break;
			case 'VBMALLCAT':
				$result = __('Any', 'vikbooking');
				break;
			case 'VBMFORMROOMSN':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBMFORMADULTS':
				$result = __('Adults', 'vikbooking');
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
		}

		return $result;
	}
}
