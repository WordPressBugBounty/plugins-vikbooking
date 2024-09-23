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

$all_rooms = $this->all_rooms;
$categories = $this->categories;
$roomrows = $this->roomrows;
$seasoncal_nights = $this->seasons_cal_nights;
$seasons_cal = $this->seasons_cal;
$tsstart = $this->tsstart;
$roomrates = $this->roomrates;

// JS lang defs
JText::script('VBO_BOOKNOW');
JText::script('VBPVIEWORDERSTHREE');
JText::script('VBEDITORDERTHREE');
JText::script('VBDAYS');
JText::script('VBDAY');
JText::script('VBMAILADULTS');
JText::script('VBMAILADULT');
JText::script('VBMAILCHILDREN');
JText::script('VBMAILCHILD');
JText::script('VBO_MISSING_SUBUNIT');
JText::script('VBRATESOVWRATESCALCULATORCALC');
JText::script('VBRATESOVWRATESCALCULATORCALCING');

$document = JFactory::getDocument();

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$vbo_app->loadDatePicker();

$pdebug = VikRequest::getint('e4j_debug', '', 'request');

$currencysymb = VikBooking::getCurrencySymb();
$vbo_df = VikBooking::getDateFormat();
$datesep = VikBooking::getDateSeparator();
$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');
$juidf = $vbo_df == "%d/%m/%Y" ? 'dd/mm/yy' : ($vbo_df == "%m/%d/%Y" ? 'mm/dd/yy' : 'yy/mm/dd');

$mb_support  = function_exists('mb_substr');
$short_wdays = [
	JText::translate('VBSUNDAY'),
	JText::translate('VBMONDAY'),
	JText::translate('VBTUESDAY'),
	JText::translate('VBWEDNESDAY'),
	JText::translate('VBTHURSDAY'),
	JText::translate('VBFRIDAY'),
	JText::translate('VBSATURDAY'),
];
$short_mons  = [
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
	JText::translate('VBMONTHTWELVE'),
];
foreach ($short_wdays as $k => $v) {
	if ($mb_support) {
		$short_wdays[$k] = mb_substr($v, 0, 3, 'UTF-8');
	} else {
		$short_wdays[$k] = substr($v, 0, 3);
	}
}
foreach ($short_mons as $k => $v) {
	if ($mb_support) {
		$short_mons[$k] = mb_substr($v, 0, 3, 'UTF-8');
	} else {
		$short_mons[$k] = substr($v, 0, 3);
	}
}
$json_short_wdays = json_encode($short_wdays);
$json_short_mons = json_encode($short_mons);

$document->addScriptDeclaration(
<<<JS
var vboMapWdays = $json_short_wdays;
var vboMapMons = $json_short_mons;
JS
);

$price_types_show = true;
$los_show = true;
$cookie = JFactory::getApplication()->input->cookie;
$cookie_tab = $cookie->get('vboRovwRab', 'cal', 'string');
$collapse_status = $cookie->get('vboRovwColl', 0, 'int');

//Prepare modal
echo $vbo_app->getJmodalScript();
echo $vbo_app->getJmodalHtml('vbo-vcm-rates-res', JText::translate('VBOVCMRATESRES'), '', 'width: 90%; height: 80%; margin-left: -45%; top: 10% !important;');
//end Prepare modal

// access room helper to detect LOS rates
$room_helper = VBORoomHelper::getInstance();

?>

<div class="vbo-ratesoverview-top-outer">
	<div class="vbo-ratesoverview-top-outer-inner vbo-toggle-small">
		<?php echo $vbo_app->printYesNoButtons('vbo_collapse', JText::translate('VBYES'), JText::translate('VBNO'), (!$collapse_status ? 1 : 0), 1, 0, "vboToggleCollapse();"); ?>
		<span onclick="jQuery(this).parent().find('input').trigger('click');"><?php echo JText::translate('VBO_EXPAND'); ?></span>
	</div>
</div>

<div class="vbo-ratesoverview-top-container<?php echo $collapse_status ? ' collapsed' : ''; ?>">
	<div class="vbo-ratesoverview-roomsel-block">
		<form method="get" action="index.php?option=com_vikbooking" name="vboratesovwform">
		<input type="hidden" name="option" value="com_vikbooking" />
			<input type="hidden" name="task" value="ratesoverv" />
			<div class="vbo-ratesoverview-roomsel-entry vbo-ratesoverview-roomsel-entry-chrooms">
				<label for="roomsel"><?php VikBookingIcons::e('bed'); ?> <?php echo JText::translate('VBPVIEWORDERSTHREE'); ?></label>
				<select name="cid[]" <?php echo count($all_rooms) > 1 ? 'multiple="multiple"' : 'onchange="document.vboratesovwform.submit();"' ?> id="roomsel" style="min-width: 160px; max-width: 250px;">
		<?php
		foreach ($all_rooms as $room) {
			?>
				<option value="<?php echo $room['id']; ?>"<?php echo in_array($room['id'], $this->req_room_ids) ? ' selected="selected"' : ''; ?>><?php echo $room['name']; ?></option>
			<?php
		}

		/**
		 * Use the optgroup to separate rooms from categories in the
		 * drop down menu, only if all_rooms > 1 e categories > 1.
		 * 
		 * @since 	1.13
		 */
		if (count($all_rooms) > 1 && count($categories) > 1) {
			?>
			<optgroup label="<?php echo addslashes(JText::translate('VBOCATEGORYFILTER')); ?>">
			<?php
			foreach ($categories as $cat) {
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
				<button type="button" class="btn vbo-config-btn" title="<?php echo htmlspecialchars(JText::translate('VBODRIVERLOAD')); ?>" onclick="document.vboratesovwform.submit();"><i class="vboicn-loop2"></i></button>
			<?php
			if (count($all_rooms) > 1 && count($all_rooms) < 50 && count($this->req_room_ids) < count($all_rooms)) {
				// display select all rooms button
				?>
				<div class="vbo-roverview-selall-rooms">
					<button type="button" class="btn vbo-config-btn" onclick="vboSelectAllRooms(this);"><?php VikBookingIcons::e('layer-group'); ?> <?php echo JText::translate('VBOSELECTALL'); ?></button>
				</div>
				<?php
			}
			?>
			</div>
			<div class="vbo-ratesoverview-roomsel-entry vbo-ratesoverview-roomsel-entry-calc">
				<label for="checkindate" onclick="vboCheckCollapse();"><?php VikBookingIcons::e('calculator'); ?> <?php echo JText::translate('VBRATESOVWRATESCALCULATOR'); ?></label>
				<div class="vbo-ratesoverview-roomsel-entry-calc-inner"<?php echo $collapse_status ? ' style="display: none;"' : ''; ?>>
					<span class="vbo-ratesoverview-entryinline vbo-ratesoverview-rcalc-srooms">
						<select name="roomselcalc" id="roomselcalc" style="max-width: 250px;">
					<?php
					foreach ($all_rooms as $room) {
						?>
							<option value="<?php echo $room['id']; ?>"<?php echo $room['id'] == $roomrows[$this->firstroom]['id'] ? ' selected="selected"' : ''; ?>><?php echo $room['name']; ?></option>
						<?php
					}
					?>
						</select>
					</span>
					<span class="vbo-ratesoverview-entryinline">
						<div class="vbo-field-calendar">
							<div class="input-append">
								<input type="text" autocomplete="off" value="" class="vbo-roverv-rcalc-cal" size="10" id="checkindate" name="checkindate" placeholder="<?php echo JText::translate('VBPICKUPAT'); ?>" />
								<button type="button" class="btn btn-secondary" id="checkindate-trig"><?php VikBookingIcons::e('calendar', 'icn-nomargin'); ?></button>
							</div>
						</div>
					</span>
					<span class="vbo-ratesoverview-entryinline">
						<div class="vbo-field-calendar">
							<div class="input-append">
								<input type="text" autocomplete="off" value="" class="vbo-roverv-rcalc-cal" size="10" id="checkoutdate" name="checkoutdate" placeholder="<?php echo JText::translate('VBRELEASEAT'); ?>" />
								<button type="button" class="btn btn-secondary" id="checkoutdate-trig"><?php VikBookingIcons::e('calendar', 'icn-nomargin'); ?></button>
							</div>
						</div>
					</span>
					<span class="vbo-ratesoverview-entryinline"><span><?php echo JText::translate('VBRATESOVWRATESCALCNUMNIGHTS'); ?></span> <input type="number" id="vbo-numnights" value="1" min="1" max="365" step="1" /></span>
					<span class="vbo-ratesoverview-entryinline"><span><?php echo JText::translate('VBRATESOVWRATESCALCNUMADULTS'); ?></span> <input type="number" id="vbo-numadults" value="<?php echo $roomrows[$this->firstroom]['fromadult']; ?>" step="1"/></span>
					<span class="vbo-ratesoverview-entryinline"><span><?php echo JText::translate('VBRATESOVWRATESCALCNUMCHILDREN'); ?></span> <input type="number" id="vbo-numchildren" value="<?php echo $roomrows[$this->firstroom]['fromchild']; ?>" step="1"/></span>
					<span class="vbo-ratesoverview-entryinline"><button type="button" class="btn vbo-config-btn" id="vbo-ratesoverview-calculate"><?php echo JText::translate('VBRATESOVWRATESCALCULATORCALC'); ?></button></span>
				</div>

				<div class="vbo-ratesoverview-calculation-response"></div>

			</div>
			<div class="vbo-ratesoverview-roomsel-entry vbo-ratesoverview-roomsel-entry-forecast"<?php echo (!empty($cookie_tab) && $cookie_tab != 'cal' ? ' style="display: none;"' : ''); ?>>
				<label onclick="vboCheckCollapse();"><?php VikBookingIcons::e('cloud-sun-rain'); ?> <?php echo JText::translate('VBOFORECAST'); ?></label>
				<div class="vbo-roverv-forecast-inner"<?php echo $collapse_status ? ' style="display: none;"' : ''; ?>>
					<?php echo JLayoutHelper::render('reports.occupancy', array('vbo_page' => 'ratesoverv', 'room_ids' => $this->req_room_ids)); ?>
				</div>
			</div>
			<div class="vbo-ratesoverview-roomsel-entry vbo-ratesoverview-roomsel-entry-los"<?php echo (!empty($cookie_tab) && $cookie_tab == 'cal' ? ' style="display: none;"' : ''); ?>>
				<label><?php echo JText::translate('VBRATESOVWNUMNIGHTSACT'); ?></label>
		<?php
		foreach ($seasoncal_nights as $numnights) {
			?>
				<span class="vbo-ratesoverview-numnight" id="numnights<?php echo $numnights; ?>"><?php echo $numnights; ?></span>
				<input type="hidden" name="nights_cal[]" id="inpnumnights<?php echo $numnights; ?>" value="<?php echo $numnights; ?>" />
			<?php
		}
		?>
				<input type="number" id="vbo-addnumnight" value="<?php echo isset($numnights) ? ($numnights + 1) : 1; ?>" min="1"/>
				<span id="vbo-addnumnight-act"><?php VikBookingIcons::e('plus-square'); ?></span>
				<button type="button" class="btn vbo-config-btn vbo-apply-los-btn" onclick="document.vboratesovwform.submit();"><?php echo JText::translate('VBRATESOVWAPPLYLOS'); ?></button>
			</div>
		</form>
	</div>
	<div class="vbo-ratesoverview-right-block">
		<div class="vbo-ratesoverview-right-inner"></div>
	</div>
</div>

<div class="vbo-ratesoverview-bottom-container">
	<?php
	foreach ($roomrows as $rid => $roomrow) {
		if (count($this->req_room_ids) < 2) {
			?>
	<div class="vbo-ratesoverview-bottom-head">
		<div class="vbo-ratesoverview-roomdetails">
			<h3 class="vbo-ratesoverview-roomname"><?php echo $roomrow['name']; ?></h3>
		</div>
		<div class="vbo-ratesoverview-tabscont">
			<div class="vbo-ratesoverview-tab-cal <?php echo (!empty($cookie_tab) && $cookie_tab == 'cal' ? 'vbo-ratesoverview-tab-active' : 'vbo-ratesoverview-tab-unactive'); ?>"><i class="vboicn-calendar"></i> <?php echo JText::translate('VBRATESOVWTABCALENDAR'); ?></div>
			<div class="vbo-ratesoverview-tab-los <?php echo (!empty($cookie_tab) && $cookie_tab == 'cal' ? 'vbo-ratesoverview-tab-unactive' : 'vbo-ratesoverview-tab-active'); ?>"><i class="vboicn-clock"></i> <?php echo JText::translate('VBRATESOVWTABLOS'); ?></div>
		</div>
	</div>
			<?php
		}
		?>

	<div class="vbo-ratesoverview-caltab-cont" style="display: <?php echo count($this->req_room_ids) > 1 || (!empty($cookie_tab) && $cookie_tab == 'cal') ? 'block' : 'none'; ?>;">
		<?php
		if (count($this->req_room_ids) > 1) {
			// display room name here when multiple rooms as well as the OBP toggle
			if (!isset($vbo_show_toggle_obp)) {
				// toggle button for showing/hiding the occupancy based pricing rules
				$vbo_show_toggle_obp = 1;
				$obp_status = $cookie->get('vboRovwObp', 1, 'int');
				?>
		<div class="vbo-ratesoverview-obp-toggle" data-obpstartstatus="<?php echo $obp_status; ?>" style="display: none;">
			<div class="vbo-ratesoverview-obp-toggle-inner vbo-toggle-small">
				<?php echo $vbo_app->printYesNoButtons('show_obp', JText::translate('VBYES'), JText::translate('VBNO'), $obp_status, 1, 0, "vboToggleOBPRows();"); ?>
				<span class="vbo-ratesoverview-obp-toggle-txt" onclick="jQuery(this).parent().find('input').trigger('click');"><?php echo JText::translate('VBOROVERVOBP'); ?></span>
			</div>
		</div>
				<?php
			}
			?>
		<div class="vbo-ratesoverview-roomdetails">
			<h3 class="vbo-ratesoverview-roomname" data-idroom="<?php echo $roomrow['id']; ?>"><?php echo $roomrow['name']; ?></h3>
		</div>
			<?php
		} else {
			// room name node is needed for JS
			?>
		<div class="vbo-ratesoverview-roomdetails" style="display: none;">
			<h3 class="vbo-ratesoverview-roomname" data-idroom="<?php echo $roomrow['id']; ?>"><?php echo $roomrow['name']; ?></h3>
		</div>
			<?php
		}
		?>
		<div class="vbo-ratesoverview-caltab-wrapper">
			<div class="vbo-table-responsive">
				<table class="vboverviewtable vbratesoverviewtable vbo-table" data-idroom="<?php echo $rid; ?>">
					<tbody>
						<tr class="vbo-roverviewrowone">
							<td class="bluedays skip-bluedays-click">
								<form name="vbratesoverview" method="post" action="index.php?option=com_vikbooking&amp;task=ratesoverv">
									<div class="vbo-roverview-datecmd-top">
										<div class="vbo-roverview-datecmd-date">
											<span>
												<?php VikBookingIcons::e('calendar'); ?>
												<input type="text" autocomplete="off" value="<?php echo date($df, $tsstart); ?>" class="vbodatepicker" name="startdate" />
											</span>
										</div>
									</div>
								</form>
							</td>
						<?php
						$nowts = getdate($tsstart);
						$days_labels = array(
							JText::translate('VBSUN'),
							JText::translate('VBMON'),
							JText::translate('VBTUE'),
							JText::translate('VBWED'),
							JText::translate('VBTHU'),
							JText::translate('VBFRI'),
							JText::translate('VBSAT')
						);
						$long_days_labels = array(
							JText::translate('VBSUNDAY'),
							JText::translate('VBMONDAY'),
							JText::translate('VBTUESDAY'),
							JText::translate('VBWEDNESDAY'),
							JText::translate('VBTHURSDAY'),
							JText::translate('VBFRIDAY'),
							JText::translate('VBSATURDAY')
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
						$cell_count = 0;
						$MAX_DAYS = 60;
						$pcheckinh = 0;
						$pcheckinm = 0;
						$pcheckouth = 0;
						$pcheckoutm = 0;
						$timeopst = VikBooking::getTimeOpenStore();
						if (is_array($timeopst)) {
							$opent = VikBooking::getHoursMinutes($timeopst[0]);
							$closet = VikBooking::getHoursMinutes($timeopst[1]);
							$pcheckinh = $opent[0];
							$pcheckinm = $opent[1];
							$pcheckouth = $closet[0];
							$pcheckoutm = $closet[1];
						}
						$weekend_arr = array(0, 6);
						while ($cell_count < $MAX_DAYS) {
							$style = '';
							$curdayymd = date('Y-m-d', $nowts[0]);
							$read_day  = $days_labels[$nowts['wday']] . ' ' . $nowts['mday'] . ' ' . $months_labels[$nowts['mon']-1] . ' ' . $nowts['year'];

							$cell_classes = array(
								'bluedays',
								'cell-' . $nowts['mday'] . '-' . $nowts['mon'],
							);

							/**
							 * Critical dates defined at room-day level.
							 * 
							 * @since 	1.13.5
							 */
							$rdaynote_keyid = $curdayymd . '_' . $rid . '_0';
							$rdaynote_icn = '';
							if (isset($this->rdaynotes[$rdaynote_keyid])) {
								// note exists for this combination of date, room ID and subunit
								array_push($cell_classes, 'vbo-roverw-roomdaynote-full');
								$rdaynote_icn = '<i class="' . VikBookingIcons::i('sticky-note') . '"></i>';
							}
							//

							if (in_array((int)$nowts['wday'], $weekend_arr)) {
								array_push($cell_classes, 'vbo-roverw-tablewday-wend');
							}
							if (isset($this->festivities[$curdayymd])) {
								array_push($cell_classes, 'vbo-roverv-festcell');
							}
							?>
							<td data-ymd="<?php echo $curdayymd; ?>" data-readymd="<?php echo $read_day; ?>" data-rid="<?php echo $rid; ?>" class="<?php echo implode(' ', $cell_classes); ?>"<?php echo $style; ?>>
							<?php
							if (!empty($rdaynote_icn)) {
								?>
								<span class="vbo-roverw-roomdaynote-icn"><?php echo $rdaynote_icn; ?></span>
								<?php
							}
							?>
								<span class="vbo-roverw-tablewday"><?php echo $days_labels[$nowts['wday']]; ?></span>
								<span class="vbo-roverw-tablemday"><?php echo $nowts['mday']; ?></span>
								<span class="vbo-roverw-tablemonth"><?php echo $months_labels[$nowts['mon']-1]; ?></span>
							</td>
							<?php
							$next = $nowts['mday'] + 1;
							$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
							$nowts = getdate($dayts);
							$cell_count++;
						}
						?>
							<td class="vbo-roverv-gonext-cell" data-rid="<?php echo $rid; ?>" rowspan="<?php echo count($roomrates[$rid]) + 3; ?>">
								<div class="vbo-roverv-gonext-cell-inner">
									<button type="button" class="btn vbo-config-btn vbo-config-btn-rounded vbo-config-btn-large" onclick="vboDisplayNextDays(this);"><?php VikBookingIcons::e('angle-double-right'); ?></button>
								</div>
							</td>
						</tr>
					<?php
					$room_obp_overrides = [];
					$closed_roomrateplans = VikBooking::getRoomRplansClosingDates($roomrow['id']);
					foreach ($roomrates[$rid] as $roomrate) {
						$nowts = getdate($tsstart);
						$cell_count = 0;
						$rplan_minlos = !empty($roomrate['minlos']) && $roomrate['minlos'] > 0 ? $roomrate['minlos'] : 0;
						$rplan_haslos = $room_helper->hasLosRecords($roomrate['idroom'], $roomrate['idprice'], true);
						?>
						<tr class="vbo-roverviewtablerow" id="vbo-roverw-<?php echo $roomrate['id'].'-'.$roomrate['idroom']; ?>">
							<td class="vbo-roverv-rplan<?php echo $rplan_minlos || $rplan_haslos ? ' vbo-roverv-rplan-restricted' : ''; ?>" data-defrate="<?php echo $roomrate['cost']; ?>" data-roomname="<?php echo htmlspecialchars($roomrow['name']); ?>">
								<span class="vbo-rplan-name"><?php echo $roomrate['name']; ?></span>
							<?php
							if ($rplan_minlos || $rplan_haslos) {
								?>
								<div class="vbo-roverv-rplan-restrictions<?php echo $rplan_haslos ? ' vbo-roverv-rplan-haslosrates' : ''; ?>">
								<?php
								if ($rplan_haslos) {
									?>
									<span class="badge badge-info vbo-roverv-rplan-restrictions-los">LOS</span>
									<?php
								}
								if ($rplan_minlos) {
									?>
									<span class="vbo-roverv-rplan-restrictions-lbl"><?php echo JText::translate('VBOMINIMUMSTAY'); ?></span>
									<span class="vbo-roverv-rplan-restrictions-val"><?php echo $rplan_minlos; ?></span>
									<?php
								}
								?>
								</div>
								<?php
							}
							?>
							</td>
						<?php
						while ($cell_count < $MAX_DAYS) {
							$style = '';
							$dclass = "vbo-roverw-rplan-on";
							if (count($closed_roomrateplans) > 0 && array_key_exists($roomrate['idprice'], $closed_roomrateplans) && in_array(date('Y-m-d', $nowts[0]), $closed_roomrateplans[$roomrate['idprice']])) {
								$dclass = "vbo-roverw-rplan-off";
							}
							$id_block = "cell-".$nowts['mday'].'-'.$nowts['mon']."-".$nowts['year']."-".$roomrate['idprice']."-".$roomrate['idroom'];
							$dclass .= ' day-block';

							$today_tsin = mktime($pcheckinh, $pcheckinm, 0, $nowts['mon'], $nowts['mday'], $nowts['year']);
							$today_tsout = mktime($pcheckouth, $pcheckoutm, 0, $nowts['mon'], ($nowts['mday'] + 1), $nowts['year']);

							$tars = VikBooking::applySeasonsRoom(array($roomrate), $today_tsin, $today_tsout);

							// store the OBP overrides for this day and rate plan
							if (!empty($tars[0]['occupancy_ovr'])) {
								if (!isset($room_obp_overrides[$nowts[0]])) {
									$room_obp_overrides[$nowts[0]] = [];
								}
								$room_obp_overrides[$nowts[0]][] = [
									'idprice' => $roomrate['idprice'],
									'obp' 	  => $tars[0]['occupancy_ovr'],
								];
							}

							?>
							<td align="center" class="<?php echo $dclass.' cell-'.$nowts['mday'].'-'.$nowts['mon']; ?>" id="<?php echo $id_block; ?>" data-vboprice="<?php echo $tars[0]['cost']; ?>" data-vbodate="<?php echo date('Y-m-d', $nowts[0]); ?>" data-vbodateread="<?php echo $days_labels[$nowts['wday']].', '.$months_labels[$nowts['mon']-1].' '.$nowts['mday']; ?>" data-vbospids="<?php echo (array_key_exists('spids', $tars[0]) && count($tars[0]['spids']) > 0 ? implode('-', $tars[0]['spids']) : ''); ?>"<?php echo $style; ?>>
								<span class="vbo-rplan-currency"><?php echo $currencysymb; ?></span>
								<span class="vbo-rplan-price"><?php echo $tars[0]['cost']; ?></span>
							</td>
							<?php

							$next = $nowts['mday'] + 1;
							$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
							$nowts = getdate($dayts);
							
							$cell_count++;
						}
						?>
						</tr>
						<?php
					}

					// Occupancy Based Pricing rules
					$adultsdiff = array_reverse(VikBooking::loadRoomAdultsDiff($roomrow['id']), true);

					// display the room OBP rules (must be defined at room-level or no overrides will be displayed)
					foreach ($adultsdiff as $adults => $diffusageprice) {
						$base_obp_rules = $diffusageprice;
						$nowts = getdate($tsstart);
						$cell_count = 0;
						?>
						<tr class="vbo-roverviewtablerow-occupancy">
							<td>
								<span class="vbo-rplan-name vbo-rplan-name-occ">
									<span><?php echo JText::translate('VBPVIEWOPTIONALSTHREE'); ?></span> 
									<span class="vbo-occ-label"><?php VikBookingIcons::e('male'); ?> x <?php echo $adults ?></span>
								</span>
							</td>
						<?php
						while ($cell_count < $MAX_DAYS) {
							$style = '';
							$dclass = "vbo-roverw-rplan-occ";

							// check for OBP overrides
							$compare_obp_ovr = [];
							if (isset($room_obp_overrides[$nowts[0]]) && count($room_obp_overrides[$nowts[0]]) === count($roomrates[$rid])) {
								// the OBP overrides affect all room rate plans, make sure the overrides are identical though
								$compare_obp_ovr = $room_obp_overrides[$nowts[0]][0]['obp'];
								foreach ($room_obp_overrides[$nowts[0]] as $room_obp_override) {
									if ($compare_obp_ovr != $room_obp_override['obp']) {
										// this OBP override does not match the first one
										$compare_obp_ovr = [];
										break;
									}
								}
							}
							if ($compare_obp_ovr && isset($compare_obp_ovr[$adults])) {
								// display the OBP override value for this day
								$diffusageprice = $compare_obp_ovr[$adults];
							} else {
								// display the OBP rules at room-level
								$diffusageprice = $base_obp_rules;
							}

							if (($diffusageprice['value'] - floor($diffusageprice['value'])) <= 0) {
								$diffusageprice['value'] = intval($diffusageprice['value']);
							}

							if ($diffusageprice['chdisc'] == 1) {
								// charge
								if ($diffusageprice['valpcent'] == 1) {
									// fixed value
									$occ_diff = '+ ' . $currencysymb . ' ' . $diffusageprice['value'];
								} else {
									// percentage value
									$occ_diff = '+ ' . $diffusageprice['value'] . '%';
								}
							} else {
								// discount
								if ($diffusageprice['valpcent'] == 1) {
									// fixed value
									$occ_diff = '- ' . $currencysymb . ' ' . $diffusageprice['value'];
								} else {
									// percentage value
									$occ_diff = '- ' . $diffusageprice['value'] . '%';
								}
							}

							?>
							<td align="center" class="<?php echo $dclass; ?>"<?php echo $style; ?>>
								<span class="vbo-roverv-priceoccdiff"><?php echo $occ_diff; ?></span>
							</td>
							<?php

							$next = $nowts['mday'] + 1;
							$dayts = mktime(0, 0, 0, $nowts['mon'], $next, $nowts['year']);
							$nowts = getdate($dayts);
							
							$cell_count++;
						}
						?>
						</tr>
						<?php
					}
					?>
						<tr class="vbo-roverviewtableavrow">
							<td><span class="vbo-roverview-roomunits"><?php echo $roomrow['units']; ?></span><span class="vbo-roverview-uleftlbl"><?php echo JText::translate('VBPCHOOSEBUSYCAVAIL'); ?></span></td>
						<?php
						$nowts = getdate($tsstart);
						$cell_count = 0;
						while ($cell_count < $MAX_DAYS) {
							$style = '';
							$dclass = "vbo-roverw-daynotbusy";
							$id_block = "cell-".$nowts['mday'].'-'.$nowts['mon']."-".$nowts['year']."-".$nowts['wday']."-".$rid."-avail";

							$totfound = 0;
							$last_bid = 0;
							$bids_pool = [];

							if (isset($this->booked_dates[$roomrow['id']]) && $this->booked_dates[$roomrow['id']]) {
								foreach ($this->booked_dates[$roomrow['id']] as $b) {
									$tmpone = getdate($b['checkin']);
									$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
									$tmptwo = getdate($b['checkout']);
									$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
									if ($nowts[0] >= $ritts && $nowts[0] < $conts) {
										$dclass = "vbo-roverw-daybusy";
										$last_bid = $b['idorder'];
										$totfound++;
										// push bid to pool
										$bid_str = '-' . $b['idorder'] . '-';
										if (!in_array($bid_str, $bids_pool)) {
											$bids_pool[] = $bid_str;
										}
									}
								}
							}

							$units_remaining = $roomrow['units'] - $totfound;
							if ($units_remaining > 0 && $units_remaining < $roomrow['units'] && $roomrow['units'] > 1) {
								$dclass .= " vbo-roverw-daybusypartially";
							} elseif ($units_remaining <= 0 && $roomrow['units'] <= 1 && !empty($last_bid)) {
								//Booking color tag
								$btag_style = '';
								$binfo = VikBooking::getBookingInfoFromID($last_bid);
								if (count($binfo) > 0) {
									$bcolortag = VikBooking::applyBookingColorTag($binfo);
									if (count($bcolortag) > 0) {
										$bcolortag['name'] = JText::translate($bcolortag['name']);
										$btag_style = "background-color: ".$bcolortag['color']."; color: ".(array_key_exists('fontcolor', $bcolortag) ? $bcolortag['fontcolor'] : '#ffffff').";";
										$dclass .= ' vbo-roverw-hascolortag';
									}
								}
								if (!empty($btag_style)) {
									$style = !empty($style) ? ' style="display: none; '.$btag_style.'"' : ' style="'.$btag_style.'"';
									$style .= ' title="'.addslashes($bcolortag['name']).'"';
								}
							}

							?>
							<td align="center" class="<?php echo $dclass.' cell-'.$nowts['mday'].'-'.$nowts['mon']; ?>" id="<?php echo $id_block; ?>" data-bids="<?php echo implode(',', $bids_pool); ?>" data-vbodateread="<?php echo $days_labels[$nowts['wday']].', '.$months_labels[$nowts['mon']-1].' '.$nowts['mday']; ?>"<?php echo $style; ?>>
								<span class="vbo-roverw-curunits"><?php echo $units_remaining; ?></span>
							</td>
							<?php

							$next = $nowts['mday'] + 1;
							$dayts = mktime(0, 0, 0, ($nowts['mon'] < 10 ? "0".$nowts['mon'] : $nowts['mon']), ($next < 10 ? "0".$next : $next), $nowts['year']);
							$nowts = getdate($dayts);
							
							$cell_count++;
						}
						?>
						</tr>
					<?php
					// VBO 1.11 - MinLOS restrictions row
					?>
						<tr class="vbo-roverviewtablerow-restrs">
							<td><span class="vbo-roverview-restrs"><?php echo JText::translate('VBOMINIMUMSTAY'); ?></span></td>
						<?php
						$nowts = getdate($tsstart);
						$cell_count = 0;
						$glob_minlos = VikBooking::getDefaultNightsCalendar();
						$glob_minlos = $glob_minlos < 1 ? 1 : $glob_minlos;
						while ($cell_count < $MAX_DAYS) {
							$style = '';
							$dclass = "vbo-roverw-rplan-restr";
							
							$id_block = "cell-".$nowts['mday'].'-'.$nowts['mon']."-".$nowts['year']."-".$rid."-restr";

							$today_tsin = mktime(0, 0, 0, $nowts['mon'], $nowts['mday'], $nowts['year']);
							$today_tsout = mktime(0, 0, 0, $nowts['mon'], ($nowts['mday'] + 1), $nowts['year']);

							$restr 	= VikBooking::parseSeasonRestrictions($today_tsin, $today_tsout, 1, (isset($this->all_restrictions[$rid]) ? $this->all_restrictions[$rid] : array()));
							$minlos = count($restr) ? $restr['minlos'] : $glob_minlos;

							?>
							<td align="center" class="<?php echo $dclass; ?>" id="<?php echo $id_block; ?>"<?php echo $style; ?>>
								<span class="vbo-roverw-curminlos<?php echo $minlos > 1 ? ' vbo-roverw-curminlos-active' : ''; ?>"><?php echo $minlos; ?></span>
							</td>
							<?php

							$next = $nowts['mday'] + 1;
							$dayts = mktime(0, 0, 0, ($nowts['mon'] < 10 ? "0".$nowts['mon'] : $nowts['mon']), ($next < 10 ? "0".$next : $next), $nowts['year']);
							$nowts = getdate($dayts);
							
							$cell_count++;
						}
						?>
						</tr>
					<?php
					//
					?>
					</tbody>
				</table>
			</div>
			<div class="vbo-ratesoverview-period-container">
				<div class="vbo-ratesoverview-period-inner">
					<div class="vbo-ratesoverview-period-lbl">
						<span><?php echo JText::translate('VBOROVWSELPERIOD'); ?></span>
					</div>
					<div class="vbo-ratesoverview-period-boxes">
						<div class="vbo-ratesoverview-period-boxes-inner">
							<div class="vbo-ratesoverview-period-box-left">
								<div class="vbo-ratesoverview-period-box-lbl">
									<span><?php echo JText::translate('VBOROVWSELPERIODFROM'); ?></span>
								</div>
								<div class="vbo-ratesoverview-period-box-val">
									<div class="vbo-ratesoverview-period-from">
										<span class="vbo-ratesoverview-period-wday"></span>
										<span class="vbo-ratesoverview-period-mday"></span>
										<span class="vbo-ratesoverview-period-month"></span>
									</div>
									<span class="vbo-ratesoverview-period-from-icon"><?php VikBookingIcons::e('calendar'); ?></span>
								</div>
							</div>
							<div class="vbo-ratesoverview-period-box-right">
								<div class="vbo-ratesoverview-period-box-lbl">
									<span><?php echo JText::translate('VBOROVWSELPERIODTO'); ?></span>
								</div>
								<div class="vbo-ratesoverview-period-box-val">
									<div class="vbo-ratesoverview-period-to">
										<span class="vbo-ratesoverview-period-wday"></span>
										<span class="vbo-ratesoverview-period-mday"></span>
										<span class="vbo-ratesoverview-period-month"></span>
									</div>
									<span class="vbo-ratesoverview-period-to-icon"><?php VikBookingIcons::e('calendar'); ?></span>
								</div>
							</div>
						</div>
						<div class="vbo-ratesoverview-period-box-cals" style="display: none;">
							<div class="vbo-ratesoverview-period-box-cals-inner">
								<div class="vbo-ratesoverview-period-cal-left">
									<h4><?php echo JText::translate('VBOROVWSELPERIODFROM'); ?></h4>
									<div class="vbo-period-from" data-idroom="<?php echo $rid; ?>" data-roomname="<?php echo htmlspecialchars($roomrow['name']); ?>"></div>
									<input type="hidden" class="vbo-period-from-val" value="" />
								</div>
								<div class="vbo-ratesoverview-period-cal-right">
									<h4><?php echo JText::translate('VBOROVWSELPERIODTO'); ?></h4>
									<div class="vbo-period-to" data-idroom="<?php echo $rid; ?>" data-roomname="<?php echo htmlspecialchars($roomrow['name']); ?>"></div>
									<input type="hidden" class="vbo-period-to-val" value="" />
								</div>
								<div class="vbo-ratesoverview-period-cal-cmd">
									<h4><?php echo JText::translate('VBOROVWSELRPLAN'); ?></h4>
									<div class="vbo-ratesoverview-period-cal-cmd-inner">
										<select class="vbo-selperiod-rplanid" onchange="vboUpdateRplan(this);">
										<?php
										foreach ($roomrates[$rid] as $krr => $roomrate) {
											?>
											<option value="<?php echo $roomrate['idprice']; ?>" data-defrate="<?php echo $roomrate['cost']; ?>"<?php echo $krr < 1 ? ' selected="selected"' : ''; ?>><?php echo $roomrate['name']; ?></option>
											<?php
										}
										?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="vbo-ratesoverview-orphans-wrapper" id="vbo-ratesoverview-orphans-wrapper-<?php echo $rid; ?>" style="display: none;">
					<div class="vbo-ratesoverview-orphans-lbl">
						<span><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBORPHANSFOUND'), 'content' => JText::translate('VBORPHANSFOUNDSHELP'), 'icon_class' => VikBookingIcons::i('exclamation-triangle'))); ?> <?php echo JText::translate('VBORPHANSFOUND'); ?></span>
					</div>
					<div class="vbo-ratesoverview-orphans-list" id="vbo-ratesoverview-orphans-list-<?php echo $rid; ?>"></div>
				</div>
			</div>
		</div>
	</div>

	<?php
		// start los pricing overview IF statement for just 1 room
		if (count($this->req_room_ids) < 2) :
	?>
	<div class="vbo-ratesoverview-lostab-cont"<?php echo (!empty($cookie_tab) && $cookie_tab == 'cal' ? ' style="display: none;"' : ''); ?>>
		<?php
		if (count($seasons_cal) > 0) {
			//Special Prices Timeline
			if (isset($seasons_cal['seasons']) && count($seasons_cal['seasons'])) {
				?>
		<div class="vbo-timeline-container">
			<ul id="vbo-timeline">
				<?php
				foreach ($seasons_cal['seasons'] as $ks => $timeseason) {
					$s_val_diff = '';
					if ($timeseason['val_pcent'] == 2) {
						//percentage
						$s_val_diff = (($timeseason['diffcost'] - abs($timeseason['diffcost'])) > 0.00 ? VikBooking::numberFormat($timeseason['diffcost']) : intval($timeseason['diffcost']))." %";
					} else {
						//absolute
						$s_val_diff = $currencysymb.''.VikBooking::numberFormat($timeseason['diffcost']);
					}
					$s_explanation = array();
					if (empty($timeseason['year'])) {
						$s_explanation[] = JText::translate('VBSEASONANYYEARS');
					}
					if (!empty($timeseason['losoverride'])) {
						$s_explanation[] = JText::translate('VBSEASONBASEDLOS');
					}
					?>
				<li data-fromts="<?php echo $timeseason['from_ts']; ?>" data-tots="<?php echo $timeseason['to_ts']; ?>">
					<input type="radio" name="timeline" class="vbo-timeline-radio" id="vbo-timeline-dot<?php echo $ks; ?>" <?php echo $ks === 0 ? 'checked="checked"' : ''; ?>/>
					<div class="vbo-timeline-relative">
						<label class="vbo-timeline-label" for="vbo-timeline-dot<?php echo $ks; ?>"><?php echo $timeseason['spname']; ?></label>
						<span class="vbo-timeline-date"><?php echo VikBooking::formatSeasonDates($timeseason['from_ts'], $timeseason['to_ts']); ?></span>
						<span class="vbo-timeline-circle" onclick="Javascript: jQuery('#vbo-timeline-dot<?php echo $ks; ?>').trigger('click');"></span>
					</div>
					<div class="vbo-timeline-content">
						<p>
							<span class="vbo-seasons-calendar-slabel vbo-seasons-calendar-season-<?php echo $timeseason['type'] == 2 ? 'discount' : 'charge'; ?>"><?php echo $timeseason['type'] == 2 ? '-' : '+'; ?> <?php echo $s_val_diff; ?> <?php echo JText::translate('VBSEASONPERNIGHT'); ?></span>
							<br/>
							<?php
							if (count($s_explanation) > 0) {
								echo implode(' - ', $s_explanation);
							}
							?>
						</p>
					</div>
				</li>
					<?php
				}
				?>
			</ul>
		</div>

		<script>
		jQuery(function() {
			jQuery('.vbo-timeline-container').css('min-height', (jQuery('.vbo-timeline-container').outerHeight() + 20));
		});
		</script>
				<?php
			}
			//
			//Begin Seasons Calendar
			?>
		<div class="table-responsive">
			<table class="table vbo-seasons-calendar-table">
				<tr class="vbo-seasons-calendar-nightsrow">
					<td>&nbsp;</td>
				<?php
				foreach ($seasons_cal['offseason'] as $numnights => $ntars) {
					?>
					<td><span><?php echo JText::sprintf(($numnights > 1 ? 'VBOSEASONCALNUMNIGHTS' : 'VBOSEASONCALNUMNIGHT'), $numnights); ?></span></td>
					<?php
				}
				?>
				</tr>
				<tr class="vbo-seasons-calendar-offseasonrow">
					<td>
						<span class="vbo-seasons-calendar-offseasonname"><?php echo JText::translate('VBOSEASONSCALOFFSEASONPRICES'); ?></span>
					</td>
				<?php
				foreach ($seasons_cal['offseason'] as $numnights => $tars) {
					?>
					<td>
						<div class="vbo-seasons-calendar-offseasoncosts">
							<?php
							foreach ($tars as $tar) {
								?>
							<div class="vbo-seasons-calendar-offseasoncost">
								<?php
								if ($price_types_show) {
								?>
								<span class="vbo-seasons-calendar-pricename"><?php echo $tar['name']; ?></span>
								<?php
								}
								?>
								<span class="vbo-seasons-calendar-pricecost">
									<span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($tar['cost']); ?></span>
								</span>
							</div>
								<?php
								if (!$price_types_show) {
									break;
								}
							}
							?>
						</div>
					</td>
					<?php
				}
				?>
				</tr>
				<?php
				if (!isset($seasons_cal['seasons'])) {
					$seasons_cal['seasons'] = array();
				}
				foreach ($seasons_cal['seasons'] as $s_id => $s) {
					$restr_diff_nights = array();
					if ($los_show && array_key_exists($s_id, $seasons_cal['restrictions'])) {
						$restr_diff_nights = VikBooking::compareSeasonRestrictionsNights($seasons_cal['restrictions'][$s_id]);
					}
					$s_val_diff = '';
					if ($s['val_pcent'] == 2) {
						//percentage
						$s_val_diff = (($s['diffcost'] - abs($s['diffcost'])) > 0.00 ? VikBooking::numberFormat($s['diffcost']) : intval($s['diffcost']))." %";
					} else {
						//absolute
						$s_val_diff = $currencysymb.''.VikBooking::numberFormat($s['diffcost']);
					}
					?>
				<tr class="vbo-seasons-calendar-seasonrow">
					<td>
						<div class="vbo-seasons-calendar-seasondates">
							<span class="vbo-seasons-calendar-seasonfrom"><?php echo date(str_replace("/", $datesep, $df), $s['from_ts']); ?></span>
							<span class="vbo-seasons-calendar-seasondates-separe">-</span>
							<span class="vbo-seasons-calendar-seasonto"><?php echo date(str_replace("/", $datesep, $df), $s['to_ts']); ?></span>
						</div>
						<div class="vbo-seasons-calendar-seasonchargedisc">
							<span class="vbo-seasons-calendar-slabel vbo-seasons-calendar-season-<?php echo $s['type'] == 2 ? 'discount' : 'charge'; ?>"><span class="vbo-seasons-calendar-operator"><?php echo $s['type'] == 2 ? '-' : '+'; ?></span><?php echo $s_val_diff; ?></span>
						</div>
						<span class="vbo-seasons-calendar-seasonname"><a href="index.php?option=com_vikbooking&amp;task=editseason&amp;cid[]=<?php echo $s['id']; ?>" target="_blank"><?php echo $s['spname']; ?></a></span>
					<?php
					if ($los_show && array_key_exists($s_id, $seasons_cal['restrictions']) && count($restr_diff_nights) == 0) {
						//Season Restrictions
						$season_restrictions = array();
						foreach ($seasons_cal['restrictions'][$s_id] as $restr) {
							$season_restrictions = $restr;
							break;
						}
						?>
						<div class="vbo-seasons-calendar-restrictions">
						<?php
						if ($season_restrictions['minlos'] > 1) {
							?>
							<span class="vbo-seasons-calendar-restriction-minlos"><?php echo JText::translate('VBORESTRMINLOS'); ?><span class="vbo-seasons-calendar-restriction-minlos-badge"><?php echo $season_restrictions['minlos']; ?></span></span>
							<?php
						}
						if (array_key_exists('maxlos', $season_restrictions) && $season_restrictions['maxlos'] > 1) {
							?>
							<span class="vbo-seasons-calendar-restriction-maxlos"><?php echo JText::translate('VBORESTRMAXLOS'); ?><span class="vbo-seasons-calendar-restriction-maxlos-badge"><?php echo $season_restrictions['maxlos']; ?></span></span>
							<?php
						}
						if (array_key_exists('wdays', $season_restrictions) && count($season_restrictions['wdays']) > 0) {
							?>
							<div class="vbo-seasons-calendar-restriction-wdays">
								<label><?php echo JText::translate((count($season_restrictions['wdays']) > 1 ? 'VBORESTRARRIVWDAYS' : 'VBORESTRARRIVWDAY')); ?></label>
							<?php
							foreach ($season_restrictions['wdays'] as $wday) {
								?>
								<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay($wday); ?></span>
								<?php
							}
							?>
							</div>
							<?php
						} elseif ((array_key_exists('cta', $season_restrictions) && count($season_restrictions['cta']) > 0) || (array_key_exists('ctd', $season_restrictions) && count($season_restrictions['ctd']) > 0)) {
							if (array_key_exists('cta', $season_restrictions) && count($season_restrictions['cta']) > 0) {
								?>
							<div class="vbo-seasons-calendar-restriction-wdays vbo-seasons-calendar-restriction-cta">
								<label><?php echo JText::translate('VBORESTRWDAYSCTA'); ?></label>
								<?php
								foreach ($season_restrictions['cta'] as $wday) {
									?>
								<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay(str_replace('-', '', $wday)); ?></span>
									<?php
								}
								?>
							</div>
								<?php
							}
							if (array_key_exists('ctd', $season_restrictions) && count($season_restrictions['ctd']) > 0) {
								?>
							<div class="vbo-seasons-calendar-restriction-wdays vbo-seasons-calendar-restriction-ctd">
								<label><?php echo JText::translate('VBORESTRWDAYSCTD'); ?></label>
								<?php
								foreach ($season_restrictions['ctd'] as $wday) {
									?>
								<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay(str_replace('-', '', $wday)); ?></span>
									<?php
								}
								?>
							</div>
								<?php
							}
						}
						?>
						</div>
						<?php
					}
					?>
					</td>
					<?php
					if (array_key_exists($s_id, $seasons_cal['season_prices']) && count($seasons_cal['season_prices'][$s_id]) > 0) {
						foreach ($seasons_cal['season_prices'][$s_id] as $numnights => $tars) {
							$show_day_cost = true;
							if ($los_show && array_key_exists($s_id, $seasons_cal['restrictions']) && array_key_exists($numnights, $seasons_cal['restrictions'][$s_id])) {
								if ($seasons_cal['restrictions'][$s_id][$numnights]['allowed'] === false) {
									$show_day_cost = false;
								}
							}
							?>
					<td>
						<?php
						if ($show_day_cost) {
						?>
						<div class="vbo-seasons-calendar-seasoncosts">
							<?php
							foreach ($tars as $tar) {
								//print the types of price that are not being modified by this special price with opacity
								$not_affected = (!array_key_exists('origdailycost', $tar));
								//
								?>
							<div class="vbo-seasons-calendar-seasoncost<?php echo ($not_affected ? ' vbo-seasons-calendar-seasoncost-notaffected' : ''); ?>">
								<?php
								if ($price_types_show) {
								?>
								<span class="vbo-seasons-calendar-pricename"><?php echo $tar['name']; ?></span>
								<?php
								}
								?>
								<span class="vbo-seasons-calendar-pricecost">
									<span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($tar['cost']); ?></span>
								</span>
							</div>
								<?php
								if (!$price_types_show) {
									break;
								}
							}
							?>
						</div>
						<?php
						} else {
							?>
							<div class="vbo-seasons-calendar-seasoncosts-disabled"></div>
							<?php
						}
						?>
					</td>
							<?php
						}
					}
					?>
				</tr>
					<?php
				}
				?>
			</table>
		</div>
			<?php
			//End Seasons Calendar
		} else {
			?>
		<p class="vbo-warning"><?php echo JText::translate('VBOWARNNORATESROOM'); ?></p>
			<?php
		}
		?>
	</div>
		<?php
		// end los pricing overview IF statement for just 1 room
		endif;
	}
	?>
</div>
<?php

// overlay modal to change rates, restrictions etc..
$vcm_enabled = VikBooking::vcmAutoUpdate();
	?>
<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-rovervw">
		<div class="vbo-roverw-infoblock">
			<span id="rovervw-roomname"></span>
			<div class="vbo-roverw-inforates">
				<span id="rovervw-rplan"></span>
				<span id="rovervw-fromdate"></span> - <span id="rovervw-todate"></span>
			</div>
		</div>
		<div class="vbo-roverw-alldays">
			<div class="vbo-roverw-alldays-inner"></div>
		</div>
		<div class="vbo-roverw-setnewrate">
			<div class="vbo-roverw-flexnew">
				<div class="vbo-roverw-newrwrap">
					<h4><i class="vboicn-calculator"></i> <?php echo JText::translate('VBRATESOVWSETNEWRATE'); ?></h4>
					<div class="vbo-roverw-newrcont">
						<label for="roverw-newrate" class="vbo-roverw-setnewrate-currency"><?php echo $currencysymb; ?></label>
						<input type="number" step="any" min="0" id="roverw-newrate" value="" placeholder="" size="7" />
					</div>
				</div>
				<div class="vbo-roverw-newrestr-wrap" style="display: none;">
					<div class="vbo-roverw-newrestrcont">
						<h4><i class="vboicn-blocked"></i> <?php echo JText::translate('VBOMINIMUMSTAYSET'); ?></h4>
						<div class="vbo-roverw-newrestrcont-inner">
							<label for="roverw-newrestr" class="vbo-roverw-setnewrestr-lbl"><?php echo JText::translate('VBDAYS'); ?></label>
							<input type="number" step="1" min="0" id="roverw-newrestr" value="" size="7" />
						</div>
					</div>
				</div>
			</div>
			<div class="vbo-roverw-setnewrate-inner">
				<div class="vbo-roverw-setnewrate-vcm">
					<div class="vbo-roverw-setnewrate-vcm-head">
						<span class="<?php echo $vcm_enabled < 0 ? 'vbo-vcm-notinstalled' : 'vbo-vcm-installed'; ?>">
							<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOUPDRATESONCHANNELS'), 'content' => ($vcm_enabled < 0 ? JText::translate('VBCONFIGVCMAUTOUPDMISS') : JText::translate('VBOUPDRATESONCHANNELSHELP')), 'icon_class' => VikBookingIcons::i('globe'))); ?>
							<?php echo JText::translate('VBOUPDRATESONCHANNELS'); ?>
						</span>
					</div>
					<div class="vbo-roverw-setnewrate-vcm-body">
						<label class="vbo-switch">
							<input type="checkbox" id="roverw-newrate-vcm" onchange="vboCheckVcmRestrictions();" value="1" <?php echo $vcm_enabled < 0 ? 'disabled="disabled"' : ($vcm_enabled > 0 ? 'checked="checked"' : ''); ?>/>
							<span class="vbo-slider<?php echo $vcm_enabled < 0 ? ' vbo-slider-disabled' : ''; ?> vbo-round"></span>
						</label>
					</div>
				</div>
				<div class="vbo-roverw-setnewrate-btns">
					<button type="button" class="btn btn-danger" onclick="hideVboDialog();"><?php echo JText::translate('VBANNULLA'); ?></button>
					<button type="button" class="btn btn-success" onclick="setNewRates();"><i class="vboicn-checkmark"></i><?php echo JText::translate('VBAPPLY'); ?></button>
				</div>
			</div>
		</div>
		<div class="vbo-roverw-closeopenrp">
			<h4><i class="vboicn-switch"></i><?php echo JText::translate('VBRATESOVWCLOSEOPENRRP'); ?> <span id="rovervw-closeopen-rplan"></span></h4>
			<div class="vbo-roverw-closeopenrp-btns">
				<button type="button" class="btn btn-danger" onclick="modRoomRatePlan('close');"><i class="vboicn-exit"></i><?php echo JText::translate('VBRATESOVWCLOSERRP'); ?></button>
				<button type="button" class="btn btn-success" onclick="modRoomRatePlan('open');"><i class="vboicn-enter"></i><?php echo JText::translate('VBRATESOVWOPENRRP'); ?></button>
				<br clear="all" /><br />
				<button type="button" class="btn btn-danger" onclick="hideVboDialog();"><?php echo JText::translate('VBANNULLA'); ?></button>
			</div>
		</div>
	</div>
	<div class="vbo-info-overlay-loading">
		<div><?php echo JText::translate('VIKLOADING'); ?></div>
	</div>
</div>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="task" value="">
	<input type="hidden" name="option" value="com_vikbooking">
</form>

<a id="vbo-base-booknow-link" style="display: none;" href="index.php?option=com_vikbooking&task=calendar&cid[]=&checkin=&checkout=&adults=&children=&idprice=&booknow=1"></a>
<a class="vbo-basenavuri-details" href="index.php?option=com_vikbooking&task=editorder&goto=ratesoverv&cid[]=%d" style="display: none;"></a>

<script type="text/Javascript">
function vboFormatCalDate(elem, idc) {
	var vb_period = elem.parent().find('.vbo-'+idc+'-val').val();
	if (!vb_period || !vb_period.length) {
		return false;
	}
	var vb_period_parts = vb_period.split("/");
	if ('%d/%m/%Y' == '<?php echo $vbo_df; ?>') {
		var period_date = new Date(vb_period_parts[2], (parseInt(vb_period_parts[1]) - 1), parseInt(vb_period_parts[0], 10), 0, 0, 0, 0);
		var data = [parseInt(vb_period_parts[0], 10), parseInt(vb_period_parts[1]), vb_period_parts[2]];
	} else if ('%m/%d/%Y' == '<?php echo $vbo_df; ?>') {
		var period_date = new Date(vb_period_parts[2], (parseInt(vb_period_parts[0]) - 1), parseInt(vb_period_parts[1], 10), 0, 0, 0, 0);
		var data = [parseInt(vb_period_parts[1], 10), parseInt(vb_period_parts[0]), vb_period_parts[2]];
	} else {
		var period_date = new Date(vb_period_parts[0], (parseInt(vb_period_parts[1]) - 1), parseInt(vb_period_parts[2], 10), 0, 0, 0, 0);
		var data = [parseInt(vb_period_parts[2], 10), parseInt(vb_period_parts[1]), vb_period_parts[0]];
	}
	var elcont = elem.closest('.vbo-ratesoverview-period-boxes').find('.vbo-ratesoverview-'+idc);
	elcont.find('.vbo-ratesoverview-period-wday').text(vboMapWdays[period_date.getDay()]);
	elcont.find('.vbo-ratesoverview-period-mday').text(period_date.getDate());
	elcont.find('.vbo-ratesoverview-period-month').text(vboMapMons[period_date.getMonth()]);
	elem.closest('.vbo-ratesoverview-period-boxes').find('.vbo-ratesoverview-'+idc+'-icon').hide();
	data.push(elem.closest('.vbo-ratesoverview-period-boxes').find('.vbo-selperiod-rplanid').val());
	data.push(elem.closest('.vbo-ratesoverview-period-boxes').find('.vbo-selperiod-rplanid option:selected').text());
	data.push(elem.closest('.vbo-ratesoverview-period-boxes').find('.vbo-selperiod-rplanid option:selected').attr('data-defrate'));
	data.push(elem.attr('data-idroom'));
	data.push(elem.attr('data-roomname'));
	var struct = getPeriodStructure(data);
	if (idc.indexOf('from') >= 0) {
		//period from date selected
		if (!vbolistener.pickFirst(struct)) {
			//first already picked: update it
			vbolistener.first = struct;
		}
	}
	if (idc.indexOf('to') >= 0) {
		//period to date selected
		if (!vbolistener.pickFirst(struct)) {
			//first already picked
			if ((vbolistener.first.isBeforeThan(struct) || vbolistener.first.isSameDay(struct)) && vbolistener.first.isSameRplan(struct) && vbolistener.first.isSameRoom(struct)) {
				//last > first: pick last
				if (vbolistener.pickLast(struct)) {
					showVboDialogPeriod();
				}
			}
		}
	}
}
function vbCalcNights() {
	var vbcheckin = document.getElementById('checkindate').value;
	var vbcheckout = document.getElementById('checkoutdate').value;
	if (!vbcheckin.length || !vbcheckout.length) {
		return;
	}
	var vbcheckinp = vbcheckin.split("-");
	var vbcheckoutp = vbcheckout.split("-");
	var vbinmonth = parseInt(vbcheckinp[1]);
	vbinmonth = vbinmonth - 1;
	var vbinday = parseInt(vbcheckinp[2], 10);
	var vbcheckind = new Date(vbcheckinp[0], vbinmonth, vbinday);
	var vboutmonth = parseInt(vbcheckoutp[1]);
	vboutmonth = vboutmonth - 1;
	var vboutday = parseInt(vbcheckoutp[2], 10);
	var vbcheckoutd = new Date(vbcheckoutp[0], vboutmonth, vboutday);
	var vbdivider = 1000 * 60 * 60 * 24;
	var vbints = vbcheckind.getTime();
	var vboutts = vbcheckoutd.getTime();
	if (vboutts > vbints) {
		var utc1 = Date.UTC(vbcheckind.getFullYear(), vbcheckind.getMonth(), vbcheckind.getDate());
		var utc2 = Date.UTC(vbcheckoutd.getFullYear(), vbcheckoutd.getMonth(), vbcheckoutd.getDate());
		var vbnights = Math.ceil((utc2 - utc1) / vbdivider);
		if (vbnights > 0) {
			jQuery('#vbo-numnights').val(vbnights);
		}
	}
}

/**
 * Scrollable tables will show the arrows to navigate to the next N days.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
function vboDisplayNextDays(btn) {
	var cell = jQuery(btn).closest('.vbo-roverv-gonext-cell');
	if (!cell || !cell.length) {
		return;
	}
	var rid = cell.attr('data-rid');
	if (!rid || !rid.length) {
		return;
	}
	var table = jQuery('table.vbratesoverviewtable[data-idroom="' + rid + '"]');
	if (!table || !table.length) {
		return;
	}
	// get the datepicker calendar instance for this room
	var calendar = table.find('input.vbodatepicker');
	try {
		// calculate the new date
		var cur_date = calendar.datepicker('getDate');
		var dobj = new Date(cur_date);
		dobj.setDate(dobj.getDate() + <?php echo isset($MAX_DAYS) ? $MAX_DAYS : 60; ?>);
		// set the new date
		calendar.datepicker('setDate', dobj);
		// populate hidden form fields
		var parentform = calendar.closest('form');
		var roomsids = jQuery('#roomsel').val();
		if (roomsids) {
			if (!Array.isArray(roomsids)) {
				// if there is just one room type, the select is not multiple, so this is a string
				roomsids = [jQuery('#roomsel').val()];
			}
			jQuery.each(roomsids, function(k, v) {
				parentform.append('<input type="hidden" name="cid[]" value="' + v + '" />');
			});
		}
		// auto-submit form with new date
		parentform.submit();
	} catch(e) {
		console.error(e);
		alert('Calendar not found. Please change date manually.');
	}
}

jQuery(function() {
	jQuery('.vbodatepicker').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		minDate: '-1y',
		numberOfMonths: 2,
		changeMonth: true,
		changeYear: true,
		yearRange: '<?php echo (date('Y') - 1).':'.(date('Y') + 3); ?>',
		onSelect: function(selectedDate) {
			var parentform = jQuery(this).closest('form');
			var roomsids = jQuery('#roomsel').val();
			if (roomsids) {
				if (!Array.isArray(roomsids)) {
					// if there is just one room type, the select is not multiple, so this is a string
					roomsids = [jQuery('#roomsel').val()];
				}
				jQuery.each(roomsids, function(k, v) {
					parentform.append('<input type="hidden" name="cid[]" value="' + v + '" />');
				});
			}
			parentform.submit();
		}
	});
	jQuery('#checkindate').datepicker({
		showOn: 'focus',
		dateFormat: 'yy-mm-dd',
		minDate: '0d',
		numberOfMonths: 2,
		onSelect: function(selectedDate) {
			var nownights = parseInt(jQuery('#vbo-numnights').val());
			var nowcheckin = jQuery('#checkindate').datepicker('getDate');
			var nowcheckindate = new Date(nowcheckin.getTime());
			nowcheckindate.setDate(nowcheckindate.getDate() + nownights);
			jQuery('#checkoutdate').datepicker( 'option', 'minDate', nowcheckindate );
			jQuery('#checkoutdate').datepicker( 'setDate', nowcheckindate );
			vbCalcNights();
		}
	});
	jQuery('#checkoutdate').datepicker({
		showOn: 'focus',
		dateFormat: 'yy-mm-dd',
		minDate: '0d',
		numberOfMonths: 2,
		onSelect: function(selectedDate) {
			if (!jQuery('#checkoutdate').val().length) {
				return;
			}
			vbCalcNights();
		}
	});
	jQuery('#checkindate-trig, #checkoutdate-trig').click(function() {
		var jdp = jQuery(this).prev('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});
	jQuery('#vbo-numnights').on('change keyup', function() {
		if (!jQuery('#checkindate').length) {
			return;
		}
		var nownights = parseInt(jQuery(this).val());
		var nowcheckin = jQuery('#checkindate').datepicker('getDate');
		var nowcheckindate = new Date(nowcheckin.getTime());
		nowcheckindate.setDate(nowcheckindate.getDate() + nownights);
		jQuery('#checkoutdate').datepicker( 'option', 'minDate', nowcheckindate );
		jQuery('#checkoutdate').datepicker( 'setDate', nowcheckindate );
	});
	jQuery('.vbo-period-from').datepicker({
		dateFormat: '<?php echo $juidf; ?>',
		minDate: '0d',
		altField: '.vbo-period-from-val',
		onSelect: function(selectedDate) {
			jQuery(this).parent().find('.vbo-period-from-val').val(selectedDate);
			jQuery(this).closest('.vbo-ratesoverview-period-box-cals').find('.vbo-period-to').datepicker("option", "minDate", selectedDate);
			vboFormatCalDate(jQuery(this), 'period-from');
		}
	});
	jQuery('.vbo-period-to').datepicker({
		dateFormat: '<?php echo $juidf; ?>',
		minDate: '0d',
		altField: '.vbo-period-to-val',
		onSelect: function(selectedDate) {
			jQuery(this).parent().find('.vbo-period-to-val').val(selectedDate);
			jQuery(this).closest('.vbo-ratesoverview-period-box-cals').find('.vbo-period-from').datepicker("option", "maxDate", selectedDate);
			vboFormatCalDate(jQuery(this), 'period-to');
		}
	});
	jQuery('.vbo-ratesoverview-period-box-left, .vbo-ratesoverview-period-box-right').click(function() {
		jQuery(this).closest('.vbo-ratesoverview-period-boxes').find('.vbo-ratesoverview-period-box-cals').fadeToggle();
	});
	jQuery("#roomsel, #roomselcalc").select2();
});
<?php
if ($df == "Y/m/d") {
	?>
Date.prototype.format = "yy/mm/dd";
	<?php
} elseif ($df == "m/d/Y") {
	?>
Date.prototype.format = "mm/dd/yy";
	<?php
} else {
	?>
Date.prototype.format = "dd/mm/yy";
	<?php
}
?>
Date.prototype.datesep = "<?php echo addslashes(VikBooking::getDateSeparator()); ?>";
var currencysymb = '<?php echo $currencysymb; ?>';
var debug_mode = '<?php echo $pdebug; ?>';
var vcm_exists = <?php echo VikBooking::vcmAutoUpdate(); ?>;
var roverw_messages = {
	"setNewRatesMissing": "<?php echo addslashes(JText::translate('VBRATESOVWERRNEWRATE')); ?>",
	"modRplansMissing": "<?php echo addslashes(JText::translate('VBRATESOVWERRMODRPLANS')); ?>",
	"openSpLink": "<?php echo addslashes(JText::translate('VBRATESOVWOPENSPL')); ?>",
	"vcmRatesChanged": "<?php echo addslashes(JText::translate('VBRATESOVWVCMRCHANGED')); ?>",
	"vcmRatesChangedOpen": "<?php echo addslashes(JText::translate('VBRATESOVWVCMRCHANGEDOPEN')); ?>",
	"notesForRoom": "<?php echo addslashes(JText::translate('VBONOTESFORROOM')); ?>"
};
var vboFests = <?php echo json_encode($this->festivities); ?>;
var vboRdayNotes = <?php echo json_encode($this->rdaynotes); ?>;
</script>

<script type="text/Javascript">
/**
 * Open a booking in a new tab.
 */
function vboRovervOpenBooking(bid) {
	var open_url = jQuery('.vbo-basenavuri-details').attr('href');
	open_url = open_url.replace('%d', bid);
	// navigate in a new tab
	window.open(open_url, '_blank');
}

/* Labels for months and weekdays */
var vbowdays = <?php echo json_encode($long_days_labels); ?>;
var vbomonths = <?php echo json_encode($long_months_labels); ?>;
/* Dates selection - Start */
var vbolistener = null;
var vbodialog_on = false;
var vbodialogfests_on = false;

jQuery(function() {
	// fests
	jQuery(document.body).on("click", "td.bluedays", function() {
		if (jQuery(this).hasClass('skip-bluedays-click')) {
			return;
		}
		var ymd = jQuery(this).attr('data-ymd');
		var daytitle = jQuery(this).attr('data-readymd');
		if (jQuery(this).hasClass('vbo-roverv-festcell')) {
			// cell has fests
			if (!vboFests.hasOwnProperty(ymd)) {
				return;
			}
			vboRenderFests(ymd, daytitle);
		} else {
			// let the admin create a new fest
			// set day title
			jQuery('.vbo-info-overlay-content-fests').find('h3').find('span').text(daytitle);
			// update ymd key for the selected date, useful for adding new fests
			jQuery('.vbo-overlay-fests-addnew').attr('data-ymd', ymd);
			// unset content and display modal for just adding a new fest
			jQuery('.vbo-overlay-fests-list').html('');
			jQuery('.vbo-secondinfo-overlay-block').fadeIn();
			vbodialogfests_on = true;
		}
		if (jQuery(this).hasClass('vbo-roverw-roomdaynote-full')) {
			// display the room-day notes
			vboRenderRdayNotes(ymd, jQuery(this).attr('data-rid'));
		}
	});
	//
	vbolistener = new CalendarListener();
	jQuery('.day-block').click(function() {
		pickBlock(jQuery(this).attr('id'));
	});
	jQuery('.day-block').hover(
		function() {
			if (vbolistener.isFirstPicked() && !vbolistener.isLastPicked()) {
				var struct = initBlockStructure(jQuery(this).attr('id'));
				var all_blocks = getAllBlocksBetween(vbolistener.first, struct, false);
				if (all_blocks !== false) {
					jQuery.each(all_blocks, function(k, v) {
						if (!v.hasClass('block-picked-middle')) {
							v.addClass('block-picked-middle');
						}
					});
					jQuery(this).addClass('block-picked-end');
				}
			}
		},
		function() {
			if (!vbolistener.isLastPicked()) {
				jQuery('.day-block').removeClass('block-picked-middle block-picked-end');
			}
		}
	);
	jQuery(document).keydown(function(e) {
		if (e.keyCode == 27) {
			hideVboDialog();
			if (vbodialogfests_on === true) {
				hideVboDialogFests();
			}
		}
	});
	jQuery(document).mouseup(function(e) {
		if (!vbodialog_on && !vbodialogfests_on) {
			return false;
		}
		if (vbodialog_on) {
			var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboDialog();
			}
		}
		if (vbodialogfests_on) {
			var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboDialogFests();
			}
		}
	});
	jQuery("body").on("click", ".vbo-roverw-daymod-infospids", function() {
		var helem = jQuery(this).next('.vbo-roverw-daymod-infospids-outcont');
		if (helem.length && helem.is(":visible")) {
			jQuery(this).removeClass("vbo-roverw-daymod-infospids-on");
			helem.hide();
		} else {
			jQuery(".vbo-roverw-daymod-infospids-on").removeClass("vbo-roverw-daymod-infospids-on");
			jQuery(".vbo-roverw-daymod-infospids-outcont").hide();
			jQuery(this).addClass("vbo-roverw-daymod-infospids-on");
			helem.show();
		}
	});
	jQuery('.vbo-roverw-closeopenrp h4').click(function() {
		jQuery('.vbo-roverw-closeopenrp-btns').fadeToggle();
	});
	jQuery(document.body).on('click', '.vbo-ratesoverview-vcmwarn-close', function() {
		vcm_exists = 0;
		jQuery('.vbo-ratesoverview-right-inner').hide().html('');
	});

	// show booking details
	jQuery('.vbo-roverw-daybusy[data-bids]').click(function() {
		var date_bids = jQuery(this).attr('data-bids');
		if (!date_bids || !date_bids.length) {
			return;
		}

		// get room name, date and day-bids
		var rid = jQuery(this).closest('table').attr('data-idroom');
		var room_name = jQuery('.vbo-ratesoverview-roomname[data-idroom="' + rid + '"]').text();
		var date_read = jQuery(this).attr('data-vbodateread');
		var def_bicon = '<?php VikBookingIcons::e('user', 'vbo-dashboard-guest-activity-avatar-icon'); ?>';
		var closure_i = '<?php VikBookingIcons::e('ban', 'vbo-dashboard-guest-activity-avatar-icon'); ?>';

		// display modal with booking details
		var rday_bookings_modal_body = VBOCore.displayModal({
			suffix: 	   'roverv-rdaybookings',
			extra_class:   'vbo-modal-rounded vbo-modal-tall',
			title: 		   room_name + ' - ' + date_read,
			dismiss_event: 'vbo-dismiss-modal-roverv-rdaybookings',
			loading_event: 'vbo-loading-modal-roverv-rdaybookings',
			loading_body:  '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>',
		});

		// show loading
		VBOCore.emitMultitaskEvent('vbo-loading-modal-roverv-rdaybookings');

		// make the request to get the bookings information
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=getbookingsinfo'); ?>",
			{
				status: 'any',
				idorders: date_bids,
				tmpl: 'component'
			},
			(res) => {
				// stop loading
				VBOCore.emitMultitaskEvent('vbo-loading-modal-roverv-rdaybookings');
				try {
					var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
					// build the HTML response
					var rday_bookings_html = '';
					rday_bookings_html += '<div class="vbo-dashboard-guests-latest">' + "\n";
					rday_bookings_html += '	<div class="vbo-dashboard-guest-messages-list">' + "\n";
					for (var b in obj_res) {
						if (!obj_res.hasOwnProperty(b)) {
							continue;
						}
						// nights and guests
						var nights_guests = [
							obj_res[b]['roomsnum'] + ' ' + Joomla.JText._((obj_res[b]['roomsnum'] > 1 ? 'VBPVIEWORDERSTHREE' : 'VBEDITORDERTHREE')),
							obj_res[b]['days'] + ' ' + Joomla.JText._((obj_res[b]['days'] > 1 ? 'VBDAYS' : 'VBDAY')),
							obj_res[b]['tot_adults'] + ' ' + Joomla.JText._((obj_res[b]['tot_adults'] > 1 ? 'VBMAILADULTS' : 'VBMAILADULT'))
						];
						if (obj_res[b]['tot_children'] > 0) {
							nights_guests.push(obj_res[b]['tot_children'] + ' ' + Joomla.JText._((obj_res[b]['tot_children'] > 1 ? 'VBMAILCHILDREN' : 'VBMAILCHILD')));
						}

						// build booking structure
						rday_bookings_html += '	<div class="vbo-dashboard-guest-activity vbo-w-guestmessages-message" data-idorder="' + obj_res[b]['id'] + '" onclick="vboRovervOpenBooking(\'' + obj_res[b]['id'] + '\');">' + "\n";
						rday_bookings_html += '		<div class="vbo-dashboard-guest-activity-avatar">' + "\n";
						if (obj_res[b]['avatar_src']) {
							rday_bookings_html += '<img class="vbo-dashboard-guest-activity-avatar-profile" src="' + obj_res[b]['avatar_src'] + '" alt="' + obj_res[b]['avatar_alt'] + '" />' + "\n";
						} else if (obj_res[b]['closure']) {
							rday_bookings_html += closure_i + "\n";
						} else {
							rday_bookings_html += def_bicon + "\n";
						}
						rday_bookings_html += '		</div>' + "\n";
						rday_bookings_html += '		<div class="vbo-dashboard-guest-activity-content">' + "\n";
						rday_bookings_html += '			<div class="vbo-dashboard-guest-activity-content-head">' + "\n";
						rday_bookings_html += '				<div class="vbo-dashboard-guest-activity-content-info-details">' + "\n";
						rday_bookings_html += '					<h4 class="vbo-w-guestmessages-message-gtitle">' + (!obj_res[b]['closure'] ? obj_res[b]['cinfo'] : obj_res[b]['closure_txt']) + '</h4>' + "\n";
						rday_bookings_html += '					<div class="vbo-dashboard-guest-activity-content-info-icon">' + "\n";
						rday_bookings_html += '						<span class="label label-info">' + obj_res[b]['id'] + '</span> ' + "\n";
						rday_bookings_html += '						<span class="badge badge-' + (obj_res[b]['status'] == 'confirmed' ? 'success' : 'warning') + '">' + obj_res[b]['status_lbl'] + '</span>' + "\n";
						rday_bookings_html += '							<span class="vbo-w-guestmessages-message-staydates">' + "\n";
						rday_bookings_html += '							<span class="vbo-w-guestmessages-message-staydates-in">' + obj_res[b]['checkin_short'] + '</span>' + "\n";
						rday_bookings_html += '							<span class="vbo-w-guestmessages-message-staydates-sep">-</span>' + "\n";
						rday_bookings_html += '							<span class="vbo-w-guestmessages-message-staydates-out">' + obj_res[b]['checkout_short'] + '</span>' + "\n";
						rday_bookings_html += '						</span>' + "\n";
						rday_bookings_html += '					</div>' + "\n";
						rday_bookings_html += '				</div>' + "\n";
						rday_bookings_html += '				<div class="vbo-dashboard-guest-activity-content-info-date">' + "\n";
						rday_bookings_html += '					<span>' + obj_res[b]['book_date'] + '</span>' + "\n";
						rday_bookings_html += '					<span>' + obj_res[b]['book_time'] + '</span>' + "\n";
						rday_bookings_html += '				</div>' + "\n";
						rday_bookings_html += '			</div>' + "\n";
						rday_bookings_html += '			<div class="vbo-dashboard-guest-activity-content-info-msg">' + "\n";
						rday_bookings_html += '				<div>' + nights_guests.join(', ') + '</div>' + "\n";
						if (obj_res[b].hasOwnProperty('sub_units_data')) {
							rday_bookings_html += '			<div class="vbo-rdaybooking-subunits">' + "\n";
							for (let sub_rname in obj_res[b]['sub_units_data']) {
								if (!obj_res[b]['sub_units_data'].hasOwnProperty(sub_rname)) {
									continue;
								}
								rday_bookings_html += '<span class="label label-success">' + (obj_res[b]['roomsnum'] > 1 ? sub_rname + ' ' : '') + '#' + obj_res[b]['sub_units_data'][sub_rname] + '</span>' + "\n";
							}
							rday_bookings_html += '			</div>' + "\n";
						}
						rday_bookings_html += '			</div>' + "\n";
						rday_bookings_html += '		</div>' + "\n";
						rday_bookings_html += '	</div>' + "\n";
					}
					rday_bookings_html += '	</div>' + "\n";
					rday_bookings_html += '</div>' + "\n";

					// append the response
					rday_bookings_modal_body.html(rday_bookings_html);
				} catch(err) {
					console.error('could not parse JSON response', err, res);
					alert('Could not parse JSON response');
				}
			},
			(err) => {
				// stop loading and display alert message
				VBOCore.emitMultitaskEvent('vbo-loading-modal-roverv-rdaybookings');
				console.error(err);
				alert(err.responseText);
			}
		);
	});
});

/**
 * Room-day notes
 */
function vboRenderRdayNotes(day, rid) {
	// compose room-day notes information (no sub-units)
	var notes_html = '';
	var keyid = day + '_' + rid + '_0';
	if (vboRdayNotes.hasOwnProperty(keyid) && vboRdayNotes[keyid]['info'] && vboRdayNotes[keyid]['info'].length) {
		for (var i = 0; i < vboRdayNotes[keyid]['info'].length; i++) {
			var note_data = vboRdayNotes[keyid]['info'][i];
			notes_html += '<div class="vbo-overlay-fest-details vbo-roverv-roomdaynotes-note-details">';
			notes_html += '	<div class="vbo-fest-info vbo-roverv-roomdaynotes-note-info">';
			notes_html += '		<div class="vbo-fest-name vbo-roverv-roomdaynotes-note-name">' + note_data['name'] + '</div>';
			notes_html += '		<div class="vbo-fest-desc vbo-roverv-roomdaynotes-note-desc">' + note_data['descr'].replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2') + '</div>';
			notes_html += '	</div>';
			notes_html += '</div>';
		}
	}
	// append room-day notes to the list of festivities
	if (notes_html.length) {
		notes_html = '<h4 class="vbo-roverw-roomdaynotes-title">' + roverw_messages.notesForRoom + '</h4>' + notes_html;
		jQuery('.vbo-overlay-fests-list').append(notes_html);
	}
}

/**
 * Fests
 */
function vboRenderFests(day, daytitle) {
	// set day title
	if (daytitle) {
		jQuery('.vbo-info-overlay-content-fests').find('h3').find('span').text(daytitle);
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
	jQuery('.vbo-secondinfo-overlay-block').fadeIn();
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
					jQuery('td.bluedays[data-ymd="'+day+'"]').removeClass('vbo-roverv-festcell');
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
				jQuery('td.bluedays[data-ymd="'+stored_fest['dt']+'"]').addClass('vbo-roverv-festcell');
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
/**
 * Fests dialog
 */
function hideVboDialogFests() {
	if (vbodialogfests_on === true) {
		jQuery(".vbo-secondinfo-overlay-block").fadeOut(400, function () {
			jQuery(".vbo-info-overlay-content").show();
		});
		vbodialogfests_on = false;
	}
}
//

function checkInvokeVcm() {
	if (!vbolistener || !vbolistener.first || !vbolistener.first.rplan) {
		return;
	}
	var rplanid = vbolistener.first.rplan;
	var idroom = vbolistener.first.idroom;
	var curval = document.getElementById('roverw-newrate-vcm').value;
	if (parseInt(curval) < 0) {
		return;
	}
	var buiscuits = document.cookie;
	if (!buiscuits.length) {
		return;
	}
	var vcmmatch = "vboVcmRov"+idroom+rplanid+"=";
	if (buiscuits.indexOf(vcmmatch) >= 0) {
		// last cookie does not terminate with ; so just use 0 to compare
		vcmmatch += "0";
		if (buiscuits.indexOf(vcmmatch) >= 0) {
			jQuery('#roverw-newrate-vcm').prop('checked', false);
		} else {
			jQuery('#roverw-newrate-vcm').prop('checked', true);
		}
	}
	vboCheckVcmRestrictions();
}

/**
 * Restrictions can be updated only if VCM is available and toggled ON, because
 * the creation and transmission is made through the Connector Class of VCM.
 *
 * @since 	1.11
 */
function vboCheckVcmRestrictions() {
	if (!jQuery('#roverw-newrate-vcm').prop('disabled') && jQuery('#roverw-newrate-vcm').prop('checked')) {
		jQuery('#roverw-newrestr').val('');
		jQuery('.vbo-roverw-newrestr-wrap').show();
	} else {
		jQuery('.vbo-roverw-newrestr-wrap').hide();
	}
}

function showVboDialog() {
	var format = new Date().format;
	format = format.replace(new RegExp("/", 'g'), new Date().datesep);
	jQuery("#rovervw-roomname").html(vbolistener.first.roomName);
	jQuery("#rovervw-rplan").html(vbolistener.first.rplanName);
	jQuery("#rovervw-closeopen-rplan").html('"'+vbolistener.first.rplanName+'"');
	jQuery("#rovervw-fromdate").html(vbolistener.first.toDate(format));
	jQuery("#rovervw-todate").html(vbolistener.last.toDate(format));
	jQuery(".vbo-roverw-alldays-inner").html("");
	var all_blocks = getAllBlocksBetween(vbolistener.first, vbolistener.last, true);
	if (all_blocks !== false) {
		var newdayscont = '';
		var highestrate = vbolistener.first.defRate;
		var allblocksclosed = true;
		jQuery.each(all_blocks, function(k, v) {
			if (!v.hasClass('vbo-roverw-rplan-off')) {
				allblocksclosed = false;
			}
			var spids = jQuery(v).attr("data-vbospids").split("-");
			var spids_det = '';
			if (jQuery(v).attr("data-vbospids").length > 0 && spids.length > 0) {
				spids_det += "<div class=\"vbo-roverw-daymod-infospids\"><span><i class=\"<?php echo VikBookingIcons::i('info-circle'); ?>\"></i></span></div>";
				spids_det += "<div class=\"vbo-roverw-daymod-infospids-outcont\">";
				spids_det += "<div class=\"vbo-roverw-daymod-infospids-incont\"><ul>";
				for (var x = 0; x < spids.length; x++) {
					spids_det += "<li><a target=\"_blank\" href=\"index.php?option=com_vikbooking&task=editseason&cid[]="+spids[x]+"\">"+roverw_messages.openSpLink.replace("%d", spids[x])+"</a></li>";
				}
				spids_det += "</ul></div></div>";
			}
			var dayrate = parseFloat(v.find('.vbo-rplan-price').text());
			if (!isNaN(dayrate) && dayrate > highestrate) {
				highestrate = dayrate;
			}
			newdayscont += "<div class=\"vbo-roverw-daymod\"><div class=\"vbo-roverw-daymod-inner\"><div class=\"vbo-roverw-daymod-innercell\"><span class=\"vbo-roverw-daydate\">"+jQuery(v).attr("data-vbodateread")+"</span><span class=\"vbo-roverw-dayprice\">"+v.html()+"</span>"+spids_det+"</div></div></div>";
		});
		jQuery(".vbo-roverw-alldays-inner").html(newdayscont);
		jQuery("#roverw-newrate").attr("placeholder", vbolistener.first.defRate).val(highestrate);
		// if all selected blocks have a closed rate plan, show it in red in the name of the rate plan because this will be triggered to VCM if "Apply" is clicked
		if (allblocksclosed) {
			jQuery("#rovervw-rplan").html('<span style="color: #f00"><i class="<?php echo VikBookingIcons::i('ban'); ?>"></i> '+vbolistener.first.rplanName+'</span>');
		}
	}
	checkInvokeVcm();

	jQuery(".vbo-info-overlay-block").fadeIn();
	vbodialog_on = true;
}

function showVboDialogPeriod() {
	var format = new Date().format;
	format = format.replace(new RegExp("/", 'g'), new Date().datesep);
	jQuery('.vbo-ratesoverview-period-box-cals').fadeOut();
	jQuery("#rovervw-roomname").html(vbolistener.first.roomName);
	jQuery("#rovervw-rplan").html(vbolistener.first.rplanName);
	jQuery("#rovervw-closeopen-rplan").html('"'+vbolistener.first.rplanName+'"');
	jQuery("#rovervw-fromdate").html(vbolistener.first.toDate(format));
	jQuery("#rovervw-todate").html(vbolistener.last.toDate(format));
	jQuery(".vbo-roverw-alldays-inner").html("");
	// reset default new price and placeholder
	jQuery("#roverw-newrate").attr("placeholder", "").val("");
	// check if all selected blocks are closed
	var all_blocks = getAllBlocksBetween(vbolistener.first, vbolistener.last, true);
	if (all_blocks !== false) {
		var allblocksclosed = true;
		jQuery.each(all_blocks, function(k, v) {
			if (!v.hasClass('vbo-roverw-rplan-off')) {
				allblocksclosed = false;
				return false;
			}
		});
		if (allblocksclosed) {
			jQuery("#rovervw-rplan").html('<span style="color: #f00"><i class="<?php echo VikBookingIcons::i('ban'); ?>"></i> '+vbolistener.first.rplanName+'</span>');
		}
	}
	//
	checkInvokeVcm();

	jQuery(".vbo-info-overlay-block").fadeIn();
	vbodialog_on = true;
}

function hideVboDialog() {
	vbolistener.clear();
	jQuery('.day-block').removeClass('block-picked-start block-picked-middle block-picked-end');
	if (vbodialog_on === true) {
		jQuery(".vbo-info-overlay-block").fadeOut(400, function () {
			jQuery(".vbo-info-overlay-content").show();
		});
		//reset period selection
		jQuery('.vbo-ratesoverview-period-from').find('span').text('');
		jQuery('.vbo-ratesoverview-period-from-icon').show();
		jQuery('.vbo-ratesoverview-period-to').find('span').text('');
		jQuery('.vbo-ratesoverview-period-to-icon').show();
		//
		vbodialog_on = false;
	}
}

function vboCheckVcmRatesChanges() {
	if (vcm_exists < 1) {
		return false;
	}
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=checkvcmrateschanges'); ?>",
		data: {
			tmpl: "component",
			e4j_debug: debug_mode
		}
	}).done(function(res) {
		if (res.indexOf('e4j.error') >= 0 ) {
			console.log(res);
			alert(res.replace("e4j.error.", ""));
			jQuery('.vbo-ratesoverview-right-inner').hide();
		} else {
			//display the VCM link for updating the rates on the OTAs
			var obj_res = JSON.parse(res);
			var esitcont = "";
			if (obj_res.changesCount > 0 && obj_res.hasOwnProperty('changesData') && obj_res.changesData.hasOwnProperty('dfrom')) {
				esitcont += "<span class=\"vbo-ratesoverview-vcmwarn-close\"> <i class=\"vboicn-cancel-circle\"></i></span>";
				esitcont += "<span class=\"vbo-ratesoverview-vcmwarn-count\"><i class=\"vboicn-notification\"></i> <span>"+roverw_messages.vcmRatesChanged.replace("%d", obj_res.changesCount)+"</span></span>";
				esitcont += "<span class=\"vbo-ratesoverview-vcmwarn-open\"><a href=\"index.php?option=com_vikchannelmanager&amp;task=ratespush&amp;vbosess=1\" class=\"btn btn-primary\">"+roverw_messages.vcmRatesChangedOpen+"</a></span>";
				jQuery('.vbo-ratesoverview-right-inner').html(esitcont).fadeIn();
			} else {
				jQuery('.vbo-ratesoverview-right-inner').hide().html('');
			}
		}
	}).fail(function() {
		console.log("vboCheckVcmRatesChanges Request Failed");
		jQuery('.vbo-ratesoverview-right-inner').hide();
	});
}

/* Delay and launch the check VCM rates modification function, when the page loads */
setTimeout(function() {
	vboCheckVcmRatesChanges();
}, 1000);
/* - */

function renderChannelManagerResult(obj) {
	console.log(obj);
	//compose modal body
	var htmlres = '<div class="vbo-vcm-rates-res-container">';
	if (obj.hasOwnProperty('channels_success')) {
		htmlres += '<div class="vbo-vcm-rates-res-success">';
		for (var ch_id in obj['channels_success']) {
			htmlres += '<div class="vbo-vcm-rates-res-channel">';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-esit">';
			htmlres += '		<i class="<?php echo VikBookingIcons::i('check'); ?>"></i>';
			htmlres += '	</div>';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-logo">';
			if (obj['channels_updated'].hasOwnProperty(ch_id) && obj['channels_updated'][ch_id]['logo'].length) {
				htmlres += '<img src="'+obj['channels_updated'][ch_id]['logo']+'" />';
			} else {
				htmlres += '<span>'+obj['channels_success'][ch_id]+'</span>';
			}
			htmlres += '	</div>';
			htmlres += '</div>';
		}
		if (obj.hasOwnProperty('channels_bkdown')) {
			htmlres += '<div class="vbo-vcm-rates-res-bkdown">';
			htmlres += '	<div><pre>'+obj['channels_bkdown']+'</pre></div>';
			htmlres += '</div>';
		}
		htmlres += '</div>';
	}
	if (obj.hasOwnProperty('channels_warnings')) {
		htmlres += '<div class="vbo-vcm-rates-res-warning">';
		for (var ch_id in obj['channels_warnings']) {
			htmlres += '<div class="vbo-vcm-rates-res-channel">';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-esit">';
			htmlres += '		<i class="<?php echo VikBookingIcons::i('exclamation-triangle'); ?>"></i>';
			htmlres += '	</div>';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-logo">';
			if (obj['channels_updated'].hasOwnProperty(ch_id) && obj['channels_updated'][ch_id]['logo'].length) {
				htmlres += '<img src="'+obj['channels_updated'][ch_id]['logo']+'" />';
			} else if (obj['channels_updated'].hasOwnProperty(ch_id)) {
				htmlres += '<span>'+obj['channels_updated'][ch_id]['name']+'</span>';
			}
			htmlres += '	</div>';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-det">';
			htmlres += '		<pre>'+obj['channels_warnings'][ch_id]+'</pre>';
			htmlres += '	</div>';
			htmlres += '</div>';
		}
		htmlres += '</div>';
	}
	if (obj.hasOwnProperty('channels_errors')) {
		htmlres += '<div class="vbo-vcm-rates-res-error">';
		for (var ch_id in obj['channels_errors']) {
			htmlres += '<div class="vbo-vcm-rates-res-channel">';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-esit">';
			htmlres += '		<i class="<?php echo VikBookingIcons::i('times'); ?>"></i>';
			htmlres += '	</div>';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-logo">';
			if (obj['channels_updated'].hasOwnProperty(ch_id) && obj['channels_updated'][ch_id]['logo'].length) {
				htmlres += '	<img src="'+obj['channels_updated'][ch_id]['logo']+'" />';
			} else if (obj['channels_updated'].hasOwnProperty(ch_id)) {
				htmlres += '	<span>'+obj['channels_updated'][ch_id]['name']+'</span>';
			}
			htmlres += '	</div>';
			htmlres += '	<div class="vbo-vcm-rates-res-channel-det">';
			htmlres += '		<pre>'+obj['channels_errors'][ch_id]+'</pre>';
			htmlres += '	</div>';
			htmlres += '</div>';
		}
		htmlres += '</div>';
	}
	htmlres += '</div>';
	//update modal body
	if (!jQuery('#jmodal-vbo-vcm-rates-res').find('.modal-body').length) {
		/**
		 * The class modal-body is appended (in WP) by the function vboOpenJModal,
		 * so it may not be available at this point of the code.
		 */
		jQuery('#jmodal-vbo-vcm-rates-res').find('.modal-body-wrapper').html('<div class="modal-body"></div>');
	}
	jQuery('#jmodal-vbo-vcm-rates-res').find('.modal-body').html(htmlres);
	//display modal with the results
	vboOpenJModal('vbo-vcm-rates-res');
}

function setNewRates() {
	var all_blocks = getAllBlocksBetween(vbolistener.first, vbolistener.last, true);
	var toval = jQuery("#roverw-newrate").val();
	var tovalint = parseFloat(toval);
	var invoke_vcm = jQuery("#roverw-newrate-vcm").is(":checked") ? 1 : 0;
	var setminlos = jQuery("#roverw-newrestr").val();
	var closerplan = 0;
	if (all_blocks !== false && toval.length > 0 && !isNaN(tovalint) && tovalint > 0.00) {
		// set cookie to remember the action to invoke VCM for this combination of room-rateplan
		var nd = new Date();
		nd.setTime(nd.getTime() + (365*24*60*60*1000));
		document.cookie = "vboVcmRov"+vbolistener.first.idroom+vbolistener.first.rplan+"="+invoke_vcm+"; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
		// check whether all blocks have closed the rate plan
		var allblocksclosed = true;
		jQuery.each(all_blocks, function(k, v) {
			if (!v.hasClass('vbo-roverw-rplan-off')) {
				allblocksclosed = false;
				// break
				return false;
			}
		});
		closerplan = allblocksclosed ? 1 : closerplan;
		//
		jQuery(".vbo-info-overlay-content").hide();
		jQuery(".vbo-info-overlay-loading").prepend('<i class="<?php echo VikBookingIcons::i('refresh', 'fa-spin fa-3x fa-fw'); ?>"></i>').fadeIn();
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=pricing.setnewrates'); ?>",
			data: {
				tmpl: "component",
				e4j_debug: debug_mode,
				id_room: vbolistener.first.idroom,
				id_price: vbolistener.first.rplan,
				rate: toval,
				vcm: invoke_vcm,
				minlos: setminlos,
				fromdate: vbolistener.first.toDate("yy-mm-dd"),
				todate: vbolistener.last.toDate("yy-mm-dd"),
				rateclosed: closerplan
			}
		}).done(function(res) {
			if (res.indexOf('e4j.error') >= 0) {
				console.log(res);
				alert(res.replace("e4j.error.", ""));
				jQuery(".vbo-info-overlay-content").show();
				jQuery(".vbo-info-overlay-loading").hide().find("i").remove();
			} else {
				//display new rates in all_blocks IDs
				var restr_set = false;
				var obj_res = JSON.parse(res);
				jQuery.each(obj_res, function(k, v) {
					if (k == 'vcm') {
						return true;
					}
					var elem = jQuery("#cell-"+k+"-"+vbolistener.first.idroom);
					if (elem.length) {
						elem.find(".vbo-rplan-price").html(v.cost);
						var spids = '';
						if (v.hasOwnProperty('spids')) {
							jQuery.each(v.spids, function(spk, spv) {
								spids += spv+'-';
							});
							//right trim dash
							spids = spids.replace(/-+$/, '');
						}
						elem.attr('data-vbospids', spids);
						// check if restrictions were set
						if (v.hasOwnProperty('newminlos')) {
							// always convert v.newminlos to a string to avoid errors with indexOf
							var newminlos = v.newminlos+'';
							if (newminlos.indexOf('e4j.error') >= 0) {
								// an error occurred
								alert(newminlos.replace("e4j.error.", ""));
							} else {
								// get cell identifier part
								restr_set = true;
								var cell_parts = k.split('-');
								var restr_elem = jQuery('#cell-'+cell_parts[0]+'-'+cell_parts[1]+'-'+cell_parts[2]+'-'+vbolistener.first.idroom+'-restr');
								if (restr_elem.length) {
									var restr_cont = restr_elem.find('.vbo-roverw-curminlos');
									restr_cont.html(newminlos);
									if (parseInt(newminlos) > 1) {
										restr_cont.addClass('vbo-roverw-curminlos-active');
									} else {
										restr_cont.removeClass('vbo-roverw-curminlos-active');
									}
								}
								// attempt to remove an eventual orphan list
								var orphan_elem = jQuery('.vbo-ratesoverview-orphan-dt[data-dt="'+cell_parts[0]+'-'+cell_parts[1]+'-'+cell_parts[2]+'-'+vbolistener.first.idroom+'"]');
								if (orphan_elem.length) {
									if (orphan_elem.length < 2) {
										jQuery('#vbo-ratesoverview-orphans-wrapper-'+vbolistener.first.idroom).fadeOut(400, function() {
											orphan_elem.remove();
										});
									} else {
										orphan_elem.remove();
									}
								}
							}
						}
						//
					}
				});
				jQuery(".vbo-info-overlay-loading").hide().find("i").remove();
				hideVboDialog();
				if (obj_res.hasOwnProperty('vcm')) {
					renderChannelManagerResult(obj_res['vcm']);
				} else {
					setTimeout(function() {
						vboCheckVcmRatesChanges();
					}, 500);
				}
				if (restr_set) {
					// re-calculate orphans after setting the restriction
					setTimeout(function() {
						vboCheckOrphans();
					}, 200);
				}
			}
		}).fail(function() { 
			alert("Request Failed");
			jQuery(".vbo-info-overlay-content").show();
			jQuery(".vbo-info-overlay-loading").hide().find("i").remove();
		});
	} else {
		alert(roverw_messages.setNewRatesMissing);
		return false;
	}
}

function modRoomRatePlan(mode) {
	var all_blocks = getAllBlocksBetween(vbolistener.first, vbolistener.last, true);
	if (all_blocks !== false && mode.length > 0) {
		jQuery(".vbo-info-overlay-content").hide();
		jQuery(".vbo-info-overlay-loading").prepend('<i class="<?php echo VikBookingIcons::i('refresh', 'fa-spin fa-3x fa-fw'); ?>"></i>').fadeIn();
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=modroomrateplans'); ?>",
			data: {
				tmpl: "component",
				e4j_debug: debug_mode,
				id_room: vbolistener.first.idroom,
				id_price: vbolistener.first.rplan,
				type: mode,
				fromdate: vbolistener.first.toDate("yy-mm-dd"),
				todate: vbolistener.last.toDate("yy-mm-dd")
			}
		}).done(function(res) {
			if (res.indexOf('e4j.error') >= 0 ) {
				console.log(res);
				alert(res.replace("e4j.error.", ""));
				jQuery(".vbo-info-overlay-content").show();
				jQuery(".vbo-info-overlay-loading").hide().find("i").remove();
			} else {
				//apply new classes in all_blocks IDs
				var obj_res = JSON.parse(res);
				jQuery.each(obj_res, function(k, v) {
					var elem = jQuery("#cell-"+k+"-"+vbolistener.first.idroom);
					if (elem.length) {
						elem.removeClass(v.oldcls).addClass(v.newcls);
					}
				});
				jQuery(".vbo-info-overlay-loading").hide().find("i").remove();
				hideVboDialog();
				setTimeout(function() {
					vboCheckVcmRatesChanges();
				}, 500);
			}
		}).fail(function() { 
			alert("Request Failed");
			jQuery(".vbo-info-overlay-content").show();
			jQuery(".vbo-info-overlay-loading").hide().find("i").remove();
		});
	} else {
		alert(roverw_messages.modRplansMissing);
		return false;
	}
}

function vboUpdateRplan(that) {
	if (vbolistener === null || vbolistener.first === null) {
		return true;
	}
	vbolistener.first.rplan = jQuery(that).val();
	vbolistener.first.rplanName = jQuery(that).find('option:selected').text();
	vbolistener.first.defRate = jQuery(that).find('option:selected').attr('data-defrate');
}

function pickBlock(id) {
	var struct = initBlockStructure(id);
	
	if (!vbolistener.pickFirst(struct)) {
		// first already picked
		if ((vbolistener.first.isBeforeThan(struct) || vbolistener.first.isSameDay(struct)) && vbolistener.first.isSameRplan(struct) && vbolistener.first.isSameRoom(struct)) {
			// last > first : pick last
			if (vbolistener.pickLast(struct)) {
				var all_blocks = getAllBlocksBetween(vbolistener.first, vbolistener.last, false);
				if (all_blocks !== false) {
					jQuery.each(all_blocks, function(k, v){
						if ( !v.hasClass('block-picked-middle') ) {
							v.addClass('block-picked-middle');
						}
					});
					jQuery('#'+vbolistener.last.id).addClass('block-picked-end');
					showVboDialog();
				}
			}
		} else {
			// last < first : clear selection
			vbolistener.clear();
			jQuery('.day-block').removeClass('block-picked-start block-picked-middle block-picked-end');
		}
	} else {
		// first picked
		jQuery('#'+vbolistener.first.id).addClass('block-picked-start');
	}
}

function getAllBlocksBetween(start, end, outers_included) {
	if (!start.isSameRplan(end) || !start.isSameRoom(end)) {
		return false;
	}
	
	if (start.isAfterThan(end)) {
		return false;
	}
	
	var queue = new Array();
	
	if (outers_included) {
		queue.push(jQuery('#'+start.id));
	}
	
	if (start.isSameDay(end)) {
		return queue;
	}

	var node = jQuery('#'+start.id).next();
	var end_id = jQuery('#'+end.id).attr('id');
	while (node.length > 0 && node.attr('id') != end_id) {
		queue.push(node);
		node = node.next();
	}
	
	if (outers_included) {
		queue.push(jQuery('#'+end.id));
	}
	
	return queue;
}

function getPeriodStructure(data) {
	return {
		"day": parseInt(data[0]),
		"month": parseInt(data[1]),
		"year": parseInt(data[2]),
		"rplan": data[3],
		"idroom": data[6],
		"roomName": data[7],
		"rplanName": data[4],
		"defRate": data[5],
		"id": "cell-"+parseInt(data[0])+"-"+parseInt(data[1])+"-"+parseInt(data[2])+"-"+data[3]+"-"+data[6],
		"isSameDay": function(block) {
			return (this.month == block.month && this.day == block.day && this.year == block.year);
		},
		"isBeforeThan": function(block) {
			return ( 
				(this.year < block.year) || 
				(this.year == block.year && this.month < block.month) || 
				(this.year == block.year &&  this.month == block.month && this.day < block.day)
			);
		},
		"isAfterThan": function(block) {
			return ( 
				(this.year > block.year) || 
				(this.year == block.year && this.month > block.month) || 
				(this.year == block.year && this.month == block.month && this.day > block.day)
			);
		},
		"isSameRplan": function(block) {
			return (this.rplan == block.rplan);
		},
		"isSameRoom": function(block) {
			return (this.idroom == block.idroom);
		},
		"toDate": function(format) {
			return format.replace(
				'dd', (this.day < 10 ? '0' : '' ) + this.day
			).replace(
				'mm', (this.month < 10 ? '0' : '') + this.month
			).replace(
				'yy', this.year
			);
		}
	};
}

function initBlockStructure(id) {
	var s = id.split("-");
	if (s.length != 6) {
		return {};
	}
	var elem = jQuery("#"+id);
	return {
		"day": parseInt(s[1]),
		"month": parseInt(s[2]),
		"year": parseInt(s[3]),
		"rplan": s[4],
		"idroom": s[5],
		"roomName": elem.parent("tr").find("td").first().attr("data-roomname"),
		"rplanName": elem.parent("tr").find("td").first().find(".vbo-rplan-name").text(),
		"defRate": elem.parent("tr").find("td").first().attr("data-defrate"),
		"id": id,
		"isSameDay": function(block) {
			return (this.month == block.month && this.day == block.day && this.year == block.year);
		},
		"isBeforeThan": function(block) {
			return ( 
				(this.year < block.year) || 
				(this.year == block.year && this.month < block.month) || 
				(this.year == block.year && this.month == block.month && this.day < block.day)
			);
		},
		"isAfterThan": function(block) {
			return ( 
				(this.year > block.year) || 
				(this.year == block.year && this.month > block.month) || 
				(this.year == block.year && this.month == block.month && this.day > block.day)
			);
		},
		"isSameRplan": function(block) {
			return (this.rplan == block.rplan);
		},
		"isSameRoom": function(block) {
			return (this.idroom == block.idroom);
		},
		"toDate": function(format) {
			return format.replace(
				'dd', (this.day < 10 ? '0' : '') + this.day
			).replace(
				'mm', (this.month < 10 ? '0' : '') + this.month
			).replace(
				'yy', this.year
			);
		}
	};
}

function CalendarListener() {
	this.first = null;
	this.last = null;
}

CalendarListener.prototype.pickFirst = function(struct) {
	if (!this.isFirstPicked()) {
		this.first = struct;
		return true;
	}
	return false;
}

CalendarListener.prototype.pickLast = function(struct) {
	if (!this.isLastPicked() && this.isFirstPicked()) {
		this.last = struct;
		return true;
	}
	return false;
}

CalendarListener.prototype.clear = function() {
	this.first = null;
	this.last = null;
}

CalendarListener.prototype.isFirstPicked = function() {
	return this.first != null;
}

CalendarListener.prototype.isLastPicked = function() {
	return this.last != null;
}

/* Dates selection - End */

function vboToggleOBPRows() {
	jQuery('tr.vbo-roverviewtablerow-occupancy').toggle();
	var newcookieval = 0;
	if (jQuery('tr.vbo-roverviewtablerow-occupancy').length && jQuery('tr.vbo-roverviewtablerow-occupancy').is(':visible')) {
		newcookieval = 1;
	}
	var nd = new Date();
	nd.setTime(nd.getTime() + (365*24*60*60*1000));
	document.cookie = "vboRovwObp=" + newcookieval + "; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
}

function vboToggleCollapse() {
	var expand = jQuery('input[name="vbo_collapse"]').is(':checked');
	var collapse = expand ? 0 : 1;
	if (expand) {
		jQuery('.vbo-ratesoverview-roomsel-entry-calc-inner, .vbo-roverv-forecast-inner').show();
		jQuery('.vbo-ratesoverview-top-container').removeClass('collapsed');
	} else {
		jQuery('.vbo-ratesoverview-roomsel-entry-calc-inner, .vbo-roverv-forecast-inner').hide();
		jQuery('.vbo-ratesoverview-top-container').addClass('collapsed');
	}
	var nd = new Date();
	nd.setTime(nd.getTime() + (365*24*60*60*1000));
	document.cookie = "vboRovwColl=" + collapse + "; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
}

function vboCheckCollapse() {
	if (jQuery('.vbo-ratesoverview-roomsel-entry-calc-inner').is(':visible') && jQuery('.vbo-roverv-forecast-inner').is(':visible')) {
		// all elements are visible, expanded
		return;
	}
	jQuery('input[name="vbo_collapse"]').trigger('click');
}

function vboSelectAllRooms(btn) {
	var sel_elem = jQuery('#roomsel');
	sel_elem.find('option').prop('selected', true);
	sel_elem.trigger('change');
	// hide button
	jQuery(btn).parent().remove();
	// auto-submit form
	document.vboratesovwform.submit();
}

var timeline_height_set = false;

jQuery(function() {
	// register to the event emitted when a new booking is created through an admin widget
	document.addEventListener('vbo_new_booking_created', (e) => {
		if (!e || !e.detail || !e.detail.hasOwnProperty('bid') || !e.detail['bid']) {
			// do nothing
			return;
		}
		// reload the page to display the new booking just created
		location.reload();
	});

	// toggle OBP rows
	if (jQuery('.vbo-ratesoverview-obp-toggle').length && jQuery('tr.vbo-roverviewtablerow-occupancy').length) {
		jQuery('.vbo-ratesoverview-obp-toggle').show();
		var initial = jQuery('.vbo-ratesoverview-obp-toggle').attr('data-obpstartstatus');
		if (initial == '0') {
			jQuery('tr.vbo-roverviewtablerow-occupancy').hide();
		}
	}
	//
	jQuery(".vbo-ratesoverview-tab-los").click(function() {
		var nd = new Date();
		nd.setTime(nd.getTime() + (365*24*60*60*1000));
		document.cookie = "vboRovwRab=los; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
		jQuery(this).removeClass("vbo-ratesoverview-tab-unactive").addClass("vbo-ratesoverview-tab-active");
		jQuery(".vbo-ratesoverview-tab-cal").removeClass("vbo-ratesoverview-tab-active").addClass("vbo-ratesoverview-tab-unactive");
		jQuery(".vbo-ratesoverview-roomsel-entry-los").show();
		jQuery(".vbo-ratesoverview-roomsel-entry-forecast").hide();
		jQuery(".vbo-ratesoverview-caltab-cont").hide();
		jQuery(".vbo-ratesoverview-lostab-cont").fadeIn();
		if (!timeline_height_set) {
			jQuery('.vbo-timeline-container').css('min-height', (jQuery('.vbo-timeline-container').outerHeight() + 20));
			timeline_height_set = true;
		}
	});
	jQuery(".vbo-ratesoverview-tab-cal").click(function() {
		var nd = new Date();
		nd.setTime(nd.getTime() + (365*24*60*60*1000));
		document.cookie = "vboRovwRab=cal; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
		jQuery(this).removeClass("vbo-ratesoverview-tab-unactive").addClass("vbo-ratesoverview-tab-active");
		jQuery(".vbo-ratesoverview-tab-los").removeClass("vbo-ratesoverview-tab-active").addClass("vbo-ratesoverview-tab-unactive");
		jQuery(".vbo-ratesoverview-roomsel-entry-los").hide();
		jQuery(".vbo-ratesoverview-roomsel-entry-forecast").show();
		jQuery(".vbo-ratesoverview-lostab-cont").hide();
		jQuery(".vbo-ratesoverview-caltab-cont").fadeIn();
	});
	if (window.location.hash == '#tabcal') {
		jQuery(".vbo-ratesoverview-tab-cal").trigger("click");
	}
	jQuery("body").on("click", ".vbo-ratesoverview-numnight", function() {
		var inpnight = jQuery(this).attr('id');
		if (jQuery('.vbo-ratesoverview-numnight').length > 1) {
			jQuery('#inp'+inpnight).remove();
			jQuery(this).remove();
		}
	});
	jQuery("body").on("dblclick", ".vbo-calcrates-rateblock", function() {
		if (jQuery(this).parent('.vbo-ratesoverview-calculation-response-room').find('.vbo-calcrates-rateblock').length < 2) {
			// remove the whole container as there is just one rate plan
			jQuery(this).parent('.vbo-ratesoverview-calculation-response-room').remove();
		} else {
			// remove only this rate plan
			jQuery(this).remove();
		}
	});
	jQuery('#vbo-addnumnight-act').click(function() {
		var setnights = jQuery('#vbo-addnumnight').val();
		if (parseInt(setnights) > 0) {
			var los_exists = false;
			jQuery('.vbo-ratesoverview-numnight').each(function() {
				if (parseInt(jQuery(this).text()) == parseInt(setnights)) {
					los_exists = true;
				}
			});
			if (!los_exists) {
				jQuery('.vbo-ratesoverview-numnight').last().after("<span class=\"vbo-ratesoverview-numnight\" id=\"numnights"+setnights+"\">"+setnights+"</span><input type=\"hidden\" name=\"nights_cal[]\" id=\"inpnumnights"+setnights+"\" value=\""+setnights+"\" />");
			} else {
				jQuery('#vbo-addnumnight').val((parseInt(setnights) + 1));
			}
		}
	});
	jQuery('#vbo-ratesoverview-calculate').click(function() {
		jQuery(this).text(Joomla.JText._('VBRATESOVWRATESCALCULATORCALCING')).prop('disabled', true);
		var checkindate = jQuery("#checkindate").val();
		if (!(checkindate.length > 0)) {
			checkindate = '<?php echo date('Y-m-d') ?>';
			jQuery("#checkindate").val(checkindate);
		}
		var nights = jQuery("#vbo-numnights").val();
		var adults = jQuery("#vbo-numadults").val();
		var children = jQuery("#vbo-numchildren").val();
		var idroom = jQuery("#roomselcalc").val();
		// always remove warning messages
		jQuery(".vbo-ratesoverview-calculation-response").find('.vbo-warning').remove();
		if (jQuery("#vbo-ratesoverview-calculation-response-room"+idroom).length) {
			// remove previous containers for this room
			jQuery("#vbo-ratesoverview-calculation-response-room"+idroom).remove();
		}
		if (!jQuery(".vbo-ratesoverview-calculation-response-room").length) {
			// if no rooms responses, empty the whole container
			jQuery('.vbo-ratesoverview-calculation-response').html('');
		}
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=calc_rates'); ?>",
			data: {
				tmpl: "component",
				id_room: idroom,
				checkin: checkindate,
				num_nights: nights,
				num_adults: adults,
				num_children: children
			}
		}).done(function(res) {
			res = JSON.parse(res);
			res = res[0];
			if (res.indexOf('e4j.error') >= 0 ) {
				jQuery(".vbo-ratesoverview-calculation-response").html("<p class='vbo-warning'>" + res.replace("e4j.error.", "") + "</p>").fadeIn();
			} else {
				var titlecont = '<span class="vbo-ratesoverview-calculation-response-room-name">'+jQuery("#roomselcalc option:selected").text() + '</span> ' + checkindate + ' - ' + jQuery("#checkoutdate").val() + ', ' + nights + ' <?php echo addslashes(JText::translate('VBRATESOVWRATESCALCNUMNIGHTS')); ?>, ' + adults + ' <?php echo addslashes(JText::translate('VBRATESOVWRATESCALCNUMADULTS')); ?>';
				var newcont = '<div class="vbo-ratesoverview-calculation-response-room" id="vbo-ratesoverview-calculation-response-room'+idroom+'"><h4>'+titlecont+'</h4>'+res+'</div>';
				// check whether the content should be appended
				if (jQuery(".vbo-ratesoverview-calculation-response").find('.vbo-ratesoverview-calculation-response-room').length) {
					newcont = jQuery(".vbo-ratesoverview-calculation-response").html() + newcont;
				}
				//
				jQuery(".vbo-ratesoverview-calculation-response").html(newcont).fadeIn();
				// loop over every room response and pricing to append the book-now button for the page calendar
				var base_booknow_link_orig = jQuery('#vbo-base-booknow-link').attr('href');
				jQuery('.vbo-calcrates-rateblock').each(function(k, v) {
					var elem = jQuery(v);
					var base_booknow_link = base_booknow_link_orig;
					// remove existing button
					elem.find('.vbo-room-booknow-rct').remove();
					//
					var b_idprice = elem.attr('data-idprice');
					base_booknow_link = base_booknow_link.replace('idprice=', 'idprice=' + b_idprice);
					var b_idroom = elem.attr('data-idroom');
					base_booknow_link = base_booknow_link.replace('cid[]=', 'cid[]=' + b_idroom);
					var b_checkin = elem.attr('data-checkin');
					base_booknow_link = base_booknow_link.replace('checkin=', 'checkin=' + b_checkin);
					var b_checkout = elem.attr('data-checkout');
					base_booknow_link = base_booknow_link.replace('checkout=', 'checkout=' + b_checkout);
					var b_adults = elem.attr('data-adults');
					base_booknow_link = base_booknow_link.replace('adults=', 'adults=' + b_adults);
					var b_children = elem.attr('data-children');
					base_booknow_link = base_booknow_link.replace('children=', 'children=' + b_children);
					var booknow = '<a href="' + base_booknow_link + '" class="btn btn-primary vbo-room-booknow-rct" target="_blank">' + Joomla.JText._('VBO_BOOKNOW') + '</a>';
					elem.append(booknow);
				});
				//
			}
			jQuery('#vbo-ratesoverview-calculate').text(Joomla.JText._('VBRATESOVWRATESCALCULATORCALC')).prop('disabled', false);
		}).fail(function() { 
			jQuery(".vbo-ratesoverview-calculation-response").fadeOut();
			jQuery('#vbo-ratesoverview-calculate').text(Joomla.JText._('VBRATESOVWRATESCALCULATORCALC')).prop('disabled', false);
			alert("Error Performing Ajax Request"); 
		});
	});

	/* Orphans Calculation */
	vboCheckOrphans();
	//
});

function vboCheckOrphans() {
	/* Orphans Calculation - Start */
	var calc_method = '<?php echo VikBooking::orphansCalculation(); ?>';
	jQuery('table.vbratesoverviewtable').each(function() {
		var current_room = jQuery(this).attr('data-idroom');
		var orphans_pool = new Array;
		var avcells 	 = jQuery(this).find('tr.vbo-roverviewtableavrow').find('td');
		var restrcells 	 = jQuery(this).find('tr.vbo-roverviewtablerow-restrs').find('td');
		avcells.each(function(k, v) {
			if (!jQuery(v).find('.vbo-roverw-curunits').length) {
				// continue
				return true;
			}
			var todayav = parseInt(jQuery(v).find('.vbo-roverw-curunits').text());
			if (!restrcells.hasOwnProperty(k) || isNaN(todayav) || todayav < 1) {
				// continue, no restriction cell found or no availability for this day
				return true;
			}
			var todayminlos = parseInt(jQuery(restrcells[k]).find('.vbo-roverw-curminlos').text());
			if (isNaN(todayminlos) || todayminlos < 2) {
				// continue, no min los > 1 set for this day
				return true;
			}
			// check if any night after today, until min los, is fully booked
			var cell_queue = new Array;
			var hasorphans = false;
			var forward_count = 0;
			cell_queue.push(v);
			for (var i = 1; i < todayminlos; i++) {
				if (!avcells.hasOwnProperty((k + i))) {
					// break loop, no info for this day after
					break;
				}
				var tomorrowav = parseInt(jQuery(avcells[(k + i)]).find('.vbo-roverw-curunits').text());
				if (isNaN(tomorrowav) || tomorrowav != 0) {
					// continue, availability found for tomorrow, we need a non available next-day
					continue;
				}
				// orphan found for this day
				hasorphans = true;
				forward_count = i;
				break;
			}
			// backward calculation method only if "prevnext".
			var backward_count = 0;
			for (var i = 1; i <= todayminlos; i++) {
				if (!avcells.hasOwnProperty((k - i))) {
					// break loop, no info for this prev day
					break;
				}
				var yesterdayav = parseInt(jQuery(avcells[(k - i)]).find('.vbo-roverw-curunits').text());
				if (isNaN(yesterdayav) || yesterdayav != 0) {
					// increase free nights going backward
					backward_count++;
				}
			}
			if (calc_method == 'prevnext' && hasorphans && backward_count > 0 && (backward_count >= todayminlos || (backward_count + forward_count) >= todayminlos)) {
				// this should not be an orphan date because of enough free days back, or enough free days in between
				hasorphans = false;
			}

			if (hasorphans && cell_queue.length) {
				for (var i in cell_queue) {
					if (cell_queue.hasOwnProperty(i)) {
						// push the cell
						orphans_pool.push(cell_queue[i]);
					}
				}
			}
		});
		if (orphans_pool.length) {
			var orphans_list = '';
			for (var i in orphans_pool) {
				if (!orphans_pool.hasOwnProperty(i)) {
					continue;
				}
				var idparts = jQuery(orphans_pool[i]).attr('id').split('-');
				orphans_list += '<div class="vbo-ratesoverview-orphan-dt" data-dt="'+idparts[1]+'-'+idparts[2]+'-'+idparts[3]+'-'+current_room+'">'+vbowdays[idparts[4]]+', '+vbomonths[(parseInt(idparts[2]) - 1)]+' '+idparts[1]+' '+idparts[3]+'</div>';
			}
			jQuery('#vbo-ratesoverview-orphans-list-'+current_room).html(orphans_list);
			jQuery('#vbo-ratesoverview-orphans-wrapper-'+current_room).fadeIn();
		}
	});
	/* Orphans Calculation - End */
}
</script>

<div class="vbo-secondinfo-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-fests">
		<h3><?php VikBookingIcons::e('star'); ?> <span></span></h3>
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
