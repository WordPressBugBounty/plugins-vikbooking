<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  html.license
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$view = $displayData['view'];

$lookup = [
	'coupons' => [
		'title' => JText::translate('VBMENUCOUPONS'),
		'desc'  => JText::translate('VBOFREECOUPONSDESCR'),
	],
	'crons' => [
		'title' => JText::translate('VBMENUCRONS'),
		'desc'  => JText::translate('VBOFREECRONSDESCR'),
	],
	'customers' => [
		'title' => JText::translate('VBMENUCUSTOMERS'),
		'desc'  => JText::translate('VBOFREECUSTOMERSDESCR'),
	],
	'einvoicing' => [
		'title' => JText::translate('VBMENUEINVOICING'),
		'desc'  => JText::translate('VBOFREEEINVDESCR'),
	],
	'invoices' => [
		'title' => JText::translate('VBMENUINVOICES'),
		'desc'  => JText::translate('VBOFREEINVOICESDESCR'),
	],
	'operators' => [
		'title' => JText::translate('VBMENUOPERATORS'),
		'desc'  => JText::translate('VBOFREEOPERATORSDESCR'),
	],
	'optionals' => [
		'title' => JText::translate('VBMENUTENFIVE'),
		'desc'  => JText::translate('VBOFREEOPTIONSDESCR'),
	],
	'packages' => [
		'title' => JText::translate('VBMENUPACKAGES'),
		'desc'  => JText::translate('VBOFREEPACKAGESDESCR'),
	],
	'payments' => [
		'title' => JText::translate('VBMENUTENEIGHT'),
		'desc'  => JText::translate('VBOFREEPAYMENTSDESCR'),
	],
	'pmsreports' => [
		'title' => JText::translate('VBMENUPMSREPORTS'),
		'desc'  => JText::translate('VBOFREEREPORTSDESCR'),
	],
	'restrictions' => [
		'title' => JText::translate('VBMENURESTRICTIONS'),
		'desc'  => JText::translate('VBOFREERESTRSDESCR'),
	],
	'seasons' => [
		'title' => JText::translate('VBMENUTENSEVEN'),
		'desc'  => JText::translate('VBOFREESEASONSDESCR'),
	],
	'stats' => [
		'title' => JText::translate('VBMENUSTATS'),
		'desc'  => JText::translate('VBOFREESTATSDESCR'),
	],
];

if (!isset($lookup[$view]))
{
	return;
}

// set up toolbar title
JToolbarHelper::title('VikBooking - ' . $lookup[$view]['title']);

if (empty($lookup[$view]['image']))
{
	// use the default logo image
	$lookup[$view]['image'] = 'vikwp_free_logo.png';
}

?>

<div class="vbo-free-nonavail-wrap">

	<div class="vbo-free-nonavail-inner">

		<div class="vbo-free-nonavail-logo">
			<img src="<?php echo VBO_SITE_URI . 'resources/' . $lookup[$view]['image']; ?>" />
		</div>

		<div class="vbo-free-nonavail-expl">
			<h3><?php echo $lookup[$view]['title']; ?></h3>

			<p class="vbo-free-nonavail-descr"><?php echo $lookup[$view]['desc']; ?></p>
			
			<p class="vbo-free-nonavail-footer-descr">
				<a href="admin.php?option=com_vikbooking&amp;view=gotopro" class="btn vbo-free-nonavail-gopro">
					<?php VikBookingIcons::e('rocket'); ?> <span><?php echo JText::translate('VBOGOTOPROBTN'); ?></span>
				</a>
			</p>
		</div>

	</div>

</div>