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

$lic_key = $this->lic_key;
$lic_date = $this->lic_date;
$is_pro = $this->is_pro;

$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();

$valid_until = date(str_replace("/", $datesep, $df), $lic_date);

?>
<div class="viwppro-cnt viwpro-procnt">
	<div class="viwpro-procnt-inner">
		<div class="vikwppro-header">
			<div class="vikwppro-header-inner">
				<div class="vikwppro-header-text">
					<h2><?php echo JText::translate('VBOPROTHANKSUSE'); ?></h2>
					<h3><?php echo JText::translate('VBOPROTHANKSLIC'); ?></h3>
				</div>
			</div>
		</div>
		<div class="vikwppro-licencecnt">
			<div class="col col-md-6 col-sm-12 vikwppro-licencetext">
				<div>
					<h3><?php echo VikBookingLicense::hasVcm() ? JText::translate('VBOLICKEYVALIDVCM') : JText::sprintf('VBOLICKEYVALIDUNTIL', $valid_until); ?></h3>
					<h4><?php echo JText::translate('VBOPROGETRENEWLICFROM'); ?></h4>
					<a href="https://vikwp.com/plugin/vikbooking?utm_source=free_version&utm_medium=vbo&utm_campaign=renewlicence" class="vikwp-btn-link" target="_blank"><?php VikBookingIcons::e('rocket'); ?> <?php echo JText::translate('VBOPROGETRENEWLIC'); ?></a>
				</div>
				<span class="icon-background"><?php VikBookingIcons::e('rocket'); ?></span>
			</div>
			<div class="col col-md-6 col-sm-12 vikwppro-licenceform">
				<form>				
					<div class="vikwppro-licenceform-inner">
						<h4><?php echo JText::translate('VBOPROALREADYHAVEKEY'); ?></h4>
						<div>
							<span class="vikwppro-inputspan"><?php VikBookingIcons::e('key'); ?><input type="text" name="key" id="lickey" value="<?php echo htmlspecialchars($lic_key); ?>" class="licence-input" autocomplete="off" /></span>
							<button type="button" class="btn btn-primary" id="vikwpvalidate" onclick="vikWpValidateLicenseKey();"><?php echo JText::translate('VBOPROVALNUPD'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php
if (!VikBookingLicense::hasVcm() && !VikBookingLicense::hideVcmAd()) {
?>
	<div class="viwpro-e4jc">
		<div class="viwpro-e4jc-inner">
			<div class="viwpro-e4jc-text">
				<h3><?php echo JText::translate('VBOPROVCMADTITLE'); ?></h3>
				<p><?php echo JText::translate('VBOPROVCMADDESCR'); ?></p>
				<a href="https://vikwp.com" class="btn btn-primary" target="_blank"><?php echo JText::translate('VBOPROVCMADMOREINFO'); ?></a>
			</div>
			<div class="viwpro-e4jc-img"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/pro_e4jconnect.png" title="e4jConnect Vik Channel Manager" /></div>
		</div>
		<div class="viwpro-e4jc-channels">
			<div class="viwpro-e4jc-channels-intro"><h3><?php echo JText::translate('VBOPROVCMADSOMECHAV'); ?></h3></div>
			<div class="viwpro-e4jc-channels-inner">
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/booking.png" alt="e4jConnect Booking.com channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/expedia.png" alt="e4jConnect Expedia channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/hostelworld.png" alt="e4jConnect Hostelworld channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/airbnb.png" alt="e4jConnect Airbnb channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/agoda.png" alt="e4jConnect Agoda channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/tripconnect.png" alt="e4jConnect TripAdvisor tripconnect channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/trivago.png" alt="e4jConnect Trivago channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/holiday_lettings.png" alt="e4jConnect Holiday Lettings channel"/></div>
			</div>
			<div class="viwpro-e4jc-channels-more"><?php echo JText::translate('VBOPROVCMADCHANDMANY'); ?></div>
		</div>
		<div class="viwpro-e4jc-noshow"><a href="admin.php?option=com_vikbooking&view=gotopro&hidead=1" class="btn"><?php echo JText::translate('VBOPROVCMADDONTSHOW'); ?></a></div>
	</div>
<?php
}
?>
</div>

<script type="text/javascript">
var vikwp_running = false;

function vikWpValidateLicenseKey() {
	if (vikwp_running) {
		// prevent double submission until request is over
		return;
	}

	// start running
	vikWpStartValidation();

	// request
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('admin.php?option=com_vikbooking&task=license.validate'); ?>",
		{
			key: document.getElementById('lickey').value
		},
		(res) => {
			try {
				var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
				document.location.href = 'admin.php?option=com_vikbooking&view=getpro';
			} catch(err) {
				console.error(err);
				// stop the request
				vikWpStopValidation();
				// display error
				alert(err.responseText || 'Request Failed');
			}
		},
		(err) => {
			console.error(err);
			// stop the request
			vikWpStopValidation();
			// display error
			alert(err.responseText || 'Request Failed');
		}
	);
}

function vikWpStartValidation() {
	vikwp_running = true;
	jQuery('#vikwpvalidate').prepend('<?php VikBookingIcons::e('refresh', 'fa-spin'); ?>');
}

function vikWpStopValidation() {
	vikwp_running = false;
	jQuery('#vikwpvalidate').find('i').remove();
}

jQuery(function() {
	jQuery('#lickey').keyup(function() {
		jQuery(this).val(jQuery(this).val().trim());
	});
	jQuery('#lickey').keypress(function(e) {
		if (e.which == 13) {
			// enter key code pressed, run the validation
			vikWpValidateLicenseKey();
			return false;
		}
	});
});
</script>
