<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('_JEXEC') or defined('ABSPATH') or die('No script kiddies please!');

defined('_VIKBOOKINGEXEC') OR die('Restricted Area');

/**
 * This is the Template used for generating the Customer Check-in Document
 * List of available special-tags that can be used in this template:
 *
 * {company_name}
 * {company_logo}
 * {company_info}
 * {checkin_date}
 * {checkout_date}
 * {num_nights}
 * {tot_guests}
 * {tot_adults}
 * {tot_children}
 * {checkin_comments}
 * {terms_and_conditions}
 *
 * The record of the booking can be accessed from the following global array in case you need any extra special tag or to perform queries for a deeper customization level:
 * $booking_info (booking array)
 * $booking_rooms (booked rooms array)
 * Example: the ID of the booking is contained in $booking_info['id'] - you can display that value within PHP tags with "echo $booking_info['id']" - or see the whole array content with the code "print_r($booking_info)"
 *
 * It is also possible to access the customer information array by using the variable $customer.
 * Debug the content of the array with the code "print_r($customer)" by placing it on any part of the PDF content below.
 */

//Custom Check-in Document PDF Template Parameters - Start
define('VBO_CHECKIN_PDF_PAGE_ORIENTATION', 'P'); //define a constant - P=portrait, L=landscape (P by default or if not specified)
define('VBO_CHECKIN_PDF_UNIT', 'mm'); //define a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default or if not specified)
define('VBO_CHECKIN_PDF_PAGE_FORMAT', 'A4'); //define a constant - A4 by default or if not specified. Could be also a custom array of width and height but constants arrays are only supported in PHP7
define('VBO_CHECKIN_PDF_MARGIN_LEFT', 10); //define a constant - 15 by default or if not specified
define('VBO_CHECKIN_PDF_MARGIN_TOP', 10); //define a constant - 27 by default or if not specified
define('VBO_CHECKIN_PDF_MARGIN_RIGHT', 10); //define a constant - 15 by default or if not specified
define('VBO_CHECKIN_PDF_MARGIN_HEADER', 1); //define a constant - 5 by default or if not specified
define('VBO_CHECKIN_PDF_MARGIN_FOOTER', 5); //define a constant - 10 by default or if not specified
define('VBO_CHECKIN_PDF_MARGIN_BOTTOM', 5); //define a constant - 25 by default or if not specified
define('VBO_CHECKIN_PDF_IMAGE_SCALE_RATIO', 1.25); //define a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default or if not specified)
$checkin_params = array(
	'show_header' => 0, //0 = false (do not show the header) - 1 = true (show the header)
	'header_data' => array(), //if empty array, no header will be displayed. The array structure is: array(logo_in_tcpdf_folder, logo_width_mm, title, text, rgb-text_color, rgb-line_color). Example: array('logo.png', 30, 'Hotel xy', 'Versilia Coast, xyz street', array(0,0,0), array(0,0,0))
	'show_footer' => 0, //0 = false (do not show the footer) - 1 = true (show the footer)
	'pdf_page_orientation' => 'VBO_CHECKIN_PDF_PAGE_ORIENTATION', //must be a constant - P=portrait, L=landscape (P by default)
	'pdf_unit' => 'VBO_CHECKIN_PDF_UNIT', //must be a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default)
	'pdf_page_format' => 'VBO_CHECKIN_PDF_PAGE_FORMAT', //must be a constant defined above or an array of custom values like: 'pdf_page_format' => array(400, 300)
	'pdf_margin_left' => 'VBO_CHECKIN_PDF_MARGIN_LEFT', //must be a constant - 15 by default
	'pdf_margin_top' => 'VBO_CHECKIN_PDF_MARGIN_TOP', //must be a constant - 27 by default
	'pdf_margin_right' => 'VBO_CHECKIN_PDF_MARGIN_RIGHT', //must be a constant - 15 by default
	'pdf_margin_header' => 'VBO_CHECKIN_PDF_MARGIN_HEADER', //must be a constant - 5 by default
	'pdf_margin_footer' => 'VBO_CHECKIN_PDF_MARGIN_FOOTER', //must be a constant - 10 by default
	'pdf_margin_bottom' => 'VBO_CHECKIN_PDF_MARGIN_BOTTOM', //must be a constant - 25 by default
	'pdf_image_scale_ratio' => 'VBO_CHECKIN_PDF_IMAGE_SCALE_RATIO', //must be a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default)
	'header_font_size' => '8', //must be a number
	'body_font_size' => '8', //must be a number
	'footer_font_size' => '6' //must be a number
);
$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();
$all_countries = VikBooking::getCountriesArray();
defined('_VIKBOOKING_CHECKIN_PARAMS') OR define('_VIKBOOKING_CHECKIN_PARAMS', '1');
//Custom Check-in Document PDF Template Parameters - End
?>

<div style="display: inline-block; width: 100%;">

	<?php
	//Company Name, Logo and Details + Document Title - Start
	?>
	<h3 style="text-align: center; color: #444;">{company_name}</h3>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="50%" align="left" valign="top">{company_info}</td>
			<td width="50%" align="right" valign="top">{company_logo}</td>
		</tr>
	</table>
	<br/>
	<h4 style="text-align: center;"><?php echo JText::translate('VBOCHECKINDOCTITLE'); ?></h4>
	<p> </p>
	<?php
	//Company Name, Logo and Details + Document Title - End

	//Customer Name + Check-in/Check-out Dates - Start
	?>
	<table width="100%" align="center" border="0" cellspacing="1" cellpadding="2" border="1" style="border: 1px solid #363636;">
		<tr>
			<td align="center" valign="top"><strong><?php echo JText::translate('VBCUSTOMERFIRSTNAME'); ?></strong></td>
			<td align="center" valign="top"><strong><?php echo JText::translate('VBCUSTOMERLASTNAME'); ?></strong></td>
			<td align="center" valign="top"><strong><?php echo JText::translate('VBOCHECKINDOCARRVDT'); ?></strong></td>
			<td align="center" valign="top"><strong><?php echo JText::translate('VBOCHECKINDOCDEPADT'); ?></strong></td>
		</tr>
		<tr>
			<td align="center"><?php echo count($customer) ? $customer['first_name'] : ''; ?></td>
			<td align="center"><?php echo count($customer) ? $customer['last_name'] : ''; ?></td>
			<td align="center">{checkin_date}</td>
			<td align="center">{checkout_date}</td>
		</tr>
	</table>
	<p> </p>
	<?php
	//Customer Name + Check-in/Check-out Dates - End

	//Rooms information and Guests Details - Start
	foreach ($booking_rooms as $k => $room) {
		//Room Specific Unit
		$spec_unit = '';
		if (!empty($room['params'])) {
			$room_params = json_decode($room['params'], true);
			$arr_features = array();
			if (is_array($room_params) && array_key_exists('features', $room_params) && @count($room_params['features']) > 0) {
				foreach ($room_params['features'] as $rind => $rfeatures) {
					foreach ($rfeatures as $fname => $fval) {
						if (strlen($fval)) {
							$arr_features[$rind] = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
							break;
						}
					}
				}
			}
			if (isset($arr_features[$room['roomindex']])) {
				$spec_unit = $arr_features[$room['roomindex']];
			}
		}
		//
		?>
	<table width="100%" border="0" cellspacing="1" cellpadding="2" style="border: 1px solid #000000;">
		<tr>
			<td align="left" valign="top" style="border-right: 1px solid #000000;"><strong><?php echo $room['room_name']; ?></strong></td>
			<td align="center" valign="top" style="border-right: 1px solid #000000;"><strong><?php echo JText::translate('VBEDITORDERADULTS'); ?></strong></td>
			<td align="center" valign="top"><strong><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></strong></td>
		</tr>
		<tr>
			<td align="left" style="border-bottom: 1px solid #000000; border-right: 1px solid #000000;"><?php echo $spec_unit; ?></td>
			<td align="center" style="border-bottom: 1px solid #000000; border-right: 1px solid #000000;"><?php echo $room['adults']; ?></td>
			<td align="center" style="border-bottom: 1px solid #000000;"><?php echo $room['children']; ?></td>
		</tr>
		<?php
		if (count($customer) && isset($customer['pax_data'][$k]) && count($customer['pax_data'][$k]) > 0) {
			?>
		<tr>
			<td align="left" valign="top" style="border-right: 1px solid #000000;"><strong><?php echo JText::translate('VBOGUESTSDETAILS'); ?></strong></td>
			<td align="center" valign="top" style="border-right: 1px solid #000000;"><strong><?php echo JText::translate('VBCUSTOMERFIRSTNAME'); ?></strong></td>
			<td align="center" valign="top"><strong><?php echo JText::translate('VBCUSTOMERLASTNAME'); ?></strong></td>
		</tr>
		<tr>
			<td style="border-right: 1px solid #000000;"> </td>
			<td align="center" valign="top" style="border-right: 1px solid #000000;">
			<?php
			foreach ($customer['pax_data'][$k] as $guest_num => $guest) {
				//by default we display the first and last name of each guest (other fields available are: 'country', 'docnum', 'extranotes')
				//in this cell we display the first name of each guest, one per line
				?>
				<span><?php echo $guest['first_name']; ?></span>
				<?php
				if ($guest_num < $room['adults']) {
					echo '<br/>';
				}
			}
			?>
			</td>
			<td align="center" valign="top">
			<?php
			foreach ($customer['pax_data'][$k] as $guest_num => $guest) {
				//by default we display the first and last name of each guest (other fields available are: 'country', 'docnum', 'extranotes')
				//in this cell we display the last name of each guest, one per line
				?>
				<span><?php echo $guest['last_name']; ?></span>
				<?php
				if ($guest_num < $room['adults']) {
					echo '<br/>';
				}
			}
			?>
			</td>
		</tr>
			<?php
		}
		?>
	</table>
		<?php
	}
	//Rooms information and Guests Details - End

	//Customer Billing Information - Start
	$customer_details = array(
		'company' => JText::translate('VBCUSTOMERCOMPANY'),
		'vat' => JText::translate('VBCUSTOMERCOMPANYVAT'),
		'email' => JText::translate('VBCUSTOMEREMAIL'),
		'phone' => JText::translate('VBCUSTOMERPHONE'),
		'address' => JText::translate('VBCUSTOMERADDRESS'),
		'city' => JText::translate('VBCUSTOMERCITY'),
		'zip' => JText::translate('VBCUSTOMERZIP'),
		'country' => JText::translate('VBCUSTOMERCOUNTRY'),
		'doctype' => JText::translate('VBCUSTOMERDOCTYPE'),
		'docnum' => JText::translate('VBCUSTOMERDOCNUM')
	);
	//display 3 details per row
	$details_per_row = 3;
	$details_counter = 0;
	$rows_written = 0;
	?>

	<p> </p>

	<?php
	if (count($customer)) {
	?>
	<table width="100%" align="center" border="0" cellspacing="1" cellpadding="3" border="1" rules="rows">
		<tr>
		<?php
		foreach ($customer_details as $key => $field) {
			if (!isset($customer[$key]) || empty($customer[$key])) {
				continue;
			}
			$details_counter++;
			$current_value = $customer[$key];
			if ($key == 'country') {
				//display the full country name, rather than the 3-chars-code version of the country
				$current_value = isset($all_countries[$customer[$key]]) ? $all_countries[$customer[$key]]['country_name'] : $current_value;
			}
			?>
		<td align="center" valign="top">
			<strong><?php echo $field; ?></strong>
			<br/>
			<span><?php echo $current_value; ?></span>
		</td>
			<?php
			if ((($rows_written * $details_per_row) + $details_counter) < count($customer_details) && $details_counter >= $details_per_row) {
				$details_counter = 0;
				$rows_written++;
				echo '</tr><tr>';
			}
		}
		// this syntax with the comparison operators is compatible with DOMDocument
		if (!($details_counter > $details_per_row)) {
			for ($i = $details_per_row; $i > $details_per_row; $i--) {
				echo '<td align="center" valign="top"> </td>';
			}
		}
		?>
		</tr>
	</table>
	<?php
	}
	?>

	<p> </p>

	<?php
	//Customer Billing Information - End

	//Terms and Conditions Text - Start
	?>
	<p>{terms_and_conditions}</p>
	<?php
	//Terms and Conditions Text - End

	//Customer Signature + Date - Start
	?>
	<div style="display: inline-block; width: 100%;">
		<table width="100%" border="0" cellspacing="1" cellpadding="1">
			<tr>
				<td align="left" valign="top"><strong><?php echo JText::translate('VBOCHECKINDOCTODAYDT'); ?></strong></td>
				<td align="center" valign="top"><strong><?php echo JText::translate('VBOCHECKINDOCGUESTSIGN'); ?></strong></td>
			</tr>
			<tr>
				<td align="left" valign="top"><?php echo date(str_replace("/", $datesep, $df)); ?></td>
				<td align="center" style="border-bottom: 1px solid #363636;">
				<?php
				// this syntax is compatible with DOMDocument
				if (!empty($customer['signature']) && is_file(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'idscans'.DIRECTORY_SEPARATOR.$customer['signature'])) {
					$sign_source = VBO_ADMIN_URI . 'resources/idscans/' . $customer['signature'] . '?' . time();
					echo '<img src="' . $sign_source . '" />';
				}
				?>
				</td>
			</tr>
		</table>
	</div>
	<?php
	//Customer Signature + Date - End
	?>
	<p><br/></p>
</div>
