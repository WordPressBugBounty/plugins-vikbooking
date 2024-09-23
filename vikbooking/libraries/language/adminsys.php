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
 * Switcher class to translate the VikBooking plugin common languages.
 *
 * @since 	1.0
 */
class VikBookingLanguageAdminSys implements JLanguageHandler
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
			 * Do not touch the first definition as it gives the title to the pages of the back-end
			 */
			case 'COM_VIKBOOKING':
				$result = __('Vik Booking', 'vikbooking');
				break;

			/**
			 * Definitions
			 */
			case 'COM_VIKBOOKING_MENU':
				$result = __('VikBooking', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_VIKBOOKING_VIEW_DEFAULT_TITLE':
				$result = __('Search Form', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_VIKBOOKING_VIEW_DEFAULT_DESC':
				$result = __('Global search form to start the booking process', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMSLIST_VIEW_DEFAULT_TITLE':
				$result = __('Rooms List', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMSLIST_VIEW_DEFAULT_DESC':
				$result = __('A page that lists various room-types', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_CATEGORY_FIELD_SELECT_TITLE':
				$result = __('Category', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_CATEGORY_FIELD_SELECT_TITLE_DESC':
				$result = __('Select a rooms category', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_LOCATIONSLIST_VIEW_DEFAULT_TITLE':
				$result = __('Locations List', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_LOCATIONSLIST_VIEW_DEFAULT_DESC':
				$result = __('VikBooking Locations List', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ORDERSLIST_VIEW_DEFAULT_TITLE':
				$result = __('Bookings List', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ORDERSLIST_VIEW_DEFAULT_DESC':
				$result = __('Allows the guests to look for their bookings by entering their PIN code or Confirmation Number', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_SORTBY_FIELD_SELECT_TITLE':
				$result = __('Order Rooms By', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_SORTTYPE_FIELD_SELECT_TITLE':
				$result = __('Sort Type', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_RESLIM_FIELD_SELECT_TITLE':
				$result = __('Results per page', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMDETAILS_VIEW_DEFAULT_TITLE':
				$result = __('Room Details', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMDETAILS_VIEW_DEFAULT_DESC':
				$result = __('Shows the details of one specific room-type', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMID_FIELD_SELECT_TITLE':
				$result = __('Room/Apartment', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMID_FIELD_SELECT_TITLE_DESC':
				$result = __('Select a room/apartment from the list', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOTIONS_VIEW_DEFAULT_TITLE':
				$result = __('Promotions', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOTIONS_VIEW_DEFAULT_DESC':
				$result = __('Shows a list of all the Special Prices marked as -Promotion-', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOSHOWCARS_FIELD_TITLE':
				$result = __('Show Rooms', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOSHOWCARS_FIELD_TITLE_DESC':
				$result = __('Choose whether the rooms, for which the promotion is valid, should be displayed', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOMAXDATE_FIELD_TITLE':
				$result = __('Max date in the future', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOMAXDATE_FIELD_TITLE_DESC':
				$result = __('Choose if the promotions should be displayed if they are 3, 6 or 12 months in advance from today', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOMAXDATE_FIELD_THREEM':
				$result = __('3 Months', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOMAXDATE_FIELD_SIXM':
				$result = __('6 Months', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOMAXDATE_FIELD_YEAR':
				$result = __('1 Year', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PROMOTIONSLIM_FIELD_TITLE':
				$result = __('Max Promotions', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_AVAILABILITY_VIEW_DEFAULT_TITLE':
				$result = __('Rooms Availability', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_AVAILABILITY_VIEW_DEFAULT_DESC':
				$result = __('Shows the monthly availability of one or more rooms', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMIDS_FIELD_SELECT_TITLE':
				$result = __('Rooms', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_ROOMIDS_FIELD_SELECT_TITLE_DESC':
				$result = __('Choose the rooms to display. Leave empty to display all rooms.', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_AVSHOWTYPE_FIELD_SELECT_TITLE':
				$result = __('Rooms Units', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_AVSHOWTYPE_NONE':
				$result = __('Show Available Days with no numbers', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_AVSHOWTYPE_REMAINING':
				$result = __('Show Number of Remaining Units', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_AVSHOWTYPE_BOOKED':
				$result = __('Show Number of Units Booked', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_CONFIGURATION':
				$result = __('Vik Booking - Access Levels', 'vikbooking');
				break;
			case 'VBOACTION_GLOBAL':
				$result = __('Global Settings Management', 'vikbooking');
				break;
			case 'VBOACTION_GLOBAL_DESC':
				$result = __('Configuration, Payment Methods and Custom Fields', 'vikbooking');
				break;
			case 'VBOACTION_RATEPLANS':
				$result = __('Rate Plans Management', 'vikbooking');
				break;
			case 'VBOACTION_RATEPLANS_DESC':
				$result = __('Tax Rates, Types of Price, Coupon Codes', 'vikbooking');
				break;
			case 'VBOACTION_ROOMS':
				$result = __('Rooms Management', 'vikbooking');
				break;
			case 'VBOACTION_ROOMS_DESC':
				$result = __('Rooms, Categories, Characteristics', 'vikbooking');
				break;
			case 'VBOACTION_PRICING':
				$result = __('Pricing Management', 'vikbooking');
				break;
			case 'VBOACTION_PRICING_DESC':
				$result = __('Rates, Seasonal Prices, Promotions, Restrictions, Options', 'vikbooking');
				break;
			case 'VBOACTION_BOOKINGS':
				$result = __('Bookings Management', 'vikbooking');
				break;
			case 'VBOACTION_BOOKINGS_DESC':
				$result = __('View Bookings, Create new Bookings', 'vikbooking');
				break;
			case 'VBOACTION_AVAILABILITY':
				$result = __('Availability Management', 'vikbooking');
				break;
			case 'VBOACTION_AVAILABILITY_DESC':
				$result = __('Availability Overview and Calendar', 'vikbooking');
				break;
			case 'VBOACTION_MANAGEMENT':
				$result = __('Management Operations', 'vikbooking');
				break;
			case 'VBOACTION_MANAGEMENT_DESC':
				$result = __('Graphs and Statistics, Cron Jobs, Translations, Customers', 'vikbooking');
				break;
			case 'VBOACTION_PMS':
				$result = __('PMS Access', 'vikbooking');
				break;
			case 'VBOACTION_PMS_DESC':
				$result = __('Reports and all the related PMS functions', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PACKAGESLIST_VIEW_DEFAULT_TITLE':
				$result = __('Packages List', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PACKAGESLIST_VIEW_DEFAULT_DESC':
				$result = __('Packages and Offers List', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PACKAGEDETAILS_VIEW_DEFAULT_TITLE':
				$result = __('Package Details', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PACKAGEDETAILS_VIEW_DEFAULT_DESC':
				$result = __('Package Details page', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_PKGDET_FIELD_SELECT_TITLE':
				$result = __('Select Package', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_BOOKING_VIEW_DEFAULT_TITLE':
				$result = __('Booking Details', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_BOOKING_VIEW_DEFAULT_DESC':
				$result = __('This type of Shortcode should be used on a hidden Post/Page for the plugin to use the permalink to rewrite the URLs of the booking details page. This Shortcode will produce no content, use the Bookings List instead.', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_OPERATORS_VIEW_DEFAULT_TITLE':
				$result = __('Operators Login', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_OPERATORS_VIEW_DEFAULT_DESC':
				$result = __('Dashboard page for the operators', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_CATEGORY_FIELD_FORCE_TITLE':
				$result = __('Force Category', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_CATEGORY_FIELD_FORCE_TITLE_DESC':
				$result = __('If you would like the results to be taken only from a specific category, set this filter to an existing category where some of the rooms should be assigned.', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_LAYOUT_STYLE':
				$result = __('Layout style', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_LAYOUT_GRID':
				$result = __('Grid', 'vikbooking');
				break;
			case 'COM_VIKBOOKING_LAYOUT_LIST':
				$result = __('List', 'vikbooking');
				break;

			/**
			 * @wponly Definitions for the Views "Gotopro" and "Getpro"
			 */
			case 'VBMAINGOTOPROTITLE':
				$result = __('Vik Booking - Upgrade to Pro', 'vikbooking');
				break;
			case 'VBOLICKEYVALIDUNTIL':
				$result = __('License Key valid until %s', 'vikbooking');
				break;
			case 'VBOLICKEYEXPIREDON':
				$result = __('Your License Key expired on %s', 'vikbooking');
				break;
			case 'VBOEMPTYLICKEY':
				$result = __('Please enter a valid License Key', 'vikbooking');
				break;
			case 'VBONOPROERROR':
				$result = __('No valid and active License Key found.', 'vikbooking');
				break;
			case 'VBMAINGETPROTITLE':
				$result = __('Vik Booking - Downloading Pro version', 'vikbooking');
				break;
			case 'VBOUPDCOMPLOKCLICK':
				$result = __('Update Completed. Please click here', 'vikbooking');
				break;
			case 'VBOUPDCOMPLNOKCLICK':
				$result = __('Update Failed. Please click here', 'vikbooking');
				break;
			case 'VBOPROPLWAIT':
				$result = __('Please wait', 'vikbooking');
				break;
			case 'VBOPRODLINGPKG':
				$result = __('Downloading the package...', 'vikbooking');
				break;
			case 'VBOPROTHANKSUSE':
				$result = __('Thanks for using the Pro version', 'vikbooking');
				break;
			case 'VBOPROTHANKSLIC':
				$result = __('Make sure to keep your License Key active in order to be able to receive future updates with all the new features.', 'vikbooking');
				break;
			case 'VBOPROGETRENEWLICFROM':
				$result = __('Get or renew your License Key from VikWP.com', 'vikbooking');
				break;
			case 'VBOPROGETRENEWLIC':
				$result = __('Renew your licence', 'vikbooking');
				break;
			case 'VBOPROVALNUPD':
				$result = __('Validate and Update', 'vikbooking');
				break;
			case 'VBOPROALREADYHAVEKEY':
				$result = __('Already have Vik Booking PRO? <br /> <small>Enter your licence key here</small>', 'vikbooking');
				break;
			case 'VBOPROWHYUPG':
				$result = __('Why Upgrade to Pro?', 'vikbooking');
				break;
			case 'VBOPROTRUEVBOPRO':
				$result = __('The true Vik Booking is Pro. Unleash the power of the only certified Booking Engine plugin for accomodations!', 'vikbooking');
				break;
			case 'VBOPROGETLICNUPG':
				$result = __('Get your License Key and Upgrade to PRO', 'vikbooking');
				break;
			case 'VBOPROWHYRATES':
				$result = __('Set up your daily/seasonal prices with just a few clicks', 'vikbooking');
				break;
			case 'VBOPROWHYRATESDESC':
				$result = __('Set different rates on some seasons, holidays, weekends or days of the year with just two clicks. Bookings Restrictions: define the minimum or maximum nights of stay for certain dates of the year and rooms, set days closed to arrival or departure.', 'vikbooking');
				break;
			case 'VBOPROWHYBOOKINGS':
				$result = __('Create and Modify Bookings via back-end', 'vikbooking');
				break;
			case 'VBOPROWHYBOOKINGSDESC':
				$result = __('The page Bookings Calendar will let you create new reservations manually, maybe to register walk-in customers or offline reservations. Modify the dates and switch, add or remove rooms of certain reservations.', 'vikbooking');
				break;
			case 'VBOPROWHYCHMANAGER':
				$result = __('Channels Management Capabilities', 'vikbooking');
				break;
			case 'VBOPROWHYCHMANAGERDESC':
				$result = __('<p>Only Vik Booking Pro is capable of working together on the same website with the complementary plugin Vik Channel Manager.</p><p>Turn your website into a professional and full solution of Booking Engine + PMS and Channel Manager to sync availability, rates and bookings with the most popular channels of the world.</p>', 'vikbooking');
				break;
			case 'VBOPROWHYUNLOCKF':
				$result = __('Unlock over 50 must-have features', 'vikbooking');
				break;
			case 'VBOPROWHYCUSTOMERS':
				$result = __('Customers Management', 'vikbooking');
				break;
			case 'VBOPROWHYCUSTOMERSDESC':
				$result = __('Keep track of the information of your guests without sharing such details with external systems', 'vikbooking');
				break;
			case 'VBOPROWHYPMSREP':
				$result = __('PMS Reports', 'vikbooking');
				break;
			case 'VBOPROWHYPMSREPDESC':
				$result = __('Reports for Revenue, Tourist Taxes and country-specific integrations for local authorities', 'vikbooking');
				break;
			case 'VBOPROWHYPROMOTIONS':
				$result = __('Promotions and Packages', 'vikbooking');
				break;
			case 'VBOPROWHYPROMOTIONSDESC':
				$result = __('Set charges/discounts with absolute/percent values. Create packages with services included', 'vikbooking');
				break;
			case 'VBOPROWHYPACKAGES':
				$result = __('Packages and Offers', 'vikbooking');
				break;
			case 'VBOPROWHYINVOICES':
				$result = __('Invoices', 'vikbooking');
				break;
			case 'VBOPROWHYINVOICESDESC':
				$result = __('Generate invoices, manually or automatically, and send them to your customers via email', 'vikbooking');
				break;
			case 'VBOPROWHYCHECKIN':
				$result = __('Online Check-in and Registration', 'vikbooking');
				break;
			case 'VBOPROWHYCHECKINDESC':
				$result = __('Guests registration supporting signature pads for check-in and check-out', 'vikbooking');
				break;
			case 'VBOPROWHYGRAPHS':
				$result = __('Graphs and Statistics', 'vikbooking');
				break;
			case 'VBOPROWHYGRAPHSDESC':
				$result = __('Monitor your business trends thanks to the Graphs & Report functions', 'vikbooking');
				break;
			case 'VBOPROWHYCRONS':
				$result = __('Automatise Email and SMS', 'vikbooking');
				break;
			case 'VBOPROWHYCRONSDESC':
				$result = __('Schedule the automated sending of certain reminders to your guests', 'vikbooking');
				break;
			case 'VBOPROWHYSMS':
				$result = __('SMS Gateways', 'vikbooking');
				break;
			case 'VBOPROWHYPAYMENTS':
				$result = __('Payment Methods', 'vikbooking');
				break;
			case 'VBOPROWHYPAYMENTSDESC':
				$result = __('PayPal, Offline Credit Card and Bank Transfer pre-installed', 'vikbooking');
				break;
			case 'VBOPROREADYTOUPG':
				$result = __('Ready to upgrade?', 'vikbooking');
				break;
			case 'VBOPROGETNEWLICFROM':
				$result = __('Get Vik Booking PRO and start now.', 'vikbooking');
				break;
			case 'VBOPROGETNEWLIC':
				$result = __('Get your License Key', 'vikbooking');
				break;
			case 'VBOPROVALNINST':
				$result = __('Upgrade Now', 'vikbooking');
				break;
			case 'VBOGOTOPROBTN':
				$result = __('Upgrade to PRO', 'vikbooking');
				break;
			case 'VBOISPROBTN':
				$result = __('PRO Version', 'vikbooking');
				break;
			case 'VBOLICKEYVALIDVCM':
				$result = __('Active License Key', 'vikbooking');
				break;
			case 'VBOPROVCMADTITLE':
				$result = __('Want to avoid the risk of overbooking?', 'vikbooking');
				break;
			case 'VBOPROVCMADDESCR':
				$result = __('The Vik Booking + Vik Channel Manager plugins suite is the very first and only native solution for WordPress to be officially certificated as <strong>Premier Connectivity Partner of Booking.com</strong>.', 'vikbooking');
				break;
			case 'VBOPROVCMADMOREINFO':
				$result = __('MORE INFORMATION', 'vikbooking');
				break;
			case 'VBOPROVCMADSOMECHAV':
				$result = __('Some of the channels available', 'vikbooking');
				break;
			case 'VBOPROVCMADCHANDMANY':
				$result = __('and many others...', 'vikbooking');
				break;
			case 'VBOPROVCMADDONTSHOW':
				$result = __('Don\'t show again', 'vikbooking');
				break;
			case 'VBOPROWHYEXTRASERVICES':
				$result = __('Configure optional and mandatory Extra Services', 'vikbooking');
				break;
			case 'VBOPROWHYEXTRASERVICESDESC':
				$result = __('Assign to your rooms some optional services that guests can order during the booking process, like breakfast, transfers or parking.<br />You can also define mandatory Fees or Taxes, like Cleaning Fees or Tourist Taxes to be paid at the time of booking.', 'vikbooking');
				break;
			case 'VBOPROWHYREPORT':
				$result = __('Occupancy Ranking report to analyse every detail', 'vikbooking');
				break;
			case 'VBOPROWHYREPORTDESC':
				$result = __('Get to monitor your future occupancy through the Occupancy Ranking report. Filter the targets by dates and analyse the data by day, week or month. The report will provide the information about the occupancy, the total number of rooms sold, nights booked, revenues and more.', 'vikbooking');
				break;
			case 'VBOPROWHYCRONJOB':
				$result = __('Notify your customers with automatic email and save time', 'vikbooking');
				break;
			case 'VBOPROWHYCRONJOBDESC':
				$result = __('<p>The relationship with your customers is fundamental for your business.</p><p>Automatise certain tasks, such as sending automatic reminders via email or SMS to your guests, either before the check-in to provide additional information, or after the check-out maybe by asking them to leave a review.</p><p>Generate and send the invoices automatically, invite your guests to fill the pre-checkin form, and much more.</p>', 'vikbooking');
				break;
			case 'VBOPROWHYMOREEXTRA':
				$result = __('and much more...', 'vikbooking');
				break;
			case 'VBOPROREADYTOINCREASE':
				$result = __('Ready to increase your bookings?', 'vikbooking');
				break;
			case 'VBOPROALREADYHAVEPRO':
				$result = __('Already have Vik Booking PRO? Upgrade to the PRO version <a href="#upgrade">here</a>.', 'vikbooking');
				break;
			case 'VBOPROREDUCEOTAFEES':
				$result = __('Would you like to reduce OTA commissions?', 'vikbooking');
				break;
			case 'VBOPROCOLLECTDIRECTBOOK':
				$result = __('Start collecting direct bookings from your own website', 'vikbooking');
				break;
			case 'VBOPROBOOKINGENGINEPMS':
				$result = __('Vik Booking PRO: the Booking Engine and PMS plugin<br />for Hotels and Accommodations on WordPress', 'vikbooking');
				break;
			case 'VBOPROADVONE':
				$result = __('Start collecting direct bookings from your website', 'vikbooking');
				break;
			case 'VBOPROADVTWO':
				$result = __('Reduce OTA commissions', 'vikbooking');
				break;
			case 'VBOPROADVTHREE':
				$result = __('No recurring fees. Become independent', 'vikbooking');
				break;
			case 'VBOPROCOLLECTDIRCTBOOKTITLE':
				$result = __('1<span>.</span> Start collect<br />direct bookings', 'vikbooking');
				break;
			case 'VBOPROCOLLECTDIRCTBOOKTITLEDESC':
				$result = __('<p>Increase your reputation, receive and manage all the bookings through your website.</p><p>Collect payments through your preferred bank (PayPal is included for free).</p>', 'vikbooking');
				break;
			case 'VBOPROSAVEOTASFEESTITLE':
				$result = __('2<span>.</span> Save on<br />OTA commissions', 'vikbooking');
				break;
			case 'VBOPROSAVEOTASFEESTITLEDESC':
				$result = __('<p>OTAs, such as Booking.com or Expedia, charge your property high fees for every booking.</p><p>Power up your website to create competition, and to reduce the costs.</p>', 'vikbooking');
				break;
			case 'VBOPROBECAMEINDEPENDENTTITLE':
				$result = __('3<span>.</span> Become truly<br />independent', 'vikbooking');
				break;
			case 'VBOPROBECAMEINDEPENDENTTITLEDESC':
				$result = __('<p>A Booking Engine fully integrated with your website with no monthly fees or commissions.</p><p>Stop &quot;renting&quot; external systems and increase your revenue.</p>', 'vikbooking');
				break;
			case 'VBOPROSYNCHNEWBOOKINGS':
				$result = __('Synchronize new bookings in real-time, manage the rates and keep the availability up to date with the most popular OTAs.', 'vikbooking');
				break;
			case 'VBOPROVCMSYNCHEVERYTHING':
				$result = __('With the complementary plugin Vik Channel Manager everything will be synced automatically.', 'vikbooking');
				break;
			/**
			 * @wponly - First Setup Dashboard
			 */
			case 'VBFIRSTSETSHORTCODES':
				$result = __('Shortcodes in Pages/Posts', 'vikbooking');
				break;
			/**
			 * @wponly - Free version texts
			 */
			case 'VBOFREEPAYMENTSDESCR':
				$result = __('Allow your guests to pay their bookings online through your preferred bank gateway. The Pro version comes with an integration for PayPal Standard and two more payment solutions, but the framework could be extended by installing apposite payment plugins for Vik Booking for your preferred bank.', 'vikbooking');
				break;
			case 'VBOFREECOUPONSDESCR':
				$result = __('Thanks to the coupon codes you can give your clients some dedicated discounts for their reservations.', 'vikbooking');
				break;
			case 'VBOFREEPACKAGESDESCR':
				$result = __('The Packages will let you create some particular booking solutions with some benefits and restrictions. For example, you could offer a stay for a particular weekend with the access to the SPA if booked N days in advance.', 'vikbooking');
				break;
			case 'VBOFREEOPTIONSDESCR':
				$result = __('Allow your guests to book some extra services, either they are optional or mandatory. This function can be used to create services or fees like tourist taxes, breakfast, transfers and anything else that could be booked with the rooms.', 'vikbooking');
				break;
			case 'VBOFREESEASONSDESCR':
				$result = __('This function will let you create seasonal prices, promotions or special rates for the weekends or any other day of the week. Those who are used to work with seasonal rates will find this feature fundamental.', 'vikbooking');
				break;
			case 'VBOFREERESTRSDESCR':
				$result = __('The booking restrictions will let you define a minimum or maximum number of nights of stay for specific rooms and dates of the year. You could also allow or deny the arrival/departure on some specific days of the week.', 'vikbooking');
				break;
			case 'VBOFREECUSTOMERSDESCR':
				$result = __('Here you can manage all of your customers information, send specific email or SMS messages, and manage their documents.', 'vikbooking');
				break;
			case 'VBOFREEINVOICESDESCR':
				$result = __('Manage all the invoices generated for the various bookings or services.', 'vikbooking');
				break;
			case 'VBOFREESTATSDESCR':
				$result = __('This page will display graphs and charts by showing important information and statistics about your reservations, occupancy and revenue.', 'vikbooking');
				break;
			case 'VBOFREECRONSDESCR':
				$result = __('Cron Jobs are essentials to automatize certain functions, such as to send email/SMS reminders to your clients before the check-in, after the check-out, remaing balance payments and much more.', 'vikbooking');
				break;
			case 'VBOFREEOPERATORSDESCR':
				$result = __('This function will let you manage the operator accounts to access certain functions via front-end, such as the Tableaux to see the next arrivals or departures.', 'vikbooking');
				break;
			case 'VBOFREEREPORTSDESCR':
				$result = __('Reports are essentials to obtain and/or export data. You can use them to calculate your revenue on some dates, your occupancy, or to generate documents for your local authorities. This framework is also extendable with custom PMS reports.', 'vikbooking');
				break;
			case 'VBOFREEEINVDESCR':
				$result = __('Some countries may require the invoices to be generated in electronic format (XML) for transmitting them to the local authorities. This framework could be extended to meet your country requirements.', 'vikbooking');
				break;
			/**
			 * @wponly Definitions for the View Import Bookings From Third Party Plugins (importbftpp)
			 */
			case 'VBOMAINIMPORTBFTPPTITLE':
				$result = __('Vik Booking - Import reservations from third party plugins', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_NOVBOROOMS':
				$result = __('There are no rooms in Vik Booking yet. Please create some room-types before importing the reservations from third party plugins into Vik Booking.', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_DISCLAIMER':
				$result = __('This tool is provided as-is, with no warranty of any kind. Third party plugins may change their structure or the data they contain may be unwanted. Please use this tool carefully, and consider uninstalling for then re-installing Vik Booking in case you do not obtain the desired result. If some reservations can be imported from a third party plugin, you may uninstall the third party plugin after the import, to continue with the setup of Vik Booking.', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_AVAILPLUGINS':
				$result = __('Third party plugins installed for importing the reservations', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_NOPLUGINS':
				$result = __('No supported third party plugins found, or no third party plugins have some reservations that could be imported into Vik Booking.', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_READBFROMP':
				$result = __('Load reservations from third party plugin', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_READBFROMP_SHORT':
				$result = __('Load reservations', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_NORESINTPP':
				$result = __('No valid reservations found for import from the selected third party plugin.', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_TOTRESFOUND':
				$result = __('Reservations found for import: %d', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_MAKERMAPPING':
				$result = __('Please select the corresponding room-type of Vik Booking for each room found in the third party plugin. In case of room-types with multiple sub-units, just select the corresponding room-type in Vik Booking for each sub-unit of the third party plugin. If some rooms of the third party plugin will not have a corresponding room-type in Vik Booking, those reservations will not be imported. Make sure to make the right selections, as this will affect the availability in Vik Booking after importing the reservations.', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_MAKERMAPPING_SHORT':
				$result = __('Select the corresponding rooms in Vik Booking', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_SELTPPROOM':
				$result = __('Third party plugin room', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_SELVBOROOM':
				$result = __('Corresponding room in Vik Booking', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_DOIMPORT':
				$result = __('Import selected reservations into Vik Booking', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_DOIMPORT_SHORT':
				$result = __('Import reservations', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_DOIMPORT_CONF':
				$result = __('All the selected reservations will be imported. Continue?', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_IMPORT_TOTRES':
				$result = __('Total reservations imported from third party plugin: %d', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_ALREADY_IMPORTED':
				$result = __('Already imported on %s', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_BOOKHIST_DESCR':
				$result = __('Booking imported from third party plugin', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_SELRES_SHORT':
				$result = __('Select the reservations you wish to import', 'vikbooking');
				break;
			case 'VBO_IMPBFTPP_WANTTO':
				$result = __('Want to import the reservations from other plugins?', 'vikbooking');
				break;
			case 'VBO_IMPBFROM_INTO_VBO':
				$result = __('Import reservations from %s into Vik Booking', 'vikbooking');
				break;
			/**
			 * @wponly Definitions for the Shortcodes view
			 */
			case 'VBO_SC_VIEWFRONT':
				$result = __('View page in front site', 'vikbooking');
				break;
			case 'VBO_SC_ADDTOPAGE':
				$result = __('Create page', 'vikbooking');
				break;
			case 'VBO_SC_VIEWTRASHPOSTS':
				$result = __('View trashed posts', 'vikbooking');
				break;
			case 'VBO_SC_ADDTOPAGE_HELP':
				$result = __('You can always create a custom page or post manually and use this Shortcode text inside it. By proceeding, a page containing this Shortcode will be created automatically.', 'vikbooking');
				break;
			case 'VBO_SC_ADDTOPAGE_OK':
				$result = __('The Shortcode was successfully added to a new page of your website. Visit the new page in the front site to see the content (if any).', 'vikbooking');
				break;
			/**
			 * @wponly - Sample Data texts
			 */
			case 'VBODASHINSTSAMPLEDTXT':
				$result = __('Alternatively, you can install one Sample Data package to skip the initial setup steps.', 'vikbooking');
				break;
			case 'VBODASHINSTSAMPLEDBTN':
				$result = __('Select Sample Data', 'vikbooking');
				break;
			case 'VBO_SAMPLEDATA_MENU_TITLE':
				$result = __('Vik Booking - Install Sample Data', 'vikbooking');
				break;
			case 'VBO_SAMPLEDATA_INSTALL':
				$result = __('Install Sample Data', 'vikbooking');
				break;
			case 'VBO_SAMPLEDATA_INTRO_DESCR':
				$result = __('Choose the type of Sample Data you would like to install. This operation will populate the plugin with some demo contents to complete the first configuration.', 'vikbooking');
				break;
			case 'VBO_SAMPLEDATA_INTRO_SUBDESCR':
				$result = __('To undo the installation of the sample data, you can deactivate and delete the plugin for then re-installing it. Otherwise, you can modify or remove some demo contents according to your needs.', 'vikbooking');
				break;
		}

		return $result;
	}
}
