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
$rooms_busy = $this->rooms_busy;
$fromts = $this->fromts;
$tots = $this->tots;

$operator_perms = !empty($this->operator['perms']) && is_array($this->operator['perms']) ? $this->operator['perms'] : [];

$pitemid = VikRequest::getInt('Itemid', 0, 'request');

// JS lang vars
JText::script('VBFORMADULTS');
JText::script('VBFORMCHILDREN');
JText::script('ORDER_SPREQUESTS');
JText::script('VBOCUSTOMERNOMINATIVE');
JText::script('VBPICKUP');
JText::script('VBRETURN');
JText::script('VBSEARCHRESROOM');
JText::script('VBOEXTRASERVICES');
JText::script('VBO_PETS');

$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();

//load jQuery
if (VikBooking::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}

$days_labels = array(
	JText::translate('VBSUN'),
	JText::translate('VBMON'),
	JText::translate('VBTUE'),
	JText::translate('VBWED'),
	JText::translate('VBTHU'),
	JText::translate('VBFRI'),
	JText::translate('VBSAT')
);
$months_labels = array(
	JText::translate('VBMONTHONE'),
	JText::translate('VBMONTHTWO'),
	JText::translate('VBMONTHTHREE'),
	JText::translate('VBMONTHFOUR'),
	JText::translate('VBMONTHFIVE'),
	JText::translate('VBMONTHSIX'),
	JText::translate('VBMONTHSEVEN'),
	JText::translate('VBMONTHEIGHT'),
	JText::translate('VBMONTHNINE'),
	JText::translate('VBMONTHTEN'),
	JText::translate('VBMONTHELEVEN'),
	JText::translate('VBMONTHTWELVE')
);

// count the cells per month
$months_days = array();
$nowinfo = getdate($fromts);
while ($nowinfo[0] <= $tots) {
	$mkey = $nowinfo['mon'] . '_' . $nowinfo['year'];
	if (!isset($months_days[$mkey])) {
		$months_days[$mkey] = 0;
	}
	$months_days[$mkey]++;
	// next loop
	$nowinfo = getdate(mktime(0, 0, 0, $nowinfo['mon'], ($nowinfo['mday'] + 1), $nowinfo['year']));
}

// today
$todaydt = date('Y-m-d');
$todayts = strtotime($todaydt);

// tomorrow
$tomorrowts = strtotime("+1 day", $todayts);

// count today and tomorrow bookings
$tod_arrive = 0;
$tom_arrive = 0;
$tod_depart = 0;
$tom_depart = 0;
$tod_stays  = 0;
$tom_stays  = 0;
foreach ($rooms_busy as $rid => $busy) {
	foreach ($busy as $b) {
		$tmpone = getdate($b['checkin']);
		$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
		$tmptwo = getdate($b['checkout']);
		$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
		if ($todayts == $ritts) {
			$tod_arrive++;
		}
		if ($tomorrowts == $ritts) {
			$tom_arrive++;
		}
		if ($todayts == $conts) {
			$tod_depart++;
		}
		if ($tomorrowts == $conts) {
			$tom_depart++;
		}
		if ($todayts > $ritts && $todayts < $conts) {
			$tod_stays++;
		}
		if ($tomorrowts > $ritts && $tomorrowts < $conts) {
			$tom_stays++;
		}
	}
}
?>

<div id="vbdialog-overlay">
	<a class="vbdialog-overlay-close">&nbsp;</a>
	<div class="vbdialog-inner">
		<div class="vbdialog-inner-tableaux"></div>
	</div>
</div>

<div class="vbo-modal-overlay-block vbo-modal-overlay-block-fests">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-fests">
		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-fests">
			<h3><?php VikBookingIcons::e('star'); ?> <span></span></h3>
		</div>
		<div class="vbo-modal-overlay-content-body">
			<div class="vbo-overlay-fests-list"></div>
		</div>
	</div>
</div>

<div class="vbo-modal-overlay-block vbo-modal-overlay-block-roomdaynotes">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-roomdaynotes">
		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-roomdaynotes">
			<h3>
				<?php VikBookingIcons::e('exclamation-circle'); ?> 
				<span class="vbo-modal-roomdaynotes-dt"></span>
				<span class="vbo-modal-overlay-close-times" onclick="hideVboDialogRdaynotes();">&times;</span>
			</h3>
		</div>
		<div class="vbo-modal-overlay-content-body">
			<div class="vbo-modal-roomdaynotes-list"></div>
			<div class="vbo-modal-roomdaynotes-addnew" data-readymd="" data-ymd="" data-roomid="" data-subroomid="">
				<h4><?php echo JText::translate('VBOADDCUSTOMNOTETODAY'); ?></h4>
				<div class="vbo-modal-roomdaynotes-addnew-elem">
					<label for="vbo-newrdnote-name"><?php echo JText::translate('VBNAME'); ?></label>
					<input type="text" id="vbo-newrdnote-name" value="" />
				</div>
				<div class="vbo-modal-roomdaynotes-addnew-elem">
					<label for="vbo-newrdnote-descr"><?php echo JText::translate('VBOINVCOLDESCR'); ?></label>
					<textarea id="vbo-newrdnote-descr"></textarea>
				</div>
				<div class="vbo-modal-roomdaynotes-addnew-elem">
					<label for="vbo-newrdnote-cdays"><?php echo JText::translate('VBOCONSECUTIVEDAYS'); ?></label>
					<input type="number" id="vbo-newrdnote-cdays" min="0" max="365" value="0" onchange="vboRdayNoteCdaysCount();" onkeyup="vboRdayNoteCdaysCount();" />
					<span class="vbo-newrdnote-dayto">
						<span class="vbo-newrdnote-dayto-lbl"><?php echo JText::translate('VBOUNTIL'); ?></span>
						<span class="vbo-newrdnote-dayto-val"></span>
					</span>
				</div>
				<div class="vbo-modal-roomdaynotes-addnew-save">
					<button type="button" class="btn vbo-pref-color-btn" onclick="vboAddRoomDayNote(this);"><?php echo JText::translate('VBOSIGNATURESAVE'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="vbo-operator-tool-container">
	<div class="vbo-operator-tool-breadcrumbs">
		<span class="vbo-operator-tool-breadcrumb vbo-operator-tool-breadcrumb-home">
			<a class="vbo-pref-color-text" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=operators' . (!empty($pitemid) ? '&Itemid=' . $pitemid : '')); ?>">
				<?php VikBookingIcons::e('th-list'); ?>
				<span class="vbo-operator-tool-breadcrumb-name"><?php echo JText::translate('VBMENUDASHBOARD'); ?></span>
				<span class="vbo-operator-tool-breadcrumb-separator"><?php VikBookingIcons::e('chevron-right'); ?></span>
			</a>
		</span>
		<!-- use .vbo-operator-tool-breadcrumb-step-current to define a previous breadcrump-step, not the home, not the current -->
		<span class="vbo-operator-tool-breadcrumb vbo-operator-tool-breadcrumb-step vbo-operator-tool-breadcrumb-step-current">
			<span class="vbo-operator-tool-breadcrumb-name"><?php echo VikBooking::getOperatorInstance()->getToolName('tableaux'); ?></span>
		</span>
	</div>
</div>

<div class="vbo-tableaux-todaystats">
	<div class="vbo-tableaux-todaystat">
		<div class="vbo-tableaux-todaystat-inner">
			<span class="vbo-tableaux-today-lbl"><?php echo JText::translate('VBOTABLEAUXARRTD'); ?></span>
			<span class="vbo-tableaux-today-val"><?php echo $tod_arrive; ?></span>
		</div>
		<div class="vbo-tableaux-todaystat-inner">
			<span class="vbo-tableaux-today-lbl"><?php echo JText::translate('VBOTABLEAUXARRTM'); ?></span>
			<span class="vbo-tableaux-today-val"><?php echo $tom_arrive; ?></span>
		</div>
	</div>
	<div class="vbo-tableaux-todaystat">
		<div class="vbo-tableaux-todaystat-inner">
			<span class="vbo-tableaux-today-lbl"><?php echo JText::translate('VBOTABLEAUXDEPTD'); ?></span>
			<span class="vbo-tableaux-today-val"><?php echo $tod_depart; ?></span>
		</div>
		<div class="vbo-tableaux-todaystat-inner">
			<span class="vbo-tableaux-today-lbl"><?php echo JText::translate('VBOTABLEAUXDEPTM'); ?></span>
			<span class="vbo-tableaux-today-val"><?php echo $tom_depart; ?></span>
		</div>
	</div>
	<div class="vbo-tableaux-todaystat">
		<div class="vbo-tableaux-todaystat-inner">
			<span class="vbo-tableaux-today-lbl"><?php echo JText::translate('VBOTABLEAUXSTYTD'); ?></span>
			<span class="vbo-tableaux-today-val"><?php echo $tod_stays; ?></span>
		</div>
		<div class="vbo-tableaux-todaystat-inner">
			<span class="vbo-tableaux-today-lbl"><?php echo JText::translate('VBOTABLEAUXSTYTM'); ?></span>
			<span class="vbo-tableaux-today-val"><?php echo $tom_stays; ?></span>
		</div>
	</div>
</div>

<div class="vbo-tableaux-outer">
	<div class="vbo-tableaux-container">
		<div class="vbo-tableaux-tbl-container">
			<div class="vbo-table-responsive vbo-tableaux-tbl-wrap">
				<table class="vbo-table vbo-tableaux-table">
					<tbody>
						<tr class="vbo-tableaux-monthsrow">
							<td class="vbo-tableaux-emptycell">
								<a href="javascript: void(0);" class="vbo-tableaux-togglefullscreen" title="<?php echo htmlentities(JText::translate('VBOTGLFULLSCREEN')); ?>"><?php VikBookingIcons::e('expand'); ?></a>
							</td>
						<?php
						foreach ($months_days as $mkey => $totdays) {
							$mkeyparts = explode('_', $mkey);
							?>
							<td class="vbo-tableaux-monthcell" colspan="<?php echo $totdays; ?>">
								<h4><?php echo $months_labels[($mkeyparts[0] - 1)] . ' ' . $mkeyparts[1]; ?></h4>
							</td>
							<?php
						}
						?>
						</tr>
						<tr class="vbo-tableaux-firstrow">
							<td class="vbo-tableaux-emptycell">&nbsp;</td>
						<?php
						$newmonth = 0;
						$nowinfo = getdate($fromts);
						while ($nowinfo[0] <= $tots) {
							$nowinfo_ymd = date('Y-m-d', $nowinfo[0]);
							$monthclass = !empty($newmonth) && $nowinfo['mon'] != $newmonth ? ' vbo-tableaux-newmonthcell' : '';
							$monthclass = date('Y-m-d', $nowinfo[0]) == $todaydt ? ' vbo-tableaux-todaycell' : $monthclass;
							$newmonth = $nowinfo['mon'];
							$read_day = $days_labels[$nowinfo['wday']] . ' ' . $nowinfo['mday'] . ' ' . $months_labels[($nowinfo['mon'] - 1)] . ' ' . $nowinfo['year'];
							$fest_class = isset($this->festivities[$nowinfo_ymd]) ? ' vbo-tableaux-festcell' : '';
							?>
							<td class="vbo-tableaux-daycell<?php echo $monthclass . $fest_class; ?>" data-ymd="<?php echo $nowinfo_ymd; ?>" data-readymd="<?php echo $read_day; ?>">
								<div class="vbo-tableaux-day-cont">
									<span class="vbo-tableaux-day-wday"><?php echo $days_labels[$nowinfo['wday']]; ?></span>
									<span class="vbo-tableaux-day-mday"><?php echo $nowinfo['mday']; ?></span>
								</div>
							</td>
							<?php
							// next loop
							$nowinfo = getdate(mktime(0, 0, 0, $nowinfo['mon'], ($nowinfo['mday'] + 1), $nowinfo['year']));
						}
						?>
						</tr>
					<?php
					$rooms_features_map = array();
					$rooms_features_bookings = array();
					$rooms_loop_count = array();
					foreach ($rooms as $rid => $rdata) {
						// distinctive features
						$room_params = !empty($rdata['params']) ? json_decode($rdata['params'], true) : array();
						if (is_array($room_params) && array_key_exists('features', $room_params) && count($room_params['features']) > 0) {
							$rooms_features_map[$rid] = array();
							foreach ($room_params['features'] as $rind => $rfeatures) {
								foreach ($rfeatures as $fname => $fval) {
									if (strlen($fval)) {
										$rooms_features_map[$rid][$rind] = '#'.$fval;
										break;
									}
								}
							}
							if (!(count($rooms_features_map[$rid]) > 0)) {
								unset($rooms_features_map[$rid]);
							}
						}
						//
						?>
						<tr class="vbo-tableaux-roomrow">
							<td class="vbo-tableaux-roomname"><?php echo $rdata['name']; ?></td>
						<?php
						$nowinfo = getdate($fromts);
						$bookbuffer = array();
						$prevbuffer = array();
						$positions = array();
						$newmonth = 0;
						while ($nowinfo[0] <= $tots) {
							$nowinfo_ymd = date('Y-m-d', $nowinfo[0]);
							$monthclass = !empty($newmonth) && $nowinfo['mon'] != $newmonth ? ' vbo-tableaux-newmonthcell' : '';
							$monthclass = date('Y-m-d', $nowinfo[0]) == $todaydt ? ' vbo-tableaux-todaycell' : $monthclass;
							$newmonth = $nowinfo['mon'];

							/**
							 * Critical dates defined at room-day level, or for any sub-unit.
							 * 
							 * @since 	1.13.5
							 */
							$rdaynote_class  = ' vbo-roomdaynote-empty';
							$rdaynote_icn 	 = 'far fa-sticky-note';
							$cell_rdnotes 	 = '';
							$rdnkeys_lookup  = range(0, $rdata['units']);
							$rdaynote_keyids = array();
							$glob_rdn_keyid  = $nowinfo_ymd . '_' . $rdata['id'] . '_0';
							// find room-day note keys with some notes (room-day level or subroom-day level)
							foreach ($rdnkeys_lookup as $lookup_index) {
								$rdaynote_keyid = $nowinfo_ymd . '_' . $rdata['id'] . '_' . $lookup_index;
								if (isset($this->rdaynotes[$rdaynote_keyid])) {
									// push associative index with notes
									$rdaynote_keyids[$lookup_index] = $rdaynote_keyid;
								}
							}
							if (count($rdaynote_keyids)) {
								// some notes exist for this combination of date, room ID and subunit
								$rdaynote_class = ' vbo-roomdaynote-full';
								$rdaynote_icn = 'sticky-note';
								/**
								 * Try to populate the notes for this room-day cell
								 * only if the previous day does not have the same note.
								 * Just the first readable room-day cell should have notes.
								 */
								$notes_titles = array();
								$yesterday_ts = mktime(0, 0, 0, $nowinfo['mon'], ($nowinfo['mday'] - 1), $nowinfo['year']);
								$yesterday_ymd = date('Y-m-d', $yesterday_ts);
								// loop through all the keys with notes
								foreach ($rdaynote_keyids as $lookup_index => $rdaynote_keyid) {
									$yesterday_keyid = $yesterday_ymd . '_' . $rdata['id'] . '_' . $lookup_index;
									foreach ($this->rdaynotes[$rdaynote_keyid]['info'] as $today_note) {
										// only manual (custom) room-day notes will be displayed
										$display_note = ($today_note->type == 'custom');
										if (isset($this->rdaynotes[$yesterday_keyid])) {
											// make sure the same note is not present for yesterday
											foreach ($this->rdaynotes[$yesterday_keyid]['info'] as $yesterday_note) {
												if ($today_note->type == $yesterday_note->type && $today_note->name == $yesterday_note->name) {
													// same note available also for yesterday
													$display_note = false;
													break;
												}
											}
										}
										if ($display_note) {
											// push just the name of the note
											array_push($notes_titles, $today_note->name);
										}
									}
								}
								// separate all notes (if any) with comma
								$cell_rdnotes = implode(', ', $notes_titles);
								//
							}
							//

							?>
							<td class="vbo-tableaux-roombooks<?php echo $monthclass . $rdaynote_class; ?>" data-day="<?php echo $nowinfo_ymd; ?>" data-dayrid="<?php echo $nowinfo_ymd . $rdata['id']; ?>" data-rdnkeys="<?php echo implode(',', array_keys($rdaynote_keyids)); ?>" data-notes="<?php echo $this->escape($cell_rdnotes); ?>">
							<?php
							$room_bookings = array();
							if (isset($rooms_busy[$rid])) {
								foreach ($rooms_busy[$rid] as $b) {
									$tmpone = getdate($b['checkin']);
									$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
									$tmptwo = getdate($b['checkout']);
									$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
									$b['basefrom'] = $ritts;
									$b['baseto'] = $conts;
									if ($nowinfo[0] >= $ritts && $nowinfo[0] <= $conts) {
										array_push($room_bookings, $b);
									}
								}
								/**
								 * By default, we attempt to sort the bookings for this day by room index.
								 * 
								 * @since 	1.13.5
								 */
								if (count($room_bookings)) {
									$rindexes_map = array();
									foreach ($room_bookings as $rbk => $rbval) {
										$rindexes_map[$rbk] = isset($rbval['indexes']) ? $rbval['indexes'] : '';
									}
									asort($rindexes_map);
									$room_bookings_copy = array();
									foreach ($rindexes_map as $rbk => $rbval) {
										array_push($room_bookings_copy, $room_bookings[$rbk]);
									}
									$room_bookings = $room_bookings_copy;
								}
								//
							}
							if (count($room_bookings) && count($bookbuffer)) {
								// sort bookings according to the previous map
								$newbookings = array();
								foreach ($bookbuffer as $oid) {
									foreach ($room_bookings as $kb => $rbook) {
										if ($oid == $rbook['idorder']) {
											// get this key first
											$newbookings[$kb] = $rbook;
											// do not break the loop as there could be multiple idorder, just continue
											continue;
										}
									}
								}
								if (count($newbookings)) {
									// merge array by keys by unsetting double keys from second array
									$room_bookings = $newbookings + $room_bookings;
								}
								// copy buffer before reset to see whether this is the first cell displayed for the booking
								$prevbuffer = $bookbuffer;
								// reset buffer to fill the current day bookings
								$bookbuffer = array();
							}
							$indexpos = 0;
							foreach ($room_bookings as $k => $rbook) {
								$contclass = 'stay';
								if ($rbook['basefrom'] == $nowinfo[0]) {
									$contclass = 'checkin';
								} elseif ($rbook['baseto'] == $nowinfo[0]) {
									$contclass = 'checkout';
								}
								$shortstaycls = '';
								if (ceil(($rbook['baseto'] - $rbook['basefrom']) / 86400) < 2) {
									$shortstaycls = ' vbo-tableaux-booking-short';
								}
								//check position
								$pos = $indexpos;
								if (isset($positions[$rbook['idorder']]) && $indexpos < $positions[$rbook['idorder']]) {
									// print empty blocks to give the right position to this booking
									$pos = $indexpos.'-'.$positions[$rbook['idorder']];
									$looplim = ($positions[$rbook['idorder']] - $indexpos);
									for ($i = 0; $i < $looplim; $i++) { 
										$indexpos++;
										?>
								<div class="vbo-tableaux-booking vbo-tableaux-booking-empty">
									<span>&nbsp;</span>
								</div>
										<?php
									}
									$pos .= '-'.$indexpos;
								}
								// push position
								if (!isset($positions[$rbook['idorder']])) {
									$positions[$rbook['idorder']] = $indexpos;
								}
								// push booking to the buffer for the ordering in the next loop
								array_push($bookbuffer, $rbook['idorder']);
								
								// cell content
								$cellcont = '&nbsp;';
								// container attributes
								$cont_attrs = array();
								if (!in_array($rbook['idorder'], $prevbuffer)) {
									// first time we print the details for this booking - compose the content of the element
									$cellcont = '';
									// get the key to use for reading values that were grouped and separated with the query
									if (!isset($rooms_loop_count[$rbook['idorder']])) {
										// the index to read of the feature depending on how many times this booking was printed (in case of multiple same rooms in one booking)
										$rooms_loop_count[$rbook['idorder']] = 0;
									} else {
										// increment index for this room booking
										$rooms_loop_count[$rbook['idorder']]++;
									}
									$nowdataindex = $rooms_loop_count[$rbook['idorder']];
									// add data attribute for adults, children and pets
									$adultsinfo = explode(';', $rbook['adults']);
									$childreninfo = explode(';', $rbook['children']);
									$petsinfo = explode(';', $rbook['pets']);
									if (isset($adultsinfo[$nowdataindex]) && isset($childreninfo[$nowdataindex])) {
										$cont_attrs['party'] = $adultsinfo[$nowdataindex] . ';' . $childreninfo[$nowdataindex];
										if (isset($petsinfo[$nowdataindex])) {
											$cont_attrs['party'] .= ';' . $petsinfo[$nowdataindex];
										}
									}
									// add data attribute for traveler first and last name
									$namesinfo = explode(';', $rbook['tnames']);
									$lnamesinfo = explode(';', $rbook['tlnames']);
									if (isset($namesinfo[$nowdataindex]) && isset($lnamesinfo[$nowdataindex]) && !empty($namesinfo[$nowdataindex])) {
										/**
										 * Make sure permissions allow to see the guest name.
										 * 
										 * @since 	1.15.0 (J) - 1.5.0 (WP)
										 */
										if (!empty($operator_perms['guestname'])) {
											$cont_attrs['tnominative'] = htmlspecialchars($namesinfo[$nowdataindex] . ' ' . $lnamesinfo[$nowdataindex]);
										}
									}
									// check-in time
									$cont_attrs['checkin'] = date(str_replace("/", $datesep, $df).' H:i', $rbook['checkin']);
									$cont_attrs['checkout'] = date(str_replace("/", $datesep, $df).' H:i', $rbook['checkout']);

									/**
									 * If permissions allow to see extra services, build a list of services included.
									 * 
									 * @since 	1.15.0 (J) - 1.5.0 (WP)
									 */
									if (!empty($operator_perms['roomextras'])) {
										// build extra info strings
										$room_extra_infos = [];

										// parse Website and OTA rate plans
										$room_rplans = explode('__', $rbook['ridtars']);
										$room_ota_rplans = explode('__', $rbook['rotarplans']);
										if (!empty($room_rplans[$nowdataindex])) {
											$rplan_name = $this->getRatePlanFromTariff(($room_rplans[$nowdataindex]));
											if ($rplan_name) {
												$room_extra_infos[] = $rplan_name;
											}
										} elseif (!empty($room_ota_rplans[$nowdataindex])) {
											$room_extra_infos[] = $room_ota_rplans[$nowdataindex];
										}

										// parse options
										$room_options = explode('__', $rbook['roptions']);
										if (!empty($room_options[$nowdataindex])) {
											$room_options_list = [];
											$all_room_options = explode(';', $room_options[$nowdataindex]);
											foreach ($all_room_options as $room_opt) {
												$room_opt_parts = explode(':', $room_opt);
												if (count($room_opt_parts) < 2 || empty($room_opt_parts[0]) || !isset($this->all_options[$room_opt_parts[0]])) {
													continue;
												}
												$room_options_list[] = $this->all_options[$room_opt_parts[0]]['name'];
											}
											if (count($room_options_list)) {
												$room_extra_infos[] = JText::translate('VBACCOPZ') . ': ' . implode(', ', $room_options_list);
											}
										}

										// parse extra services
										$room_extras = explode('__', $rbook['rextras']);
										if (!empty($room_extras[$nowdataindex])) {
											$room_extras_list = [];
											$decoded_room_extras = json_decode($room_extras[$nowdataindex]);
											if (is_array($decoded_room_extras)) {
												foreach ($decoded_room_extras as $dre) {
													if (!empty($dre->name)) {
														$room_extras_list[] = $dre->name;
													}
												}
											}
											if (count($room_extras_list)) {
												$room_extra_infos[] = JText::translate('VBOEXTRASERVICES') . ': ' . implode(', ', $room_extras_list);
											}
										}

										// set the whole room extra infos string
										$cont_attrs['rextrainfos'] = implode(' - ', $room_extra_infos);
									}

									// check for special requests
									if (preg_match("/special requests: (.*?)(?=[a-z0-9\-_ ]+:|$)/is", $rbook['custdata'], $matches)) {
										$cont_attrs['sprequest'] = htmlspecialchars($matches[1]);
									}
									// distinctive features
									if (!empty($rbook['indexes']) && isset($rooms_features_map[$rid])) {
										$bookindexes = explode(';', $rbook['indexes']);
										if (!isset($rooms_features_bookings[$rid.'_'.$rbook['idorder']])) {
											// the index to read of the feature depending on how many times this booking was printed (in case of multiple same rooms in one booking)
											$rooms_features_bookings[$rid.'_'.$rbook['idorder']] = 0;
										} else {
											// increment index for this room booking
											$rooms_features_bookings[$rid.'_'.$rbook['idorder']]++;
										}
										$nowfeatindex = $rooms_features_bookings[$rid.'_'.$rbook['idorder']];
										if (isset($bookindexes[$nowfeatindex]) && isset($rooms_features_map[$rid][$bookindexes[$nowfeatindex]])) {
											// get this room feature
											$cellcont .= '<span class="vbo-tableaux-roomindex">'.$rooms_features_map[$rid][$bookindexes[$nowfeatindex]] . '</span> ';
										}
									}
									// customer details
									if (!empty($rbook['first_name']) || !empty($rbook['last_name'])) {
										// customer record
										if (!empty($operator_perms['guestname'])) {
											$cellcont .= $rbook['first_name'].' '.$rbook['last_name'];
										} else {
											$cellcont .= '#' . $rbook['idorder'];
										}
									} else {
										// parse the customer data string
										$custdata_parts = explode("\n", $rbook['custdata']);
										$enoughinfo = false;
										if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
											// get the first two fields
											$custvalues = array();
											foreach ($custdata_parts as $custdet) {
												if (strlen($custdet) < 1) {
													continue;
												}
												$custdet_parts = explode(':', $custdet);
												if (count($custdet_parts) >= 2) {
													unset($custdet_parts[0]);
													array_push($custvalues, trim(implode(':', $custdet_parts)));
												}
												if (count($custvalues) > 1) {
													break;
												}
											}
											if (count($custvalues) > 1) {
												$enoughinfo = true;
												$cellcont .= implode(' ', $custvalues);
											}
										}
										if (!$enoughinfo) {
											$cellcont .= $rbook['idorder'];
										}
									}
								}
								$data_attrs = array();
								foreach ($cont_attrs as $datak => $datav) {
									array_push($data_attrs, "data-{$datak}=\"{$datav}\"");
								}
								?>
								<div class="vbo-tableaux-booking vbo-tableaux-booking-<?php echo $contclass.$shortstaycls.' vbo-'.$pos; ?>" data-bid="<?php echo $rbook['idorder']; ?>" <?php echo implode(' ', $data_attrs); ?>>
									<span><?php echo $cellcont; ?></span>
								</div>
								<?php
								// increase the positioning index
								$indexpos++;
							}
							?>
								<span class="vbo-roomdaynote-trigger" data-roomday="<?php echo $glob_rdn_keyid; ?>"><?php VikBookingIcons::e($rdaynote_icn, 'vbo-roomdaynote-display'); ?></span>
							</td>
							<?php
							// next loop
							$nowinfo = getdate(mktime(0, 0, 0, $nowinfo['mon'], ($nowinfo['mday'] + 1), $nowinfo['year']));
						}
						?>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
var vboFests = <?php echo json_encode($this->festivities); ?>;
var vboRdayNotes = <?php echo json_encode($this->rdaynotes); ?>;
var vbdialog_on = false;
var vbodialogfests_on = false;
var vbodialogrdaynotes_on = false;

function vbDialogClose() {
	if (vbdialog_on === true) {
		jQuery("#vbdialog-overlay").fadeOut();
		vbdialog_on = false;
	}
}

/**
 * Fests
 */
function hideVboDialogFests() {
	if (vbodialogfests_on === true) {
		jQuery(".vbo-modal-overlay-block-fests").fadeOut(400, function () {
			jQuery(".vbo-modal-overlay-content-fests").show();
		});
		vbodialogfests_on = false;
	}
}

function vboRenderFests(day, daytitle) {
	// set day title
	if (daytitle) {
		jQuery('.vbo-modal-overlay-content-fests').find('h3').find('span').text(daytitle);
	}
	// compose fests information
	var fests_html = '';
	if (vboFests[day] && vboFests[day]['festinfo'] && vboFests[day]['festinfo'].length) {
		for (var i = 0; i < vboFests[day]['festinfo'].length; i++) {
			var fest = vboFests[day]['festinfo'][i];
			fests_html += '<div class="vbo-overlay-fest-details">';
			fests_html += '	<div class="vbo-fest-info">';
			fests_html += '		<div class="vbo-fest-name">' + fest['trans_name'] + '</div>';
			fests_html += '		<div class="vbo-fest-desc">' + fest['descr'].replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2') + '</div>';
			fests_html += '	</div>';
			fests_html += '</div>';
		}
	}
	// set content and display modal
	jQuery('.vbo-overlay-fests-list').html(fests_html);
	jQuery('.vbo-modal-overlay-block-fests').fadeIn();
	vbodialogfests_on = true;
}

/**
 * Room-day-notes
 */
var rdaynote_icn_full = '<?php echo VikBookingIcons::i('sticky-note', 'vbo-roomdaynote-display'); ?>';
var rdaynote_icn_empty = '<?php echo VikBookingIcons::i('far fa-sticky-note', 'vbo-roomdaynote-display'); ?>';
var rooms_features_map = JSON.parse('<?php echo isset($rooms_features_map) && count($rooms_features_map) ? json_encode($rooms_features_map) : json_encode(array()); ?>');

function hideVboDialogRdaynotes() {
	if (vbodialogrdaynotes_on === true) {
		jQuery(".vbo-modal-overlay-block-roomdaynotes").fadeOut(400, function () {
			jQuery(".vbo-modal-overlay-content-roomdaynotes").show();
		});
		// reset values
		jQuery('#vbo-newrdnote-name').val('');
		jQuery('#vbo-newrdnote-descr').val('');
		jQuery('#vbo-newrdnote-cdays').val('0').trigger('change');
		// turn flag off
		vbodialogrdaynotes_on = false;
	}
}

function vboRenderRdayNotes(day, idroom, subunit, readymd) {
	// compose room-day notes information (subunit always = 0 for room-day level)
	var notes_html = '';
	var keyids = new Array;
	// always push 0 as the room-level day note key
	keyids.push(0);
	// grab all the other note keys at sub-unit level
	if (jQuery('.vbo-tableaux-roombooks[data-dayrid="' + day + idroom + '"]').length) {
		var all_nkeys = jQuery('.vbo-tableaux-roombooks[data-dayrid="' + day + idroom + '"]').attr('data-rdnkeys');
		if (all_nkeys && all_nkeys.length) {
			var nkeys = all_nkeys.split(',');
			for (var k in nkeys) {
				var int_index = parseInt(nkeys[k]);
				if (!nkeys.hasOwnProperty(k) || isNaN(int_index) || int_index < 1) {
					continue;
				}
				// push sub-unit key for lookup
				keyids.push(nkeys[k]);
			}
		}
	}
	// parse all room keys with notes
	for (var z = 0; z < keyids.length; z++) {
		// this is the real sub-unit we are parsing
		var subunit_index = keyids[z];
		var keyid = day + '_' + idroom + '_' + subunit_index;
		if (vboRdayNotes.hasOwnProperty(keyid) && vboRdayNotes[keyid]['info'] && vboRdayNotes[keyid]['info'].length) {
			for (var i = 0; i < vboRdayNotes[keyid]['info'].length; i++) {
				var note_data = vboRdayNotes[keyid]['info'][i];
				var subunit_name = '';
				if (parseInt(subunit_index) > 0) {
					subunit_name = '(' + subunit_index + ') ';
					if (rooms_features_map.hasOwnProperty(idroom) && rooms_features_map[idroom].hasOwnProperty(subunit_index)) {
						subunit_name = rooms_features_map[idroom][subunit_index] + ' - ';
					}
				}
				notes_html += '<div class="vbo-overlay-fest-details vbo-modal-roomdaynotes-note-details">';
				notes_html += '	<div class="vbo-fest-info vbo-modal-roomdaynotes-note-info">';
				notes_html += '		<div class="vbo-fest-name vbo-modal-roomdaynotes-note-name">' + subunit_name + note_data['name'] + '</div>';
				notes_html += '		<div class="vbo-fest-desc vbo-modal-roomdaynotes-note-desc">' + note_data['descr'].replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2') + '</div>';
				notes_html += '	</div>';
				notes_html += '</div>';
			}
		}
	}
	// update attributes keys for the selected date, useful for adding new notes (always at room-level)
	jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-ymd', day).attr('data-roomid', idroom).attr('data-subroomid', '0');
	if (readymd !== null) {
		jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-readymd', readymd);
		jQuery('.vbo-newrdnote-dayto-val').text(readymd);
	}
	// set content and display modal
	jQuery('.vbo-modal-roomdaynotes-list').html(notes_html);
}

function vboAddRoomDayNote(that) {
	var mainelem = jQuery(that).closest('.vbo-modal-roomdaynotes-addnew');
	var ymd = mainelem.attr('data-ymd');
	var roomid = mainelem.attr('data-roomid');
	var subroomid = mainelem.attr('data-subroomid');
	var note_name = jQuery('#vbo-newrdnote-name').val();
	var note_descr = jQuery('#vbo-newrdnote-descr').val();
	var note_cdays = jQuery('#vbo-newrdnote-cdays').val();
	if (!note_name.length && !note_descr.length) {
		alert('Missing required fields');
		return false;
	}
	// make the AJAX request to the controller to add this note to the DB
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=add_roomdaynote&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>",
		data: {
			option: "com_vikbooking",
			task: "add_roomdaynote",
			dt: ymd,
			idroom: roomid,
			subunit: subroomid,
			type: "custom",
			name: note_name,
			descr: note_descr,
			cdays: note_cdays
		}
	}).done(function(res) {
		// parse the JSON response that contains the note object for the passed date
		try {
			var stored_notes = JSON.parse(res);
			var counter = 0;
			for (var keyid in stored_notes) {
				if (!stored_notes.hasOwnProperty(keyid)) {
					continue;
				}
				var is_date_visible = (jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').length > 0);
				if (!vboRdayNotes.hasOwnProperty(keyid) && is_date_visible) {
					// we need to add the proper class to the cell for this note (if it's visible)
					jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').parent('td').removeClass('vbo-roomdaynote-empty').addClass('vbo-roomdaynote-full').find('i').attr('class', rdaynote_icn_full);
				}
				// update global object with the new notes in any case
				vboRdayNotes[keyid] = stored_notes[keyid];
				// update readable notes in custom data attribute
				if (counter < 1 && is_date_visible && note_name.length) {
					// we push just this name
					var current_cell_notes = jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').parent('td').attr('data-notes');
					var all_cell_notes = current_cell_notes && current_cell_notes.length ? current_cell_notes.split(', ') : new Array;
					all_cell_notes.push(note_name);
					jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').parent('td').attr('data-notes', all_cell_notes.join(', '));
				}
				// increase counter
				counter++;
			}
			// close modal
			hideVboDialogRdaynotes();
			// reset input fields
			jQuery('#vbo-newrdnote-name').val('');
			jQuery('#vbo-newrdnote-descr').val('');
			jQuery('#vbo-newrdnote-cdays').val('0').trigger('change');
		} catch (e) {
			console.log(res);
			alert('Invalid response');
			return false;
		}
	}).fail(function() {
		alert('Request failed');
	});
}

function vboRdayNoteCdaysCount() {
	var cdays = parseInt(jQuery('#vbo-newrdnote-cdays').val());
	var defymd = jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-ymd');
	var defreadymd = jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-readymd');
	defreadymd = !defreadymd || !defreadymd.length ? defymd : defreadymd;
	if (isNaN(cdays) || cdays < 1) {
		jQuery('.vbo-newrdnote-dayto-val').text(defreadymd);
		return;
	}
	// calculate target (until) date
	var targetdate = new Date(defymd);
	targetdate.setDate(targetdate.getDate() + cdays);
	var target_y = targetdate.getFullYear();
	var target_m = targetdate.getMonth() + 1;
	target_m = target_m < 10 ? '0' + target_m : target_m;
	var target_d = targetdate.getDate();
	target_d = target_d < 10 ? '0' + target_d : target_d;
	// display target date
	var display_target = target_y + '-' + target_m + '-' + target_d;
	// check if we can get the "read ymd property"
	if (jQuery('.vbo-tableaux-daycell[data-ymd="' + display_target + '"]').length) {
		display_target = jQuery('.vbo-tableaux-daycell[data-ymd="' + display_target + '"]').attr('data-readymd');
	}
	jQuery('.vbo-newrdnote-dayto-val').text(display_target);
}

jQuery(function() {
	
	jQuery('.vbo-tableaux-togglefullscreen').click(function() {
		if (jQuery('.vbo-tableaux-outer').hasClass('vbo-tableaux-fullscreen')) {
			jQuery(this).find('i').addClass('fa-expand').removeClass('fa-compress');
			jQuery('.vbo-tableaux-outer').removeClass('vbo-tableaux-fullscreen');
		} else {
			jQuery(this).find('i').addClass('fa-compress').removeClass('fa-expand');
			jQuery('.vbo-tableaux-outer').addClass('vbo-tableaux-fullscreen');
		}
		jQuery(this).blur();
	});

	jQuery('.vbo-tableaux-booking').click(function() {
		jQuery('.vbdialog-inner-tableaux').html('');
		var attrs = jQuery(this).data();
		var bid = attrs['bid'];
		if (!attrs.hasOwnProperty('party')) {
			var first = jQuery(this).closest('tr').find('.vbo-tableaux-booking[data-bid="'+bid+'"]').first();
			if (!first) {
				return false;
			}
			first.trigger('click');
			return;
		}
		// prepare data to be displayed:
		var ovcont = '';
		// find room name
		var rname = jQuery(this).closest('tr').find('.vbo-tableaux-roomname').text();
		if (rname && rname.length) {
			// try to find room index
			var rindex = '';
			if (jQuery(this).find('.vbo-tableaux-roomindex').length) {
				rindex = jQuery(this).find('.vbo-tableaux-roomindex').text();
				rindex = rindex.length ? ' ' + rindex : rindex;
			}
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBSEARCHRESROOM') + '</span><span class="vbo-tableaux-bookdet-val">' + rname + rindex + '</span></div>';
		}
		//
		if (attrs.hasOwnProperty('tnominative')) {
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBOCUSTOMERNOMINATIVE') + '</span><span class="vbo-tableaux-bookdet-val">'+attrs['tnominative']+'</span></div>';
		}
		if (attrs.hasOwnProperty('party')) {
			var guests = attrs['party'].split(';');
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBFORMADULTS') + '</span><span class="vbo-tableaux-bookdet-val">' + guests[0] + '</span></div>';
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBFORMCHILDREN') + '</span><span class="vbo-tableaux-bookdet-val">' + guests[1] + '</span></div>';
			if (guests.length > 2 && guests[2] > 0) {
				ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBO_PETS') + '</span><span class="vbo-tableaux-bookdet-val">' + guests[2] + '</span></div>';
			}
		}
		if (attrs.hasOwnProperty('checkin')) {
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBPICKUP') + '</span><span class="vbo-tableaux-bookdet-val">'+attrs['checkin']+'</span></div>';
		}
		if (attrs.hasOwnProperty('checkout')) {
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBRETURN') + '</span><span class="vbo-tableaux-bookdet-val">'+attrs['checkout']+'</span></div>';
		}
		if (attrs.hasOwnProperty('rextrainfos')) {
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('VBOEXTRASERVICES') + '</span><span class="vbo-tableaux-bookdet-val">'+attrs['rextrainfos']+'</span></div>';
		}
		if (attrs.hasOwnProperty('sprequest')) {
			ovcont += '<div class="vbo-tableaux-bookdet-entry"><span class="vbo-tableaux-bookdet-lbl">' + Joomla.JText._('ORDER_SPREQUESTS') + '</span><span class="vbo-tableaux-bookdet-val">'+attrs['sprequest']+'</span></div>';
		}
		jQuery('.vbdialog-inner-tableaux').html(ovcont);
		jQuery('#vbdialog-overlay').fadeIn();
		vbdialog_on = true;
	});

	jQuery(document).keydown(function(e) {
		if ( e.keyCode == 27 ) {
			if (vbdialog_on === true) {
				vbDialogClose();
			}
			if (vbodialogfests_on === true) {
				hideVboDialogFests();
			}
			if (vbodialogrdaynotes_on === true) {
				hideVboDialogRdaynotes();
			}
		}
	});

	jQuery(document).mouseup(function(e) {
		if (!vbdialog_on && !vbodialogfests_on && !vbodialogrdaynotes_on) {
			return false;
		}
		if (vbdialog_on) {
			var vbdialog_cont = jQuery(".vbdialog-inner");
			if (!vbdialog_cont.is(e.target) && vbdialog_cont.has(e.target).length === 0) {
				vbDialogClose();
			}
		}
		if (vbodialogfests_on) {
			var vbo_overlay_cont = jQuery(".vbo-modal-overlay-content-fests");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboDialogFests();
			}
		}
		if (vbodialogrdaynotes_on) {
			var vbo_overlay_cont = jQuery(".vbo-modal-overlay-content-roomdaynotes");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboDialogRdaynotes();
			}
		}
	});

	// fests
	jQuery(document.body).on("click", "td.vbo-tableaux-daycell.vbo-tableaux-festcell", function() {
		if (jQuery(this).hasClass('skip-tableaux-daycell-click')) {
			return;
		}
		var ymd = jQuery(this).attr('data-ymd');
		var daytitle = jQuery(this).attr('data-readymd');
		// front-end tableaux does not permit to add new fests
		if (!vboFests.hasOwnProperty(ymd)) {
			return;
		}
		vboRenderFests(ymd, daytitle);
	});

	// room-day notes
	jQuery(document.body).on("click", ".vbo-roomdaynote-display", function() {
		if (!jQuery(this).closest('.vbo-roomdaynote-trigger').length) {
			return;
		}
		var daytitle = new Array;
		var roomday_info = jQuery(this).closest('.vbo-roomdaynote-trigger').attr('data-roomday').split('_');
		// readable day
		var readymd = roomday_info[0];
		if (jQuery('.vbo-tableaux-daycell[data-ymd="' + roomday_info[0] + '"]').length) {
			readymd = jQuery('.vbo-tableaux-daycell[data-ymd="' + roomday_info[0] + '"]').attr('data-readymd');
		}
		daytitle.push(readymd);
		// room name
		if (jQuery('.vbo-tableaux-roomname[data-roomid="' + roomday_info[1] + '"]').length) {
			daytitle.push(jQuery('.vbo-tableaux-roomname[data-roomid="' + roomday_info[1] + '"]').text());
		}
		// set day title
		jQuery('.vbo-modal-overlay-content-head-roomdaynotes').find('h3').find('span.vbo-modal-roomdaynotes-dt').text(daytitle.join(', '));
		// populate current room day notes
		vboRenderRdayNotes(roomday_info[0], roomday_info[1], roomday_info[2], readymd);
		// display modal
		jQuery('.vbo-modal-overlay-block-roomdaynotes').fadeIn();
		vbodialogrdaynotes_on = true;
		//
	});

<?php
if ($this->past_days) {
	// let the tableaux horizontal scroll reach today's date
	?>
	let today_cell_el = document.querySelector('.vbo-tableaux-daycell.vbo-tableaux-todaycell');
	if (today_cell_el) {
		let first_cell_width = document.querySelector('.vbo-tableaux-firstrow')?.querySelector('.vbo-tableaux-emptycell')?.offsetWidth;
		let yscroll = today_cell_el.offsetLeft - first_cell_width - document.querySelector('.vbo-table-responsive.vbo-tableaux-tbl-wrap').offsetLeft
		document.querySelector('.vbo-table-responsive.vbo-tableaux-tbl-wrap').scrollLeft = (yscroll > 0 ? yscroll : 0);
	}
	<?php
}
?>
});
</script>
