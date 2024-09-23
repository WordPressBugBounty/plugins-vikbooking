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
 * {invoice_date}
 * {customer_info}
 * {invoice_notes}
 *
 * The records of the invoice and customer can be accessed from the following global array variables in case you need any extra special tag or to perform queries for a deeper customization:
 * $invoice (array)
 * $customer (array)
 * Example: the number of the invoice is contained in $invoice['number'] - you can see the whole array content with the code "print_r($invoice);"
 * Example: the email of the customer is contained in $customer['email'] - you can see the whole array content with the code "print_r($customer);"
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
	'footer_font_size' => '8' //must be a number
);
defined('_VIKBOOKING_INVOICE_PARAMS') OR define('_VIKBOOKING_INVOICE_PARAMS', '1');
//

$currencyname = VikBooking::getCurrencyName();

?>

<table width="100%" border="0" cellspacing="1" cellpadding="2">
	<tr>
		<td width="70%">{company_logo}<br/>{company_info}</td>
		<td width="30%" align="right" valign="bottom">
			<table align="right" width="100%" style="border: 1px solid #22485d;" bgcolor="#fbfbfb" cellspacing="0" cellpadding="2">
				<tr>
					<td align="right"><strong><?php echo JText::translate('VBOINVNUM'); ?> {invoice_number}</strong></td>
				</tr>
				<tr>
					<td align="right"><strong><?php echo JText::translate('VBOINVDATE'); ?> {invoice_date}</strong></td>
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
<?php
$rows_written = 0;
foreach ($invoice['rawcont']['rows'] as $index => $row) {
	?>
	<tr>
		<td><?php echo $row['service']; ?></td>
		<td><?php echo $currencyname . ' ' . VikBooking::numberFormat($row['net']); ?></td>
		<td><?php echo $currencyname . ' ' . VikBooking::numberFormat($row['tax']); ?></td>
		<td><?php echo $currencyname . ' ' . VikBooking::numberFormat($row['tot']); ?></td>
	</tr>
	<?php
	$rows_written++;
}
if ($rows_written < 10) {
	// we write at least 10 rows to give a decent height to the document
	for ($i = 0; $i < (10 - $rows_written); $i++) {
		?>
	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
		<?php
	}
}
?>
</table>
<br/>
<table width="100%" border="0" cellspacing="1" cellpadding="2">
	<tr bgcolor="#fbfbfb">
		<td rowspan="3" width="60%" valign="top"><strong><?php echo JText::translate('VBOINVCOLCUSTINFO'); ?></strong><br/>{customer_info}</td>
		<td width="244" align="left"><strong><?php echo JText::translate('VBOINVCOLTOTAL'); ?></strong> <?php echo $currencyname . ' ' . VikBooking::numberFormat($invoice['rawcont']['totalnet']); ?></td>
	</tr>
	<tr bgcolor="#fbfbfb">
		<td align="left"><strong><?php echo JText::translate('VBOINVCOLTAX'); ?></strong> <?php echo $currencyname . ' ' . VikBooking::numberFormat($invoice['rawcont']['totaltax']); ?></td>
	</tr>
	<tr bgcolor="#fbfbfb">
		<td align="left" valign="bottom"><strong><u><?php echo JText::translate('VBOINVCOLGRANDTOTAL'); ?></u></strong> <?php echo $currencyname . ' ' . VikBooking::numberFormat($invoice['rawcont']['totaltot']); ?></td>
	</tr>
</table>

<p>{invoice_notes}</p>
