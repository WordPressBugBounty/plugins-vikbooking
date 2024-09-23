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
 * Switcher class to translate the VikBooking plugin site languages.
 *
 * @since 	1.0
 */
class VikBookingLanguageSite implements JLanguageHandler
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
			case 'VBDATE':
				$result = __('Date', 'vikbooking');
				break;
			case 'VBIP':
				$result = __('IP', 'vikbooking');
				break;
			case 'VBORDNOL':
				$result = __('Reservation', 'vikbooking');
				break;
			case 'VBINATTESA':
				$result = __('Waiting for the Payment', 'vikbooking');
				break;
			case 'VBCOMPLETED':
				$result = __('Completed', 'vikbooking');
				break;
			case 'VBROOMBOOKEDBYOTHER':
				$result = __('Sorry, the room has been booked. Please make a new reservation.', 'vikbooking');
				break;
			case 'VBROOMISLOCKED':
				$result = __('The room is now being booked by another customer. Please make a new reservation.', 'vikbooking');
				break;
			case 'VBINVALIDDATES':
				$result = __('Check-in and Check-out Dates are wrong', 'vikbooking');
				break;
			case 'VBINCONGRTOT':
				$result = __('Error, booking total is wrong', 'vikbooking');
				break;
			case 'VBINCONGRDATAREC':
				$result = __('Error, Wrong data.', 'vikbooking');
				break;
			case 'VBINCONGRDATA':
				$result = __('Error, Wrong data.', 'vikbooking');
				break;
			case 'VBINSUFDATA':
				$result = __('Error, Insufficient Data Received.', 'vikbooking');
				break;
			case 'VBINVALIDTOKEN':
				$result = __('Error, Invalid Token. Unable to Save the Reservation', 'vikbooking');
				break;
			case 'VBERRREPSEARCH':
				$result = __('Error, the room is already booked and is no longer available. Please search for another one.', 'vikbooking');
				break;
			case 'VBORDERNOTFOUND':
				$result = __('Error, Booking not found', 'vikbooking');
				break;
			case 'VBERRCALCTAR':
				$result = __('An Error occured processing fares. Please choose new dates', 'vikbooking');
				break;
			case 'VBTARNOTFOUND':
				$result = __('Error, Not Existing Fare', 'vikbooking');
				break;
			case 'VBNOTARSELECTED':
				$result = __('No Fares selected, please try again', 'vikbooking');
				break;
			case 'VBROOMNOTCONS':
				$result = __('The Room selected is not available from the', 'vikbooking');
				break;
			case 'VBROOMNOTCONSTO':
				$result = __('to the', 'vikbooking');
				break;
			case 'VBROOMNOTRIT':
				$result = __('The room is not available from the', 'vikbooking');
				break;
			case 'VBROOMNOTFND':
				$result = __('Room not found', 'vikbooking');
				break;
			case 'VBROOMNOTAV':
				$result = __('Room not available', 'vikbooking');
				break;
			case 'VBNOTARFNDSELO':
				$result = __('No Fares Found. Please select a different date or room', 'vikbooking');
				break;
			case 'VBSRCHNOTM':
				$result = __('Search Notification', 'vikbooking');
				break;
			case 'VBCAT':
				$result = __('Category', 'vikbooking');
				break;
			case 'VBANY':
				$result = __('Any', 'vikbooking');
				break;
			case 'VBPICKUP':
				$result = __('Check-in', 'vikbooking');
				break;
			case 'VBRETURN':
				$result = __('Check-out', 'vikbooking');
				break;
			case 'VBSRCHRES':
				$result = __('Search Results', 'vikbooking');
				break;
			case 'VBNOROOMSINDATE':
				$result = __('No rooms available in the dates requested.', 'vikbooking');
				break;
			case 'VBNOROOMAVFOR':
				$result = __('No room is available for booking for', 'vikbooking');
				break;
			case 'VBDAYS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBDAY':
				$result = __('Night', 'vikbooking');
				break;
			case 'VBPICKBRET':
				$result = __('Check-out date previous than check-in', 'vikbooking');
				break;
			case 'VBWRONGDF':
				$result = __('Wrong Date Format. Right Format is', 'vikbooking');
				break;
			case 'VBSELPRDATE':
				$result = __('Please select Check-in and Check-out Dates and select the number of people per room', 'vikbooking');
				break;
			case 'VBPICKUPROOM':
				$result = __('Check-in Date', 'vikbooking');
				break;
			case 'VBRETURNROOM':
				$result = __('Check-out Date', 'vikbooking');
				break;
			case 'VBALLE':
				$result = __('At', 'vikbooking');
				break;
			case 'VBROOMCAT':
				$result = __('Rooms Category', 'vikbooking');
				break;
			case 'VBALLCAT':
				$result = __('Any', 'vikbooking');
				break;
			case 'VBERRCONNPAYP':
				$result = __('Error while connecting to Paypal.com', 'vikbooking');
				break;
			case 'VBIMPVERPAYM':
				$result = __('Unable to process the payment of the', 'vikbooking');
				break;
			case 'VBRENTALORD':
				$result = __('Reservation', 'vikbooking');
				break;
			case 'VBCOMPLETED':
				$result = __('Completed', 'vikbooking');
				break;
			case 'VBVALIDPWSAVE':
				$result = __('Valid Paypal Payment, Error Saving the Booking', 'vikbooking');
				break;
			case 'VBVALIDPWSAVEMSG':
				$result = __('Payment received with Success, Booking not Saved. Correct the problem manually.', 'vikbooking');
				break;
			case 'VBPAYPALRESP':
				$result = __('Paypal Response', 'vikbooking');
				break;
			case 'VBINVALIDPAYPALP':
				$result = __('Invalid Paypal Payment', 'vikbooking');
				break;
			case 'ERRSELECTPAYMENT':
				$result = __('Please Select a Payment Method', 'vikbooking');
				break;
			case 'VBPAYMENTNOTVER':
				$result = __('Payment Not Verified', 'vikbooking');
				break;
			case 'VBSERVRESP':
				$result = __('Server Response', 'vikbooking');
				break;
			case 'VBCONFIGONETWELVE':
				$result = __('DD/MM/YYYY', 'vikbooking');
				break;
			case 'VBCONFIGONETENTHREE':
				$result = __('YYYY/MM/DD', 'vikbooking');
				break;
			case 'VBROOMSFND':
				$result = __('Rooms Found', 'vikbooking');
				break;
			case 'VBPROSEGUI':
				$result = __('Continue', 'vikbooking');
				break;
			case 'VBSTARTFROM':
				$result = __('Starting From', 'vikbooking');
				break;
			case 'VBRENTAL':
				$result = __('Reservation', 'vikbooking');
				break;
			case 'VBFOR':
				$result = __('for', 'vikbooking');
				break;
			case 'VBPRICE':
				$result = __('Price', 'vikbooking');
				break;
			case 'VBACCOPZ':
				$result = __('Options', 'vikbooking');
				break;
			case 'VBBOOKNOW':
				$result = __('Book Now', 'vikbooking');
				break;
			case 'VBDAL':
				$result = __('Arrival', 'vikbooking');
				break;
			case 'VBAL':
				$result = __('Departure', 'vikbooking');
				break;
			case 'VBRIEPILOGOORD':
				$result = __('Booking Summary', 'vikbooking');
				break;
			case 'VBTOTAL':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBIMP':
				$result = __('Taxable Income', 'vikbooking');
				break;
			case 'VBIVA':
				$result = __('Tax', 'vikbooking');
				break;
			case 'VBDUE':
				$result = __('Total Due', 'vikbooking');
				break;
			case 'VBFILLALL':
				$result = __('Please Fill all Fields', 'vikbooking');
				break;
			case 'VBPURCHDATA':
				$result = __('Purchaser Details', 'vikbooking');
				break;
			case 'VBNAME':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBLNAME':
				$result = __('Last Name', 'vikbooking');
				break;
			case 'VBMAIL':
				$result = __('e-Mail', 'vikbooking');
				break;
			case 'VBPHONE':
				$result = __('Phone', 'vikbooking');
				break;
			case 'VBADDR':
				$result = __('Address', 'vikbooking');
				break;
			case 'VBCAP':
				$result = __('Zip Code', 'vikbooking');
				break;
			case 'VBCITY':
				$result = __('City', 'vikbooking');
				break;
			case 'VBNAT':
				$result = __('State', 'vikbooking');
				break;
			case 'VBDOBIRTH':
				$result = __('Date of birth', 'vikbooking');
				break;
			case 'VBFISCALCODE':
				$result = __('Fiscal Code', 'vikbooking');
				break;
			case 'VBORDCONFIRM':
				$result = __('Confirm Reservation', 'vikbooking');
				break;
			case 'VBTHANKSONE':
				$result = __('Thanks, Booking Successfully Completed', 'vikbooking');
				break;
			case 'VBTHANKSTWO':
				$result = __('To review your reservation, please visit', 'vikbooking');
				break;
			case 'VBTHANKSTHREE':
				$result = __('This Page', 'vikbooking');
				break;
			case 'VBORDEREDON':
				$result = __('Booking Date', 'vikbooking');
				break;
			case 'VBPERSDETS':
				$result = __('Personal Details', 'vikbooking');
				break;
			case 'VBROOMRENTED':
				$result = __('Room Reserved', 'vikbooking');
				break;
			case 'VBOPTS':
				$result = __('Options', 'vikbooking');
				break;
			case 'VBWAITINGPAYM':
				$result = __('Waiting for the Payment', 'vikbooking');
				break;
			case 'VBWAITINGFORPAYMENT':
				$result = __('Waiting for the Payment', 'vikbooking');
				break;
			case 'VBBACK':
				$result = __('Back', 'vikbooking');
				break;
			case 'ORDDD':
				$result = __('Nights', 'vikbooking');
				break;
			case 'ORDNOTAX':
				$result = __('Net Price', 'vikbooking');
				break;
			case 'ORDTAX':
				$result = __('Tax', 'vikbooking');
				break;
			case 'ORDWITHTAX':
				$result = __('Total Price', 'vikbooking');
				break;
			case 'VBADDNOTES':
				$result = __('Notes', 'vikbooking');
				break;
			case 'VBCHANGEDATES':
				$result = __('Change Dates', 'vikbooking');
				break;
			case 'VBCHOOSEPAYMENT':
				$result = __('Payment Method', 'vikbooking');
				break;
			case 'VBLIBONE':
				$result = __('Reservation Received on the', 'vikbooking');
				break;
			case 'VBLIBTWO':
				$result = __('Purchaser Info', 'vikbooking');
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
			case 'VBLEAVEDEPOSIT':
				$result = __('Leave a deposit of ', 'vikbooking');
				break;
			case 'VBLIBPAYNAME':
				$result = __('Payment Method', 'vikbooking');
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
				$result = __('Date of birth', 'vikbooking');
				break;
			case 'ORDER_FLIGHTNUM':
				$result = __('Flight Number', 'vikbooking');
				break;
			case 'ORDER_NOTES':
				$result = __('Notes', 'vikbooking');
				break;
			case 'VBLISTSFROM':
				$result = __('Starting From', 'vikbooking');
				break;
			case 'VBLISTPICK':
				$result = __('View Details &gt;&gt;', 'vikbooking');
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
			case 'VBLEGFREE':
				$result = __('Available', 'vikbooking');
				break;
			case 'VBLEGWARNING':
				$result = __('Partially Reserved', 'vikbooking');
				break;
			case 'VBLEGBUSY':
				$result = __('Not Available', 'vikbooking');
				break;
			case 'VBBOOKTHISROOM':
				$result = __('Book Now', 'vikbooking');
				break;
			case 'VBSELECTPDDATES':
				$result = __('Select a Check-in and Check-out Date', 'vikbooking');
				break;
			case 'VBDETAILCNOTAVAIL':
				$result = __('%s is not available for %d nights or for the number of guests requested. Please try with different dates or guests.', 'vikbooking');
				break;
			case 'VBREGSIGNUP':
				$result = __('Sign Up', 'vikbooking');
				break;
			case 'VBREGNAME':
				$result = __('Name', 'vikbooking');
				break;
			case 'VBREGLNAME':
				$result = __('Last Name', 'vikbooking');
				break;
			case 'VBREGEMAIL':
				$result = __('e-Mail', 'vikbooking');
				break;
			case 'VBREGUNAME':
				$result = __('Username', 'vikbooking');
				break;
			case 'VBREGPWD':
				$result = __('Password', 'vikbooking');
				break;
			case 'VBREGCONFIRMPWD':
				$result = __('Confirm Password', 'vikbooking');
				break;
			case 'VBREGSIGNUPBTN':
				$result = __('Sign Up', 'vikbooking');
				break;
			case 'VBREGSIGNIN':
				$result = __('Login', 'vikbooking');
				break;
			case 'VBREGSIGNINBTN':
				$result = __('Login', 'vikbooking');
				break;
			case 'VBREGERRINSDATA':
				$result = __('Please fill in all the registration fields', 'vikbooking');
				break;
			case 'VBREGERRSAVING':
				$result = __('Error while creating an account, please try again', 'vikbooking');
				break;
			case 'VBHOUR':
				$result = __('Hour', 'vikbooking');
				break;
			case 'VBHOURS':
				$result = __('Hours', 'vikbooking');
				break;
			case 'VBSEPDRIVERD':
				$result = __('Billing Information', 'vikbooking');
				break;
			case 'VBORDERNUMBER':
				$result = __('Booking Number', 'vikbooking');
				break;
			case 'VBORDERDETAILS':
				$result = __('Booking Details', 'vikbooking');
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
			case 'VBTOTPAYMENTINVALID':
				$result = __('Invalid Amount Paid', 'vikbooking');
				break;
			case 'VBTOTPAYMENTINVALIDTXT':
				$result = __('A payment for the booking %s has been received. The total amount received is %s instead of %s.', 'vikbooking');
				break;
			case 'VBLOCLISTLOCOPENTIME':
				$result = __('Opening Time', 'vikbooking');
				break;
			case 'VBHAVEACOUPON':
				$result = __('Enter here your coupon code', 'vikbooking');
				break;
			case 'VBSUBMITCOUPON':
				$result = __('Apply', 'vikbooking');
				break;
			case 'VBCOUPONNOTFOUND':
				$result = __('Error, Coupon not found', 'vikbooking');
				break;
			case 'VBCOUPONINVDATES':
				$result = __('The Coupon is not valid for these reservation dates', 'vikbooking');
				break;
			case 'VBCOUPONINVROOM':
				$result = __('The Coupon is not valid for this room', 'vikbooking');
				break;
			case 'VBCOUPONINVMINTOTORD':
				$result = __('This coupon requires a greater booking total amount', 'vikbooking');
				break;
			case 'VBCOUPON':
				$result = __('Coupon', 'vikbooking');
				break;
			case 'VBNEWTOTAL':
				$result = __('Total', 'vikbooking');
				break;
			case 'VBFORMROOMSN':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBFORMADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBFORMCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBFORMNUMROOM':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBJSTOTNIGHTS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBSEARCHROOMNUM':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBSEARCHROOMADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBSEARCHROOMCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBSEARCHRESNIGHT':
				$result = __('Night', 'vikbooking');
				break;
			case 'VBSEARCHRESNIGHTS':
				$result = __('Nights', 'vikbooking');
				break;
			case 'VBSEARCHRESROOM':
				$result = __('Room', 'vikbooking');
				break;
			case 'VBSEARCHRESROOMS':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBSEARCHRESADULT':
				$result = __('Adult', 'vikbooking');
				break;
			case 'VBSEARCHRESADULTS':
				$result = __('Adults', 'vikbooking');
				break;
			case 'VBSEARCHRESCHILD':
				$result = __('Child', 'vikbooking');
				break;
			case 'VBSEARCHRESCHILDREN':
				$result = __('Children', 'vikbooking');
				break;
			case 'VBSELECTR':
				$result = __('Select', 'vikbooking');
				break;
			case 'VBSELECTEDR':
				$result = __('Selected', 'vikbooking');
				break;
			case 'VBSEARCHRESDETAILS':
				$result = __('Details', 'vikbooking');
				break;
			case 'VBERRSELECTINGROOMS':
				$result = __('Error selecting the rooms, please try again.', 'vikbooking');
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
			case 'VBCHARACTERISTICS':
				$result = __('Characteristics', 'vikbooking');
				break;
			case 'VBPRICEDETAILS':
				$result = __('Price Breakdown', 'vikbooking');
				break;
			case 'VBPRICEDETAILSDAY':
				$result = __('Day', 'vikbooking');
				break;
			case 'VBPRICEDETAILSPRICE':
				$result = __('Price per Night', 'vikbooking');
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
			case 'VBSEARCHCONTINUESUBM':
				$result = __('Continue', 'vikbooking');
				break;
			case 'VBSRCHDIALOGROOM':
				$result = __('Room Selection', 'vikbooking');
				break;
			case 'VBSRCHDIALOGROOMS':
				$result = __('Rooms Selection', 'vikbooking');
				break;
			case 'VBDIALOGMESSONE':
				$result = __('Room Selected:', 'vikbooking');
				break;
			case 'VBDIALOGBTNCANCEL':
				$result = __('Cancel', 'vikbooking');
				break;
			case 'VBDIALOGBTNCONTINUE':
				$result = __('Continue', 'vikbooking');
				break;
			case 'VBYOURRESERV':
				$result = __('Your Reservation', 'vikbooking');
				break;
			case 'VBCHECKINONTHE':
				$result = __('Check-in on the', 'vikbooking');
				break;
			case 'VBCHECKOUTONTHE':
				$result = __('Check-out on the', 'vikbooking');
				break;
			case 'VBCHECKINOUTOF':
				$result = __('%s of %s', 'vikbooking');
				break;
			case 'VBDAYMONTHONE':
				$result = __('1st', 'vikbooking');
				break;
			case 'VBDAYMONTHTWO':
				$result = __('2nd', 'vikbooking');
				break;
			case 'VBDAYMONTHTHREE':
				$result = __('3rd', 'vikbooking');
				break;
			case 'VBDAYMONTHFOUR':
				$result = __('4th', 'vikbooking');
				break;
			case 'VBDAYMONTHFIVE':
				$result = __('5th', 'vikbooking');
				break;
			case 'VBDAYMONTHSIX':
				$result = __('6th', 'vikbooking');
				break;
			case 'VBDAYMONTHSEVEN':
				$result = __('7th', 'vikbooking');
				break;
			case 'VBDAYMONTHEIGHT':
				$result = __('8th', 'vikbooking');
				break;
			case 'VBDAYMONTHNINE':
				$result = __('9th', 'vikbooking');
				break;
			case 'VBDAYMONTHTEN':
				$result = __('10th', 'vikbooking');
				break;
			case 'VBDAYMONTHELEVEN':
				$result = __('11th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWELVE':
				$result = __('12th', 'vikbooking');
				break;
			case 'VBDAYMONTHTHIRTEEN':
				$result = __('13th', 'vikbooking');
				break;
			case 'VBDAYMONTHFOURTEEN':
				$result = __('14th', 'vikbooking');
				break;
			case 'VBDAYMONTHFIFTEEN':
				$result = __('15th', 'vikbooking');
				break;
			case 'VBDAYMONTHSIXTEEN':
				$result = __('16th', 'vikbooking');
				break;
			case 'VBDAYMONTHSEVENTEEN':
				$result = __('17th', 'vikbooking');
				break;
			case 'VBDAYMONTHEIGHTEEN':
				$result = __('18th', 'vikbooking');
				break;
			case 'VBDAYMONTHNINETEEN':
				$result = __('19th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTY':
				$result = __('20th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYONE':
				$result = __('21st', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYTWO':
				$result = __('22nd', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYTHREE':
				$result = __('23rd', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYFOUR':
				$result = __('24th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYFIVE':
				$result = __('25th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYSIX':
				$result = __('26th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYSEVEN':
				$result = __('27th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYEIGHT':
				$result = __('28th', 'vikbooking');
				break;
			case 'VBDAYMONTHTWENTYNINE':
				$result = __('29th', 'vikbooking');
				break;
			case 'VBDAYMONTHTHIRTY':
				$result = __('30th', 'vikbooking');
				break;
			case 'VBDAYMONTHTHIRTYONE':
				$result = __('31st', 'vikbooking');
				break;
			case 'VBSTEPDATES':
				$result = __('Dates', 'vikbooking');
				break;
			case 'VBSTEPROOMSELECTION':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'VBSTEPOPTIONS':
				$result = __('Options', 'vikbooking');
				break;
			case 'VBSTEPCONFIRM':
				$result = __('Book', 'vikbooking');
				break;
			case 'VBLISTPERNIGHT':
				$result = __('per night', 'vikbooking');
				break;
			case 'VBSEARCHERRNOTENOUGHROOMS':
				$result = __('We are sorry, but there aren\'t enough rooms for %s. Please select a different number of rooms or guests.', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYARRIVAL':
				$result = __('Error, the arrival day in %s must be on a %s. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRMAXLOSEXCEEDED':
				$result = __('Error, the maximum number of nights of stay in %s is %d. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRMINLOSEXCEEDED':
				$result = __('Error, the minimum number of nights of stay in %s is %d. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRMULTIPLYMINLOS':
				$result = __('Error, the number of nights of stay allowed in %s must be a multiple of %d. Please try again.', 'vikbooking');
				break;
			case 'VBCONFIRMNUMB':
				$result = __('Confirmation Number', 'vikbooking');
				break;
			case 'VBCCCREDITCARDNUMBER':
				$result = __('Credit Card Number', 'vikbooking');
				break;
			case 'VBCCVALIDTHROUGH':
				$result = __('Valid Through', 'vikbooking');
				break;
			case 'VBCCCVV':
				$result = __('CVV', 'vikbooking');
				break;
			case 'VBCCFIRSTNAME':
				$result = __('First Name', 'vikbooking');
				break;
			case 'VBCCLASTNAME':
				$result = __('Last Name', 'vikbooking');
				break;
			case 'VBCCBILLINGINFO':
				$result = __('Billing Information', 'vikbooking');
				break;
			case 'VBCCCOMPANY':
				$result = __('Company', 'vikbooking');
				break;
			case 'VBCCADDRESS':
				$result = __('Address', 'vikbooking');
				break;
			case 'VBCCCITY':
				$result = __('City', 'vikbooking');
				break;
			case 'VBCCSTATEPROVINCE':
				$result = __('State/Province', 'vikbooking');
				break;
			case 'VBCCZIP':
				$result = __('ZIP Code', 'vikbooking');
				break;
			case 'VBCCCOUNTRY':
				$result = __('Country', 'vikbooking');
				break;
			case 'VBCCPHONE':
				$result = __('Phone', 'vikbooking');
				break;
			case 'VBCCEMAIL':
				$result = __('eMail', 'vikbooking');
				break;
			case 'VBCCPROCESSPAY':
				$result = __('Process and Pay', 'vikbooking');
				break;
			case 'VBCCPROCESSING':
				$result = __('Processing...', 'vikbooking');
				break;
			case 'VBCCOFFLINECCMESSAGE':
				$result = __('Please provide your Credit Card information. Your card will not be charged and the information will be securely kept by us.', 'vikbooking');
				break;
			case 'VBOFFLINECCSEND':
				$result = __('Submit Credit Card Information', 'vikbooking');
				break;
			case 'VBOFFLINECCSENT':
				$result = __('Processing...', 'vikbooking');
				break;
			case 'VBOFFCCMAILSUBJECT':
				$result = __('Credit Card Information Received', 'vikbooking');
				break;
			case 'VBOFFCCTOTALTOPAY':
				$result = __('Total to Pay', 'vikbooking');
				break;
			case 'VBDBTEXTROOMCLOSED':
				$result = __('Room Closed', 'vikbooking');
				break;
			case 'VBINVALIDCONFIRMNUMBER':
				$result = __('Error, Invalid Confirmation Number', 'vikbooking');
				break;
			case 'VBSEARCHCONFIRMNUMB':
				$result = __('Search Bookings', 'vikbooking');
				break;
			case 'VBSEARCHCONFIRMNUMBBTN':
				$result = __('Search', 'vikbooking');
				break;
			case 'VBERRPEOPLEPERROOM':
				$result = __('%d people in one room (adults: %d, children: %d)', 'vikbooking');
				break;
			case 'VBREQUESTCANCMOD':
				$result = __('Cancellation/Modification Request', 'vikbooking');
				break;
			case 'VBREQUESTCANCMODOPENTEXT':
				$result = __('Click here to request a cancellation or modification of the booking', 'vikbooking');
				break;
			case 'VBREQUESTCANCMODEMAIL':
				$result = __('e-Mail', 'vikbooking');
				break;
			case 'VBREQUESTCANCMODREASON':
				$result = __('Message', 'vikbooking');
				break;
			case 'VBREQUESTCANCMODSUBMIT':
				$result = __('Send Request', 'vikbooking');
				break;
			case 'VBCANCREQUESTEMAILSUBJ':
				$result = __('Booking Cancellation-Modification Request', 'vikbooking');
				break;
			case 'VBCANCREQUESTEMAILHEAD':
				$result = __("A Cancellation-Modification Request has been sent by the customer for the booking id %s.\nBooking details: %s", 'vikbooking');
				break;
			case 'VBCANCREQUESTMAILSENT':
				$result = __('Your request has been sent successfully. Please do not send it again', 'vikbooking');
				break;
			case 'VBSRCHERRCHKINPASSED':
				$result = __('Check-in for today no longer available, please select a different Check-in Date', 'vikbooking');
				break;
			case 'VBSRCHERRCHKINPAST':
				$result = __('The Check-in Date is in the past, please select a different Date', 'vikbooking');
				break;
			case 'VBORDERSTATUSCANCELLED':
				$result = __('Cancelled', 'vikbooking');
				break;
			case 'VBCANCELLED':
				$result = __('Cancelled', 'vikbooking');
				break;
			case 'VBSEARCHBUTTON':
				$result = __('Search', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYCOMBO':
				$result = __('Error, the departure day in %s must be on a %s if arriving on a %s', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYARRIVALRANGE':
				$result = __('Error, the arrival day in these dates must be on a %s. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRMAXLOSEXCEEDEDRANGE':
				$result = __('Error, the maximum number of nights of stay in these dates is %d. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRMINLOSEXCEEDEDRANGE':
				$result = __('Error, the minimum number of nights of stay in these dates is %d. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRMULTIPLYMINLOSRANGE':
				$result = __('Error, the number of nights of stay allowed in these dates must be a multiple of %d. Please try again.', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYCOMBORANGE':
				$result = __('Error, the departure day in these dates must be on a %s if arriving on a %s', 'vikbooking');
				break;
			case 'VBRESTRTIPWDAYARRIVAL':
				$result = __('Some results were excluded: try selecting the arrival day in %s as a %s.', 'vikbooking');
				break;
			case 'VBRESTRTIPMAXLOSEXCEEDED':
				$result = __('Some results were excluded: the maximum number of nights of stay in %s is %d.', 'vikbooking');
				break;
			case 'VBRESTRTIPMINLOSEXCEEDED':
				$result = __('Some results were excluded: the minimum number of nights of stay in %s is %d.', 'vikbooking');
				break;
			case 'VBRESTRTIPMULTIPLYMINLOS':
				$result = __('Some results were excluded: the number of nights of stay allowed in %s should be a multiple of %d.', 'vikbooking');
				break;
			case 'VBRESTRTIPWDAYCOMBO':
				$result = __('Some results were excluded: the departure day in %s should be on a %s if arriving on a %s', 'vikbooking');
				break;
			case 'VBRESTRTIPWDAYARRIVALRANGE':
				$result = __('Some results were excluded: the arrival day in these dates should be on a %s.', 'vikbooking');
				break;
			case 'VBRESTRTIPMAXLOSEXCEEDEDRANGE':
				$result = __('Some results were excluded: the maximum number of nights of stay in these dates is %d.', 'vikbooking');
				break;
			case 'VBRESTRTIPMINLOSEXCEEDEDRANGE':
				$result = __('Some results were excluded: the minimum number of nights of stay in these dates is %d.', 'vikbooking');
				break;
			case 'VBRESTRTIPMULTIPLYMINLOSRANGE':
				$result = __('Some results were excluded: the number of nights of stay allowed in these dates should be a multiple of %d.', 'vikbooking');
				break;
			case 'VBRESTRTIPWDAYCOMBORANGE':
				$result = __('Some results were excluded: the departure day in these dates should be on a %s if arriving on a %s', 'vikbooking');
				break;
			case 'VBLASTUNITSAVAIL':
				$result = __('Last %d available!', 'vikbooking');
				break;
			case 'VBPRICECALWARNING':
				$result = __('<sup>***</sup>Prices may change depending on the number of guests and on the number of nights of stay.', 'vikbooking');
				break;
			case 'VBMAXTOTPEOPLE':
				$result = __('Total People', 'vikbooking');
				break;
			case 'VBSENTVIAMAIL':
				$result = __('Sent via eMail', 'vikbooking');
				break;
			case 'VBTOTALREMAINING':
				$result = __('Remaining Balance', 'vikbooking');
				break;
			case 'VBBREAKFASTINCLUDED':
				$result = __('Breakfast Included', 'vikbooking');
				break;
			case 'VBFREECANCELLATION':
				$result = __('Free Cancellation', 'vikbooking');
				break;
			case 'VBFREECANCELLATIONWITHIN':
				$result = __('Refundable up to %d days before arrival', 'vikbooking');
				break;
			case 'VBNEWORDER':
				$result = __('New Reservation #%s', 'vikbooking');
				break;
			case 'VBORDERPAYMENT':
				$result = __('Payment for Reservation #%s', 'vikbooking');
				break;
			case 'VBERRROOMUNITSNOTAVAIL':
				$result = __('Error, %d units of the room %s are not available on the requested dates. Please select other rooms or use different dates.', 'vikbooking');
				break;
			case 'VBERRJSNOUNITS':
				$result = __('There are no more units available of this room with the current selection', 'vikbooking');
				break;
			case 'VBERRCURCONVNODATA':
				$result = __('Insufficient data received for converting the currency', 'vikbooking');
				break;
			case 'VBERRCURCONVINVALIDDATA':
				$result = __('Invalid data received for converting the currency', 'vikbooking');
				break;
			case 'VBCCCREDITCARDNUMBERINVALID':
				$result = __('Invalid Credit Card Information Received, please try again', 'vikbooking');
				break;
			case 'VBCCPAYMENTNOTVERIFIED':
				$result = __('The payment was not verified, please try again', 'vikbooking');
				break;
			case 'VBCCINFOSENTOK':
				$result = __('Thank you! Credit Card Information Successfully Received', 'vikbooking');
				break;
			case 'VBLEGBUSYCHECKIN':
				$result = __('Check-out Only', 'vikbooking');
				break;
			case 'TRIP_CONNECT_DISCLAIMER':
				$result = __('We may use third-party service providers to process your personal information on our behalf for the purposes specified above. For example, we may share some information about you with these third parties so that they can contact you directly by email (e.g. to obtain post-stay reviews about your travel experience).', 'vikbooking');
				break;
			case 'VBOKDISCLAIMER':
				$result = __('Okay', 'vikbooking');
				break;
			case 'VBONOPROMOTIONSFOUND':
				$result = __('No active promotions found', 'vikbooking');
				break;
			case 'VBOPROMOPERCENTDISCOUNT':
				$result = __('Off', 'vikbooking');
				break;
			case 'VBOPROMOFIXEDDISCOUNT':
				$result = __('Off per Day', 'vikbooking');
				break;
			case 'VBOPROMORENTFROM':
				$result = __('From', 'vikbooking');
				break;
			case 'VBOPROMORENTTO':
				$result = __('To', 'vikbooking');
				break;
			case 'VBOPROMOVALIDUNTIL':
				$result = __('Valid until', 'vikbooking');
				break;
			case 'VBOPROMOROOMBOOKNOW':
				$result = __('Book it Now', 'vikbooking');
				break;
			case 'VBOSEASONSCALOFFSEASONPRICES':
				$result = __('Off-Season Prices', 'vikbooking');
				break;
			case 'VBOSEASONCALNUMNIGHTS':
				$result = __('%d Nights', 'vikbooking');
				break;
			case 'VBOSEASONCALNUMNIGHT':
				$result = __('%d Night', 'vikbooking');
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
			case 'VBOAVAILABILITYCALENDAR':
				$result = __('Availability Calendar', 'vikbooking');
				break;
			case 'VBOSEASONSCALENDAR':
				$result = __('Rates &amp; Restrictions - Seasons Calendar', 'vikbooking');
				break;
			case 'VBDETAILMULTIRNOTAVAIL':
				$result = __('%d units of %s are not available for %d nights', 'vikbooking');
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
			case 'VBAMOUNTPAID':
				$result = __('Total Paid', 'vikbooking');
				break;
			case 'ORDER_SPREQUESTS':
				$result = __('Special Requests', 'vikbooking');
				break;
			case 'VBBOOKINGDATE':
				$result = __('Booking Date', 'vikbooking');
				break;
			case 'VBAVAILBOOKNOW':
				$result = __('Book Now', 'vikbooking');
				break;
			case 'ORDER_TERMSCONDITIONS':
				$result = __('I agree to the terms and conditions', 'vikbooking');
				break;
			case 'VBYES':
				$result = __('Yes', 'vikbooking');
				break;
			case 'VBNO':
				$result = __('No', 'vikbooking');
				break;
			case 'VBRETURNINGCUSTOMER':
				$result = __('Returning Customer?', 'vikbooking');
				break;
			case 'VBENTERPINCODE':
				$result = __('Please enter your PIN Code', 'vikbooking');
				break;
			case 'VBAPPLYPINCODE':
				$result = __('Apply', 'vikbooking');
				break;
			case 'VBWELCOMEBACK':
				$result = __('Welcome back', 'vikbooking');
				break;
			case 'VBINVALIDPINCODE':
				$result = __('Invalid PIN Code. Please try again or just enter your information below', 'vikbooking');
				break;
			case 'VBYOURPIN':
				$result = __('PIN Code', 'vikbooking');
				break;
			case 'VBOROOMREQINFOBTN':
				$result = __('Request Information', 'vikbooking');
				break;
			case 'VBOROOMREQINFOTITLE':
				$result = __('Request Information for %s', 'vikbooking');
				break;
			case 'VBOROOMREQINFONAME':
				$result = __('Full Name', 'vikbooking');
				break;
			case 'VBOROOMREQINFOEMAIL':
				$result = __('e-Mail', 'vikbooking');
				break;
			case 'VBOROOMREQINFOMESS':
				$result = __('Message', 'vikbooking');
				break;
			case 'VBOROOMREQINFOSEND':
				$result = __('Send Request', 'vikbooking');
				break;
			case 'VBOROOMREQINFOMISSFIELD':
				$result = __('Please fill in all the fields in order to request information.', 'vikbooking');
				break;
			case 'VBOROOMREQINFOSUBJ':
				$result = __('Information Request for %s', 'vikbooking');
				break;
			case 'VBOROOMREQINFOSENTOK':
				$result = __('Information Request Successfully Sent!', 'vikbooking');
				break;
			case 'VBOROOMREQINFOTKNERR':
				$result = __('Error, Invalid Token.', 'vikbooking');
				break;
			case 'VBMAILYOURBOOKING':
				$result = __('Your Booking', 'vikbooking');
				break;
			case 'VBODEFAULTDISTFEATUREONE':
				$result = __('Room Number', 'vikbooking');
				break;
			case 'VBONOPKGFOUND':
				$result = __('No active packages found', 'vikbooking');
				break;
			case 'VBOPKGLIST':
				$result = __('Packages and Offers', 'vikbooking');
				break;
			case 'VBOPKGVALIDATES':
				$result = __('Validity', 'vikbooking');
				break;
			case 'VBOPKGCOSTPERPERSON':
				$result = __('per Person', 'vikbooking');
				break;
			case 'VBOPKGCOSTPERNIGHT':
				$result = __('per Night', 'vikbooking');
				break;
			case 'VBOPKGMOREDETAILS':
				$result = __('More Details', 'vikbooking');
				break;
			case 'VBOPKGNOTFOUND':
				$result = __('Package not found or no longer available.', 'vikbooking');
				break;
			case 'VBOPKGBOOKNOWROOMS':
				$result = __('Book now your room', 'vikbooking');
				break;
			case 'VBOPKGROOMCHECKAVAIL':
				$result = __('Check Availability', 'vikbooking');
				break;
			case 'VBOPKGERRNOTFOUND':
				$result = __('Package not found.', 'vikbooking');
				break;
			case 'VBOPKGERRNOTROOM':
				$result = __('The room requested is not available for this package.', 'vikbooking');
				break;
			case 'VBOPKGERRNUMNIGHTS':
				$result = __('Invalid number of nights requested for this package.', 'vikbooking');
				break;
			case 'VBOPKGERRCHECKIND':
				$result = __('Check-in Date not allowed for this package.', 'vikbooking');
				break;
			case 'VBOPKGERRCHECKOUTD':
				$result = __('Check-out Date not allowed for this package.', 'vikbooking');
				break;
			case 'VBOPKGERREXCLUDEDATE':
				$result = __('Invalid date selected for this package (%s)', 'vikbooking');
				break;
			case 'VBOPKGLINK':
				$result = __('Packages', 'vikbooking');
				break;
			case 'VBOROOMCUSTRATEPLAN':
				$result = __('Room Rate', 'vikbooking');
				break;
			case 'VBERRDATESCLOSED':
				$result = __('We will be closed on the following dates: %s', 'vikbooking');
				break;
			case 'VBCONFIRMNUMBORPIN':
				$result = __('Confirmation Number or PIN Code', 'vikbooking');
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
			case 'VBLEGBUSYCHECKOUT':
				$result = __('Check-in Only', 'vikbooking');
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
			case 'VBOEMAILINVOICEATTACHSUBJ':
				$result = __('Invoice for your stay', 'vikbooking');
				break;
			case 'VBOEMAILINVOICEATTACHTXT':
				$result = __("Dear Customer, \nattached to this message you will find the invoice for your stay. \nThank you!", 'vikbooking');
				break;
			case 'VBOINVAPPLYFILTER':
				$result = __('Apply Filter', 'vikbooking');
				break;
			case 'VBOPRINT':
				$result = __('Print', 'vikbooking');
				break;
			case 'VBOEXTRASERVICES':
				$result = __('Extra Services', 'vikbooking');
				break;
			case 'VBCHOOSEDEPOSIT':
				$result = __('Payment Terms', 'vikbooking');
				break;
			case 'VBCHOOSEDEPOSITPAYFULL':
				$result = __('Pay full amount now', 'vikbooking');
				break;
			case 'VBCHOOSEDEPOSITPAYDEPOF':
				$result = __('Leave a deposit of %s', 'vikbooking');
				break;
			case 'VBCUSTCHOICEPAYFULLADMIN':
				$result = __('Customer has chosen to pay the full amount.', 'vikbooking');
				break;
			case 'VBCCCREDITCARDTYPE':
				$result = __('Card Type', 'vikbooking');
				break;
			case 'VBCCOFFLINECCTOGGLEFORM':
				$result = __('Hide/Show Credit Card Details Submission Form', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYCTAMONTH':
				$result = __('Error, arrivals on %s are not permitted on %s', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYCTDMONTH':
				$result = __('Error, departures on %s are not permitted on %s', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYCTARANGE':
				$result = __('Error, arrivals on %s are not permitted on the selected dates', 'vikbooking');
				break;
			case 'VBRESTRERRWDAYCTDRANGE':
				$result = __('Error, departures on %s are not permitted on the selected dates', 'vikbooking');
				break;
			case 'VBORESTRWDAYSCTA':
				$result = __('Week Days Closed to Arrival', 'vikbooking');
				break;
			case 'VBORESTRWDAYSCTD':
				$result = __('Week Days Closed to Departure', 'vikbooking');
				break;
			case 'VBOALERTFILLINALLF':
				$result = __('Please fill in or accept all the required fields', 'vikbooking');
				break;
			case 'VBOBOOKNOLONGERPAYABLE':
				$result = __('Error, this booking has a check-in date in the past and it was not confirmed on time. The booking is now Cancelled.', 'vikbooking');
				break;
			case 'VBOERRAUTOREMOVED':
				$result = __('Time over to confirm the reservation. Please make a new one.', 'vikbooking');
				break;
			case 'VBMINUTE':
				$result = __('Minute', 'vikbooking');
				break;
			case 'VBMINUTES':
				$result = __('Minutes', 'vikbooking');
				break;
			case 'VBOTIMERPAYMENTSTR':
				$result = __('You have %s left to confirm the reservation', 'vikbooking');
				break;
			case 'VBOTERMSCONDS':
				$result = __('Terms and Conditions', 'vikbooking');
				break;
			case 'VBOTERMSCONDSDEFTEXT':
				$result = __("1. Check-in time is from %s and check-out time is until %s.\n2. The guest acknowledge joint and several liability for all services rendered until full settlement of bills.\n3. Guests will be held responsible for any loss or damage to the rooms caused by themselves, their friends or any person for whom they are responsible.\n4. Hotel Management is not responsible for your personal belongings and valuables like money, jewellery or any other valuables left by guests in the rooms.\n5. Complimentary safe deposit boxes, subject to the terms and conditions for use are available in rooms.\n6. Regardless of charge instructions, I acknowledge that I am personally liable for the payment of all charges incurred by me during my stay.", 'vikbooking');
				break;
			case 'VBOSIGNTITLE':
				$result = __('Check-in Signature', 'vikbooking');
				break;
			case 'VBOCUSTOMERNOMINATIVE':
				$result = __('Customer Name', 'vikbooking');
				break;
			case 'VBOROOMSBOOKED':
				$result = __('Rooms', 'vikbooking');
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
			case 'VBOTERMSCONDSIACCEPT':
				$result = __('I accept the Terms and Conditions', 'vikbooking');
				break;
			case 'VBOTERMSCONDSACCCLOSE':
				$result = __('I have read and agree to the terms and conditions', 'vikbooking');
				break;
			case 'VBOSIGNMUSTACCEPT':
				$result = __('You must accept the Terms and Conditions.', 'vikbooking');
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
			case 'VBOSIGNATUREGUESTS':
				$result = __('Guests', 'vikbooking');
				break;
			case 'VBOSIGNATURETHANKS':
				$result = __('Thank you! Signature saved correctly', 'vikbooking');
				break;
			case 'VBOERRMAXDATEBOOKINGS':
				$result = __('Maximum date in the future allowed for booking is %s', 'vikbooking');
				break;
			case 'VBOERRMINDAYSADV':
				$result = __('Minimum days in advance for bookings is %d', 'vikbooking');
				break;
			case 'VBOSEARCHERRCODETHREEBASE':
				$result = __('the number of guests selected', 'vikbooking');
				break;
			case 'VBOBOOKSOLSUGGNIGHTS':
				$result = __('Closest booking solutions for %s', 'vikbooking');
				break;
			case 'VBOBOOKSOLSUGGCKIN':
				$result = __('Check-in on %s', 'vikbooking');
				break;
			case 'VBOBOOKSOLSUGGCKOUT':
				$result = __('Check-out on %s', 'vikbooking');
				break;
			case 'VBOSEARCHSUGGAVNEAR':
				$result = __('Availability for the dates near the ones selected', 'vikbooking');
				break;
			case 'VBOBOOKSOLSUGGPARTY':
				$result = __('Other booking solutions for %s', 'vikbooking');
				break;
			case 'VBOSEARCHNOSOLUTIONS':
				$result = __('No booking solutions found for the number of rooms and guests requested', 'vikbooking');
				break;
			case 'VBOSEARCHNOBOOKSOLUTIONS':
				$result = __('No booking solutions found for the number of nights and guests requested', 'vikbooking');
				break;
			case 'VBOBOOKSOLSEARCHROOMS':
				$result = __('Search Rooms', 'vikbooking');
				break;
			case 'VBOCANCYOURBOOKING':
				$result = __('Cancel your Booking', 'vikbooking');
				break;
			case 'VBOCANCBOOKINGREASON':
				$result = __('Please specify your cancellation reasons', 'vikbooking');
				break;
			case 'VBOBOOKCANCELLEDEMAILSUBJ':
				$result = __('Booking Cancellation', 'vikbooking');
				break;
			case 'VBOBOOKCANCELLEDEMAILHEAD':
				$result = __("The booking ID %s has been cancelled on behalf of the customer. The rate plan selected, and the cancellation terms, allowed the customer to cancel this reservation.\nBooking details: %s", 'vikbooking');
				break;
			case 'VBOBOOKCANCELLEDRESP':
				$result = __('The reservation has been cancelled successfully', 'vikbooking');
				break;
			case 'VBOERRMISSDATA':
				$result = __('Missing required data to complete the action', 'vikbooking');
				break;
			case 'VBOERRCANNOTCANCBOOK':
				$result = __('This booking cannot be cancelled due to the cancellation terms not matching the requirements', 'vikbooking');
				break;
			case 'VBOMODYOURBOOKING':
				$result = __('Modify your Booking', 'vikbooking');
				break;
			case 'VBOMODYOURBOOKINGCONF':
				$result = __('Do you really want to modify your booking?', 'vikbooking');
				break;
			case 'VBOYOURBOOKCONFAT':
				$result = __('Your confirmed booking at %s', 'vikbooking');
				break;
			case 'VBOYOURBOOKISCONF':
				$result = __('Booking Confirmed', 'vikbooking');
				break;
			case 'VBOYOURBOOKISPEND':
				$result = __('Booking waiting for the payment', 'vikbooking');
				break;
			case 'VBOYOURBOOKISCANC':
				$result = __('Booking Cancelled', 'vikbooking');
				break;
			case 'VBOMODBOOKCANCMOD':
				$result = __('Cancel Modification', 'vikbooking');
				break;
			case 'VBOERRCANNOTMODBOOK':
				$result = __('This booking cannot be modified due to the modification terms not matching the requirements', 'vikbooking');
				break;
			case 'VBOMODBOOKHELPSEARCH':
				$result = __('Select the new dates, number of rooms and guests to modify your booking', 'vikbooking');
				break;
			case 'VBOMODBOOKHELPROOMS':
				$result = __('Select the new rooms to modify your booking', 'vikbooking');
				break;
			case 'VBOMODBOOKHELPSHOWPRC':
				$result = __('Choose your rate plans to modify the booking', 'vikbooking');
				break;
			case 'VBOMODBOOKHELPOCONF':
				$result = __('Confirm your details to modify the booking', 'vikbooking');
				break;
			case 'VBOMODBOOKPREVTOT':
				$result = __('Previous Total', 'vikbooking');
				break;
			case 'VBOMODBOOKDIFFTOT':
				$result = __('Totals Difference', 'vikbooking');
				break;
			case 'VBOMODBOOKCONFIRMBTN':
				$result = __('Modify Booking', 'vikbooking');
				break;
			case 'VBOMODDEDORDER':
				$result = __('Reservation #%s Modified', 'vikbooking');
				break;
			case 'VBOMODDEDORDERC':
				$result = __('Reservation Modified', 'vikbooking');
				break;
			case 'VBOBOOKINGMODOK':
				$result = __('Your booking was successfully modified', 'vikbooking');
				break;
			case 'VBOBOOKMODLOGSTR':
				$result = __("Booking modified on %s.\nPrevious dates booked: %s.\nPrevious rooms booked: %s.\nPrevious Total: %s.", 'vikbooking');
				break;
			case 'VBCUSTOMERCOMPANY':
				$result = __('Company Name', 'vikbooking');
				break;
			case 'VBCUSTOMERCOMPANYVAT':
				$result = __('VAT ID', 'vikbooking');
				break;
			case 'VBONONREFUNDRATE':
				$result = __('Non Refundable Rate', 'vikbooking');
				break;
			case 'VBOOPERAUTHCODE':
				$result = __('Authentication Code', 'vikbooking');
				break;
			case 'VBOOPERINVAUTHCODE':
				$result = __('Invalid authentication code. Please try again', 'vikbooking');
				break;
			case 'VBOOPERPGTABLEAUX':
				$result = __('Tableaux', 'vikbooking');
				break;
			case 'VBOLOGOUT':
				$result = __('Logout', 'vikbooking');
				break;
			case 'VBONOTAUTHORIZEDRES':
				$result = __('You are not authorized to access this resource.', 'vikbooking');
				break;
			case 'VBOTABLEAUXARRTD':
				$result = __('Arriving today', 'vikbooking');
				break;
			case 'VBOTABLEAUXARRTM':
				$result = __('Arriving tomorrow', 'vikbooking');
				break;
			case 'VBOTABLEAUXDEPTD':
				$result = __('Departing today', 'vikbooking');
				break;
			case 'VBOTABLEAUXDEPTM':
				$result = __('Departing tomorrow', 'vikbooking');
				break;
			case 'VBOTABLEAUXSTYTD':
				$result = __('Staying today', 'vikbooking');
				break;
			case 'VBOTABLEAUXSTYTM':
				$result = __('Staying tomorrow', 'vikbooking');
				break;
			case 'VBOTGLFULLSCREEN':
				$result = __('Toggle Fullscreen', 'vikbooking');
				break;
			case 'VBOOPERVIEWPG':
				$result = __('View', 'vikbooking');
				break;
			case 'VBOCHATWITH':
				$result = __('Chat with %s', 'vikbooking');
				break;
			case 'VBOPRECHECKIN':
				$result = __('Pre Check-in', 'vikbooking');
				break;
			case 'VBOGUESTNUM':
				$result = __('Guest #%d', 'vikbooking');
				break;
			case 'VBOCUSTDOCTYPE':
				$result = __('ID Type', 'vikbooking');
				break;
			case 'VBOCUSTDOCNUM':
				$result = __('ID Number', 'vikbooking');
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
			case 'VBOSUBMITPRECHECKIN':
				$result = __('Save check-in information', 'vikbooking');
				break;
			case 'VBOSUBMITPRECHECKINTNKS':
				$result = __('Information correctly saved. Thank you!', 'vikbooking');
				break;
			case 'VBOPRECHECKIN':
				$result = __('Pre Check-in', 'vikbooking');
				break;
			case 'VBOADDEXTRASTOBOOK':
				$result = __('Reserve additional services', 'vikbooking');
				break;
			case 'VBOUPSELLTITLE':
				$result = __('Add some extra services to your reservation!', 'vikbooking');
				break;
			case 'VBOUPSELLADD':
				$result = __('Add', 'vikbooking');
				break;
			case 'VBOUPSELLREMOVE':
				$result = __('Remove', 'vikbooking');
				break;
			case 'VBOUPSELLUPDATE':
				$result = __('Update Reservation', 'vikbooking');
				break;
			case 'VBOUPSELLCONFIRM':
				$result = __('The selected extra services will be added to your reservation. The action cannot be undone. Do you want to proceed?', 'vikbooking');
				break;
			case 'VBOUPSELLRESULTOK':
				$result = __('Thank you! Your reservation has been updated correctly!', 'vikbooking');
				break;
			case 'VBOUPSELLQUANTOPT0':
				$result = __('Please select a quantity greater than zero', 'vikbooking');
				break;
			case 'VBOMAILSUBJECT':
				$result = __('Your reservation at %s', 'vikbooking');
				break;
			case 'VBONROOMSMISSINGPL':
				$result = __('%d rooms missing', 'vikbooking');
				break;
			case 'VBONROOMSMISSINGSI':
				$result = __('%d room missing', 'vikbooking');
				break;
			case 'VBORESERVE':
				$result = __('Reserve', 'vikbooking');
				break;
			case 'VBOLEAVEAREVIEW':
				$result = __('Leave a review', 'vikbooking');
				break;
			case 'VBOLEAVEAREVIEWSTAY':
				$result = __('Leave a review for your stay', 'vikbooking');
				break;
			case 'VBOREVIEWLEAVEMESS':
				$result = __('Please describe your experience', 'vikbooking');
				break;
			case 'VBOREVIEWMESSPRIVACY':
				$result = __('By leaving a review you agree with our Terms and Conditions and Privacy Policy.', 'vikbooking');
				break;
			case 'VBOREVIEWSUBMIT':
				$result = __('Submit review', 'vikbooking');
				break;
			case 'VBOREVIEWMESSLIM':
				$result = __('Please use a minimum of %d chars for your review message', 'vikbooking');
				break;
			case 'VBOTHANKSREVIEWLEFT':
				$result = __('Thank you! We have received your review.', 'vikbooking');
				break;
			case 'VBOREVIEWGENERROR':
				$result = __('Error, please try again.', 'vikbooking');
				break;
			case 'VBOGREVOWNREPLY':
				$result = __('Owner reply', 'vikbooking');
				break;
			case 'VBOREVIEWRATEEXP':
				$result = __('Please rate your experience', 'vikbooking');
				break;
			case 'VBOREVIEWYOURRATING':
				$result = __('Your rating', 'vikbooking');
				break;
			case 'VBOMEALPLAN':
				$result = __('Meal Plan', 'vikbooking');
				break;
			case 'VBOBEDPREFERENCE':
				$result = __('Bed preference', 'vikbooking');
				break;
			case 'VBOBOOKERISGENIUS':
				$result = __('Booker is Genius', 'vikbooking');
				break;
			case 'VBOADDCUSTOMNOTETODAY':
				$result = __('Add new note for today', 'vikbooking');
				break;
			case 'VBOCONSECUTIVEDAYS':
				$result = __('Consecutive days', 'vikbooking');
				break;
			case 'VBOUNTIL':
				$result = __('Until', 'vikbooking');
				break;
			case 'VBOSEARCHSUGGINTROCODE1':
				$result = __('The requested combination of rooms and guests is only available on other dates.', 'vikbooking');
				break;
			case 'VBOSEARCHSUGGINTROCODE2':
				$result = __('We could not find enough rooms for the party requested, but you can book a different combination of rooms.', 'vikbooking');
				break;
			case 'VBO_DEBUG_RULE_CONDTEXT':
				$result = __('[Rule %s was not compliant. Special tag %s was not applied.]', 'vikbooking');
				break;
			case 'VBODISTFEATURERUNIT':
				$result = __('Room Unit #', 'vikbooking');
				break;
			case 'VBO_GEO_ADDRESS':
				$result = __('Base Address', 'vikbooking');
				break;
			case 'VBO_CANCEL_SELECTION':
				$result = __('Do you want to cancel the selection?', 'vikbooking');
				break;
			case 'VBO_PAYNOW':
				$result = __('Pay Now', 'vikbooking');
				break;
			case 'VBO_AMOUNT_REFUNDED':
				$result = __('Amount Refunded', 'vikbooking');
				break;
			case 'VBO_CUSTOMER_UPLOAD_DOCS':
				$result = __('Upload Documents', 'vikbooking');
				break;
			case 'VBO_UPLOAD_FAILED':
				$result = __('Uploading failed. Please try again', 'vikbooking');
				break;
			case 'VBO_REMOVEF_CONFIRM':
				$result = __('Do you want to remove the selected file?', 'vikbooking');
				break;
			case 'VBO_PRECHECKIN_TOAST_HELP':
				$result = __('Click the save button at the bottom of the page when you are done.', 'vikbooking');
				break;
			case 'VBO_PRECHECKIN_DISCLAIMER':
				$result = __('Personal data is collected and processed in accordance with the Privacy Policy accepted at the time of booking.', 'vikbooking');
				break;
			case 'VBO_ALT_DATES_INQ':
				$result = __('Alternative dates were used to allocate the inquiry reservation', 'vikbooking');
				break;
			case 'VBO_ALT_PARTY_INQ':
				$result = __('Alternative room party used to allocate the inquiry reservation', 'vikbooking');
				break;
			case 'VBO_ALT_DUMMY_INQ':
				$result = __('Dummy room was used to allocate the inquiry reservation due to no availability', 'vikbooking');
				break;
			case 'VBO_INQUIRY_PENDING':
				$result = __('Pending inquiry', 'vikbooking');
				break;
			case 'VBO_INV_TAX_SUMMARY':
				$result = __('Tax Summary', 'vikbooking');
				break;
			case 'VBO_INV_TAX_ALIQUOTE':
				$result = __('Tax Rate', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAY_SOLS':
				$result = __('Split stay solutions', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAY_SOLS_DESCR':
				$result = __('By placing a split stay reservation you will have to change room(s) during your stay.', 'vikbooking');
				break;
			case 'VBO_SPLIT_STAY_RES':
				$result = __('Split stay reservation', 'vikbooking');
				break;
			case 'VBO_STATE_PROVINCE':
				$result = __('State/Province', 'vikbooking');
				break;
			case 'VBO_CANC_FEE':
				$result = __('Cancellation fee', 'vikbooking');
				break;
			case 'VBO_UPGRADE_ROOMS':
				$result = __('Get a better room', 'vikbooking');
				break;
			case 'VBO_UPGRADE_CONFIRM':
				$result = __('Confirm upgrade', 'vikbooking');
				break;
			case 'VBO_YOU_SAVE_PCENT':
				$result = __('You save %s', 'vikbooking');
				break;
			case 'VBO_KEEP_ROOM':
				$result = __('Keep this room', 'vikbooking');
				break;
			case 'VBO_DOUPGRADE_CONFIRM':
				$result = __('Do you want to upgrade to this room? Your booking will be modified.', 'vikbooking');
				break;
			case 'VBCOUPONINVMAXTOTORD':
				$result = __('This coupon requires a lower booking total amount', 'vikbooking');
				break;
			case 'VBO_RM_COUPON_CONFIRM':
				$result = __('Do you want to remove the coupon discount?', 'vikbooking');
				break;
			case 'VBO_PET':
				$result = __('Pet', 'vikbooking');
				break;
			case 'VBO_PETS':
				$result = __('Pets', 'vikbooking');
				break;
			case 'VBMENUDASHBOARD':
				$result = __('Dashboard', 'vikbooking');
				break;
			case 'VBOOPERPERMTABLEAUX':
				$result = __('Tableaux', 'vikbooking');
				break;
			case 'VBO_W_FINANCE_TITLE':
				$result = __('Finance', 'vikbooking');
				break;
			case 'VBO_GROSS_BOOKING_VALUE':
				$result = __('Gross Booking Value', 'vikbooking');
				break;
			case 'VBTOTALCOMMISSIONS':
				$result = __('Commissions', 'vikbooking');
				break;
			case 'VBOREPORTREVENUE':
				$result = __('Revenue', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREVWEB':
				$result = __('IBE Revenue', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEREVOTA':
				$result = __('OTA Revenue', 'vikbooking');
				break;
			case 'VBO_AVG_COMMISSIONS':
				$result = __('Average Commissions', 'vikbooking');
				break;
			case 'VBO_COMMISSION_SAVINGS':
				$result = __('Commission Savings', 'vikbooking');
				break;
			case 'VBCUSTOMERTOTBOOKINGS':
				$result = __('Total Bookings', 'vikbooking');
				break;
			case 'VBOGRAPHTOTNIGHTSLBL':
				$result = __('Nights Booked', 'vikbooking');
				break;
			case 'VBOREPORTREVENUEPOCC':
				$result = __('% Occupancy', 'vikbooking');
				break;
			case 'VBTRKAVGLOS':
				$result = __('Average Nights of Stay', 'vikbooking');
				break;
			case 'VBOGRAPHTOTUNITSLBL':
				$result = __('Total Units', 'vikbooking');
				break;
			case 'VBOSTATSTOPCOUNTRIES':
				$result = __('Top Countries', 'vikbooking');
				break;
			case 'VBOCHANNELS':
				$result = __('Channels', 'vikbooking');
				break;
			case 'VBORDFROMSITE':
				$result = __('Website', 'vikbooking');
				break;
			case 'VBO_LISTING_NAMES':
				// @TRANSLATORS: Listings: Studio, Villa, Apartment, Double Room.
				$result = __('Listings: %s.', 'vikbooking');
				break;
			case 'VBO_ALL_LISTINGS':
				$result = __('All listings', 'vikbooking');
				break;
		}

		return $result;
	}
}
