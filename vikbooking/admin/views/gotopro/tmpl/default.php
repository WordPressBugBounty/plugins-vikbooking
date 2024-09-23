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

?>
<div class="viwppro-cnt">
	<div class="vikwp-alreadypro"><?php echo JText::translate('VBOPROALREADYHAVEPRO'); ?></div>
	<div class="vikwppro-header">
		<div class="vikwppro-header-inner">
			<div class="vikwppro-header-text">
				<h2><?php echo JText::translate('VBOPROREDUCEOTAFEES'); ?></h2>
				<h3><?php echo JText::translate('VBOPROCOLLECTDIRECTBOOK'); ?></h3>
				<h4><?php echo JText::translate('VBOPROBOOKINGENGINEPMS'); ?></h4>
				<ul>
					<li><?php VikBookingIcons::e('check'); ?> <span><?php echo JText::translate('VBOPROADVONE'); ?></li>
					<li><?php VikBookingIcons::e('check'); ?> <span><?php echo JText::translate('VBOPROADVTWO'); ?></span> </li>
					<li><?php VikBookingIcons::e('check'); ?> <span><?php echo JText::translate('VBOPROADVTHREE'); ?></span> </li>
				</ul>
				<a href="https://vikwp.com/plugin/vikbooking?utm_source=free_version&utm_medium=vbo&utm_campaign=gotopro" id="vikwpgotoget" class="vikwp-btn-link"><?php VikBookingIcons::e('rocket'); ?> <?php echo JText::translate('VBOGOTOPROBTN'); ?></a>
			</div>
			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/bookings.png" alt="<?php echo JText::translate('VBOPROADVONE'); ?>" />
			</div>
		</div>
	</div>
	<div class="vikwppro-advantages">
		<div class="vikwppro-advantages-item">
			<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/receive-payments.png" alt="<?php echo JText::translate('VBOPROCOLLECTDIRCTBOOKTITLE'); ?>" />
			<h4><?php echo JText::translate('VBOPROCOLLECTDIRCTBOOKTITLE'); ?></h4>
			<?php echo JText::translate('VBOPROCOLLECTDIRCTBOOKTITLEDESC'); ?>
		</div>
		<div class="vikwppro-advantages-item">
			<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/save-on-ota.png" alt="<?php echo JText::translate('VBOPROSAVEOTASFEESTITLE'); ?>" />
			<h4><?php echo JText::translate('VBOPROSAVEOTASFEESTITLE'); ?></h4>
			<?php echo JText::translate('VBOPROSAVEOTASFEESTITLEDESC'); ?>
		</div>
		<div class="vikwppro-advantages-item">
			<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/rocket.png" alt="<?php echo JText::translate('VBOPROBECAMEINDEPENDENTTITLE'); ?>" />
			<h4><?php echo JText::translate('VBOPROBECAMEINDEPENDENTTITLE'); ?></h4>
			<?php echo JText::translate('VBOPROBECAMEINDEPENDENTTITLEDESC'); ?>
		</div>
	</div>
	<div class="viwppro-feats-cnt">
		<div class="viwppro-feats-row vikwppro-even viwppro-row-heightsmall">
			<div class="viwppro-feats-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/rates_overview.gif" alt="<?php echo JText::translate('VBOPROWHYRATES'); ?>" />
			</div>
			<div class="viwppro-feats-text">
				<h4><?php echo JText::translate('VBOPROWHYRATES'); ?></h4>
				<p><?php echo JText::translate('VBOPROWHYRATESDESC'); ?></p>
			</div>
		</div>
		<div class="viwppro-feats-row vikwppro-odd">
			<div class="viwppro-feats-text">
				<h4><?php echo JText::translate('VBOPROWHYEXTRASERVICES'); ?></h4>
				<p><?php echo JText::translate('VBOPROWHYEXTRASERVICESDESC'); ?></p>
			</div>
			<div class="viwppro-feats-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/vbo-extra-services.jpg" alt="<?php echo JText::translate('VBOPROWHYEXTRASERVICES'); ?>" />
			</div>
		</div>
		
		<div class="viwppro-feats-row vikwppro-odd">
			<div class="viwppro-feats-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/occupancy-report.jpg" alt="<?php echo JText::translate('VBOPROWHYREPORT'); ?>" />
			</div>
			<div class="viwppro-feats-text">
				<h4><?php echo JText::translate('VBOPROWHYREPORT'); ?></h4>
				<p><?php echo JText::translate('VBOPROWHYREPORTDESC'); ?></p>
			</div>			
		</div>

		<div class="viwppro-feats-row vikwppro-odd">
			<div class="viwppro-feats-text">
				<h4><?php echo JText::translate('VBOPROWHYCRONJOB'); ?></h4>
				<p><?php echo JText::translate('VBOPROWHYCRONJOBDESC'); ?></p>
			</div>
			<div class="viwppro-feats-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/hotel-vik-booking-invoices-wordpress.jpg" alt="<?php echo JText::translate('VBOPROWHYCRONJOB'); ?>" />
			</div>
		</div>

		<div class="viwppro-feats-row vikwppro-even">
			<div class="viwppro-feats-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/channelrates.jpg" alt="<?php echo JText::translate('VBOPROWHYCHMANAGER'); ?>" />
			</div>
			<div class="viwppro-feats-text">
				<h4><?php echo JText::translate('VBOPROWHYCHMANAGER'); ?></h4>
				<p><?php echo JText::translate('VBOPROWHYCHMANAGERDESC'); ?></p>
			</div>
		</div>
	</div>
	<div class="viwppro-extra">
		<h3><?php echo JText::translate('VBOPROWHYUNLOCKF'); ?></h3>
		<div class="viwppro-extra-inner">
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('calendar-check'); ?>
						<h4><?php echo JText::translate('VBOPROWHYCHECKIN'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYCHECKINDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('chart-line'); ?>
						<h4><?php echo JText::translate('VBOPROWHYPMSREP'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYPMSREPDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('certificate'); ?>
						<h4><?php echo JText::translate('VBOPROWHYPROMOTIONS'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYPROMOTIONSDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('file-text'); ?>
						<h4><?php echo JText::translate('VBOPROWHYINVOICES'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYINVOICESDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('users'); ?>
						<h4><?php echo JText::translate('VBOPROWHYCUSTOMERS'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYCUSTOMERSDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('pie-chart'); ?>
						<h4><?php echo JText::translate('VBOPROWHYGRAPHS'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYGRAPHSDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('sms'); ?>
						<h4><?php echo JText::translate('VBOPROWHYCRONS'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYCRONSDESC'); ?></p>
					</div>
				</div>
			</div>
			<div class="viwppro-extra-item">
				<div class="viwppro-extra-item-inner">
					<div class="viwppro-extra-item-text">
						<?php VikBookingIcons::e('credit-card'); ?>
						<h4><?php echo JText::translate('VBOPROWHYPAYMENTS'); ?></h4>
						<p><?php echo JText::translate('VBOPROWHYPAYMENTSDESC'); ?></p>
					</div>
				</div>
			</div>
		</div>
		<div class="vikwp-extra-more"><?php echo JText::translate('VBOPROWHYMOREEXTRA'); ?></div>
		<a name="upgrade"></a>
	</div>
	<div class="vikwppro-licencecnt">
		<div class="col col-md-6 col-sm-12 vikwppro-licencetext">
			<div>
				<h3><?php echo JText::translate('VBOPROREADYTOINCREASE'); ?></h3>
			<?php
			if ($lic_date > 0) {
				$valid_until = date(str_replace("/", $datesep, $df), $lic_date);
				?>
				<h4 class="vikwppro-lickey-expired"><?php echo JText::sprintf('VBOLICKEYEXPIREDON', $valid_until); ?></h4>
				<?php
			}
			?>
				<h4 class="vikwppro-licencecnt-get"><?php echo JText::translate('VBOPROGETNEWLICFROM'); ?></h4>
				<a href="https://vikwp.com/plugin/vikbooking?utm_source=free_version&utm_medium=vbo&utm_campaign=gotopro" class="vikwp-btn-link" target="_blank"><?php VikBookingIcons::e('rocket'); ?> <?php echo JText::translate('VBOGOTOPROBTN'); ?></a>
			</div>
			<span class="icon-background"><?php VikBookingIcons::e('rocket'); ?></span>
		</div>
		<div class="col col-md-6 col-sm-12 vikwppro-licenceform">
			<form>
				<div class="vikwppro-licenceform-inner">
					<h4><?php echo JText::translate('VBOPROALREADYHAVEKEY'); ?></h4>
					<span class="vikwppro-inputspan"><?php VikBookingIcons::e('key'); ?><input type="text" name="key" id="lickey" value="<?php echo htmlspecialchars($lic_key); ?>" class="licence-input" autocomplete="off" /></span>
					<button type="button" class="btn vikwp-btn-green" id="vikwpvalidate" onclick="vikWpValidateLicenseKey();"><?php echo JText::translate('VBOPROVALNINST'); ?></button>
				</div>
			</form>
		</div>
	</div>

	<div class="viwpro-e4jc">
		<div class="viwpro-e4jc-inner">
			<div class="viwpro-e4jc-text">
				<h3 style="margin-top: 0;"><?php echo JText::translate('VBOPROVCMADTITLE'); ?></h3>
				<h4><?php echo JText::translate('VBOPROSYNCHNEWBOOKINGS'); ?></h4>
				<h5><?php echo JText::translate('VBOPROVCMSYNCHEVERYTHING'); ?></h5>
				<p><?php echo JText::translate('VBOPROVCMADDESCR'); ?></p>
				<p class="vikpro-e4jc-badgeimg"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/bookingcom-premier-badge.png" title="e4jConnect Premier Partner" /> <img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/airbnb-preferred-badge.png" title="Airbnb e4jConnect Preferred Partner" /> <img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/expedia-partner.png" title="e4jConnect Expedia Partner" /> <img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/google-partner.png" title="e4jConnect Google Partner" /> <img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/vrbo-partner-badge.png" title="e4jConnect Vrbo Partner" /></p>
				<a href="https://vikwp.com/plugin/vikchannelmanager?utm_source=free_version&utm_medium=vbo&utm_campaign=gotopro" class="vikwp-pro-discover btn btn-primary" target="_blank"><?php echo JText::translate('VBOPROVCMADMOREINFO'); ?></a>
			</div>
			<div class="viwpro-e4jc-img"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/vcm-synch-img.jpg" title="e4jConnect Vik Channel Manager" /></div>
		</div>
		<div class="viwpro-e4jc-channels">
			<div class="viwpro-e4jc-channels-intro"><h3><?php echo JText::translate('VBOPROVCMADSOMECHAV'); ?></h3></div>
			<div class="viwpro-e4jc-channels-inner">
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/booking.png" alt="e4jConnect Booking.com channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/airbnb.png" alt="e4jConnect Airbnb channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/expedia.png" alt="e4jConnect Expedia channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/google-hotel.png" alt="e4jConnect Google Hotel channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/hostelworld.png" alt="e4jConnect Hostelworld channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/vrbo.png" alt="e4jConnect VRBO channel"/></div>
				<div class="viwpro-e4jc-channel"><img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/tripconnect.png" alt="e4jConnect TripAdvisor tripconnect channel"/></div>
			</div>
			<div class="viwpro-e4jc-channels-more"><?php echo JText::translate('VBOPROVCMADCHANDMANY'); ?></div>
		</div>
	</div>

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
	jQuery('.vikwp-alreadypro a').click(function(e) {
		e.preventDefault();
		jQuery('html,body').animate({ scrollTop: (jQuery('.vikwppro-licencecnt').offset().top - 50) }, { duration: 'fast' });
	});
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
