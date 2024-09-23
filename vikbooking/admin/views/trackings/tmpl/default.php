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

$rows = $this->rows;
$lim0 = $this->lim0;
$navbut = $this->navbut;
$orderby = $this->orderby;
$ordersort = $this->ordersort;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$vbo_app->loadDatePicker();

$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$juidf = $nowdf == "%d/%m/%Y" ? 'dd/mm/yy' : ($nowdf == "%m/%d/%Y" ? 'mm/dd/yy' : 'yy/mm/dd');

$dates_filter = '';
$pdatefilt = $this->datefilt;
$pdatefiltfrom = $this->datefiltfrom;
$pdatefiltto = $this->datefiltto;
if ((!empty($pdatefiltfrom) || !empty($pdatefiltto))) {
	$dates_filter = '&amp;datefilt='.$pdatefilt.(!empty($pdatefiltfrom) ? '&amp;datefiltfrom='.$pdatefiltfrom : '').(!empty($pdatefiltto) ? '&amp;datefiltto='.$pdatefiltto : '');
}
$pactive_tab = VikRequest::getString('vbo_active_tab', 'vbo-trackings-tabcont-list', 'request');

?>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('.vbo-trackings-togglesubrow-cont').click(function() {
		var toggler = jQuery(this).find('i.vbo-trackings-togglesubrow');
		var elem = toggler.closest('.vbo-trackings-table-body-row').find('.vbo-trackings-table-body-subrow');
		elem.slideToggle(400, function() {
			if (elem.is(':visible')) {
				toggler.removeClass('fa-chevron-down').addClass('fa-chevron-up');
			} else {
				toggler.removeClass('fa-chevron-up').addClass('fa-chevron-down');
			}
		});
	});
	jQuery('.vbo-trackings-table-body-row').dblclick(function() {
		if (jQuery(this).find('.vbo-trackings-table-body-subrow').is(':visible')) {
			e.preventDefault();
			return;
		}
		jQuery(this).find('.vbo-trackings-togglesubrow-cont').trigger('click');
	});
	jQuery('#vbo-date-from').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		<?php echo ($this->mindate > 0 ? 'minDate: "'.date(str_replace('%', '', $nowdf), $this->mindate).'", ' : '').($this->maxdate > 0 ? 'maxDate: "'.date(str_replace('%', '', $nowdf), $this->maxdate).'", ' : ''); ?>
		onSelect: function( selectedDate ) {
			jQuery('#vbo-date-to').datepicker('option', 'minDate', selectedDate);
		}
	});
	jQuery('#vbo-date-to').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		<?php echo ($this->mindate > 0 ? 'minDate: "'.date(str_replace('%', '', $nowdf), $this->mindate).'", ' : '').($this->maxdate > 0 ? 'maxDate: "'.date(str_replace('%', '', $nowdf), $this->maxdate).'", ' : ''); ?>
		onSelect: function( selectedDate ) {
			jQuery('#vbo-date-from').datepicker('option', 'maxDate', selectedDate);
		}
	});
	jQuery('#vbo-date-from-trig, #vbo-date-to-trig').click(function() {
		var jdp = jQuery(this).prev('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});
	jQuery('.vbo-trackings-tab').click(function() {
		var newtabrel = jQuery(this).attr('data-vbotab');
		var oldtabrel = jQuery(".vbo-trackings-tab-active").attr('data-vbotab');
		if (newtabrel == oldtabrel) {
			return;
		}
		jQuery(".vbo-trackings-tab").removeClass("vbo-trackings-tab-active");
		jQuery(this).addClass("vbo-trackings-tab-active");
		jQuery("." + oldtabrel).hide();
		jQuery("." + newtabrel).fadeIn();
		jQuery("#vbo_active_tab").val(newtabrel);
	});
	jQuery(".vbo-trackings-tab[data-vbotab='<?php echo $pactive_tab; ?>']").trigger('click');
});
</script>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">

	<div id="filter-bar" class="btn-toolbar vbo-btn-toolbar vbo-trackings-filters" style="width: 100%; display: inline-block;">
		<div class="btn-group pull-right">
			<a class="btn" href="index.php?option=com_vikbooking&task=trkconfig"><?php VikBookingIcons::e('cogs'); ?> <?php echo JText::translate('VBTRKSETTINGS'); ?></a>
		</div>
		<div class="btn-group pull-left input-append">
			<input type="text" id="vbo-date-from" placeholder="<?php echo JText::translate('VBNEWSEASONONE'); ?>" value="<?php echo $pdatefiltfrom; ?>" size="14" name="datefiltfrom" onfocus="this.blur();" />
			<button type="button" class="btn" id="vbo-date-from-trig"><i class="icon-calendar"></i></button>
		</div>
		<div class="btn-group pull-left input-append">
			<input type="text" id="vbo-date-to" placeholder="<?php echo JText::translate('VBNEWSEASONTWO'); ?>" value="<?php echo $pdatefiltto; ?>" size="14" name="datefiltto" onfocus="this.blur();" />
			<button type="button" class="btn" id="vbo-date-to-trig"><i class="icon-calendar"></i></button>
		</div>
		<div class="btn-group pull-left">
		<?php
		$datesel = '<select name="datefilt">';
		$datesel .= '<option value="1"'.(!empty($pdatefilt) && $pdatefilt == 1 ? ' selected="selected"' : '').'>'.JText::translate('VBTRKFILTTRKDATES').'</option>';
		$datesel .= '<option value="2"'.(!empty($pdatefilt) && $pdatefilt == 2 ? ' selected="selected"' : '').'>'.JText::translate('VBTRKBOOKINGDATES').'</option>';
		$datesel .= '<option value="3"'.(!empty($pdatefilt) && $pdatefilt == 3 ? ' selected="selected"' : '').'>'.JText::translate('VBOFILTERDATEIN').'</option>';
		$datesel .= '<option value="4"'.(!empty($pdatefilt) && $pdatefilt == 4 ? ' selected="selected"' : '').'>'.JText::translate('VBOFILTERDATEOUT').'</option>';
		$datesel .= '</select>';
		echo $datesel;
		?>
		</div>
		<div class="btn-group pull-left">
			<span style="font-size: 15px;">&nbsp;</span>
		</div>
		<div class="btn-group pull-left">
			<select name="countryfilt" id="countryfilt">
				<option value=""><?php echo JText::translate('VBCOUNTRYFILTER'); ?></option>
			<?php
			$pcountryfilt = VikRequest::getString('countryfilt', '', 'request');
			foreach ($this->countries as $c) {
				?>
				<option value="<?php echo $c['country']; ?>"<?php echo $c['country'] == $pcountryfilt ? ' selected="selected"' : ''; ?>><?php echo $c['country_name']; ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="btn-group pull-left">
			<span style="font-size: 15px;">&nbsp;</span>
		</div>
		<div class="btn-group pull-left">
			<select name="referrer" style="max-width: 170px;">
				<option value=""><?php echo JText::translate('VBREFERRERFILTER'); ?></option>
			<?php
			$preferrer = VikRequest::getString('referrer', '', 'request');
			foreach ($this->referrers as $r) {
				$say_referrer = !strcasecmp($r['referrer'], 'googlehotel') ? 'Google Hotel' : $r['referrer'];
				$say_referrer = strpos($say_referrer, 'http') === false ? ucwords($say_referrer) : $say_referrer;
				?>
				<option value="<?php echo $r['referrer']; ?>"<?php echo $r['referrer'] == $preferrer ? ' selected="selected"' : ''; ?>><?php echo $say_referrer; ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="btn-group pull-left">
			<span style="font-size: 15px;">&nbsp;</span>
		</div>
		<div class="btn-group pull-left">
			<button type="submit" class="btn"><i class="icon-search"></i> <?php echo JText::translate('VBTRKFILTRES'); ?></button>
		</div>
		<div class="btn-group pull-left">
			<button type="button" class="btn" onclick="jQuery('#filter-bar').find('input, select').val('');document.adminForm.submit();"><?php echo JText::translate('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
	</div>

<?php
if (!(int)VikBookingTracker::loadSettings('trkenabled')) {
	?>
	<p class="err"><?php echo JText::translate('VBTRKDISABLED'); ?></p>
	<?php
}
if (empty($rows)) {
	?>
	<p class="warn"><?php echo JText::translate('VBNOTRACKINGS'); ?></p>
	<?php
} else {
	// gather all the IPs with missing geo information
	$missing_ips = array();
	foreach ($rows as $row) {
		if (empty($row['geo']) && !empty($row['ip'])) {
			$missing_ips[$row['id']] = $row['ip'];
		}
	}
	if (count($missing_ips)) {
		?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=getgeoinfo'); ?>",
			data: {
				tmpl: "component",
				ips: <?php echo json_encode($missing_ips); ?>
			}
		}).done(function(res) {
			if (res.indexOf('e4j.error') >= 0 ) {
				console.log(res);
			} else {
				var obj_res = JSON.parse(res);
				for (var i in obj_res) {
					if (!obj_res.hasOwnProperty(i)) {
						continue;
					}
					if (obj_res[i].hasOwnProperty('geo') && jQuery('#geo-'+i).length) {
						jQuery('#geo-'+i).text(obj_res[i]['geo']);
					}
					if (obj_res[i].hasOwnProperty('country') && jQuery('#country-'+i).length) {
						jQuery('#country-'+i).text(obj_res[i]['country']);
					}
					if (obj_res[i].hasOwnProperty('country') && obj_res[i].hasOwnProperty('country3') && !vboCountryHasVal(obj_res[i]['country3'])) {
						jQuery('#countryfilt').append('<option value="'+obj_res[i]['country3']+'">'+obj_res[i]['country']+'</option>');
					}
				}
			}
		}).fail(function() {
			console.log("getgeoinfo Request Failed");
		});
	});
	function vboCountryHasVal(c3) {
		var hasval = false;
		jQuery('#countryfilt option').each(function(k, v) {
			if (jQuery(v).attr('value') == c3) {
				hasval = true;
				return false;
			}
		});

		return hasval;
	}
	</script>
		<?php
	}
	?>

	<div class="vbo-trackings-outer-response">
		<div class="vbo-trackings-tabs">
			<div class="vbo-trackings-tab vbo-trackings-tab-active" data-vbotab="vbo-trackings-tabcont-list"><?php echo JText::translate('VBTRKVISITORS'); ?></div>
			<div class="vbo-trackings-tab" data-vbotab="vbo-trackings-tabcont-stats"><?php echo JText::translate('VBTRKCONVRATES'); ?></div>
		</div>
		<div class="vbo-trackings-tabcont-list" style="display: block;">
			<div class="vbo-trackings-table">
				<div class="vbo-trackings-table-head">
					<div class="vbo-trackings-table-head-inner">
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-chevron"></div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-ckb">
							<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle">
						</div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-id">
							<a href="index.php?option=com_vikbooking&amp;task=trackings<?php echo $dates_filter; ?>&amp;vborderby=id&amp;vbordersort=<?php echo ($orderby == "id" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "id" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "id" ? "vbo-list-activesort" : "")); ?>">
								ID<?php echo ($orderby == "id" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "id" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
							</a>
						</div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-lastdt">
							<a href="index.php?option=com_vikbooking&amp;task=trackings<?php echo $dates_filter; ?>&amp;vborderby=lastdt&amp;vbordersort=<?php echo ($orderby == "lastdt" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "lastdt" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "lastdt" ? "vbo-list-activesort" : "")); ?>">
								<?php echo JText::translate('VBTRKLASTDT').($orderby == "lastdt" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "lastdt" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
							</a>
						</div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-customer">
							<span><?php echo JText::translate( 'VBOCUSTOMER' ); ?></span>
						</div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-country">
							<a href="index.php?option=com_vikbooking&amp;task=trackings<?php echo $dates_filter; ?>&amp;vborderby=country&amp;vbordersort=<?php echo ($orderby == "country" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "country" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "country" ? "vbo-list-activesort" : "")); ?>">
								<?php echo JText::translate('ORDER_STATE').($orderby == "country" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "country" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
							</a>
						</div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-geo">
							<a href="index.php?option=com_vikbooking&amp;task=trackings<?php echo $dates_filter; ?>&amp;vborderby=geo&amp;vbordersort=<?php echo ($orderby == "geo" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "geo" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "geo" ? "vbo-list-activesort" : "")); ?>">
								<?php echo JText::translate('VBTRKGEOINFO').($orderby == "geo" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "geo" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
							</a>
						</div>
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-dt">
							<a href="index.php?option=com_vikbooking&amp;task=trackings<?php echo $dates_filter; ?>&amp;vborderby=dt&amp;vbordersort=<?php echo ($orderby == "dt" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "dt" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "dt" ? "vbo-list-activesort" : "")); ?>">
								<?php echo JText::translate('VBTRKFIRSTDT').($orderby == "dt" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "dt" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
							</a>
						</div>
						<div class="vbo-trackings-table-head-cell center vbo-trackings-table-cell-published">
							<a href="index.php?option=com_vikbooking&amp;task=trackings<?php echo $dates_filter; ?>&amp;vborderby=published&amp;vbordersort=<?php echo ($orderby == "published" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "published" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "published" ? "vbo-list-activesort" : "")); ?>">
								<?php echo JText::translate('VBTRKPUBLISHED').($orderby == "published" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "published" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
							</a>
						</div>
					</div>
				</div>

				<div class="vbo-trackings-table-body">
			<?php
			$kk = 0;
			$i = 0;
			for ($i = 0, $n = count($rows); $i < $n; $i++) {
				$row = $rows[$i];
				$customer_info = JText::translate('VBOANONYMOUS');
				if (!empty($row['first_name']) || !empty($row['last_name'])) {
					$customer_info = $row['first_name'].' '.$row['last_name'];
					$check_country = $row['country'];
					if (empty($check_country) && !empty($row['c_country'])) {
						$check_country = $row['c_country'];
					}
					if (!empty($check_country)) {
						if (file_exists(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$check_country.'.png')) {
							$customer_info .= '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$check_country.'.png'.'" title="'.$check_country.'" class="vbo-country-flag vbo-country-flag-left"/>';
						}
					}
				}
				$dt_info = getdate(strtotime($row['dt']));
				$lastdt_info = getdate(strtotime($row['lastdt']));
				?>
					<div class="vbo-trackings-table-body-row">
						<div class="vbo-trackings-table-head-cell vbo-trackings-table-cell-chevron vbo-trackings-togglesubrow-cont">
							<?php VikBookingIcons::e('chevron-down', 'vbo-trackings-togglesubrow'); ?>
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-ckb">
							<input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);">
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-id">
							<div class="vbo-trackings-table-body-hidden-lbl">ID</div>
							<?php echo $row['id']; ?>
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-lastdt">
							<div class="vbo-trackings-table-body-hidden-lbl"><?php echo JText::translate('VBTRKLASTDT'); ?></div>
							<div class="vbo-trackings-dtonly">
								<?php echo date(str_replace("/", $datesep, $df), strtotime($row['lastdt'])); ?>
							</div>
							<div class="vbo-trackings-timeonly">
								<span class="vbo-trackings-wday"><?php echo JText::translate('VB'.strtoupper(substr($lastdt_info['weekday'], 0, 3))); ?></span>
								<span class="vbo-trackings-time"><?php echo date('H:i', strtotime($row['lastdt'])); ?></span>
							</div>
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-customer">
							<div class="vbo-trackings-table-body-hidden-lbl"><?php echo JText::translate('VBOCUSTOMER'); ?></div>
							<?php echo $customer_info; ?>
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-country" id="country-<?php echo $row['id']; ?>">
							<div class="vbo-trackings-table-body-hidden-lbl"><?php echo JText::translate('ORDER_STATE'); ?></div>
							<?php echo !empty($row['country_name']) ? $row['country_name'] : '-----'; ?>
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-geo" id="geo-<?php echo $row['id']; ?>">
							<div class="vbo-trackings-table-body-hidden-lbl"><?php echo JText::translate('VBTRKGEOINFO'); ?></div>
							<?php echo !empty($row['geo']) ? $row['geo'] : '-----'; ?>
						</div>
						<div class="vbo-trackings-table-body-cell vbo-trackings-table-cell-dt">
							<div class="vbo-trackings-table-body-hidden-lbl"><?php echo JText::translate('VBTRKFIRSTDT'); ?></div>
							<div class="vbo-trackings-dtonly">
								<?php echo date(str_replace("/", $datesep, $df), strtotime($row['dt'])); ?>
							</div>
							<div class="vbo-trackings-timeonly">
								<span class="vbo-trackings-wday"><?php echo JText::translate('VB'.strtoupper(substr($dt_info['weekday'], 0, 3))); ?></span>
								<span class="vbo-trackings-time"><?php echo date('H:i', strtotime($row['dt'])); ?></span>
							</div>
						</div>
						<div class="vbo-trackings-table-body-cell center vbo-trackings-table-cell-published">
							<div class="vbo-trackings-table-body-hidden-lbl"><?php echo JText::translate('VBTRKPUBLISHED'); ?></div>
							<a href="index.php?option=com_vikbooking&amp;task=modtracking&amp;cid[]=<?php echo $row['id']; ?>"><?php echo ($row['published'] ? "<i class=\"".VikBookingIcons::i('check', 'vbo-icn-img')."\" style=\"color: #099909;\" title=\"".JText::translate('VBTRKMAKENOTAVAIL')."\"></i>" : "<i class=\"".VikBookingIcons::i('times-circle', 'vbo-icn-img')."\" style=\"color: #ff0000;\" title=\"".JText::translate('VBTRKMAKEAVAIL')."\"></i>"); ?></a>
						</div>

						<div class="vbo-trackings-table-body-subrow">
							<div class="vbo-tracking-info-container">
							<?php
							$tot_infos = count($row['infos']);
							foreach ($row['infos'] as $k => $info) {
								$trkdata = json_decode($info['trkdata']);
								$trkdata = !is_object($trkdata) ? (new stdClass) : $trkdata;
								$is_subidentifier = false;
								$is_opening = false;
								if (!isset($row['infos'][($k - 1)]) || $info['identifier'] != $row['infos'][($k - 1)]['identifier']) {
									// open identifier because previous is different or not set (this is the first record)
									$is_opening = true;
									echo '<div class="vbo-tracking-identifier-container">'."\n";
								} elseif (isset($row['infos'][($k - 1)]) && $info['identifier'] == $row['infos'][($k - 1)]['identifier']) {
									$is_subidentifier = true;
								}
								?>
								<div class="vbo-tracking-info-details<?php echo $is_subidentifier ? ' vbo-tracking-info-details-continue' : ''; ?><?php echo !empty($info['idorder']) ? ' vbo-tracking-info-hasconversion' : ''; ?>">
								<?php
								$device = '';
								if ($is_opening) {
									if ($info['device'] == 'C') {
										// computer
										$device = '<i class="'.VikBookingIcons::i('desktop', 'vbo-tracking-i-desktop').'"></i>';
									} elseif ($info['device'] == 'S') {
										// smartphone
										$device = '<i class="'.VikBookingIcons::i('mobile', 'vbo-tracking-i-mobile').'"></i>';
									} elseif ($info['device'] == 'T') {
										// tablet
										$device = '<i class="'.VikBookingIcons::i('tablet', 'vbo-tracking-i-tablet').'"></i>';
									}
								}
								if (!empty($device)) {
									?>
									<div class="vbo-tracking-info-device-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBTRKDEVICE'); ?></div>
										<span class="vbo-tracking-info-device"><?php echo $device; ?></span>
									</div>
									<?php
								} else {
									?>
									<div class="vbo-tracking-info-device-cont"></div>
									<?php
								}
								?>
									<div class="vbo-tracking-info-dt-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBTRKTRACKTIME'); ?></div>
									<?php
									if (!$is_subidentifier) {
										$subdt_info = getdate(strtotime($info['trackingdt']));
										?>
										<div class="vbo-tracking-info-dtonly">
											<?php echo date(str_replace("/", $datesep, $df), strtotime($info['trackingdt'])); ?>
										</div>
										<div class="vbo-tracking-info-timeonly">
											<span class="vbo-tracking-info-wday"><?php echo JText::translate('VB'.strtoupper(substr($subdt_info['weekday'], 0, 3))); ?></span>
											<span class="vbo-tracking-info-time"><?php echo date('H:i', strtotime($info['trackingdt'])); ?></span>
										</div>
										<?php
									} else {
										$diff_info = VikBookingTracker::datesDiff($info['trackingdt'], $row['infos'][($k - 1)]['trackingdt']);
										$diff_type = JText::translate('VBTRKDIFFSECS');
										if ($diff_info['type'] == 'minutes') {
											$diff_type = JText::translate('VBTRKDIFFMINS');
										} elseif ($diff_info['type'] == 'hours') {
											$diff_type = JText::translate('VBCONFIGONETENEIGHT');
										}
										?>
										<span class="vbo-tracking-info-aftertime" title="<?php echo date(str_replace("/", $datesep, $df).' H:i:s', strtotime($info['trackingdt'])); ?>">+ <?php echo $diff_info['diff'] . ' ' . $diff_type; ?></span>
										<?php
									}
									?>
									</div>
									<div class="vbo-tracking-info-dates-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBTRKBOOKINGDATES'); ?></div>
										<div class="vbo-tracking-info-dates-in">
											<span class="vbo-tracking-info-lbl">
												<?php echo JText::translate('VBPICKUPAT'); ?>
											</span>
											<span class="vbo-tracking-info-val">
												<?php
												$tsdt = strtotime($trkdata->checkin);
												$time_info = getdate($tsdt);
												echo JText::translate('VB'.strtoupper(substr($time_info['weekday'], 0, 3))) . ', ' . date(str_replace("/", $datesep, $df), $tsdt);
												?>
											</span>
										</div>
										<div class="vbo-tracking-info-dates-out">
											<span class="vbo-tracking-info-lbl">
												<?php echo JText::translate('VBRELEASEAT'); ?>
											</span>
											<span class="vbo-tracking-info-val">
												<?php
												$tsdt = strtotime($trkdata->checkout);
												$time_info = getdate($tsdt);
												echo JText::translate('VB'.strtoupper(substr($time_info['weekday'], 0, 3))) . ', ' . date(str_replace("/", $datesep, $df), $tsdt);
												?>
											</span>
										</div>
									<?php
									if (isset($trkdata->nights)) {
										?>
										<div class="vbo-tracking-info-dates-out">
											<span class="vbo-tracking-info-lbl">
												<?php echo JText::translate('VBPVIEWORDERSSIX'); ?>
											</span>
											<span class="vbo-tracking-info-val">
												<?php echo $trkdata->nights; ?>
											</span>
										</div>
										<?php
									}
									?>
									</div>
								<?php
								if (isset($trkdata->party)) {
									?>
									<div class="vbo-tracking-info-party-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBQUICKRESGUESTS'); ?></div>
									<?php
									foreach ($trkdata->party as $numroom => $guests) {
										?>
										<div class="vbo-tracking-info-party-room">
											<span class="vbo-tracking-info-lbl">
												<?php echo JText::translate('VBMAILROOMNUM').$numroom; ?>
											</span>
											<span class="vbo-tracking-info-val">
												<?php echo $guests->adults." ".($guests->adults > 1 ? JText::translate('VBMAILADULTS') : JText::translate('VBMAILADULT')).($guests->children > 0 ? ", ".$guests->children." ".($guests->children > 1 ? JText::translate('VBMAILCHILDREN') : JText::translate('VBMAILCHILD')) : "");; ?>
											</span>
										</div>
										<?php
									}
									?>
									</div>
									<?php
								}
								if (isset($trkdata->rooms) || isset($trkdata->rplans)) {
									?>
									<div class="vbo-tracking-info-roomsrates-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBTRKROOMSRATES'); ?></div>
									<?php
									if (isset($trkdata->rooms)) {
										?>
											<div class="vbo-tracking-info-rooms">
										<?php
										if (isset($trkdata->rindex) && is_array($trkdata->rindex)) {
											$rindexes = array_filter($trkdata->rindex);
											if (count($rindexes)) {
												// display all indexes booked, even the empty ones
												?>
												<div class="vbo-tracking-info-rooms-room">
													<span class="vbo-tracking-info-lbl">
														<?php echo JText::translate('VBODISTFEATURERUNIT') . ' ' . implode(', ', $trkdata->rindex); ?>
													</span>
												</div>
												<?php
											}
										}
										foreach ($trkdata->rooms as $idroom => $units) {
											?>
												<div class="vbo-tracking-info-rooms-room">
													<span class="vbo-tracking-info-lbl">
														<?php echo (isset($this->rooms[$idroom]) ? $this->rooms[$idroom] : '?').($units > 1 ? ' (x'.$units.')' : ''); ?>
													</span>
												</div>
											<?php
										}
										?>
											</div>
										<?php
									}
									if (isset($trkdata->rplans)) {
										?>
											<div class="vbo-tracking-info-rplans">
										<?php
										foreach ($trkdata->rplans as $idprice => $units) {
											?>
												<div class="vbo-tracking-info-rplans-room">
													<span class="vbo-tracking-info-lbl">
														<?php echo (isset($this->prices[$idprice]) ? $this->prices[$idprice] : '?').($units > 1 ? ' (x'.$units.')' : ''); ?>
													</span>
												</div>
											<?php
										}
										?>
											</div>
										<?php
									}
									?>
									</div>
									<?php
								}
								if (!empty($info['idorder'])) {
									if ($info['status'] == "confirmed") {
										$saystaus = '<span class="label label-success vbo-status-label">'.JText::translate('VBCONFIRMED').'</span>';
									} elseif ($info['status'] == "standby") {
										$saystaus = '<span class="label label-warning vbo-status-label">'.JText::translate('VBSTANDBY').'</span>';
									} else {
										$saystaus = '<span class="label label-error vbo-status-label">'.JText::translate('VBCANCELLED').'</span>';
									}
									?>
									<div class="vbo-tracking-info-booking-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBTRKBOOKCONV'); ?></div>
										<?php echo $saystaus; ?>
										<a href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $info['idorder']; ?>" target="_blank"><?php VikBookingIcons::e('external-link'); ?> <?php echo $info['idorder']; ?></a>
									</div>
									<?php
								} else {
									/**
									 * Check if some extra tracking data for special referres (set by VCM) should be displayed.
									 * 
									 * @since 	1.15.0 (J) - 1.5.0 (WP)
									 */
									$extra_trk_props = array();
									$common_props = VikBookingTracker::$common_trk_props;
									foreach ($trkdata as $trk_prop => $trk_val) {
										if (!is_string($trk_prop) || !is_scalar($trk_val) || in_array($trk_prop, $common_props)) {
											continue;
										}
										$say_trk_prop = ucwords(str_replace('_', ' ', $trk_prop));
										$extra_trk_props[$say_trk_prop] = $trk_val;
									}
									if (count($extra_trk_props)) {
										// display the extra tracking properties
										?>
									<div class="vbo-tracking-info-booking-cont">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBOGUESTEXTRANOTES'); ?></div>
										<div class="vbo-tracking-info-extra-props">
										<?php
										foreach ($extra_trk_props as $trk_prop => $trk_val) {
											?>
											<div class="vbo-tracking-info-extra-prop">
												<span class="vbo-tracking-info-extra-prop-name"><strong><?php echo $trk_prop; ?></strong></span>
												<span class="vbo-tracking-info-extra-prop-val"><?php echo $trk_val; ?></span>
											</div>
											<?php
										}
										?>
										</div>
									</div>
										<?php
									}
								}
								if (!empty($info['referrer'])) {
									/**
									 * The tracking referrer may be equal to a channel available in VCM. Try to get its logo.
									 * 
									 * @since 	1.15.0 (J) - 1.5.0 (WP)
									 */
									$referrer_logo = VikBooking::getVcmChannelsLogo($info['referrer']);
									if ($referrer_logo === false) {
										$say_referrer = strpos($info['referrer'], 'http') === false ? ucwords($info['referrer']) : $info['referrer'];
										$referrer_logo = '<span><i class="' . VikBookingIcons::i('globe') . '"></i> ' . $say_referrer . '</span>';
									} else {
										$referrer_logo = '<span class="vbo-tracking-info-referrer-logo"><img src="' . $referrer_logo . '"/></span>';
									}
									?>
									<div class="vbo-tracking-info-booking-referrer">
										<div class="vbo-tracking-info-subrow-lbl"><?php echo JText::translate('VBTRKREFERRER'); ?></div>
										<?php echo $referrer_logo; ?>
									</div>
									<?php
								}
								if (isset($trkdata->msg)) {
									?>
									<div class="vbo-tracking-info-search-results">
									<?php
									foreach ($trkdata->msg as $msg) {
										$msg_type = strtolower($msg->type);
										$msg_icon = '<i class="'.VikBookingIcons::i('info-circle').'"></i>';
										if ($msg_type == 'success') {
											$msg_icon = '<i class="'.VikBookingIcons::i('check-circle').'"></i>';
										} elseif ($msg_type == 'warning') {
											$msg_icon = '<i class="'.VikBookingIcons::i('exclamation-triangle').'"></i>';
										} elseif ($msg_type == 'error') {
											$msg_icon = '<i class="'.VikBookingIcons::i('times-circle').'"></i>';
										}
										?>
										<div class="vbo-tracking-info-search-result vbo-tracking-info-search-result-<?php echo $msg_type; ?>">
											<p><?php echo $msg_icon . ' ' . $msg->text; ?></p>
										</div>
										<?php
									}
									?>
									</div>
									<?php
								}
								?>
								</div>
								<?php
								if (!isset($row['infos'][($k + 1)]) || $info['identifier'] != $row['infos'][($k + 1)]['identifier']) {
									// close current identifier because next is different
									echo '</div>'."\n";
								}
							}
							?>
							</div>
						</div>
					</div>
				<?php
				$kk = 1 - $kk;
			}
			?>
				</div>
			</div>
		</div>
		<?php
		// calculate most demanded nights, conversion rates, best referrers, average LOS
		$demands_nights = array();
		$demands_count  = array();
		$referrer_count = array();
		$totidentifiers = array();
		$totbookings 	= array();
		$los_pool 		= array();
		foreach ($this->stats_data as $stat) {
			if (!empty($stat['referrer'])) {
				if (!isset($referrer_count[$stat['referrer']])) {
					$referrer_count[$stat['referrer']] = 0;
				}
				$referrer_count[$stat['referrer']]++;
			}
			if (!isset($totidentifiers[$stat['identifier']])) {
				// total identifiers
				$totidentifiers[$stat['identifier']] = 1;
			}
			if (!empty($stat['idorder']) && !isset($totbookings[$stat['identifier']])) {
				// one conversion per tracking identifier
				$totbookings[$stat['identifier']] = $stat['idorder'];
			}
			// loop through the nights of this tracking info record
			$in_ts 	 = strtotime($stat['checkin']);
			$in_info = getdate($in_ts);
			$out_ts  = strtotime($stat['checkout']);
			$out_dt  = date('Y-m-d', $out_ts);
			$in_dt   = date('Y-m-d', $in_info[0]);
			$now_los = 0;
			while ($in_dt != $out_dt) {
				if (!($in_ts < $out_ts)) {
					// prevent any possible loop in case of records with invalid data
					break;
				}
				$now_los++;
				if (!isset($demands_nights[$in_dt])) {
					$demands_nights[$in_dt] = 0;
				}
				// increase the requests for this night
				$demands_nights[$in_dt]++;
				if (!isset($demands_count[$in_dt])) {
					$demands_count[$in_dt] = array();
				}
				if (!in_array($stat['idtracking'], $demands_count[$in_dt])) {
					// push this visitor (tracking) ID to the counter for this night
					array_push($demands_count[$in_dt], $stat['idtracking']);
				}
				// update next loop
				$in_info = getdate(mktime(0, 0, 0, $in_info['mon'], ($in_info['mday'] + 1), $in_info['year']));
				$in_dt   = date('Y-m-d', $in_info[0]);
			}
			array_push($los_pool, $now_los);
		}

		// sort most demanded nights and best referrers
		arsort($demands_nights);
		arsort($referrer_count);

		// count values that could be 0
		$cnt_tot_idfs = count($totidentifiers);
		$cnt_tot_idfs = $cnt_tot_idfs > 0 ? $cnt_tot_idfs : 1;
		$cnt_los_pool = count($los_pool);
		$cnt_los_pool = $cnt_los_pool > 0 ? $cnt_los_pool : 1;

		// average conversion rate: 100 : totidentifiers = x : totbookings
		$avg_conv_rate = 100 * count($totbookings) / $cnt_tot_idfs;
		$avg_conv_rate = round($avg_conv_rate, 2);
		$avg_conv_colr = '#550000'; //black-red
		if ($avg_conv_rate > 33 && $avg_conv_rate <= 66) {
			$avg_conv_colr = '#ff4d4d'; //red
		} elseif ($avg_conv_rate > 66 && $avg_conv_rate < 100) {
			$avg_conv_colr = '#ffa64d'; //orange
		} elseif ($avg_conv_rate >= 100) {
			$avg_conv_colr = '#2a762c'; //green
		}

		// average length of stay
		$avg_los = array_sum($los_pool) / $cnt_los_pool;
		$avg_los = round($avg_los, 1);
		?>
		<div class="vbo-trackings-tabcont-stats" style="display: none;">
			<div class="vbo-trackings-chart-bestnights">
				<h4><?php echo JText::translate('VBTRKMOSTDEMNIGHTS'); ?></h4>
			<?php
			// the 14 most demanded nights
			$max = 14;
			$ind = 0;
			foreach ($demands_nights as $dt => $tot) {
				$dt_info = getdate(strtotime($dt));
				?>
				<div class="vbo-trackings-chart-container" id="vbo-trackings-chart-container-<?php echo $dt; ?>">
					<span class="vbo-trackings-chart-date"><?php echo JText::translate('VB'.strtoupper(substr($dt_info['weekday'], 0, 3))); ?>, <?php echo date(str_replace("/", $datesep, $df), $dt_info[0]); ?></span>
					<div class="vbo-trackings-chart-cont">
						<div class="vbo-trackings-chart-totreqs">
							<span class="vbo-trackings-chart-tot"><?php echo $tot; ?></span>
							<span class="vbo-trackings-chart-txt"><?php echo JText::translate('VBTRKREQSNUM'); ?></span>
						</div>
						<div class="vbo-trackings-chart-totviss">
							<span class="vbo-trackings-chart-tot"><?php echo count($demands_count[$dt]); ?></span>
							<span class="vbo-trackings-chart-txt"><?php echo JText::translate('VBTRKVISSNUM'); ?></span>
						</div>
					</div>
				</div>
				<?php
				$ind++;
				if ($ind >= $max) {
					break;
				}
			}
			?>
			</div>

			<div class="vbo-trackings-chart-middle">

				<div class="vbo-trackings-chart-avgvals">
					<div class="vbo-trackings-chart-avgval-container">
						<h4><?php echo JText::translate('VBTRKAVGVALS'); ?></h4>
						<div class="vbo-trackings-chart-avgval-listcont">
							<div class="vbo-trackings-avgval">
								<div class="vbo-trackings-avgval-det">
									<h5><?php echo JText::translate('VBTRKTOTVISS'); ?></h5>
									<div class="vbo-trackings-chart-avgviss">
										<span class="vbo-trackings-chart-tot"><?php echo count($totidentifiers); ?></span>
									</div>
								</div>
							</div>
							<div class="vbo-trackings-avgval">
								<div class="vbo-trackings-avgval-det">
									<h5><?php echo JText::translate('VBCUSTOMERTOTBOOKINGS'); ?></h5>
									<div class="vbo-trackings-chart-totres">
										<span class="vbo-trackings-chart-tot"><?php echo count($totbookings); ?></span>
									</div>
								</div>
							</div>
							<div class="vbo-trackings-avgval">
								<div class="vbo-trackings-avgval-det">
									<h5><?php echo JText::translate('VBTRKAVGLOS'); ?></h5>
									<div class="vbo-trackings-chart-avglos">
										<span class="vbo-trackings-chart-tot"><?php echo $avg_los; ?></span>
									</div>
								</div>
							</div>
							<div class="vbo-trackings-avgval">
								<div class="vbo-trackings-avgval-det">
									<h5><?php echo JText::translate('VBTRKAVGCONVRATE'); ?> <?php echo $vbo_app->createPopover(array('title' => JText::translate('VBTRKAVGCONVRATE'), 'content' => JText::translate('VBTRKAVGCONVRATEHELP'))); ?></h5>
									<div class="vbo-trackings-chart-avgconvrate">
										<span class="vbo-trackings-chart-tot" style="color: <?php echo $avg_conv_colr; ?>;"><?php echo $avg_conv_rate; ?></span>
										<span class="vbo-trackings-chart-pcent">%</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
			if (count($referrer_count)) {
			?>
				<div class="vbo-trackings-chart-referrers">
					<h4><?php echo JText::translate('VBTRKBESTREFERRERS'); ?></h4>
				<?php
				// the 5 best referrers
				$max = 5;
				$ind = 0;
				foreach ($referrer_count as $name => $tot) {
					?>
					<div class="vbo-trackings-chart-referrer">
						<span class="vbo-trackings-chart-date"><?php echo $name; ?></span>
						<div class="vbo-trackings-chart-cont">
							<div class="vbo-trackings-chart-totreqs">
								<span class="vbo-trackings-chart-tot"><?php echo $tot; ?></span>
								<span class="vbo-trackings-chart-txt"><?php echo JText::translate('VBTRKVISSNUM'); ?></span>
							</div>
						</div>
					</div>
					<?php
					$ind++;
					if ($ind >= $max) {
						break;
					}
				}
				?>
				</div>
			<?php
			}
			?>
			</div>

		</div>
	</div>
<?php
}
?>
	<input type="hidden" name="vbo_active_tab" id="vbo_active_tab" value="">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="trackings" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $navbut; ?>
</form>
