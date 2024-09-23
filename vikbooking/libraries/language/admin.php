<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	language
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking plugin admin languages.
 *
 * @since 	1.0
 */
class VikBookingLanguageAdmin implements JLanguageHandler
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
			 * Definitions
			 */
			case 'VBSAVE':
				$result = __('Save', 'vikbooking');
				break;
			case 'VBANNULLA':
				$result = __('Cancel', 'vikbooking');
				break;
			case 'VBELIMINA':
				$result = __('Delete', 'vikbooking');
				break;
			case 'VBBACK':
				$result = __('Back', 'vikbooking');
				break;
			case 'VBCONFIRMED':
				$result = __('Confirmed', 'vikbooking');
				break;
			case 'VBSTANDBY':
				$result = __('Standby', 'vikbooking');
				break;
			case 'VBLEFT':
				$result = __('Left', 'vikbooking');
				break;
			case 'VBRIGHT':
				$result = __('Right', 'vikbooking');
				break;
			case 'VBBOTTOMCENTER':
				$result = __('Bottom, Center', 'vikbooking');
				break;
			case 'VBSTATUS':
				$result = __('Status', 'vikbooking');
				break;
			case 'VBMAINDEAFULTTITLE':
				$result = __('Vik Booking - Rooms List', 'vikbooking');
				break;
			case 'VBMAINDEFAULTDEL':
				$result = __('Delete Room', 'vikbooking');
				break;
			case 'VBMAINDEFAULTEDITC':
				$result = __('Edit Room', 'vikbooking');
				break;
			case 'VBMAINDEFAULTEDITT':
				$result = __('Edit/View Rates', 'vikbooking');
				break;
			case 'VBMAINDEFAULTCAL':
				$result = __('Rooms Calendar', 'vikbooking');
				break;
			case 'VBMAINDEFAULTNEW':
				$result = __('New Room', 'vikbooking');
				break;
			case 'VBMAINIVATITLE':
				$result = __('Vik Booking - Tax Rates List', 'vikbooking');
				break;
			case 'VBMAINIVADEL':
				$result = __('Delete Tax Rate', 'vikbooking');
				break;
			case 'VBMAINIVAEDIT':
				$result = __('Edit Tax Rate', 'vikbooking');
				break;
			case 'VBMAINIVANEW':
				$result = __('New Tax Rate', 'vikbooking');
				break;
			case 'VBMAINCATTITLE':
				$result = __('Vik Booking - Categories List', 'vikbooking');
				break;
			case 'VBMAINCATDEL':
				$result = __('Delete Categories', 'vikbooking');
				break;
			case 'VBMAINCATEDIT':
				$result = __('Edit Category', 'vikbooking');
				break;
			case 'VBMAINCATNEW':
				$result = __('New Category', 'vikbooking');
				break;
			case 'VBMAINCARATTITLE':
				$result = __('Vik Booking - Characteristics List', 'vikbooking');
				break;
			case 'VBMAINCARATDEL':
				$result = __('Delete Characteristics', 'vikbooking');
				break;
			case 'VBMAINCARATEDIT':
				$result = __('Edit Characteristic', 'vikbooking');
				break;
			case 'VBMAINCARATNEW':
				$result = __('New Characteristic', 'vikbooking');
				break;
			case 'VBMAINOPTTITLE':
				$result = __('Vik Booking - Options List', 'vikbooking');
				break;
			case 'VBMAINOPTDEL':
				$result = __('Delete Options', 'vikbooking');
				break;
			case 'VBMAINOPTEDIT':
				$result = __('Edit Option', 'vikbooking');
				break;
			case 'VBMAINOPTNEW':
				$result = __('New Option', 'vikbooking');
				break;
			case 'VBMAINPRICETITLE':
				$result = __('Vik Booking - Types of Price', 'vikbooking');
				break;
			case 'VBMAINPRICEDEL':
				$result = __('Delete Prices', 'vikbooking');
				break;
			case 'VBMAINPRICEEDIT':
				$result = __('Edit Price', 'vikbooking');
				break;
			case 'VBMAINPRICENEW':
				$result = __('New Price', 'vikbooking');
				break;
			case 'VBMAINIVATITLENEW':
				$result = __('Vik Booking - New Tax Rate', 'vikbooking');
				break;
			case 'VBMAINIVATITLEEDIT':
				$result = __('Vik Booking - Edit Tax Rate', 'vikbooking');
				break;
			case 'VBMAINPRICETITLENEW':
				$result = __('Vik Booking - New Price', 'vikbooking');
				break;
			case 'VBMAINPRICETITLEEDIT':
				$result = __('Vik Booking - Edit Price', 'vikbooking');
				break;
			case 'VBMAINCATTITLENEW':
				$result = __('Vik Booking - New Category', 'vikbooking');
				break;
			case 'VBMAINCATTITLEEDIT':
				$result = __('Vik Booking - Edit Category', 'vikbooking');
				break;
			case 'VBMAINCARATTITLENEW':
				$result = __('Vik Booking - New Characteristic', 'vikbooking');
				break;
			case 'VBMAINCARATTITLEEDIT':
				$result = __('Vik Booking - Edit Characteristic', 'vikbooking');
				break;
			case 'VBMAINOPTTITLENEW':
				$result = __('Vik Booking - New Option', 'vikbooking');
				break;
			case 'VBMAINOPTTITLEEDIT':
				$result = __('Vik Booking - Edit Option', 'vikbooking');
				break;
			case 'VBMAINROOMTITLENEW':
				$result = __('Vik Booking - New Room', 'vikbooking');
				break;
			case 'VBMAINROOMTITLEEDIT':
				$result = __('Vik Booking - Edit Room', 'vikbooking');
				break;
			case 'VBMAINTARIFFETITLE':
				$result = __('Vik Booking - Rooms Base Rates', 'vikbooking');
				break;
			case 'VBMAINTARIFFEDEL':
				$result = __('Delete Rates', 'vikbooking');
				break;
			case 'VBMAINTARIFFEBACK':
				$result = __('Quit Inserting', 'vikbooking');
				break;
			case 'VBMAINORDERTITLE':
				$result = __('Vik Booking - Bookings List', 'vikbooking');
				break;
			case 'VBMAINORDERDEL':
				$result = __('Remove Bookings', 'vikbooking');
				break;
			case 'VBMAINORDEREDIT':
				$result = __('Booking Details', 'vikbooking');
				break;
			case 'VBMAINORDERTITLEEDIT':
				$result = __('Vik Booking - Booking Details', 'vikbooking');
				break;
			case 'VBMAINCALTITLE':
				$result = __('Vik Booking - Bookings Calendar', 'vikbooking');
				break;
			case 'VBMAINCHOOSEBUSY':
				$result = __('Reservations for', 'vikbooking');
				break;
			case 'VBMAINEBUSYTITLE':
				$result = __('Vik Booking - Edit Reservation', 'vikbooking');
				break;
			case 'VBMAINEBUSYDEL':
				$result = __('Delete Reservation', 'vikbooking');
				break;
			case 'VBMAINCONFIGTITLE':
				$result = __('Vik Booking - Global Configuration', 'vikbooking');
				break;
			case 'VBMAINPAYMENTSTITLE':
				$result = __('Vik Booking - Payment Methods', 'vikbooking');
				break;
			case 'VBMAINPAYMENTSDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBMAINPAYMENTSEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINPAYMENTSNEW':
				$result = __('New', 'vikbooking');
				break;
			case 'VBMAINPAYMENTTITLENEW':
				$result = __('Vik Booking - New Payment Method', 'vikbooking');
				break;
			case 'VBMAINPAYMENTTITLEEDIT':
				$result = __('Vik Booking - Edit Payment Method', 'vikbooking');
				break;
			case 'VBMAINOVERVIEWTITLE':
				$result = __('Vik Booking - Availability Overview', 'vikbooking');
				break;
			case 'VBPANELONE':
				$result = __('Main Settings', 'vikbooking');
				break;
			case 'VBPANELTWO':
				$result = __('Prices and Payments', 'vikbooking');
				break;
			case 'VBPANELTHREE':
				$result = __('Views and Layout', 'vikbooking');
				break;
			case 'VBPANELFOUR':
				$result = __('Company and Reservations', 'vikbooking');
				break;
			case 'VBMESSDELBUSY':
				$result = __('Reservation Deleted', 'vikbooking');
				break;
			case 'VBROOMNOTCONSTO':
				$result = __('to', 'vikbooking');
				break;
			case 'VBROOMNOTRIT':
				$result = __('Room is not available from', 'vikbooking');
				break;
			case 'ERRPREV':
				$result = __('Check-out date is previous than Check-in', 'vikbooking');
				break;
			case 'ERRROOMLOCKED':
				$result = __('The room is not available in the days requested. The Room is waiting for the payment to confirm the reservation', 'vikbooking');
				break;
			case 'RESUPDATED':
				$result = __('Reservation Updated', 'vikbooking');
				break;
			case 'VBSETTINGSAVED':
				$result = __('Settings Saved. Click the button Renew Session to apply the new configuration settings immediately.', 'vikbooking');
				break;
			case 'VBPAYMENTSAVED':
				$result = __('Payment Method Saved', 'vikbooking');
				break;
			case 'ERRINVFILEPAYMENT':
				$result = __('File Class is already used in another payment method', 'vikbooking');
				break;
			case 'VBPAYMENTUPDATED':
				$result = __('Payment Method Updated', 'vikbooking');
				break;
			case 'VBRENTALORD':
				$result = __('Reservation', 'vikbooking');
				break;
			case 'VBCOMPLETED':
				$result = __('Completed', 'vikbooking');
				break;
			case 'ERRCONFORDERROOMNA':
				$result = __('Error, the Room is no longer available. Reservation was set to confirmed', 'vikbooking');
				break;
			case 'VBORDERSETASCONF':
				$result = __('Booking successfully set to Confirmed', 'vikbooking');
				break;
			case 'VBOVERVIEWNOROOMS':
				$result = __('No Room Found', 'vikbooking');
				break;
			case 'VBFOOTER':
				$result = __('VikBooking v.%s - Powered by', 'vikbooking');
				break;
			case 'VBINSERTFEE':
				$result = __('Insert base rates', 'vikbooking');
				break;
			case 'VBMSGONE':
				$result = __('No Prices Found, Insert Prices from', 'vikbooking');
				break;
			case 'VBHERE':
				$result = __('Here', 'vikbooking');
				break;
			case 'VBMSGTWO':
				$result = __('Days Field is empty', 'vikbooking');
				break;
			case 'VBDAYS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBDAYSFROM':
				$result = __('from', 'vikbooking');
				break;
			case 'VBDAYSTO':
				$result = __('to', 'vikbooking');
				break;
			case 'VBDAILYPRICES':
				$result = __('Price(s) per Night', 'vikbooking');
				break;
			case 'VBDAY':
				$result = __('Night', 'vikbooking');
				break;
			case 'VBINSERT':
				$result = __('Insert', 'vikbooking');
				break;
			case 'VBMODRES':
				$result = __('Edit Reservation', 'vikbooking');
				break;
			case 'VBQUICKBOOK':
				$result = __('Quick Reservation', 'vikbooking');
				break;
			case 'VBBOOKMADE':
				$result = __('Reservation Saved', 'vikbooking');
				break;
			case 'VBBOOKNOTMADE':
				$result = __('Unable to save the Reservation, Room not Available', 'vikbooking');
				break;
			case 'VBMSGTHREE':
				$result = __('Check-in Field is empty', 'vikbooking');
				break;
			case 'VBMSGFOUR':
				$result = __('Check-out Field is empty', 'vikbooking');
				break;
			case 'VBDATEPICKUP':
				$result = __('Check-in Date and Time', 'vikbooking');
				break;
			case 'VBAT':
				$result = __('at', 'vikbooking');
				break;
			case 'VBDATERELEASE':
				$result = __('Check-out Date and Time', 'vikbooking');
				break;
			case 'VBCUSTINFO':
				$result = __('Customer Information', 'vikbooking');
				break;
			case 'VBMAKERESERV':
				$result = __('Save Booking', 'vikbooking');
				break;
			case 'VBNOFUTURERES':
				$result = __('No Future Reservations', 'vikbooking');
				break;
			case 'VBVIEW':
				$result = __('View Mode', 'vikbooking');
				break;
			case 'VBTHREEMONTHS':
				$result = __('3 Months', 'vikbooking');
				break;
			case 'VBSIXMONTHS':
				$result = __('6 Months', 'vikbooking');
				break;
			case 'VBTWELVEMONTHS':
				$result = __('1 Year', 'vikbooking');
				break;
			case 'VBSUN':
				$result = __('Sun', 'vikbooking');
				break;
			case 'VBMON':
				$result = __('Mon', 'vikbooking');
				break;
			case 'VBTUE':
				$result = __('Tue', 'vikbooking');
				break;
			case 'VBWED':
				$result = __('Wed', 'vikbooking');
				break;
			case 'VBTHU':
				$result = __('Thu', 'vikbooking');
				break;
			case 'VBFRI':
				$result = __('Fri', 'vikbooking');
				break;
			case 'VBSAT':
				$result = __('Sat', 'vikbooking');
				break;
			case 'VBPICKUPAT':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBRELEASEAT':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBNOROOMSFOUND':
				$result = __('No rooms found', 'vikbooking');
				break;
			case 'VBJSDELROOM':
				$result = __('Every selected Room will be removed with its own contents. Confirm', 'vikbooking');
				break;
			case 'VBPVIEWROOMONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPVIEWROOMTWO':
				$result = __('Category', 'vikbooking');
				break;
			case 'VBPVIEWROOMTHREE':
				$result = __('Characteristics', 'vikbooking');
				break;
			case 'VBPVIEWROOMFOUR':
				$result = __('Options', 'vikbooking');
				break;
			case 'VBPVIEWROOMSIX':
				$result = __('Available', 'vikbooking');
				break;
			case 'VBPVIEWROOMSEVEN':
				$result = __('Units', 'vikbooking');
				break;
			case 'VBMAKENOTAVAIL':
				$result = __('Make Not Available', 'vikbooking');
				break;
			case 'VBMAKEAVAIL':
				$result = __('Make Available', 'vikbooking');
				break;
			case 'VBANYTHING':
				$result = __('Any', 'vikbooking');
				break;
			case 'VBNOORDERSFOUND':
				$result = __('No bookings found', 'vikbooking');
				break;
			case 'VBJSDELORDER':
				$result = __('Every selected booking will be removed with its reservation. Confirm', 'vikbooking');
				break;
			case 'VBPVIEWORDERSONE':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBPVIEWORDERSTWO':
				$result = __('Customer Information', 'vikbooking');
				break;
			case 'VBPVIEWORDERSTHREE':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBPVIEWORDERSPEOPLE':
				$result = __('Guests', 'vikbooking');
				break;
			case 'VBPVIEWORDERSFOUR':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBPVIEWORDERSFIVE':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBPVIEWORDERSSIX':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBPVIEWORDERSSEVEN':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBPVIEWORDERSEIGHT':
				$result = __('Status', 'vikbooking');
				break;
			case 'VBPVIEWPLACESONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBNOIVAFOUND':
				$result = __('No Tax rates Found', 'vikbooking');
				break;
			case 'VBJSDELIVA':
				$result = __('Remove every selected Tax Rate', 'vikbooking');
				break;
			case 'VBPVIEWIVAONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPVIEWIVATWO':
				$result = __('tax Rate', 'vikbooking');
				break;
			case 'VBNOCATEGORIESFOUND':
				$result = __('No Categories found', 'vikbooking');
				break;
			case 'VBJSDELCATEGORIES':
				$result = __('Remove every selected Category', 'vikbooking');
				break;
			case 'VBPVIEWCATEGORIESONE':
				$result = __('Category Name', 'vikbooking');
				break;
			case 'VBNOCARATFOUND':
				$result = __('No Characteristics found', 'vikbooking');
				break;
			case 'VBJSDELCARAT':
				$result = __('Remove every selected Characteristic', 'vikbooking');
				break;
			case 'VBPVIEWCARATONE':
				$result = __('Characteristic Name', 'vikbooking');
				break;
			case 'VBPVIEWCARATTWO':
				$result = __('Icon', 'vikbooking');
				break;
			case 'VBPVIEWCARATTHREE':
				$result = __('Text', 'vikbooking');
				break;
			case 'VBNOOPTIONALSFOUND':
				$result = __('No Options found', 'vikbooking');
				break;
			case 'VBJSDELOPTIONALS':
				$result = __('Remove every selected Option', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSTWO':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSTHREE':
				$result = __('Price', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSFOUR':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSFIVE':
				$result = __('Per Night', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSSIX':
				$result = __('Allowed Quantity', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSSEVEN':
				$result = __('Image', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSEIGHT':
				$result = __('Maximum Cost', 'vikbooking');
				break;
			case 'VBNOPRICESFOUND':
				$result = __('No Prices Found', 'vikbooking');
				break;
			case 'VBJSDELPRICES':
				$result = __('Remove every selected Price ? Each Tax Rate with one of these prices will become null.', 'vikbooking');
				break;
			case 'VBPVIEWPRICESONE':
				$result = __('Price Name', 'vikbooking');
				break;
			case 'VBPVIEWPRICESTWO':
				$result = __('Price Attributes', 'vikbooking');
				break;
			case 'VBPVIEWPRICESTHREE':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBJSDELBUSY':
				$result = __('Delete Reservation', 'vikbooking');
				break;
			case 'VBPEDITBUSYONE':
				$result = __('Reservation not found', 'vikbooking');
				break;
			case 'VBPEDITBUSYTWO':
				$result = __('Booking Date', 'vikbooking');
				break;
			case 'VBPEDITBUSYTHREE':
				$result = __('Reservation for', 'vikbooking');
				break;
			case 'VBPEDITBUSYFOUR':
				$result = __('Check-in Date', 'vikbooking');
				break;
			case 'VBPEDITBUSYFIVE':
				$result = __('At H:M', 'vikbooking');
				break;
			case 'VBPEDITBUSYSIX':
				$result = __('Check-out Date', 'vikbooking');
				break;
			case 'VBPEDITBUSYSEVEN':
				$result = __('Price Type', 'vikbooking');
				break;
			case 'VBPEDITBUSYEIGHT':
				$result = __('Options/Taxes/Fees', 'vikbooking');
				break;
			case 'VBEDITORDERONE':
				$result = __('Booking Date', 'vikbooking');
				break;
			case 'VBEDITORDERTWO':
				$result = __('Customer Info', 'vikbooking');
				break;
			case 'VBEDITORDERTHREE':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBEDITORDERFOUR':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBEDITORDERFIVE':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBEDITORDERSIX':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBEDITORDERSEVEN':
				$result = __('Rate', 'vikbooking');
				break;
			case 'VBEDITORDEREIGHT':
				$result = __('Options', 'vikbooking');
				break;
			case 'VBEDITORDERNINE':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBEDITORDERROOMSNUM':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBEDITORDERADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBEDITORDERCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBNEWIVAONE':
				$result = __('Tax Rate Name', 'vikbooking');
				break;
			case 'VBNEWIVATWO':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBNEWPRICEONE':
				$result = __('Price Name', 'vikbooking');
				break;
			case 'VBNEWPRICETWO':
				$result = __('Price Attributes', 'vikbooking');
				break;
			case 'VBNEWPRICETHREE':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBNEWCATONE':
				$result = __('Category Name', 'vikbooking');
				break;
			case 'VBNEWCARATONE':
				$result = __('Characteristic Name', 'vikbooking');
				break;
			case 'VBNEWCARATTWO':
				$result = __('Characteristic Image', 'vikbooking');
				break;
			case 'VBNEWCARATTHREE':
				$result = __('Tooltip Text or Font Icon HTML', 'vikbooking');
				break;
			case 'VBNEWOPTONE':
				$result = __('Option Name', 'vikbooking');
				break;
			case 'VBNEWOPTTWO':
				$result = __('Option Description', 'vikbooking');
				break;
			case 'VBNEWOPTTHREE':
				$result = __('Option Price', 'vikbooking');
				break;
			case 'VBNEWOPTFOUR':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBNEWOPTFIVE':
				$result = __('Cost per Night', 'vikbooking');
				break;
			case 'VBNEWOPTSIX':
				$result = __('Selectable Quantity', 'vikbooking');
				break;
			case 'VBNEWOPTSEVEN':
				$result = __('Option Image', 'vikbooking');
				break;
			case 'VBNEWOPTEIGHT':
				$result = __('Maximum Cost', 'vikbooking');
				break;
			case 'VBNEWOPTNINE':
				$result = __('Resize Image', 'vikbooking');
				break;
			case 'VBNEWOPTTEN':
				$result = __('If Larger than', 'vikbooking');
				break;
			case 'VBNEWROOMONE':
				$result = __('Room Category', 'vikbooking');
				break;
			case 'VBNEWROOMTHREE':
				$result = __('Room Characteristics', 'vikbooking');
				break;
			case 'VBNEWROOMFOUR':
				$result = __('Room Options', 'vikbooking');
				break;
			case 'VBNEWROOMFIVE':
				$result = __('Room Name', 'vikbooking');
				break;
			case 'VBNEWROOMSIX':
				$result = __('Room Main Image', 'vikbooking');
				break;
			case 'VBNEWROOMSEVEN':
				$result = __('Room Description', 'vikbooking');
				break;
			case 'VBNEWROOMEIGHT':
				$result = __('Room Published/Available', 'vikbooking');
				break;
			case 'VBNEWROOMNINE':
				$result = __('Room Units', 'vikbooking');
				break;
			case 'VBNOTARFOUND':
				$result = __('No Rates found', 'vikbooking');
				break;
			case 'VBJSDELTAR':
				$result = __('Remove every selected Rate', 'vikbooking');
				break;
			case 'VBPVIEWTARONE':
				$result = __('Rate for nights', 'vikbooking');
				break;
			case 'VBPVIEWTARTWO':
				$result = __('Update Rates', 'vikbooking');
				break;
			case 'VBCONFIGONETHREE':
				$result = __('Check-out Time', 'vikbooking');
				break;
			case 'VBCONFIGONEFIVE':
				$result = __('Booking Enabled', 'vikbooking');
				break;
			case 'VBCONFIGONESIX':
				$result = __('Booking Disabled Message', 'vikbooking');
				break;
			case 'VBCONFIGONESEVEN':
				$result = __('Check-In Time', 'vikbooking');
				break;
			case 'VBCONFIGONEEIGHT':
				$result = __('Hours of Extended Gratuity Period', 'vikbooking');
				break;
			case 'VBCONFIGONENINE':
				$result = __('Checked-out room is available after', 'vikbooking');
				break;
			case 'VBCONFIGONEELEVEN':
				$result = __('Check-in/out Date Format', 'vikbooking');
				break;
			case 'VBCONFIGONETWELVE':
				$result = __('DD/MM/YYYY', 'vikbooking');
				break;
			case 'VBCONFIGONETENTHREE':
				$result = __('YYYY/MM/DD', 'vikbooking');
				break;
			case 'VBCONFIGONETENFOUR':
				$result = __('Choose Rooms Category', 'vikbooking');
				break;
			case 'VBCONFIGONETENFIVE':
				$result = __('Token Form Booking Submit', 'vikbooking');
				break;
			case 'VBCONFIGONETENSIX':
				$result = __('Admin e-Mail', 'vikbooking');
				break;
			case 'VBCONFIGONETENSEVEN':
				$result = __('Minutes of Waiting for the Payment', 'vikbooking');
				break;
			case 'VBCONFIGONETENEIGHT':
				$result = __('hours', 'vikbooking');
				break;
			case 'VBCONFIGTWOONE':
				$result = __('Enable Paypal', 'vikbooking');
				break;
			case 'VBCONFIGTWOTWO':
				$result = __('Payments Account<br/><small>(for Gateways like Paypal)</small>', 'vikbooking');
				break;
			case 'VBCONFIGTWOTHREE':
				$result = __('Pay Entire Amount', 'vikbooking');
				break;
			case 'VBCONFIGTWOFOUR':
				$result = __('Leave a deposit of ', 'vikbooking');
				break;
			case 'VBCONFIGTWOFIVE':
				$result = __('Prices Tax Included', 'vikbooking');
				break;
			case 'VBCONFIGTWOSIX':
				$result = __('Payment Transaction Name', 'vikbooking');
				break;
			case 'VBCONFIGTHREEONE':
				$result = __('Company Name', 'vikbooking');
				break;
			case 'VBCONFIGTHREETWO':
				$result = __('Front Title Tag', 'vikbooking');
				break;
			case 'VBCONFIGTHREETHREE':
				$result = __('Front Title Tag Class', 'vikbooking');
				break;
			case 'VBCONFIGTHREEFOUR':
				$result = __('Search Button Text', 'vikbooking');
				break;
			case 'VBCONFIGTHREEFIVE':
				$result = __('Search Button Class', 'vikbooking');
				break;
			case 'VBCONFIGTHREESIX':
				$result = __('Show VikBooking Footer', 'vikbooking');
				break;
			case 'VBCONFIGTHREESEVEN':
				$result = __('Opening Page Text', 'vikbooking');
				break;
			case 'VBCONFIGTHREEEIGHT':
				$result = __('Closing Page Text', 'vikbooking');
				break;
			case 'VBCONFIGFOURONE':
				$result = __('Enable Removed Bookings Saving', 'vikbooking');
				break;
			case 'VBCONFIGFOURTWO':
				$result = __('Enable Search Statistics', 'vikbooking');
				break;
			case 'VBCONFIGFOURTHREE':
				$result = __('Send Searches Notifies to Admin', 'vikbooking');
				break;
			case 'VBCONFIGFOURFOUR':
				$result = __('Disclaimer', 'vikbooking');
				break;
			case 'VBCONFIGFOURLOGO':
				$result = __('Company Logo', 'vikbooking');
				break;
			case 'VBCONFIGFOURORDMAILFOOTER':
				$result = __('Footer Text Reservation eMail', 'vikbooking');
				break;
			case 'NESSUNAIVA':
				$result = __('No Tax Rates Found', 'vikbooking');
				break;
			case 'ASKFISCCODE':
				$result = __('Ask Italian Fiscal Code', 'vikbooking');
				break;
			case 'VBCONFIGTHREECURNAME':
				$result = __('Currency Name', 'vikbooking');
				break;
			case 'VBCONFIGTHREECURSYMB':
				$result = __('Currency Symbol', 'vikbooking');
				break;
			case 'VBCONFIGTHREECURCODEPP':
				$result = __('Transactions Currency Code', 'vikbooking');
				break;
			case 'VBPCHOOSEBUSYORDATE':
				$result = __('Reservation Date', 'vikbooking');
				break;
			case 'VBPCHOOSEBUSYCAVAIL':
				$result = __('Units Available', 'vikbooking');
				break;
			case 'VBYES':
				$result = __('Yes', 'vikbooking');
				break;
			case 'VBNO':
				$result = __('No', 'vikbooking');
				break;
			case 'VBMAINSEASONSTITLE':
				$result = __('Vik Booking - Special Prices', 'vikbooking');
				break;
			case 'VBMAINSEASONSDEL':
				$result = __('Delete', 'vikbooking');
				break;
			case 'VBMAINSEASONSEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINSEASONSNEW':
				$result = __('New Special Price', 'vikbooking');
				break;
			case 'VBMAINSEASONTITLENEW':
				$result = __('Vik Booking - New Special Price', 'vikbooking');
				break;
			case 'VBMAINSEASONTITLEEDIT':
				$result = __('Vik Booking - Edit Special Price', 'vikbooking');
				break;
			case 'VBSETORDCONFIRMED':
				$result = __('Set to Confirmed', 'vikbooking');
				break;
			case 'VBPAYMENTMETHOD':
				$result = __('Method of Payment', 'vikbooking');
				break;
			case 'VBUSEJUTILITY':
				$result = __('Send emails with JUtility', 'vikbooking');
				break;
			case 'VBCONFIGTHREENINE':
				$result = __('Show Partially Reserved Days', 'vikbooking');
				break;
			case 'VBCONFIGTHREETEN':
				$result = __('Number of Months to Show', 'vikbooking');
				break;
			case 'VBLIBONE':
				$result = __('Reservation Received on the', 'vikbooking');
				break;
			case 'VBLIBTWO':
				$result = __('Customer Info', 'vikbooking');
				break;
			case 'VBLIBTHREE':
				$result = __('Rooms Reserved', 'vikbooking');
				break;
			case 'VBLIBFOUR':
				$result = __('Check-in Date', 'vikbooking');
				break;
			case 'VBLIBFIVE':
				$result = __('Check-out Date', 'vikbooking');
				break;
			case 'VBLIBSIX':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBLIBSEVEN':
				$result = __('Booking Status', 'vikbooking');
				break;
			case 'VBLIBEIGHT':
				$result = __('Booking Date', 'vikbooking');
				break;
			case 'VBLIBNINE':
				$result = __('Personal Details', 'vikbooking');
				break;
			case 'VBLIBTEN':
				$result = __('Rooms Booked', 'vikbooking');
				break;
			case 'VBLIBELEVEN':
				$result = __('Check-in Date', 'vikbooking');
				break;
			case 'VBLIBTWELVE':
				$result = __('Check-out Date', 'vikbooking');
				break;
			case 'VBLIBTENTHREE':
				$result = __('To see your booking details, visit the following page', 'vikbooking');
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
			case 'VBNOSEASONS':
				$result = __('No Special Prices found', 'vikbooking');
				break;
			case 'VBJSDELSEASONS':
				$result = __('Confirm', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSONE':
				$result = __('From', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSTWO':
				$result = __('To', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSTHREE':
				$result = __('Type', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSFOUR':
				$result = __('Value', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSFIVE':
				$result = __('Charge', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSSIX':
				$result = __('Discount', 'vikbooking');
				break;
			case 'VBNOROOMSFOUNDSEASONS':
				$result = __('No Rooms found', 'vikbooking');
				break;
			case 'VBNEWSEASONONE':
				$result = __('From', 'vikbooking');
				break;
			case 'VBNEWSEASONTWO':
				$result = __('To', 'vikbooking');
				break;
			case 'VBNEWSEASONTHREE':
				$result = __('Type', 'vikbooking');
				break;
			case 'VBNEWSEASONFOUR':
				$result = __('Value', 'vikbooking');
				break;
			case 'VBNEWSEASONFIVE':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBNEWSEASONSIX':
				$result = __('Charge', 'vikbooking');
				break;
			case 'VBNEWSEASONSEVEN':
				$result = __('Discount', 'vikbooking');
				break;
			case 'ERRINVDATESEASON':
				$result = __('Invalid Dates', 'vikbooking');
				break;
			case 'ERRINVDATEROOMSLOCSEASON':
				$result = __('Season with same dates and rooms already exists', 'vikbooking');
				break;
			case 'VBSEASONSAVED':
				$result = __('Special Price Saved', 'vikbooking');
				break;
			case 'VBSEASONUPDATED':
				$result = __('Updated', 'vikbooking');
				break;
			case 'VBSEASONANY':
				$result = __('Any', 'vikbooking');
				break;
			case 'VBNOPAYMENTS':
				$result = __('No Payment Methods found', 'vikbooking');
				break;
			case 'VBJSDELPAYMENTS':
				$result = __('Confirm', 'vikbooking');
				break;
			case 'VBPSHOWPAYMENTSONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPSHOWPAYMENTSTWO':
				$result = __('File', 'vikbooking');
				break;
			case 'VBPSHOWPAYMENTSTHREE':
				$result = __('Notes', 'vikbooking');
				break;
			case 'VBPSHOWPAYMENTSFOUR':
				$result = __('Cost', 'vikbooking');
				break;
			case 'VBPSHOWPAYMENTSFIVE':
				$result = __('Published', 'vikbooking');
				break;
			case 'VBNEWPAYMENTONE':
				$result = __('Payment Name', 'vikbooking');
				break;
			case 'VBNEWPAYMENTTWO':
				$result = __('File Class', 'vikbooking');
				break;
			case 'VBNEWPAYMENTTHREE':
				$result = __('Published', 'vikbooking');
				break;
			case 'VBNEWPAYMENTFOUR':
				$result = __('Cost', 'vikbooking');
				break;
			case 'VBNEWPAYMENTFIVE':
				$result = __('Notes', 'vikbooking');
				break;
			case 'VBNEWPAYMENTSIX':
				$result = __('Yes', 'vikbooking');
				break;
			case 'VBNEWPAYMENTSEVEN':
				$result = __('No', 'vikbooking');
				break;
			case 'VBLIBPAYNAME':
				$result = __('Payment Method', 'vikbooking');
				break;
			case 'VBNEWPAYMENTEIGHT':
				$result = __('Auto-Set Booking Confirmed', 'vikbooking');
				break;
			case 'VBNEWPAYMENTNINE':
				$result = __('Always Show Notes', 'vikbooking');
				break;
			case 'VBNOFIELDSFOUND':
				$result = __('No Custom Fields Found', 'vikbooking');
				break;
			case 'VBPVIEWCUSTOMFONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPVIEWCUSTOMFTWO':
				$result = __('Type', 'vikbooking');
				break;
			case 'VBPVIEWCUSTOMFTHREE':
				$result = __('Required', 'vikbooking');
				break;
			case 'VBPVIEWCUSTOMFFOUR':
				$result = __('Ordering', 'vikbooking');
				break;
			case 'VBPVIEWCUSTOMFFIVE':
				$result = __('e-Mail', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFONE':
				$result = __('Field Name', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFTWO':
				$result = __('Type', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFTHREE':
				$result = __('Text', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFFOUR':
				$result = __('Dropdown Select', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFFIVE':
				$result = __('Checkbox', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFSIX':
				$result = __('Required', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFSEVEN':
				$result = __('is e-Mail', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFEIGHT':
				$result = __('Popup Link', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFNINE':
				$result = __('Add Answer', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFTEN':
				$result = __('Textarea', 'vikbooking');
				break;
			case 'VBMAINCUSTOMFTITLE':
				$result = __('Vik Booking - Custom Fields', 'vikbooking');
				break;
			case 'VBMAINCUSTOMFDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBMAINCUSTOMFEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINCUSTOMFNEW':
				$result = __('New', 'vikbooking');
				break;
			case 'VBMENUONE':
				$result = __('Reservations', 'vikbooking');
				break;
			case 'VBMENUTWO':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBMENUTHREE':
				$result = __('Bookings', 'vikbooking');
				break;
			case 'VBMENUFOUR':
				$result = __('Global', 'vikbooking');
				break;
			case 'VBMENUFIVE':
				$result = __('Types of Price', 'vikbooking');
				break;
			case 'VBMENUSIX':
				$result = __('Categories', 'vikbooking');
				break;
			case 'VBMENUSEVEN':
				$result = __('All Bookings', 'vikbooking');
				break;
			case 'VBMENUEIGHT':
				$result = __('Search Statistics', 'vikbooking');
				break;
			case 'VBMENUNINE':
				$result = __('Tax Rates', 'vikbooking');
				break;
			case 'VBMENUTEN':
				$result = __('Rooms List', 'vikbooking');
				break;
			case 'VBMENUTWELVE':
				$result = __('Configuration', 'vikbooking');
				break;
			case 'VBMENUTENFOUR':
				$result = __('Characteristics', 'vikbooking');
				break;
			case 'VBMENUTENFIVE':
				$result = __('Options/Extras', 'vikbooking');
				break;
			case 'VBMENUTENSEVEN':
				$result = __('Special Prices', 'vikbooking');
				break;
			case 'VBMENUTENEIGHT':
				$result = __('Payment Methods', 'vikbooking');
				break;
			case 'VBMENUTENNINE':
				$result = __('Availability Overview', 'vikbooking');
				break;
			case 'VBMENUTENTEN':
				$result = __('Custom Fields', 'vikbooking');
				break;
			case 'ORDER_NAME':
				$result = __('Name', 'vikbooking');
				break;
			case 'ORDER_LNAME':
				$result = __('Last Name', 'vikbooking');
				break;
			case 'ORDER_EMAIL':
				$result = __('e-Mail', 'vikbooking');
				break;
			case 'ORDER_PHONE':
				$result = __('Phone', 'vikbooking');
				break;
			case 'ORDER_ADDRESS':
				$result = __('Address', 'vikbooking');
				break;
			case 'ORDER_ZIP':
				$result = __('Zip Code', 'vikbooking');
				break;
			case 'ORDER_CITY':
				$result = __('City', 'vikbooking');
				break;
			case 'ORDER_STATE':
				$result = __('Country', 'vikbooking');
				break;
			case 'ORDER_DBIRTH':
				$result = __('Date of Birth', 'vikbooking');
				break;
			case 'ORDER_FLIGHTNUM':
				$result = __('Flight Number', 'vikbooking');
				break;
			case 'ORDER_NOTES':
				$result = __('Notes', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_MENU':
				$result = __('VikBooking', 'vikbooking');
				break;
			case 'VBMOREIMAGES':
				$result = __('Extra Images', 'vikbooking');
				break;
			case 'VBADDIMAGES':
				$result = __('Add Upload Field', 'vikbooking');
				break;
			case 'VBRESIZEIMAGES':
				$result = __('Resize Images', 'vikbooking');
				break;
			case 'VBCONFIGREQUIRELOGIN':
				$result = __('Require Login', 'vikbooking');
				break;
			case 'VBSEASON':
				$result = __('Season', 'vikbooking');
				break;
			case 'VBWEEKDAYS':
				$result = __('Week Days', 'vikbooking');
				break;
			case 'VBSEASONDAYS':
				$result = __('Days of the Week', 'vikbooking');
				break;
			case 'VBSUNDAY':
				$result = __('Sunday', 'vikbooking');
				break;
			case 'VBMONDAY':
				$result = __('Monday', 'vikbooking');
				break;
			case 'VBTUESDAY':
				$result = __('Tuesday', 'vikbooking');
				break;
			case 'VBWEDNESDAY':
				$result = __('Wednesday', 'vikbooking');
				break;
			case 'VBTHURSDAY':
				$result = __('Thursday', 'vikbooking');
				break;
			case 'VBFRIDAY':
				$result = __('Friday', 'vikbooking');
				break;
			case 'VBSATURDAY':
				$result = __('Saturday', 'vikbooking');
				break;
			case 'VBSPRICESHELP':
				$result = __('Insert a starting and an ending date (Season) or select one or more days of the week (Week Days). Only one filter is required. Provide a Season and Week Days to combine the filters', 'vikbooking');
				break;
			case 'VBSPRICESHELPTITLE':
				$result = __('Seasons and Week Days', 'vikbooking');
				break;
			case 'VBSPNAME':
				$result = __('Special Price Name', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSPNAME':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPSHOWSEASONSWDAYS':
				$result = __('Week Days', 'vikbooking');
				break;
			case 'VBPLACELAT':
				$result = __('Latitude', 'vikbooking');
				break;
			case 'VBPLACELNG':
				$result = __('Longitude', 'vikbooking');
				break;
			case 'VBPLACEDESCR':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBDAILYFARES':
				$result = __('Add Rates per Night', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFSEPARATOR':
				$result = __('Separator', 'vikbooking');
				break;
			case 'VBSEPDRIVERD':
				$result = __('Billing Information', 'vikbooking');
				break;
			case 'VBCONFIGONEJQUERY':
				$result = __('Load jQuery Library', 'vikbooking');
				break;
			case 'VBCONFIGONECALENDAR':
				$result = __('Calendar Type', 'vikbooking');
				break;
			case 'VBORDERNUMBER':
				$result = __('Booking Number', 'vikbooking');
				break;
			case 'VBORDERDETAILS':
				$result = __('Booking Details', 'vikbooking');
				break;
			case 'VBNEWCATDESCR':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBPVIEWCATEGORIESDESCR':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBPAYMENTSHELPCONFIRMTXT':
				$result = __('Auto-Set Reservation to Confirmed', 'vikbooking');
				break;
			case 'VBPAYMENTSHELPCONFIRM':
				$result = __('If enabled, the status of the booking will be set to Confirmed upon the selection of this payment option, by skipping any transaction. This setting should always be disabled for payment gateways that require a validation of the credit card transaction. It could be turned on for payment methods like Cash, Pay on Arrival or Bank Transfer.', 'vikbooking');
				break;
			case 'VBNEWPAYMENTCHARGEORDISC':
				$result = __('Charge/Discount', 'vikbooking');
				break;
			case 'VBNEWPAYMENTCHARGEPLUS':
				$result = __('Charge +', 'vikbooking');
				break;
			case 'VBNEWPAYMENTDISCMINUS':
				$result = __('Discount -', 'vikbooking');
				break;
			case 'VBPSHOWPAYMENTSCHARGEORDISC':
				$result = __('Charge/Discount', 'vikbooking');
				break;
			case 'VBPLACEOPENTIME':
				$result = __('Opening Time', 'vikbooking');
				break;
			case 'VBPLACEOPENTIMETXT':
				$result = __('The opening time for Check-in and-or Check-out. If empty, the global opening time of the configuration will be applied', 'vikbooking');
				break;
			case 'VBPLACEOPENTIMEFROM':
				$result = __('From', 'vikbooking');
				break;
			case 'VBPLACEOPENTIMETO':
				$result = __('To', 'vikbooking');
				break;
			case 'VBSPONLYPICKINCL':
				$result = __('Check-in Date must be after the beginning of the Season', 'vikbooking');
				break;
			case 'VBSELVEHICLE':
				$result = __('Select Room', 'vikbooking');
				break;
			case 'VBNEWOPTFORCESEL':
				$result = __('Always Selected', 'vikbooking');
				break;
			case 'VBNEWOPTFORCEVALT':
				$result = __('Quantity', 'vikbooking');
				break;
			case 'VBNEWOPTFORCEVALTPDAY':
				$result = __('per Night of Journey', 'vikbooking');
				break;
			case 'VBCONFIGONECOUPONS':
				$result = __('Enable Coupons', 'vikbooking');
				break;
			case 'VBNOCOUPONSFOUND':
				$result = __('No coupon found', 'vikbooking');
				break;
			case 'VBPVIEWCOUPONSONE':
				$result = __('Code', 'vikbooking');
				break;
			case 'VBPVIEWCOUPONSTWO':
				$result = __('Type', 'vikbooking');
				break;
			case 'VBPVIEWCOUPONSTHREE':
				$result = __('Valid Dates', 'vikbooking');
				break;
			case 'VBPVIEWCOUPONSFOUR':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBPVIEWCOUPONSFIVE':
				$result = __('Min. Booking Total', 'vikbooking');
				break;
			case 'VBCOUPONTYPEPERMANENT':
				$result = __('Permanent', 'vikbooking');
				break;
			case 'VBCOUPONTYPEGIFT':
				$result = __('Gift', 'vikbooking');
				break;
			case 'VBCOUPONALWAYSVALID':
				$result = __('Always Valid', 'vikbooking');
				break;
			case 'VBCOUPONALLVEHICLES':
				$result = __('All Rooms', 'vikbooking');
				break;
			case 'VBNEWCOUPONONE':
				$result = __('Coupon Code', 'vikbooking');
				break;
			case 'VBNEWCOUPONTWO':
				$result = __('Coupon Type', 'vikbooking');
				break;
			case 'VBNEWCOUPONTHREE':
				$result = __('Percent or Total', 'vikbooking');
				break;
			case 'VBNEWCOUPONFOUR':
				$result = __('Value', 'vikbooking');
				break;
			case 'VBNEWCOUPONFIVE':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBNEWCOUPONSIX':
				$result = __('Validity Dates', 'vikbooking');
				break;
			case 'VBNEWCOUPONSEVEN':
				$result = __('Min. Booking Total', 'vikbooking');
				break;
			case 'VBNEWCOUPONEIGHT':
				$result = __('All', 'vikbooking');
				break;
			case 'VBNEWCOUPONNINE':
				$result = __('If no dates specified, the coupon will be always valid', 'vikbooking');
				break;
			case 'VBCOUPONEXISTS':
				$result = __('Error, the coupon code already exists', 'vikbooking');
				break;
			case 'VBCOUPONSAVEOK':
				$result = __('Coupon Successfully Saved', 'vikbooking');
				break;
			case 'VBMENUFARES':
				$result = __('Pricing', 'vikbooking');
				break;
			case 'VBMENUDASHBOARD':
				$result = __('Dashboard', 'vikbooking');
				break;
			case 'VBMENUPRICESTABLE':
				$result = __('Rates Table', 'vikbooking');
				break;
			case 'VBMENUQUICKRES':
				$result = __('Calendar', 'vikbooking');
				break;
			case 'VBMENUCOUPONS':
				$result = __('Coupons', 'vikbooking');
				break;
			case 'VBMAINCOUPONTITLE':
				$result = __('Vik Booking - Coupons', 'vikbooking');
				break;
			case 'VBMAINCOUPONNEW':
				$result = __('New', 'vikbooking');
				break;
			case 'VBMAINCOUPONEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINCOUPONDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBMAINDASHBOARDTITLE':
				$result = __('Vik Booking - Dashboard', 'vikbooking');
				break;
			case 'VBDASHUPCRES':
				$result = __('Upcoming Reservations', 'vikbooking');
				break;
			case 'VBDASHALLPLACES':
				$result = __('Any', 'vikbooking');
				break;
			case 'VBDASHUPRESONE':
				$result = __('ID', 'vikbooking');
				break;
			case 'VBDASHUPRESTWO':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBDASHUPRESTHREE':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBDASHUPRESFOUR':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBDASHUPRESFIVE':
				$result = __('Status', 'vikbooking');
				break;
			case 'VBDASHUPRESSIX':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBDASHSTATS':
				$result = __('Configuration Status', 'vikbooking');
				break;
			case 'VBDASHNOPRICES':
				$result = __('Types of Price', 'vikbooking');
				break;
			case 'VBDASHNOCATEGORIES':
				$result = __('Categories', 'vikbooking');
				break;
			case 'VBDASHNOROOMS':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBDASHNODAILYFARES':
				$result = __('Costs/Pricing', 'vikbooking');
				break;
			case 'VBDASHTOTRESCONF':
				$result = __('Confirmed Reservations', 'vikbooking');
				break;
			case 'VBDASHTOTRESPEND':
				$result = __('Standby Reservations', 'vikbooking');
				break;
			case 'VBCOUPON':
				$result = __('Coupon', 'vikbooking');
				break;
			case 'VBCONFIGTHEME':
				$result = __('Theme', 'vikbooking');
				break;
			case 'VBNEWOPTPERPERSON':
				$result = __('Cost per Person', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSPERPERS':
				$result = __('Per Person', 'vikbooking');
				break;
			case 'VBNEWROOMADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBNEWROOMCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBNEWROOMMIN':
				$result = __('Min.', 'vikbooking');
				break;
			case 'VBNEWROOMMAX':
				$result = __('Max.', 'vikbooking');
				break;
			case 'VBPVIEWROOMADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBPVIEWROOMCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBADULTSDIFFNUM':
				$result = __('#%s Adult(s) Usage', 'vikbooking');
				break;
			case 'VBADULTSDIFFCHDISCONE':
				$result = __('Charge +', 'vikbooking');
				break;
			case 'VBADULTSDIFFCHDISCTWO':
				$result = __('Discount -', 'vikbooking');
				break;
			case 'VBNEWROOMADULTSDIFF':
				$result = __('Adults Charges/Discounts', 'vikbooking');
				break;
			case 'VBNEWROOMADULTSDIFFHELP':
				$result = __('With this function you can set charges or discounts based on the Adults Occupancy of the room. The standard room price(s) are meant for the maximum or minimum number of adults that this room allows. For example a double room for maximum 2 adults can be given also to a single adult, in this case you might want to set a discount or a charge for the single usage of the double room. The price(s) for 2 adults are the Prices per Night that this room has defined in the Rates Table (in case the Rates Table is meant for the maximum occupancy of the room).', 'vikbooking');
				break;
			case 'VBNEWROOMNOTCHANGENUMMESS':
				$result = __('You have changed the minimum and the maximum number of adults for this room, please save the changes and enter this page again for adding Charges or Discounts based on the Adults Occupancy.', 'vikbooking');
				break;
			case 'VBNEWROOMADULTSDIFFBEFSAVE':
				$result = __('After saving, if the maximum number of adults is greater than 1 and the minimum numb. of adults is less than the maximum, you will be able to set charges or discounts based on the Adults Occupancy of the room.', 'vikbooking');
				break;
			case 'VBUPDROOMADCHDISCSAVED':
				$result = __('Adults Charges/Discounts Successfully Saved', 'vikbooking');
				break;
			case 'VBCONFIGSHOWCHILDREN':
				$result = __('Show Number of Children', 'vikbooking');
				break;
			case 'VBERRNOFAREFOUND':
				$result = __('Error, rate not found', 'vikbooking');
				break;
			case 'VBNEWROOMSMALLDESC':
				$result = __('Short Description', 'vikbooking');
				break;
			case 'VBMAILROOMNUM':
				$result = __('Room #', 'vikbooking');
				break;
			case 'VBMAILADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBMAILCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBMAILADULT':
				$result = __('Adult', 'vikbooking');
				break;
			case 'VBMAILCHILD':
				$result = __('Child', 'vikbooking');
				break;
			case 'VBERRCONFORDERNOTAVROOM':
				$result = __('Error, the following rooms are no longer available:', 'vikbooking');
				break;
			case 'VBUNABLESETRESCONF':
				$result = __('The reservation cannot be set to confirmed', 'vikbooking');
				break;
			case 'VBPEDITBUSYERRNOFARES':
				$result = __('No Rates found for these rooms and for this number of nights of stay. Unable to edit the reservation.', 'vikbooking');
				break;
			case 'VBPEDITBUSYTOTPAID':
				$result = __('Total Paid', 'vikbooking');
				break;
			case 'VBQUICKADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBQUICKCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBNEWOPTFORCEVALPERCHILD':
				$result = __('per Child', 'vikbooking');
				break;
			case 'VBNEWOPTIFCHILDREN':
				$result = __('Only for Children', 'vikbooking');
				break;
			case 'VBNEWOPTMAXQUANTSEL':
				$result = __('Max Quantity Selectable', 'vikbooking');
				break;
			case 'VBNEWOPTFORCESUMMARY':
				$result = __('Show Only in Reservation Summary', 'vikbooking');
				break;
			case 'VBSPECIALPRICEVALHELP':
				$result = __('This value will be added to or deducted from the average cost of every night of stay affected by this Special Price', 'vikbooking');
				break;
			case 'VBNEWSEASONVALUEOVERRIDE':
				$result = __('Value Overrides', 'vikbooking');
				break;
			case 'VBNEWSEASONNIGHTSOVR':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBNEWSEASONVALUESOVR':
				$result = __('Value', 'vikbooking');
				break;
			case 'VBNEWSEASONVALUEOVERRIDEHELP':
				$result = __('The default absolute or percentage value can be different depending on the nights of stay. For example you can override the default value of the Special Price for 7 Nights of stay and set it to a lower charge or to a higher decrease. Do not override the default value for always applying the same charge or decrease regardless the length of stay in the days affected by this Special Price.', 'vikbooking');
				break;
			case 'VBNEWSEASONADDOVERRIDE':
				$result = __('Add Value Override', 'vikbooking');
				break;
			case 'VBMENURESTRICTIONS':
				$result = __('Restrictions', 'vikbooking');
				break;
			case 'VBNORESTRICTIONSFOUND':
				$result = __('No Restrictions found', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSONE':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSTWO':
				$result = __('Month', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSTHREE':
				$result = __('Arrival Week Day', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSFOUR':
				$result = __('Min Num of Nights', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSFIVE':
				$result = __('Max Num of Nights', 'vikbooking');
				break;
			case 'VBRESTRICTIONSHELPTITLE':
				$result = __('Restrictions', 'vikbooking');
				break;
			case 'VBRESTRICTIONSSHELP':
				$result = __('With the restrictions you can limit the minimum length of stay for a specific month of the Year or for a certain range of dates and optionally force the arrival Day of the Week. For example you can create a restriction for your apartment in August, forcing the arrival day to Saturday and the minimum length of stay to 7 nights, 14 nights etc.. The minimum number of nights will be set to 1 in case it is left empty.', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONONE':
				$result = __('Month', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONWDAY':
				$result = __('Force Arrival Week Day', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONNAME':
				$result = __('Restriction Name', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONMINLOS':
				$result = __('Min Num of Nights', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONMULTIPLYMINLOS':
				$result = __('Multiply Min Num of Nights', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONMULTIPLYMINLOSHELP':
				$result = __('If this setting is enabled the minimum number of nights will be multiplied every time this is passed. For example if you want to force the Arrival day to Saturday and the Departure day must still be on Saturday, you have to set the Minimum Number of Nights to 7 and if this setting is enabled, 8, 9, 10, 11, 12 and 13 nights of stay will not be allowed but only 14, 21, 28 etc. nights will be allowed. This is useful if you want to give your rooms only for weeks. The Maximum number of Nights is automatically calculated from the Rates Table of each room, infact, if a room does not have a rate for 28 nights, this room will not show up in the results so it will not be available. In case you want the calendar to force the Maximum Number of Nights for this month, set a number of MaxLOS below.', 'vikbooking');
				break;
			case 'VBUSELESSRESTRICTION':
				$result = __('Error, the restriction would be useless without an Arrival Week Day, without the CTA or CTD and the Minimum Num of Nights as 1 which is the default MinLOS', 'vikbooking');
				break;
			case 'VBRESTRICTIONSAVED':
				$result = __('Restriction Saved Successfully', 'vikbooking');
				break;
			case 'VBMAINRESTRICTIONSTITLE':
				$result = __('Vik Booking - Restrictions', 'vikbooking');
				break;
			case 'VBMAINRESTRICTIONDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBMAINRESTRICTIONEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINRESTRICTIONNEW':
				$result = __('New Restriction', 'vikbooking');
				break;
			case 'VBRESTRICTIONMONTHEXISTS':
				$result = __('Error, a restriction for the selected month already exists.', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONMAXLOS':
				$result = __('Max Num of Nights', 'vikbooking');
				break;
			case 'VBCONFIRMNUMB':
				$result = __('Confirmation Number', 'vikbooking');
				break;
			case 'VBPVIEWORDERSSEARCHSUBM':
				$result = __('Filter Bookings', 'vikbooking');
				break;
			case 'VBADULTSDIFFONPERNIGHT':
				$result = __('per Night', 'vikbooking');
				break;
			case 'VBADULTSDIFFONTOTAL':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBPVIEWOPTIONALSORDERING':
				$result = __('Ordering', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFDATETYPE':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBRESENDORDEMAIL':
				$result = __('Re-send Email', 'vikbooking');
				break;
			case 'VBORDEREMAILRESENT':
				$result = __('Booking Email sent to %s', 'vikbooking');
				break;
			case 'VBCUSTEMAIL':
				$result = __('Customer e-Mail', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPARAMS':
				$result = __('Search Parameters', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPNUMROOM':
				$result = __('Number of Rooms', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPNUMADULTS':
				$result = __('Number of Adults', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPNUMCHILDREN':
				$result = __('Number of Children', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPFROM':
				$result = __('From', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPTO':
				$result = __('To', 'vikbooking');
				break;
			case 'VBMAXTOTPEOPLE':
				$result = __('Total People', 'vikbooking');
				break;
			case 'VBMAXTOTPEOPLEDESC':
				$result = __('Max. Numb. of Adults + Children', 'vikbooking');
				break;
			case 'VBCONFIGONEMDY':
				$result = __('MM/DD/YYYY', 'vikbooking');
				break;
			case 'VBCLOSEROOM':
				$result = __('Close room in these dates', 'vikbooking');
				break;
			case 'VBDBTEXTROOMCLOSED':
				$result = __('Room Closed', 'vikbooking');
				break;
			case 'VBSUBMCLOSEROOM':
				$result = __('Close Room', 'vikbooking');
				break;
			case 'VBCANCELLED':
				$result = __('Cancelled', 'vikbooking');
				break;
			case 'VBCSVEXPORT':
				$result = __('CSV Report Export', 'vikbooking');
				break;
			case 'VBCSVEXPFILTDATES':
				$result = __('Check-in Date Range', 'vikbooking');
				break;
			case 'VBCSVEXPFILTBSTATUS':
				$result = __('Booking Status', 'vikbooking');
				break;
			case 'VBCSVSTATUSCONFIRMED':
				$result = __('Confirmed', 'vikbooking');
				break;
			case 'VBCSVSTATUSSTANDBY':
				$result = __('Stand-By', 'vikbooking');
				break;
			case 'VBCSVSTATUSCANCELLED':
				$result = __('Cancelled', 'vikbooking');
				break;
			case 'VBCSVGENERATE':
				$result = __('Generate and Download CSV', 'vikbooking');
				break;
			case 'VBCSVEXPNORECORDS':
				$result = __('No records to export', 'vikbooking');
				break;
			case 'VBCSVCHECKIN':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBCSVCHECKOUT':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBCSVNIGHTS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBCSVROOM':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBCSVPEOPLE':
				$result = __('People', 'vikbooking');
				break;
			case 'VBCSVCUSTINFO':
				$result = __('Customer Information', 'vikbooking');
				break;
			case 'VBCSVCUSTMAIL':
				$result = __('Customer eMail', 'vikbooking');
				break;
			case 'VBCSVPAYMENTMETHOD':
				$result = __('Payment Method', 'vikbooking');
				break;
			case 'VBCSVORDIDCONFNUMB':
				$result = __('ID - Confirmation Number', 'vikbooking');
				break;
			case 'VBCSVTOTAL':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBCSVTOTPAID':
				$result = __('Total Paid', 'vikbooking');
				break;
			case 'VBCSVCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBNEWOPTIFAGEINTERVAL':
				$result = __('In case of Charges per Children Age, add Age Intervals and Costs (fixed or percentage values of adults tariffs)', 'vikbooking');
				break;
			case 'VBADDAGEINTERVAL':
				$result = __('Add Age Interval Charge', 'vikbooking');
				break;
			case 'VBNEWAGEINTERVALFROM':
				$result = __('From Age', 'vikbooking');
				break;
			case 'VBNEWAGEINTERVALTO':
				$result = __('To Age', 'vikbooking');
				break;
			case 'VBNEWAGEINTERVALCOST':
				$result = __('Charge', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHDEFNIGHTS':
				$result = __('Minimum Number of Nights', 'vikbooking');
				break;
			case 'VIKLOADING':
				$result = __('Loading...', 'vikbooking');
				break;
			case 'VBPAYMENTPARAMETERS':
				$result = __('Parameters', 'vikbooking');
				break;
			case 'VBNEWSEASONVALUESOVREMORE':
				$result = __('and more', 'vikbooking');
				break;
			case 'VBNEWSEASONROUNDCOST':
				$result = __('Round to Integer', 'vikbooking');
				break;
			case 'VBNEWSEASONROUNDCOSTNO':
				$result = __('- disabled -', 'vikbooking');
				break;
			case 'VBNEWSEASONROUNDCOSTUP':
				$result = __('Round Up', 'vikbooking');
				break;
			case 'VBNEWSEASONROUNDCOSTDOWN':
				$result = __('Round Down', 'vikbooking');
				break;
			case 'VBCONFIGNUMDECIMALS':
				$result = __('Number of Decimals', 'vikbooking');
				break;
			case 'VBCONFIGNUMDECSEPARATOR':
				$result = __('Decimal Separator', 'vikbooking');
				break;
			case 'VBCONFIGNUMTHOSEPARATOR':
				$result = __('Thousand Separator', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONOR':
				$result = __('or', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONDATERANGE':
				$result = __('Dates Range', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONDFROMRANGE':
				$result = __('From Date', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONDTORANGE':
				$result = __('To Date', 'vikbooking');
				break;
			case 'VBRESTRICTIONERRDRANGE':
				$result = __('Error: restrictions must have a month or a dates range, from and to.', 'vikbooking');
				break;
			case 'VBRESTRICTIONSDRANGE':
				$result = __('Dates Range', 'vikbooking');
				break;
			case 'VBMAINNEWRESTRICTIONTITLE':
				$result = __('Vik Booking - New Restriction', 'vikbooking');
				break;
			case 'VBMAINEDITRESTRICTIONTITLE':
				$result = __('Vik Booking - Edit Restriction', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONALLCOMBO':
				$result = __('Forced Combinations:', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONALLCOMBOHELP':
				$result = __('if none selected, any check-out week day in accordance with the max and min number of nights will be accepted', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONALLROOMS':
				$result = __('Apply to all rooms', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONROOMSAFF':
				$result = __('Rooms affected by this restriction', 'vikbooking');
				break;
			case 'VBRESTRLISTROOMS':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBRESTRALLROOMS':
				$result = __('ALL', 'vikbooking');
				break;
			case 'VBPAYMENTLOGTOGGLE':
				$result = __('Payments Log', 'vikbooking');
				break;
			case 'VBMINTOTPEOPLE':
				$result = __('Min. Total People', 'vikbooking');
				break;
			case 'VBMINTOTPEOPLEDESC':
				$result = __('Min. Numb. of Adults + Children', 'vikbooking');
				break;
			case 'VBPVIEWROOMTOTPEOPLE':
				$result = __('Total People', 'vikbooking');
				break;
			case 'VBADMINNOTESTOGGLE':
				$result = __('Administrator Notes', 'vikbooking');
				break;
			case 'VBADMINNOTESUPD':
				$result = __('Update', 'vikbooking');
				break;
			case 'VBCONFIGMINDAYSADVANCE':
				$result = __('Days in Advance for bookings', 'vikbooking');
				break;
			case 'VBNEWROOMPARAMS':
				$result = __('Room Parameters', 'vikbooking');
				break;
			case 'VBPARAMLASTAVAIL':
				$result = __('Show how many units are still available when less than', 'vikbooking');
				break;
			case 'VBPARAMLASTAVAILHELP':
				$result = __('if greater than zero, the rooms displayed in the search results will say something like "Last 2 available".', 'vikbooking');
				break;
			case 'VBPARAMCUSTPRICE':
				$result = __('Custom Starting From Price', 'vikbooking');
				break;
			case 'VBPARAMCUSTPRICEHELP':
				$result = __('the Views Rooms List and Room Details will display this price. If empty, the price displayed will be taken from the page Rates Table.', 'vikbooking');
				break;
			case 'VBPARAMCUSTPRICETEXT':
				$result = __('Custom Price Label', 'vikbooking');
				break;
			case 'VBPARAMCUSTPRICETEXTHELP':
				$result = __('this text can be something like Per Night, Per Weekend, Per Week etc..if empty the system will display Per Night.', 'vikbooking');
				break;
			case 'VBCALBOOKINGSTATUS':
				$result = __('Booking Status', 'vikbooking');
				break;
			case 'VBCALBOOKINGPAYMENT':
				$result = __('Method of payment', 'vikbooking');
				break;
			case 'VBPAYMUNDEFINED':
				$result = __('::Not Relevant::', 'vikbooking');
				break;
			case 'VBQUICKRESWARNSTANDBY':
				$result = __('Booking Status: Waiting for the payment. Choose one type of price and eventually some of the Options. Then click on Save to complete the Standby - Quick Reservation.', 'vikbooking');
				break;
			case 'VBCHANGEPAYLABEL':
				$result = __('::Change method of payment::', 'vikbooking');
				break;
			case 'VBCHANGEPAYCONFIRM':
				$result = __('Change method of payment to ', 'vikbooking');
				break;
			case 'VBAMOUNTPAID':
				$result = __('Amount Paid', 'vikbooking');
				break;
			case 'VBAPPLYDISCOUNT':
				$result = __('Apply Discount', 'vikbooking');
				break;
			case 'VBAPPLYDISCOUNTSAVE':
				$result = __('Save', 'vikbooking');
				break;
			case 'VBADMINDISCOUNT':
				$result = __('Discount', 'vikbooking');
				break;
			case 'VBICSEXPORT':
				$result = __('ICS Report Export', 'vikbooking');
				break;
			case 'VBICSGENERATE':
				$result = __('Generate and Download ICS File', 'vikbooking');
				break;
			case 'VBICSEXPNORECORDS':
				$result = __('No records to export', 'vikbooking');
				break;
			case 'VBICSEXPSUMMARY':
				$result = __('Check-in @ %s', 'vikbooking');
				break;
			case 'VBICSEXPDESCRIPTION':
				$result = __('Booking ID: %sPeople: %sNights: %sReservation Total: %sCustomer Details:%s', 'vikbooking');
				break;
			case 'VBPARAMPRICECALENDAR':
				$result = __('Availability Calendars with Prices', 'vikbooking');
				break;
			case 'VBPARAMPRICECALENDARDISABLED':
				$result = __('Disabled', 'vikbooking');
				break;
			case 'VBPARAMPRICECALENDARENABLED':
				$result = __('Enabled', 'vikbooking');
				break;
			case 'VBPARAMPRICECALENDARHELP':
				$result = __('if enabled, the View Room Details will display the cost for each day in the availability calendars', 'vikbooking');
				break;
			case 'VBPARAMDEFCALCOST':
				$result = __('Default Cost per Night', 'vikbooking');
				break;
			case 'VBPARAMDEFCALCOSTHELP':
				$result = __('This price will be used as default cost per night. The Special Prices will then be applied to this cost', 'vikbooking');
				break;
			case 'VBVIEWORDFRONT':
				$result = __('View in front site', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLE':
				$result = __('Show # Adults/Children', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLENO':
				$result = __('Disabled', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLEADU':
				$result = __('Min-Max # of Adults', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLECHI':
				$result = __('Min-Max # of Children', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLEADUTOT':
				$result = __('Min-Max # of Adults and Total People', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLECHITOT':
				$result = __('Min-Max # of Children and Total People', 'vikbooking');
				break;
			case 'VBPARAMSHOWPEOPLEALLTOT':
				$result = __('Min-Max # of Adults, Children and Total People', 'vikbooking');
				break;
			case 'VBCONFIGMULTIPAY':
				$result = __('Allow Multiple Payments for one Reservation', 'vikbooking');
				break;
			case 'VBCONFIGFLUSHSESSION':
				$result = __('Renew Session', 'vikbooking');
				break;
			case 'VBCONFIGFLUSHSESSIONCONF':
				$result = __('The PHP Session will be renewed and the new settings will be applied but any logged in user will be logged out. Proceed?', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFCOUNTRY':
				$result = __('Country', 'vikbooking');
				break;
			case 'VBPVIEWORDERCHANNEL':
				$result = __('From', 'vikbooking');
				break;
			case 'VBORDFROMSITE':
				$result = __('Website', 'vikbooking');
				break;
			case 'VBNEWOPTISCITYTAX':
				$result = __('It\'s a City Tax', 'vikbooking');
				break;
			case 'VBNEWOPTISFEE':
				$result = __('It\'s a Fee', 'vikbooking');
				break;
			case 'VBOPTHELPCITYTAXFEE':
				$result = __('These settings are needed by the system to know if this Option is a City Tax like local/tourist taxes, or if it\'s a Fee like a booking fee, a mandatory resort fee, or a gratuity service fee that has to be paid at time of booking. Leave these settings disabled if the Option is not a tax or a fee, but just an extra service.', 'vikbooking');
				break;
			case 'VBTOTALVAT':
				$result = __('Taxes (VAT)', 'vikbooking');
				break;
			case 'VBTOTALCITYTAX':
				$result = __('City Taxes', 'vikbooking');
				break;
			case 'VBTOTALFEES':
				$result = __('Fees', 'vikbooking');
				break;
			case 'VBNEWPRICEBREAKFAST':
				$result = __('Breakfast Included', 'vikbooking');
				break;
			case 'VBNEWPRICEFREECANC':
				$result = __('Refundable', 'vikbooking');
				break;
			case 'VBNEWPRICEFREECANCDLINE':
				$result = __('Up to n days before arrival', 'vikbooking');
				break;
			case 'VBNEWPRICEFREECANCDLINETIP':
				$result = __('Up to %d days before arrival', 'vikbooking');
				break;
			case 'VBCONFIGTAXSUMMARY':
				$result = __('Show Tax in Summary Only', 'vikbooking');
				break;
			case 'VBSPYEARTIED':
				$result = __('Tied to the Year', 'vikbooking');
				break;
			case 'VBTWOYEARS':
				$result = __('2 Years', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPSMARTSEARCH':
				$result = __('Availability for Multiple-rooms search', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPSMARTSEARCHDYN':
				$result = __('Dynamic', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPSMARTSEARCHAUTO':
				$result = __('Automatic', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPMAXDATEFUT':
				$result = __('Maximum Date in the Future from today', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPMAXDATEDAYS':
				$result = __('Days', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPMAXDATEWEEKS':
				$result = __('Weeks', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPMAXDATEMONTHS':
				$result = __('Months', 'vikbooking');
				break;
			case 'VBCONFIGSEARCHPMAXDATEYEARS':
				$result = __('Years', 'vikbooking');
				break;
			case 'VBCONFIGFIRSTWDAY':
				$result = __('Calendars first day of the week', 'vikbooking');
				break;
			case 'VBROOMFILTER':
				$result = __('Filter by Room', 'vikbooking');
				break;
			case 'VBIMGCAPTION':
				$result = __('Image Caption', 'vikbooking');
				break;
			case 'VBIMGUPDATE':
				$result = __('Save Changes', 'vikbooking');
				break;
			case 'VBREMOVEIMG':
				$result = __('Remove Image', 'vikbooking');
				break;
			case 'VBORDERCHILDAGES':
				$result = __('(%s years old)', 'vikbooking');
				break;
			case 'VBPEDITBUSYTRAVELERINFO':
				$result = __('Traveler Details', 'vikbooking');
				break;
			case 'VBTRAVELERNAME':
				$result = __('First Name', 'vikbooking');
				break;
			case 'VBTRAVELERLNAME':
				$result = __('Last Name', 'vikbooking');
				break;
			case 'VBMENUCHANNELMANAGER':
				$result = __('Channel Manager', 'vikbooking');
				break;
			case 'VBBACKVCM':
				$result = __('Back to Channel Manager', 'vikbooking');
				break;
			case 'VBSWROOMOCC':
				$result = __('%s Adults', 'vikbooking');
				break;
			case 'VBSWITCHRWITH':
				$result = __('Switch Room', 'vikbooking');
				break;
			case 'VBSWITCHRERR':
				$result = __('Error: the room %s cannot be switched to the %s on these dates.', 'vikbooking');
				break;
			case 'VBSWITCHROK':
				$result = __('The room %s has been switched to the %s. Choose Rates and Options for all the Rooms then click the Save button again.', 'vikbooking');
				break;
			case 'VBDELCONFIRM':
				$result = __('Some records will be deleted. Proceed?', 'vikbooking');
				break;
			case 'VBSAVECLOSE':
				$result = __('Save &amp; Close', 'vikbooking');
				break;
			case 'VBSAVENEW':
				$result = __('Save &amp; New', 'vikbooking');
				break;
			case 'VBOADDTAXBKDWN':
				$result = __('Add Tax Breakdown', 'vikbooking');
				break;
			case 'VBOTAXNAMEBKDWN':
				$result = __('Tax Type/Name', 'vikbooking');
				break;
			case 'VBOTAXNAMEBKDWNEX':
				$result = __('i.e. Federal Taxes', 'vikbooking');
				break;
			case 'VBOTAXRATEBKDWN':
				$result = __('% Rate', 'vikbooking');
				break;
			case 'VBOTAXBKDWNCOUNT':
				$result = __('Tax Breakdown', 'vikbooking');
				break;
			case 'VBOTAXBKDWNERRNOMATCH':
				$result = __('The Tax Breakdown was saved successfully but the sum of the sub-taxes in not equal to the parent tax rate.', 'vikbooking');
				break;
			case 'VBOSELECTALL':
				$result = __('Select All', 'vikbooking');
				break;
			case 'VBOSPTYPESPRICE':
				$result = __('Types of Price', 'vikbooking');
				break;
			case 'VBOISPROMOTION':
				$result = __('Promotion', 'vikbooking');
				break;
			case 'VBOPROMOVALIDITY':
				$result = __('Promotion valid up to', 'vikbooking');
				break;
			case 'VBOPROMOVALIDITYDAYSADV':
				$result = __('days in advance from Start Date', 'vikbooking');
				break;
			case 'VBOPROMOTEXT':
				$result = __('Promotion Text', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDAR':
				$result = __('Show Seasons Calendar', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARDISABLED':
				$result = __('Disabled', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARENABLED':
				$result = __('Enabled', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARENABLEDALL':
				$result = __('Charges, Discounts and Promotions', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARENABLEDCHARGEDISC':
				$result = __('Charges and Discounts', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARENABLEDCHARGE':
				$result = __('Charges Only', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALNIGHTS':
				$result = __('Number of Nights', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALNIGHTSHELP':
				$result = __('The prices will be displayed in the various Seasons for this length of stay', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARPRICES':
				$result = __('Types of Price', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARPRICESANY':
				$result = __('Show All', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARPRICESLOW':
				$result = __('Show Lowest', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARLOS':
				$result = __('Length of Stay', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARLOSSHOW':
				$result = __('Show', 'vikbooking');
				break;
			case 'VBPARAMSEASONCALENDARLOSHIDE':
				$result = __('Hide', 'vikbooking');
				break;
			case 'VBPARAMROOMMULTIUNITS':
				$result = __('Book Multiple Units', 'vikbooking');
				break;
			case 'VBPARAMROOMMULTIUNITSDISABLE':
				$result = __('Disabled', 'vikbooking');
				break;
			case 'VBPARAMROOMMULTIUNITSENABLE':
				$result = __('Enabled', 'vikbooking');
				break;
			case 'VBPARAMROOMMULTIUNITSHELP':
				$result = __('if enabled, the details page of this room will let the users choose the number of units they would like to book.', 'vikbooking');
				break;
			case 'VBOSEASONAFFECTEDROOMS':
				$result = __('Rooms Affected', 'vikbooking');
				break;
			case 'VBMENURATESOVERVIEW':
				$result = __('Rates Overview', 'vikbooking');
				break;
			case 'VBRATESOVWROOM':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBOSEASONCALNUMNIGHT':
				$result = __('%d Night', 'vikbooking');
				break;
			case 'VBOSEASONCALNUMNIGHTS':
				$result = __('%d Nights', 'vikbooking');
				break;
			case 'VBOSEASONSCALOFFSEASONPRICES':
				$result = __('Off-Season Prices', 'vikbooking');
				break;
			case 'VBORESTRMINLOS':
				$result = __('Min. Nights', 'vikbooking');
				break;
			case 'VBORESTRMAXLOS':
				$result = __('Max. Nights', 'vikbooking');
				break;
			case 'VBORESTRARRIVWDAY':
				$result = __('Arrival Week Day', 'vikbooking');
				break;
			case 'VBORESTRARRIVWDAYS':
				$result = __('Arrival Week Days', 'vikbooking');
				break;
			case 'VBWEEKDAYZERO':
				$result = __('Sunday', 'vikbooking');
				break;
			case 'VBWEEKDAYONE':
				$result = __('Monday', 'vikbooking');
				break;
			case 'VBWEEKDAYTWO':
				$result = __('Tuesday', 'vikbooking');
				break;
			case 'VBWEEKDAYTHREE':
				$result = __('Wednesday', 'vikbooking');
				break;
			case 'VBWEEKDAYFOUR':
				$result = __('Thursday', 'vikbooking');
				break;
			case 'VBWEEKDAYFIVE':
				$result = __('Friday', 'vikbooking');
				break;
			case 'VBWEEKDAYSIX':
				$result = __('Saturday', 'vikbooking');
				break;
			case 'VBRATESOVWNUMNIGHTSACT':
				$result = __('Length of Stay', 'vikbooking');
				break;
			case 'VBRATESOVWAPPLYLOS':
				$result = __('Apply', 'vikbooking');
				break;
			case 'VBMAINRATESOVERVIEWTITLE':
				$result = __('Vik Booking - Rates Overview', 'vikbooking');
				break;
			case 'VBRATESOVWRATESCALCULATOR':
				$result = __('Rates Calculator', 'vikbooking');
				break;
			case 'VBRATESOVWRATESCALCNUMNIGHTS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBRATESOVWRATESCALCNUMADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBRATESOVWRATESCALCNUMCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBRATESOVWRATESCALCULATORCALC':
				$result = __('Calculate', 'vikbooking');
				break;
			case 'VBRATESOVWRATESCALCULATORCALCING':
				$result = __('Calculating...', 'vikbooking');
				break;
			case 'VBCALCRATESROOMNOTAVAILCOMBO':
				$result = __('The room is not available from %s to %s or has no rates for this combination of adults/children.', 'vikbooking');
				break;
			case 'VBCALCRATESNET':
				$result = __('Net', 'vikbooking');
				break;
			case 'VBCALCRATESTAX':
				$result = __('Taxes', 'vikbooking');
				break;
			case 'VBCALCRATESCITYTAX':
				$result = __('City Taxes', 'vikbooking');
				break;
			case 'VBCALCRATESFEES':
				$result = __('Fees', 'vikbooking');
				break;
			case 'VBCALCRATESTOT':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBCALCRATESSPAFFDAYS':
				$result = __('Nights modified by Special Prices:', 'vikbooking');
				break;
			case 'VBCALCRATESADUOCCUPANCY':
				$result = __('%d Adults Occupancy:', 'vikbooking');
				break;
			case 'VBAFFANYROOM':
				$result = __('Any Room', 'vikbooking');
				break;
			case 'VBSHORTMONTHONE':
				$result = __('Jan', 'vikbooking');
				break;
			case 'VBSHORTMONTHTWO':
				$result = __('Feb', 'vikbooking');
				break;
			case 'VBSHORTMONTHTHREE':
				$result = __('Mar', 'vikbooking');
				break;
			case 'VBSHORTMONTHFOUR':
				$result = __('Apr', 'vikbooking');
				break;
			case 'VBSHORTMONTHFIVE':
				$result = __('May', 'vikbooking');
				break;
			case 'VBSHORTMONTHSIX':
				$result = __('Jun', 'vikbooking');
				break;
			case 'VBSHORTMONTHSEVEN':
				$result = __('Jul', 'vikbooking');
				break;
			case 'VBSHORTMONTHEIGHT':
				$result = __('Aug', 'vikbooking');
				break;
			case 'VBSHORTMONTHNINE':
				$result = __('Sep', 'vikbooking');
				break;
			case 'VBSHORTMONTHTEN':
				$result = __('Oct', 'vikbooking');
				break;
			case 'VBSHORTMONTHELEVEN':
				$result = __('Nov', 'vikbooking');
				break;
			case 'VBSHORTMONTHTWELVE':
				$result = __('Dec', 'vikbooking');
				break;
			case 'VBMDAYFRIST':
				$result = __('st', 'vikbooking');
				break;
			case 'VBMDAYSECOND':
				$result = __('nd', 'vikbooking');
				break;
			case 'VBMDAYTHIRD':
				$result = __('rd', 'vikbooking');
				break;
			case 'VBMDAYNUMGEN':
				$result = __('th', 'vikbooking');
				break;
			case 'VBCONFIGROUTER':
				$result = __('SEF Router', 'vikbooking');
				break;
			case 'VBCHANNELFILTER':
				$result = __('Filter by Channel', 'vikbooking');
				break;
			case 'VBPARAMPAGETITLE':
				$result = __('Custom Page Title', 'vikbooking');
				break;
			case 'VBPARAMPAGETITLEBEFORECUR':
				$result = __('Add it Before the Current Page Title', 'vikbooking');
				break;
			case 'VBPARAMPAGETITLEAFTERCUR':
				$result = __('Add it After the Current Page Title', 'vikbooking');
				break;
			case 'VBPARAMPAGETITLEREPLACECUR':
				$result = __('Replace the Current Page Title', 'vikbooking');
				break;
			case 'VBPARAMKEYWORDSMETATAG':
				$result = __('Keywords Meta Tag', 'vikbooking');
				break;
			case 'VBPARAMDESCRIPTIONMETATAG':
				$result = __('Description Meta Tag', 'vikbooking');
				break;
			case 'VBROOMSEFALIAS':
				$result = __('SEF Alias', 'vikbooking');
				break;
			case 'VBCONFIGTODAYBOOKINGS':
				$result = __('Bookings for today at any time', 'vikbooking');
				break;
			case 'VBSEASONANYYEARS':
				$result = __('Valid any Year', 'vikbooking');
				break;
			case 'VBSEASONBASEDLOS':
				$result = __('Based on Length of Stay', 'vikbooking');
				break;
			case 'VBSEASONPERNIGHT':
				$result = __('per night', 'vikbooking');
				break;
			case 'VBOVERVIEWUBOOKEDFILT':
				$result = __('Show Units Booked', 'vikbooking');
				break;
			case 'VBOVERVIEWULEFTFILT':
				$result = __('Show Units Remaining', 'vikbooking');
				break;
			case 'VBOVERVIEWLEGEND':
				$result = __('Legend:', 'vikbooking');
				break;
			case 'VBOVERVIEWLEGRED':
				$result = __('No Availability', 'vikbooking');
				break;
			case 'VBOVERVIEWLEGYELLOW':
				$result = __('Partially Available', 'vikbooking');
				break;
			case 'VBOVERVIEWLEGGREEN':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBOCPARAMBOOKING':
				$result = __('Booking', 'vikbooking');
				break;
			case 'VBOCPARAMSYSTEM':
				$result = __('System', 'vikbooking');
				break;
			case 'VBOCPARAMCURRENCY':
				$result = __('Currency', 'vikbooking');
				break;
			case 'VBOCPARAMTAXPAY':
				$result = __('Tax and Payments', 'vikbooking');
				break;
			case 'VBOCPARAMLAYOUT':
				$result = __('Appearance and Texts', 'vikbooking');
				break;
			case 'VBOCPARAMCOMPANY':
				$result = __('Company', 'vikbooking');
				break;
			case 'VBCONFIGMULTILANG':
				$result = __('Enable Multi-Language', 'vikbooking');
				break;
			case 'VBMENUTRANSLATIONS':
				$result = __('Translations', 'vikbooking');
				break;
			case 'VBMAINTRANSLATIONSTITLE':
				$result = __('Vik Booking - Translations', 'vikbooking');
				break;
			case 'VBOGETTRANSLATIONS':
				$result = __('Load Translations', 'vikbooking');
				break;
			case 'VBTRANSLATIONERRONELANG':
				$result = __('There is only one content-language enabled for this Wordpress-site so translations cannot be created.', 'vikbooking');
				break;
			case 'VBTANSLATIONSCHANGESCONF':
				$result = __('Some changes were made to the translations. Proceed without Saving?', 'vikbooking');
				break;
			case 'VBTRANSLATIONSELTABLEMESS':
				$result = __('No Contents Selected for Translation', 'vikbooking');
				break;
			case 'VBTRANSLATIONDEFLANG':
				$result = __('Default Language', 'vikbooking');
				break;
			case 'VBTRANSLATIONERRINVTABLE':
				$result = __('Error: Invalid or Empty Table Set for Translation', 'vikbooking');
				break;
			case 'VBOTRANSLSAVEDOK':
				$result = __('Translations Saved!', 'vikbooking');
				break;
			case 'ORDER_SPREQUESTS':
				$result = __('Special Requests', 'vikbooking');
				break;
			case 'VBTRANSLATIONINISTATUS':
				$result = __('Status', 'vikbooking');
				break;
			case 'VBOINIMISSINGFILE':
				$result = __('Missing Translation File', 'vikbooking');
				break;
			case 'VBOINIDEFINITIONS':
				$result = __('Definitions', 'vikbooking');
				break;
			case 'VBOINIPATH':
				$result = __('Path', 'vikbooking');
				break;
			case 'VBORDERING':
				$result = __('Ordering', 'vikbooking');
				break;
			case 'VBTOTALREMAINING':
				$result = __('Remaining Balance', 'vikbooking');
				break;
			case 'VBSENDCANCORDEMAIL':
				$result = __('Send Cancellation eMail', 'vikbooking');
				break;
			case 'VBCANCORDEREMAILSENT':
				$result = __('Booking Cancellation Email sent to %s', 'vikbooking');
				break;
			case 'VBDASHTODAYCHECKIN':
				$result = __('Arriving Today', 'vikbooking');
				break;
			case 'VBDASHTODAYCHECKOUT':
				$result = __('Departing Today', 'vikbooking');
				break;
			case 'VBCUSTOMERNOMINATIVE':
				$result = __('Customer Name', 'vikbooking');
				break;
			case 'VBDASHWEEKGLOBAVAIL':
				$result = __('This Week\'s Bookings', 'vikbooking');
				break;
			case 'VBDASHNEXTREFRESH':
				$result = __('Next Refresh in', 'vikbooking');
				break;
			case 'VBTODAY':
				$result = __('Today', 'vikbooking');
				break;
			case 'VBISNOMINATIVE':
				$result = __('Nominative', 'vikbooking');
				break;
			case 'VBCSVOPTIONS':
				$result = __('Options', 'vikbooking');
				break;
			case 'VBLOADBOOTSTRAP':
				$result = __('Load Bootstrap CSS', 'vikbooking');
				break;
			case 'VBFILLCUSTFIELDS':
				$result = __('Assign Customer', 'vikbooking');
				break;
			case 'VBAPPLY':
				$result = __('Apply', 'vikbooking');
				break;
			case 'VBISPHONENUMBER':
				$result = __('Phone Number', 'vikbooking');
				break;
			case 'ORDER_TERMSCONDITIONS':
				$result = __('I agree to the terms and conditions', 'vikbooking');
				break;
			case 'VBCSVTOTTAXES':
				$result = __('Total Taxes', 'vikbooking');
				break;
			case 'VBDASHROOMSLOCKED':
				$result = __('Rooms Locked - Waiting for Confirmation', 'vikbooking');
				break;
			case 'VBDASHROOMNAME':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBDASHLOCKUNTIL':
				$result = __('Locked Until', 'vikbooking');
				break;
			case 'VBDASHBOOKINGID':
				$result = __('Booking ID', 'vikbooking');
				break;
			case 'VBDASHUNLOCK':
				$result = __('Unlock', 'vikbooking');
				break;
			case 'VBSPMAINSETTINGS':
				$result = __('Pricing Rule', 'vikbooking');
				break;
			case 'VBSPPROMOTIONLABEL':
				$result = __('Promotion', 'vikbooking');
				break;
			case 'VBPROMOFORCEMINLOS':
				$result = __('Force minimum length of stay', 'vikbooking');
				break;
			case 'VBMENURATEPLANS':
				$result = __('Rate Plans', 'vikbooking');
				break;
			case 'VBMENUCUSTOMERS':
				$result = __('Customers', 'vikbooking');
				break;
			case 'VBNOCUSTOMERS':
				$result = __('No Customers found', 'vikbooking');
				break;
			case 'VBCUSTOMERFIRSTNAME':
				$result = __('First Name', 'vikbooking');
				break;
			case 'VBCUSTOMERLASTNAME':
				$result = __('Last Name', 'vikbooking');
				break;
			case 'VBCUSTOMEREMAIL':
				$result = __('eMail', 'vikbooking');
				break;
			case 'VBCUSTOMERPHONE':
				$result = __('Phone', 'vikbooking');
				break;
			case 'VBCUSTOMERCOUNTRY':
				$result = __('Country', 'vikbooking');
				break;
			case 'VBCUSTOMERPIN':
				$result = __('PIN', 'vikbooking');
				break;
			case 'VBCUSTOMERGENERATEPIN':
				$result = __('Generate PIN', 'vikbooking');
				break;
			case 'VBMAINCUSTOMERSTITLE':
				$result = __('Vik Booking - Customers', 'vikbooking');
				break;
			case 'VBMAINCUSTOMERNEW':
				$result = __('New', 'vikbooking');
				break;
			case 'VBMAINCUSTOMEREDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINCUSTOMERDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBMAINMANAGECUSTOMERTITLE':
				$result = __('Vik Booking - Customer Details', 'vikbooking');
				break;
			case 'VBERRCUSTOMEREMAILEXISTS':
				$result = __('Customer with the same email address already exists', 'vikbooking');
				break;
			case 'VBCUSTOMERSAVED':
				$result = __('Customer Saved Successfully', 'vikbooking');
				break;
			case 'VBCONFIGENABLECUSTOMERPIN':
				$result = __('Enable Customers PIN Code', 'vikbooking');
				break;
			case 'VBCUSTOMERTOTBOOKINGS':
				$result = __('Total Bookings', 'vikbooking');
				break;
			case 'VBYOURPIN':
				$result = __('PIN Code', 'vikbooking');
				break;
			case 'VBUPDROOMOK':
				$result = __('Room Updated Successfully', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATEROOMS':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATEOPTIONS':
				$result = __('Options, Taxes, Fees', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATECATEGORIES':
				$result = __('Categories', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATESPECIALPRICES':
				$result = __('Special Prices', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATETYPESPRICE':
				$result = __('Types of Price', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATEPAYMENTS':
				$result = __('Payment Methods', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATECFIELDS':
				$result = __('Custom Fields', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATECHARACTERISTICS':
				$result = __('Characteristics', 'vikbooking');
				break;
			case 'VBINIEXPLCOM_VIKBOOKING_FRONT':
				$result = __('Component Front-End', 'vikbooking');
				break;
			case 'VBINIEXPLCOM_VIKBOOKING_ADMIN':
				$result = __('Component Back-End', 'vikbooking');
				break;
			case 'VBINIEXPLCOM_VIKBOOKING_ADMIN_SYS':
				$result = __('Component Back-End SYS', 'vikbooking');
				break;
			case 'VBINIEXPLMOD_VIKBOOKING_SEARCH':
				$result = __('Search Module', 'vikbooking');
				break;
			case 'VBINIEXPLMOD_VIKBOOKING_HORIZONTALSEARCH':
				$result = __('Horizontal Search Module', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATETEXTS':
				$result = __('Texts', 'vikbooking');
				break;
			case 'VBOXMLCONTENT':
				$result = __('Content', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATEPACKAGES':
				$result = __('Packages/Offers', 'vikbooking');
				break;
			case 'VBOXMLTRANSLATECRONJOBS':
				$result = __('Scheduled Cron Jobs', 'vikbooking');
				break;
			case 'VBCHANNELMANAGERRESULTOK':
				$result = __('The Channel Manager has successfully notified the Channels!', 'vikbooking');
				break;
			case 'VBCHANNELMANAGERRESULTKO':
				$result = __('The Channel Manager did not notify any channel either because none needed to be updated or an error occurred.', 'vikbooking');
				break;
			case 'VBCHANNELMANAGEROPEN':
				$result = __('Go to Channel Manager', 'vikbooking');
				break;
			case 'VBCHANNELMANAGERINVOKEASK':
				$result = __('Would you like to invoke the Channel Manager to notify the changes to the Channels?', 'vikbooking');
				break;
			case 'VBCHANNELMANAGERSENDRQ':
				$result = __('Send Update Request', 'vikbooking');
				break;
			case 'GETFULLCARDDETAILS':
				$result = __('Get full Credit Card Details', 'vikbooking');
				break;
			case 'VBOROOMOCCUPANCYPRNOTSUPP':
				$result = __('Occupancy Pricing not supported.', 'vikbooking');
				break;
			case 'VBSEASONOCCUPANCYPR':
				$result = __('Occupancy Pricing Overrides', 'vikbooking');
				break;
			case 'VBSENDEREMAIL':
				$result = __('Sender e-Mail', 'vikbooking');
				break;
			case 'VBOROOMLEGUNITOCC':
				$result = __('Units and Occupancy', 'vikbooking');
				break;
			case 'VBOROOMLEGPHOTODESC':
				$result = __('Photos and Descriptions', 'vikbooking');
				break;
			case 'VBOROOMLEGCARATCATOPT':
				$result = __('Categories, Characteristics and Options', 'vikbooking');
				break;
			case 'VBOSELORDRAGFILES':
				$result = __('Select or Drag & Drop Files', 'vikbooking');
				break;
			case 'VBOUPLOADFILEDONE':
				$result = __('Done', 'vikbooking');
				break;
			case 'VBOBULKUPLOAD':
				$result = __('Bulk Upload', 'vikbooking');
				break;
			case 'VBORMALLPHOTOS':
				$result = __('Remove all Photos', 'vikbooking');
				break;
			case 'VBOROOMSAVEOK':
				$result = __('Room created successfully!', 'vikbooking');
				break;
			case 'VBOGOTORATES':
				$result = __('Go to Rates Table page', 'vikbooking');
				break;
			case 'VBOBULKUPLOADAFTERSAVE':
				$result = __('Bulk Upload available after saving', 'vikbooking');
				break;
			case 'VBORPARAMREQINFO':
				$result = __('Enable Request Information', 'vikbooking');
				break;
			case 'VBOROOMUNITSDISTFEAT':
				$result = __('Units Distinctive Features', 'vikbooking');
				break;
			case 'VBOROOMUNITSDISTFEATTOGGLE':
				$result = __('Toggle Distinctive Features', 'vikbooking');
				break;
			case 'VBODEFAULTDISTFEATUREONE':
				$result = __('Room Number', 'vikbooking');
				break;
			case 'VBODEFAULTDISTFEATURETWO':
				$result = __('Room Code', 'vikbooking');
				break;
			case 'VBODISTFEATURETXT':
				$result = __('Feature', 'vikbooking');
				break;
			case 'VBODISTFEATUREVAL':
				$result = __('Value', 'vikbooking');
				break;
			case 'VBODISTFEATURERUNIT':
				$result = __('Room Unit #', 'vikbooking');
				break;
			case 'VBODISTFEATUREADD':
				$result = __('Add Feature', 'vikbooking');
				break;
			case 'VBOFEATASSIGNUNIT':
				$result = __('Assign Unit #', 'vikbooking');
				break;
			case 'VBOFEATASSIGNUNITEMPTY':
				$result = __('Not Specified', 'vikbooking');
				break;
			case 'VBOFEATUNITASSIGNED':
				$result = __('Room Unit Assigned', 'vikbooking');
				break;
			case 'VBDASHTODROCC':
				$result = __('Today\'s Room Occupancy', 'vikbooking');
				break;
			case 'VBCONFIGAUTODISTFEATURE':
				$result = __('Auto-Assign Room Units', 'vikbooking');
				break;
			case 'VBCONFIGAUTODISTFEATUREHELP':
				$result = __('The room types (with more than 1 unit) using the Units Distinctive Features, can automatically assign the first available room unit to the bookings. For example: new bookings can be automatically assigned to the room number #101, #102, #103 etc..', 'vikbooking');
				break;
			case 'VBOCONFIGEDITTMPLFILE':
				$result = __('Edit Template File', 'vikbooking');
				break;
			case 'VBOCONFIGEMAILTEMPLATE':
				$result = __('Customer Email', 'vikbooking');
				break;
			case 'VBOUPDTMPLFILEERR':
				$result = __('Error: empty or invalid Template File Path', 'vikbooking');
				break;
			case 'VBOUPDTMPLFILENOBYTES':
				$result = __('Error: 0 bytes written on file', 'vikbooking');
				break;
			case 'VBOUPDTMPLFILEOK':
				$result = __('Template File Successfully Updated', 'vikbooking');
				break;
			case 'VBOEDITTMPLFILE':
				$result = __('Edit Template File Source Code', 'vikbooking');
				break;
			case 'VBOTMPLFILENOTREAD':
				$result = __('Error reading the source code of the file', 'vikbooking');
				break;
			case 'VBOSAVETMPLFILE':
				$result = __('Save & Write Source Code', 'vikbooking');
				break;
			case 'VBMENUPACKAGES':
				$result = __('Packages &amp; Offers', 'vikbooking');
				break;
			case 'VBNOPACKAGES':
				$result = __('No Packages found', 'vikbooking');
				break;
			case 'VBPACKAGESNAME':
				$result = __('Package Name', 'vikbooking');
				break;
			case 'VBPACKAGESDROM':
				$result = __('From Date', 'vikbooking');
				break;
			case 'VBPACKAGESDTO':
				$result = __('To Date', 'vikbooking');
				break;
			case 'VBPACKAGESCOST':
				$result = __('Price', 'vikbooking');
				break;
			case 'VBPACKAGESROOMSCOUNT':
				$result = __('Rooms Affected', 'vikbooking');
				break;
			case 'VBMAINPACKAGESTITLE':
				$result = __('Vik Booking - Packages & Offers', 'vikbooking');
				break;
			case 'VBMAINPACKAGENEW':
				$result = __('New', 'vikbooking');
				break;
			case 'VBMAINPACKAGEEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINPACKAGEDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBNEWPKGNAME':
				$result = __('Package Name', 'vikbooking');
				break;
			case 'VBNEWPKGALIAS':
				$result = __('SEF Alias', 'vikbooking');
				break;
			case 'VBNEWPKGIMG':
				$result = __('Main Image', 'vikbooking');
				break;
			case 'VBNEWPKGDFROM':
				$result = __('Validity Start Date', 'vikbooking');
				break;
			case 'VBNEWPKGDTO':
				$result = __('Validity End Date', 'vikbooking');
				break;
			case 'VBNEWPKGEXCLDATES':
				$result = __('Excluded Dates', 'vikbooking');
				break;
			case 'VBNEWPKGMINLOS':
				$result = __('Min. Number of Nights', 'vikbooking');
				break;
			case 'VBNEWPKGMAXLOS':
				$result = __('Max. Number of Nights', 'vikbooking');
				break;
			case 'VBNEWPKGCOST':
				$result = __('Package Cost', 'vikbooking');
				break;
			case 'VBNEWPKGCOSTTYPE':
				$result = __('Cost Type', 'vikbooking');
				break;
			case 'VBNEWPKGCOSTTYPEPNIGHT':
				$result = __('Per Night', 'vikbooking');
				break;
			case 'VBNEWPKGCOSTTYPETOTAL':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBNEWPKGCOSTTYPEPPERSON':
				$result = __('Cost Per Person', 'vikbooking');
				break;
			case 'VBNEWPKGSHOWOPT':
				$result = __('Display Rooms Options', 'vikbooking');
				break;
			case 'VBNEWPKGSHOWOPTALL':
				$result = __('Display all the assigned Rooms Options', 'vikbooking');
				break;
			case 'VBNEWPKGSHOWOPTOBL':
				$result = __('Apply just the mandatory Options (i.e. City Taxes)', 'vikbooking');
				break;
			case 'VBNEWPKGHIDEOPT':
				$result = __('Hide all Rooms Options', 'vikbooking');
				break;
			case 'VBNEWPKGDESCR':
				$result = __('Package Description', 'vikbooking');
				break;
			case 'VBNEWPKGSHORTDESCR':
				$result = __('Package Short Description', 'vikbooking');
				break;
			case 'VBNEWPKGBENEFITS':
				$result = __('Benefits', 'vikbooking');
				break;
			case 'VBNEWPKGCONDS':
				$result = __('Conditions', 'vikbooking');
				break;
			case 'VBNEWPKGBENEFITSHELP':
				$result = __('i.e. Save xx% and get free xyz.', 'vikbooking');
				break;
			case 'VBNEWPKGROOMS':
				$result = __('Available Rooms', 'vikbooking');
				break;
			case 'VBOEXCLWEEKD':
				$result = __('Exclude Week Days', 'vikbooking');
				break;
			case 'VBOPKGSAVED':
				$result = __('Package Saved Successfully!', 'vikbooking');
				break;
			case 'VBOPKGUPDATED':
				$result = __('Package Updated Successfully!', 'vikbooking');
				break;
			case 'VBOROOMCUSTRATEPLAN':
				$result = __('Room Rate', 'vikbooking');
				break;
			case 'VBOROOMCUSTRATEPLANADD':
				$result = __('Set Custom Rate', 'vikbooking');
				break;
			case 'VBOROOMCUSTRATETAXHELP':
				$result = __('Custom Rates should always be inclusive of taxes', 'vikbooking');
				break;
			case 'VBORESRATESUPDATED':
				$result = __('Reservation and Rates Updated', 'vikbooking');
				break;
			case 'VBOSEARCHCUSTBY':
				$result = __('Search by PIN or Name', 'vikbooking');
				break;
			case 'VBOOPTASSTOXROOMS':
				$result = __('This option is assigned to %d rooms over %d.', 'vikbooking');
				break;
			case 'VBCONFIGCLOSINGDATES':
				$result = __('Closing Dates', 'vikbooking');
				break;
			case 'VBCONFIGCLOSINGDATEFROM':
				$result = __('From Date', 'vikbooking');
				break;
			case 'VBCONFIGCLOSINGDATETO':
				$result = __('To Date', 'vikbooking');
				break;
			case 'VBCONFIGCLOSINGDATEADD':
				$result = __('Add', 'vikbooking');
				break;
			case 'VBTOTALCOMMISSIONS':
				$result = __('Commissions', 'vikbooking');
				break;
			case 'VBMENUMANAGEMENT':
				$result = __('Management', 'vikbooking');
				break;
			case 'VBMENUSTATS':
				$result = __('Graphs &amp; Statistics', 'vikbooking');
				break;
			case 'VBMENUCRONS':
				$result = __('Scheduled Cron Jobs', 'vikbooking');
				break;
			case 'VBOIBECHANNEL':
				$result = __('Website/IBE', 'vikbooking');
				break;
			case 'VBNOBOOKINGSTATS':
				$result = __('No bookings found for these dates. Reports cannot be generated.', 'vikbooking');
				break;
			case 'VBOSTATSFOR':
				$result = __('%d Confirmed Bookings over %d days - coming from %d Channels', 'vikbooking');
				break;
			case 'VBSTATSOTACOMMISSIONS':
				$result = __('OTA Commissions', 'vikbooking');
				break;
			case 'VBOSTATSTOPCOUNTRIES':
				$result = __('Top Countries', 'vikbooking');
				break;
			case 'VBOSTATSTOTINCOME':
				$result = __('Total Gross Income', 'vikbooking');
				break;
			case 'VBOSTATSTOTINCOMELESSCMMS':
				$result = __('Total After Commissions', 'vikbooking');
				break;
			case 'VBOSTATSTOTINCOMELESSTAX':
				$result = __('Total Net Income', 'vikbooking');
				break;
			case 'VBOSTATSTOTINCOMELESSTAXHELP':
				$result = __('Total Net Income After Commissions, Taxes, City Taxes and Fees', 'vikbooking');
				break;
			case 'VBMAINSTATSTITLE':
				$result = __('Vik Booking - Graphs &amp; Statistics', 'vikbooking');
				break;
			case 'VBPANELFIVE':
				$result = __('SMS Gateway', 'vikbooking');
				break;
			case 'VBOCPARAMSMS':
				$result = __('SMS APIs', 'vikbooking');
				break;
			case 'VBCONFIGSMSCLASS':
				$result = __('SMS API File', 'vikbooking');
				break;
			case 'VBCONFIGSMSAUTOSEND':
				$result = __('Enable Auto-Sending', 'vikbooking');
				break;
			case 'VBCONFIGSMSSENDTO':
				$result = __('Send SMS To', 'vikbooking');
				break;
			case 'VBCONFIGSMSSENDTOADMIN':
				$result = __('Administrator', 'vikbooking');
				break;
			case 'VBCONFIGSMSSENDTOCUSTOMER':
				$result = __('Customer', 'vikbooking');
				break;
			case 'VBCONFIGSMSSADMINPHONE':
				$result = __('Administrator Phone Number', 'vikbooking');
				break;
			case 'VBCONFIGSMSPARAMETERS':
				$result = __('Gateway Parameters', 'vikbooking');
				break;
			case 'VBCONFIGSMSREMAINBAL':
				$result = __('Remaining Balance', 'vikbooking');
				break;
			case 'VBCONFIGSMSESTCREDIT':
				$result = __('Estimate Credit', 'vikbooking');
				break;
			case 'VBCONFIGSMSADMTPL':
				$result = __('Administrator SMS Template', 'vikbooking');
				break;
			case 'VBCONFIGSMSCUSTOTPL':
				$result = __('Customer SMS Template', 'vikbooking');
				break;
			case 'VBSENDSMSACTION':
				$result = __('Send Custom SMS', 'vikbooking');
				break;
			case 'VBSENDSMSCUSTCONT':
				$result = __('Message', 'vikbooking');
				break;
			case 'VBSENDSMSERRMISSDATA':
				$result = __('Empty Phone Number or Message', 'vikbooking');
				break;
			case 'VBSENDSMSERRMISSAPI':
				$result = __('No SMS APIs Configured', 'vikbooking');
				break;
			case 'VBSENDSMSOK':
				$result = __('SMS Successfully Sent!', 'vikbooking');
				break;
			case 'VBOSENDSMSERRMAILSUBJ':
				$result = __('Error Sending the SMS', 'vikbooking');
				break;
			case 'VBOSENDADMINSMSERRMAILTXT':
				$result = __('An error occurred while sending the SMS to the Administrator. Below is the provider response.', 'vikbooking');
				break;
			case 'VBOSENDCUSTOMERSMSERRMAILTXT':
				$result = __('An error occurred while sending the SMS to the Customer. Below is the provider response.', 'vikbooking');
				break;
			case 'VBMAKEORDERPAYABLE':
				$result = __('Make payable from front site', 'vikbooking');
				break;
			case 'VBCONFIGCRONKEY':
				$result = __('Cron Jobs Secret Key', 'vikbooking');
				break;
			case 'VBMAILYOURBOOKING':
				$result = __('Your Booking', 'vikbooking');
				break;
			case 'VBMAINCRONSTITLE':
				$result = __('Vik Booking - Scheduled Cron Jobs', 'vikbooking');
				break;
			case 'VBMAINCRONNEW':
				$result = __('New Cron Job', 'vikbooking');
				break;
			case 'VBMAINCRONEDIT':
				$result = __('Edit', 'vikbooking');
				break;
			case 'VBMAINCRONDEL':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBNOCRONS':
				$result = __('No Cron Jobs currently set up or scheduled.', 'vikbooking');
				break;
			case 'VBCRONNAME':
				$result = __('Cron Job Name', 'vikbooking');
				break;
			case 'VBCRONCLASS':
				$result = __('Class File', 'vikbooking');
				break;
			case 'VBCRONLASTEXEC':
				$result = __('Last Execution', 'vikbooking');
				break;
			case 'VBCRONPUBLISHED':
				$result = __('Published', 'vikbooking');
				break;
			case 'VBOCRONSAVED':
				$result = __('Cron Job Saved!', 'vikbooking');
				break;
			case 'VBOCRONUPDATED':
				$result = __('Cron Job Updated!', 'vikbooking');
				break;
			case 'VBCRONLOGS':
				$result = __('Execution Logs', 'vikbooking');
				break;
			case 'VBCRONACTIONS':
				$result = __('Actions', 'vikbooking');
				break;
			case 'VBCRONACTION':
				$result = __('Execute', 'vikbooking');
				break;
			case 'VBCRONEXECRESULT':
				$result = __('Cron Job Result', 'vikbooking');
				break;
			case 'VBCRONPARAMS':
				$result = __('Parameters', 'vikbooking');
				break;
			case 'VBCRONGETCMD':
				$result = __('Get Command', 'vikbooking');
				break;
			case 'VBCRONGETCMDHELP':
				$result = __('This cron job could be executed automatically by your server at regular intervals. The cron can also be executed manually by an administrator but letting the server do it, will be effortless and fully functional. Only servers supporting a Cron utility like crontab will be able of executing this cron job.', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTSTEPS':
				$result = __('Installation Steps', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTSTEPONE':
				$result = __('Download the executable PHP file for this cron job onto a local folder of your computer.', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTSTEPTWO':
				$result = __('Upload the downloaded file onto a directory of your server, either before, in or after the root directory of the web-server.', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTSTEPTHREE':
				$result = __('Log in to your server control panel and add a new job for your Cron Utility. Your hosting company should help you use this tool.', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTSTEPFOUR':
				$result = __('Cron Jobs require the execution interval and the command to execute. Set the necessary interval and the proper command to execute this cron job repetitively.', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTPATH':
				$result = __('Assuming that the executable PHP file was uploaded onto the root directory of your web-server, the command you should set in the Cron Utility should look similar to the one below. In this example, the path to the PHP interpreter has been set to <em>/usr/bin/php</em> but this may differ for your server.', 'vikbooking');
				break;
			case 'VBCRONGETCMDINSTURL':
				$result = __('Please be aware that PHP files in or after the root directory of the web-server can be executed at a public URL. This may not be secure if you do not want anyone to be able to launch the cron job except for the server. If the file was in the root directory, it would be callable at the URL below.', 'vikbooking');
				break;
			case 'VBCRONGETCMDGETFILE':
				$result = __('Download Executable File', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMCTYPE':
				$result = __('Reminder Type', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMCTYPEA':
				$result = __('Check-in Reminder', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMCTYPEB':
				$result = __('Remaining Balance Payment Reminder', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMCTYPEC':
				$result = __('After Check-out Message', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMCTYPECHELP':
				$result = __('If type = After Check-out Message or Leave Review, this will be the number of days after the check-out. If reservation date, this will target those who booked N days ago, where 0 means today. Number of days before the check-in otherwise.', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMBEFD':
				$result = __('Days in Advance', 'vikbooking');
				break;
			case 'VBOCRONEMAILREMPARAMSUBJECT':
				$result = __('eMail Subject', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMTEXT':
				$result = __('Message', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMTEST':
				$result = __('Test Mode', 'vikbooking');
				break;
			case 'VBOCRONSMSREMPARAMTESTHELP':
				$result = __('if enabled, the cron will not actually send the SMS', 'vikbooking');
				break;
			case 'VBOCRONEMAILREMPARAMTESTHELP':
				$result = __('if enabled, the cron will not actually send the eMail', 'vikbooking');
				break;
			case 'VBOCRONSMSREMHELP':
				$result = __('This cron job should be scheduled to run at regular intervals of one time per day. Executing the cron job once per day, at the preferred time, will guarantee the best result.', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMCWHEN':
				$result = __('Generate Invoices', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMCWHENA':
				$result = __('After the Check-in date', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMCWHENB':
				$result = __('Whenever the booking status is Confirmed', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMCWHENC':
				$result = __('After the Check-out date', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMSKIPOTAS':
				$result = __('Skip OTAs Bookings', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMDGEN':
				$result = __('Use Generation Date', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMSKIPOTASHELP':
				$result = __('if enabled, all the bookings transmitted by the channel manager will be ignored', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMEMAILSEND':
				$result = __('Send Invoices via eMail', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMTEST':
				$result = __('Test Mode', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMTESTHELP':
				$result = __('if enabled, the cron will not actually generate the invoices, nor it will send them via eMail to the customers', 'vikbooking');
				break;
			case 'VBOCRONINVGENPARAMTEXT':
				$result = __('eMail message with PDF attached', 'vikbooking');
				break;
			case 'VBOCRONINVGENHELP':
				$result = __('This cron job should be scheduled to run once per day. Remember to create at least one invoice manually from the back-end before running this cron. This is to set the invoices starting number and other details.', 'vikbooking');
				break;
			case 'VBCONFIGTHREECHECKINOUTSTAT':
				$result = __('Show Status Only Check-in/out', 'vikbooking');
				break;
			case 'VBOCONFIGCUSTCSSTPL':
				$result = __('Custom CSS Overrides', 'vikbooking');
				break;
			case 'VBOCONFIGINVOICETEMPLATE':
				$result = __('Invoices', 'vikbooking');
				break;
			case 'VBQUICKRESGUESTS':
				$result = __('Guests', 'vikbooking');
				break;
			case 'VBOGENINVOICES':
				$result = __('Generate Invoices', 'vikbooking');
				break;
			case 'VBINVSTARTNUM':
				$result = __('Invoices Starting Number', 'vikbooking');
				break;
			case 'VBINVNUMSUFFIX':
				$result = __('Invoices Number Suffix', 'vikbooking');
				break;
			case 'VBINVUSEDATE':
				$result = __('Invoices Date', 'vikbooking');
				break;
			case 'VBINVUSEDATEBOOKING':
				$result = __('Use Booking Date', 'vikbooking');
				break;
			case 'VBINVCOMPANYINFO':
				$result = __('Company Information Header', 'vikbooking');
				break;
			case 'VBINVSENDVIAMAIL':
				$result = __('Send Invoices via eMail', 'vikbooking');
				break;
			case 'VBOINVNUM':
				$result = __('Invoice Number', 'vikbooking');
				break;
			case 'VBOINVDATE':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBOINVCOLDESCR':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBOINVCOLNETPRICE':
				$result = __('Net', 'vikbooking');
				break;
			case 'VBOINVCOLTAX':
				$result = __('Taxes', 'vikbooking');
				break;
			case 'VBOINVCOLPRICE':
				$result = __('Price', 'vikbooking');
				break;
			case 'VBOINVCOLCUSTINFO':
				$result = __('Customer Information', 'vikbooking');
				break;
			case 'VBOINVCOLBOOKINGDETS':
				$result = __('Booking Details', 'vikbooking');
				break;
			case 'VBOINVCHECKIN':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBOINVCHECKOUT':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBOINVTOTGUESTS':
				$result = __('Guests', 'vikbooking');
				break;
			case 'VBOINVTOTNIGHTS':
				$result = __('Number of nights', 'vikbooking');
				break;
			case 'VBOINVTOTADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBOINVTOTCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBOINVCOLTOTAL':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBOINVCOLGRANDTOTAL':
				$result = __('Grand Total', 'vikbooking');
				break;
			case 'VBOGENINVERRNOBOOKINGS':
				$result = __('Unable to generate the invoices: no confirmed bookings with a total amount greater than zero.', 'vikbooking');
				break;
			case 'VBOGENINVERRBOOKING':
				$result = __('Error generating the invoice for the Booking ID %d', 'vikbooking');
				break;
			case 'VBOTOTINVOICESGEND':
				$result = __('Invoices Generated: %d - Invoices sent via eMail: %d', 'vikbooking');
				break;
			case 'VBOINVDOWNLOAD':
				$result = __('Download Invoice', 'vikbooking');
				break;
			case 'VBOEMAILINVOICEATTACHSUBJ':
				$result = __('Invoice for your reservation', 'vikbooking');
				break;
			case 'VBOEMAILINVOICEATTACHTXT':
				$result = __("Dear Customer, \nattached to this message you will find the invoice for your reservation. \nThank you!", 'vikbooking');
				break;
			case 'VBMENUINVOICES':
				$result = __('Invoices', 'vikbooking');
				break;
			case 'VBTOTINVOICES':
				$result = __('Total Invoices', 'vikbooking');
				break;
			case 'VBNOINVOICESFOUND':
				$result = __('No Invoices found.', 'vikbooking');
				break;
			case 'VBOINVCREATIONDATE':
				$result = __('Created on', 'vikbooking');
				break;
			case 'VBOINVBOOKINGID':
				$result = __('Booking ID', 'vikbooking');
				break;
			case 'VBOINVEMAILED':
				$result = __('Emailed to', 'vikbooking');
				break;
			case 'VBOINVEMAILNOW':
				$result = __('Send via eMail', 'vikbooking');
				break;
			case 'VBOINVREEMAIL':
				$result = __('Re-Send via eMail', 'vikbooking');
				break;
			case 'VBOINVOPEN':
				$result = __('View', 'vikbooking');
				break;
			case 'VBOINVDOWNLOAD':
				$result = __('Download', 'vikbooking');
				break;
			case 'VBINVSELECTALL':
				$result = __('Select All', 'vikbooking');
				break;
			case 'VBINVDESELECTALL':
				$result = __('Deselect All', 'vikbooking');
				break;
			case 'VBOINVAPPLYFILTER':
				$result = __('Apply Filter', 'vikbooking');
				break;
			case 'VBOINVREMOVEFILTER':
				$result = __('Remove Filter', 'vikbooking');
				break;
			case 'VBOTOTINVOICESRMVD':
				$result = __('Invoices Removed: %d', 'vikbooking');
				break;
			case 'VBMAININVOICESTITLE':
				$result = __('Vik Booking - Invoices', 'vikbooking');
				break;
			case 'VBMAININVOICESDOWNLOAD':
				$result = __('Download Selected Invoices', 'vikbooking');
				break;
			case 'VBMAININVOICESRESEND':
				$result = __('Send Selected Invoices via eMail', 'vikbooking');
				break;
			case 'VBMAININVOICESDEL':
				$result = __('Remove Selected Invoices', 'vikbooking');
				break;
			case 'VBOINVBOOKDETAILS':
				$result = __('Booking Details', 'vikbooking');
				break;
			case 'VBOPRINT':
				$result = __('Print', 'vikbooking');
				break;
			case 'VBRATESOVWTABLOS':
				$result = __('Length of Stay Pricing Overview', 'vikbooking');
				break;
			case 'VBRATESOVWTABCALENDAR':
				$result = __('Calendar Pricing Overview', 'vikbooking');
				break;
			case 'VBAFFANYPRICE':
				$result = __('Any Type of Price', 'vikbooking');
				break;
			case 'VBRATESOVWPRICETYPE':
				$result = __('Type of Price', 'vikbooking');
				break;
			case 'VBRATESOVWAFFALLPRICETYPE':
				$result = __('All', 'vikbooking');
				break;
			case 'VBRATESOVWSETNEWRATE':
				$result = __('Set New Rate', 'vikbooking');
				break;
			case 'VBRATESOVWERRNEWRATE':
				$result = __('Error while setting new rates. Missing data', 'vikbooking');
				break;
			case 'VBRATESOVWERRNORATES':
				$result = __('Error while setting new rates. No rates', 'vikbooking');
				break;
			case 'VBRATESOVWERRNORATESMOD':
				$result = __('Error: no changes needed for the selected rates', 'vikbooking');
				break;
			case 'VBOGOTOROVERVCAL':
				$result = __('Go to Calendar Rates Overview', 'vikbooking');
				break;
			case 'VBRATESOVWOPENSPL':
				$result = __('Special Price rule #%d', 'vikbooking');
				break;
			case 'VBRECORDSREMOVED':
				$result = __('Records Removed: %d', 'vikbooking');
				break;
			case 'VBCUSTOMERADDRESS':
				$result = __('Address', 'vikbooking');
				break;
			case 'VBCUSTOMERCITY':
				$result = __('City', 'vikbooking');
				break;
			case 'VBCUSTOMERZIP':
				$result = __('ZIP', 'vikbooking');
				break;
			case 'VBCUSTOMERDOCTYPE':
				$result = __('ID Type', 'vikbooking');
				break;
			case 'VBCUSTOMERDOCNUM':
				$result = __('ID Number', 'vikbooking');
				break;
			case 'VBCUSTOMERDOCIMG':
				$result = __('ID Scan Image', 'vikbooking');
				break;
			case 'VBCUSTOMERNOTES':
				$result = __('Notes', 'vikbooking');
				break;
			case 'VBOTAKESNAPCAM':
				$result = __('Take Webcam Snapshot', 'vikbooking');
				break;
			case 'VBOTAKESNAPCAMCONFIGURE':
				$result = __('Configure Webcam', 'vikbooking');
				break;
			case 'VBOTAKESNAPCAMTAKEIT':
				$result = __('Take Snapshot!', 'vikbooking');
				break;
			case 'VBOSNAPCAMNOTALLOWED':
				$result = __('You must allow the script to use your camera before taking the snapshot', 'vikbooking');
				break;
			case 'VBOTAKESNAPCAMLOADING':
				$result = __('Uploading Snapshot...Please Wait', 'vikbooking');
				break;
			case 'VBOSTATSALLROOMS':
				$result = __('- All Rooms', 'vikbooking');
				break;
			case 'VBOFILTERBYDATES':
				$result = __('Filter by Date', 'vikbooking');
				break;
			case 'VBOFILTERDATEBOOK':
				$result = __('Reservation Date', 'vikbooking');
				break;
			case 'VBOFILTERDATEIN':
				$result = __('Check-in Date', 'vikbooking');
				break;
			case 'VBOFILTERDATEOUT':
				$result = __('Check-out Date', 'vikbooking');
				break;
			case 'VBOCSVEXPCUSTOMERS':
				$result = __('CSV Export', 'vikbooking');
				break;
			case 'VBOCSVEXPCUSTOMERSGET':
				$result = __('Download CSV Export', 'vikbooking');
				break;
			case 'VBOANYCOUNTRY':
				$result = __('-- Any Country --', 'vikbooking');
				break;
			case 'VBOCUSTOMEREXPSEL':
				$result = __('Export Information about %d selected Customers', 'vikbooking');
				break;
			case 'VBOCUSTOMEREXPALL':
				$result = __('Export Customers Information', 'vikbooking');
				break;
			case 'VBMAINEXPCUSTOMERSTITLE':
				$result = __('Vik Booking - Export Customers Information', 'vikbooking');
				break;
			case 'VBOCUSTOMEREXPNOTES':
				$result = __('Include Notes', 'vikbooking');
				break;
			case 'VBOCUSTOMEREXPSCANIMG':
				$result = __('Include ID Image Scan URL', 'vikbooking');
				break;
			case 'VBOCUSTOMEREXPPIN':
				$result = __('Include PIN Code', 'vikbooking');
				break;
			case 'VBONORECORDSCSVCUSTOMERS':
				$result = __('No customer records to export', 'vikbooking');
				break;
			case 'VBOVIEWBOOKINGDET':
				$result = __('View Details', 'vikbooking');
				break;
			case 'VBISADDRESS':
				$result = __('Address', 'vikbooking');
				break;
			case 'VBISCITY':
				$result = __('City', 'vikbooking');
				break;
			case 'VBISZIP':
				$result = __('ZIP', 'vikbooking');
				break;
			case 'VBPEDITBUSYEXTRACOSTS':
				$result = __('Extra Services', 'vikbooking');
				break;
			case 'VBPEDITBUSYADDEXTRAC':
				$result = __('Add', 'vikbooking');
				break;
			case 'VBPEDITBUSYEXTRACNAME':
				$result = __('Service Name', 'vikbooking');
				break;
			case 'VBNEWAGEINTERVALCOSTPCENT':
				$result = __('% (Adults Rate)', 'vikbooking');
				break;
			case 'VBOCPARAMDEPOSITPAY':
				$result = __('Deposit', 'vikbooking');
				break;
			case 'VBOCONFDEPONLYIFDADV':
				$result = __('Deposit option not available when booked less than n days in advance', 'vikbooking');
				break;
			case 'VBOCONFDEPCUSTCHOICE':
				$result = __('Let the customers choose to leave a deposit', 'vikbooking');
				break;
			case 'VBCONFIGDATESEP':
				$result = __('Dates Separator', 'vikbooking');
				break;
			case 'VBOCCLOGDATAREMOVEDPCIDSS':
				$result = __('--- Credit Card details removed for PCI-DSS reasons ---', 'vikbooking');
				break;
			case 'VBRATESOVWCLOSEOPENRRP':
				$result = __('Close/Open Room Rate Plan', 'vikbooking');
				break;
			case 'VBRATESOVWCLOSERRP':
				$result = __('Close Rate Plan', 'vikbooking');
				break;
			case 'VBRATESOVWOPENRRP':
				$result = __('Open Rate Plan', 'vikbooking');
				break;
			case 'VBRATESOVWERRMODRPLANS':
				$result = __('Error while modifying rate plans. Missing data', 'vikbooking');
				break;
			case 'VBRATESOVWVCMRCHANGED':
				$result = __('Channel Manager - Rates Modification Count: %d', 'vikbooking');
				break;
			case 'VBRATESOVWVCMRCHANGEDOPEN':
				$result = __('Launch Channel Manager', 'vikbooking');
				break;
			case 'VBOVWNUMMONTHS':
				$result = __('Months:', 'vikbooking');
				break;
			case 'VBOVWDNDERRNOTENCELLS':
				$result = __('Error: landed date has %s free nights, while booking moved is %d nights long', 'vikbooking');
				break;
			case 'VBOVWDNDMOVINGBID':
				$result = __('Modifying Booking ID %d', 'vikbooking');
				break;
			case 'VBOVWDNDMOVINGROOM':
				$result = __('Switching room to %s', 'vikbooking');
				break;
			case 'VBOVWDNDMOVINGDATES':
				$result = __('Modifying dates to %s', 'vikbooking');
				break;
			case 'VBOVWALTBKERRMISSDATA':
				$result = __('Error, missing data for modifying the booking.', 'vikbooking');
				break;
			case 'VBOVWALTBKSWITCHROK':
				$result = __('The room %s has been switched to the %s.', 'vikbooking');
				break;
			case 'VBOVERVIEWLEGDND':
				$result = __('Drag &amp; Drop', 'vikbooking');
				break;
			case 'VBOVWGETBKERRMISSDATA':
				$result = __('Error, missing data for getting the booking information', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONSETCTA':
				$result = __('Set Days Closed to Arrival (CTA)', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONSETCTD':
				$result = __('Set Days Closed to Departure (CTD)', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONWDAYSCTA':
				$result = __('Week days closed to arrival', 'vikbooking');
				break;
			case 'VBNEWRESTRICTIONWDAYSCTD':
				$result = __('Week days closed to departure', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSCTA':
				$result = __('CTA Week Days', 'vikbooking');
				break;
			case 'VBPVIEWRESTRICTIONSCTD':
				$result = __('CTD Week Days', 'vikbooking');
				break;
			case 'VBORESTRWDAYSCTA':
				$result = __('Week Days Closed to Arrival', 'vikbooking');
				break;
			case 'VBORESTRWDAYSCTD':
				$result = __('Week Days Closed to Departure', 'vikbooking');
				break;
			case 'VBOCONFIGWARNDIFFCHECKINOUT':
				$result = __('Warning: the check-in and check-out times have changed and there are future bookings saved with the old times. This may cause issues with the availability. Consider updating all the future reservations manually to avoid problems.', 'vikbooking');
				break;
			case 'VBPARAMCUSTPRICESUBTEXT':
				$result = __('Custom Price Sub-Text', 'vikbooking');
				break;
			case 'VBPARAMCUSTPRICESUBTEXTHELP':
				$result = __('this text can be displayed below the label \'per night\' to include additional information like the cost per occupancy. HTML is allowed in this text', 'vikbooking');
				break;
			case 'VBBOOKINGINVNOTES':
				$result = __('Booking Invoice Notes', 'vikbooking');
				break;
			case 'VBBOOKINGINVNOTESHELP':
				$result = __('You can use this text-area field to enter some additional information related to this booking for the invoice. Example: a booking reference number for third parties that should be included in the invoice.', 'vikbooking');
				break;
			case 'VBOGENBOOKINGINVOICE':
				$result = __('Generate Invoice', 'vikbooking');
				break;
			case 'VBCONFIGVCMAUTOUPD':
				$result = __('Channel Manager Auto-Sync', 'vikbooking');
				break;
			case 'VBCONFIGVCMAUTOUPDHELP':
				$result = __('If enabled, the availability update requests for all new bookings, booking modifications, confirmations and cancellations made through the Administrator section, will be automatically sent to the channel manager in background. For the bookings generated via front-end, the channel manager update requests can be automated from the page Settings of Vik Channel Manager (Auto-Sync). Recommended value for this setting: Enabled.', 'vikbooking');
				break;
			case 'VBCONFIGVCMAUTOUPDMISS':
				$result = __('Vik Channel Manager is not installed.', 'vikbooking');
				break;
			case 'VBWAITINGFORPAYMENT':
				$result = __('Waiting for the Payment', 'vikbooking');
				break;
			case 'VBLEAVEDEPOSIT':
				$result = __('Leave a deposit of ', 'vikbooking');
				break;
			case 'VBCONFIGSMSSENDWHEN':
				$result = __('Send SMS When', 'vikbooking');
				break;
			case 'VBCONFIGSMSSENDWHENCONF':
				$result = __('Reservation is Confirmed', 'vikbooking');
				break;
			case 'VBCONFIGSMSSENDWHENCONFPEND':
				$result = __('Reservation is Pending or Confirmed', 'vikbooking');
				break;
			case 'VBCONFIGSMSADMTPLPEND':
				$result = __('Admin SMS Template (Pending)', 'vikbooking');
				break;
			case 'VBCONFIGSMSCUSTOTPLPEND':
				$result = __('Customer SMS Template (Pending)', 'vikbooking');
				break;
			case 'VBINVEDITBINFO':
				$result = __('Modify Information/Rates', 'vikbooking');
				break;
			case 'VBSAVEANDDOINV':
				$result = __('Save &amp; Invoice', 'vikbooking');
				break;
			case 'VBCONFIRMGENINV':
				$result = __('The invoice will be generated. Proceed?', 'vikbooking');
				break;
			case 'VBSENDEMAILACTION':
				$result = __('Send Custom Email', 'vikbooking');
				break;
			case 'VBSENDEMAILCUSTSUBJ':
				$result = __('Subject', 'vikbooking');
				break;
			case 'VBSENDEMAILCUSTCONT':
				$result = __('Message', 'vikbooking');
				break;
			case 'VBSENDEMAILCUSTATTCH':
				$result = __('Attachment', 'vikbooking');
				break;
			case 'VBSENDEMAILCUSTFROM':
				$result = __('From Address', 'vikbooking');
				break;
			case 'VBSENDEMAILERRMISSDATA':
				$result = __('Missing required data for sending the email message.', 'vikbooking');
				break;
			case 'VBSENDEMAILOK':
				$result = __('The message was sent successfully', 'vikbooking');
				break;
			case 'VBEMAILCUSTFROMTPL':
				$result = __('- Load text from Template -', 'vikbooking');
				break;
			case 'VBEMAILCUSTFROMTPLUSE':
				$result = __('Use Template', 'vikbooking');
				break;
			case 'VBEMAILCUSTFROMTPLRM':
				$result = __('Remove Template', 'vikbooking');
				break;
			case 'VBOCPARAMBOOKTAGS':
				$result = __('Bookings Color-Tags', 'vikbooking');
				break;
			case 'VBOCOLORTAGADD':
				$result = __('Add Color Tag', 'vikbooking');
				break;
			case 'VBOCOLORTAGRMALL':
				$result = __('Reset Color Tags', 'vikbooking');
				break;
			case 'VBOCOLORTAGADDPLCHLD':
				$result = __('Color Tag Name', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULECUSTOMCOLOR':
				$result = __('Custom Color Rule', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULECONFTWO':
				$result = __('Confirmed (No Rates)', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULECONFTHREE':
				$result = __('To be Paid', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULECONFFOUR':
				$result = __('Partially Paid', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULECONFFIVE':
				$result = __('Fully Paid', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULEINVONE':
				$result = __('Invoiced', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULEINVTWO':
				$result = __('Invoice Paid', 'vikbooking');
				break;
			case 'VBOAVOVWBMODECLASSIC':
				$result = __('Classic View', 'vikbooking');
				break;
			case 'VBOAVOVWBMODETAGS':
				$result = __('Tags View', 'vikbooking');
				break;
			case 'VBOAVOVWBMODETAGSLBL':
				$result = __('Tags:', 'vikbooking');
				break;
			case 'VBOVERVIEWSTICKYTHEADON':
				$result = __('Sticky Date Headers ON', 'vikbooking');
				break;
			case 'VBOVERVIEWSTICKYTHEADOFF':
				$result = __('Sticky Date Headers OFF', 'vikbooking');
				break;
			case 'VBOVERVIEWTOGGLESUBROOM':
				$result = __('Toggle Availability by Units', 'vikbooking');
				break;
			case 'VBOFILTERBYPAYMENT':
				$result = __('Filter by Payment', 'vikbooking');
				break;
			case 'VBOFILTCONFNUMCUST':
				$result = __('Booking ID/Confirmation Number', 'vikbooking');
				break;
			case 'VBCONFIGLOGOBACKEND':
				$result = __('Back-end Logo (180px)', 'vikbooking');
				break;
			case 'VBOCONFIGCUSTBACKCSSTPL':
				$result = __('Back-end Custom CSS', 'vikbooking');
				break;
			case 'VBCONFIGSENDEMAILWHEN':
				$result = __('Send Emails When', 'vikbooking');
				break;
			case 'VBOCUSTOMERDETAILS':
				$result = __('Customer Details', 'vikbooking');
				break;
			case 'VBOCUSTOMERSALESCHANNEL':
				$result = __('Sales Channel', 'vikbooking');
				break;
			case 'VBOCUSTOMERISCHANNEL':
				$result = __('Is a Sales Channel', 'vikbooking');
				break;
			case 'VBOCUSTOMERCOMMISSION':
				$result = __('Commissions per booking', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSON':
				$result = __('Calculate Commissions on', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSONTOTAL':
				$result = __('Booking Total Amount', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSONRRATES':
				$result = __('Rooms Rates only', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSONTAX':
				$result = __('Apply Commissions on', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSONTAXINCL':
				$result = __('Amount Tax Included', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSONTAXEXCL':
				$result = __('Amount Tax Excluded', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSNAME':
				$result = __('Sales Channel Name', 'vikbooking');
				break;
			case 'VBOCUSTOMERCMMSCOLOR':
				$result = __('Sales Channel Color', 'vikbooking');
				break;
			case 'VBOSTATSMODETS':
				$result = __('Data based on bookings creation date', 'vikbooking');
				break;
			case 'VBOSTATSMODENIGHTS':
				$result = __('Data based on booked dates', 'vikbooking');
				break;
			case 'VBOGRAPHTOTSALES':
				$result = __('Total Sales', 'vikbooking');
				break;
			case 'VBOGRAPHTOTNIGHTS':
				$result = __('Total nights booked: %d', 'vikbooking');
				break;
			case 'VBOGRAPHAVGVALUES':
				$result = __('Average Values may be applied when the booked dates are not included in the dates filter', 'vikbooking');
				break;
			case 'VBOGRAPHTOTNIGHTSLBL':
				$result = __('Nights Booked', 'vikbooking');
				break;
			case 'VBOGRAPHTOTOCCUPANCY':
				$result = __('Total Occupancy: %s%%', 'vikbooking');
				break;
			case 'VBOGRAPHTOTOCCUPANCYLBL':
				$result = __('Total Occupancy', 'vikbooking');
				break;
			case 'VBOGRAPHTOTUNITSLBL':
				$result = __('Total Units', 'vikbooking');
				break;
			case 'VBCUSTOMERCOMPANY':
				$result = __('Company Name', 'vikbooking');
				break;
			case 'VBCUSTOMERCOMPANYVAT':
				$result = __('VAT ID', 'vikbooking');
				break;
			case 'VBISCOMPANY':
				$result = __('Company Name', 'vikbooking');
				break;
			case 'VBISVAT':
				$result = __('VAT ID', 'vikbooking');
				break;
			case 'VBCSVEXPDATESRANGE':
				$result = __('Dates Range', 'vikbooking');
				break;
			case 'VBOPREVROOMMOVED':
				$result = __('Previous room %s was switched on %s', 'vikbooking');
				break;
			case 'VBOBOOKINGCREATEDBY':
				$result = __('Booking created by User ID %s', 'vikbooking');
				break;
			case 'VBOFILTERBYSTATUS':
				$result = __('Filter by Status', 'vikbooking');
				break;
			case 'VBCSVCREATEDBY':
				$result = __('Created by', 'vikbooking');
				break;
			case 'VBMAINTITLEUPDATEPROGRAM':
				$result = __('Vik Booking - Software Update', 'vikbooking');
				break;
			case 'VBCHECKINGVERSION':
				$result = __('Checking Version...', 'vikbooking');
				break;
			case 'VBDOWNLOADUPDATEBTN1':
				$result = __('Download Update & Install', 'vikbooking');
				break;
			case 'VBDOWNLOADUPDATEBTN0':
				$result = __('Download & Re-Install', 'vikbooking');
				break;
			case 'VBCONFIGMINAUTOREMOVE':
				$result = __('Minutes of Waiting for Auto Removing Unpaid Reservations', 'vikbooking');
				break;
			case 'VBOMANAGECHECKSINOUT':
				$result = __('Customer Check-in/Check-Out Management', 'vikbooking');
				break;
			case 'VBOMANAGECHECKIN':
				$result = __('Registration', 'vikbooking');
				break;
			case 'VBOMANAGECHECKOUT':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBOGUESTSDETAILS':
				$result = __('Guests Details', 'vikbooking');
				break;
			case 'VBOGUESTNUM':
				$result = __('Guest #%d', 'vikbooking');
				break;
			case 'VBOGUESTEXTRANOTES':
				$result = __('Extra Notes', 'vikbooking');
				break;
			case 'VBOCHECKEDSTATUS':
				$result = __('Registration', 'vikbooking');
				break;
			case 'VBOCHECKEDSTATUSIN':
				$result = __('Checked-in', 'vikbooking');
				break;
			case 'VBOCHECKEDSTATUSOUT':
				$result = __('Checked-out', 'vikbooking');
				break;
			case 'VBOCHECKEDSTATUSNOS':
				$result = __('No Show', 'vikbooking');
				break;
			case 'VBOCHECKEDSTATUSZERO':
				$result = __('none', 'vikbooking');
				break;
			case 'VBOSETCHECKEDSTATUSIN':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBOSETCHECKEDSTATUSOUT':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBOSETCHECKEDSTATUSNOS':
				$result = __('No Show', 'vikbooking');
				break;
			case 'VBOSETCHECKEDSTATUSZERO':
				$result = __('Cancel Registration', 'vikbooking');
				break;
			case 'VBONEWAMOUNTPAID':
				$result = __('New Amount Paid', 'vikbooking');
				break;
			case 'VBOTOGGLECHECKINNOTES':
				$result = __('Check-in Notes (internal only)', 'vikbooking');
				break;
			case 'VBOCHECKINTIMEOVER':
				$result = __('The check-out date is in the past.', 'vikbooking');
				break;
			case 'VBOCHECKINACTCONFGUESTS':
				$result = __('Some guests details are missing. Proceed?', 'vikbooking');
				break;
			case 'VBOCHECKINERRNOCUSTOMER':
				$result = __('No customer assigned to this booking yet. Please create a new customer.', 'vikbooking');
				break;
			case 'VBOCHECKINSTATUSUPDATED':
				$result = __('Information saved successfully', 'vikbooking');
				break;
			case 'VBOCHECKINUPDATEBTN':
				$result = __('Update Information', 'vikbooking');
				break;
			case 'VBONORESULTS':
				$result = __('No results', 'vikbooking');
				break;
			case 'VBONOCHECKINSTODAY':
				$result = __('No arrivals for today.', 'vikbooking');
				break;
			case 'VBONOCHECKOUTSTODAY':
				$result = __('No departures for today.', 'vikbooking');
				break;
			case 'VBODASHSEARCHKEYS':
				$result = __('Search', 'vikbooking');
				break;
			case 'VBOGENCHECKINDOC':
				$result = __('Generate Check-in Document', 'vikbooking');
				break;
			case 'VBODWNLCHECKINDOC':
				$result = __('Download Check-in Document', 'vikbooking');
				break;
			case 'VBOSIGNATURESHARE':
				$result = __('Share Link', 'vikbooking');
				break;
			case 'VBOSIGNATURESKIP':
				$result = __('Skip Signature', 'vikbooking');
				break;
			case 'VBOSIGNATURESIGNABOVE':
				$result = __('Sign above', 'vikbooking');
				break;
			case 'VBOSIGNATURESAVE':
				$result = __('Save', 'vikbooking');
				break;
			case 'VBOSIGNATURECLEAR':
				$result = __('Clear', 'vikbooking');
				break;
			case 'VBOSIGNATUREISEMPTY':
				$result = __('No Signature Provided!', 'vikbooking');
				break;
			case 'VBONOSIGNATURECONF':
				$result = __('Proceed without the signature?', 'vikbooking');
				break;
			case 'VBOSIGNSHAREEMAIL':
				$result = __('Send Signature Link via eMail', 'vikbooking');
				break;
			case 'VBOSIGNSHARESMS':
				$result = __('Send Signature Link via SMS', 'vikbooking');
				break;
			case 'VBOSIGNSENDLINK':
				$result = __('Send Link', 'vikbooking');
				break;
			case 'VBOSIGNSHAREEMPTY':
				$result = __('Please enter a value', 'vikbooking');
				break;
			case 'VBOSIGNSHARESUBJECT':
				$result = __('Check-in Signature', 'vikbooking');
				break;
			case 'VBOSIGNSHAREMESSAGE':
				$result = __("Dear %s,\n\nHere is the link to sign the Check-in Document for your stay:\n%s\n\nKind Regards,\n%s", 'vikbooking');
				break;
			case 'VBOSIGNSHAREMESSAGESMS':
				$result = __("Dear %s,\n\nLink for Signature:\n%s", 'vikbooking');
				break;
			case 'VBORELOADING':
				$result = __('Reloading', 'vikbooking');
				break;
			case 'VBOERRSTORESIGNFILE':
				$result = __('Error saving the signature', 'vikbooking');
				break;
			case 'VBOCURRENTSIGNATURE':
				$result = __('Customer Signature Available', 'vikbooking');
				break;
			case 'VBOSIGNATUREAGAIN':
				$result = __('New Signature', 'vikbooking');
				break;
			case 'VBOTERMSCONDS':
				$result = __('Terms and Conditions', 'vikbooking');
				break;
			case 'VBOTERMSCONDSIACCEPT':
				$result = __('I accept the Terms and Conditions', 'vikbooking');
				break;
			case 'VBOTERMSCONDSACCCLOSE':
				$result = __('I have read and agree to the terms and conditions', 'vikbooking');
				break;
			case 'VBOSIGNMUSTACCEPT':
				$result = __('You must accept the Terms and Conditions.', 'vikbooking');
				break;
			case 'VBOTERMSCONDSDEFTEXT':
				$result = __("1. Check-in time is from %s and check-out time is until %s.\n2. The guest acknowledge joint and several liability for all services rendered until full settlement of bills.\n3. Guests will be held responsible for any loss or damage to the rooms caused by themselves, their friends or any person for whom they are responsible.\n4. Hotel Management is not responsible for your personal belongings and valuables like money, jewellery or any other valuables left by guests in the rooms.\n5. Complimentary safe deposit boxes, subject to the terms and conditions for use are available in rooms.\n6. Regardless of charge instructions, I acknowledge that I am personally liable for the payment of all charges incurred by me during my stay.", 'vikbooking');
				break;
			case 'VBOCHECKINDOCTITLE':
				$result = __('Check-in Document', 'vikbooking');
				break;
			case 'VBOCHECKINDOCARRVDT':
				$result = __('Arrival Date', 'vikbooking');
				break;
			case 'VBOCHECKINDOCDEPADT':
				$result = __('Departure Date', 'vikbooking');
				break;
			case 'VBOCHECKINDOCTODAYDT':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBOCHECKINDOCGUESTSIGN':
				$result = __('Guest Signature', 'vikbooking');
				break;
			case 'VBOERRGENCHECKINDOC':
				$result = __('Could not generate the PDF file', 'vikbooking');
				break;
			case 'VBOGENCHECKINDOCSUCCESS':
				$result = __('Check-in Document generated successfully', 'vikbooking');
				break;
			case 'VBOCONFIGCHECKINTEMPLATE':
				$result = __('Check-in Document', 'vikbooking');
				break;
			case 'VBOCONFDEPOSITOVRDS':
				$result = __('Deposit Amount Overrides', 'vikbooking');
				break;
			case 'VBOCONFDEPOSITOVRDSMORE':
				$result = __('(and more)', 'vikbooking');
				break;
			case 'VBOSHOWQUICKRES':
				$result = __('New Reservation', 'vikbooking');
				break;
			case 'VBOBOOKINGLANG':
				$result = __('Language', 'vikbooking');
				break;
			case 'VBOBOOKADDROOM':
				$result = __('Add Room', 'vikbooking');
				break;
			case 'VBOBOOKCANTADDROOM':
				$result = __('The room cannot be added to the booking', 'vikbooking');
				break;
			case 'VBOBOOKADDROOMERR':
				$result = __('%s is not available from %s to %s', 'vikbooking');
				break;
			case 'VBOBOOKRMROOMCONFIRM':
				$result = __('Do you want to remove this room from the reservation?', 'vikbooking');
				break;
			case 'VBOCONFIGSHOWSEARCHSUGG':
				$result = __('Suggest solutions when no availability', 'vikbooking');
				break;
			case 'VBOYESWITHAVAILABILITY':
				$result = __('Yes, with units available', 'vikbooking');
				break;
			case 'VBOYESNOAVAILABILITY':
				$result = __('Yes, without units available', 'vikbooking');
				break;
			case 'VBOLOADFA':
				$result = __('Load Font Awesome', 'vikbooking');
				break;
			case 'VBOCONFNODEPNONREFUND':
				$result = __('Pay Entire Amount if Non-Refundable Rates', 'vikbooking');
				break;
			case 'VBOCONFNODEPNONREFUNDHELP':
				$result = __('If enabled, and at least one non-refundable rate plan is selected for the rooms reservation, then no deposit will be available. Customers will be asked to pay the 100% of the reservation. This parameter, if the criteria is met, has higher priority than any other.', 'vikbooking');
				break;
			case 'VBOPAYMENTHIDENONREF':
				$result = __('Hide for Non-Refundable Rates', 'vikbooking');
				break;
			case 'VBOPAYMENTHIDENONREFHELP':
				$result = __('If enabled, and at least one non-refundable rate plan is selected for the rooms reservation, then this payment method will not be available to complete the booking.', 'vikbooking');
				break;
			case 'VBOBOOKDETTABDETAILS':
				$result = __('Booking Details', 'vikbooking');
				break;
			case 'VBOBOOKDETTABADMIN':
				$result = __('Administration', 'vikbooking');
				break;
			case 'VBOMISSPRTYPEROOMH':
				$result = __('The rooms of this reservation have no rates defined. Make sure to set a rate for each room booked, or the reservation will be incomplete.', 'vikbooking');
				break;
			case 'VBOREMOVEROOM':
				$result = __('Remove Room', 'vikbooking');
				break;
			case 'VBCONFIGCLOSINGDATESHELP':
				$result = __('The Closing Dates of the company are settings defined at Account-Level for the booking engine of the website. The closed dates will not be selectable from the calendars in the front-end. However, these settings will NOT apply to the channels connected through the Channel Manager. You should rather close the rooms from the page Calendar if you wanted the Channel Manager to close the availability on the channels.', 'vikbooking');
				break;
			case 'VBOROOMNORATE':
				$result = __('- no rate plan assigned', 'vikbooking');
				break;
			case 'VBOCUSTOMER':
				$result = __('Customer', 'vikbooking');
				break;
			case 'VBOUPDRATESONCHANNELS':
				$result = __('Update Rates on Channels', 'vikbooking');
				break;
			case 'VBOUPDRATESONCHANNELSHELP':
				$result = __('If enabled, the new rate you are setting will be transmitted automatically to all channels connected. The Channel Manager will transmit the rates to the channels by following the rules already configured in the system. Rules are taken from the previous execution of the function Bulk Actions - Rates Upload. For example, if you have previously transmitted your rates increased by n% to channels like Booking.com or Expedia, the Channel Manager will transmit these rates by using the same rules you have already used.', 'vikbooking');
				break;
			case 'VBOVCMRATESRES':
				$result = __('Channel Manager Update Response', 'vikbooking');
				break;
			case 'VBOROVWSELPERIOD':
				$result = __('Select Period', 'vikbooking');
				break;
			case 'VBOROVWSELPERIODFROM':
				$result = __('From', 'vikbooking');
				break;
			case 'VBOROVWSELPERIODTO':
				$result = __('To', 'vikbooking');
				break;
			case 'VBOBCOMREPORTNOSHOW':
				$result = __('Report to Booking.com as No-Show', 'vikbooking');
				break;
			case 'VBOBCOMREPORTNOSHOWCONF':
				$result = __('Do you really want to report the reservation to Booking.com as No-Show?', 'vikbooking');
				break;
			case 'VBOPRINTRECEIPT':
				$result = __('Print Receipt', 'vikbooking');
				break;
			case 'VBOCONFIGALLOWMODCANC':
				$result = __('Bookings Modification/Cancellation', 'vikbooking');
				break;
			case 'VBOCONFIGMODCANC0':
				$result = __('Disabled', 'vikbooking');
				break;
			case 'VBOCONFIGMODCANC1':
				$result = __('Disabled, with Request', 'vikbooking');
				break;
			case 'VBOCONFIGMODCANC2':
				$result = __('Modification Enabled, Cancellation Disabled', 'vikbooking');
				break;
			case 'VBOCONFIGMODCANC3':
				$result = __('Cancellation Enabled, Modification Disabled', 'vikbooking');
				break;
			case 'VBOCONFIGMODCANC4':
				$result = __('Enabled', 'vikbooking');
				break;
			case 'VBOCONFIGMODCANCMINDAYS':
				$result = __('Min. Days in Advance', 'vikbooking');
				break;
			case 'VBOCONFIGALLOWMODCANCHELP':
				$result = __('Choose whether the customers should be allowed to modify or cancel their reservations via front-end. If Disabled, with Request, customers will only be able to contact the property by sending a message. It is also possible to define a minimum number of days in advance to make a modification or a cancellation. The refundable settings of the selected Types of Price will be always applied, regardless of this limit.', 'vikbooking');
				break;
			case 'VBOPRICEATTRHELP':
				$result = __('The attribute is an additional information you can pass to the Type of Price for any number of nights of stay. It is NOT a mandatory field and it can be left empty. An example of attribute could be &quot;Cancellation Fees&quot;. From the page Rates Table, you will be able to specify the value for the attribute for any number of nights of stay. For example, from 1 to 7 nights: &quot;deposit non refundable&quot;. From 8 to 14 nights: &quot;50% deposit refund&quot;. The attribute will be visible to the customer during the reservation process.', 'vikbooking');
				break;
			case 'VBNEWPRICECANCPOLICY':
				$result = __('Cancellation Policy', 'vikbooking');
				break;
			case 'VBNEWPRICECANCPOLICYHELP':
				$result = __('The cancellation policy text will be displayed to the customers that would like to cancel their refundable reservation. With this text you can give extra details about what will be refunded, how and when. This text should NOT replace or define your general Terms and Conditions.', 'vikbooking');
				break;
			case 'VBOROVWSELRPLAN':
				$result = __('Rate Plan', 'vikbooking');
				break;
			case 'VBOBOOKMODLOGSTR':
				$result = __("Booking modified on %s.\nPrevious dates booked: %s.\nPrevious rooms booked: %s.\nPrevious Total: %s.", 'vikbooking');
				break;
			case 'VBOPRICETYPEMINLOS':
				$result = __('Minimum Nights of Stay', 'vikbooking');
				break;
			case 'VBOPRICETYPEMINLOSHELP':
				$result = __('The minimum number of nights of stay required for this type of price to be available for selection. This setting defines a restriction at rate plan level. In order to apply restrictions at room level, use the apposite function &quot;Restrictions&quot;.', 'vikbooking');
				break;
			case 'VBOSEARCHEXISTCUST':
				$result = __('Existing Customer', 'vikbooking');
				break;
			case 'VBOCONFIGTHUMBSIZE':
				$result = __('Thumbnails Size px', 'vikbooking');
				break;
			case 'VBOPRICETYPEMINHADV':
				$result = __('Minimum Hours in Advance', 'vikbooking');
				break;
			case 'VBOPRICETYPEMINHADVHELP':
				$result = __('Define the minimum number of hours in advance from the booking date and the check-in date for this Rate Plan to be available. For example, if you don\'t want to display this Rate Plan if there are less than 48 hours to the arrival date and time, set it to 48. Leave it empty (to 0 hours) if you always want to display this Type of Price.', 'vikbooking');
				break;
			case 'VBOPRICETYPESRESTR':
				$result = __('Restrictions', 'vikbooking');
				break;
			case 'VBOAGEINTVALBCOSTPCENT':
				$result = __('% (Room Rate)', 'vikbooking');
				break;
			case 'VBOSUCCUPDOPTION':
				$result = __('Room Option updated successfully', 'vikbooking');
				break;
			case 'VBOFISCRECEIPT':
				$result = __('Receipt', 'vikbooking');
				break;
			case 'VBOFISCRECEIPTNUM':
				$result = __('Number', 'vikbooking');
				break;
			case 'VBOFISCRECEIPTDATE':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBORECEIPTNOTESDEF':
				$result = __('Additional Notes for the Receipt...', 'vikbooking');
				break;
			case 'VBOCOLORTAGRULERCPONE':
				$result = __('Receipt Issued', 'vikbooking');
				break;
			case 'VBCUSTOMERGENDER':
				$result = __('Gender', 'vikbooking');
				break;
			case 'VBCUSTOMERGENDERM':
				$result = __('Male', 'vikbooking');
				break;
			case 'VBCUSTOMERGENDERF':
				$result = __('Female', 'vikbooking');
				break;
			case 'VBCUSTOMERBDATE':
				$result = __('Date of Birth', 'vikbooking');
				break;
			case 'VBCUSTOMERPBIRTH':
				$result = __('Place of Birth', 'vikbooking');
				break;
			case 'VBCUSTOMERMISSIMPDET':
				$result = __('Important Details Missing', 'vikbooking');
				break;
			case 'VBCUSTOMERMISSIMPDETHELP':
				$result = __('Some important details are missing for this customer. Other functions may require this information, so make sure to complete the customer profile.', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTAB':
				$result = __('Booking History', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYLBLTYPE':
				$result = __('Event', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYLBLDATE':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYLBLDESC':
				$result = __('Description', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYLBLTPAID':
				$result = __('Total Paid', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYLBLTOT':
				$result = __('Booking Total', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTNC':
				$result = __('New Confirmed Booking', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTMW':
				$result = __('Booking Modified by Customer', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTMB':
				$result = __('Administrator Booking Modification', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTNP':
				$result = __('New Stand-by Booking', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTP0':
				$result = __('Payment Received', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTPN':
				$result = __('Other Payment Received', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCR':
				$result = __('Customer Request', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCW':
				$result = __('Booking Cancelled by Customer', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCA':
				$result = __('Booking Auto Cancellation', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCB':
				$result = __('Administrator Booking Cancellation', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRA':
				$result = __('Cancel Booking Registration', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRB':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRC':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRZ':
				$result = __('No-Show', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTTC':
				$result = __('Administrator Booking Confirmation', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTMC':
				$result = __('Booking Modified from Channel', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCC':
				$result = __('Booking Cancelled from Channel', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTNO':
				$result = __('New Booking from Channel', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTAC':
				$result = __('Booking Confirmed via App', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTAR':
				$result = __('Booking Cancelled via App', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTAM':
				$result = __('Booking Modified via App', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTAN':
				$result = __('Booking Created via App', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTNB':
				$result = __('Booking Created by Administrator', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTBR':
				$result = __('Booking Receipt Issued', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTBI':
				$result = __('Booking Invoice Issued', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRP':
				$result = __('Reporting', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCE':
				$result = __('Email to Client', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCS':
				$result = __('SMS to Client', 'vikbooking');
				break;
			case 'VBMAINPMSREPORTSTITLE':
				$result = __('Vik Booking - PMS Reports', 'vikbooking');
				break;
			case 'VBMENUPMS':
				$result = __('PMS', 'vikbooking');
				break;
			case 'VBMENUPMSREPORTS':
				$result = __('Reports', 'vikbooking');
				break;
			case 'VBOREPORTSELECT':
				$result = __('- Select Report Type -', 'vikbooking');
				break;
			case 'VBOREPORTLOAD':
				$result = __('Load Report Data', 'vikbooking');
				break;
			case 'VBOREPORTREVENUE':
				$result = __('Revenue', 'vikbooking');
				break;
			case 'VBOREPORTSDATEFROM':
				$result = __('Date From', 'vikbooking');
				break;
			case 'VBOREPORTSDATETO':
				$result = __('Date To', 'vikbooking');
				break;
			case 'VBOREPORTSROOMFILT':
				$result = __('Room Filter', 'vikbooking');
				break;
			case 'VBOREPORTSERRNODATES':
				$result = __('Please select the desired dates for the Report.', 'vikbooking');
				break;
			case 'VBOREPORTSERRNORESERV':
				$result = __('No bookings found with the parameters specified.', 'vikbooking');
				break;
			case 'VBOREPORTCSVEXPORT':
				$result = __('Export as CSV', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEDAY':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBOREPORTREVENUERSOLD':
				$result = __('Rooms Sold', 'vikbooking');
				break;
			case 'VBOREPORTREVENUETOTB':
				$result = __('Total Bookings', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEPOCC':
				$result = __('% Occupancy', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREVWEB':
				$result = __('IBE Revenue', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREVOTA':
				$result = __('OTA Revenue', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEADR':
				$result = __('ADR', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEADRHELP':
				$result = __('The &quot;Average Daily Rate&quot; is calculated by dividing the Total Revenue by the number of Rooms Sold.', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREVPAR':
				$result = __('RevPAR', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREVPARH':
				$result = __('The &quot;Revenue Per Available Room&quot; is calculated by dividing the Total Revenue by the total number of Rooms Available.', 'vikbooking');
				break;
			case 'VBOREPORTREVENUETAX':
				$result = __('Taxes/Fees', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREV':
				$result = __('Revenue', 'vikbooking');
				break;
			case 'VBOREPORTSTOTALROW':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBOREPORTTOPCOUNTRIES':
				$result = __('Top Countries', 'vikbooking');
				break;
			case 'VBOREPORTTOPCOUNTRIESC':
				$result = __('Country', 'vikbooking');
				break;
			case 'VBOREPORTTOPCUNKNC':
				$result = __('Unknown', 'vikbooking');
				break;
			case 'VBOREPORTTOURISTTAXES':
				$result = __('Tourist Taxes', 'vikbooking');
				break;
			case 'VBOREPORTOURISTTAXCUSTOMER':
				$result = __('Customer Name', 'vikbooking');
				break;
			case 'VBOREPORTOURISTTAXTOT':
				$result = __('Taxes', 'vikbooking');
				break;
			case 'VBOREPORTOPTIONSEXTRAS':
				$result = __('Options/Extras', 'vikbooking');
				break;
			case 'VBOREPORTOPTIONSEXTRASHELP':
				$result = __('The values for the Options/Extras are tax included. The amount is calculated by subtracting the amount of City Taxes and Room Costs from the Booking Total. It\'s the sum of all costs excluding the City Taxes and the costs of the rooms. It may include costs per children based on their age.', 'vikbooking');
				break;
			case 'VBOREPORTFILTALLOPTS':
				$result = __('- All Options', 'vikbooking');
				break;
			case 'VBOREPORTALLOGGIATIPOLIZIA':
				$result = __('Italian Police: Guests Details', 'vikbooking');
				break;
			case 'JSEARCH_TOOLS':
				$result = __('Search Tools', 'vikbooking');
				break;
			case 'VBCONFIGSMSADMTPLCANC':
				$result = __('Admin SMS Template (Cancelled)', 'vikbooking');
				break;
			case 'VBCONFIGSMSCUSTOTPLCANC':
				$result = __('Customer SMS Template (Cancelled)', 'vikbooking');
				break;
			case 'VBDASHFIRSTSETTITLE':
				$result = __('Initial Setup', 'vikbooking');
				break;
			case 'VBDASHFIRSTSETSUBTITLE':
				$result = __('Complete the configuration of the following tasks to get started.', 'vikbooking');
				break;
			case 'VBCONFIGURETASK':
				$result = __('Configure', 'vikbooking');
				break;
			case 'VBMENUTRACKINGS':
				$result = __('Statistics Tracking', 'vikbooking');
				break;
			case 'VBMAINTRACKINGSTITLE':
				$result = __('Vik Booking - Statistics Tracking', 'vikbooking');
				break;
			case 'VBNOTRACKINGS':
				$result = __('No data available.', 'vikbooking');
				break;
			case 'VBTRKLASTDT':
				$result = __('Last Visit', 'vikbooking');
				break;
			case 'VBTRKFIRSTDT':
				$result = __('First Visit', 'vikbooking');
				break;
			case 'VBTRKPUBLISHED':
				$result = __('Tracking Status', 'vikbooking');
				break;
			case 'VBTRKGEOINFO':
				$result = __('Geo Info', 'vikbooking');
				break;
			case 'VBTRKMAKEAVAIL':
				$result = __('Enable tracking for this visitor', 'vikbooking');
				break;
			case 'VBTRKMAKENOTAVAIL':
				$result = __('Disable tracking for this visitor', 'vikbooking');
				break;
			case 'VBOANONYMOUS':
				$result = __('Anonymous', 'vikbooking');
				break;
			case 'VBTRKDEVICE':
				$result = __('Device', 'vikbooking');
				break;
			case 'VBTRKTRACKTIME':
				$result = __('Tracking Time', 'vikbooking');
				break;
			case 'VBTRKBOOKINGDATES':
				$result = __('Booking Dates', 'vikbooking');
				break;
			case 'VBTRKROOMSRATES':
				$result = __('Rooms and Rates', 'vikbooking');
				break;
			case 'VBTRKTGLPUBLISHED':
				$result = __('Invert Tracking Status', 'vikbooking');
				break;
			case 'VBTRKFILTTRKDATES':
				$result = __('Tracking Dates', 'vikbooking');
				break;
			case 'VBTRKFILTRES':
				$result = __('Filter Results', 'vikbooking');
				break;
			case 'VBCOUNTRYFILTER':
				$result = __('Filter by Country', 'vikbooking');
				break;
			case 'VBTRKDIFFSECS':
				$result = __('seconds', 'vikbooking');
				break;
			case 'VBTRKDIFFMINS':
				$result = __('minutes', 'vikbooking');
				break;
			case 'VBTRKSETTINGS':
				$result = __('Tracking Settings', 'vikbooking');
				break;
			case 'VBTRKENABLED':
				$result = __('Tracking Enabled', 'vikbooking');
				break;
			case 'VBTRKDISABLED':
				$result = __('Tracking is disabled', 'vikbooking');
				break;
			case 'VBMAINTRKSETTSTITLE':
				$result = __('Vik Booking - Tracking Settings', 'vikbooking');
				break;
			case 'VBTRKCOOKIERFRDUR':
				$result = __('Referrer Cookie Duration', 'vikbooking');
				break;
			case 'VBTRKCOOKIERFRDURHELP':
				$result = __('The Referrer is the system that redirects/sends the visitor to your website. It could be a search engine, a social network or a marketing campaign. This value is not always available and it may be empty. The duration of the cookie defines for how long the visitor should be assigned to a certain referrer for any eventual conversion made after the first visit. The minimum duration should be greater than zero.', 'vikbooking');
				break;
			case 'VBTRKCAMPAIGNS':
				$result = __('Tracking Campaigns', 'vikbooking');
				break;
			case 'VBTRKCAMPAIGNSHELP':
				$result = __('You can add custom tracking campaigns to obtain a specific referrer for each tracking. This is useful to keep track of the provenience of a specific visitor. For example, if you are sending newsletters or marketing emails to your customers, you can include such instructions in the links and track who clicked on them and then searched for a room. The same function can be used to track other kind of marketing campaigns, such as ones from social networks or analytics techniques used by search engines. To set up a custom campaign rule you need to specify a request key (make sure to not use preserved request keys, numbers are always better), optionally a request value fort the key, and the name of the campaign that will be used as referrer for the trackings.', 'vikbooking');
				break;
			case 'VBTRKADDCAMPAIGN':
				$result = __('Add Campaign', 'vikbooking');
				break;
			case 'VBTRKCAMPAIGNKEY':
				$result = __('Request Key', 'vikbooking');
				break;
			case 'VBTRKCAMPAIGNVAL':
				$result = __('Request Key Value', 'vikbooking');
				break;
			case 'VBTRKCAMPAIGNNAME':
				$result = __('Referrer Name', 'vikbooking');
				break;
			case 'VBREFERRERFILTER':
				$result = __('Filter by Referrer', 'vikbooking');
				break;
			case 'VBTRKBOOKCONV':
				$result = __('Booking Conversion', 'vikbooking');
				break;
			case 'VBTRKREFERRER':
				$result = __('Referrer', 'vikbooking');
				break;
			case 'VBTRKVISITORS':
				$result = __('Visitors', 'vikbooking');
				break;
			case 'VBTRKCONVRATES':
				$result = __('Conversion Rates', 'vikbooking');
				break;
			case 'VBTRKREQSNUM':
				$result = __('Request(s)', 'vikbooking');
				break;
			case 'VBTRKVISSNUM':
				$result = __('Visitor(s)', 'vikbooking');
				break;
			case 'VBTRKMOSTDEMNIGHTS':
				$result = __('Most Demanded Nights', 'vikbooking');
				break;
			case 'VBTRKAVGVALS':
				$result = __('Average Values', 'vikbooking');
				break;
			case 'VBTRKTOTVISS':
				$result = __('Total Visitors', 'vikbooking');
				break;
			case 'VBTRKAVGCONVRATE':
				$result = __('Average Conversion Rate', 'vikbooking');
				break;
			case 'VBTRKAVGCONVRATEHELP':
				$result = __('This percentage value is calculated proportionally by taking into account the total number of visitors and the total numbers of bookings. It shows how many visitors completed the reservation process by generating a booking.', 'vikbooking');
				break;
			case 'VBTRKAVGLOS':
				$result = __('Average Nights of Stay', 'vikbooking');
				break;
			case 'VBTRKBESTREFERRERS':
				$result = __('Best Referrers', 'vikbooking');
				break;
			case 'VBTRKCOOKIEEXPL':
				$result = __('The Statistics Tracking functions use cookies to store information about the visitor\'s fingerprint and referrer. These cookies are sent to the visitors with the sole purpose of knowing the dates/rooms/rate plans they search on this website. The cookies do not contain any personal information. By default, such cookies are not shared with any third party system. It may be necessary to inform your visitors of the usage you make of these internal tracking cookies.', 'vikbooking');
				break;
			case 'VBOMINIMUMSTAY':
				$result = __('Minimum Stay', 'vikbooking');
				break;
			case 'VBOMINIMUMSTAYSET':
				$result = __('Set Minimum Stay', 'vikbooking');
				break;
			case 'VBORPHANSFOUND':
				$result = __('Orphan dates found', 'vikbooking');
				break;
			case 'VBORPHANSFOUNDSHELP':
				$result = __('The orphan dates are nights that cannot be booked by any customer due to some booking restrictions. If you are willing to sell those nights, then you should adjust the minimum number of nights of stay to allow bookings.', 'vikbooking');
				break;
			case 'VBOBTNKEEPREMIND':
				$result = __('Okay, keep reminding.', 'vikbooking');
				break;
			case 'VBOBTNDONTREMIND':
				$result = __('Okay, do not remind again.', 'vikbooking');
				break;
			case 'VBORPHANSCHECKBTN':
				$result = __('Check Orphans', 'vikbooking');
				break;
			case 'VBMENUTABLEAUX':
				$result = __('Tableaux', 'vikbooking');
				break;
			case 'VBMAINTABLEAUXTITLE':
				$result = __('Vik Booking - Tableaux', 'vikbooking');
				break;
			case 'VBMENUOPERATORS':
				$result = __('Operators', 'vikbooking');
				break;
			case 'VBMENUOPERATORSTITLE':
				$result = __('Vik Booking - Operators', 'vikbooking');
				break;
			case 'VBNOOPERATORS':
				$result = __('No Operators found', 'vikbooking');
				break;
			case 'VBOCODEOPERATOR':
				$result = __('Authentication Code', 'vikbooking');
				break;
			case 'VBOCODEOPERATORSHELP':
				$result = __('Operators can log in through their authentication code or through their website account if you select a user. You can combine the login methods or you can set just one.', 'vikbooking');
				break;
			case 'VBMAINMANAGEOPERATORTITLE':
				$result = __('Vik Booking - Operator Details', 'vikbooking');
				break;
			case 'VBOOPERATORDETS':
				$result = __('Operator Details', 'vikbooking');
				break;
			case 'VBOCODEOPERATORGEN':
				$result = __('Generate Code', 'vikbooking');
				break;
			case 'VBOOPERATORPERMS':
				$result = __('Permissions', 'vikbooking');
				break;
			case 'VBOOPERPERMTABLEAUX':
				$result = __('Tableaux', 'vikbooking');
				break;
			case 'VBERROPERATOREXISTS':
				$result = __('Operator with the same details already exists', 'vikbooking');
				break;
			case 'VBOPERATORSAVED':
				$result = __('Operator Saved', 'vikbooking');
				break;
			case 'VBERROPERATORDATA':
				$result = __('Name, eMail and Authentication Code or Website User are mandatory fields', 'vikbooking');
				break;
			case 'VBOPERMSOPERATORS':
				$result = __('Operators Permissions', 'vikbooking');
				break;
			case 'VBOPERMSOPERATORSSHELP':
				$result = __('This window lets you manage the permissions of the various operators for accessing the Tableaux via front-end.', 'vikbooking');
				break;
			case 'VBOOPERATOR':
				$result = __('Operator', 'vikbooking');
				break;
			case 'VBOPERMTBLXDAYS':
				$result = __('Visible Days', 'vikbooking');
				break;
			case 'VBOPERMTBLXROOMS':
				$result = __('Visible Rooms', 'vikbooking');
				break;
			case 'VBOADDOPERATORPERM':
				$result = __('Add operator permission', 'vikbooking');
				break;
			case 'VBOPERMSUPDOPEROK':
				$result = __('Permissions Updated', 'vikbooking');
				break;
			case 'VBMENUEINVOICING':
				$result = __('e-Invoicing', 'vikbooking');
				break;
			case 'VBMAINEINVOICINGTITLE':
				$result = __('Vik Booking - e-Invoicing', 'vikbooking');
				break;
			case 'VBODRIVERLOAD':
				$result = __('Load Data', 'vikbooking');
				break;
			case 'VBODRIVERSELECT':
				$result = __('- Select e-Invoicing Driver -', 'vikbooking');
				break;
			case 'VBODRIVERSETTINGS':
				$result = __('Driver Settings', 'vikbooking');
				break;
			case 'VBCUSTOMERFISCCODE':
				$result = __('Personal Number', 'vikbooking');
				break;
			case 'VBCUSTOMERFISCCODEHELP':
				$result = __('Depending on the country of residence of the customer, this can be their Personal Identification Number, the Fiscal Code, the Social Secutiry Number or the National Insurance Number. This code may be required for issuing invoices or electronic invoices.', 'vikbooking');
				break;
			case 'VBCUSTOMERPEC':
				$result = __('Certified eMail Address', 'vikbooking');
				break;
			case 'VBCUSTOMERPECHELP':
				$result = __('The Certified eMail Address may be required for issuing electronic invoices depending on your country.', 'vikbooking');
				break;
			case 'VBCUSTOMERRECIPCODE':
				$result = __('Company Identification Code', 'vikbooking');
				break;
			case 'VBCUSTOMERRECIPCODEHELP':
				$result = __('The Company Identification Code may be required for issuing electronic invoices depending on your country. It is different than the Company VAT ID, and can be left empty if not needed.', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFFLAG':
				$result = __('Type Flag', 'vikbooking');
				break;
			case 'VBNEWCUSTOMFFLAGHELP':
				$result = __('There are several sub-types of fields that tell the system what kind of information was collected from the customer. Choose the appropriate type and remember to only create one field of type eMail that will be used for the notifications.', 'vikbooking');
				break;
			case 'VBODRIVERGENERATEINVS':
				$result = __('Generate e-Invoices', 'vikbooking');
				break;
			case 'VBODRIVERSETTSUPD':
				$result = __('Settings saved!', 'vikbooking');
				break;
			case 'VBNEWOPTALWAYSAV':
				$result = __('Always available', 'vikbooking');
				break;
			case 'VBOPROMOVALIDITYHELP':
				$result = __('If this value is set to a number greater than zero, this promotion will be valid only for early bookings. If you need to apply the promotion only to those who book N days in advance, then you should set the number of days in advance from the apposite input field. Otherwise, you should keep this setting to 0. This setting is not for Last Minute promotions, but rather for Early Bird promotions.', 'vikbooking');
				break;
			case 'VBOPROMOLASTMINUTE':
				$result = __('Last Minute validity', 'vikbooking');
				break;
			case 'VBOPROMOLASTMINUTEHELP':
				$result = __('If you are willing to apply discounts only to last minute bookings, then you should provide a number of days and/or hours for the validity of the promotion. If the time remaining to the arrival from the booking date is less than the limit you defined, the promotion will be applied.', 'vikbooking');
				break;
			case 'VBOPTPCENTROOMFEE':
				$result = __('% room total fee', 'vikbooking');
				break;
			case 'VBOPTPCENTROOMFEEHELP':
				$result = __('The price of this service can be an absolute value in your currency, or a percentage value of the room final price comprehensive of any modifications for the occupancy. The percentage costs are usually needed in some countries to calculate special tourist tax or tourism levy.', 'vikbooking');
				break;
			case 'VBOCALCLOSEOTHERROOMS':
				$result = __('Close other rooms', 'vikbooking');
				break;
			case 'VBOCALCLOSEALLROOMSDT':
				$result = __('Close all other rooms', 'vikbooking');
				break;
			case 'VBOASSIGNNEWCUST':
				$result = __('Assign new customer', 'vikbooking');
				break;
			case 'VBOCREATENEWCUST':
				$result = __('Create new customer', 'vikbooking');
				break;
			case 'VBOASSIGNNEWCUSTCONF':
				$result = __('Do you want to assign this new customer to the booking?', 'vikbooking');
				break;
			case 'VBMAINMANAGEMANINVTITLE':
				$result = __('Vik Booking - Custom Invoice', 'vikbooking');
				break;
			case 'VBOMANUALINVOICE':
				$result = __('Custom invoice', 'vikbooking');
				break;
			case 'VBOMANINVDETAILS':
				$result = __('Invoice Details', 'vikbooking');
				break;
			case 'VBOMANINVSERVICES':
				$result = __('Services', 'vikbooking');
				break;
			case 'VBOMANINVSERVICENM':
				$result = __('Service Name', 'vikbooking');
				break;
			case 'VBOMANINVADDSRV':
				$result = __('Add service', 'vikbooking');
				break;
			case 'VBCONFIGCHATENABLED':
				$result = __('Chat Enabled', 'vikbooking');
				break;
			case 'VBCONFIGCHATENABLEDHELP':
				$result = __('The chatting system between the guests and the property managers can be enabled for the reservations only if the Channel Manager is installed.', 'vikbooking');
				break;
			case 'VBCONFIGCHATPARAMS':
				$result = __('Chat Params', 'vikbooking');
				break;
			case 'VBCONFIGCHATRESSTATALL':
				$result = __('Chat enabled for all reservations', 'vikbooking');
				break;
			case 'VBCONFIGCHATRESSTATCONF':
				$result = __('Chat enabled only for Confirmed reservations', 'vikbooking');
				break;
			case 'VBCONFIGCHATAVCHECKIN':
				$result = __('Chat is available after check-in for # days', 'vikbooking');
				break;
			case 'VBCONFIGCHATAVCHECKOUT':
				$result = __('Chat is available after check-out for # days', 'vikbooking');
				break;
			case 'VBOREPORTDAILYROOMREPORT':
				$result = __('Daily Room Report', 'vikbooking');
				break;
			case 'VBOTYPEARRIVAL':
				$result = __('Arrival', 'vikbooking');
				break;
			case 'VBOTYPEDEPARTURE':
				$result = __('Departure', 'vikbooking');
				break;
			case 'VBOTYPESTAYOVER':
				$result = __('Stayover', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTPU':
				$result = __('New amount paid', 'vikbooking');
				break;
			case 'VBOPREVAMOUNTPAID':
				$result = __('Previous amount paid %s', 'vikbooking');
				break;
			case 'VBOREPORTTRANSACTIONSREPORT':
				$result = __('Transactions Report', 'vikbooking');
				break;
			case 'VBCONFIGPRECHECKINENABLED':
				$result = __('Pre Check-in Enabled', 'vikbooking');
				break;
			case 'VBCONFIGPRECHECKINMIND':
				$result = __('Pre Check-in days in advance', 'vikbooking');
				break;
			case 'VBOCHECKEDSTATUSPRECHECKIN':
				$result = __('Pre Checked-in', 'vikbooking');
				break;
			case 'VBDASHLASTRES':
				$result = __('Latest Reservations', 'vikbooking');
				break;
			case 'VBNEWIVATAXCAP':
				$result = __('Tax cap', 'vikbooking');
				break;
			case 'VBNEWIVATAXCAPHELP':
				$result = __('A tax cap places an upper bound on the amount of government tax a company might be required to pay. In this case the tax is said to be capped. This function is only required for some countries, where there is a limit (maximum amount) of taxes that should be paid. Please ignore this setting if nothing similar applied to your country of residence.', 'vikbooking');
				break;
			case 'VBOFILTEISROPTIONAL':
				$result = __('optional', 'vikbooking');
				break;
			case 'VBOREPORTOCCUPANCYRANKING':
				$result = __('Occupancy Ranking', 'vikbooking');
				break;
			case 'VBOGROUPBY':
				$result = __('Group by', 'vikbooking');
				break;
			case 'VBOWEEK':
				$result = __('Week', 'vikbooking');
				break;
			case 'VBODAY':
				$result = __('Day', 'vikbooking');
				break;
			case 'VBOADDCUSTOMFESTTODAY':
				$result = __('Add new note for today', 'vikbooking');
				break;
			case 'VBOPAYMENTONLYNONREF':
				$result = __('Only for Non-Refundable Rates', 'vikbooking');
				break;
			case 'VBOPAYMENTONLYNONREFHELP':
				$result = __('If enabled, this payment method will be displayed only if at least one non-refundable rate plan is selected for the rooms reservation. If only refundable rate plans are selected for the rooms, then this payment option will not be available to complete the reservation.', 'vikbooking');
				break;
			case 'VBCONFIGATTACHICAL':
				$result = __('Attach iCal Reminder', 'vikbooking');
				break;
			case 'VBCONFIGATTACHICALHELP':
				$result = __('If enabled, a calendar reminder in iCal format will be attached to the confirmation email for the customer and/or the administrator. This is useful to save the event on any calendar application of any device.', 'vikbooking');
				break;
			case 'VBOGOTOOPPORTUNITIES':
				$result = __('Opportunities', 'vikbooking');
				break;
			case 'VBOCATEGORYFILTER':
				$result = __('Filter by Category', 'vikbooking');
				break;
			case 'VBCONFIGUPSELLINGENABLED':
				$result = __('Enable Upselling Extras', 'vikbooking');
				break;
			case 'VBCONFIGUPSELLINGENABLEDHELP':
				$result = __('If enabled, non-cancelled bookings with a check-out date in the future will allow the customers to add some options/extra services to their reservation. This way it will be possible for the customers to order extra services for the rooms booked and add them to their reservations. This is valid for all reservations, the ones coming from your website as well as the ones downloaded by the Channel Manager from the various OTAs.', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTUE':
				$result = __('New extras booked', 'vikbooking');
				break;
			case 'VBORESTRREPEATONWDAYS':
				$result = __('Repeat restriction every %s', 'vikbooking');
				break;
			case 'VBORESTRREPEATUNTIL':
				$result = __('Repeat until', 'vikbooking');
				break;
			case 'VBOADMINLEGENDDETAILS':
				$result = __('Details', 'vikbooking');
				break;
			case 'VBOADMINLEGENDSETTINGS':
				$result = __('Settings', 'vikbooking');
				break;
			case 'VBOMAILSUBJECT':
				$result = __('Your reservation at %s', 'vikbooking');
				break;
			case 'VBOWEBSITERATES':
				$result = __('Website Rates', 'vikbooking');
				break;
			case 'VBOROOMCALXREFSETTINGS':
				$result = __('Availability Calendar Settings', 'vikbooking');
				break;
			case 'VBOROOMSHARECALENDAR':
				$result = __('Share calendar with', 'vikbooking');
				break;
			case 'VBOROOMCALENDARSHAREDBY':
				$result = __('Calendar shared by', 'vikbooking');
				break;
			case 'VBOROOMSHARECALENDARHELP':
				$result = __('Do not make any selection if this has to be an independent room! This function is only useful for those working with certain types of room that can be booked in full or partially. For example, if you rent an apartment with 4 rooms in full, but you would also like to rent the 4 rooms separately, this function will help you sync the calendars of all rooms. In this example, by booking the apartment in full, the calendars linked for the 4 independent rooms will be reserved as well to sync the availability on the requested dates. In this same example, if one of the 4 rooms was booked, then the full apartment would no longer be available. Use this function properly to avoid problems with the remaining availability of your rooms linked.', 'vikbooking');
				break;
			case 'VBOCUSTOMERDOCUMENTS':
				$result = __('Customer Documents', 'vikbooking');
				break;
			case 'VBOMANUALUPLOAD':
				$result = __('Upload File', 'vikbooking');
				break;
			case 'VBODROPFILES':
				$result = __('or DRAG FILES HERE', 'vikbooking');
				break;
			case 'VBODROPFILESSTOPREMOVING':
				$result = __('Press ESC from keyboard to stop deleting the files', 'vikbooking');
				break;
			case 'VBODROPFILESHINT':
				$result = __('Drag & drop some documents here to upload them. It is possible to remove the documents by keeping them pressed.<br />You do not need to hit the "Save" button to keep the uploaded files.', 'vikbooking');
				break;
			case 'VBOPREVIEW':
				$result = __('Preview', 'vikbooking');
				break;
			case 'VBOWIZARDTARIFFSMESS':
				$result = __('Please specify the base-cost per night for each rate plan.', 'vikbooking');
				break;
			case 'VBOWIZARDTARIFFSHELP':
				$result = __('This should be the cost applied for the longer period of the year for the room\'s default occupancy. You will be able to set later the occupancy based pricing, as well as some seasonal pricing or different costs for some dates of the year.', 'vikbooking');
				break;
			case 'VBOWIZARDTARIFFSWHTC':
				$result = __('What\'s the starting cost per night for your room?', 'vikbooking');
				break;
			case 'VBOTOGGLEWIZARD':
				$result = __('Open Wizard', 'vikbooking');
				break;
			case 'VBOCHANNELS':
				$result = __('Channels', 'vikbooking');
				break;
			case 'VBOROOMCALENDARSHARED':
				$result = __('Availability calendar shared with other room types', 'vikbooking');
				break;
			case 'VBOSUBUNITOVERBOOKEDERR':
				$result = __('Warning - the sub-unit %s of the room %s has been overbooked from %s till %s by the booking ID %d. Please double check this reservation manually to assign a different sub-unit that was not booked yet. The main room has still got some free sub-units on these dates, and so the overbooking can be resolved manually.', 'vikbooking');
				break;
			case 'VBOSUBUNITOVERBOOKEDGOTO':
				$result = __('Adjust room sub-unit overbooked', 'vikbooking');
				break;
			case 'VBOROOMSUBUNITCHANGEFT':
				$result = __('Sub-unit changed for room %s from %s to %s', 'vikbooking');
				break;
			case 'VBOWIZARDRPLANSMESS':
				$result = __('The types of price are the rate plans that guests can choose for booking the rooms. They identify the costs, the cancellation policies and the services included.', 'vikbooking');
				break;
			case 'VBOWIZARDRPLANSMESS2':
				$result = __('Standard Rate and Non-refundable Rate are two common rate plans. However, depending on your business, you can choose to set up the types of price differently, for example Breakfast Only, Half-Board, Full-Board etc.. would be a valid configuration as well. <br/>This wizard will suggest to create some default rate plans. If they do not match your needs, feel free to close this window to create your type(s) of price manually, even just one.', 'vikbooking');
				break;
			case 'VBOSTANDARDRATE':
				$result = __('Standard Rate', 'vikbooking');
				break;
			case 'VBONONREFRATE':
				$result = __('Non-refundable Rate', 'vikbooking');
				break;
			case 'VBOSUGGRATEPLANS':
				$result = __('Suggested rate plans', 'vikbooking');
				break;
			case 'VBOCREATERATEPLANS':
				$result = __('Create Rate Plans', 'vikbooking');
				break;
			case 'VBOCLOSE':
				$result = __('Close', 'vikbooking');
				break;
			case 'VBOTOTALS':
				$result = __('Totals', 'vikbooking');
				break;
			case 'VBOREPORTTOTROOMSHELP':
				$result = __('Total sellable rooms: %d', 'vikbooking');
				break;
			case 'VBOSHEETNCHART':
				$result = __('Sheet + Chart', 'vikbooking');
				break;
			case 'VBOSHEETONLY':
				$result = __('Sheet', 'vikbooking');
				break;
			case 'VBOCHARTONLY':
				$result = __('Chart', 'vikbooking');
				break;
			case 'VBONEXTWEEKND':
				$result = __('Next weekend', 'vikbooking');
				break;
			case 'VBOROOMSOCCUPANCY':
				$result = __('Rooms Occupancy', 'vikbooking');
				break;
			case 'VBOROOMSUNSOLD':
				$result = __('Rooms Unsold', 'vikbooking');
				break;
			case 'VBOFORECAST':
				$result = __('Forecast', 'vikbooking');
				break;
			case 'VBOWEEKND':
				$result = __('Weekend', 'vikbooking');
				break;
			case 'VBOBOOKEDATPRICE':
				$result = __('Booked at', 'vikbooking');
				break;
			case 'VBOBOOKEDATPRICEHELP':
				$result = __('Keep the checkbox ticked to maintain the original room cost that was selected at the time of booking. If you untick the checkbox, the new price at today will be applied. Alternatively, you can choose to set a custom rate.', 'vikbooking');
				break;
			case 'VBOPARAMSUGGOCC':
				$result = __('Suggested occupancy', 'vikbooking');
				break;
			case 'VBODASHFIRSTSETUPROOMS':
				$result = __('Create some room-types to enable the booking process. Your rooms will have their own calendars for the availability and rates, and your guests will be booking the room-types you have set up.', 'vikbooking');
				break;
			case 'VBODASHFIRSTSETUPTARIFFS':
				$result = __('It is necessary to define the basic costs per night for each room for the various rate plans you have created. Other seasonal rates can be set up later from the page Rates Overview.', 'vikbooking');
				break;
			case 'VBODASHFIRSTSETUPSHORTCODES':
				$result = __('Shortcodes are necessary to display the contents of the plugin into the front-end section of your website. You should create some Shortcodes of various types, and use them onto some pages of your website to publish the desired contents.', 'vikbooking');
				break;
			case 'VBOCONFIGORPHANSCALCM':
				$result = __('Orphan dates calculation', 'vikbooking');
				break;
			case 'VBOCONFIGORPHANSCALCMHELP':
				$result = __('The dates displayed as &quot;orphans&quot; are those nights that cannot be booked due to some booking restrictions that force a minimum length of stay greater than 1 night. Such orphan dates can be calculated in two ways: by only checking the bookings for the dates ahead (in the future), or by also considering the free nights before a specific date. Choose your preferred calculation method to see warning messages about the various orphan dates found, in case you would like to reduce or remove the restrictions for the minimum stay.', 'vikbooking');
				break;
			case 'VBOCONFIGORPHANSCALCMN':
				$result = __('Free nights ahead', 'vikbooking');
				break;
			case 'VBOCONFIGORPHANSCALCMPN':
				$result = __('Free nights ahead and back', 'vikbooking');
				break;
			case 'VBOCONFIGSEARCHRESTPL':
				$result = __('Multiple rooms booking layout', 'vikbooking');
				break;
			case 'VBOCONFIGSEARCHRESTPLCL':
				$result = __('Classic', 'vikbooking');
				break;
			case 'VBOCONFIGSEARCHRESTPLCM':
				$result = __('Compact', 'vikbooking');
				break;
			case 'VBOSPWDAYSHELP':
				$result = __('Selecting no week days equals to selecting all 7 week days', 'vikbooking');
				break;
			case 'VBOSPNAMEHELP':
				$result = __('The name of this pricing rule. Visible only if &quot;Promotion&quot; enabled. Can be left empty', 'vikbooking');
				break;
			case 'VBOSPYEARTIEDHELP':
				$result = __('If disabled, the pricing rule will be applied on the selected range of dates regardless of the year', 'vikbooking');
				break;
			case 'VBOSPONLCKINHELP':
				$result = __('If enabled, the rule will be applied only if the check-in date for the stay is included in the range of dates', 'vikbooking');
				break;
			case 'VBOSPTPROMOHELP':
				$result = __('Make this pricing rule a &quot;Promotion&quot; to display it in the front-end booking process', 'vikbooking');
				break;
			case 'VBOPROMOCHANNELSHELP':
				$result = __('Select some of the active channels supporting promotions to create it also there', 'vikbooking');
				break;
			case 'VBOPROMOTEXTHELP':
				$result = __('The (optional) information/description text of your promotion. Visible only on your website', 'vikbooking');
				break;
			case 'VBOPROMOWARNNODATES':
				$result = __('A range of dates is mandatory to create a promotion', 'vikbooking');
				break;
			case 'VBOCHPROMOSUCCESS':
				$result = __('Promotion created successfully', 'vikbooking');
				break;
			case 'VBOSUGGCREATEPROMOOCC':
				$result = __('Create Promotion', 'vikbooking');
				break;
			case 'VBOINSIGHT':
				$result = __('Insight', 'vikbooking');
				break;
			case 'VBOSEVLOWOCC':
				$result = __('Your occupancy is particularly low on these dates.', 'vikbooking');
				break;
			case 'VBOSEVMEDOCC':
				$result = __('You\'ve got a medium occupancy on these dates.', 'vikbooking');
				break;
			case 'VBOSEVHIGHOCC':
				$result = __('Your occupancy is pretty high on these dates.', 'vikbooking');
				break;
			case 'VBOSEVLOWINDAYS':
				$result = __('Since these dates are very near, you could create a last-minute promotion to offer a discount to max out your occupancy.', 'vikbooking');
				break;
			case 'VBOSEVMEDINDAYS':
				$result = __('Since these dates are not so close and not so far, you could create a basic promotion to attract more guests.', 'vikbooking');
				break;
			case 'VBOSEVHIGHINDAYS':
				$result = __('Since these dates are pretty far ahead, you could create an early-booker promotion to fill your rooms earlier.', 'vikbooking');
				break;
			case 'VBOPANELREVIEWS':
				$result = __('Guest Reviews', 'vikbooking');
				break;
			case 'VBOGUESTREVVCMNOT':
				$result = __('Vik Channel Manager is not installed. The Guest Reviews are handled by the Channel Manager, which must be installed on your website and configured with at least one booking channel.', 'vikbooking');
				break;
			case 'VBOCONFIGGRENABLE':
				$result = __('Enable Guest Reviews', 'vikbooking');
				break;
			case 'VBOCONFIGGRENABLEHELP':
				$result = __('If enabled, guests will be able to review their stay at your property starting from their check-out date. Reviews can be left by visiting the front-end booking details page. Only one review per reservation is allowed.', 'vikbooking');
				break;
			case 'VBOCONFIGGRMINCHARS':
				$result = __('Review message minimum chars', 'vikbooking');
				break;
			case 'VBOCONFIGGRMINCHARSHELP':
				$result = __('A minimum number of chars required for submitting the review. Set it to 0 to disable any limit. Set it to -1 for not requesting any comment', 'vikbooking');
				break;
			case 'VBOCONFIGGRAPPROVAL':
				$result = __('Reviews approval', 'vikbooking');
				break;
			case 'VBOCONFIGGRAPPRAUTO':
				$result = __('Automatic approval', 'vikbooking');
				break;
			case 'VBOCONFIGGRAPPRMANUAL':
				$result = __('Manual approval', 'vikbooking');
				break;
			case 'VBOCONFIGGRAPPROVALHELP':
				$result = __('If set to automatic, all reviews will be instantly published on any section of your website where those are displayed. Alternatively, you can choose to manually approve any new review', 'vikbooking');
				break;
			case 'VBOCONFIGGRTYPE':
				$result = __('Review type', 'vikbooking');
				break;
			case 'VBOCONFIGGRTYPEGLOBAL':
				$result = __('Global rating', 'vikbooking');
				break;
			case 'VBOCONFIGGRTYPESERVICE':
				$result = __('Rating per service', 'vikbooking');
				break;
			case 'VBOCONFIGGRSERVICES':
				$result = __('Services to be rated', 'vikbooking');
				break;
			case 'VBOGREVVALUE':
				$result = __('Value for money', 'vikbooking');
				break;
			case 'VBOGREVLOCATION':
				$result = __('Location', 'vikbooking');
				break;
			case 'VBOGREVSTAFF':
				$result = __('Staff', 'vikbooking');
				break;
			case 'VBOGREVCLEAN':
				$result = __('Clean', 'vikbooking');
				break;
			case 'VBOGREVCOMFORT':
				$result = __('Comfort', 'vikbooking');
				break;
			case 'VBOGREVFACILITIES':
				$result = __('Facilities', 'vikbooking');
				break;
			case 'VBOGUESTREVSVCMREQ':
				$result = __('Guest reviews require the Channel Manager to be installed and configured on your website.', 'vikbooking');
				break;
			case 'VBOSEEGUESTREVIEW':
				$result = __('See guest review', 'vikbooking');
				break;
			case 'VBOCRONREMTYPEREVIEW':
				$result = __('Leave a review', 'vikbooking');
				break;
			case 'VBOREPORTRPLANSREVENUE':
				$result = __('Rate Plans Revenue', 'vikbooking');
				break;
			case 'VBOROVERVOBP':
				$result = __('Occupancy Based Pricing Rules', 'vikbooking');
				break;
			case 'VBOCONSECUTIVEDAYS':
				$result = __('Consecutive days', 'vikbooking');
				break;
			case 'VBOMINGUESTSFILT':
				$result = __('Minimum guests filtering', 'vikbooking');
				break;
			case 'VBOIFGUESTSMORETHAN':
				$result = __('Apply only if guests are more than', 'vikbooking');
				break;
			case 'VBOIFGUESTSLESSTHAN':
				$result = __('Apply only if guests are less than', 'vikbooking');
				break;
			case 'VBOMINMAXGUESTSOPTCONFL1':
				$result = __('Minimum guests should always be less than maximum guests. You have a conflict with the configuration of your room option, which would never make it appear. Please resolve it by amending the minimum guests filtering.', 'vikbooking');
				break;
			case 'VBOMINMAXGUESTSOPTCONFL2':
				$result = __('You have a conflict between the minimum and maximum guests in the configuration of your room option, which would never make it appear. A difference of at least 2 guests is necessary between the minimum and maximum guests filtering.', 'vikbooking');
				break;
			case 'VBOROOMSASSIGNED':
				$result = __('Rooms assigned', 'vikbooking');
				break;
			case 'VBOMINGUESTSFILTHELP':
				$result = __('Applying this option only when there is a minimum number of guests may alterate the calculation of the costs per children based on their age. For example, if the minimum number of guests is set to greater than 4, by booking a room for 2 adults and 3 children this option/extra cost will be displayed as we have 5 guests in total. However, the first 4 guests will be excluded from the calculation, and so in this example only the fifth guest, which is one child, will be asked to pay. The first and the second child will not pay for any age interval, only the third child will pay the regular fees per age. This is useful to apply extra costs only for a specific number/combination of guests. The filter can be combined with the maximum number of guests, or you can leave it empty as 0.', 'vikbooking');
				break;
			case 'VBONEWOPTISDMGDEP':
				$result = __('It\'s a damage deposit', 'vikbooking');
				break;
			case 'VBOGUESTMISCONDUCT':
				$result = __('Guest misconduct', 'vikbooking');
				break;
			case 'VBOREPORTMISCONDUCT':
				$result = __('Report misconduct', 'vikbooking');
				break;
			case 'VBOREPGUESTMISCONDUCTTO':
				$result = __('Report Guest Misconduct to %s', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTGM':
				$result = __('Guest misconduct report', 'vikbooking');
				break;
			case 'VBOTRANSMITDMGDEPTOOTA':
				$result = __('Transmit damage deposit to %s', 'vikbooking');
				break;
			case 'VBOTRANSMITDMGDEPLASTDT':
				$result = __('Last transmission date of this damage deposit: %s', 'vikbooking');
				break;
			case 'VBOCARATIMGORICON':
				$result = __('Image or font-icon', 'vikbooking');
				break;
			case 'VBOCARATIMGORICONHELP':
				$result = __('Characteristics are better displayed with an image or with a font icon. You should choose either to upload a custom image file or to use a font icon. Font icons are scalable, and well displayed on any kind of device, so they are usually better than custom images. When creating a new characteristic, you can select one font icon from the pre-set of icons, or you can always specify your custom HTML code to render the icon.', 'vikbooking');
				break;
			case 'VBOPOSITIONORDERING':
				$result = __('Ordering position', 'vikbooking');
				break;
			case 'VBOPOSITIONORDERINGHELP':
				$result = __('Leave this field empty for letting the system calculate the ordering position automatically', 'vikbooking');
				break;
			case 'VBOFONTICNSPREINST':
				$result = __('Font icons pre-installed', 'vikbooking');
				break;
			case 'VBOPROMOONFINALPRICE':
				$result = __('Apply on rooms final cost', 'vikbooking');
				break;
			case 'VBOPROMOONFINALPRICEHELP':
				$result = __('This setting will determine how the promotion will be applied onto the rooms costs', 'vikbooking');
				break;
			case 'VBOPROMOONFINALPRICETXT':
				$result = __('All special pricing rules are applied on the rooms base costs as a cumulative charge or discount even in case of multiple rules applied on the same stay dates. This algorithm follows the OpenTravel (OTA) standards, and here is an example of how two special pricing rules are typically applied on the bases costs to obtain the final price:<br/><br/><ul><li>Room-type base cost = 80/night</li><li>Booking for 3 nights</li><li>One Special Price sets a charge of 20/night to obtain a cost of 100/night</li><li>One Last-Minute promotion applies a 10% off</li></ul><br/><strong>Calculation of final price</strong><br/><ul><li>1st night (80 + 20 - 8) = 92</li><li>2nd night (80 + 20 - 8) = 92</li><li>3rd night (80 + 20 - 8) = 92</li><li><u>Final price</u> 92 * 3 = 276</li></ul><br/>With this default calculation method, the 10% off promotion has been applied cumulatively on the room base cost for each night affected.<br/>If the parameter <i>Apply on rooms final cost</i> was enabled, the calculation would be performed with the following method:<br/><ul><li>1st night (80 + 20) = 100</li><li>2nd night (80 + 20) = 100</li><li>3rd night (80 + 20) = 100</li><li><u>Final price before promotion</u> 100 * 3 = 300</li><li><u>Promotion applied on final cost</u> 300 - 10% = 270</li></ul><br/>You should choose the calculation method that best fits your needs. Applying promotions on the final price for specific dates is usually more handy, but you can choose to adopt the default calculation method like for all the other special pricing rules.', 'vikbooking');
				break;
			case 'VBOCOUPONEXCLTAX':
				$result = __('Exclude Taxes/Fees', 'vikbooking');
				break;
			case 'VBOCOUPONEXCLTAXHELP':
				$result = __('If enabled, any percent coupon code will not discount taxes or mandatory fees/services. If you disable it, the coupon code will be applied on the entire reservation amount.', 'vikbooking');
				break;
			case 'VBOUNTIL':
				$result = __('Until', 'vikbooking');
				break;
			case 'VBONOTESFORROOM':
				$result = __('Notes for this room', 'vikbooking');
				break;
			case 'VBCUSTOMERDOCISSUE':
				$result = __('Document Issue Date', 'vikbooking');
				break;
			case 'VBCUSTOMERNATION':
				$result = __('Nationality', 'vikbooking');
				break;
			case 'VBOREPORTERRNODATA':
				$result = __('Data Missing! Please insert the required data to proceed.', 'vikbooking');
				break;
			case 'VBOPREFCOUNTRIESORD':
				$result = __('Preferred Countries Ordering', 'vikbooking');
				break;
			case 'VBOPREFCOUNTRIESORDHELP':
				$result = __('The Preferred Countries are used to build input fields to collect phone numbers. These countries are taken from the installed languages on your website, and they will be used to display some countries at the top of the list next to each input field of type phone number. To add custom countries or to remove some, click the edit icon and enter the comma separated alpha-2 country codes (ISO 3166-1).', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTEC':
				$result = __('Cancellation Email Sent', 'vikbooking');
				break;
			case 'VBO_WIDGETS_WELCOME':
				$result = __('Admin Widgets Customizer', 'vikbooking');
				break;
			case 'VBO_WIDGETS_WELCOME_DESC1':
				$result = __('<strong>Welcome to the Admin Widgets Customizer!</strong><br/>This interface will let you manage all the elements displayed in your Dashboard through <b>Sections</b>, <b>Containers</b> and <b>Widgets</b>.', 'vikbooking');
				break;
			case 'VBO_WIDGETS_WELCOME_DESC2':
				$result = __('Add new <b>Sections</b> to better organize your elements, and arrange their position with drag and drop actions. Add new <b>Containers</b> with a proper size to contain your <b>widgets</b>.<br/>Four sizes are available for the containers:<ul><li><b>Full Width</b> gives a <u>100%</u> width to the container. Only one container of this type will fit in one row.</li><li><b>Large</b> gives the container a <u>75%</u> width. Another small container could fit in the same row next to this size.</li><li><b>Medium</b> gives the container a <u>50%</u> width. Another medium container could fit next to it, or even two small containers.</li><li><b>Small</b> gives the container a width of <u>25%</u>. One row could fit up to 4 containers of this size.</li></ul>', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CUSTWIDGETS':
				$result = __('Customize Widgets', 'vikbooking');
				break;
			case 'VBO_WIDGETS_AUTOSAVE':
				$result = __('Changes will be saved automatically', 'vikbooking');
				break;
			case 'VBO_WIDGETS_RESTDEFAULTSHORT':
				$result = __('Restore', 'vikbooking');
				break;
			case 'VBO_WIDGETS_RESTDEFAULT':
				$result = __('Restore Default Widgets Configuration', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CONTSIZE':
				$result = __('Container Size', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CONTFULL':
				$result = __('Full Width', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CONTLARGE':
				$result = __('Large', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CONTMEDIUM':
				$result = __('Medium', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CONTSMALL':
				$result = __('Small', 'vikbooking');
				break;
			case 'VBO_WIDGETS_ADDWIDGCONT':
				$result = __('Add Widgets Container', 'vikbooking');
				break;
			case 'VBO_WIDGETS_SELWIDGADD':
				$result = __('Choose the widget to add', 'vikbooking');
				break;
			case 'VBO_WIDGETS_ADDNEWWIDG':
				$result = __('Add New Widget', 'vikbooking');
				break;
			case 'VBO_WIDGETS_SAVINGMAP':
				$result = __('Saving the widgets map', 'vikbooking');
				break;
			case 'VBO_WIDGETS_ERRSAVINGMAP':
				$result = __('Could not update the map. Please try again', 'vikbooking');
				break;
			case 'VBO_WIDGETS_LASTUPD':
				$result = __('Last update', 'vikbooking');
				break;
			case 'VBO_WIDGETS_ENTERSECTNAME':
				$result = __('Please enter a name for the section', 'vikbooking');
				break;
			case 'VBO_WIDGETS_NEWSECT':
				$result = __('New Section', 'vikbooking');
				break;
			case 'VBO_WIDGETS_CONFRMELEM':
				$result = __('The selected element will be removed. Proceed?', 'vikbooking');
				break;
			case 'VBO_WIDGETS_SELCONTSIZE':
				$result = __('Please select the size of the container', 'vikbooking');
				break;
			case 'VBO_WIDGETS_UPDWIDGCONT':
				$result = __('Update Widgets Container', 'vikbooking');
				break;
			case 'VBO_WIDGETS_EDITWIDGCONT':
				$result = __('Edit Widgets Container', 'vikbooking');
				break;
			case 'VBO_WIDGETS_ERRDISPWIDG':
				$result = __('Could not display the widget. Try reloading the page', 'vikbooking');
				break;
			case 'VBO_W_STICKYN_TITLE':
				$result = __('Sticky Notes', 'vikbooking');
				break;
			case 'VBO_W_STICKYN_DESCR':
				$result = __('Write down something important for all your colleagues.', 'vikbooking');
				break;
			case 'VBO_STICKYN_TITLE':
				$result = __('Note Title', 'vikbooking');
				break;
			case 'VBO_STICKYN_TEXT':
				$result = __('Write here the text of the note.', 'vikbooking');
				break;
			case 'VBO_STICKYN_TEXT2':
				$result = __('Use <b>shortcuts</b> to <u>format</u> the <i>text</i>.', 'vikbooking');
				break;
			case 'VBO_STICKYN_CUSTOMURI':
				$result = __('Enter a custom URI for the link', 'vikbooking');
				break;
			case 'VBO_W_STICKYN_HELP_TITLE':
				$result = __('Notes text formatting', 'vikbooking');
				break;
			case 'VBO_W_STICKYN_HELP_DESCR':
				$result = __('You can use the following keyboard shortcuts while typing the text of your note:<br/><br/><b>Ctrl + B</b>: makes the text selection bold.<br/><b>Ctrl + I</b>: makes the text selection italic.<br/><b>Ctrl + U</b>: underlines the text selection.<br/><b>Ctrl + S</b>: strike-through the text selection.<br/><b>Ctrl + H/T</b>: makes the current block a title/heading.<br/><b>Ctrl + P</b>: makes the current block a paragraph.<br/><b>Ctrl + L</b>: adds a new bullet list, or converts the text selection to a link.<br/><b>Ctrl + O/N</b>: adds a new numbered list.<br/><b>Ctrl + A</b>: makes the whole text selected.<br/><b>Ctrl + M</b>: converts the markup text selection to HTML.', 'vikbooking');
				break;
			case 'VBO_W_STICKYN_HELP_DESCR_MAC':
				$result = __('You can use the following keyboard shortcuts while typing the text of your note:<br/><br/><b>&#8984; + B</b>: makes the text selection bold.<br/><b>&#8984; + I</b>: makes the text selection italic.<br/><b>&#8984; + U</b>: underlines the text selection.<br/><b>&#8984; + S</b>: strike-through the text selection.<br/><b>&#8984; + H/T</b>: makes the current block a title/heading.<br/><b>&#8984; + P</b>: makes the current block a paragraph.<br/><b>&#8984; + L</b>: adds a new bullet list, or converts the text selection to a link.<br/><b>&#8984; + O/N</b>: adds a new numbered list.<br/><b>&#8984; + A</b>: makes the whole text selected.<br/><b>Ctrl + M</b>: converts the markup text selection to HTML.<br/><br/>Some commands may also be executed by holding <kbd>Ctrl</kbd> rather than <kbd>&#8984;</kbd>.', 'vikbooking');
				break;
			case 'VBO_W_VISITCOUNT_TITLE':
				$result = __('Visitors Counter', 'vikbooking');
				break;
			case 'VBO_W_VISITCOUNT_DESCR':
				$result = __('Counts the daily visitors that looked for a room from your website', 'vikbooking');
				break;
			case 'VBO_W_VISITCOUNT_VTODAY':
				$result = __('Visitors today', 'vikbooking');
				break;
			case 'VBO_W_VISITCOUNT_VTMON':
				$result = __('Visitors this month', 'vikbooking');
				break;
			case 'VBO_W_VISITCOUNT_VLMON':
				$result = __('Visitors last month', 'vikbooking');
				break;
			case 'VBO_W_VISITCOUNT_VDIFF':
				$result = __('Turnout', 'vikbooking');
				break;
			case 'VBO_W_ARRIVETOD_TITLE':
				$result = __('Arriving Today', 'vikbooking');
				break;
			case 'VBO_W_ARRIVETOD_DESCR':
				$result = __('A list of reservations for guests arriving today', 'vikbooking');
				break;
			case 'VBO_W_DEPARTTOD_TITLE':
				$result = __('Departing Today', 'vikbooking');
				break;
			case 'VBO_W_DEPARTTOD_DESCR':
				$result = __('A list of reservations for guests departing today', 'vikbooking');
				break;
			case 'VBO_W_LASTRES_TITLE':
				$result = __('Last Reservations', 'vikbooking');
				break;
			case 'VBO_W_LASTRES_DESCR':
				$result = __('A list of the most recent reservations received', 'vikbooking');
				break;
			case 'VBO_W_NEXTRES_TITLE':
				$result = __('Next Bookings', 'vikbooking');
				break;
			case 'VBO_W_NEXTRES_DESCR':
				$result = __('A list of the upcoming arrivals', 'vikbooking');
				break;
			case 'VBO_W_ORPHDATES_TITLE':
				$result = __('Orphan Dates', 'vikbooking');
				break;
			case 'VBO_W_ORPHDATES_DESCR':
				$result = __('The future orphan dates found due to booking restrictions', 'vikbooking');
				break;
			case 'VBO_W_ROOMSLOCK_TITLE':
				$result = __('Rooms Locked', 'vikbooking');
				break;
			case 'VBO_W_ROOMSLOCK_DESCR':
				$result = __('The rooms temporary locked for bookings with pending payment', 'vikbooking');
				break;
			case 'VBO_W_TODROCC_DESCR':
				$result = __('A list of all occupied rooms will be displayed with the customer details. Useful to identify in which room a guest is staying', 'vikbooking');
				break;
			case 'VBO_W_WEEKLYB_TITLE':
				$result = __('Weekly Bookings', 'vikbooking');
				break;
			case 'VBO_W_WEEKLYB_DESCR':
				$result = __('Various donut charts will display your weekly overall occupancy', 'vikbooking');
				break;
			case 'VBO_W_OCCFORECAST_TITLE':
				$result = __('Occupancy Forecast', 'vikbooking');
				break;
			case 'VBO_W_OCCFORECAST_DESCR':
				$result = __('Check your occupancy for the next festivities or custom dates', 'vikbooking');
				break;
			case 'VBO_COND_TEXT_MNG_TITLE':
				$result = __('Vik Booking - Conditional Text', 'vikbooking');
				break;
			case 'VBO_COND_TEXTS':
				$result = __('Conditional Texts', 'vikbooking');
				break;
			case 'VBO_COND_TEXT_RULES':
				$result = __('Conditional Text Rules', 'vikbooking');
				break;
			case 'VBO_NEW_COND_TEXT':
				$result = __('New Conditional Text', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_NAME':
				$result = __('Conditional Text Name', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_TKN':
				$result = __('Special Tag', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_TKN_HELP':
				$result = __('The special tag (token) is generated automatically from the name of the conditional text. You can use it on most messages to execute all rules.', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_ADDRULE':
				$result = __('Add Rule', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_MSG':
				$result = __('Conditional Message', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_MSG_HELP':
				$result = __('If you would like these conditional rules to produce a message, enter it here. Leave it empty otherwise.', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_DISABLED':
				$result = __('This rule is already being used', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_NORULES_SEL':
				$result = __('Please select one rule to add', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_RMCONF':
				$result = __('Do you want to remove this rule?', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_WARN_NORULES':
				$result = __('No rules defined to restrict the conditional text', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_SEPEMAIL':
				$result = __('You can separate multiple email addresses with the comma', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_BCCEMAIL':
				$result = __('Set as BCC', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_DEBUG_RULES':
				$result = __('Debug Rules', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_DEBUG_RULES_HELP':
				$result = __('If enabled, the special tags will debug the execution of the rules. Use it only for testing.', 'vikbooking');
				break;
			case 'VBO_DEBUG_RULE_CONDTEXT':
				$result = __('[Rule %s was not compliant. Special tag %s was not applied.]', 'vikbooking');
				break;
			case 'VBO_EXPAND':
				$result = __('Expand', 'vikbooking');
				break;
			case 'VBO_COLLAPSE':
				$result = __('Collapse', 'vikbooking');
				break;
			case 'VBO_TEMPLATE_FILES':
				$result = __('Template Files', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_TAG_ADD_HELP':
				$result = __('Select the position in the template file where you would like to add the special tag %s', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_TAG_RM_HELP':
				$result = __('Do you want to remove this tag from the template file %s? You will then be able to add it again to a different position.', 'vikbooking');
				break;
			case 'VBO_CSS_EDITING_HELP':
				$result = __('Click the various elements on the template to edit their CSS styling properties.', 'vikbooking');
				break;
			case 'VBO_INSPECTOR_START':
				$result = __('Customize colors and styles', 'vikbooking');
				break;
			case 'VBO_INSP_CSS_FONTCOLOR':
				$result = __('Font Color', 'vikbooking');
				break;
			case 'VBO_INSP_CSS_BACKGCOLOR':
				$result = __('Background Color', 'vikbooking');
				break;
			case 'VBO_INSP_HTML_TAG':
				$result = __('HTML Tag', 'vikbooking');
				break;
			case 'VBO_INSP_CSS_BORDER':
				$result = __('Border', 'vikbooking');
				break;
			case 'VBO_INSP_CSS_BORDERWIDTH':
				$result = __('Border Width', 'vikbooking');
				break;
			case 'VBO_INSP_CSS_BORDERCOLOR':
				$result = __('Border Color', 'vikbooking');
				break;
			case 'VBO_FILE_FROM_MEDIAMNG':
				$result = __('Media Manager', 'vikbooking');
				break;
			case 'VBO_FILE_FROM_LOCALDIR':
				$result = __('Full Path', 'vikbooking');
				break;
			case 'VBO_FILE_FROM_LOCALDIR_HELP':
				$result = __('Enter the full path to the local file starting with %s', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_RETCUST':
				$result = __('Returning Customer', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_DTA':
				$result = __('Days to arrival', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_COUNTRIES':
				$result = __('Countries', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_ATTFILES':
				$result = __('Attach Files', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_ATTFILES_DESCR':
				$result = __('Add file attachments to the email message', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_BOOKDATES':
				$result = __('Booking Dates', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_BOOKDATES_DESCR':
				$result = __('Restriction applied to certain booking dates', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_BOOKSTAT_DESCR':
				$result = __('Filter by reservation statuses', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_CHANNELS_DESCR':
				$result = __('Filter the reservations by source of provenience', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_COUNTRIES_DESCR':
				$result = __('Restriction applied to certain countries', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_DTA_DESCR':
				$result = __('Restriction for minimum days offset to arrival', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_EXTRAMAIL':
				$result = __('Extra Email', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_EXTRAMAIL_DESCR':
				$result = __('Add one or more email recipient addresses', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_LANG_DESCR':
				$result = __('Restriction for specific booking languages', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_NOG_DESCR':
				$result = __('Filter by number of guests', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_NON_DESCR':
				$result = __('Filter by number of nights', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_OPTS_DESCR':
				$result = __('Restriction applied to certain options/extras', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_PAYM_DESCR':
				$result = __('Filter by payment method', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_RPL_DESCR':
				$result = __('Restriction applied to certain rate plans', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_RETCUST_DESCR':
				$result = __('The same customer must have placed other reservations before', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_ROOMS_DESCR':
				$result = __('Restriction for specific rooms booked', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_STAYDATES':
				$result = __('Stay Dates', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_STAYDATES_DESCR':
				$result = __('Restriction applied to certain dates of stay', 'vikbooking');
				break;
			case 'VBO_EDITTPL_FATALERROR':
				$result = __('Your PHP version may not support these functions, or the source code of the template may not be compatible. Please edit the template file manually, any changes will be restored.', 'vikbooking');
				break;
			case 'VBO_GMAPS_APIKEY':
				$result = __('Google Maps API Key', 'vikbooking');
				break;
			case 'VBO_GEO_INFO':
				$result = __('Geographic Information', 'vikbooking');
				break;
			case 'VBO_GEO_UNSUPPORTED':
				$result = __('This function relies on the Google Maps APIs. Please enter your Google Maps API Key from the Configuration settings, then refresh this page.', 'vikbooking');
				break;
			case 'VBO_ENABLE_GEOCODING':
				$result = __('Enable Geocoding', 'vikbooking');
				break;
			case 'VBO_GEO_ADDRESS':
				$result = __('Base Address', 'vikbooking');
				break;
			case 'VBO_GEO_COORDS':
				$result = __('Center Coordinates', 'vikbooking');
				break;
			case 'VBO_GEO_COORDS_LAT':
				$result = __('Latitude', 'vikbooking');
				break;
			case 'VBO_GEO_COORDS_LNG':
				$result = __('Longitude', 'vikbooking');
				break;
			case 'VBO_GEO_ZOOM':
				$result = __('Zoom Level', 'vikbooking');
				break;
			case 'VBO_GEO_MTYPE':
				$result = __('Map Type', 'vikbooking');
				break;
			case 'VBO_GEO_MTYPE_ROADMAP':
				$result = __('Street', 'vikbooking');
				break;
			case 'VBO_GEO_MTYPE_SATELLITE':
				$result = __('Satellite', 'vikbooking');
				break;
			case 'VBO_GEO_MTYPE_HYBRID':
				$result = __('Hybrid', 'vikbooking');
				break;
			case 'VBO_GEO_MTYPE_TERRAIN':
				$result = __('Terrain', 'vikbooking');
				break;
			case 'VBO_GEO_MAP_HEIGHT':
				$result = __('Map Height', 'vikbooking');
				break;
			case 'VBO_GEO_MARKERS_ONEMULTI':
				$result = __('Map Markers', 'vikbooking');
				break;
			case 'VBO_GEO_MARKERS_ONEMULTI_HELP':
				$result = __('Choose to display in the map one marker for each sub-unit, in case your room-type has got more than one unit available located at different positions, or if one marker for the entire room-type is sufficient.', 'vikbooking');
				break;
			case 'VBO_GEO_MARKERS_ONE':
				$result = __('One per room-type', 'vikbooking');
				break;
			case 'VBO_GEO_MARKERS_MULTI':
				$result = __('One per sub-unit', 'vikbooking');
				break;
			case 'VBO_GEO_MARKERS_MULTI_ERRUNIT':
				$result = __('This room-type needs to have at least two units', 'vikbooking');
				break;
			case 'VBO_GEO_CUSTOMIZE_MARKER':
				$result = __('Customize Marker', 'vikbooking');
				break;
			case 'VBO_GEO_RMCONF_MARKER':
				$result = __('Do you want to remove the marker from the map?', 'vikbooking');
				break;
			case 'VBO_GEO_HIDE_FROM_MAP':
				$result = __('Hide from map', 'vikbooking');
				break;
			case 'VBO_GEO_MAP_GOVERLAY':
				$result = __('Map Ground Overlay', 'vikbooking');
				break;
			case 'VBO_GEO_MAP_GOVERLAY_HELP':
				$result = __('You can add an image overlay to the map, maybe to represent better a specific area of your property. This is useful for example to show the map of a camping site or a village/resort and to place the markers on their exact positions. You don\'t need this function if you simply want to show the location of your room-type.', 'vikbooking');
				break;
			case 'VBO_PLEASE_SELECT':
				$result = __('Please make a selection', 'vikbooking');
				break;
			case 'VBO_GEO_MAP_GOVERLAY_COORDS_HELP':
				$result = __('The ground overlay image requires four coordinates in order to draw a rectangle on the map by using your custom image. The rectangle is composed of two points, south-west and north-east. In order to define the best position for your ground overlay image, you will need to provide a coordinate for: the south point (first point latitude), the west point (first point longitude), the north point (second point latitude) and the east point (second point longitude). You can click the helper button to use the current map bounds as the bounds for your overlay image.', 'vikbooking');
				break;
			case 'VBO_GEO_MAP_COPYBOUNDS':
				$result = __('Use current map bounds', 'vikbooking');
				break;
			case 'VBO_GEO_IMPORT_FROM':
				$result = __('Import configuration', 'vikbooking');
				break;
			case 'VBO_GEO_IMPORT_CONF':
				$result = __('Do you want to import the geographic information?', 'vikbooking');
				break;
			case 'VBO_GEO_FAILED_REASON':
				$result = __('Geocoding failed for the following reason:', 'vikbooking');
				break;
			case 'VBO_PLEASE_FILL_FIELDS':
				$result = __('Please fill all required fields', 'vikbooking');
				break;
			case 'VBO_MARKER_ICN_TYPE':
				$result = __('Marker Icon Type', 'vikbooking');
				break;
			case 'VBO_MARKER_ICN_TYPE_GMAPS':
				$result = __('Google Maps Default Icon', 'vikbooking');
				break;
			case 'VBO_MARKER_ICN_TYPE_SVG':
				$result = __('SVG Icon', 'vikbooking');
				break;
			case 'VBO_MARKER_ICN_TYPE_IMG':
				$result = __('Image Icon', 'vikbooking');
				break;
			case 'VBO_ADD_NEW':
				$result = __('Add New', 'vikbooking');
				break;
			case 'VBO_NEW_SVG_ICN_NAME':
				$result = __('New SVG Icon Name', 'vikbooking');
				break;
			case 'VBO_SVG_PATH':
				$result = __('SVG Path', 'vikbooking');
				break;
			case 'VBO_SVG_PATH_HELP':
				$result = __('In order to add a new custom SVG icon it is necessary to copy its shape commands, usually contained inside the <strong>d</strong> attribute of the <strong>&lsaquo;path&rsaquo;</strong> tag. SVG icons have an HTML structure, and either if you download or generate a .svg file, by opening the file with a text-editor, you will be able to locate the shape commands inside the <strong>d</strong> attribute. This is an example of an HTML SVG tag:<br/>&lsaquo;svg&rsaquo;<br/>&nbsp;&nbsp;&lsaquo;path d=&quot;<strong>M150 0 L75 200 L225 200 Z</strong>&quot; /&rsaquo;<br/>&lsaquo;/svg&rsaquo;<br/>In this example, the path shape you want to copy and use in this tool is just <u><i>M150 0 L75 200 L225 200 Z</i></u>', 'vikbooking');
				break;
			case 'VBO_FILL_COLOR':
				$result = __('Fill Color', 'vikbooking');
				break;
			case 'VBO_OPACITY':
				$result = __('Opacity', 'vikbooking');
				break;
			case 'VBO_WIDTH':
				$result = __('Width', 'vikbooking');
				break;
			case 'VBO_HEIGHT':
				$result = __('Height', 'vikbooking');
				break;
			case 'VBO_COORD_SOUTH':
				$result = __('South Coordinate', 'vikbooking');
				break;
			case 'VBO_COORD_WEST':
				$result = __('West Coordinate', 'vikbooking');
				break;
			case 'VBO_COORD_NORTH':
				$result = __('North Coordinate', 'vikbooking');
				break;
			case 'VBO_COORD_EAST':
				$result = __('East Coordinate', 'vikbooking');
				break;
			case 'VBO_PREF_COLORS':
				$result = __('Preferred Colors', 'vikbooking');
				break;
			case 'VBO_PREF_COLORS_HELP':
				$result = __('Select your preferred colors to adjust the default front-end styles. This way, the look of the elements (titles, buttons, font color and backgrounds) of the various pages of the booking process can match with your Theme or Property design.', 'vikbooking');
				break;
			case 'VBO_PREF_COLOR_TEXTS':
				$result = __('Titles and Headings', 'vikbooking');
				break;
			case 'VBO_PREF_COLOR_BKGROUND':
				$result = __('Elements with backgrounds', 'vikbooking');
				break;
			case 'VBO_PREF_COLOR_BKGROUNDHOV':
				$result = __('Hovered elements', 'vikbooking');
				break;
			case 'VBO_BKGROUND_COL':
				$result = __('Background color', 'vikbooking');
				break;
			case 'VBO_FONT_COL':
				$result = __('Font color', 'vikbooking');
				break;
			case 'VBO_PREF_COLOR_EXAMPLERES':
				$result = __('Styles examples', 'vikbooking');
				break;
			case 'VBO_INTERACTIVE_MAP_BOOK':
				$result = __('Interactive Map Booking', 'vikbooking');
				break;
			case 'VBO_INTERACTIVE_MAP_BOOK_HELP':
				$result = __('If enabled, guests will be able to book their rooms directly through an interactive map based on Google Maps. All your room-types must have geographical information defined in order for this function to be available to guests.', 'vikbooking');
				break;
			case 'VBO_NO_EMPTY_DECIMALS':
				$result = __('No decimals if N.00', 'vikbooking');
				break;
			case 'VBO_FORCE_BOOKDATES':
				$result = __('Force Booking', 'vikbooking');
				break;
			case 'VBO_FORCE_BOOKDATES_HELP':
				$result = __('The room may not be available on the selected dates, and if you choose to force the booking, you may cause an overbooking. Forcing the reservation may be useful when you want to keep a room closed, but still be able to register reservations for those dates. The system will no longer check if the room is available on the selected dates, so be careful.', 'vikbooking');
				break;
			case 'VBO_FORCED_BOOKDATES':
				$result = __('Booking was forced due to non-availability', 'vikbooking');
				break;
			case 'VBO_BOOKING_SHOULDFORCE':
				$result = __('You can choose to force the booking to skip the availability check, but you may cause overbooking.', 'vikbooking');
				break;
			case 'VBO_PAYBUT_POS':
				$result = __('Payment &quot;button&quot; position', 'vikbooking');
				break;
			case 'VBO_PAYBUT_POS_TOP':
				$result = __('Top', 'vikbooking');
				break;
			case 'VBO_PAYBUT_POS_MIDDLE':
				$result = __('Middle', 'vikbooking');
				break;
			case 'VBO_PAYBUT_POS_BOTTOM':
				$result = __('Bottom', 'vikbooking');
				break;
			case 'VBO_PAYMET_LOGO':
				$result = __('Custom logo', 'vikbooking');
				break;
			case 'VBO_ISSUE_REFUND':
				$result = __('Issue a refund', 'vikbooking');
				break;
			case 'VBO_REFUND':
				$result = __('Refund', 'vikbooking');
				break;
			case 'VBO_AMOUNT_REFUNDED':
				$result = __('Amount Refunded', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRF':
				$result = __('Refund transaction', 'vikbooking');
				break;
			case 'VBO_REFUND_AMOUNT':
				$result = __('Refund amount', 'vikbooking');
				break;
			case 'VBO_REFUND_REASON':
				$result = __('Reason for refund', 'vikbooking');
				break;
			case 'VBO_DOREFUND_CONFIRM':
				$result = __('The amount will be refunded. Continue?', 'vikbooking');
				break;
			case 'VBO_REFUND_SUCCESS':
				$result = __('Refund transaction was successful', 'vikbooking');
				break;
			case 'VBO_STATUS_REFUNDED':
				$result = __('Refunded', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTRU':
				$result = __('Refunded amount updated', 'vikbooking');
				break;
			case 'VBO_NEWREFUND_AMOUNT':
				$result = __('Refunded amount changed from %s to %s', 'vikbooking');
				break;
			case 'VBO_CUSTOMER_UPLOAD_DOCS':
				$result = __('Upload Documents', 'vikbooking');
				break;
			case 'VBOCUSTPLACEBIRTH':
				$result = __('Place of birth', 'vikbooking');
				break;
			case 'VBOCUSTNATIONALITY':
				$result = __('Nationality', 'vikbooking');
				break;
			case 'VBOCUSTGENDER':
				$result = __('Gender', 'vikbooking');
				break;
			case 'VBOCUSTGENDERM':
				$result = __('Male', 'vikbooking');
				break;
			case 'VBOCUSTGENDERF':
				$result = __('Female', 'vikbooking');
				break;
			case 'VBOCUSTDOCTYPE':
				$result = __('ID Type', 'vikbooking');
				break;
			case 'VBOCUSTDOCNUM':
				$result = __('ID Number', 'vikbooking');
				break;
			case 'VBO_CUSTOMER_UPLOAD_DOCS':
				$result = __('Uploaded Documents', 'vikbooking');
				break;
			case 'VBO_TOT_REFUNDS':
				$result = __('Refunds', 'vikbooking');
				break;
			case 'VBO_AMOUNT_PAYABLE':
				$result = __('Amount Payable', 'vikbooking');
				break;
			case 'VBO_AMOUNT_PAYABLE_RQ':
				$result = __('Request Payment', 'vikbooking');
				break;
			case 'VBO_AMOUNT_PAYABLE_CONF':
				$result = __('The reservation will allow the payment to be made via front-end. Make sure to inform the customer by using the re-send email button.', 'vikbooking');
				break;
			case 'VBO_NEWPAYABLE_AMOUNT':
				$result = __('Reservation amount payable changed to %s', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTPB':
				$result = __('Payable amount updated', 'vikbooking');
				break;
			case 'VBO_GUEST_MESSAGING':
				$result = __('Guest Messaging', 'vikbooking');
				break;
			case 'VBO_PURGERM_RESERVATION':
				$result = __('Remove Reservation', 'vikbooking');
				break;
			case 'VBO_VCM_SPECIAL_OFFER':
				$result = __('%s - Special Offer', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTCM':
				$result = __('Channel Manager', 'vikbooking');
				break;
			case 'VBO_REVIEW_YOUR_GUEST':
				$result = __('Review your guest', 'vikbooking');
				break;
			case 'VBO_WRITE_REVIEW':
				$result = __('Write Review', 'vikbooking');
				break;
			case 'VBO_BTYPE_INQUIRY':
				$result = __('Inquiry', 'vikbooking');
				break;
			case 'VBO_BTYPE_REQUEST':
				$result = __('Request to book', 'vikbooking');
				break;
			case 'VBO_PREAPPROVE_INQUIRY':
				$result = __('Pre-approve', 'vikbooking');
				break;
			case 'VBO_WITHDRAW_PREAPPROVAL':
				$result = __('Withdraw pre-approval', 'vikbooking');
				break;
			case 'VBO_WITHDRAW_SPOFFER':
				$result = __('Withdraw special offer', 'vikbooking');
				break;
			case 'VBO_W_LATESTFROMGUESTS_TITLE':
				$result = __('Latest from your guests', 'vikbooking');
				break;
			case 'VBO_W_LATESTFROMGUESTS_DESCR':
				$result = __('Latest messages and reviews from your guests. Channel Manager required.', 'vikbooking');
				break;
			case 'VBO_CONDTEXT_RULE_WDAYS_DESCR':
				$result = __('Filter by check-in or check-out week day.', 'vikbooking');
				break;
			case 'VBO_APPEARANCE_PREF':
				$result = __('Appearance', 'vikbooking');
				break;
			case 'VBO_APPEARANCE_PREF_LIGHT':
				$result = __('Light', 'vikbooking');
				break;
			case 'VBO_APPEARANCE_PREF_AUTO':
				$result = __('Auto', 'vikbooking');
				break;
			case 'VBO_APPEARANCE_PREF_DARK':
				$result = __('Dark', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTPO':
				$result = __('Payout received', 'vikbooking');
				break;
			case 'VBO_MODE_TEXTHTML':
				$result = __('Text/HTML', 'vikbooking');
				break;
			case 'VBO_MODE_VISUAL':
				$result = __('Visual', 'vikbooking');
				break;
			case 'VBO_TPL_TEXT':
				$result = __('Text', 'vikbooking');
				break;
			case 'VBO_CRON_DADV_LOWER':
				$result = __('Include less days', 'vikbooking');
				break;
			case 'VBO_CRON_DADV_LOWER_HELP':
				$result = __('If type = Check-in and Days in advance greater than 1, by turning on this option the Cron Job will also include the reservations with a number of days to arrival (from the booking date) lower than the option days in advance. For example, if days in advance is set to 7, but a last-minute reservation is made 2 days before the arrival, with this setting enabled this reservation will be notified.', 'vikbooking');
				break;
			case 'VBO_COPY_ORIGINAL_TN':
				$result = __('Copy original', 'vikbooking');
				break;
			case 'VBOYESTERDAY':
				$result = __('Yesterday', 'vikbooking');
				break;
			case 'VBOTOMORROW':
				$result = __('Tomorrow', 'vikbooking');
				break;
			case 'VBOARRIVING':
				$result = __('Arriving', 'vikbooking');
				break;
			case 'VBODEPARTING':
				$result = __('Departing', 'vikbooking');
				break;
			case 'VBONOCHECKINSYESTERDAY':
				$result = __('No arrivals yesterday.', 'vikbooking');
				break;
			case 'VBONOCHECKOUTSYESTERDAY':
				$result = __('No departures yesterday.', 'vikbooking');
				break;
			case 'VBONOCHECKINSTOMORROW':
				$result = __('No arrivals tomorrow.', 'vikbooking');
				break;
			case 'VBONOCHECKOUTSTOMORROW':
				$result = __('No departures tomorrow.', 'vikbooking');
				break;
			case 'VBOREPORTRATESFLOW':
				$result = __('Rates Flow', 'vikbooking');
				break;
			case 'VBOCHANNEL':
				$result = __('Channel', 'vikbooking');
				break;
			case 'VBO_BASE_RATE':
				$result = __('Base Rate', 'vikbooking');
				break;
			case 'VBO_MOBILE_APP':
				$result = __('App', 'vikbooking');
				break;
			case 'VBO_W_RATESFLOW_DESCR':
				$result = __('The changes made to your room rates', 'vikbooking');
				break;
			case 'VBO_QUARTER':
				$result = __('Quarter', 'vikbooking');
				break;
			case 'VBO_W_LATESTEVS_TITLE':
				$result = __('Latest Events', 'vikbooking');
				break;
			case 'VBO_W_LATESTEVS_DESCR':
				$result = __('The most recent events for your bookings', 'vikbooking');
				break;
			case 'VBO_W_CHECKAV_TITLE':
				$result = __('Check Availability', 'vikbooking');
				break;
			case 'VBO_W_CHECKAV_DESCR':
				$result = __('Calculate the rates for the available rooms', 'vikbooking');
				break;
			case 'VBO_BOOKNOW':
				$result = __('Book Now', 'vikbooking');
				break;
			case 'VBO_SELECT':
				$result = __('Select', 'vikbooking');
				break;
			case 'VBO_ALT_STAY_DATES':
				$result = __('Alternative stay dates', 'vikbooking');
				break;
			case 'VBO_ALT_ROOM_PARTIES':
				$result = __('Alternative combinations of rooms', 'vikbooking');
				break;
			case 'VBO_AV_ECODE_3':
				$result = __('No rooms can allocate the provided number of guests.', 'vikbooking');
				break;
			case 'VBO_AV_ECODE_7':
				$result = __('No rooms available for the dates requested.', 'vikbooking');
				break;
			case 'VBO_CONFIG_BACKUP':
				$result = __('Backup', 'vikbooking');
				break;
			case 'VBO_CONFIG_BACKUP_TYPE':
				$result = __('Export Type', 'vikbooking');
				break;
			case 'VBO_CONFIG_BACKUP_FOLDER':
				$result = __('Folder Path', 'vikbooking');
				break;
			case 'VBO_CONFIG_BACKUP_FOLDER_HELP':
				$result = __('Enter here the path used to store the backup archives created by VikBooking. In case the folder does not exist, the system will attempt to create it. Installation base path: %s', 'vikbooking');
				break;
			case 'VBO_CONFIG_BACKUP_MANAGE_BTN':
				$result = __('Manage Backups', 'vikbooking');
				break;
			case 'VBMAINBACKUPSTITLE':
				$result = __('Vik Booking - Backup Archives', 'vikbooking');
				break;
			case 'VBOMAINTITLENEWBACKUP':
				$result = __('Vik Booking - New Backup', 'vikbooking');
				break;
			case 'VBO_BACKUP_SIZE':
				$result = __('File Size', 'vikbooking');
				break;
			case 'VBO_BACKUP_DOWNLOAOD':
				$result = __('Download', 'vikbooking');
				break;
			case 'VBOBACKUPRESTORECONF1':
				$result = __('Do you want to restore the program data with the selected backup?', 'vikbooking');
				break;
			case 'VBOBACKUPRESTORECONF2':
				$result = __('Confirm that you want to proceed one last time. This action cannot be undone.', 'vikbooking');
				break;
			case 'VBOBACKUPRESTORED':
				$result = __('The backup has been restored successfully!', 'vikbooking');
				break;
			case 'VBO_BACKUP_ACTION_LABEL':
				$result = __('Action', 'vikbooking');
				break;
			case 'VBO_BACKUP_ACTION_CREATE':
				$result = __('Create New', 'vikbooking');
				break;
			case 'VBO_BACKUP_ACTION_UPLOAD':
				$result = __('Upload Existing', 'vikbooking');
				break;
			case 'VBO_CRONJOB_BACKUP_CREATOR_DESCRIPTION':
				$result = __('Periodically creates a backup of the contents created through VikBooking.', 'vikbooking');
				break;
			case 'VBO_CRONJOB_BACKUP_CREATOR_FIELD_MAX':
				$result = __('Maximum Archives', 'vikbooking');
				break;
			case 'VBO_CRONJOB_BACKUP_CREATOR_FIELD_MAX_DESC':
				$result = __('Choose the maximum number of backup archives that can be created. When the specified threshold is reached, the system will automatically delete the oldest backup to allow the creation of a new one.', 'vikbooking');
				break;
			case 'VBO_BACKUP_EXPORT_TYPE_FULL':
				$result = _x('Full', 'Backup type: "FULL"', 'vikbooking');
				break;
			case 'VBO_BACKUP_EXPORT_TYPE_FULL_DESCRIPTION':
				$result = __('The backup will export all the contents created through VikBooking.', 'vikbooking');
				break;
			case 'VBO_BACKUP_EXPORT_TYPE_MANAGEMENT':
				$result = _x('Management', 'Backup export type: "MANAGEMENT"', 'vikbooking');
				break;
			case 'VBO_BACKUP_EXPORT_TYPE_MANAGEMENT_DESCRIPTION':
				$result = __('The backup will export only the contents used to set up the program. The records related to the customers, such as the bookings, will be completely ignored. This is useful to copy the configuration of this website into a new one.', 'vikbooking');
				break;
			case 'VBO_CONV_RES_OTA_CURRENCY':
				$result = __('Do you want to convert the amounts of this reservation from %s to %s?', 'vikbooking');
				break;
			case 'VBO_CONV_RES_OTA_CURRENCY_EXC':
				$result = __('%s is equal to %s', 'vikbooking');
				break;
			case 'VBO_CONV_RES_OTA_CURRENCY_APPLY':
				$result = __('Convert currency and amounts', 'vikbooking');
				break;
			case 'VBO_CONV_RES_OTA_CURRENCY_HISTORY':
				$result = __('Currency and amounts converted from %s to %s', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTIR':
				$result = __('Inquiry reservation', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_ALERT':
				$result = __('This is a pending inquiry reservation waiting to be confirmed, modified, cancelled, or paid.', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_AVTYPE_1':
				$result = __('The rooms were available for the requested dates and guests at the time of inquiry.', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_AVTYPE_2':
				$result = __('No valid rooms were available at the time of inquiry. Alternative and near dates with enough availability have been applied.', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_AVTYPE_3':
				$result = __('No available room could fit the requested number of guests. Alternative rooms and dates have been applied.', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_AVTYPE_4':
				$result = __('No alternative dates or rooms were found at the time of inquiry. The first room has been assigned to the reservation.', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_SUGG':
				$result = __('Adjust the reservation if needed, and get in touch with the guest for their booking request.', 'vikbooking');
				break;
			case 'VBO_WEB_INQUIRY_ORIGRQ':
				$result = __('Original information request', 'vikbooking');
				break;
			case 'VBO_INQUIRY_PENDING':
				$result = __('Pending inquiry', 'vikbooking');
				break;
			case 'VBO_CONF_CHECKIN_DATA':
				$result = __('Check-in data collection', 'vikbooking');
				break;
			case 'VBO_CONF_CHECKIN_DATA_BASIC':
				$result = __('Default adults information', 'vikbooking');
				break;
			case 'VBO_CONF_CHECKIN_DATA_CUSTOM':
				$result = __('Custom driver: %s', 'vikbooking');
				break;
			case 'VBO_SEARCH_ADMIN_WIDGETS':
				$result = __('Search widgets', 'vikbooking');
				break;
			case 'VBO_W_BOOKSCAL_TITLE':
				$result = __('Bookings Calendar', 'vikbooking');
				break;
			case 'VBO_W_BOOKSCAL_DESCR':
				$result = __('Room calendars to quickly navigate across monthly availability and reservations', 'vikbooking');
				break;
			case 'VBO_CRON_OTA_RES':
				$result = __('Channel Manager reservations', 'vikbooking');
				break;
			case 'VBO_CRON_OTA_RES_HELP':
				$result = __('Choose what to do with the Channel Manager (OTA) reservations. They can be included, excluded, or you can choose to include only the OTA reservations and exclude the ones coming from the website.', 'vikbooking');
				break;
			case 'VBO_INCLUDED':
				$result = __('Included', 'vikbooking');
				break;
			case 'VBO_INCLUDE_ONLY':
				$result = __('Include only OTA bookings', 'vikbooking');
				break;
			case 'VBO_EXCLUDED':
				$result = __('Excluded', 'vikbooking');
				break;
			case 'VBO_W_REMINDERS_TITLE':
				$result = __('Reminders', 'vikbooking');
				break;
			case 'VBO_W_REMINDERS_DESCR':
				$result = __('Custom reminders for scheduled notifications or expiring events.', 'vikbooking');
				break;
			case 'VBO_REMINDER_TITLE':
				$result = __('Reminder title', 'vikbooking');
				break;
			case 'VBO_REMINDER_DUE_DATE':
				$result = __('Due date', 'vikbooking');
				break;
			case 'VBO_REMINDER_TIME':
				$result = __('Time', 'vikbooking');
				break;
			case 'VBO_SHOW_COMPLETED':
				$result = __('Show completed', 'vikbooking');
				break;
			case 'VBO_SHOW_EXPIRED':
				$result = __('Show expired', 'vikbooking');
				break;
			case 'VBO_REL_EXP_FUTURE':
				$result = _x('in %s', 'Future reminder due date, like "in 1 month"', 'vikbooking');
				break;
			case 'VBO_REL_EXP_PAST':
				$result = _x('%s ago', 'Past reminder due date, like "1 month ago"', 'vikbooking');
				break;
			case 'VBO_ASSIGN_TO_BOOKING':
				$result = __('Assign to this booking', 'vikbooking');
				break;
			case 'VBO_BROWSER_NOTIFS_OFF':
				$result = __('Browser notifications are disabled', 'vikbooking');
				break;
			case 'VBO_BROWSER_NOTIFS_OFF_HELP':
				$result = __("Could not enable browser notifications.\nThis feature is available only in secure contexts (HTTPS).", 'vikbooking');
				break;
			case 'VBO_BROWSER_NOTIFS_ON':
				$result = __('Browser notifications are enabled!', 'vikbooking');
				break;
			case 'VBO_DEF_VALUE':
				$result = __('Default value', 'vikbooking');
				break;
			case 'VBO_COUNTRY_DEF_VALUE_HELP':
				$result = __('If you would like to pre-select a specific country by default, specify here the ISO 3166-1 Alpha-3 country code (3-char code)', 'vikbooking');
				break;
			case 'VBO_DATA_CALCULATION':
				$result = __('Data calculation', 'vikbooking');
				break;
			case 'VBO_DATA_CALCULATION_HELP':
				$result = __('Choose how the report should calculate the values. By default, the system will consider only the stayed nights in the range of dates used as filter. For example, if you are querying the report for a specific month, say March, a reservation with stay dates between March and April will have its values adjusted proportionally with the amounts for March. Instead, if you wish to base the data on the check-in date, maybe to get an idea of the effective incomes, in the above example a reservation with a check-out date exceeding the end-date in the filters will still be considered in full with no average calculation.', 'vikbooking');
				break;
			case 'VBO_STAYED_NIGHTS':
				$result = __('Stayed nights (average values)', 'vikbooking');
				break;
			case 'VBO_FULL_STAY_VALUES':
				$result = __('Full values from check-in', 'vikbooking');
				break;
			case 'VBO_INSERT_CONT_WRAPPER':
				$result = __('Insert content wrapper', 'vikbooking');
				break;
			case 'VBO_CONT_WRAPPER':
				$result = __('Content Wrapper', 'vikbooking');
				break;
			case 'VBO_CONT_WRAPPER_HELP':
				$result = __('When you insert a content wrapper to the body of the message, this will wrap your text within the default HTML layout. It is needed to beautify some contents of your email message.', 'vikbooking');
				break;
			case 'VBO_CUSTOMER_PROF_PIC':
				$result = __('Profile picture', 'vikbooking');
				break;
			case 'VBO_CUSTOMER_PROF_PIC_HELP':
				$result = __('An optional profile picture or logo of the customer. Can be a URL or an uploaded image.', 'vikbooking');
				break;
			case 'VBO_W_CURRCONV_TITLE':
				$result = __('Currency Converter', 'vikbooking');
				break;
			case 'VBO_W_CURRCONV_DESCR':
				$result = __('Exchange rates between currencies', 'vikbooking');
				break;
			case 'VBO_CONV_FROM':
				$result = __('Convert from', 'vikbooking');
				break;
			case 'VBO_CONV_TO':
				$result = __('Convert to', 'vikbooking');
				break;
			case 'VBO_CONV_RATE':
				$result = __('Conversion rate', 'vikbooking');
				break;
			case 'VBO_CRON_EMAIL_REMINDER_TITLE':
				$result = __('Reminder - Email', 'vikbooking');
				break;
			case 'VBO_CRON_PRECHECKIN_REMINDER_TITLE':
				$result = __('Reminder - Pre-checkin', 'vikbooking');
				break;
			case 'VBO_CRON_BACKUP_CREATOR_TITLE':
				$result = __('Backup - Creator', 'vikbooking');
				break;
			case 'VBO_CRON_SMS_REMINDER_TITLE':
				$result = __('Reminder - SMS', 'vikbooking');
				break;
			case 'VBO_CRON_INVOICES_GENERATOR_TITLE':
				$result = __('Invoices - Generator', 'vikbooking');
				break;
			case 'VBO_CRON_WEBHOOK_TITLE':
				$result = __('Webhook Notification', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_DESCRIPTION':
				$result = __('This cron job allows to set up automated actions whenever new events related to bookings take place. This is useful to post the changes to remote URLs or to pass the data to third party plugins.', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_TYPE_LABEL':
				$result = __('Notification type', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_TYPE_DESC':
				$result = __('Choose the type of webhook notification to trigger:<br/><ul><li><strong>URL</strong> POST request with a JSON payload to a remote URL.</li><li><strong>PHP Callback</strong> Invoke a custom PHP function or a class method.</li><li><strong>WordPress Action</strong> Calls a custom event (hook) by passing the payload to the action.</li></ul>', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_TYPE_URL_OPTION':
				$result = __('URL', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_TYPE_CALLBACK_OPTION':
				$result = __('PHP Callback', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_TYPE_ACTION_OPTION':
				$result = __('WordPress Action', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_HANDLER_LABEL':
				$result = __('Handler', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_HANDLER_DESC':
				$result = __('Depending on the type of webhook notification, enter the endpoint URL, the PHP callback (syntax: <code>function_name</code> or <code>object,method</code>) or the name of the WordPress hook.', 'vikbooking');
				break;
			case 'VBO_STATUS_PAID':
				$result = __('Paid', 'vikbooking');
				break;
			case 'VBO_STATUS_UNPAID':
				$result = __('Unpaid', 'vikbooking');
				break;
			case 'VBO_INV_TAX_SUMMARY':
				$result = __('Tax Summary', 'vikbooking');
				break;
			case 'VBO_INV_TAX_ALIQUOTE':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBO_INV_VATGST':
				$result = __('VAT/GST', 'vikbooking');
				break;
			case 'VBO_PREVIOUS_CHECKINS':
				$result = __('Previous registrations', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAYS':
				$result = __('Split stays', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAY':
				$result = __('Split stay', 'vikbooking');
				break;
			case 'VBO_BOOK_SPLIT_STAYS':
				$result = __('Booking split stays', 'vikbooking');
				break;
			case 'VBO_BOOK_SPLIT_STAYS_HELP':
				$result = __('Split stays is a feature that allows you to split longer stays between multiple room types. In case the requested dates are not available in one same room, guests can choose to book a split stay with different rooms. Some nights of the stay will be assigned to one available room, while the other nights will be assigned to different rooms. This feature aims to maximize your rooms occupancy.', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAYS_RATIO':
				$result = __('Nights/Transfers ratio', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAYS_RATIO_HELP':
				$result = __('The ratio will be used to determine whether booking a split stay should be suggested. Thanks to this value, the system will understand if too many room transfers, depending on the number of nights of stay, are being suggested to the customers to satisfy their request.', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAY_RATIO_TEST':
				$result = __('According to the current ratio (%s), a stay for %d night(s) will allow up to %d room transfer(s).', 'vikbooking');
				break;
			case 'VBO_MULTITASK_PANEL':
				$result = __('Multitask Panel', 'vikbooking');
				break;
			case 'VBO_BOOK_SPLIT_STAY_CANNOTDRAG':
				$result = __('The booking ID %d is a split stay reservation for multiple rooms, and so you cannot change dates to just one room. Use the apposite page to modify the reservation instead.', 'vikbooking');
				break;
			case 'VBO_MODIFY_DATES':
				$result = __('Modify dates', 'vikbooking');
				break;
			case 'VBO_MODIFY_DATES_HELP':
				$result = __('It is possible to modify the stay dates of some rooms when multiple rooms are booked with the same reservation. However, the selectable dates are constrained by the booking global check-in and check-out dates. This function is helpful in case the guests of a room will arrive some days later or will leave some days before. It is important to note that the sole goal of this function is to free up the availability of some rooms before or after the booking check-out or check-in dates. Defining a lower number of nights of stay for a room will NOT affect the pricing, which will still be based on the total number of nights of stay of the main reservation. If you wish to adjust the pricing when a room needs to change the dates of stay, then you should remove this room from the main reservation and manually create a new booking for this room, for the same customer, by selecting the proper check-in and check-out dates. You basically need to move the room from one reservation to another.', 'vikbooking');
				break;
			case 'VBO_STATE_PROVINCE':
				$result = __('State/Province', 'vikbooking');
				break;
			case 'VBO_3CHAR_CODE':
				$result = __('3-char code', 'vikbooking');
				break;
			case 'VBO_2CHAR_CODE':
				$result = __('2-char code', 'vikbooking');
				break;
			case 'VBMAINSTATESTITLE':
				$result = __('Vik Booking - States/Provinces', 'vikbooking');
				break;
			case 'VBO_MANAGE':
				$result = __('Manage', 'vikbooking');
				break;
			case 'VBO_NUMBER_USES':
				$result = __('Number of uses', 'vikbooking');
				break;
			case 'VBO_CUSTOMERS_ASSIGNED':
				$result = __('Customers assigned', 'vikbooking');
				break;
			case 'VBO_CUSTOMERS_ASSIGNED_HELP':
				$result = __('It is possible to limit the discount to just some customers.', 'vikbooking');
				break;
			case 'VBO_APPLY_AUTOMATICALLY':
				$result = __('Apply automatically', 'vikbooking');
				break;
			case 'VBO_APPLY_AUTOMATICALLY_HELP':
				$result = __('If enabled, the discount will be applied automatically when an eligible customer is recognized.', 'vikbooking');
				break;
			case 'VBO_SEARCHING':
				$result = __('Searching...', 'vikbooking');
				break;
			case 'VBO_ERR_LOAD_RESULTS':
				$result = __('An error occurred while loading the results', 'vikbooking');
				break;
			case 'VBO_EMPTY_DATA':
				$result = __('Empty data', 'vikbooking');
				break;
			case 'VBO_SET_STANDBY':
				$result = __('Set to Pending (Standby)', 'vikbooking');
				break;
			case 'VBO_W_GUESTMESSAGES_TITLE':
				$result = __('Guest messages', 'vikbooking');
				break;
			case 'VBO_W_GUESTMESSAGES_DESCR':
				$result = __('Navigate through all your guest messages.', 'vikbooking');
				break;
			case 'VBO_NO_REPLY_NEEDED':
				$result = __('No reply needed', 'vikbooking');
				break;
			case 'VBO_WANT_PROCEED':
				$result = __('Do you want to proceed?', 'vikbooking');
				break;
			case 'VBO_W_FINANCE_TITLE':
				$result = __('Finance', 'vikbooking');
				break;
			case 'VBO_W_FINANCE_DESCR':
				$result = __('Metrics about occupancy, revenue and commission savings.', 'vikbooking');
				break;
			case 'VBO_GROSS_BOOKING_VALUE':
				$result = __('Gross Booking Value', 'vikbooking');
				break;
			case 'VBO_AVG_COMMISSIONS':
				$result = __('Average Commissions', 'vikbooking');
				break;
			case 'VBO_COMMISSION_SAVINGS':
				$result = __('Commission Savings', 'vikbooking');
				break;
			case 'VBO_COMPARE_WITH_LAST_Y':
				$result = __('Compare with last year', 'vikbooking');
				break;
			case 'VBO_COMPARE_WITH_PREV_Q':
				$result = __('Compare with previous quarter', 'vikbooking');
				break;
			case 'VBO_COMPARE_WITH_PREV_M':
				$result = __('Compare with previous month', 'vikbooking');
				break;
			case 'VBO_COMPARE_WITH_PREV_W':
				$result = __('Compare with previous week', 'vikbooking');
				break;
			case 'VBO_COMPARE_WITH_PREV_D':
				$result = __('Compare with previous day', 'vikbooking');
				break;
			case 'VBO_VS_LAST_Y':
				$result = __('vs last year', 'vikbooking');
				break;
			case 'VBO_VS_PREV_Q':
				$result = __('vs previous quarter', 'vikbooking');
				break;
			case 'VBO_VS_PREV_M':
				$result = __('vs previous month', 'vikbooking');
				break;
			case 'VBO_VS_PREV_W':
				$result = __('vs previous week', 'vikbooking');
				break;
			case 'VBO_VS_PREV_D':
				$result = __('vs day before', 'vikbooking');
				break;
			case 'VBO_DAYS_AFTER_CHECKOUT':
				$result = __('Days after check-out', 'vikbooking');
				break;
			case 'VBO_CRON_CUSTOMER_DISCOUNTS_TITLE':
				$result = __('Customer discount', 'vikbooking');
				break;
			case 'VBO_MIN_BOOKINGS_DISC_HELP':
				$result = __('If a customer has got a number of confirmed reservations equal to, or greater than this value, the account will be assigned to the selected coupon discount.', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTPC':
				$result = __('Pre-checkin information received', 'vikbooking');
				break;
			case 'VBO_CANC_FEE':
				$result = __('Cancellation fee', 'vikbooking');
				break;
			case 'VBO_ROOM_UPGRADE':
				$result = __('Room Upgrade', 'vikbooking');
				break;
			case 'VBO_ROOM_UPGRADE_HELP':
				$result = __('If enabled, guests will be able to upgrade to "better" rooms by editing their reservations. It is necessary to select the compatible rooms for the upgrade option (i.e. upgrade from "Double Room Standard" to "Double Room Deluxe").', 'vikbooking');
				break;
			case 'VBO_ELIGIBLE_ROOMS':
				$result = __('Eligible rooms', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTGR':
				$result = __('Guest review', 'vikbooking');
				break;
			case 'VBO_HISTORY_GROUPS':
				$result = __('Groups', 'vikbooking');
				break;
			case 'VBO_HISTORY_GPM':
				$result = __('Payments', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTUR':
				$result = __('Room Upgrade', 'vikbooking');
				break;
			case 'VBO_ROOM_UPGRADE_CONDTEXT_H':
				$result = __('Matches the available upgrade options depending on the nights and rooms booked', 'vikbooking');
				break;
			case 'VBO_ROOM_UPGRADE_ADDCONTMSG':
				$result = __('Add upgrade details to message', 'vikbooking');
				break;
			case 'VBO_UPGRADE_CONFIRM':
				$result = __('Confirm upgrade', 'vikbooking');
				break;
			case 'VBO_YOU_SAVE_PCENT':
				$result = __('You save %s', 'vikbooking');
				break;
			case 'VBO_CRON_TESTMAIL':
				$result = __('Test eMail Address', 'vikbooking');
				break;
			case 'VBO_CRON_TESTMAIL_HELP':
				$result = __('If provided, the cron job will send the message to this email address, not to the customer. Useful for testing purposes.', 'vikbooking');
				break;
			case 'VBO_NEW_BOOKING':
				$result = __('New Booking', 'vikbooking');
				break;
			case 'VBO_CHILDFEES_AGEBUCKETS_HELP':
				$result = __('For a better compatibility with OTAs, it is recommended to define at most 3 age intervals (buckets), where the &quot;to age&quot; is inclusive, and the next from-age interval should be greater by one (i.e. from 0 to 3, from 4 to 8, from 9 to 16). Also, fixed amounts in your currency are way more compatible than percent values of the room/adults rate.', 'vikbooking');
				break;
			case 'VBO_MISSING_SUBUNIT':
				$result = __('Missing sub-unit', 'vikbooking');
				break;
			case 'VBO_QUICK_ACTIONS':
				$result = __('Quick Actions', 'vikbooking');
				break;
			case 'VBO_USEFUL_LONGSTAY_PROMOS':
				$result = __('Useful for long-term stay promotions', 'vikbooking');
				break;
			case 'VBO_MAX_BOOK_TOTAL':
				$result = __('Max. Booking Total', 'vikbooking');
				break;
			case 'VBO_WELCOME_ADMIN_USER':
				$result = __('Welcome back, %s!', 'vikbooking');
				break;
			case 'VBO_BOOKINGS_YESTERDAY':
				$result = __('Bookings collected yesterday: %d', 'vikbooking');
				break;
			case 'VBO_ADMIN_WIDGET':
				$result = __('Admin widget', 'vikbooking');
				break;
			case 'VBO_CONGRATS':
				$result = __('Congratulations!', 'vikbooking');
				break;
			case 'VBO_SHOW_CANCELLATIONS':
				$result = __('Show cancellations', 'vikbooking');
				break;
			case 'VBO_CANCELLATIONS':
				$result = __('Cancellations', 'vikbooking');
				break;
			case 'VBO_EXPORT_AS':
				$result = __('Export as', 'vikbooking');
				break;
			case 'VBO_AUTO_EXPORT':
				$result = __('Auto-Export', 'vikbooking');
				break;
			case 'VBO_AUTO_EXPORT_JSON_HELP':
				$result = __('The JSON Payload will automatically set dynamic and required variables for the Report to generate the file to export.', 'vikbooking');
				break;
			case 'VBO_REMOVE_LOCAL_FILE':
				$result = __('Remove local file', 'vikbooking');
				break;
			case 'VBO_AUTO_EXPORT_RMFILE_HELP':
				$result = __('If you are sending the exported file to an email address, then after testing you can choose not to keep the file locally and have it removed automatically.', 'vikbooking');
				break;
			case 'VBO_MEAL_PLANS_INCL':
				$result = __('Meal Plans Included', 'vikbooking');
				break;
			case 'VBO_MEAL_BREAKFAST':
				$result = __('Breakfast', 'vikbooking');
				break;
			case 'VBO_MEAL_LUNCH':
				$result = __('Lunch', 'vikbooking');
				break;
			case 'VBO_MEAL_DINNER':
				$result = __('Dinner', 'vikbooking');
				break;
			case 'VBO_PET':
				$result = __('Pet', 'vikbooking');
				break;
			case 'VBO_PETS':
				$result = __('Pets', 'vikbooking');
				break;
			case 'VBO_MEAL_PLAN':
				$result = __('Meal Plan', 'vikbooking');
				break;
			case 'VBO_MEALS':
				$result = __('Meals', 'vikbooking');
				break;
			case 'VBO_SHORTM_BREAKFAST':
				// @TRANSLATORS: Short for Breakfast
				$result = _x('B', 'Short for Breakfast', 'vikbooking');
				break;
			case 'VBO_SHORTM_LUNCH':
				// @TRANSLATORS: Short for Lunch
				$result = _x('L', 'Short for Lunch', 'vikbooking');
				break;
			case 'VBO_SHORTM_DINNER':
				// @TRANSLATORS: Short for Dinner
				$result = _x('D', 'Short for Dinner', 'vikbooking');
				break;
			case 'VBO_CONF_SWAP_RNUMB':
				// @TRANSLATORS: Confirmation for swapping a room number with another
				$result = _x('Do you really want to swap the room number %s with the number %s?', 'Confirmation for swapping a room number with another', 'vikbooking');
				break;
			case 'VBO_SWAP_ROOMS_LOG':
				// @TRANSLATORS: Room-name: unit number N was swapped with unit number N
				$result = _x('%s: unit number %s was swapped with unit number %s', 'Room-name: unit number N was swapped with unit number N', 'vikbooking');
				break;
			case 'VBO_DRAG_SUBUNITS_SAMEDATE':
				$result = __('Room sub-units cannot be moved onto a different date. You should rather modify the reservation.', 'vikbooking');
				break;
			case 'VBO_ERR_SUBUN_MOVE_SUBUN':
				$result = __('Only room sub-units can be moved onto a sub-unit row.', 'vikbooking');
				break;
			case 'VBO_TAKINGS':
				$result = __('Takings', 'vikbooking');
				break;
			case 'VBO_TOTAL_TAKINGS':
				$result = __('Total Takings', 'vikbooking');
				break;
			case 'VBO_IS_PET_FEE':
				$result = __('Pet Fee', 'vikbooking');
				break;
			case 'VBO_ASSIGN_BOOKING_TO_OTA_CONFIRM':
				// @TRANSLATORS: Confirm the will to assign the reservation to the selected Channel/OTA by knowing that their policies or commissions may apply
				$result = _x('Do you want to assign the reservation to the selected channel %s? The OTA will be notified and their policies will be applied.', 'Confirm the will to assign the reservation to the selected Channel/OTA by knowing that their policies or commissions may apply', 'vikbooking');
				break;
			case 'VBO_BOOKING_OTA_ASSIGNED':
				// @TRANSLATORS: The name of the Channel/OTA assigned to the reservation
				$result = _x('Channel assigned: %s', 'The name of the Channel/OTA assigned to the reservation', 'vikbooking');
				break;
			case 'VBO_MAX_ADV_BOOK_NOTICE':
				$result = __('Maximum advance booking notice', 'vikbooking');
				break;
			case 'VBO_MAX_ADV_BOOK_NOTICE_HELP':
				$result = __('Defines the maximum notice period allowed for booking. If not specified, the global configuration setting for the &quot;Maximum date in the future from today&quot; will be used.', 'vikbooking');
				break;
			case 'VBO_PAY_PROCESS_NO_DIRECT_CHARGE':
				$result = __('The active payment processor does not support direct charges over credit card numbers.', 'vikbooking');
				break;
			case 'VBO_W_VIRTUALTERMINAL_TITLE':
				$result = __('Virtual Terminal', 'vikbooking');
				break;
			case 'VBO_W_VIRTUALTERMINAL_DESCR':
				$result = __('Manually charge credit card details for your OTA reservations. Only supported by some payment processors.', 'vikbooking');
				break;
			case 'VBO_CC_AMOUNT':
				$result = __('Amount', 'vikbooking');
				break;
			case 'VBO_CC_NUMBER':
				$result = __('Card Number', 'vikbooking');
				break;
			case 'VBO_CC_EXPIRY_DT':
				$result = __('Expiration Date', 'vikbooking');
				break;
			case 'VBO_CC_CVV':
				$result = __('CVV', 'vikbooking');
				break;
			case 'VBO_CC_DOCHARGE':
				$result = __('Charge Credit Card', 'vikbooking');
				break;
			case 'VBO_CC_TN_ERROR':
				$result = __('Transaction error', 'vikbooking');
				break;
			case 'VBO_NOPROMO_UPD_CHANNELS':
				$result = __('The channels connected with the Channel Manager do not allow regular pricing rules to be converted into promotions. Please delete this special price and create a new one as a promotion.', 'vikbooking');
				break;
			case 'VBO_IMPORTANT':
				$result = __('Important', 'vikbooking');
				break;
			case 'VBO_WARN_CM_RES_MOD':
				$result = __('Warning: this is a Channel Manager reservation. Changes or cancellations should not be made manually. They should be made automatically by the Channel Manager itself, if requested by the guest.', 'vikbooking');
				break;
			case 'VBO_SEE_ALL':
				$result = __('See all', 'vikbooking');
				break;
			case 'VBO_COPY':
				$result = __('Copy', 'vikbooking');
				break;
			case 'VBO_COPIED':
				$result = __('Copied', 'vikbooking');
				break;
			case 'VBO_READ_MORE':
				$result = __('Read more', 'vikbooking');
				break;
			case 'VBOBOOKHISTORYTOB':
				$result = __('Overbooking', 'vikbooking');
				break;
			case 'VBO_BTYPE_OVERBOOKING':
				$result = __('Overbooking', 'vikbooking');
				break;
			case 'VBO_CONF_RM_OVERBOOKING_FLAG':
				$result = __('Do you want to remove the overbooking flag? The action cannot be undone.', 'vikbooking');
				break;
			case 'VBO_OVERBOOKING_FLAG_REMOVED':
				$result = __('The overbooking flag was removed.', 'vikbooking');
				break;
			case 'VBO_CHOOSE_CHILDREN_HELP':
				$result = __('If enabled, every booking form will ask the guests to choose the number of children for each room party, beside the number of adults. Turn it off if children are not allowed or if they are charged like adults.', 'vikbooking');
				break;
			case 'VBO_GUESTS_LABEL':
				$result = __('Guests label', 'vikbooking');
				break;
			case 'VBO_GUESTS_LABEL_HELP':
				$result = __('The label to use for selecting the number of guests for each room. Choosing &quot;Guests&quot; over &quot;Adults&quot; can be helpful if children are not allowed or if they are charged like adults regardless of their age.', 'vikbooking');
				break;
			case 'VBO_GUESTS_POLICY':
				$result = __('Guests allowed policy', 'vikbooking');
				break;
			case 'VBO_GUESTS_POLICY_HELP':
				$result = __('An optional text that will be displayed in the booking forms. Useful to provide information such as infants allowed, children rates, minimum guests age etc..', 'vikbooking');
				break;
			case 'VBO_MARK_UNITS_CLOSED':
				$result = __('Mark the units as closed?', 'vikbooking');
				break;
			case 'VBO_W_BULKMESSAGING_TITLE':
				$result = __('Bulk Messaging', 'vikbooking');
				break;
			case 'VBO_W_BULKMESSAGING_DESCR':
				$result = __('Send bulk communication messages to your guests.', 'vikbooking');
				break;
			case 'VBO_SEND':
				$result = __('Send', 'vikbooking');
				break;
			case 'VBO_SEND_MESSAGES':
				$result = __('Send Messages', 'vikbooking');
				break;
			case 'VBO_W_NOTIFSCENTER_TITLE':
				$result = __('Notifications Center', 'vikbooking');
				break;
			case 'VBO_W_NOTIFSCENTER_DESCR':
				$result = __('Shows a list of notifications.', 'vikbooking');
				break;
			case 'VBO_SECOND':
				// @TRANSLATORS: 1 second (singular)
				$result = __('second', 'vikbooking');
				break;
			case 'VBO_MINUTE':
				// @TRANSLATORS: 1 minute (singular)
				$result = __('minute', 'vikbooking');
				break;
			case 'VBO_HOUR':
				// @TRANSLATORS: 1 hour (singular)
				$result = __('hour', 'vikbooking');
				break;
			case 'VBO_NOMORE_NOTIFS':
				$result = __('There are no more notifications to display!', 'vikbooking');
				break;
			case 'VBO_NO_NOTIFS':
				$result = __('There are no notifications.', 'vikbooking');
				break;
			case 'VBO_MARK_ALL_READ':
				$result = __('Mark all as read', 'vikbooking');
				break;
			case 'VBO_TAKE_ACTION':
				// @TRANSLATORS: Click the button to visit the URL and take action
				$result = __('Take action', 'vikbooking');
				break;
			case 'VBO_NOTIFS_GROUP_WEBSITE':
				$result = __('Website', 'vikbooking');
				break;
			case 'VBO_NOTIFS_GROUP_OTAS':
				// @TRANSLATORS: OTAs stands for channels, like Booking.com, Airbnb, Expedia
				$result = __('OTAs', 'vikbooking');
				break;
			case 'VBO_NOTIFS_GROUP_CM':
				$result = __('Channel Manager', 'vikbooking');
				break;
			case 'VBO_NOTIFS_GROUP_GUESTS':
				$result = __('Guests', 'vikbooking');
				break;
			case 'VBO_NOTIFS_GROUP_OPERATORS':
				$result = __('Operators', 'vikbooking');
				break;
			case 'VBO_VCC_BALANCE':
				$result = __('Virtual Credit Card Balance', 'vikbooking');
				break;
			case 'VBO_BOOK_MOD_SUMMARY_SNG':
				// @TRANSLATORS: Summary of what was changed with a booking modification, singular version
				$result = __('%s was modified', 'vikbooking');
				break;
			case 'VBO_BOOK_MOD_SUMMARY_PLR':
				// @TRANSLATORS: Summary of what was changed with a booking modification, plural version
				$result = __('%s were modified', 'vikbooking');
				break;
			case 'VBO_MESSAGE_FROM':
				// @TRANSLATORS: Message from John
				$result = __('Message from %s', 'vikbooking');
				break;
			case 'VBO_REPLY':
				$result = __('Reply', 'vikbooking');
				break;
			case 'VBO_CHECK_HOW_WENT':
				$result = __('Check how it went!', 'vikbooking');
				break;
			case 'VBO_ONLY_UNREAD':
				$result = __('Only unread', 'vikbooking');
				break;
			case 'VBO_VERIFY_LISTING_INFO':
				$result = __('Please verify the information for the listing %s', 'vikbooking');
				break;
			case 'VBO_REPORT_STAY_CHANGE_OTA':
				$result = __('Report stay change to %s', 'vikbooking');
				break;
			case 'VBO_REPORT_STAY_CHANGE_OTA_ALLR_HELP':
				$result = __('The channel %s will be informed of the stay dates change for the whole reservation.', 'vikbooking');
				break;
			case 'VBO_REPORT_STAY_CHANGE_OTA_ONER_HELP':
				$result = __('The channel %s will be informed of the stay dates change for this room.', 'vikbooking');
				break;
			case 'VBO_DO_WAIVE_FEES':
				$result = __('Waive fees', 'vikbooking');
				break;
			case 'VBO_DONT_WAIVE_FEES':
				$result = __('Do not waive fees', 'vikbooking');
				break;
			case 'VBO_EVENTS':
				$result = __('Events', 'vikbooking');
				break;
			case 'VBO_CRONJOB_WEBHOOK_EVENTS_DESC':
				$result = __('Optional events to filter. If some are selected, only the most recent booking events of such types will be used to trigger a webhook action. Useful to target just OTA reservations, new bookings, modifications, cancellations etc.. Do not select any events to target any recent change to a reservation record.', 'vikbooking');
				break;
			case 'VBO_LAYOUT':
				$result = __('Layout', 'vikbooking');
				break;
			case 'VBO_TABLE':
				$result = __('Table', 'vikbooking');
				break;
			case 'VBO_SCROLL':
				$result = __('Scroll', 'vikbooking');
				break;
			case 'VBO_GUEST':
				$result = __('Guest', 'vikbooking');
				break;
			case 'VBO_PROCESSING_FEES':
				$result = __('Processing fees', 'vikbooking');
				break;
			case 'VBO_SENDER':
				$result = __('Sender', 'vikbooking');
				break;
			case 'VBO_FIRST_NAME_ACCURATE_HELP':
				$result = __('Searching by guest first name may be more accurate.', 'vikbooking');
				break;
			case 'VBO_MESSAGE_CONTAINS_HELP':
				$result = __('The text contained in the message.', 'vikbooking');
				break;
			case 'VBO_FROM_DT_HELP':
				$result = __('Minimum date filter.', 'vikbooking');
				break;
			case 'VBO_TO_DT_HELP':
				$result = __('Maximum date filter.', 'vikbooking');
				break;
			case 'VBO_PAGE_NUMBER':
				// @TRANSLATORS: Page 2
				$result = __('Page %d', 'vikbooking');
				break;
			case 'VBO_FILTERS_APPLIED':
				$result = __('Filters applied', 'vikbooking');
				break;
			case 'VBO_NO_RECORDS_FOUND':
				$result = __('No records found', 'vikbooking');
				break;
			case 'VBO_ACTIVE_PERMS':
				$result = __('Active permissions', 'vikbooking');
				break;
			case 'VBO_OPER_PROF_PIC_HELP':
				$result = __('An optional profile picture or logo of the operator. Can be a URL or an uploaded image.', 'vikbooking');
				break;
			case 'VBO_PERM_NEGATIVE_DAYS':
				$result = __('Negative values will allow to access past dates.', 'vikbooking');
				break;
			case 'VBO_CREDIT_CARD':
				$result = __('Credit Card', 'vikbooking');
				break;
		}

		return $result;
	}
}
