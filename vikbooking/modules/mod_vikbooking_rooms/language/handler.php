<?php

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking Rooms widget languages.
 *
 * @since 	1.0
 */
class Mod_VikBooking_RoomsLanguageHandler implements JLanguageHandler
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

			case 'MOD_VIKBOOKING_ROOMS':
				$result = __('VikBooking Rooms', 'vikbooking');
				break;
			case 'MOD_VIKBOOKING_ROOMS_DESC':
				$result = __('Horizontal carousel for certain rooms.', 'vikbooking');
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

			case 'VBMODROOMSTARTFROM':
				$result = __('from', 'vikbooking');
				break;
			case 'VBMODROOMSCONTINUE':
				$result = __('Details', 'vikbooking');
				break;
			case 'VBMODROOMSBEDS':
				$result = __('Sleeps', 'vikbooking');
				break;
			case 'VBROOMSCONFIGURATION':
				$result = __('Room Configuration', 'vikbooking');
				break;
			case 'VBROOMSSLIDERCONF':
				$result = __('Carousel Configuration', 'vikbooking');
				break;
			case 'VBROOMSWIDTH':
				$result = __('Room Width', 'vikbooking');
				break;
			case 'VBROOMSWIDTHDESC':
				$result = __('Width of the single room displayed', 'vikbooking');
				break;
			case 'VBROOMSNUMB':
				$result = __('Maximum Rooms Displayed', 'vikbooking');
				break;
			// case 'VBROOMSNUMBDESC':
			// 	$result = __('Maximum Rooms Displayed', 'vikbooking');
			// 	break;
			case 'VBROOMSNUMBROW':
				$result = __('Rooms per row', 'vikbooking');
				break;
			case 'VBROOMSDESCLABEL':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBROOMSSCROLLABAR':
				$result = __('Scrollbar', 'vikbooking');
				break;
			case 'VBROOMSSCROLLABARDESC':
				$result = __('Show the scrollbar', 'vikbooking');
				break;
			case 'VBROOMSDOTNAV':
				$result = __('Dotted Navigation', 'vikbooking');
				break;
			// case 'VBROOMSDOTNAVDESC':
			// 	$result = __('Show Dotted Navigation', 'vikbooking');
			// 	break;
			case 'VBROOMSARROWS':
				$result = __('Display Navigation', 'vikbooking');
				break;
			case 'VBROOMSARROWSDESC':
				$result = __('Display Navigation', 'vikbooking');
				break;
			case 'VBROOMSAUTOPLAY':
				$result = __('Autoplay', 'vikbooking');
				break;
			case 'VBROOMSAUTOPLAYDESC':
				$result = __('Enable automatic scrolling', 'vikbooking');
				break;
			case 'VBROOMSTIMESCROLL':
				$result = __('Time Scrolling', 'vikbooking');
				break;
			case 'VBROOMSTIMESCROLLDESC':
				$result = __('Autoplay Time Scrolling', 'vikbooking');
				break;
			case 'VBROOMSORDERFILTER':
				$result = __('Ordering and Filtering', 'vikbooking');
				break;
			// case 'VBROOMSORDERFILTERDESC':
			// 	$result = __('Ordering and Filtering', 'vikbooking');
			// 	break;
			case 'ORDERING':
				$result = __('Ordering', 'vikbooking');
				break;
			case 'BYPRICE':
				$result = __('By Price', 'vikbooking');
				break;
			case 'BYNAME':
				$result = __('By Name', 'vikbooking');
				break;
			case 'LOADJQ':
				$result = __('Load jQuery', 'vikbooking');
				break;
			case 'LOADJQDESC':
				$result = __('Load jQuery', 'vikbooking');
				break;
			case 'BYCATEGORY':
				$result = __('By Category', 'vikbooking');
				break;
			case 'VBROOMSORDERTYPE':
				$result = __('Order Type', 'vikbooking');
				break;
			// case 'VBROOMSORDERTYPEDESC':
			// 	$result = __('Ascending or Descending', 'vikbooking');
			// 	break;
			case 'TYPEASC':
				$result = __('Ascending', 'vikbooking');
				break;
			case 'TYPEDESC':
				$result = __('Descending', 'vikbooking');
				break;
			case 'VBROOMSCURRENCY':
				$result = __('Currency Symbol', 'vikbooking');
				break;
			case 'VBROOMSCURRENCYDESC':
				$result = __('The Currency Symbol to display', 'vikbooking');
				break;
			case 'VBROOMSCATEGORY':
				$result = __('Show Category Name', 'vikbooking');
				break;
			case 'VBROOMSCATEGORYDESC':
				$result = __('Show Category Name', 'vikbooking');
				break;
			case 'VBROOMSSHOWPEOPLE':
				$result = __('Show Number People', 'vikbooking');
				break;
			case 'VBROOMSSHOWPEOPLEDESC':
				$result = __('Show the number of the people of the room/apartment', 'vikbooking');
				break;
			case 'VBROOMSFILTERCAT':
				$result = __('Filtering by Category', 'vikbooking');
				break;
			case 'VBROOMSSELECTCAT':
				$result = __('Select a Category', 'vikbooking');
				break;
			case 'VBROOMSSHOWDETAILSBTN':
				$result = __('Show Details button', 'vikbooking');
				break;
			case 'SHOWROOMDESC':
				$result = __('Show description', 'vikbooking');
				break;
			case 'SHOWCARATS':
				$result = __('Show Characteristics', 'vikbooking');
				break;
			case 'SHOWCARATSDESC':
				$result = __('Show the Vik Booking characteristics for each room', 'vikbooking');
				break;
			case 'VBMODROOMSPREV':
				$result = __('Prev', 'vikbooking');
				break;
			case 'VBMODROOMSNEXT':
				$result = __('Next', 'vikbooking');
				break;
			case 'JYES':
				$result = __('yes', 'vikbooking');
				break;
			case 'JNO':
				$result = __('no', 'vikbooking');
				break;
			case 'VBMODROOMSBED':
				$result = __('Sleep', 'vikbooking');
				break;
		}

		return $result;
	}
}
