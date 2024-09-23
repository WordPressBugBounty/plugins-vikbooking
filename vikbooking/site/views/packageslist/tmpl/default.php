<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

$packages = $this->packages;
$vbo_tn=$this->vbo_tn;
$navig=$this->navig;

$currencysymb = VikBooking::getCurrencySymb();
$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();
$pitemid = VikRequest::getString('Itemid', '', 'request');

if (!(count($packages) > 0)) {
	?>
<h3 class="vbclistheadt"><?php echo JText::translate('VBONOPKGFOUND'); ?></h3>
	<?php
} else {
	?>
<h3 class="vbclistheadt"><?php echo JText::translate('VBOPKGLIST'); ?></h3>
<div class="vbo-pkglist-container">
	<?php
	foreach ($packages as $pk => $package) {
		$costfor = array();
		if ($package['perperson'] == 1) {
			$costfor[] = JText::translate('VBOPKGCOSTPERPERSON');
		}
		if ($package['pernight_total'] == 1) {
			$costfor[] = JText::translate('VBOPKGCOSTPERNIGHT');
		}
		?>
	<div class="vbo-pkglist-pkg">
		<div class="vbo-pkglist-pkg-bone">
		<?php
		if(!empty($package['img'])) {
			?>
			<div class="vbo-pkglist-pkg-img">
				<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/thumb_<?php echo $package['img']; ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" />
			</div>
			<?php
		}
		?>
		</div>
		<div class="vbo-pkglist-pkg-btwo">
			<div class="vbo-pkglist-pkg-name">
				<h4><?php echo $package['name']; ?></h4>
			</div>
			<div class="vbo-pkglist-pkg-dates-cont">
				<div class="vbo-pkglist-pkg-dates">
					<?php VikBookingIcons::e('clock-o'); ?>
					<span class="vbo-pkglist-pkg-dates-lbl"><?php echo JText::translate('VBOPKGVALIDATES'); ?></span>
					<span class="vbo-pkglist-pkg-dates-ft"><?php echo date(str_replace("/", $datesep, $df), $package['dfrom']).($package['dfrom'] != $package['dto'] ? ' - '.date(str_replace("/", $datesep, $df), $package['dto']) : ''); ?></span>
				</div>
			</div>
			<div class="vbo-pkglist-pkg-shortdescr"><?php echo $package['shortdescr']; ?></div>
		</div>
		<div class="vbo-pkglist-pkg-bthree">
			<div class="vbo-pkglist-pkg-cost">
				<span class="vbo-pkglist-pkg-price"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo VikBooking::numberFormat($package['cost']); ?></span></span>
				<span class="vbo-pkglist-pkg-priceper"><?php echo implode(', ', $costfor); ?></span>
			</div>
			<div class="vbo-pkglist-pkg-details">
				<a class="btn vbo-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=packagedetails&pkgid='.$package['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBOPKGMOREDETAILS'); ?></a>
			</div>
		<?php
		if(!empty($package['benefits'])) {
			?>
			<div class="vbo-pkglist-pkg-benefits">
				<?php echo $package['benefits']; ?>
			</div>
			<?php
		}
		?>
		</div>
	</div>
		<?php
	}
?>
</div>
<?php
}
//pagination
if(strlen($navig) > 0) {
	?>
	<div class="pagination"><?php echo $navig; ?></div>
	<?php
}