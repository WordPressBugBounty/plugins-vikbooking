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
 * This is the Template used for generating any invoice
 * List of available special-tags that can be used in this template:
 *
 * {company_logo}
 * {company_info}
 * {invoice_number}
 * {invoice_suffix}
 * {invoice_date}
 * {invoice_products_descriptions}
 * {customer_info}
 * {invoice_totalnet}
 * {invoice_totaltax}
 * {invoice_grandtotal}
 * {checkin_date}
 * {checkout_date}
 * {num_nights}
 * {tot_guests}
 * {tot_adults}
 * {tot_children}
 * {inv_notes}
 * {customfield 2} - Will be replaced with the Custom Field of ID 2 (Last Name by default - you can use any ID)
 *
 * The record of the booking can be accessed from the following global array in case you need any extra special tag or to perform queries for a deeper customization level:
 * $booking_info (booking array)
 * $booking_rooms (booked rooms array)
 * Example: the ID of the booking is contained in $booking_info['id'] - you can see the whole array content with the code "print_r($booking_info)"
 *
 * It is also possible to access the customer information array by using the code below:
 * 
 * $customer = [];
 * if (isset($booking_info) && !empty($booking_info['id'])) {
 * 	$customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking_info['id']);
 * }
 * 
 * The variable $customer will always be an array, even if no customers were found. In this case, the array will be empty.
 * Debug the content of the array with the code "print_r($customer);" by placing it on any part of the PDF content below.
 * 
 * Need to access the events related to the payments of the reservation? The PHP code below will help you obtain and debug the array of the "payment events".
 * 
 * if (isset($booking_info) && !empty($booking_info['id'])) {
 * 	echo '<pre>' . print_r(VikBooking::getBookingHistoryInstance($booking_info['id'])->getEventsWithData(['P0', 'PN', 'PU'], null, false), true) . '</pre>';
 * }
 * 
 */

// Custom Invoice PDF Template Parameters
defined('VBO_INVOICE_PDF_PAGE_ORIENTATION') OR define('VBO_INVOICE_PDF_PAGE_ORIENTATION', 'P'); //define a constant - P=portrait, L=landscape (P by default or if not specified)
defined('VBO_INVOICE_PDF_UNIT') OR define('VBO_INVOICE_PDF_UNIT', 'mm'); //define a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default or if not specified)
defined('VBO_INVOICE_PDF_PAGE_FORMAT') OR define('VBO_INVOICE_PDF_PAGE_FORMAT', 'A4'); //define a constant - A4 by default or if not specified. Could be also a custom array of width and height but constants arrays are only supported in PHP7
defined('VBO_INVOICE_PDF_MARGIN_LEFT') OR define('VBO_INVOICE_PDF_MARGIN_LEFT', 10); //define a constant - 15 by default or if not specified
defined('VBO_INVOICE_PDF_MARGIN_TOP') OR define('VBO_INVOICE_PDF_MARGIN_TOP', 10); //define a constant - 27 by default or if not specified
defined('VBO_INVOICE_PDF_MARGIN_RIGHT') OR define('VBO_INVOICE_PDF_MARGIN_RIGHT', 10); //define a constant - 15 by default or if not specified
defined('VBO_INVOICE_PDF_MARGIN_HEADER') OR define('VBO_INVOICE_PDF_MARGIN_HEADER', 1); //define a constant - 5 by default or if not specified
defined('VBO_INVOICE_PDF_MARGIN_FOOTER') OR define('VBO_INVOICE_PDF_MARGIN_FOOTER', 5); //define a constant - 10 by default or if not specified
defined('VBO_INVOICE_PDF_MARGIN_BOTTOM') OR define('VBO_INVOICE_PDF_MARGIN_BOTTOM', 5); //define a constant - 25 by default or if not specified
defined('VBO_INVOICE_PDF_IMAGE_SCALE_RATIO') OR define('VBO_INVOICE_PDF_IMAGE_SCALE_RATIO', 1.25); //define a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default or if not specified)
$invoice_params = array(
	'show_header' => 0, //0 = false (do not show the header) - 1 = true (show the header)
	'header_data' => array(), //if empty array, no header will be displayed. The array structure is: array(logo_in_tcpdf_folder, logo_width_mm, title, text, rgb-text_color, rgb-line_color). Example: array('logo.png', 30, 'Hotel xy', 'Versilia Coast, xyz street', array(0,0,0), array(0,0,0))
	'show_footer' => 0, //0 = false (do not show the footer) - 1 = true (show the footer)
	'pdf_page_orientation' => 'VBO_INVOICE_PDF_PAGE_ORIENTATION', //must be a constant - P=portrait, L=landscape (P by default)
	'pdf_unit' => 'VBO_INVOICE_PDF_UNIT', //must be a constant - [pt=point, mm=millimeter, cm=centimeter, in=inch] (mm by default)
	'pdf_page_format' => 'VBO_INVOICE_PDF_PAGE_FORMAT', //must be a constant defined above or an array of custom values like: 'pdf_page_format' => array(400, 300)
	'pdf_margin_left' => 'VBO_INVOICE_PDF_MARGIN_LEFT', //must be a constant - 15 by default
	'pdf_margin_top' => 'VBO_INVOICE_PDF_MARGIN_TOP', //must be a constant - 27 by default
	'pdf_margin_right' => 'VBO_INVOICE_PDF_MARGIN_RIGHT', //must be a constant - 15 by default
	'pdf_margin_header' => 'VBO_INVOICE_PDF_MARGIN_HEADER', //must be a constant - 5 by default
	'pdf_margin_footer' => 'VBO_INVOICE_PDF_MARGIN_FOOTER', //must be a constant - 10 by default
	'pdf_margin_bottom' => 'VBO_INVOICE_PDF_MARGIN_BOTTOM', //must be a constant - 25 by default
	'pdf_image_scale_ratio' => 'VBO_INVOICE_PDF_IMAGE_SCALE_RATIO', //must be a constant - ratio used to adjust the conversion of pixels to user units (1.25 by default)
	'header_font_size' => '10', //must be a number
	'body_font_size' => '10', //must be a number
	'footer_font_size' => '8', //must be a number
	'show_lines_taxrate_col' => 0, // 0 to not show the tax rate for each line, 1 to show it, but add a new TD to the HTML below.
);
defined('_VIKBOOKING_INVOICE_PARAMS') OR define('_VIKBOOKING_INVOICE_PARAMS', '1');
//
?>

<div style="color: #444;">
	<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td width="70%">
				<span>{company_logo}</span>
				<br/>
				<span>{company_info}</span>
			</td>
			<td width="30%" align="right" valign="bottom">
				<table align="right" width="100%" style="border: 1px solid #22485d;" bgcolor="#fbfbfb" cellspacing="0" cellpadding="2">
					<tr>
						<td align="right"><?php echo JText::translate('VBOINVNUM'); ?> <strong><span>{invoice_number}</span><span>{invoice_suffix}</span></strong></td>
					</tr>
					<tr>
						<td align="right"><?php echo JText::translate('VBOINVDATE'); ?> <strong><span>{invoice_date}</span></strong></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<br/>
	<br/>
	<br/>
	<table width="100%" bgcolor="#fbfbfb" border="0" cellspacing="1" cellpadding="2">
		<tr bgcolor="#B2C1DF">
			<td width="40%"><strong><?php echo JText::translate('VBOINVCOLDESCR'); ?></strong></td>
			<td width="20%"><strong><?php echo JText::translate('VBOINVCOLNETPRICE'); ?></strong></td>
			<td width="20%"><strong><?php echo JText::translate('VBOINVCOLTAX'); ?></strong></td>
			<td width="20%"><strong><?php echo JText::translate('VBOINVCOLPRICE'); ?></strong></td>
		</tr>
		{invoice_products_descriptions}
	</table>
	<br/>
	<table width="100%" bgcolor="#fbfbfb" border="0" cellspacing="1" cellpadding="2">
		<tr bgcolor="#B2C1DF">
			<td width="40%"><strong><?php echo JText::translate('VBO_INV_TAX_SUMMARY'); ?></strong></td>
			<td width="30%"><strong><?php echo JText::translate('VBO_INV_TAX_ALIQUOTE'); ?></strong></td>
			<td width="30%"><strong><?php echo JText::translate('VBOINVCOLTOTAL'); ?></strong></td>
		</tr>
		{invoice_tax_summary}
	</table>
	<br/>
	<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr bgcolor="#fbfbfb">
			<td rowspan="3" valign="top"><strong><?php echo JText::translate('VBOINVCOLCUSTINFO'); ?></strong><br/><br/><span>{customer_info}</span></td>
			<td rowspan="3" valign="top">
				<strong><?php echo JText::translate('VBOINVCOLBOOKINGDETS'); ?></strong><br/><br/>
				<?php echo JText::translate('VBOINVCHECKIN'); ?>: <span>{checkin_date}</span><br/>
				<?php echo JText::translate('VBOINVCHECKOUT'); ?>: <span>{checkout_date}</span><br/>
				<?php echo JText::translate('VBOINVTOTGUESTS'); ?>: <span>{tot_guests}</span>
			</td>
			<td align="left"><strong><?php echo JText::translate('VBOINVCOLTOTAL'); ?></strong> <span>{invoice_totalnet}</span></td>
		</tr>
		<tr bgcolor="#fbfbfb">
			<td align="left"><strong><?php echo JText::translate('VBOINVCOLTAX'); ?></strong> <span>{invoice_totaltax}</span></td>
		</tr>
		<tr bgcolor="#fbfbfb">
			<td align="left" valign="bottom"><strong><u><?php echo JText::translate('VBOINVCOLGRANDTOTAL'); ?></u></strong> <span>{invoice_grandtotal}</span></td>
		</tr>
	</table>
	<div>{inv_notes}</div>
</div>
