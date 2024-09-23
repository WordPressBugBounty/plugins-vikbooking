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

$rooms = $this->rooms;
$tsstart = $this->tsstart;
$wmonthsel = $this->wmonthsel;
$busy = $this->busy;
$vbo_tn = $this->vbo_tn;

$currencysymb = VikBooking::getCurrencySymb();
$showpartlyres=VikBooking::showPartlyReserved();
$vbdateformat = VikBooking::getDateFormat();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();

$document = JFactory::getDocument();
//load jQuery
if (VikBooking::loadJquery()) {
	//JHtml::fetch('jquery.framework', true, true);
	JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-1.12.4.min.js');
}

$pmonth = VikRequest::getInt('month', '', 'request');
$pshowtype = VikRequest::getInt('showtype', 2, 'request');
//1 = do not show the units - 2 = show the units remaning - 3 = show the number of units booked.
$pshowtype = $pshowtype >= 1 && $pshowtype <= 3 ? $pshowtype : 1; 
$pitemid = VikRequest::getString('Itemid', '', 'request');

$begin_info = getdate($tsstart);

$rids_qstring = '';
foreach ($rooms as $room) {
	$rids_qstring .= '&room_ids[]=' . $room['id'];
}

$inonout_allowed = true;
$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	if ($timeopst[0] < $timeopst[1]) {
		// check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
		$inonout_allowed = false;
	}
}

?>
<h3><?php echo JText::translate('VBOAVAILABILITYCALENDAR'); ?></h3>

<div class="vbo-availability-controls">
	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=availability' . $rids_qstring . (!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" name="vbmonths">
		<?php echo $wmonthsel; ?>
	<?php
	foreach ($rooms as $room) {
		?>
		<input type="hidden" name="room_ids[]" value="<?php echo $room['id']; ?>" />
		<?php
	}
	?>
		<input type="hidden" name="showtype" value="<?php echo $pshowtype; ?>" />
	</form>
	<div class="vblegendediv">
		<span class="vblegenda"><span class="vblegenda-status vblegfree">&nbsp;</span> <span class="vblegenda-lbl"> <?php echo JText::translate('VBLEGFREE'); ?></span></span>
	<?php
	if ($showpartlyres) {
		?>
		<span class="vblegenda"><span class="vblegenda-status vblegwarning">&nbsp;</span> <span class="vblegenda-lbl"> <?php echo JText::translate('VBLEGWARNING'); ?></span></span>
		<?php
	}
	?>
		<span class="vblegenda"><span class="vblegenda-status vblegbusy">&nbsp;</span> <span class="vblegenda-lbl"> <?php echo JText::translate('VBLEGBUSY'); ?></span></span>
	</div>
</div>
	
<?php
$check = is_array($busy) && count($busy) > 0 ? true : false;
$days_labels = array(
	JText::translate('VBSUN'),
	JText::translate('VBMON'),
	JText::translate('VBTUE'),
	JText::translate('VBWED'),
	JText::translate('VBTHU'),
	JText::translate('VBFRI'),
	JText::translate('VBSAT')
);
?>
<div class="vbo-availability-wrapper">
<?php
foreach ($rooms as $rk => $room) {
	$nowts = $begin_info;
	$carats = VikBooking::getRoomCaratOriz($room['idcarat'], $vbo_tn);
	?>
	<div class="vbo-availability-room-container">
		<div class="vbo-availability-room-details">
			<div class="vbo-availability-room-details-first">
				<div class="vbo-availability-room-details-left">
				<?php
				if (!empty($room['img'])) {
					?>
					<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room['img']; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>"/>
					<?php
				}
				?>
				</div>
				<div class="vbo-availability-room-details-right">
					<h4><?php echo $room['name']; ?></h4>
					<div class="vbo-availability-room-details-descr">
						<?php echo $room['smalldesc']; ?>
					</div>
				<?php
				if (!empty($carats)) {
					?>
					<div class="room_carats">
						<?php echo $carats; ?>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vbo-availability-room-details-last vbselectr">
				<div class="vbo-availability-room-details-last-inner">
					<a class="btn vbo-pref-color-btn" id="vbo-av-btn-<?php echo $room['id']; ?>" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$room['id'].'&checkin=-1'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBAVAILBOOKNOW'); ?></a>
				</div>
				<div class="vbo-availability-room-details-last-checkin" id="vbo-av-checkin-<?php echo $room['id']; ?>"><?php VikBookingIcons::e('sign-in', 'vbo-pref-color-element'); ?> <span></span></div>
			</div>
		</div>
		<div class="vbo-availability-room-monthcal table-responsive">
			<table class="table" id="vbo-av-table-<?php echo $room['id']; ?>" data-room-table="<?php echo $room['id']; ?>">
				<tr class="vbo-availability-room-monthdays">
					<td class="vbo-availability-month-name vbo-pref-color-text" rowspan="2"><?php echo VikBooking::sayMonth($nowts['mon'])." ".$nowts['year']; ?></td>
				<?php
				$mon = $nowts['mon'];
				while ($nowts['mon'] == $mon) {
					?>
					<td class="vbo-availability-month-day">
						<span class="vbo-availability-daynumber"><?php echo $nowts['mday']; ?></span>
						<span class="vbo-availability-weekday"><?php echo $days_labels[$nowts['wday']]; ?></span>
					</td>
					<?php
					$next = $nowts['mday'] + 1;
					$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
					$nowts = getdate($dayts);
				}
				?>
				</tr>
				<tr class="vbo-availability-room-avdays">
				<?php
				$nowts = getdate($tsstart);
				$mon = $nowts['mon'];
				while ($nowts['mon'] == $mon) {
					$dclass = "vbo-free-cell";
					$is_checkin = false;
					$is_checkout = false;
					$dlnk = "";
					$totfound = 0;
					if (array_key_exists($room['id'], $busy) && count($busy[$room['id']]) > 0) {
						foreach ($busy[$room['id']] as $b) {
							$info_in = getdate($b['checkin']);
							$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
							$info_out = getdate($b['checkout']);
							$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
							if ($nowts[0] >= $checkin_ts && $nowts[0] == $checkout_ts) {
								$is_checkout = true;
							}
							if ($nowts[0] >= $checkin_ts && $nowts[0] < $checkout_ts) {
								$totfound++;
								$dclass = "vbo-occupied-cell";
								if ($nowts[0] == $checkin_ts) {
									$is_checkin = true;
								}
							}
						}
					}
					$useday = ($nowts['mday'] < 10 ? "0".$nowts['mday'] : $nowts['mday']);
					$dclass .= ($totfound < $room['units'] && $totfound > 0 ? ' vbo-partially-cell' : '');

					// partially reserved days can be disabled from the configuration
					$dclass = !$showpartlyres && $totfound < $room['units'] && $totfound > 0 ? 'vbo-free-cell' : $dclass;
					if ($is_checkout && $room['units'] < 2 && !$inonout_allowed) {
						// in case check-in on check-out is disabled, add the occupied class for the check-out date
						$totfound = 1;
						$dclass = "vbo-occupied-cell";
					}

					// check if the date is closed at property-level
					if (!$totfound && VikBooking::validateClosingDates($nowts[0], ($nowts[0] + 86399))) {
						// this date is closed at property-level
						$totfound = $room['units'];
						$dclass = "vbo-occupied-cell";
					}

					$show_day_units = $totfound;
					if ($pshowtype == 1) {
						$show_day_units = '';
					} elseif ($pshowtype == 2 && $totfound >= 1) {
						$show_day_units = ($room['units'] - $totfound);
						$show_day_units = $show_day_units < 0 ? 0 : $show_day_units;
					} elseif ($pshowtype == 3 && $totfound >= 1) {
						$show_day_units = $totfound;
					}
					if (!$showpartlyres && $totfound < $room['units'] && $totfound > 0) {
						$show_day_units = '';
					}
					if ($totfound == 1) {
						$dclass .= $is_checkin === true ? ' vbo-checkinday-cell' : '';
						$dclass .= $is_checkout === true ? ' vbo-checkoutday-cell' : '';
						$dlnk = "<span class=\"vbo-availability-day-container\" data-units-booked=\"".$totfound."\" data-units-left=\"".($room['units'] - $totfound)."\">".$show_day_units."</span>";
					} elseif ($totfound > 1) {
						$dlnk = "<span class=\"vbo-availability-day-container\" data-units-booked=\"".$totfound."\" data-units-left=\"".($room['units'] - $totfound)."\">".$show_day_units."</span>";
					}
					?>
					<td class="<?php echo $dclass; ?>" data-cell-date="<?php echo date(str_replace("/", $datesep, $df), $nowts[0]); ?>" data-cell-ts="<?php echo $nowts[0]; ?>"><?php echo $dlnk; ?></td>
					<?php
					$next = $nowts['mday'] + 1;
					$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
					$nowts = getdate($dayts);
				}
				?>
				</tr>
			</table>
		</div>
	</div>
	<?php
}
?>
</div>

<script type="text/javascript">
jQuery(function() {
	jQuery(".vbo-free-cell, .vbo-partially-cell").click(function() {
		var idroom = jQuery(this).closest("table").attr("data-room-table");
		var celldate = jQuery(this).attr("data-cell-date");
		var cellts = jQuery(this).attr("data-cell-ts");
		if (idroom.length && celldate.length && cellts.length) {
			jQuery("#vbo-av-checkin-"+idroom).hide().find("span").text("");
			if (jQuery("#vbo-av-btn-"+idroom).length) {
				var btnlink = jQuery("#vbo-av-btn-"+idroom).attr("href");
				if (jQuery(this).hasClass("vbo-cell-selected-arrival")) {
					jQuery("#vbo-av-table-"+idroom).find("tr").find("td").removeClass("vbo-cell-selected-arrival");
					jQuery("#vbo-av-checkin-"+idroom).fadeOut().find("span").text(celldate);
					btnlink = btnlink.replace(/(checkin=)[^\&]+/, '$1' + "-1");
				} else {
					jQuery("#vbo-av-table-"+idroom).find("tr").find("td").removeClass("vbo-cell-selected-arrival");
					jQuery(this).addClass("vbo-cell-selected-arrival");
					jQuery("#vbo-av-checkin-"+idroom).fadeIn().find("span").text(celldate);
					btnlink = btnlink.replace(/(checkin=)[^\&]+/, '$1' + cellts);
				}
				jQuery("#vbo-av-btn-"+idroom).attr("href", btnlink);
			}
		}
	});
});
</script>
