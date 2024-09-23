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

$ordersrooms = $this->ordersrooms;
$ord = $this->ord;
$all_rooms = $this->all_rooms;
$customer = $this->customer;

$dbo = JFactory::getDbo();
$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$vbo_app->loadDatePicker();

// availability helper
$av_helper = VikBooking::getAvailabilityInstance();

// room stay dates in case of split stay (or modified room nights)
$room_stay_dates   = [];
$js_cal_def_vals   = [];
$room_stay_records = [];
if ($ord['split_stay']) {
	if ($ord['status'] == 'confirmed') {
		$room_stay_dates = $av_helper->loadSplitStayBusyRecords($ord['id']);
	} else {
		$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $ord['id'], []);
	}
	// immediately count the number of nights of stay for each split room
	foreach ($room_stay_dates as $sps_r_k => $sps_r_v) {
		if (!empty($sps_r_v['checkin_ts']) && !empty($sps_r_v['checkout_ts'])) {
			// overwrite values for compatibility with non-confirmed bookings
			$sps_r_v['checkin'] = $sps_r_v['checkin_ts'];
			$sps_r_v['checkout'] = $sps_r_v['checkout_ts'];
		}
		$sps_r_v['nights'] = $av_helper->countNightsOfStay($sps_r_v['checkin'], $sps_r_v['checkout']);
		// overwrite the whole array
		$room_stay_dates[$sps_r_k] = $sps_r_v;
	}
} elseif (!$ord['split_stay'] && !$ord['closure'] && $ord['roomsnum'] > 1 && $ord['days'] > 1 && $ord['status'] == 'confirmed') {
	// load the occupied stay dates for each room in case they were modified
	$room_stay_records = $av_helper->loadSplitStayBusyRecords($ord['id']);
}

$pgoto = VikRequest::getString('goto', '', 'request');
$currencysymb = VikBooking::getCurrencySymb(true);
$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$rit = date('d/m/Y', $ord['checkin']);
	$con = date('d/m/Y', $ord['checkout']);
	$df = 'd/m/Y';
	$juidf = 'dd/mm/yy';
} elseif ($nowdf == "%m/%d/%Y") {
	$rit = date('m/d/Y', $ord['checkin']);
	$con = date('m/d/Y', $ord['checkout']);
	$df = 'm/d/Y';
	$juidf = 'mm/dd/yy';
} else {
	$rit = date('Y/m/d', $ord['checkin']);
	$con = date('Y/m/d', $ord['checkout']);
	$df = 'Y/m/d';
	$juidf = 'yy/mm/dd';
}
$datesep = VikBooking::getDateSeparator(true);

// stay dates details
$checkin_info = getdate($ord['checkin']);
$checkout_info = getdate($ord['checkout']);

$ritho = '';
$conho = '';
$ritmi = '';
$conmi = '';
for ($i = 0; $i < 24; $i++) {
	$ritho .= "<option value=\"".$i."\"".($checkin_info['hours']==$i ? " selected=\"selected\"" : "").">".($i < 10 ? "0".$i : $i)."</option>\n";
	$conho .= "<option value=\"".$i."\"".($checkout_info['hours']==$i ? " selected=\"selected\"" : "").">".($i < 10 ? "0".$i : $i)."</option>\n";
}
for ($i = 0; $i < 60; $i++) {
	$ritmi .= "<option value=\"".$i."\"".($checkin_info['minutes']==$i ? " selected=\"selected\"" : "").">".($i < 10 ? "0".$i : $i)."</option>\n";
	$conmi .= "<option value=\"".$i."\"".($checkout_info['minutes']==$i ? " selected=\"selected\"" : "").">".($i < 10 ? "0".$i : $i)."</option>\n";
}
if ($ord) {
	$pcheckin = $ord['checkin'];
	$pcheckout = $ord['checkout'];
	$secdiff = $pcheckout - $pcheckin;
	$daysdiff = $secdiff / 86400;
	if (is_int($daysdiff)) {
		if ($daysdiff < 1) {
			$daysdiff = 1;
		}
	} else {
		if ($daysdiff < 1) {
			$daysdiff = 1;
		} else {
			$sum = floor($daysdiff) * 86400;
			$newdiff = $secdiff - $sum;
			$maxhmore = VikBooking::getHoursMoreRb() * 3600;
			if ($maxhmore >= $newdiff) {
				$daysdiff = floor($daysdiff);
			} else {
				$daysdiff = ceil($daysdiff);
			}
		}
	}
}
$otachannel = '';
$otachannel_name = '';
$otachannel_bid = '';
$otacurrency = '';
if (!empty($ord['channel'])) {
	$channelparts = explode('_', $ord['channel']);
	$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) > 0 ? $channelparts[1] : ucwords($channelparts[0]);
	$otachannel_name = $otachannel;
	$otachannel_bid = $otachannel.(!empty($ord['idorderota']) ? ' - Booking ID: '.$ord['idorderota'] : '');
	if (strstr($otachannel, '.') !== false) {
		$otaccparts = explode('.', $otachannel);
		$otachannel = $otaccparts[0];
	}
	$otacurrency = !empty($ord['chcurrency']) ? $ord['chcurrency'] : '';
}

$status_type = '';
if (!empty($ord['type'])) {
	$status_type = JText::translate('VBO_BTYPE_' . strtoupper($ord['type']));
	if (!strcasecmp($ord['type'], 'overbooking')) {
		$status_type = '<span class="label label-error vbo-label-nested vbo-label-overbooking">' . $status_type . '</span>';
	}
	$status_type .= ' / ';
}
if ($ord['status'] == "confirmed") {
	$saystaus = '<span class="label label-success">' . $status_type . JText::translate('VBCONFIRMED') . '</span>';
} elseif ($ord['status']=="standby") {
	$saystaus = '<span class="label label-warning">' . $status_type . JText::translate('VBSTANDBY') . '</span>';
} else {
	$saystaus = '<span class="label label-error">' . $status_type . JText::translate('VBCANCELLED') . '</span>';
}

//Package or custom rate
$is_package = !empty($ord['pkg']) ? true : false;
$is_cust_cost = false;
foreach ($ordersrooms as $kor => $or) {
	if ($is_package !== true && !empty($or['cust_cost']) && $or['cust_cost'] > 0.00) {
		$is_cust_cost = true;
		break;
	}
}
$ivas = array();
$wiva = "";
$jstaxopts = '<option value=\"\">'.JText::translate('VBNEWOPTFOUR').'</option>';
$q = "SELECT * FROM `#__vikbooking_iva`;";
$dbo->setQuery($q);
$dbo->execute();
if ($dbo->getNumRows() > 0) {
	$ivas = $dbo->loadAssocList();
	$wiva = "<select name=\"aliq%s\"><option value=\"\">".JText::translate('VBNEWOPTFOUR')."</option>\n";
	foreach ($ivas as $kiva => $iv) {
		$wiva .= "<option value=\"".$iv['id']."\" data-aliqid=\"".$iv['id']."\"" . ($kiva == 0 ? ' selected="selected"' : '') . ">".(empty($iv['name']) ? $iv['aliq']."%" : $iv['name']." - ".$iv['aliq']."%")."</option>\n";
		$jstaxopts .= '<option value=\"'.$iv['id'].'\">'.(empty($iv['name']) ? $iv['aliq']."%" : addslashes($iv['name'])." - ".$iv['aliq']."%").'</option>';
	}
	$wiva .= "</select>\n";
}

// room switching
$switching = false;
$switcher = '';
if ($ord && count($all_rooms) > 1 && (!empty($ordersrooms[0]['idtar']) || $is_package || $is_cust_cost)) {
	$switching = true;
	$occ_rooms = array();
	foreach ($all_rooms as $r) {
		$rkey = $r['fromadult'] < $r['toadult'] ? $r['fromadult'].' - '.$r['toadult'] : $r['toadult'];
		$occ_rooms[$rkey][] = $r;
	}
	$switcher = '<select class="vbo-rswitcher-select" name="%s" id="vbswr%d" onchange="vbIsSwitchable(this.value, %d, %d);"><option></option>'."\n";
	foreach ($occ_rooms as $occ => $rr) {
		$switcher .= '<optgroup label="'.JText::sprintf('VBSWROOMOCC', $occ).'">'."\n";
		foreach ($rr as $r) {
			$switcher .= '<option value="'.$r['id'].'">'.$r['name'].'</option>'."\n";
		}
		$switcher .= '</optgroup>'."\n";
	}
	$switcher .= '</select>'."\n";
}

$canDo = JFactory::getUser();

JText::script('VBPEDITBUSYEXTRACOSTS');
JText::script('VBDASHBOOKINGID');
JText::script('VBPVIEWORDERSONE');
JText::script('VBEDITORDERTHREE');
JText::script('VBDAYS');
JText::script('VBEDITORDERADULTS');
JText::script('VBEDITORDERCHILDREN');
JText::script('VBPEDITBUSYADDEXTRAC');
JText::script('VBO_CONF_RM_OVERBOOKING_FLAG');

?>
<script type="text/javascript">

Joomla.submitbutton = function(task) {
	if ( task == 'removebusy' ) {
		if (confirm('<?php echo htmlspecialchars(JText::translate('VBDELCONFIRM'), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401); ?>')) {
			Joomla.submitform(task, document.adminForm);
		} else {
			return false;
		}
	} else {
		Joomla.submitform(task, document.adminForm);
	}
}

function vbIsSwitchable(toid, fromid, orid) {
	if (parseInt(toid) == parseInt(fromid)) {
		document.getElementById('vbswr'+orid).value = '';
		return false;
	}
	return true;
}

var vboMessages = {
	jscurrency: "<?php echo $currencysymb; ?>",
	extracnameph: "<?php echo htmlspecialchars(JText::translate('VBPEDITBUSYEXTRACNAME')); ?>",
	taxoptions : "<?php echo $jstaxopts; ?>",
	cantaddroom: "<?php echo htmlspecialchars(JText::translate('VBOBOOKCANTADDROOM')); ?>"
};

var vbo_overlay_on = false,
	vbo_can_add_room = false;

jQuery(function() {

	// remove overbooking flag
	jQuery('.vbo-label-overbooking').on('click', function() {
		if (confirm(Joomla.JText._('VBO_CONF_RM_OVERBOOKING_FLAG'))) {
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.delete_type_flag'); ?>",
				{
					bid: '<?php echo $ord['id']; ?>',
					flag: 'overbooking',
				},
				(success) => {
					// remove "overbooking" label
					let parent_label = jQuery('.vbo-bookdet-container').find('.vbo-label-overbooking').parent('.label');
					jQuery('.vbo-bookdet-container').find('.vbo-label-overbooking').remove();
					parent_label.text(parent_label.text().replace(' / ', ''));
				},
				(error) => {
					alert(error.responseText);
				}
			);
		}
	});

	jQuery('#vbo-add-room').click(function() {
		jQuery(".vbo-info-overlay-block").fadeToggle(400, function() {
			if (jQuery(".vbo-info-overlay-block").is(":visible")) {
				vbo_overlay_on = true;
			} else {
				vbo_overlay_on = false;
			}
		});
	});

	jQuery(document).mouseup(function(e) {
		if (!vbo_overlay_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
			vboAddRoomCloseModal();
		}
	});

	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27 && vbo_overlay_on) {
			vboAddRoomCloseModal();
		}
	});

	jQuery(".vbo-rswitcher-select").select2({placeholder: '<?php echo addslashes(JText::translate('VBSWITCHRWITH')); ?>'});

	jQuery('.vb-cal-img, .vbo-caltrigger').click(function() {
		var jdp = jQuery(this).prev('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});
});

function vboAddRoomId(rid) {
	document.getElementById('add_room_id').value = rid;
	var fdate = document.getElementById('checkindate').value;
	var tdate = document.getElementById('checkoutdate').value;
	if (rid.length && fdate.length && tdate.length) {
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=isroombookable'); ?>",
			data: {
				tmpl: "component",
				rid: rid,
				fdate: fdate,
				tdate: tdate
			}
		}).done(function(res) {
			var obj_res = JSON.parse(res);
			if (obj_res['status'] != 1) {
				vbo_can_add_room = false;
				alert(obj_res['err']);
				document.getElementById('add-room-status').style.color = 'red';
			} else {
				vbo_can_add_room = true;
				document.getElementById('add-room-status').style.color = 'green';
			}
		}).fail(function() {
			console.log("isroombookable Request Failed");
			alert('Generic Error');
		});
	} else {
		vbo_can_add_room = false;
		document.getElementById('add-room-status').style.color = '#333333';
	}
}

function vboAddRoomSubmit() {
	if (vbo_can_add_room && document.getElementById('add_room_id').value.length) {
		document.adminForm.task.value = 'updatebusy';
		document.adminForm.submit();
	} else {
		alert(vboMessages.cantaddroom);
	}
}

function vboAddRoomCloseModal() {
	document.getElementById('add_room_id').value = '';
	vbo_can_add_room = false;
	jQuery(".vbo-info-overlay-block").fadeOut();
	vbo_overlay_on = false;
}

function vboConfirmRmRoom(roid) {
	document.getElementById('rm_room_oid').value = '';
	if (!roid.length) {
		return false;
	}
	if (confirm('<?php echo addslashes(JText::translate('VBOBOOKRMROOMCONFIRM')); ?>')) {
		document.getElementById('rm_room_oid').value = roid;
		document.adminForm.task.value = 'updatebusy';
		document.adminForm.submit();
	}
}

function vboEditbusyModifyRoomDates(enabled, room_ind) {
	var elem = jQuery('.vbo-editbooking-room-nights-modify-details[data-roomind="' + room_ind + '"]');
	if (!elem || !elem.length) {
		return false;
	}
	// get calendar fields for this room
	var checkin_field  = elem.find('input.vbo-editbusy-room-modify-dates-checkin');
	var checkout_field = elem.find('input.vbo-editbusy-room-modify-dates-checkout');
	if (!enabled) {
		// hide wrapper element
		elem.hide();
		// make sure the calendars are empty
		checkin_field.val('');
		checkout_field.val('');
		return true;
	}
	// set up calendars
	jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ '' ] );
	<?php
	// calculate min/max dates for check-in and check-out to constrain the values
	$prev_day_checkout_ts = mktime(0, 0, 0, $checkout_info['mon'], ($checkout_info['mday'] - 1), $checkout_info['year']);
	$next_day_checkin_ts  = mktime(0, 0, 0, $checkin_info['mon'], ($checkin_info['mday'] + 1), $checkin_info['year']);
	?>
	var min_checkin_date_obj  = new Date('<?php echo date('Y-m-d', $ord['checkin']); ?> 00:00:00');
	var max_checkin_date_obj  = new Date('<?php echo date('Y-m-d H:i:s', $prev_day_checkout_ts); ?>');
	var min_checkout_date_obj = new Date('<?php echo date('Y-m-d H:i:s', $next_day_checkin_ts); ?>');
	var max_checkout_date_obj = new Date('<?php echo date('Y-m-d', $ord['checkout']); ?> 00:00:00');
	checkin_field.datepicker({
		showOn: 'focus',
		numberOfMonths: 1,
	});
	checkin_field.datepicker('option', 'dateFormat', '<?php echo $juidf; ?>');
	checkin_field.datepicker('option', 'minDate', min_checkin_date_obj);
	checkin_field.datepicker('option', 'maxDate', max_checkin_date_obj);
	checkin_field.datepicker('setDate', checkin_field.attr('data-current-value'));
	checkout_field.datepicker({
		showOn: 'focus',
		numberOfMonths: 1,
	});
	checkout_field.datepicker('option', 'dateFormat', '<?php echo $juidf; ?>');
	checkout_field.datepicker('option', 'minDate', min_checkout_date_obj);
	checkout_field.datepicker('option', 'maxDate', max_checkout_date_obj);
	checkout_field.datepicker('setDate', checkout_field.attr('data-current-value'));
	// show wrapper element
	elem.show();
}

</script>

<script type="text/javascript">
/* custom extra services for each room */
function vboAddExtraCost(rnum) {
	var telem = jQuery("#vbo-ebusy-extracosts-"+rnum);
	if (telem.length > 0) {
		var extracostcont = "<div class=\"vbo-editbooking-room-extracost\">"+"\n"+
			"<div class=\"vbo-ebusy-extracosts-cellname\">"+"\n"+
			"	<input type=\"text\" name=\"extracn["+rnum+"][]\" value=\"\" autocomplete=\"off\" placeholder=\""+vboMessages.extracnameph+"\" size=\"25\" />"+"\n"+
			"	<span class=\"vbo-ebusy-extracosts-search\" onclick=\"vboSearchExtraCost(this);\"><i class=\"<?php echo VikBookingIcons::i('search') ?>\"></i></span>"+"\n"+
			"</div>"+"\n"+
			"<div class=\"vbo-ebusy-extracosts-cellcost\"><span class=\"vbo-ebusy-extracosts-currency\">"+vboMessages.jscurrency+"</span> <input type=\"number\" step=\"any\" name=\"extracc["+rnum+"][]\" value=\"0.00\" size=\"5\" /></div>"+"\n"+
			"<div class=\"vbo-ebusy-extracosts-celltax\"><select name=\"extractx["+rnum+"][]\">"+vboMessages.taxoptions+"</select></div>"+"\n"+
			"<div class=\"vbo-ebusy-extracosts-cellrm\"><button class=\"btn btn-danger\" type=\"button\" onclick=\"vboRemoveExtraCost(this);\">&times;</button></div>"+"\n"+
		"</div>";
		telem.find(".vbo-editbooking-room-extracosts-wrap").append(extracostcont);
	}
}

function vboRemoveExtraCost(elem) {
	var parel = jQuery(elem).closest('.vbo-editbooking-room-extracost');
	if (parel.length > 0) {
		parel.remove();
	}
}

// active service element for search
var search_service_element = null;

function vboSearchExtraCost(elem) {
	parel = jQuery(elem).closest('.vbo-editbooking-room-extracost');
	if (!parel || !parel.length) {
		return;
	}

	// set active service element for search
	search_service_element = parel;

	// current search value
	var search_txt = parel.find('.vbo-ebusy-extracosts-cellname').find('input[type="text"]').val();

	// build modal content
	var servsearch_modal = jQuery('<div></div>').addClass('vbo-modal-overlay-block vbo-modal-overlay-servsearch').css('display', 'block');
	var servsearch_dismiss = jQuery('<a></a>').addClass('vbo-modal-overlay-close');
	servsearch_dismiss.on('click', function() {
		jQuery('.vbo-modal-overlay-servsearch').fadeOut();
	});
	servsearch_modal.append(servsearch_dismiss);
	var servsearch_content = jQuery('<div></div>').addClass('vbo-modal-overlay-content vbo-modal-overlay-content-servsearch');
	var servsearch_head = jQuery('<div></div>').addClass('vbo-modal-overlay-content-head');
	var servsearch_head_title = jQuery('<span></span>').text(Joomla.JText._('VBPEDITBUSYEXTRACOSTS'));
	var servsearch_head_close = jQuery('<span></span>').addClass('vbo-modal-overlay-close-times').html('&times;');
	servsearch_head_close.on('click', function() {
		jQuery('.vbo-modal-overlay-servsearch').fadeOut();
	});
	servsearch_head.append(servsearch_head_title).append(servsearch_head_close);
	var servsearch_body = jQuery('<div></div>').addClass('vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll');
	var servsearch_form = jQuery('<div></div>').addClass('vbo-modal-servsearch-wrap');
	var servsearch_sample = jQuery('.vbo-modal-sample-servsearch').html();
	servsearch_form.append(servsearch_sample);
	servsearch_form.find('input.vbo-servsearch-inp').val(search_txt);
	// attach click listener event for search
	servsearch_form.find('button.vbo-config-btn').on('click', function() {
		var btn_trigger = jQuery(this);
		var form_wrap = btn_trigger.closest('.vbo-modal-servsearch-form-wrap');
		var search_term = form_wrap.find('input.vbo-servsearch-inp').val();
		var search_results = form_wrap.find('.vbo-modal-servsearch-results');

		// empty the search results and add the loading class
		search_results.html('');
		btn_trigger.find('i').attr('class', '<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>');

		// make the request
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.search_service'); ?>",
			{
				service_name: search_term,
				tmpl: "component"
			},
			function(response) {
				btn_trigger.find('i').attr('class', '<?php echo VikBookingIcons::i('search'); ?>');
				var book_base_nav_uri = jQuery('.vbo-booking-basenavuri').attr('href');
				try {
					var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
					if (!obj_res) {
						console.error('Unexpected JSON response', obj_res);
						return false;
					}

					for (var i = 0; i < obj_res.length; i++) {
						// build result structure
						var result_wrap = jQuery('<div></div>').addClass('vbo-modal-servsearch-result');
						var result_service = jQuery('<div></div>').addClass('vbo-modal-servsearch-result-service');
						var result_booking = jQuery('<div></div>').addClass('vbo-modal-servsearch-result-booking');

						// service details
						result_service.append('<span class="vbo-modal-servsearch-result-service-name">' + obj_res[i]['service']['name'] + '</span>');
						result_service.append('<span class="vbo-modal-servsearch-result-service-cost">' + obj_res[i]['service']['format_cost'] + '</span>');
						result_service.append('<span class="vbo-modal-servsearch-result-service-add"><button type="button" class="btn btn-success" data-servicecost="' + obj_res[i]['service']['cost'] + '" data-serviceidtax="' + obj_res[i]['service']['idtax'] + '"><?php VikBookingIcons::e('plus-circle'); ?> ' + Joomla.JText._('VBPEDITBUSYADDEXTRAC') + '</button></span>');

						// booking details
						var serv_booking = '';
						serv_booking += '<div>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-lbl">' + Joomla.JText._('VBDASHBOOKINGID') + '</span>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-val"><a href="' + book_base_nav_uri.replace('%d', obj_res[i]['idorder']) + '" target="_blank">' + obj_res[i]['idorder'] + '</a></span>' + "\n";
						serv_booking += '</div>' + "\n";
						serv_booking += '<div>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-lbl">' + Joomla.JText._('VBPVIEWORDERSONE') + '</span>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-val">' + obj_res[i]['format_dt'] + '</span>' + "\n";
						serv_booking += '</div>' + "\n";
						serv_booking += '<div>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-lbl">' + Joomla.JText._('VBEDITORDERTHREE') + '</span>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-val">' + obj_res[i]['room_name'] + '</span>' + "\n";
						serv_booking += '</div>' + "\n";
						serv_booking += '<div>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-lbl">' + Joomla.JText._('VBDAYS') + '</span>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-val">' + obj_res[i]['nights'] + '</span>' + "\n";
						serv_booking += '</div>' + "\n";
						serv_booking += '<div>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-lbl">' + Joomla.JText._('VBEDITORDERADULTS') + '</span>' + "\n";
						serv_booking += '	<span class="vbo-modal-servsearch-result-booking-val">' + obj_res[i]['adults'] + '</span>' + "\n";
						serv_booking += '</div>' + "\n";
						if (obj_res[i]['children'] > 0) {
							serv_booking += '<div>' + "\n";
							serv_booking += '	<span class="vbo-modal-servsearch-result-booking-lbl">' + Joomla.JText._('VBEDITORDERCHILDREN') + '</span>' + "\n";
							serv_booking += '	<span class="vbo-modal-servsearch-result-booking-val">' + obj_res[i]['children'] + '</span>' + "\n";
							serv_booking += '</div>' + "\n";
						}
						result_booking.append(serv_booking);

						result_wrap.append(result_service);
						result_wrap.append(result_booking);

						// delegate click event on button to add the selected service
						result_wrap.find('.vbo-modal-servsearch-result-service-add button').on('click', function() {
							var service_name = jQuery(this).closest('.vbo-modal-servsearch-result-service').find('.vbo-modal-servsearch-result-service-name').text();
							var service_cost = jQuery(this).attr('data-servicecost');
							var service_idtax = jQuery(this).attr('data-serviceidtax');
							if (!search_service_element) {
								return;
							}
							// populate fields from where the search was started
							search_service_element.find('.vbo-ebusy-extracosts-cellname').find('input[type="text"]').val(service_name);
							search_service_element.find('.vbo-ebusy-extracosts-cellcost').find('input[type="number"]').val(service_cost);
							search_service_element.find('.vbo-ebusy-extracosts-celltax').find('select').val(service_idtax);
							// trigger the event to dismiss the modal
							jQuery('.vbo-modal-overlay-close-times').trigger('click');
						});

						// append result
						search_results.append(result_wrap);
					}
				} catch(err) {
					console.error('could not parse JSON response', err, response);
				}
			},
			function(error) {
				btn_trigger.find('i').attr('class', '<?php echo VikBookingIcons::i('search'); ?>');
				console.error(error);
			}
		);
	});
	servsearch_body.append(servsearch_form);
	servsearch_content.append(servsearch_head).append(servsearch_body);
	servsearch_modal.append(servsearch_content);
	// append modal to body
	if (jQuery('.vbo-modal-overlay-servsearch').length) {
		jQuery('.vbo-modal-overlay-servsearch').remove();
	}
	jQuery('body').append(servsearch_modal);
	// give the focus to the input search button
	servsearch_form.find('input.vbo-servsearch-inp').focus();
	// trigger the search function immediately
	servsearch_form.find('button.vbo-config-btn').trigger('click');
}
</script>

<a class="vbo-booking-basenavuri" href="<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=%d', $xhtml = false); ?>" style="display: none;"></a>

<div class="vbo-modal-sample-servsearch" style="display: none;">
	<div class="vbo-modal-servsearch-form-wrap">
		<div class="vbo-modal-servsearch-input">
			<div class="input-append">
				<input type="text" value="" class="vbo-servsearch-inp" />
				<button type="button" class="btn vbo-config-btn"><?php VikBookingIcons::e('search'); ?> <?php echo JText::translate('VBODASHSEARCHKEYS'); ?></button>
			</div>
		</div>
		<div class="vbo-modal-servsearch-results"></div>
	</div>
</div>

<div class="vbo-bookingdet-topcontainer vbo-editbooking-topcontainer">
	<form name="adminForm" id="adminForm" action="index.php" method="post">
		
		<div class="vbo-info-overlay-block">
			<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
			<div class="vbo-info-overlay-content">
				<h3><?php echo JText::translate('VBOBOOKADDROOM'); ?></h3>
				<div class="vbo-add-room-overlay">
					<div class="vbo-add-room-entry">
						<label for="add-room-id"><?php echo JText::translate('VBDASHROOMNAME'); ?> <span id="add-room-status" style="color: #333333;"><i class="vboicn-checkmark"></i></span></label>
						<select id="add-room-id" onchange="vboAddRoomId(this.value);">
							<option value=""></option>
						<?php
						$some_disabled = isset($all_rooms[(count($all_rooms) - 1)]['avail']) && !$all_rooms[(count($all_rooms) - 1)]['avail'];
						$optgr_enabled = false;
						foreach ($all_rooms as $ar) {
							if ($some_disabled && !$optgr_enabled && $ar['avail']) {
								$optgr_enabled = true;
								?>
							<optgroup label="<?php echo addslashes(JText::translate('VBPVIEWROOMSIX')); ?>">
								<?php
							} elseif ($some_disabled && $optgr_enabled && !$ar['avail']) {
								$optgr_enabled = false;
								?>
							</optgroup>
								<?php
							}
							?>
							<option value="<?php echo $ar['id']; ?>"><?php echo $ar['name']; ?></option>
							<?php
						}
						?>
						</select>
						<input type="hidden" name="add_room_id" id="add_room_id" value="" />
					</div>
					<div class="vbo-add-room-entry">
						<div class="vbo-add-room-entry-inline">
							<label for="add_room_adults"><?php echo JText::translate('VBEDITORDERADULTS'); ?></label>
							<input type="number" min="0" name="add_room_adults" id="add_room_adults" value="1" />
						</div>
						<div class="vbo-add-room-entry-inline">
							<label for="add_room_children"><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></label>
							<input type="number" min="0" name="add_room_children" id="add_room_children" value="0" />
						</div>
					</div>
					<div class="vbo-add-room-entry">
						<div class="vbo-add-room-entry-inline">
							<label for="add_room_fname"><?php echo JText::translate('VBTRAVELERNAME'); ?></label>
							<input type="text" name="add_room_fname" id="add_room_fname" value="<?php echo isset($ordersrooms[0]) && isset($ordersrooms[0]['t_first_name']) ? $this->escape($ordersrooms[0]['t_first_name']) : ''; ?>" size="12" />
						</div>
						<div class="vbo-add-room-entry-inline">
							<label for="add_room_lname"><?php echo JText::translate('VBTRAVELERLNAME'); ?></label>
							<input type="text" name="add_room_lname" id="add_room_lname" value="<?php echo isset($ordersrooms[0]) && isset($ordersrooms[0]['t_last_name']) ? $this->escape($ordersrooms[0]['t_last_name']) : ''; ?>" size="12" />
						</div>
					</div>
					<div class="vbo-add-room-entry">
						<div class="vbo-add-room-entry-inline">
							<label for="add_room_price"><?php echo JText::translate('VBOROOMCUSTRATEPLAN'); ?> (<?php echo $currencysymb; ?>)</label>
							<input type="number" step="any" min="0" name="add_room_price" id="add_room_price" value="" />
						</div>
					<?php
					if (!empty($wiva)) :
					?>
						<div class="vbo-add-room-entry-inline">
							<label>&nbsp;</label>
							<?php echo str_replace('%s', '_add_room', $wiva); ?>
						</div>
					<?php
					endif;
					?>
					</div>
					<div class="vbo-center">
						<br />
						<button type="button" class="btn btn-large btn-success" onclick="vboAddRoomSubmit();"><i class="vboicn-checkmark"></i> <?php echo JText::translate('VBOBOOKADDROOM'); ?></button>
					</div>
				</div>
			</div>
		</div>
		
		<div class="vbo-bookdet-container">
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span>ID</span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $ord['id']; ?></span>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERONE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo date(str_replace("/", $datesep, $df).' H:i', $ord['ts']); ?></span>
				</div>
			</div>
		<?php
		if (count($customer)) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<div class="vbo-customer-info-box">
						<div class="vbo-customer-info-box-name">
							<?php echo (isset($customer['country_img']) ? $customer['country_img'] . ' ' : ''); ?>

							<?php if ($canDo->authorise('core.edit', 'com_vikbooking') && $canDo->authorise('core.vbo.management', 'com_vikbooking')): ?>
								<a href="index.php?option=com_vikbooking&task=editcustomer&cid[]=<?php echo $customer['id']; ?>" target="_blank"><?php echo ltrim($customer['first_name'] . ' ' . $customer['last_name']); ?></a>
							<?php else: ?>
								<?php echo ltrim($customer['first_name'] . ' ' . $customer['last_name']); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERROOMSNUM'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<?php echo $ord['roomsnum']; ?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERFOUR'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $ord['days']; ?></span>
				<?php
				if ($ord['split_stay']) {
					?>
					<span class="hasTooltip" title="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_SPLIT_STAY')); ?>"><?php VikBookingIcons::e('random'); ?></span>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERFIVE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				$short_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
				?>
					<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $ord['checkin']); ?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERSIX'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				$short_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
				?>
					<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $ord['checkout']); ?>
				</div>
			</div>
		<?php
		if (!empty($ord['channel'])) {
			$ota_logo_img = VikBooking::getVcmChannelsLogo($ord['channel']);
			if ($ota_logo_img === false) {
				$ota_logo_img = $otachannel_name;
			} else {
				$ota_logo_img = '<img src="'.$ota_logo_img.'" class="vbo-channelimg-medium"/>';
			}
			?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBPVIEWORDERCHANNEL'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $ota_logo_img; ?></span>
				</div>
			</div>
			<?php
		}
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBSTATUS'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $saystaus; ?></span>
				</div>
			</div>
		</div>

		<?php
		if (!empty($ord['channel']) && !empty($ord['idorderota'])) {
			?>
		<p class="warn"><?php echo JText::translate('VBO_WARN_CM_RES_MOD'); ?></p>
			<?php
		}
		?>

		<div class="vbo-bookingdet-innertop">
			<div class="vbo-bookingdet-commands">
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="if (confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')){document.location.href='index.php?option=com_vikbooking&task=removeorders&goto=<?php echo $ord['id']; ?>&cid[]=<?php echo $ord['id']; ?>';}"><?php VikBookingIcons::e('trash'); ?> <?php echo $ord['status'] == 'cancelled' ? JText::translate('VBO_PURGERM_RESERVATION') : JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
			</div>
			<div class="vbo-bookingdet-tabs">
				<div class="vbo-bookingdet-tab vbo-bookingdet-tab-active" data-vbotab="vbo-tab-details"><?php echo JText::translate('VBMODRES'); ?></div>
			</div>
		</div>

		<div class="vbo-bookingdet-tab-cont" id="vbo-tab-details" style="display: block;">
			<div class="vbo-bookingdet-innercontainer">
				<div class="vbo-bookingdet-customer">
					<div class="vbo-bookingdet-detcont<?php echo $ord['closure'] > 0 ? ' vbo-bookingdet-closure' : ''; ?>">
						<div class="vbo-editbooking-custarea-lbl">
							<?php echo JText::translate('VBEDITORDERTWO'); ?>
						</div>
						<div class="vbo-editbooking-custarea">
							<textarea name="custdata"><?php echo htmlspecialchars($ord['custdata']); ?></textarea>
						</div>
					</div>
					<div class="vbo-bookingdet-detcont">
					<?php
					$canforce = VikRequest::getInt('canforce', 0, 'request');
					if ($canforce) {
						?>
						<div class="vbo-bookingdet-checkdt">
							<label for="forcebooking-on">
								<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBO_FORCE_BOOKDATES'), 'content' => JText::translate('VBO_FORCE_BOOKDATES_HELP'))); ?>
								<?php echo JText::translate('VBO_FORCE_BOOKDATES'); ?>
							</label>
							<div>
								<?php echo $vbo_app->printYesNoButtons('forcebooking', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0); ?>
							</div>
						</div>
						<?php
					}
					?>
						<div class="vbo-bookingdet-checkdt">
							<label for="checkindate"><?php echo JText::translate('VBPEDITBUSYFOUR'); ?></label>
							<?php echo $vbo_app->getCalendar($rit, 'checkindate', 'checkindate', $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
							<span class="vbo-time-selects">
								<select name="checkinh"><?php echo $ritho; ?></select>
								<span class="vbo-time-selects-divider">:</span>
								<select name="checkinm"><?php echo $ritmi; ?></select>
							</span>
						</div>
						<div class="vbo-bookingdet-checkdt">
							<label for="checkoutdate"><?php echo JText::translate('VBPEDITBUSYSIX'); ?></label>
							<?php echo $vbo_app->getCalendar($con, 'checkoutdate', 'checkoutdate', $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
							<span class="vbo-time-selects">
								<select name="checkouth"><?php echo $conho; ?></select>
								<span class="vbo-time-selects-divider">:</span>
								<select name="checkoutm"><?php echo $conmi; ?></select>
							</span>
						</div>
					<?php
					/**
					 * Check if the booking allows to report a stay change to the OTA (for all rooms booked).
					 * 
					 * @since 	  1.16.8 (J) - 1.6.8 (WP)
					 * 
					 * @requires  VCM >= 1.8.24
					 */
					if (!empty($otachannel_name) && class_exists('VCMOtaReporting') && VCMOtaReporting::getInstance($ord)->stayChangeAllowed()) {
						?>
						<div class="vbo-bookingdet-checkdt vbo-editbook-report-stay-change-ota">
							<label for="ota_stay_change_all-on"><?php echo JText::sprintf('VBO_REPORT_STAY_CHANGE_OTA', $otachannel_name); ?></label>
							<div>
								<?php echo $vbo_app->printYesNoButtons('ota_stay_change_all', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, "jQuery('#vbo-report-stay-change-ota-allr').toggle();"); ?>
							</div>
							<p id="vbo-report-stay-change-ota-allr" class="info" style="display: none;"><?php echo JText::sprintf('VBO_REPORT_STAY_CHANGE_OTA_ALLR_HELP', $otachannel_name); ?></p>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<div class="vbo-editbooking-summary">
			<?php
			if ($ord && (!empty($ordersrooms[0]['idtar']) || $is_package || $is_cust_cost)) {
				// order from front end or correctly saved - start
				$proceedtars = true;
				$rooms = array();
				$tars = array();
				$arrpeople = array();
				foreach ($ordersrooms as $kor => $or) {
					$num = $kor + 1;
					$rooms[$num] = $or;
					$arrpeople[$num]['adults'] = $or['adults'];
					$arrpeople[$num]['children'] = $or['children'];

					if ($is_package) {
						continue;
					}

					// default values to be considered
					$room_nights   = (int)$ord['days'];
					$room_checkin  = $ord['checkin'];
					$room_checkout = $ord['checkout'];
					if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
						// use appropriate values for split stays
						$room_nights   = $room_stay_dates[$kor]['nights'];
						$room_checkin  = $room_stay_dates[$kor]['checkin'];
						$room_checkout = $room_stay_dates[$kor]['checkout'];
					}

					// seek for rates
					$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `days`=" . $room_nights . " AND `idroom`=" . (int)$or['idroom'] . " ORDER BY `#__vikbooking_dispcost`.`cost` ASC;";
					$dbo->setQuery($q);
					$tar = $dbo->loadAssocList();
					if (!$tar) {
						$proceedtars = false;
						break;
					}
					$tar = VikBooking::applySeasonsRoom($tar, $room_checkin, $room_checkout);

					// apply OBP rules
					$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $or, $or['adults']);

					// push tariffs for this room
					$tars[$num] = $tar;
				}
				if ($proceedtars) {
					?>
					<input type="hidden" name="areprices" value="yes"/>
					<input type="hidden" name="rm_room_oid" id="rm_room_oid" value="" />
					<div class="vbo-editbooking-tbl">
					<?php
					// rooms loop start
					foreach ($ordersrooms as $kor => $or) {
						$num = $kor + 1;

						// default values to be considered
						$room_nights   = (int)$ord['days'];
						$room_checkin  = $ord['checkin'];
						$room_checkout = $ord['checkout'];
						if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
							// use appropriate values for split stays
							$room_nights   = $room_stay_dates[$kor]['nights'];
							$room_checkin  = $room_stay_dates[$kor]['checkin'];
							$room_checkout = $room_stay_dates[$kor]['checkout'];
						}
						?>
						<div class="vbo-bookingdet-summary-room vbo-editbooking-summary-room">
							<div class="vbo-editbooking-summary-room-head">
								<div class="vbo-bookingdet-summary-roomnum"><?php VikBookingIcons::e('bed'); ?> <?php echo $or['name']; ?></div>
							<?php
							if ($ord['roomsnum'] > 1) {
								?>
								<div class="vbo-editbooking-room-remove pro-feature">
									<button type="button" class="btn btn-danger" onclick="vboConfirmRmRoom('<?php echo $or['id']; ?>');"><?php VikBookingIcons::e('times-circle'); ?> <?php echo JText::translate('VBOREMOVEROOM'); ?></button>
								</div>
								<?php
							}
							$switch_code = '';
							if ($switching) {
								$switch_code = sprintf($switcher, 'switch_'.$or['id'], $or['id'], $or['idroom'], $or['id']);
								?>
								<div class="vbo-editbooking-room-switch pro-feature">
									<?php echo $switch_code; ?>
								</div>
								<?php
							}
							?>
								<div class="vbo-bookingdet-summary-roomguests">
									<?php VikBookingIcons::e('male'); ?>
									<div class="vbo-bookingdet-summary-roomadults">
										<span><?php echo JText::translate('VBEDITORDERADULTS'); ?>:</span> <?php echo $arrpeople[$num]['adults']; ?>
									</div>
								<?php
								if ($arrpeople[$num]['children'] > 0) {
									?>
									<div class="vbo-bookingdet-summary-roomchildren">
										<span><?php echo JText::translate('VBEDITORDERCHILDREN'); ?>:</span> <?php echo $arrpeople[$num]['children']; ?>
									</div>
									<?php
								}
								?>
								</div>
								<?php
								if (!empty($arrpeople[$num]['t_first_name'])) {
								?>
								<div class="vbo-bookingdet-summary-guestname">
									<span><?php echo $arrpeople[$num]['t_first_name'].' '.$arrpeople[$num]['t_last_name']; ?></span>
								</div>
								<?php
								}
								?>
							</div>
						<?php
						// split stay
						if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
							// print split stay information for this room
							$room_stay_nights = $room_stay_dates[$kor]['nights'];
							// calendar default values
							$cal_def_checkin  = date($df, $room_stay_dates[$kor]['checkin']);
							$cal_def_checkout = date($df, $room_stay_dates[$kor]['checkout']);
							$js_cal_def_vals[] = [
								'id' => 'room-splitstay-checkin' . $num,
								'value' => $cal_def_checkin,
							];
							$js_cal_def_vals[] = [
								'id' => 'room-splitstay-checkout' . $num,
								'value' => $cal_def_checkout,
							];
							?>
							<input type="hidden" name="split_stay_data[<?php echo $kor; ?>][idroom]" value="<?php echo $room_stay_dates[$kor]['idroom']; ?>" />
							<input type="hidden" name="split_stay_data[<?php echo $kor; ?>][idbusy]" value="<?php echo !empty($room_stay_dates[$kor]['id']) ? $room_stay_dates[$kor]['id'] : ''; ?>" />
							<div class="vbo-editbooking-room-splitstay">
								<h4>
									<span class="vbo-editbooking-room-splitstay-first"><?php VikBookingIcons::e('random'); ?> <?php echo JText::translate('VBO_SPLIT_STAY'); ?></span>
									<span class="vbo-editbooking-room-splitstay-second"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
								</h4>
								<div class="vbo-editbooking-room-traveler-guestsinfo vbo-editbooking-room-splitstay-details">
									<div class="vbo-editbooking-room-splitstay-date vbo-editbooking-room-splitstay-date-in">
										<label for="room-splitstay-checkin<?php echo $num; ?>"><?php echo JText::translate('VBPEDITBUSYFOUR'); ?></label>
										<?php echo $vbo_app->getCalendar($cal_def_checkin, 'split_stay_data[' . $kor . '][checkin]', 'room-splitstay-checkin' . $num, $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
									</div>
									<div class="vbo-editbooking-room-splitstay-date vbo-editbooking-room-splitstay-date-out">
										<label for="room-splitstay-checkout<?php echo $num; ?>"><?php echo JText::translate('VBPEDITBUSYSIX'); ?></label>
										<?php echo $vbo_app->getCalendar($cal_def_checkout, 'split_stay_data[' . $kor . '][checkout]', 'room-splitstay-checkout' . $num, $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
									</div>
								</div>
							</div>
							<?php
						}

						if (!$ord['split_stay'] && !$ord['closure'] && $ord['roomsnum'] > 1 && $ord['days'] > 1 && $ord['status'] == 'confirmed') {
							/**
							 * We allow to modify the nights of stay for each room in the limits of the booking dates.
							 * This function will only affect the availability, not the calculation of the rate plans/options.
							 * It is meant to simply free up rooms on dates within the booking check-in and check-out dates.
							 * 
							 * @since 	1.16.0 (J) - 1.6.0 (WP)
							 */
							$room_now_checkin  = $ord['checkin'];
							$room_now_checkout = $ord['checkout'];
							$room_now_nights   = $ord['days'];
							if (isset($room_stay_records[$kor]) && $room_stay_records[$kor]['idroom'] == $or['idroom']) {
								$room_now_checkin  = $room_stay_records[$kor]['checkin'];
								$room_now_checkout = $room_stay_records[$kor]['checkout'];
								$room_now_nights   = $av_helper->countNightsOfStay($room_now_checkin, $room_now_checkout);
							}
							?>
							<div class="vbo-editbooking-room-nights-info">
								<div class="vbo-editbooking-room-nights-info-top">
									<h4>
										<span class="vbo-editbooking-room-nights-txt"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_now_nights . ' ' . ($room_now_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
									</h4>
									<div class="vbo-editbooking-room-nights-modify">
										<label for="room_modify_dates<?php echo $kor; ?>-on"><?php echo JText::translate('VBO_MODIFY_DATES'); ?></label>
										<span><?php echo $vbo_app->printYesNoButtons('room_modify_dates' . $kor, JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, 'vboEditbusyModifyRoomDates(this.checked, ' . $kor . ')'); ?></span>
									</div>
								</div>
								<div class="vbo-editbooking-room-traveler-guestsinfo vbo-editbooking-room-nights-modify-details" data-roomind="<?php echo $kor; ?>" style="display: none;">
									<div class="vbo-editbooking-room-modify-date vbo-editbooking-room-modify-date-in">
										<label for="room-modify-checkin<?php echo $num; ?>"><?php echo JText::translate('VBPEDITBUSYFOUR'); ?></label>
										<div class="vbo-input-calendar-box">
											<input type="text" name="<?php echo 'room_modify_dates[' . $kor . '][checkin]'; ?>" class="vbo-editbusy-room-modify-dates-checkin" value="" data-current-value="<?php echo date($df, $room_now_checkin); ?>" autocomplete="off" /><?php VikBookingIcons::e('calendar', 'vbo-caltrigger'); ?>
										</div>
									</div>
									<div class="vbo-editbooking-room-modify-date vbo-editbooking-room-modify-date-out">
										<label for="room-modify-checkout<?php echo $num; ?>"><?php echo JText::translate('VBPEDITBUSYSIX'); ?></label>
										<div class="vbo-input-calendar-box">
											<input type="text" name="<?php echo 'room_modify_dates[' . $kor . '][checkout]'; ?>" class="vbo-editbusy-room-modify-dates-checkout" value="" data-current-value="<?php echo date($df, $room_now_checkout); ?>" autocomplete="off" /><?php VikBookingIcons::e('calendar', 'vbo-caltrigger'); ?>
										</div>
									</div>
									<div class="vbo-editbooking-room-modify-date vbo-editbooking-room-modify-date-help">
										<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBO_MODIFY_DATES'), 'content' => JText::translate('VBO_MODIFY_DATES_HELP'))); ?>
									</div>
								<?php
								/**
								 * Check if the booking allows to report a stay change to the OTA (for the current room booked).
								 * 
								 * @since 	  1.16.8 (J) - 1.6.8 (WP)
								 * 
								 * @requires  VCM >= 1.8.24
								 */
								if (!empty($otachannel_name) && class_exists('VCMOtaReporting') && VCMOtaReporting::getInstance($ord)->stayChangeAllowed()) {
									?>
									<div class="vbo-editbooking-room-modify-date vbo-editbook-report-stay-change-ota">
										<label for="ota_stay_change_room_<?php echo $kor; ?>-on"><?php echo JText::sprintf('VBO_REPORT_STAY_CHANGE_OTA', $otachannel_name); ?></label>
										<div>
											<?php echo $vbo_app->printYesNoButtons('ota_stay_change_room_' . $kor, JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, "jQuery('#vbo-report-stay-change-ota-oner{$kor}').toggle();"); ?>
										</div>
										<p id="vbo-report-stay-change-ota-oner<?php echo $kor; ?>" class="info" style="display: none;"><?php echo JText::sprintf('VBO_REPORT_STAY_CHANGE_OTA_ONER_HELP', $otachannel_name); ?></p>
									</div>
									<?php
								}
								?>
								</div>
							</div>
							<?php
						}

						$from_a = $or['fromadult'];
						$from_a = $from_a > $or['adults'] ? $or['adults'] : $from_a;
						$to_a = $or['toadult'];
						$to_a = $to_a < $or['adults'] ? $or['adults'] : $to_a;
						$from_c = $or['fromchild'];
						$from_c = $from_c > $or['children'] ? $or['children'] : $from_c;
						$to_c = $or['tochild'];
						$to_c = $to_c < $or['children'] ? $or['children'] : $to_c;
						$adults_opts = '';
						$children_opts = '';
						for ($z = $from_a; $z <= $to_a; $z++) {
							$adults_opts .= '<option value="'.$z.'"'.($z == $or['adults'] ? ' selected="selected"' : '').'>'.$z.'</option>';
						}
						for ($z = $from_c; $z <= $to_c; $z++) {
							$children_opts .= '<option value="'.$z.'"'.($z == $or['children'] ? ' selected="selected"' : '').'>'.$z.'</option>';
						}
						?>
							<div class="vbo-editbooking-room-traveler">
								<h4><?php echo JText::translate('VBPEDITBUSYTRAVELERINFO'); ?></h4>
								<div class="vbo-editbooking-room-traveler-guestsinfo">
									<div class="vbo-editbooking-room-traveler-name">
										<label for="t_first_name<?php echo $num; ?>"><?php echo JText::translate('VBTRAVELERNAME'); ?></label>
										<input type="text" name="t_first_name<?php echo $num; ?>" id="t_first_name<?php echo $num; ?>" value="<?php echo $this->escape($or['t_first_name']); ?>" size="20" />
									</div>
									<div class="vbo-editbooking-room-traveler-name">
										<label for="t_last_name<?php echo $num; ?>"><?php echo JText::translate('VBTRAVELERLNAME'); ?></label>
										<input type="text" name="t_last_name<?php echo $num; ?>" id="t_last_name<?php echo $num; ?>" value="<?php echo $this->escape($or['t_last_name']); ?>" size="20" />
									</div>
									<div class="vbo-editbooking-room-traveler-guestnum">
										<label for="adults<?php echo $num; ?>"><?php echo JText::translate('VBMAILADULTS'); ?></label>
										<select name="adults<?php echo $num; ?>" id="adults<?php echo $num; ?>">
											<?php echo $adults_opts; ?>
										</select>
									</div>
									<div class="vbo-editbooking-room-traveler-guestnum">
										<label for="children<?php echo $num; ?>"><?php echo JText::translate('VBMAILCHILDREN'); ?></label>
										<select name="children<?php echo $num; ?>" id="children<?php echo $num; ?>">
											<?php echo $children_opts; ?>
										</select>
									</div>
									<div class="vbo-editbooking-room-traveler-guestnum">
										<label for="pets<?php echo $num; ?>"><?php VikBookingIcons::e('dog'); ?> <?php echo JText::translate('VBO_PETS'); ?></label>
										<input type="number" class="vbo-small-input" name="pets<?php echo $num; ?>" id="pets<?php echo $num; ?>" value="<?php echo (int)$or['pets']; ?>" min="0" max="99" />
									</div>
								</div>
							</div>
							<div class="vbo-editbooking-room-pricetypes">
								<h4><?php echo JText::translate('VBPEDITBUSYSEVEN'); ?></h4>
								<div class="vbo-editbooking-room-pricetypes-wrap">
							<?php
							$is_cust_cost = !empty($or['cust_cost']) && $or['cust_cost'] > 0.00 ? true : false;
							$active_rplan_id = 0;
							if ($is_package || $is_cust_cost) {
								if ($is_package) {
									$pkg_name = (!empty($or['pkg_name']) ? $or['pkg_name'] : JText::translate('VBOROOMCUSTRATEPLAN'));
									?>
									<div class="vbo-editbooking-room-pricetype vbo-editbooking-room-pricetype-active">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="pid<?php echo $num.$or['id']; ?>"><?php echo $pkg_name; ?></label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb." ".VikBooking::numberFormat($or['cust_cost']); ?>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input type="radio" name="pkgid<?php echo $num; ?>" id="pid<?php echo $num.$or['id']; ?>" value="<?php echo $or['pkg_id']; ?>" checked="checked" />
										</div>
									</div>
									<?php
								} else {
									//custom rate
									?>
									<div class="vbo-editbooking-room-pricetype vbo-editbooking-room-pricetype-active">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="pid<?php echo $num.$or['id']; ?>">
												<?php echo JText::translate('VBOROOMCUSTRATEPLAN').(!empty($or['otarplan']) ? ' ('.ucwords($or['otarplan']).')' : ''); ?>
											</label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb; ?> <input type="number" step="any" name="cust_cost<?php echo $num; ?>" value="<?php echo $or['cust_cost']; ?>" size="4" onchange="if (this.value.length) {document.getElementById('pid<?php echo $num.$or['id']; ?>').checked = true; jQuery('#pid<?php echo $num.$or['id']; ?>').trigger('change');}"/>
												<div class="vbo-editbooking-room-pricetype-seltax" id="tax<?php echo $num; ?>" style="display: block;">
													<?php echo (!empty($wiva) ? str_replace('%s', $num, str_replace('data-aliqid="'.(int)$or['cust_idiva'].'"', 'selected="selected"', $wiva)) : ''); ?>
												</div>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input class="vbo-pricetype-radio" type="radio" name="priceid<?php echo $num; ?>" id="pid<?php echo $num.$or['id']; ?>" value="" checked="checked" />
										</div>
									</div>
									<?php
									//print the standard rates anyway
									foreach ($tars[$num] as $k => $t) {
									?>
									<div class="vbo-editbooking-room-pricetype">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="pid<?php echo $num.$t['idprice']; ?>"><?php echo VikBooking::getPriceName($t['idprice']).(strlen((string)$t['attrdata']) ? " - ".VikBooking::getPriceAttr($t['idprice']).": ".$t['attrdata'] : ""); ?></label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb." ".VikBooking::numberFormat($t['cost']); ?>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input class="vbo-pricetype-radio" type="radio" name="priceid<?php echo $num; ?>" id="pid<?php echo $num.$t['idprice']; ?>" value="<?php echo $t['idprice']; ?>" />
										</div>
									</div>
									<?php
									}
								}
							} else {
								$sel_rate_changed = false;
								foreach ($tars[$num] as $k => $t) {
									$sel_rate_changed = $t['id'] == $or['idtar'] && !empty($or['room_cost']) ? $or['room_cost'] : $sel_rate_changed;
									$format_cost = VikBooking::numberFormat($t['cost']);
									if ($t['id'] == $or['idtar'] && !empty($or['room_cost'])) {
										$active_rplan_id = $t['idprice'];
									}
									?>
									<div class="vbo-editbooking-room-pricetype<?php echo $t['id'] == $or['idtar'] ? ' vbo-editbooking-room-pricetype-active' : ''; ?>">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="pid<?php echo $num.$t['idprice']; ?>"><?php echo VikBooking::getPriceName($t['idprice']).(strlen((string)$t['attrdata']) ? " - ".VikBooking::getPriceAttr($t['idprice']).": ".$t['attrdata'] : ""); ?></label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb." ".$format_cost; ?>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input class="vbo-pricetype-radio" type="radio" name="priceid<?php echo $num; ?>" id="pid<?php echo $num.$t['idprice']; ?>" value="<?php echo $t['idprice']; ?>"<?php echo ($t['id'] == $or['idtar'] ? " checked=\"checked\"" : ""); ?>/>
										</div>
									<?php
									if ($t['id'] == $or['idtar'] && !empty($or['room_cost']) && VikBooking::numberFormat($or['room_cost']) != $format_cost) {
										/**
										 * The current price is different from the price paid at the time of booking.
										 * Display a checkbox with the information of the previous price to keep it.
										 * 
										 * @since 	1.3.0
										 */
										?>
										<div class="vbo-editbooking-room-pricetype-older">
											<div class="vbo-editbooking-room-pricetype-older-inner">
												<label for="olderpid<?php echo $num.$t['idprice']; ?>"><?php echo JText::translate('VBOBOOKEDATPRICE') . ' ' . $vbo_app->createPopover(array('title' => JText::translate('VBOBOOKEDATPRICE'), 'content' => JText::translate('VBOBOOKEDATPRICEHELP'))); ?></label>
												<div class="vbo-editbooking-room-pricetype-cost">
													<?php echo $currencysymb." ".VikBooking::numberFormat($or['room_cost']); ?>
												</div>
											</div>
											<div class="vbo-editbooking-room-pricetype-check-older">
												<input type="checkbox" name="olderpriceid<?php echo $num; ?>" id="olderpid<?php echo $num.$t['idprice']; ?>" value="<?php echo $t['idprice'] . ':' . $or['room_cost']; ?>" checked="checked"/>
											</div>
										</div>
										<?php
									}
									?>
									</div>
									<?php
								}
								//print the set custom rate anyway
								?>
									<div class="vbo-editbooking-room-pricetype">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="cust_cost<?php echo $num; ?>" class="vbo-custrate-lbl-add"><?php echo JText::translate('VBOROOMCUSTRATEPLANADD'); ?></label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb; ?> <input type="number" step="any" name="cust_cost<?php echo $num; ?>" id="cust_cost<?php echo $num; ?>" value="" placeholder="<?php echo VikBooking::numberFormat(($sel_rate_changed !== false ? $sel_rate_changed : 0)); ?>" size="4" onchange="if (this.value.length) {document.getElementById('priceid<?php echo $num; ?>').checked = true; jQuery('#priceid<?php echo $num; ?>').trigger('change');document.getElementById('tax<?php echo $num; ?>').style.display = 'block';}" />
												<div class="vbo-editbooking-room-pricetype-seltax" id="tax<?php echo $num; ?>" style="display: none;">
													<?php echo (!empty($wiva) ? str_replace('%s', $num, $wiva) : ''); ?>
												</div>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input class="vbo-pricetype-radio" type="radio" name="priceid<?php echo $num; ?>" id="priceid<?php echo $num; ?>" value="" onclick="document.getElementById('tax<?php echo $num; ?>').style.display = 'block';" />
										</div>
									</div>
								<?php
							}
							?>
								</div>
							</div>
						<?php
						/**
						 * Meal plans included in the room rate.
						 * 
						 * @since 	1.16.1 (J) - 1.6.1 (WP)
						 */
						$meal_plan_manager = VBOMealplanManager::getInstance();
						?>
							<div class="vbo-editbooking-room-services vbo-editbooking-room-meal-plans">
								<h4><?php echo JText::translate('VBO_MEAL_PLANS_INCL'); ?></h4>
								<div class="vbo-editbooking-room-services-wrap vbo-editbooking-room-meal-plans-wrap">
								<?php
								foreach ($meal_plan_manager->getPlans() as $meal_enum => $meal_name) {
									if (!empty($or['meals'])) {
										// check if meal is included in room-reservation record
										$meal_included = $meal_plan_manager->roomRateMealIncluded($or, $meal_enum);
									} else {
										// check if meal is included in the selected rate plan
										$meal_included = $active_rplan_id ? $meal_plan_manager->ratePlanMealIncluded($active_rplan_id, $meal_enum) : false;
									}
									if (!$meal_included && empty($or['meals']) && !empty($ord['idorderota']) && !empty($ord['channel']) && !empty($ord['custdata'])) {
										// check if meal is included by using the raw customer data or OTA reservation and room
										$meal_included = $meal_plan_manager->otaDataMealIncluded($ord, $or, $meal_enum);
									}
									?>
									<div class="vbo-editbooking-room-service vbo-editbooking-room-meal-plan">
										<div class="vbo-editbooking-room-service-inner vbo-editbooking-room-meal-plan-inner">
											<label for="mealplan-<?php echo $num; ?>-<?php echo $meal_enum; ?>"><?php echo $meal_name; ?></label>
										</div>
										<div class="vbo-editbooking-room-service-check vbo-editbooking-room-meal-plan-check">
											<input type="checkbox" name="mealplan<?php echo $num; ?>[]" id="mealplan-<?php echo $num; ?>-<?php echo $meal_enum; ?>" value="<?php echo $meal_enum; ?>" <?php echo $meal_included ? 'checked="checked"' : ''; ?>/>
										</div>
									</div>
									<?php
								}
								?>
								</div>
							</div>
						<?php

						//Room Options Start
						$optionals = empty($or['idopt']) ? '' : VikBooking::getRoomOptionals($or['idopt']);
						if ($optionals) {
							// apply filters to options
							VikBooking::filterOptionalsByDate($optionals, $room_checkin, $room_checkout);
							VikBooking::filterOptionalsByParty($optionals, $or['adults'], $or['children']);
						}
						$arropt = array();
						if ($optionals) {
						?>
							<div class="vbo-editbooking-room-services vbo-editbooking-room-options pro-feature">
								<h4><?php echo JText::translate('VBPEDITBUSYEIGHT'); ?></h4>
								<div class="vbo-editbooking-room-services-wrap vbo-editbooking-room-options-wrap">
								<?php
								list($optionals, $ageintervals) = VikBooking::loadOptionAgeIntervals($optionals, $or['adults'], $or['children']);
								if ($ageintervals) {
									if ($optionals) {
										$ageintervals = array(0 => $ageintervals);
										$optionals = array_merge($ageintervals, $optionals);
									} else {
										$optionals = array(0 => $ageintervals);
									}
								}
								if (!empty($or['optionals'])) {
									$haveopt = explode(";", $or['optionals']);
									foreach ($haveopt as $ho) {
										if (!empty($ho)) {
											$havetwo = explode(":", $ho);
											if (strstr($havetwo[1], '-') != false) {
												$arropt[$havetwo[0]][] = $havetwo[1];
											} else {
												$arropt[$havetwo[0]] = $havetwo[1];
											}
										}
									}
								} else {
									$arropt[] = "";
								}
								foreach ($optionals as $k => $o) {
									$oval = "";
									if (intval($o['hmany']) == 1) {
										if (array_key_exists($o['id'], $arropt)) {
											$oval = $arropt[$o['id']];
										}
									} else {
										if (array_key_exists($o['id'], $arropt) && !is_array($arropt[$o['id']])) {
											$oval = " checked=\"checked\"";
										}
									}
									if (!empty($o['ageintervals'])) {
										if ($or['children'] > 0) {
											for ($ch = 1; $ch <= $or['children']; $ch++) {
												$chageselect = '<select name="optid'.$num.$o['id'].'[]">'."\n".'<option value="">  </option>'."\n";
												/**
												 * Age intervals may be overridden per child number.
												 * 
												 * @since 	1.13.5
												 */
												$optageovrct = VikBooking::getOptionIntervalChildOverrides($o, $or['adults'], $or['children']);
												$intervals = explode(';;', (isset($optageovrct['ageintervals_child' . $ch]) ? $optageovrct['ageintervals_child' . $ch] : $o['ageintervals']));
												//
												foreach ($intervals as $kintv => $intv) {
													if (empty($intv)) {
														continue;
													}
													$intvparts = explode('_', $intv);
													$intvparts[2] = intval($o['perday']) == 1 ? ($intvparts[2] * $room_nights) : $intvparts[2];
													if (array_key_exists(3, $intvparts) && strpos($intvparts[3], '%') !== false) {
														$pricestr = floatval($intvparts[2]) >= 0 ? '+ '.VikBooking::numberFormat($intvparts[2]) : '- '.VikBooking::numberFormat($intvparts[2]);
													} else {
														$pricestr = floatval($intvparts[2]) >= 0 ? '+ '.VikBooking::numberFormat($intvparts[2]) : '- '.VikBooking::numberFormat($intvparts[2]);
													}
													$selstatus = '';
													if (isset($arropt[$o['id']]) && is_array($arropt[$o['id']])) {
														$ageparts = explode('-', $arropt[$o['id']][($ch - 1)]);
														if ($kintv == ($ageparts[1] - 1)) {
															$selstatus = ' selected="selected"';
														}
													}
													$chageselect .= '<option value="'.($kintv + 1).'"'.$selstatus.'>'.$intvparts[0].' - '.$intvparts[1].' ('.$pricestr.' '.(array_key_exists(3, $intvparts) && strpos($intvparts[3], '%') !== false ? '%' : $currencysymb).')'.'</option>'."\n";
												}
												$chageselect .= '</select>'."\n";
												?>
									<div class="vbo-editbooking-room-service vbo-editbooking-room-option vbo-editbooking-room-option-childage">
										<div class="vbo-editbooking-room-service-inner vbo-editbooking-room-option-inner">
											<label for="optid<?php echo $num.$o['id'].$ch; ?>"><?php echo JText::translate('VBMAILCHILD').' #'.$ch; ?></label>
											<div class="vbo-editbooking-room-service-select vbo-editbooking-room-option-select">
												<?php echo $chageselect; ?>
											</div>
										</div>
									</div>
												<?php
											}
										}
									} else {
										$optquancheckb = 1;
										$forcedquan = 1;
										$forceperday = false;
										$forceperchild = false;
										if (intval($o['forcesel']) == 1 && strlen((string)$o['forceval'])) {
											$forceparts = explode("-", $o['forceval']);
											$forcedquan = intval($forceparts[0]);
											$forceperday = intval($forceparts[1]) == 1 ? true : false;
											$forceperchild = intval($forceparts[2]) == 1 ? true : false;
											$optquancheckb = $forcedquan;
											$optquancheckb = $forceperchild === true && array_key_exists($num, $arrpeople) && array_key_exists('children', $arrpeople[$num]) ? ($optquancheckb * $arrpeople[$num]['children']) : $optquancheckb;
										}
										if (intval($o['perday']) == 1) {
											$thisoptcost = $o['cost'] * $room_nights;
										} else {
											$thisoptcost = $o['cost'];
										}
										if ($o['maxprice'] > 0 && $thisoptcost > $o['maxprice']) {
											$thisoptcost = $o['maxprice'];
										}
										$thisoptcost = $thisoptcost * $optquancheckb;
										if (intval($o['perperson']) == 1) {
											$thisoptcost = $thisoptcost * $arrpeople[$num]['adults'];
										}
										if ($o['pcentroom']) {
											// it's a percent value, so we do not multiply anything
											$thisoptcost = $o['cost'];
										}
										?>
									<div class="vbo-editbooking-room-service vbo-editbooking-room-option">
										<div class="vbo-editbooking-room-service-inner vbo-editbooking-room-option-inner">
											<label for="optid<?php echo $num.$o['id']; ?>"><?php echo $o['name']; ?></label>
											<div class="vbo-editbooking-room-service-price vbo-editbooking-room-option-price">
												<?php echo (int)$o['pcentroom'] ? '' : $currencysymb.' '; ?><?php echo VikBooking::numberFormat($thisoptcost); ?><?php echo (int)$o['pcentroom'] ? '%' : ''; ?>
											</div>
										</div>
										<div class="vbo-editbooking-room-service-check vbo-editbooking-room-option-check">
											<?php echo (intval($o['hmany'])==1 ? "<input type=\"number\" name=\"optid".$num.$o['id']."\" id=\"optid".$num.$o['id']."\" value=\"".$oval."\" min=\"0\" />" : "<input type=\"checkbox\" name=\"optid".$num.$o['id']."\" id=\"optid".$num.$o['id']."\" value=\"".$optquancheckb."\"".$oval."/>"); ?>
										</div>
									</div>
										<?php
									}
								}
								?>
								</div>
							</div>
						<?php
						}
						//Room Options End
						//custom extra services for each room Start
						?>
							<div class="vbo-editbooking-room-extracosts pro-feature" id="vbo-ebusy-extracosts-<?php echo $num; ?>">
								<h4>
									<?php echo JText::translate('VBPEDITBUSYEXTRACOSTS'); ?> 
									<button class="btn vbo-ebusy-addextracost" type="button" onclick="vboAddExtraCost('<?php echo $num; ?>');"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBPEDITBUSYADDEXTRAC'); ?></button>
								</h4>
								<div class="vbo-editbooking-room-extracosts-wrap">
							<?php
							if (!empty($or['extracosts'])) {
								$cur_extra_costs = json_decode($or['extracosts'], true);
								foreach ($cur_extra_costs as $eck => $ecv) {
									$ec_taxopts = '';
									foreach ($ivas as $iv) {
										$ec_taxopts .= "<option value=\"".$iv['id']."\"".(!empty($ecv['idtax']) && $ecv['idtax'] == $iv['id'] ? ' selected="selected"' : '').">".(empty($iv['name']) ? $iv['aliq']."%" : $iv['name']." - ".$iv['aliq']."%")."</option>\n";
									}
									?>
									<div class="vbo-editbooking-room-extracost">
										<div class="vbo-ebusy-extracosts-cellname">
											<input type="text" name="extracn[<?php echo $num; ?>][]" value="<?php echo JHtml::fetch('esc_attr', $ecv['name']); ?>" placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBPEDITBUSYEXTRACNAME')); ?>" size="25" />
											<input type="hidden" name="extractype[<?php echo $num; ?>][]" value="<?php echo isset($ecv['type']) ? JHtml::fetch('esc_attr', $ecv['type']) : ''; ?>" />
											<input type="hidden" name="extracfk[<?php echo $num; ?>][]" value="<?php echo isset($ecv['fk']) ? (string)$ecv['fk'] : ''; ?>" />
											<input type="hidden" name="extracdata[<?php echo $num; ?>][]" value="<?php echo isset($ecv['data']) ? JHtml::fetch('esc_attr', json_encode($ecv['data'])) : ''; ?>" />
										</div>
										<div class="vbo-ebusy-extracosts-cellcost">
											<span class="vbo-ebusy-extracosts-currency"><?php echo $currencysymb; ?></span> 
											<input type="number" step="any" name="extracc[<?php echo $num; ?>][]" value="<?php echo addslashes($ecv['cost']); ?>" size="5" />
										</div>
										<div class="vbo-ebusy-extracosts-celltax">
											<select name="extractx[<?php echo $num; ?>][]">
												<option value=""><?php echo JText::translate('VBNEWOPTFOUR'); ?></option>
												<?php echo $ec_taxopts; ?>
											</select>
										</div>
										<div class="vbo-ebusy-extracosts-cellrm">
											<button class="btn btn-danger" type="button" onclick="vboRemoveExtraCost(this);">&times;</button>
										</div>
									</div>
									<?php
								}
							}
							?>
								</div>
							</div>
						<?php
						//custom extra services for each room End
						?>
						</div>
						<?php
					}
					//Rooms Loop End
					?>
						<div class="vbo-bookingdet-summary-room vbo-editbooking-summary-room vbo-editbooking-summary-totpaid">
							<div class="vbo-editbooking-summary-room-head">
								<div class="vbo-editbooking-addroom pro-feature">
									<button class="btn btn-success" type="button" id="vbo-add-room"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBOBOOKADDROOM'); ?></button>
								</div>
								<div class="vbo-editbooking-totpaid">
									<label for="totpaid"><?php echo JText::translate('VBPEDITBUSYTOTPAID'); ?></label>
									<?php echo $currencysymb; ?> <input type="number" min="0" step="any" id="totpaid" name="totpaid" value="<?php echo $ord['totpaid']; ?>"/>
								</div>
								<div class="vbo-editbooking-totpaid vbo-editbooking-totrefund">
									<label for="refund"><?php echo JText::translate('VBO_AMOUNT_REFUNDED'); ?></label>
									<?php echo $currencysymb; ?> <input type="number" min="0" step="any" id="refund" name="refund" value="<?php echo $ord['refund']; ?>"/>
								</div>
							</div>
						</div>
					</div>
					<?php
				} else {
					?>
					<p class="err"><?php echo JText::translate('VBPEDITBUSYERRNOFARES'); ?></p>
					<?php
				}
				//order from front end or correctly saved - end
			} elseif ($ord && empty($ordersrooms[0]['idtar'])) {
				//order is a quick reservation from administrator - start
				$proceedtars = true;
				$rooms = array();
				$tars = array();
				$arrpeople = array();
				foreach ($ordersrooms as $kor => $or) {
					$num = $kor + 1;
					$rooms[$num] = $or;
					$arrpeople[$num]['adults'] = $or['adults'];
					$arrpeople[$num]['children'] = $or['children'];

					// default values to be considered
					$room_nights   = (int)$ord['days'];
					$room_checkin  = $ord['checkin'];
					$room_checkout = $ord['checkout'];
					if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
						// use appropriate values for split stays
						$room_nights   = $room_stay_dates[$kor]['nights'];
						$room_checkin  = $room_stay_dates[$kor]['checkin'];
						$room_checkout = $room_stay_dates[$kor]['checkout'];
					}

					// seek for rates
					$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `days`=" . $room_nights . " AND `idroom`=" . (int)$or['idroom'] . " ORDER BY `#__vikbooking_dispcost`.`cost` ASC;";
					$dbo->setQuery($q);
					$tar = $dbo->loadAssocList();
					if (!$tar) {
						$proceedtars = false;
						break;
					}

					$tar = VikBooking::applySeasonsRoom($tar, $room_checkin, $room_checkout);

					// apply OBP rules
					$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $or, $or['adults']);

					// push tariffs for this room
					$tars[$num] = $tar;
				}
				if ($proceedtars) {
					?>
					<input type="hidden" name="areprices" value="quick"/>
					<div class="vbo-editbooking-tbl">
					<?php
					//Rooms Loop Start
					foreach ($ordersrooms as $kor => $or) {
						$num = $kor + 1;

						// default values to be considered
						$room_nights   = (int)$ord['days'];
						$room_checkin  = $ord['checkin'];
						$room_checkout = $ord['checkout'];
						if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
							// use appropriate values for split stays
							$room_nights   = $room_stay_dates[$kor]['nights'];
							$room_checkin  = $room_stay_dates[$kor]['checkin'];
							$room_checkout = $room_stay_dates[$kor]['checkout'];
						}
						?>
						<div class="vbo-bookingdet-summary-room vbo-editbooking-summary-room">
							<div class="vbo-editbooking-summary-room-head">
								<div class="vbo-bookingdet-summary-roomnum"><?php VikBookingIcons::e('bed'); ?> <?php echo $or['name']; ?></div>
								<div class="vbo-bookingdet-summary-roomguests">
									<?php VikBookingIcons::e('male'); ?>
									<div class="vbo-bookingdet-summary-roomadults">
										<span><?php echo JText::translate('VBEDITORDERADULTS'); ?>:</span> <?php echo $or['adults']; ?>
									</div>
								<?php
								if ($or['children'] > 0) {
									?>
									<div class="vbo-bookingdet-summary-roomchildren">
										<span><?php echo JText::translate('VBEDITORDERCHILDREN'); ?>:</span> <?php echo $or['children']; ?>
									</div>
									<?php
								}
								?>
								</div>
								<?php
								if (!empty($arrpeople[$num]['t_first_name'])) {
								?>
								<div class="vbo-bookingdet-summary-guestname">
									<span><?php echo $arrpeople[$num]['t_first_name'].' '.$arrpeople[$num]['t_last_name']; ?></span>
								</div>
								<?php
								}
								?>
							</div>
							<div class="vbo-editbooking-room-pricetypes">
								<h4><?php echo JText::translate('VBPEDITBUSYSEVEN'); ?><?php echo $ord['closure'] < 1 && $ord['status'] != 'cancelled' ? '&nbsp;&nbsp; '.$vbo_app->createPopover(array('title' => JText::translate('VBPEDITBUSYSEVEN'), 'content' => JText::translate('VBOMISSPRTYPEROOMH'))) : ''; ?></h4>
								<div class="vbo-editbooking-room-pricetypes-wrap">
								<?php
								//print the standard rates
								foreach ($tars[$num] as $k => $t) {
									?>
									<div class="vbo-editbooking-room-pricetype">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="pid<?php echo $num.$t['idprice']; ?>"><?php echo VikBooking::getPriceName($t['idprice']).(strlen((string)$t['attrdata']) ? " - ".VikBooking::getPriceAttr($t['idprice']).": ".$t['attrdata'] : ""); ?></label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb." ".VikBooking::numberFormat($t['cost']); ?>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input class="vbo-pricetype-radio" type="radio" name="priceid<?php echo $num; ?>" id="pid<?php echo $num.$t['idprice']; ?>" value="<?php echo $t['idprice']; ?>" />
										</div>
									</div>
									<?php
								}
								//print the custom cost
								?>
									<div class="vbo-editbooking-room-pricetype">
										<div class="vbo-editbooking-room-pricetype-inner">
											<label for="cust_cost<?php echo $num; ?>" class="vbo-custrate-lbl-add"><?php echo JText::translate('VBOROOMCUSTRATEPLANADD'); ?></label>
											<div class="vbo-editbooking-room-pricetype-cost">
												<?php echo $currencysymb; ?> <input type="number" step="any" name="cust_cost<?php echo $num; ?>" id="cust_cost<?php echo $num; ?>" value="" placeholder="<?php echo VikBooking::numberFormat((!empty($ord['idorderota']) && !empty($ord['total']) ? $ord['total'] : 0)); ?>" size="4" onchange="if (this.value.length) {document.getElementById('priceid<?php echo $num; ?>').checked = true; jQuery('#priceid<?php echo $num; ?>').trigger('change'); document.getElementById('tax<?php echo $num; ?>').style.display = 'block';}" />
												<div class="vbo-editbooking-room-pricetype-seltax" id="tax<?php echo $num; ?>" style="display: none;"><?php echo (!empty($wiva) ? str_replace('%s', $num, $wiva) : ''); ?></div>
											</div>
										</div>
										<div class="vbo-editbooking-room-pricetype-check">
											<input class="vbo-pricetype-radio" type="radio" name="priceid<?php echo $num; ?>" id="priceid<?php echo $num; ?>" value="" onclick="document.getElementById('tax<?php echo $num; ?>').style.display = 'block';" />
										</div>
									</div>
								<?php
								//
								?>
								</div>
							</div>
						<?php
						$optionals = empty($or['idopt']) ? '' : VikBooking::getRoomOptionals($or['idopt']);
						if ($optionals) {
							// apply filters to options
							VikBooking::filterOptionalsByDate($optionals, $room_checkin, $room_checkout);
							VikBooking::filterOptionalsByParty($optionals, $or['adults'], $or['children']);
						}
						$arropt = array();
						//Room Options Start
						if ($optionals) {
							list($optionals, $ageintervals) = VikBooking::loadOptionAgeIntervals($optionals, $or['adults'], $or['children']);
							if ($ageintervals) {
								if ($optionals) {
									$ageintervals = array(0 => $ageintervals);
									$optionals = array_merge($ageintervals, $optionals);
								} else {
									$optionals = array(0 => $ageintervals);
								}
							}
							if (!empty($or['optionals'])) {
								$haveopt = explode(";", $or['optionals']);
								foreach ($haveopt as $ho) {
									if (!empty($ho)) {
										$havetwo = explode(":", $ho);
										if (strstr($havetwo[1], '-') != false) {
											$arropt[$havetwo[0]][] = $havetwo[1];
										} else {
											$arropt[$havetwo[0]] = $havetwo[1];
										}
									}
								}
							} else {
								$arropt[] = "";
							}
							?>
							<div class="vbo-editbooking-room-services vbo-editbooking-room-options">
								<h4><?php echo JText::translate('VBPEDITBUSYEIGHT'); ?></h4>
								<div class="vbo-editbooking-room-services-wrap vbo-editbooking-room-options-wrap">
								<?php
								foreach ($optionals as $k => $o) {
									$oval = "";
									if (intval($o['hmany']) == 1) {
										if (array_key_exists($o['id'], $arropt)) {
											$oval = $arropt[$o['id']];
										}
									} else {
										if (array_key_exists($o['id'], $arropt) && !is_array($arropt[$o['id']])) {
											$oval = " checked=\"checked\"";
										}
									}
									if (!empty($o['ageintervals'])) {
										if ($or['children'] > 0) {
											for ($ch = 1; $ch <= $or['children']; $ch++) {
												$chageselect = '<select name="optid'.$num.$o['id'].'[]">'."\n".'<option value="">  </option>'."\n";
												/**
												 * Age intervals may be overridden per child number.
												 * 
												 * @since 	1.13.5
												 */
												$optageovrct = VikBooking::getOptionIntervalChildOverrides($o, $or['adults'], $or['children']);
												$intervals = explode(';;', (isset($optageovrct['ageintervals_child' . $ch]) ? $optageovrct['ageintervals_child' . $ch] : $o['ageintervals']));
												//
												foreach ($intervals as $kintv => $intv) {
													if (empty($intv)) {
														continue;
													}
													$intvparts = explode('_', $intv);
													$intvparts[2] = intval($o['perday']) == 1 ? ($intvparts[2] * $room_nights) : $intvparts[2];
													if (array_key_exists(3, $intvparts) && strpos($intvparts[3], '%') !== false) {
														$pricestr = floatval($intvparts[2]) >= 0 ? '+ '.VikBooking::numberFormat($intvparts[2]) : '- '.VikBooking::numberFormat($intvparts[2]);
													} else {
														$pricestr = floatval($intvparts[2]) >= 0 ? '+ '.VikBooking::numberFormat($intvparts[2]) : '- '.VikBooking::numberFormat($intvparts[2]);
													}
													$selstatus = '';
													if (isset($arropt[$o['id']]) && is_array($arropt[$o['id']])) {
														$ageparts = explode('-', $arropt[$o['id']][($ch - 1)]);
														if ($kintv == ($ageparts[1] - 1)) {
															$selstatus = ' selected="selected"';
														}
													}
													$chageselect .= '<option value="'.($kintv + 1).'"'.$selstatus.'>'.$intvparts[0].' - '.$intvparts[1].' ('.$pricestr.' '.(array_key_exists(3, $intvparts) && strpos($intvparts[3], '%') !== false ? '%' : $currencysymb).')'.'</option>'."\n";
												}
												$chageselect .= '</select>'."\n";
												?>
									<div class="vbo-editbooking-room-service vbo-editbooking-room-option vbo-editbooking-room-option-childage">
										<div class="vbo-editbooking-room-service-inner vbo-editbooking-room-option-inner">
											<label for="optid<?php echo $num.$o['id'].$ch; ?>"><?php echo JText::translate('VBMAILCHILD').' #'.$ch; ?></label>
											<div class="vbo-editbooking-room-service-select vbo-editbooking-room-option-select">
												<?php echo $chageselect; ?>
											</div>
										</div>
									</div>
												<?php
											}
										}
									} else {
										$optquancheckb = 1;
										$forcedquan = 1;
										$forceperday = false;
										$forceperchild = false;
										if (intval($o['forcesel']) == 1 && strlen((string)$o['forceval'])) {
											$forceparts = explode("-", $o['forceval']);
											$forcedquan = intval($forceparts[0]);
											$forceperday = intval($forceparts[1]) == 1 ? true : false;
											$forceperchild = intval($forceparts[2]) == 1 ? true : false;
											$optquancheckb = $forcedquan;
											$optquancheckb = $forceperchild === true && array_key_exists($num, $arrpeople) && array_key_exists('children', $arrpeople[$num]) ? ($optquancheckb * $arrpeople[$num]['children']) : $optquancheckb;
										}
										if (intval($o['perday']) == 1) {
											$thisoptcost = $o['cost'] * $room_nights;
										} else {
											$thisoptcost = $o['cost'];
										}
										if ($o['maxprice'] > 0 && $thisoptcost > $o['maxprice']) {
											$thisoptcost = $o['maxprice'];
										}
										$thisoptcost = $thisoptcost * $optquancheckb;
										if (intval($o['perperson']) == 1) {
											$thisoptcost = $thisoptcost * $arrpeople[$num]['adults'];
										}
										?>
									<div class="vbo-editbooking-room-service vbo-editbooking-room-option">
										<div class="vbo-editbooking-room-service-inner vbo-editbooking-room-option-inner">
											<label for="optid<?php echo $num.$o['id']; ?>"><?php echo $o['name']; ?></label>
											<div class="vbo-editbooking-room-service-check vbo-editbooking-room-option-check">
												<?php echo (intval($o['hmany'])==1 ? "<input type=\"number\" name=\"optid".$num.$o['id']."\" id=\"optid".$num.$o['id']."\" value=\"".$oval."\" min=\"0\"/>" : "<input type=\"checkbox\" name=\"optid".$num.$o['id']."\" id=\"optid".$num.$o['id']."\" value=\"".$optquancheckb."\"".$oval."/>"); ?>
											</div>
										</div>
									</div>
										<?php
									}
								}
								?>
								</div>
							</div>
							<?php
						}
						//Room Options End
						//custom extra services for each room Start
						if (!empty($or['extracosts'])) {
							$cur_extra_costs = json_decode($or['extracosts'], true);
							?>
							<div class="vbo-editbooking-room-extracosts" id="vbo-ebusy-extracosts-<?php echo $num; ?>">
								<h4>
									<?php echo JText::translate('VBPEDITBUSYEXTRACOSTS'); ?> 
									<button class="btn vbo-ebusy-addextracost" type="button" onclick="vboAddExtraCost('<?php echo $num; ?>');"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBPEDITBUSYADDEXTRAC'); ?></button>
								</h4>
								<div class="vbo-editbooking-room-extracosts-wrap">
								<?php
								foreach ($cur_extra_costs as $eck => $ecv) {
									$ec_taxopts = '';
									foreach ($ivas as $iv) {
										$ec_taxopts .= "<option value=\"".$iv['id']."\"".(!empty($ecv['idtax']) && $ecv['idtax'] == $iv['id'] ? ' selected="selected"' : '').">".(empty($iv['name']) ? $iv['aliq']."%" : $iv['name']." - ".$iv['aliq']."%")."</option>\n";
									}
									?>
									<div class="vbo-editbooking-room-extracost">
										<div class="vbo-ebusy-extracosts-cellname">
											<input type="text" name="extracn[<?php echo $num; ?>][]" value="<?php echo addslashes($ecv['name']); ?>" placeholder="<?php echo addslashes(JText::translate('VBPEDITBUSYEXTRACNAME')); ?>" size="25" />
										</div>
										<div class="vbo-ebusy-extracosts-cellcost">
											<span class="vbo-ebusy-extracosts-currency"><?php echo $currencysymb; ?></span> 
											<input type="number" step="any" name="extracc[<?php echo $num; ?>][]" value="<?php echo addslashes($ecv['cost']); ?>" size="5" />
										</div>
										<div class="vbo-ebusy-extracosts-celltax">
											<select name="extractx[<?php echo $num; ?>][]">
												<option value=""><?php echo JText::translate('VBNEWOPTFOUR'); ?></option>
												<?php echo $ec_taxopts; ?>
											</select>
										</div>
										<div class="vbo-ebusy-extracosts-cellrm">
											<button class="btn btn-danger" type="button" onclick="vboRemoveExtraCost(this);">&times;</button>
										</div>
									</div>
									<?php
								}
							?>
								</div>
							</div>
						<?php
						}
						//custom extra services for each room End
						?>
						</div>
						<?php
					}
					//Rooms Loop End
					?>
						<div class="vbo-bookingdet-summary-room vbo-editbooking-summary-room vbo-editbooking-summary-totpaid">
							<div class="vbo-editbooking-summary-room-head">
								<div class="vbo-editbooking-totpaid">
									<label for="totpaid"><?php echo JText::translate('VBPEDITBUSYTOTPAID'); ?></label>
									<?php echo $currencysymb; ?> <input type="number" min="0" step="any" id="totpaid" name="totpaid" value="<?php echo $ord['totpaid']; ?>"/>
								</div>
								<div class="vbo-editbooking-totpaid vbo-editbooking-totrefund">
									<label for="refund"><?php echo JText::translate('VBO_AMOUNT_REFUNDED'); ?></label>
									<?php echo $currencysymb; ?> <input type="number" min="0" step="any" id="refund" name="refund" value="<?php echo $ord['refund']; ?>"/>
								</div>
							</div>
						</div>
					</div>
					<?php
				} else {
					?>
					<p class="err"><?php echo JText::translate('VBPEDITBUSYERRNOFARES'); ?></p>
					<?php
				}
				//order is a quick reservation from administrator - end
			}
			?>
				</div>
			</div>
		</div>
		<input type="hidden" name="task" value="">
		<input type="hidden" name="idorder" value="<?php echo $ord['id']; ?>">
		<input type="hidden" name="option" value="com_vikbooking">
		<?php
		$pfrominv = VikRequest::getInt('frominv', '', 'request');
		echo $pfrominv == 1 ? '<input type="hidden" name="frominv" value="1">' : '';
		$pvcm = VikRequest::getInt('vcm', '', 'request');
		echo $pvcm == 1 ? '<input type="hidden" name="vcm" value="1">' : '';
		echo $pgoto == 'overv' ? '<input type="hidden" name="goto" value="overv">' : '';
		?>
	</form>
</div>

<script type="text/javascript">
jQuery(function() {
	jQuery('#checkindate').val('<?php echo $rit; ?>').attr('data-alt-value', '<?php echo $rit; ?>');
	jQuery('#checkoutdate').val('<?php echo $con; ?>').attr('data-alt-value', '<?php echo $con; ?>');
<?php
foreach ($js_cal_def_vals as $js_cal_def_val) {
	?>
	jQuery('#<?php echo $js_cal_def_val['id']; ?>').val('<?php echo $js_cal_def_val['value']; ?>').attr('data-alt-value', '<?php echo $js_cal_def_val['value']; ?>');
	<?php
}
?>
	jQuery('.vbo-pricetype-radio').change(function() {
		jQuery(this).closest('.vbo-editbooking-room-pricetypes').find('.vbo-editbooking-room-pricetype.vbo-editbooking-room-pricetype-active').removeClass('vbo-editbooking-room-pricetype-active');
		jQuery(this).closest('.vbo-editbooking-room-pricetype').addClass('vbo-editbooking-room-pricetype-active');
	});
});
if (jQuery.isFunction(jQuery.fn.tooltip)) {
	jQuery(".hasTooltip").tooltip();
}
</script>
