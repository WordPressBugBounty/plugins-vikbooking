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

$roomids = $this->roomids;
$rooms = $this->rooms;
$rooms_busy = $this->rooms_busy;
$months = $this->months;
$fromts = $this->fromts;
$tots = $this->tots;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$vbo_app->loadDatePicker();

JHtml::fetch('script', VBO_ADMIN_URI . 'resources/js_upload/jquery.stickytableheaders.min.js');

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
$currencysymb = VikBooking::getCurrencySymb(true);

$month = VikRequest::getInt('month', 0, 'request');
$printtableaux = VikRequest::getInt('printtableaux', 0, 'request');

$colortags = VikBooking::loadBookingsColorTags();

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
$long_months_labels = $months_labels;
foreach ($months_labels as $i => $v) {
	$months_labels[$i] = function_exists('mb_substr') ? mb_substr($v, 0, 3, 'UTF-8') : substr($v, 0, 3);
}

?>
<div class="vbo-tableaux-container">
	<form name="adminForm" action="index.php?option=com_vikbooking&task=tableaux" method="post" id="adminForm">
		<div class="vbo-tableaux-months-wrap">
		<?php
		foreach ($months as $mdata) {
			?>
			<div class="vbo-tableaux-month-cont">
				<div class="vbo-tableaux-month-link<?php echo $month == $mdata['from'] ? ' vbo-tableaux-month-link-active' : ''; ?>">
					<span class="vbo-tableaux-selmonth" data-ts="<?php echo $mdata['from']; ?>" data-from="<?php echo date($df, $mdata['from']); ?>" data-to="<?php echo date($df, $mdata['to']); ?>"><?php echo $mdata['name']; ?></span>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<div id="filter-bar" class="btn-toolbar vbo-btn-toolbar vbo-tableaux-filters" style="width: 100%; display: inline-block;">
			<div class="btn-group pull-left" style="font-size: 12px;">
				<select id="roomsel" name="roomids[]" multiple="multiple">
				<?php
				foreach ($this->allrooms as $rid => $rdata) {
					?>
					<option value="<?php echo $rid; ?>"<?php echo in_array($rid, $roomids) ? ' selected="selected"' : ''; ?>><?php echo $rdata['name']; ?></option>
					<?php
				}

				/**
				 * Use the optgroup to separate rooms from categories in the
				 * drop down menu, only if allrooms > 1 e categories > 1.
				 * 
				 * @since 	1.13.5
				 */
				if (count($this->allrooms) > 1 && count($this->categories) > 1) {
					?>
					<optgroup label="<?php echo addslashes(JText::translate('VBOCATEGORYFILTER')); ?>">
					<?php
					foreach ($this->categories as $cat) {
						// we use negative values for the IDs of the categories
						$cat_id = ($cat['id'] - ($cat['id'] * 2));
						?>
						<option value="<?php echo $cat_id; ?>"><?php echo (in_array($cat['id'], $this->reqcats) ? '- ' : '') . $cat['name']; ?></option>
						<?php
					}
					?>
					</optgroup>
					<?php
				}
				//
				?>
				</select>
			</div>
			<div class="btn-group pull-left">
				<span style="font-size: 15px;">&nbsp;</span>
			</div>
			<div class="btn-group pull-left input-append">
				<input type="text" id="vbo-date-from" placeholder="<?php echo JText::translate('VBNEWSEASONONE'); ?>" value="<?php echo date($df, $fromts); ?>" size="14" name="tbxfromdate" onfocus="this.blur();" />
				<button type="button" class="btn" id="vbo-date-from-trig"><i class="icon-calendar"></i></button>
			</div>
			<div class="btn-group pull-left input-append">
				<input type="text" id="vbo-date-to" placeholder="<?php echo JText::translate('VBNEWSEASONTWO'); ?>" value="<?php echo date($df, $tots); ?>" size="14" name="tbxtodate" onfocus="this.blur();" />
				<button type="button" class="btn" id="vbo-date-to-trig"><i class="icon-calendar"></i></button>
			</div>
			<div class="btn-group pull-left">
				<span style="font-size: 15px;">&nbsp;</span>
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn"><i class="icon-search"></i> <?php echo JText::translate('VBAPPLY'); ?></button>
			</div>
			<div class="btn-group pull-right">
				<a href="javascript: void(0);" onclick="VBOCore.emitEvent('vbo-tool-permissions-modal-toggle');" class="vbo-perms-operators"><i class="vboicn-user-plus icn-nomargin"></i> <span><?php echo JText::translate('VBOPERMSOPERATORS'); ?></span></a>
			</div>
			<div class="btn-group pull-right">
				<a href="javascript: void(0);" onclick="vboSendPrintTableaux();" class="vbcsvexport"><?php VikBookingIcons::e('print'); ?> <span><?php echo JText::translate('VBOPRINT'); ?></span></a>
			</div>
		</div>
		<input type="hidden" name="month" id="vbo-month" value="<?php echo $month; ?>" />
		<input type="hidden" name="task" value="tableaux" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>

	<?php
	/**
	 * Render the permissions layout to manage the permissions of
	 * a specific tool, tableaux, for all the operators.
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	$layout_data = [
		'tool' => 'tableaux',
	];
	// render the permissions layout
	echo JLayoutHelper::render('operators.permissions', $layout_data, null, [
		'component' => 'com_vikbooking',
		'client' 	=> 'administrator',
	]);

	// count the cells per month
	$thead_info = array();
	$months_days = array();
	$nowinfo = getdate($fromts);
	while ($nowinfo[0] <= $tots) {
		$mkey = $nowinfo['mon'] . '_' . $nowinfo['year'];
		if (!isset($months_days[$mkey])) {
			$months_days[$mkey] = 0;
		}
		$months_days[$mkey]++;
		// push information for the table head
		array_push($thead_info, array(
			'mon'  => $long_months_labels[($nowinfo['mon'] - 1)],
			'mday' => $nowinfo['mday'],
			'wday' => $days_labels[$nowinfo['wday']],
			'year' => $nowinfo['year'],
			'ts'   => $nowinfo[0],
		));
		// next loop
		$nowinfo = getdate(mktime(0, 0, 0, $nowinfo['mon'], ($nowinfo['mday'] + 1), $nowinfo['year']));
	}

	// today
	$todaydt = date('Y-m-d');
	?>

	<div class="vbo-tableaux-tbl-container">
		<div class="vbo-table-responsive vbo-tableaux-tbl-wrap" id="vbo-tableaux-table-scroller">
			<table class="vbo-table vbo-tableaux-table">
				<thead class="vbo-tableaux-table-head" style="display: none;">
					<tr class="vbo-tableaux-table-head-row">
						<th class="vbo-tableaux-table-head-cell vbo-tableaux-table-head-cell-empty">&nbsp;</th>
					<?php
					foreach ($thead_info as $head_day) {
						?>
						<th class="vbo-tableaux-table-head-cell">
							<div class="vbo-tableaux-table-head-cell-monyear">
								<span><?php echo $head_day['mon'] . ' ' . $head_day['year']; ?></span>
							</div>
							<div class="vbo-tableaux-table-head-cell-wmday">
								<span><?php echo $head_day['wday'] . ' ' . $head_day['mday']; ?></span>
							</div>
						</th>
						<?php
					}
					?>
					</tr>
				</thead>
				<tbody>
					<tr class="vbo-tableaux-monthsrow">
						<td class="vbo-tableaux-emptycell">&nbsp;</td>
					<?php
					foreach ($months_days as $mkey => $totdays) {
						$mkeyparts = explode('_', $mkey);
						?>
						<td class="vbo-tableaux-monthcell" colspan="<?php echo $totdays; ?>"><?php echo $long_months_labels[($mkeyparts[0] - 1)] . ' ' . $mkeyparts[1]; ?></td>
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
						$monthclass = $nowinfo_ymd == $todaydt ? ' vbo-tableaux-todaycell' : $monthclass;
						$newmonth = $nowinfo['mon'];
						$read_day = $days_labels[$nowinfo['wday']] . ' ' . $nowinfo['mday'] . ' ' . $months_labels[($nowinfo['mon'] - 1)] . ' ' . $nowinfo['year'];
						$fest_class = isset($this->festivities[$nowinfo_ymd]) ? ' vbo-tableaux-festcell' : '';
						?>
						<td class="vbo-tableaux-daycell<?php echo $monthclass . $fest_class; ?>"<?php echo $printtableaux > 0 ? ' width="100' : ''; ?> data-ymd="<?php echo $nowinfo_ymd; ?>" data-readymd="<?php echo $read_day; ?>">
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
				$colortags_map = array();
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
					// extra class for single-unit rooms
					$room_extra_class = $rdata['units'] == 1 ? 'vbo-tableaux-booking-singleunit ' : '';
					?>
					<tr class="vbo-tableaux-roomrow">
						<td class="vbo-tableaux-roomname" data-roomid="<?php echo $rdata['id']; ?>"><?php echo $rdata['name']; ?></td>
					<?php
					$nowinfo = getdate($fromts);
					$bookbuffer = array();
					$prevbuffer = array();
					$positions = array();
					$newmonth = 0;
					while ($nowinfo[0] <= $tots) {
						$nowinfo_ymd = date('Y-m-d', $nowinfo[0]);
						$monthclass = !empty($newmonth) && $nowinfo['mon'] != $newmonth ? ' vbo-tableaux-newmonthcell' : '';
						$monthclass = $nowinfo_ymd == $todaydt ? ' vbo-tableaux-todaycell' : $monthclass;
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
							 * @since 	1.13.5 (J) - 1.3.5 (WP)
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
							<div class="<?php echo $room_extra_class; ?>vbo-tableaux-booking vbo-tableaux-booking-empty">
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

							// booking color tag (if enabled from overv View through cookie or session)
							if ($this->pbmode == 'tags' && !isset($colortags_map[$rbook['idorder']])) {
								$colortags_map[$rbook['idorder']] = VikBooking::applyBookingColorTag($rbook);
							}

							if (!in_array($rbook['idorder'], $prevbuffer)) {
								// first time we print the details for this booking - compose the content of the element
								$cellcont = '';

								// customer details
								if (!empty($rbook['first_name']) || !empty($rbook['last_name'])) {
									/**
									 * Check if we need to display a profile picture or a channel logo.
									 * 
									 * @since 	1.16.0 (J) - 1.6.0 (WP)
									 */
									$booking_avatar_src = null;
									$booking_avatar_alt = null;
									if (!empty($rbook['pic'])) {
										// customer profile picture
										$booking_avatar_src = strpos($rbook['pic'], 'http') === 0 ? $rbook['pic'] : VBO_SITE_URI . 'resources/uploads/' . $rbook['pic'];
										$booking_avatar_alt = basename($booking_avatar_src);
									} elseif (!empty($rbook['channel'])) {
										// channel logo
										$logo_helper = VikBooking::getVcmChannelsLogo($rbook['channel'], $get_istance = true);
										if ($logo_helper !== false) {
											$booking_avatar_src = $logo_helper->getSmallLogoURL();
											$booking_avatar_alt = $logo_helper->provenience;
										}
									}

									if (!empty($booking_avatar_src)) {
										// make sure the alt attribute is not too long in case of broken images
										$booking_avatar_alt = !empty($booking_avatar_alt) && strlen($booking_avatar_alt) > 15 ? '...' . substr($booking_avatar_alt, -12) : $booking_avatar_alt;
										// append booking avatar image
										$cellcont .= '<span class="vbo-tableaux-booking-avatar"><img src="' . $booking_avatar_src . '" class="vbo-tableaux-booking-avatar-img" ' . (!empty($booking_avatar_alt) ? 'alt="' . htmlspecialchars($booking_avatar_alt) . '" ' : '') . '/></span>';
									}

									// customer record
									$cellcont .= '<span class="vbo-tableaux-guest-name">' . $rbook['first_name'] . ' ' . $rbook['last_name'] . '</span>';
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
											$cellcont .= '<span class="vbo-tableaux-guest-name">' . implode(' ', $custvalues) . '</span>';;
										}
									}
									if (!$enoughinfo) {
										$cellcont .= '<span class="vbo-tableaux-guest-name">' . $rbook['idorder'] . '</span>';;
									}
								}

								// distinctive features
								if (!empty($rbook['indexes']) && isset($rooms_features_map[$rid])) {
									$bookindexes = explode(';', $rbook['indexes']);
									$roomindexes = explode(';', $rbook['roomids']);
									if (!isset($rooms_features_bookings[$rid.'_'.$rbook['idorder']])) {
										// the index to read of the feature depending on how many times this booking was printed (in case of multiple same rooms in one booking)
										$rooms_features_bookings[$rid.'_'.$rbook['idorder']] = 0;
									} else {
										// increment index for this room booking
										$rooms_features_bookings[$rid.'_'.$rbook['idorder']]++;
									}
									// seek for the index occurrence of this room in the list of rooms booked
									$count_pos = -1;
									$room_pos  = null;
									foreach ($roomindexes as $rk => $rv) {
										if ((int)$rv == (int)$rid) {
											$count_pos++;
											if ($count_pos == $rooms_features_bookings[$rid.'_'.$rbook['idorder']]) {
												$room_pos = $rk;
												break;
											}
										}
									}
									$nowfeatindex = isset($bookindexes[$room_pos]) ? $room_pos : null;
									if (!is_null($room_pos) && isset($bookindexes[$nowfeatindex]) && isset($rooms_features_map[$rid][$bookindexes[$nowfeatindex]])) {
										// get this room feature
										$cellcont .= '<span class="vbo-tableaux-roomindex">' . $rooms_features_map[$rid][$bookindexes[$nowfeatindex]] . '</span> ';
									}
								}

								// add invoice/receipt number if available (taken from booking color tag)
								if (isset($colortags_map[$rbook['idorder']]) && count($colortags_map[$rbook['idorder']])) {
									if (isset($colortags_map[$rbook['idorder']]['invoice_number'])) {
										// invoice number
										$invstyle = isset($colortags_map[$rbook['idorder']]['fontcolor']) ? ' style="color: '.$colortags_map[$rbook['idorder']]['fontcolor'].';"' : '';
										$cellcont .= ' <a class="vbo-tableaux-invlink"'.$invstyle.' href="'.VBO_SITE_URI.'helpers/invoices/generated/'.$colortags_map[$rbook['idorder']]['invoice_file_name'].'" target="_blank"><i class="'.VikBookingIcons::i('file-text').'"></i> '.$colortags_map[$rbook['idorder']]['invoice_number'].'</a>';
									} elseif (isset($colortags_map[$rbook['idorder']]['receipt_number'])) {
										// receipt number
										$cellcont .= ' <i class="'.VikBookingIcons::i('file-text-o').'" title="'.JText::translate('VBOFISCRECEIPT').' #'.$colortags_map[$rbook['idorder']]['receipt_number'].'"></i>';
									}
								}
							}

							// color tag styles
							$cont_styles = array();
							$span_styles = array();
							if (isset($colortags_map[$rbook['idorder']]) && count($colortags_map[$rbook['idorder']])) {
								$cont_styles[] = 'background-color: '.$colortags_map[$rbook['idorder']]['color'].';';
								if (isset($colortags_map[$rbook['idorder']]['fontcolor'])) {
									$cont_styles[] = 'color: '.$colortags_map[$rbook['idorder']]['fontcolor'].';';
									$span_styles[] = 'color: '.$colortags_map[$rbook['idorder']]['fontcolor'].';';
								}
							}

							// try to guess if this will be the last element of the day (single-unit rooms, check-out day with wider space because of no check-ins on the same day)
							$last_checkout_class = '';
							if ($rdata['units'] == 1 && strpos($contclass, 'checkout') !== false && !isset($room_bookings[($k + 1)])) {
								$last_checkout_class = 'vbo-tableaux-booking-checkout-last ';
							}
							?>
							<div class="<?php echo $room_extra_class . $last_checkout_class; ?>vbo-tableaux-booking vbo-tableaux-booking-<?php echo $contclass . $shortstaycls . ' vbo-' . $pos; ?>"<?php echo count($cont_styles) ? ' style="'.implode('', $cont_styles).'"' : ''; ?> data-bid="<?php echo $rbook['idorder']; ?>">
								<span<?php echo count($span_styles) ? ' style="'.implode('', $span_styles).'"' : ''; ?>><?php echo $cellcont; ?></span>
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

<div class="vbo-modal-overlay-block vbo-modal-overlay-block-fests">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-fests">
		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-fests">
			<h3><?php VikBookingIcons::e('star'); ?> <span></span></h3>
		</div>
		<div class="vbo-modal-overlay-content-body">
			<div class="vbo-overlay-fests-list"></div>
			<div class="vbo-overlay-fests-addnew" data-ymd="">
				<h4><?php echo JText::translate('VBOADDCUSTOMFESTTODAY'); ?></h4>
				<div class="vbo-overlay-fests-addnew-elem">
					<label for="vbo-newfest-name"><?php echo JText::translate('VBPVIEWPLACESONE'); ?></label>
					<input type="text" id="vbo-newfest-name" value="" />
				</div>
				<div class="vbo-overlay-fests-addnew-elem">
					<label for="vbo-newfest-descr"><?php echo JText::translate('VBPLACEDESCR'); ?></label>
					<textarea id="vbo-newfest-descr"></textarea>
				</div>
				<div class="vbo-overlay-fests-addnew-save">
					<button type="button" class="btn btn-success" onclick="vboAddFest(this);"><?php echo JText::translate('VBSAVE'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="vbo-modal-overlay-block vbo-modal-overlay-block-roomdaynotes">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-roomdaynotes">
		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-roomdaynotes">
			<h3><?php VikBookingIcons::e('exclamation-circle'); ?> <span></span></h3>
		</div>
		<div class="vbo-modal-overlay-content-body">
			<div class="vbo-modal-roomdaynotes-list"></div>
			<div class="vbo-modal-roomdaynotes-addnew" data-readymd="" data-ymd="" data-roomid="" data-subroomid="">
				<h4><?php echo JText::translate('VBOADDCUSTOMFESTTODAY'); ?></h4>
				<div class="vbo-modal-roomdaynotes-addnew-elem">
					<label for="vbo-newrdnote-name"><?php echo JText::translate('VBPVIEWPLACESONE'); ?></label>
					<input type="text" id="vbo-newrdnote-name" value="" />
				</div>
				<div class="vbo-modal-roomdaynotes-addnew-elem">
					<label for="vbo-newrdnote-descr"><?php echo JText::translate('VBPLACEDESCR'); ?></label>
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
					<button type="button" class="btn btn-success" onclick="vboAddRoomDayNote(this);"><?php echo JText::translate('VBSAVE'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<a class="vbo-basenavuri-details" href="index.php?option=com_vikbooking&task=editorder&goto=tableaux&cid[]=%d" style="display: none;"></a>
<a class="vbo-basenavuri-edit" href="index.php?option=com_vikbooking&task=editbusy&goto=tableaux&cid[]=%d" style="display: none;"></a>

<script type="text/javascript">
var vboFests = <?php echo json_encode($this->festivities); ?>;
var vboRdayNotes = <?php echo json_encode($this->rdaynotes); ?>;
var vbodialog_on = false;
var vbodialogfests_on = false;
var vbodialogrdaynotes_on = false;

/**
 * Fests dialog
 */
function hideVboDialogFests() {
	if (vbodialogfests_on === true) {
		jQuery(".vbo-modal-overlay-block-fests").fadeOut(400, function () {
			jQuery(".vbo-modal-overlay-content-fests").show();
		});
		vbodialogfests_on = false;
	}
}

/**
 * Room-day-notes dialog
 */
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

/* Hover Tooltip */
var hovtimer;
var hovtip = false;
var vboMessages = {
	loadingTip: "<?php echo addslashes(JText::translate('VIKLOADING')); ?>",
	numRooms: "<?php echo addslashes(JText::translate('VBEDITORDERROOMSNUM')); ?>",
	numAdults: "<?php echo addslashes(JText::translate('VBEDITORDERADULTS')); ?>",
	numNights: "<?php echo addslashes(JText::translate('VBDAYS')); ?>",
	checkinLbl: "<?php echo addslashes(JText::translate('VBPICKUPAT')); ?>",
	checkoutLbl: "<?php echo addslashes(JText::translate('VBRELEASEAT')); ?>",
	numChildren: "<?php echo addslashes(JText::translate('VBEDITORDERCHILDREN')); ?>",
	totalAmount: "<?php echo addslashes(JText::translate('VBEDITORDERNINE')); ?>",
	totalPaid: "<?php echo addslashes(JText::translate('VBPEDITBUSYTOTPAID')); ?>",
	currencySymb: "<?php echo $currencysymb; ?>"
};

function registerHoveringTooltip(that) {
	if (hovtip) {
		return false;
	}
	if (hovtimer) {
		clearTimeout(hovtimer);
		hovtimer = null;
	}
	var elem = jQuery(that);
	var cellheight = elem.outerHeight();
	hovtimer = setTimeout(function() {
		hovtip = true;
		jQuery(
			"<div class=\"vbo-overview-tipblock\">"+
				"<div class=\"vbo-overview-tipinner\"><span class=\"vbo-overview-tiploading\">"+vboMessages.loadingTip+"</span></div>"+
				"<div class=\"vbo-overview-tipexpander\" style=\"display: none;\"><div class=\"vbo-overview-expandtoggle\"><i class=\"<?php echo VikBookingIcons::i('expand'); ?>\"></i></div></div>"+
			"</div>"
		).appendTo(elem);
		jQuery(".vbo-overview-tipblock").css("bottom", "+="+cellheight);
		loadTooltipBookings(elem.attr('data-bid'));
	}, 900);
}

function unregisterHoveringTooltip() {
	clearTimeout(hovtimer);
	hovtimer = null;
}

function adjustHoveringTooltip() {
	setTimeout(function() {
		var difflim = 35;
		var otop = jQuery(".vbo-overview-tipblock").offset().top;
		if (otop < difflim) {
			jQuery(".vbo-overview-tipblock").css("bottom", "-="+(difflim - otop));
		} else {
			// check top position from table
			var tipheight = jQuery(".vbo-overview-tipblock").outerHeight();
			var blocktop = jQuery(".vbo-overview-tipblock").parent().offset().top;
			var tabletop = jQuery(".vbo-tableaux-table").offset().top;
			if ((blocktop - tabletop) < tipheight) {
				// tooltip is going under the table, move it down the container
				jQuery(".vbo-overview-tipblock").css("bottom", "-"+tipheight+'px');
			}
			// check left position from second cell
			var tipwidth = jQuery(".vbo-overview-tipblock").outerWidth();
			var cellscndleft = jQuery('.vbo-tableaux-table').find('tr td').first().next().offset().left;
			var blockleft = jQuery('.vbo-overview-tipblock').parent().offset().left;
			if (tipwidth > (blockleft - cellscndleft)) {
				// tooltip should start from left: 0 going to the right
				jQuery(".vbo-overview-tipblock").css("left", "0px");
			}
		}
	}, 100);
}

function hideVboTooltip() {
	jQuery('.vbo-overview-tipblock').remove();
	hovtip = false;
}

function loadTooltipBookings(bid) {
	if (!bid || bid === undefined || !bid.length) {
		hideVboTooltip();
		return false;
	}
	// ajax request
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=getbookingsinfo'); ?>",
		data: {
			tmpl: "component",
			idorders: bid
		}
	}).done(function(res) {
		try {
			var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
			jQuery('.vbo-overview-tiploading').remove();
			var container = jQuery('.vbo-overview-tipinner');
			jQuery(obj_res).each(function(k, v) {
				// get base navigation URIs
				var base_uri_details = jQuery('.vbo-basenavuri-details').attr('href');
				var base_uri_edit = jQuery('.vbo-basenavuri-edit').attr('href');

				// build content
				var bcont = "<div class=\"vbo-overview-tip-bookingcont\">";
				bcont += "<div class=\"vbo-overview-tip-bookingcont-left\">";
				bcont += "<div class=\"vbo-overview-tip-bid\"><span class=\"vbo-overview-tip-lbl\">ID <span class=\"vbo-overview-tip-lbl-innerleft\"><a href=\"" + base_uri_edit.replace('%d', v.id) + "\" target=\"_blank\"><i class=\"<?php echo VikBookingIcons::i('edit'); ?>\"></i></a></span></span><span class=\"vbo-overview-tip-cnt\">"+v.id+"</span></div>";
				bcont += "<div class=\"vbo-overview-tip-bstatus\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBPVIEWORDERSEIGHT')); ?></span><span class=\"vbo-overview-tip-cnt\"><div class=\"label "+(v.status == 'confirmed' ? 'label-success' : 'label-warning')+"\">"+v.status_lbl+"</div></span></div>";
				bcont += "<div class=\"vbo-overview-tip-bdate\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBPVIEWORDERSONE')); ?></span><span class=\"vbo-overview-tip-cnt\"><a href=\"" + base_uri_details.replace('%d', v.id) + "\" target=\"_blank\">"+v.ts+"</a></span></div>";
				bcont += "</div>";
				bcont += "<div class=\"vbo-overview-tip-bookingcont-right\">";
				bcont += "<div class=\"vbo-overview-tip-bcustomer\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBOCUSTOMER')); ?></span><span class=\"vbo-overview-tip-cnt\">"+v.cinfo+"</span></div>";
				if (v.roomsnum > 1) {
					bcont += "<div class=\"vbo-overview-tip-brooms\"><span class=\"vbo-overview-tip-lbl\">"+vboMessages.numRooms+"</span><span class=\"vbo-overview-tip-cnt\">"+v.room_names+"</span></div>";
				}
				bcont += "<div class=\"vbo-overview-tip-bguests\"><span class=\"vbo-overview-tip-lbl\">"+vboMessages.numNights+"</span><span class=\"vbo-overview-tip-cnt hasTooltip\" title=\""+vboMessages.checkinLbl+" "+v.checkin+" - "+vboMessages.checkoutLbl+" "+v.checkout+"\">"+v.days+", "+vboMessages.numAdults+": "+v.tot_adults+(v.tot_children > 0 ? ", "+vboMessages.numChildren+": "+v.tot_children : "")+"</span></div>";
				if (v.hasOwnProperty('rindexes')) {
					for (var rindexk in v.rindexes) {
						if (v.rindexes.hasOwnProperty(rindexk)) {
							bcont += "<div class=\"vbo-overview-tip-brindexes\"><span class=\"vbo-overview-tip-lbl\">"+rindexk+"</span><span class=\"vbo-overview-tip-cnt\">"+v.rindexes[rindexk]+"</span></div>";
						}
					}
				}
				if (v.hasOwnProperty('channelimg')) {
					bcont += "<div class=\"vbo-overview-tip-bprovenience\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBPVIEWORDERCHANNEL')); ?></span><span class=\"vbo-overview-tip-cnt\">"+v.channelimg+"</span></div>";
				}
				bcont += "<div class=\"vbo-overview-tip-bookingcont-total\">";
				bcont += "<div class=\"vbo-overview-tip-btot\"><span class=\"vbo-overview-tip-lbl\">"+vboMessages.totalAmount+"</span><span class=\"vbo-overview-tip-cnt\">"+vboMessages.currencySymb+" "+v.format_tot+"</span></div>";
				if (v.totpaid > 0.00) {
					bcont += "<div class=\"vbo-overview-tip-btot\"><span class=\"vbo-overview-tip-lbl\">"+vboMessages.totalPaid+"</span><span class=\"vbo-overview-tip-cnt\">"+vboMessages.currencySymb+" "+v.format_totpaid+"</span></div>";
				}
				var getnotes = v.adminnotes;
				if (getnotes !== null && getnotes.length) {
					bcont += "<div class=\"vbo-overview-tip-notes\"><span class=\"vbo-overview-tip-lbl\"><span class=\"vbo-overview-tip-notes-inner\"><i class=\"vboicn-info hasTooltip\" title=\""+getnotes+"\"></i></span></span></div>";
				}
				bcont += "</div>";
				bcont += "</div>";
				bcont += "</div>";
				container.append(bcont);
				jQuery('.vbo-overview-tipexpander').show();
			});
			// adjust the position so that it won't go under other contents
			adjustHoveringTooltip()
			//
		} catch(err) {
			// restore
			hideVboTooltip();
			// display error
			console.error('could not parse JSON response', err, res);
			alert('Could not parse JSON response');
		}
	}).fail(function(err) { 
		// restore
		hideVboTooltip();
		// display error
		console.error(err);
		alert(err.responseText);
	});
}

function vboSendPrintTableaux() {
	jQuery('#adminForm').attr('target', '_blank').append('<input type="hidden" class="vbo-tableaux-rmafterprint" name="tmpl" value="component" /><input type="hidden" class="vbo-tableaux-rmafterprint" name="printtableaux" value="1" />').submit();
	setTimeout(function() {
		jQuery('#adminForm').attr('target', '_self');
		jQuery('.vbo-tableaux-rmafterprint').remove();
	}, 500);
}

/**
 * Fests
 */
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
			fests_html += '	<div class="vbo-fest-cmds">';
			fests_html += '		<button type="button" class="btn btn-danger" onclick="vboRemoveFest(\'' + day + '\', \'' + i + '\', \'' + fest['type'] + '\', this);"><?php VikBookingIcons::e('trash-alt'); ?></button>';
			fests_html += '	</div>';
			fests_html += '</div>';
		}
	}
	// update ymd key for the selected date, useful for adding new fests
	jQuery('.vbo-overlay-fests-addnew').attr('data-ymd', day);
	// set content and display modal
	jQuery('.vbo-overlay-fests-list').html(fests_html);
	jQuery('.vbo-modal-overlay-block-fests').fadeIn();
	vbodialogfests_on = true;
}

function vboRemoveFest(day, index, fest_type, that) {
	if (!confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')) {
		return false;
	}
	var elem = jQuery(that);
	// make the AJAX request to the controller to remove this fest from the DB
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=remove_fest'); ?>",
		data: {
			tmpl: "component",
			dt: day,
			ind: index,
			type: fest_type
		}
	}).done(function(res) {
		if (res.indexOf('e4j.ok') >= 0) {
			// delete fest also from the json-decode array of objects
			if (vboFests[day] && vboFests[day]['festinfo']) {
				// use splice to remove the desired index from array, or delete would not make the length of the array change
				vboFests[day]['festinfo'].splice(index, 1);
				// re-build indexes of delete buttons, fundamental for removing the right index at next click
				vboRenderFests(day);
				if (!vboFests[day]['festinfo'].length) {
					// delete also this date object from fests
					delete vboFests[day];
					// no more fests, remove the class for this date from all cells
					jQuery('td.vbo-tableaux-daycell[data-ymd="'+day+'"]').removeClass('vbo-tableaux-festcell');
				}
			}
			elem.closest('.vbo-overlay-fest-details').remove();
		} else {
			console.log(res);
			alert('Invalid response');
		}
	}).fail(function() {
		alert('Request failed');
	});
}

function vboAddFest(that) {
	var elem = jQuery(that);
	var ymd = elem.closest('.vbo-overlay-fests-addnew').attr('data-ymd');
	var fest_name = jQuery('#vbo-newfest-name').val();
	var fest_descr = jQuery('#vbo-newfest-descr').val();
	if (!fest_name.length) {
		return false;
	}
	// make the AJAX request to the controller to add this fest to the DB
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=add_fest'); ?>",
		data: {
			tmpl: "component",
			dt: ymd,
			type: "custom",
			name: fest_name,
			descr: fest_descr
		}
	}).done(function(res) {
		// parse the JSON response that contains the fest object for the passed date
		try {
			var stored_fest = JSON.parse(res);
			if (!vboFests.hasOwnProperty(stored_fest['dt'])) {
				// we need to add the proper class to all cells to show that there is a fest
				jQuery('td.vbo-tableaux-daycell[data-ymd="'+stored_fest['dt']+'"]').addClass('vbo-tableaux-festcell');
			}
			vboFests[stored_fest['dt']] = stored_fest;
			hideVboDialogFests();
			// reset input fields
			jQuery('#vbo-newfest-name').val('');
			jQuery('#vbo-newfest-descr').val('');
		} catch (e) {
			console.log(res);
			alert('Invalid response');
			return false;
		}
	}).fail(function() {
		alert('Request failed');
	});
}
//

/**
 * Room-day notes
 */
var rdaynote_icn_full = '<?php echo VikBookingIcons::i('sticky-note', 'vbo-roomdaynote-display'); ?>';
var rdaynote_icn_empty = '<?php echo VikBookingIcons::i('far fa-sticky-note', 'vbo-roomdaynote-display'); ?>';
var rooms_features_map = JSON.parse('<?php echo isset($rooms_features_map) && count($rooms_features_map) ? json_encode($rooms_features_map) : json_encode(array()); ?>');

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
				notes_html += '	<div class="vbo-fest-cmds vbo-modal-roomdaynotes-note-cmds">';
				notes_html += '		<button type="button" class="btn btn-danger" onclick="vboRemoveRdayNote(\'' + i + '\', \'' + day + '\', \'' + idroom + '\', \'' + subunit_index + '\', \'' + note_data['type'] + '\', this);"><?php VikBookingIcons::e('trash-alt'); ?></button>';
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
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=add_roomdaynote'); ?>",
		data: {
			tmpl: "component",
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

function vboRdayNotesAvailable(day, idroom) {
	/**
	 * We check whether a room has notes at parent or subunit level for the given date.
	 */
	var base_key = day + '_' + idroom + '_';
	var notes_available = false;
	for (var k in vboRdayNotes) {
		if (!vboRdayNotes.hasOwnProperty(k) || k.indexOf(base_key) < 0) {
			continue;
		}
		// notes found for this room, either parent or sub-unit
		if (vboRdayNotes[k]['info'] && vboRdayNotes[k]['info'].length) {
			notes_available = true;
			break;
		}
	}

	return notes_available;
}

function vboRemoveRdayNote(index, day, idroom, subunit, note_type, that) {
	if (!confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')) {
		return false;
	}
	var elem = jQuery(that);
	// make the AJAX request to the controller to remove this note from the DB
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=remove_roomdaynote'); ?>",
		data: {
			tmpl: "component",
			dt: day,
			idroom: idroom,
			subunit: subunit,
			ind: index,
			type: note_type
		}
	}).done(function(res) {
		if (res.indexOf('e4j.ok') >= 0) {
			var keyid = day + '_' + idroom + '_' + subunit;
			var glob_keyid = day + '_' + idroom + '_0';
			// delete note also from the json-decode array of objects
			if (vboRdayNotes[keyid] && vboRdayNotes[keyid]['info']) {
				// get the original note name
				var old_name = vboRdayNotes[keyid]['info'][index]['name'];
				// use splice to remove the desired index from array, or delete would not make the length of the array change
				vboRdayNotes[keyid]['info'].splice(index, 1);
				// re-build indexes of delete buttons, fundamental for removing the right index at next click (reload always at room-level)
				vboRenderRdayNotes(day, idroom, '0', null);
				if (!vboRdayNotes[keyid]['info'].length && !vboRdayNotesAvailable(day, idroom)) {
					// delete also this date object from notes
					delete vboRdayNotes[keyid];
					// no more notes, update the proper class attribute for this cell (should be visible)
					if (jQuery('.vbo-roomdaynote-trigger[data-roomday="' + glob_keyid + '"]').length) {
						jQuery('.vbo-roomdaynote-trigger[data-roomday="' + glob_keyid + '"]').parent('td').removeClass('vbo-roomdaynote-full').addClass('vbo-roomdaynote-empty').attr('data-notes', '').find('i').attr('class', rdaynote_icn_empty);
					}
				} else if (old_name && old_name.length) {
					// try to adjust the readable notes in custom data attribute
					var current_cell_notes = jQuery('.vbo-roomdaynote-trigger[data-roomday="' + glob_keyid + '"]').parent('td').attr('data-notes');
					var all_cell_notes = current_cell_notes && current_cell_notes.length ? current_cell_notes.split(', ') : new Array;
					var new_cell_notes = new Array;
					for (var i = 0; i < all_cell_notes.length; i++) {
						if (all_cell_notes[i].indexOf(old_name) < 0) {
							new_cell_notes.push(all_cell_notes[i]);
						}
					}
					jQuery('.vbo-roomdaynote-trigger[data-roomday="' + glob_keyid + '"]').parent('td').attr('data-notes', new_cell_notes.join(', '));
				}
			}
			elem.closest('.vbo-modal-roomdaynotes-note-details').remove();
		} else {
			console.log(res);
			alert('Invalid response');
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
//

/**
 * Tells whether an element can fit entirely in the viewport.
 * Example: if a table is too high to fit in the screen.
 * If false, it means that scrolling will always be needed.
 */
function vboFitsInViewport(selector) {
	var elemHeight = jQuery(selector).outerHeight();
	var maxHeight  = jQuery(window).height();

	return elemHeight < maxHeight;
}

// tableaux scrolling
var fittingTableaux = null;
var tableauxStickySet = false;

/**
 * Throttle guarantees a constant flow of events at a given time interval,
 * but it runs immediately when the event takes place. We rather need a
 * debounce technique to group a flurry of events into one single event.
 * This is useful for listening to the scrolling event of the table, actually
 * the DIV that wraps it with overflow, and setting the sticky headers.
 */
function vboDebounceScroll(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) {
			func.apply(context, args);
		}
	}
}
/**
 * We do not need a throttle technique, but this method would
 * throttle the scroll event rather than debouncing it.
 */
function vboThrottleScroll(method, delay) {
	var time = Date.now();
	return function() {
		if ((time + delay - Date.now()) < 0) {
			method();
			time = Date.now();
		}
	}
}

/**
 * This will fire during the throttle of the scrolling event.
 */
function vboHandleScroll() {
	if (fittingTableaux === null) {
		fittingTableaux = vboFitsInViewport('.vbo-tableaux-table');
	}
	if (fittingTableaux) {
		// no sticky table headers needed, remove the listener as it is not needed
		document.getElementById('vbo-tableaux-table-scroller').removeEventListener('scroll', vboDebounceScroll(vboHandleScroll, 1000));
		return;
	}
	// we have scrolled (vertically or horizontally), update the position of the sticky table head
	if (tableauxStickySet) {
		// destroy sticky table head
		jQuery('table.vbo-tableaux-table').stickyTableHeaders('destroy');
	}
	// re-build sticky head
	jQuery('table.vbo-tableaux-table').stickyTableHeaders({cacheHeaderHeight: true, fixedOffset: jQuery('.navbar')});
	tableauxStickySet = true;
}

jQuery(function() {
	// add listener to scroll event on the main table
	document.getElementById('vbo-tableaux-table-scroller').addEventListener('scroll', vboDebounceScroll(vboHandleScroll, 1000));

	jQuery("#roomsel, .vbo-perm-roomsel").select2({placeholder: '<?php echo addslashes(JText::translate('VBCOUPONALLVEHICLES')); ?>', width: "300px", allowClear: true});
	
	jQuery('#vbo-date-from').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		<?php echo ($this->mindate > 0 ? 'minDate: "'.date(str_replace('%', '', $nowdf), $this->mindate).'", ' : '').($this->maxdate > 0 ? 'maxDate: "'.date(str_replace('%', '', $nowdf), $this->maxdate).'", ' : ''); ?>
		onSelect: function( selectedDate ) {
			jQuery('#vbo-date-to').datepicker('option', 'minDate', selectedDate);
			jQuery('#vbo-month').val('');
		}
	});

	jQuery('#vbo-date-to').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		<?php echo ($this->mindate > 0 ? 'minDate: "'.date(str_replace('%', '', $nowdf), $this->mindate).'", ' : '').($this->maxdate > 0 ? 'maxDate: "'.date(str_replace('%', '', $nowdf), $this->maxdate).'", ' : ''); ?>
		onSelect: function( selectedDate ) {
			jQuery('#vbo-date-from').datepicker('option', 'maxDate', selectedDate);
			jQuery('#vbo-month').val('');
		}
	});

	jQuery('#vbo-date-from-trig, #vbo-date-to-trig').click(function() {
		var jdp = jQuery(this).prev('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});

	jQuery('.vbo-tableaux-selmonth').click(function() {
		var xts = jQuery(this).attr('data-ts');
		var xfrom = jQuery(this).attr('data-from');
		var xto = jQuery(this).attr('data-to');
		jQuery('#vbo-month').val(xts);
		jQuery('#vbo-date-from').val(xfrom);
		jQuery('#vbo-date-to').val(xto);
		jQuery('#adminForm').submit();
	});

	/* Expand/Collapse tooltip */
	jQuery(document.body).on("click", ".vbo-overview-expandtoggle", function() {
		jQuery(this).closest('.vbo-overview-tipblock').toggleClass('vbo-overview-tipblock-expanded');
	});
	/* ----------------------- */

	/* Hover Tooltip */
	jQuery('.vbo-tableaux-booking-checkin, .vbo-tableaux-booking-checkout, .vbo-tableaux-booking-stay').hover(function() {
		registerHoveringTooltip(this);
	}, unregisterHoveringTooltip);

	jQuery(document).keydown(function(e) {
		if (e.keyCode == 27) {
			if (hovtip === true) {
				hideVboTooltip();
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
		if (!hovtip && !vbodialogfests_on && !vbodialogrdaynotes_on) {
			return false;
		}
		if (hovtip) {
			var vbo_overlay_cont = jQuery(".vbo-overview-tipblock");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboTooltip();
				return true;
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
	jQuery(document.body).on("click", "td.vbo-tableaux-daycell", function() {
		if (jQuery(this).hasClass('skip-tableaux-daycell-click')) {
			return;
		}
		var ymd = jQuery(this).attr('data-ymd');
		var daytitle = jQuery(this).attr('data-readymd');
		if (jQuery(this).hasClass('vbo-tableaux-festcell')) {
			// cell has fests
			if (!vboFests.hasOwnProperty(ymd)) {
				return;
			}
			vboRenderFests(ymd, daytitle);
		} else {
			// let the admin create a new fest
			// set day title
			jQuery('.vbo-modal-overlay-content-fests').find('h3').find('span').text(daytitle);
			// update ymd key for the selected date, useful for adding new fests
			jQuery('.vbo-overlay-fests-addnew').attr('data-ymd', ymd);
			// unset content and display modal for just adding a new fest
			jQuery('.vbo-overlay-fests-list').html('');
			jQuery('.vbo-modal-overlay-block-fests').fadeIn();
			vbodialogfests_on = true;
		}
	});
	//

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
		jQuery('.vbo-modal-overlay-content-head-roomdaynotes').find('h3').find('span').text(daytitle.join(', '));
		// populate current room day notes
		vboRenderRdayNotes(roomday_info[0], roomday_info[1], roomday_info[2], readymd);
		// display modal
		jQuery('.vbo-modal-overlay-block-roomdaynotes').fadeIn();
		vbodialogrdaynotes_on = true;
		//
	});
	//

	// use sticky headers only if the table is longer than the max viewport height
	fittingTableaux = vboFitsInViewport('.vbo-tableaux-table');
	if (!fittingTableaux) {
		// display table head
		jQuery('.vbo-tableaux-table-head').show();
		// set sticky head for the vertical scrolling
		jQuery('table.vbo-tableaux-table').stickyTableHeaders({cacheHeaderHeight: true, fixedOffset: jQuery('.navbar')});
		tableauxStickySet = true;
	} else {
		// hide table head
		jQuery('.vbo-tableaux-table-head').hide();
	}

<?php
if ($printtableaux > 0) {
	?>
	window.print();
	<?php
}
?>
});
</script>
