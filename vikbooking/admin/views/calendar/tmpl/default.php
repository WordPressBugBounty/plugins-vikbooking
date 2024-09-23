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

$room = $this->room;
$allc = $this->allc;
$payments = $this->payments;
$busy = $this->busy;
$vmode = $this->vmode;

$dbo = JFactory::getDbo();
$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$vbo_app->loadDatePicker();

$document = JFactory::getDocument();
$document->addStyleSheet(VBO_ADMIN_URI.'resources/jquery.highlighttextarea.min.css');
JHtml::fetch('script', VBO_ADMIN_URI.'resources/jquery.highlighttextarea.min.js');

// JS lang def
JText::script('VBFILLCUSTFIELDS');
JText::script('VBMSGTHREE');
JText::script('VBMSGFOUR');
JText::script('VBDBTEXTROOMCLOSED');
JText::script('ORDER_NOTES');
JText::script('VBSUBMCLOSEROOM');
JText::script('VBCUSTINFO');
JText::script('VBMAKERESERV');
JText::script('VBDAY');
JText::script('VBOCALCLOSEOTHERROOMS');
JText::script('VBERRCUSTOMEREMAILEXISTS');
JText::script('VBAPPLY');
JText::script('VBANNULLA');
JText::script('VBO_MARK_UNITS_CLOSED');

$vbo_df = VikBooking::getDateFormat(true);
if ($vbo_df == "%d/%m/%Y") {
	$df = 'd/m/Y';
	$juidf = 'dd/mm/yy';
} elseif ($vbo_df == "%m/%d/%Y") {
	$df = 'm/d/Y';
	$juidf = 'mm/dd/yy';
} else {
	$df = 'Y/m/d';
	$juidf = 'yy/mm/dd';
}
$datesep = VikBooking::getDateSeparator(true);
$prices_vat_included = (int)VikBooking::ivaInclusa();

$pcheckin = VikRequest::getString('checkin', '', 'request');
$pcheckin_ymd = $pcheckin;
if (!empty($pcheckin)) {
	$pcheckin = date(str_replace('%', '', $vbo_df), strtotime($pcheckin));
}
$pcheckout = VikRequest::getString('checkout', '', 'request');
$pcheckout_ymd = $pcheckout;
if (!empty($pcheckout)) {
	$pcheckout = date(str_replace('%', '', $vbo_df), strtotime($pcheckout));
}

$ptmpl = VikRequest::getString('tmpl', '', 'request');
$poverview = VikRequest::getInt('overv', '', 'request');
$poverview_change = VikRequest::getInt('overview_change', 0, 'request');
$padults = VikRequest::getInt('adults', 0, 'request');
$pchildren = VikRequest::getInt('children', 0, 'request');
$pidprice = VikRequest::getInt('idprice', 0, 'request');
$pbooknow = VikRequest::getInt('booknow', 0, 'request');

/**
 * Split stay data for booking.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
$split_stay = VikRequest::getVar('split_stay', array());
$rooms_data = [];
foreach ($allc as $room_info) {
	$rooms_data[$room_info['id']] = $room_info['name'];
}

if ($this->new_res_id) {
	?>
<p class="successmade"><?php echo JText::translate('VBBOOKMADE'); ?> &nbsp;&nbsp;&nbsp; <a href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $this->new_res_id; ?>" target="_blank" class="btn"><i class="vboicn-eye"></i> <?php echo JText::translate('VBOVIEWBOOKINGDET'); ?></a></p>
	<?php
	if ($poverview > 0 && $ptmpl == 'component') {
		$poverview_change = 1;
	}
}

$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	$opent = VikBooking::getHoursMinutes($timeopst[0]);
	$closet = VikBooking::getHoursMinutes($timeopst[1]);
	$hcheckin = $opent[0];
	$mcheckin = $opent[1];
	$hcheckout = $closet[0];
	$mcheckout = $closet[1];
} else {
	$hcheckin = 0;
	$mcheckin = 0;
	$hcheckout = 0;
	$mcheckout = 0;
}
$formatparts = explode(':', VikBooking::getNumberFormatData());
$currencysymb = VikBooking::getCurrencySymb(true);
$globnumadults = VikBooking::getSearchNumAdults(true);
$adultsparts = explode('-', $globnumadults);
$seladults = "<select name=\"adults\" id=\"vbo-sel-adults\">\n";
for ($i = $adultsparts[0]; $i <= ((int)$adultsparts[1] * $room['units']); $i++) {
	$seladults .= "<option value=\"".$i."\"".((intval($adultsparts[0]) < 1 && $i == 1 && $padults < 1) || ($padults > 0 && $i == $padults) ? " selected=\"selected\"" : "").">".$i."</option>\n";
}
$seladults .= "</select>\n";
$globnumchildren = VikBooking::getSearchNumChildren(true);
$childrenparts = explode('-', $globnumchildren);
$selchildren = "<select name=\"children\" id=\"vbo-sel-children\">\n";
for ($i = $childrenparts[0]; $i <= ((int)$childrenparts[1] * $room['units']); $i++) {
	$selchildren .= "<option value=\"".$i."\"" . ($pchildren > 0 && $i == $pchildren ? ' selected="selected"' : '') . ">".$i."</option>\n";
}
$selchildren .= "</select>\n";
$selpayments = '<select name="payment"><option value="">'.JText::translate('VBPAYMUNDEFINED').'</option>';
if (is_array($payments) && $payments) {
	foreach ($payments as $pay) {
		$selpayments .= '<option value="'.$pay['id'].'">'.$pay['name'].'</option>';
	}
}
$selpayments .= '</select>';
// custom fields
$all_countries = array();
$q = "SELECT * FROM `#__vikbooking_custfields` ORDER BY `#__vikbooking_custfields`.`ordering` ASC;";
$dbo->setQuery($q);
$all_cfields = $dbo->loadAssocList();
if ($all_cfields) {
	$q = "SELECT * FROM `#__vikbooking_countries` ORDER BY `#__vikbooking_countries`.`country_name` ASC;";
	$dbo->setQuery($q);
	$all_countries = $dbo->loadAssocList();
}

$wiva = "";
$q = "SELECT * FROM `#__vikbooking_iva`;";
$dbo->setQuery($q);
$ivas = $dbo->loadAssocList();
if ($ivas) {
	foreach ($ivas as $kiv => $iv) {
		$wiva .= "<option value=\"".$iv['id']."\" data-aliqid=\"".$iv['id']."\"".($kiv < 1 ? ' selected="selected"' : '').">".(empty($iv['name']) ? $iv['aliq']."%" : $iv['name']." - ".$iv['aliq']."%")."</option>\n";
	}
}

// close other rooms select
$closeotherrooms = '';
if (count($allc) > 1) {
	$closeotherrooms = "<select id=\"vbo-calendar-closeall\" multiple=\"multiple\" name=\"closeothers[]\" onchange=\"vboCheckCloseOthers();\">\n";
	$closeotherrooms .= "<option></option>\n";
	$closeotherrooms .= "<option value=\"".$room['id']."\" data-currentr=\"true\" disabled=\"disabled\">".$room['name']."</option>\n";
	$closeotherrooms .= "<option value=\"-1\">- ".JText::translate('VBOCALCLOSEALLROOMSDT')."</option>\n";
	foreach ($allc as $cc) {
		if ($cc['id'] == $room['id']) {
			continue;
		}
		$closeotherrooms .= "<option value=\"".$cc['id']."\">".$cc['name']."</option>\n";
	}
	$closeotherrooms .= "</select>\n";
}

?>

<div class="vbo-admin-container">
	
	<div class="vbo-config-maintab-left">

		<fieldset class="adminform">
			<div class="vbo-params-wrap">
				<legend class="adminlegend">
					<div class="vbo-quickres-head">
						<span><?php echo $room['name'] . " - " . JText::translate('VBQUICKBOOK') . (!empty($split_stay) ? ' (' . JText::translate('VBO_SPLIT_STAY') . ')' : ''); ?></span>
						<div class="vbo-quickres-head-right">
							<form name="vbchroom" id="vbchroom" method="post" action="index.php?option=com_vikbooking">
								<input type="hidden" name="task" value="calendar"/>
								<input type="hidden" name="option" value="com_vikbooking"/>
								<select id="vbo-calendar-changeroom" name="cid[]" onchange="jQuery('#vbchroom').submit();">
								<?php
								foreach ($allc as $cc) {
									echo "<option value=\"".$cc['id']."\"".($cc['id'] == $room['id'] ? " selected=\"selected\"" : "").">".$cc['name']."</option>\n";
								}
								?>
								</select>
							<?php
							if ($ptmpl == 'component') {
								echo "<input type=\"hidden\" name=\"tmpl\" value=\"component\" />\n";
							}
							?>
							</form>
						</div>
					</div>
				</legend>
				<form name="newb" method="post" action="index.php?option=com_vikbooking" id="vbo-calendar-newb-form">
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBDATEPICKUP'); ?></div>
							<div class="vbo-param-setting">
								<div class="input-append">
									<input type="text" autocomplete="off" name="checkindate" id="checkindate" size="10" />
									<button type="button" class="btn vbodatepicker-trig-icon"><span class="icon-calendar"></span></button>
								</div>
								<span class="vbo-calendar-time-inline"><?php echo JText::translate('VBAT')." ".($hcheckin < 10 ? '0'.$hcheckin : $hcheckin).":".($mcheckin < 10 ? '0'.$mcheckin : $mcheckin); ?></span>
								<input type="hidden" name="checkinh" value="<?php echo $hcheckin; ?>"/>
								<input type="hidden" name="checkinm" value="<?php echo $mcheckin; ?>"/>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBDATERELEASE'); ?></div>
							<div class="vbo-param-setting">
								<div class="input-append">
									<input type="text" autocomplete="off" name="checkoutdate" id="checkoutdate" size="10" />
									<button type="button" class="btn vbodatepicker-trig-icon"><span class="icon-calendar"></span></button>
								</div>
								<span class="vbo-calendar-time-inline"><?php echo JText::translate('VBAT')." ".($hcheckout < 10 ? '0'.$hcheckout : $hcheckout).":".($mcheckout < 10 ? '0'.$mcheckout : $mcheckout); ?></span>
								<span style="display: none; margin-left: 25px; vertical-align: top;" id="vbjstotnights">
									<span style="font-weight: bold;"><?php echo JText::translate('VBDAYS'); ?></span>
									<input type="number" min="1" step="1" value="1" id="vbo-numnights" style="margin: 0;" />
								</span>
								<input type="hidden" name="checkouth" value="<?php echo $hcheckout; ?>"/>
								<input type="hidden" name="checkoutm" value="<?php echo $mcheckout; ?>"/>
							</div>
						</div>
						<div class="vbo-param-container" style="<?php echo !empty($split_stay) ? 'display: none;' : ''; ?>">
							<div class="vbo-param-label">
								<span class="vbcloseroomsp">
									<label for="setclosed-on"><?php echo JText::translate('VBCLOSEROOM'); ?> <i class="<?php echo VikBookingIcons::i('ban'); ?>" style="float: none;"></i></label>
								</span>
							</div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('setclosed', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, 'vbCloseRoom();'); ?>
								<div class="vbo-close-all-rooms-sel" id="vbo-close-all-rooms-sel" style="display: none;"><?php echo $closeotherrooms; ?></div>
							</div>
						</div>
					<?php
					if ($room['units'] > 1 && empty($split_stay)) {
						$num_rooms_vals = range(1, $room['units']);
						$num_rooms_opts = '';
						foreach ($num_rooms_vals as $nrv) {
							$num_rooms_opts .= '<option value="'.$nrv.'">'.$nrv.'</option>'."\n";
						}
						?>
						<div class="vbo-param-container" id="vbo-row-numrooms">
							<div class="vbo-param-label"><?php echo JText::translate('VBPVIEWROOMSEVEN'); ?></div>
							<div class="vbo-param-setting">
								<select name="num_rooms" id="vbo-sel-numrooms">
									<?php echo $num_rooms_opts; ?>
								</select>
							</div>
						</div>
						<?php
					} else {
						?>
						<input type="hidden" name="num_rooms" value="1"/>
						<?php
					}
					?>
						<div class="vbo-param-container" id="vbo-row-people">
							<div class="vbo-param-label"><?php echo JText::translate('VBQUICKRESGUESTS'); ?></div>
							<div class="vbo-param-setting">
								<span class="vbo-quickres-aduchi-wrap">
									<span class="vbo-quickres-aduchi-inlbl"><?php echo JText::translate('VBQUICKADULTS'); ?></span>
									<?php echo $seladults; ?>
								</span>
								<span class="vbo-quickres-aduchi-wrap">
									<span class="vbo-quickres-aduchi-inlbl"><?php echo JText::translate('VBQUICKCHILDREN'); ?></span>
									<?php echo $selchildren; ?>
								</span>
							</div>
						</div>
				<?php
				if (!empty($split_stay)) {
					foreach ($split_stay as $sps_k => $sps_v) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $sps_k < 1 ? '<i class="' . VikBookingIcons::i('random') . '"></i> ' . JText::translate('VBO_SPLIT_STAY') : ' '; ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-cal-splitstay-details">
									<div class="vbo-cal-splitstay-room">
										<span class="vbo-cal-splitstay-room-name"><?php VikBookingIcons::e('bed'); ?> <?php echo $rooms_data[$sps_v['idroom']]; ?></span>
										<span class="vbo-cal-splitstay-room-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $sps_v['nights']; ?> <?php echo $sps_v['nights'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'); ?></span>
									</div>
									<div class="vbo-cal-splitstay-dates">
										<span class="vbo-cal-splitstay-dates-in"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $datesep, $df), strtotime($sps_v['checkin'])); ?></span>
										<span class="vbo-cal-splitstay-dates-out"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo date(str_replace("/", $datesep, $df), strtotime($sps_v['checkout'])); ?></span>
									</div>
								</div>
							</div>
						</div>
						<?php
					}
				}
				?>
						<div class="vbo-param-container"<?php echo ($poverview > 0 ? ' style="display: none;"' : ''); ?> id="vbo-row-bstat">
							<div class="vbo-param-label"><?php echo JText::translate('VBCALBOOKINGSTATUS'); ?></div>
							<div class="vbo-param-setting">
								<select name="newstatus">
									<option value="confirmed"><?php echo JText::translate('VBCONFIRMED'); ?></option>
									<option value="standby"><?php echo JText::translate('VBSTANDBY'); ?></option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container" id="vbo-row-bpay">
							<div class="vbo-param-label"><?php echo JText::translate('VBCALBOOKINGPAYMENT'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $selpayments; ?>
							</div>
						</div>
						<div class="vbo-param-container" id="vbo-row-fillcustfields">
							<div class="vbo-param-label">&nbsp;</div>
							<div class="vbo-param-setting">
								<span class="vbo-assign-customer" id="vbfillcustfields">
									<i class="<?php echo VikBookingIcons::i('user-circle'); ?>"></i> 
									<span><?php echo JText::translate('VBFILLCUSTFIELDS'); ?></span>
								</span>
							</div>
						</div>
						<div class="vbo-param-container" id="vbo-row-cmail">
							<div class="vbo-param-label"><?php echo JText::translate('VBCUSTEMAIL'); ?></div>
							<div class="vbo-param-setting">
								<input type="text" name="custmail" id="custmailfield" value="" size="25"/>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label" id="vbcustdatalbl"><?php echo JText::translate('VBCUSTINFO'); ?></div>
							<div class="vbo-param-setting">
								<textarea name="custdata" id="vbcustdatatxtarea" rows="5" cols="70" style="min-width: 300px;"></textarea>
							</div>
						</div>
						<div class="vbo-param-container" id="vbo-website-rates-row" style="display: none;">
							<div class="vbo-param-label"><?php echo JText::translate('VBOWEBSITERATES'); ?></div>
							<div class="vbo-param-setting" id="vbo-website-rates-cont"></div>
						</div>
						<div class="vbo-param-container" id="vbo-row-custcost">
							<div class="vbo-param-label"><?php echo JText::translate('VBOROOMCUSTRATEPLANADD'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-calendar-costs-wrapper">
									<?php echo $currencysymb; ?> <input name="cust_cost" id="cust_cost" value="" onfocus="document.getElementById('taxid').style.display = 'inline-block';" onkeyup="vbCalcDailyCost(this.value);" onchange="vbCalcDailyCost(this.value);" type="number" step="any" min="0">
									<select class="vbo-calendar-taxid" name="taxid" id="taxid" style="display: none;">
										<option value=""><?php echo JText::translate('VBNEWOPTFOUR'); ?></option>
										<?php echo $wiva; ?>
									</select>
									<div id="avg-daycost" class="vbo-calendar-avg-daycost" style="display: none;">
										<select name="totalpnight">
											<option value="total"></option>
											<option value="pnight"></option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div id="vbo-force-bookingdates" class="vbo-param-container" style="display: none;">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBO_FORCE_BOOKDATES'), 'content' => JText::translate('VBO_FORCE_BOOKDATES_HELP'))); ?> <?php echo JText::translate('VBO_FORCE_BOOKDATES'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('forcebooking', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0); ?>
								<div class="vbo-param-setting-comment"></div>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label">&nbsp;</div>
							<div class="vbo-param-setting">
								<button type="submit" id="quickbsubmit" class="btn btn-success btn-large"><i class="icon-save"></i> <span><?php echo JText::translate('VBMAKERESERV'); ?></span></button>
							</div>
						</div>
					</div>
					<?php
					if ($poverview > 0) {
						?>
						<input type="hidden" name="overv" value="<?php echo $poverview; ?>" />
						<?php
					}
					if ($ptmpl == 'component') {
						?>
						<input type="hidden" name="tmpl" value="component" />
						<?php
					}
					if (!empty($split_stay)) {
						// add hidden fields for the split stay booking
						foreach ($split_stay as $spsk => $sps_room) {
							foreach ($sps_room as $sps_room_k => $sps_room_v) {
								?>
						<input type="hidden" name="split_stay_data[<?php echo $spsk; ?>][<?php echo $sps_room_k; ?>]" value="<?php echo JHtml::fetch('esc_attr', $sps_room_v); ?>" />
								<?php
							}
						}
					}
					?>
					<input type="hidden" name="customer_id" value="" id="customer_id_inpfield"/>
					<input type="hidden" name="countrycode" value="" id="ccode_inpfield"/>
					<input type="hidden" name="state" value="" id="state_inpfield"/>
					<input type="hidden" name="t_first_name" value="" id="t_first_name_inpfield"/>
					<input type="hidden" name="t_last_name" value="" id="t_last_name_inpfield"/>
					<input type="hidden" name="phone" value="" id="phonefield"/>
					<input type="hidden" name="idprice" value="" id="booking-idprice"/>
					<input type="hidden" name="roomcost" value="" id="booking-roomcost"/>
					<input type="hidden" name="task" value="calendar"/>
					<input type="hidden" name="cid[]" value="<?php echo $room['id']; ?>"/>
					<input type="hidden" name="option" value="com_vikbooking" />
				</form>
			</div>
		</fieldset>

	</div>

	<div class="vbo-config-maintab-right">
		<div class="vbo-avcalendars-wrapper">
			<div class="vbo-avcalendars-roomphoto">
			<?php
			if (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $room['img'])) {
				?>
				<img alt="Room Image" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room['img']; ?>" />
				<?php
			} else {
				VikBookingIcons::e('image', 'vbo-enormous-icn');
			}
			?>
			</div>
		<?php
		$check = false;
		if (empty($busy)) {
			?>
			<p class="warn"><?php echo JText::translate('VBNOFUTURERES'); ?></p>
			<?php
		} else {
			$check = true;
			// build URL for each number of calendar months
			$base_uri_data = [
				'option' => 'com_vikbooking',
				'task' => 'calendar',
				'cid' => [
					$room['id']
				],
				'split_stay' => $split_stay,
			];
			if (!empty($pcheckin_ymd) && !empty($pcheckout_ymd)) {
				$base_uri_data['checkin'] = $pcheckin_ymd;
				$base_uri_data['checkout'] = $pcheckout_ymd;
			}
			if ($padults > 0) {
				$base_uri_data['adults'] = $padults;
				$base_uri_data['children'] = $pchildren;
			}
			if (!empty($pidprice)) {
				$base_uri_data['idprice'] = $pidprice;
			}
			if (!empty($pbooknow)) {
				$base_uri_data['booknow'] = $pbooknow;
			}
			if ($ptmpl == 'component') {
				$base_uri_data['tmpl'] = 'component';
			}
			?>
			<div>
				<a class="vbmodelink<?php echo $vmode == 3 ? ' vbmodelink-active' : ''; ?>" href="index.php?<?php echo http_build_query(array_merge($base_uri_data, ['vmode' => 3])); ?>"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo JText::translate('VBTHREEMONTHS'); ?></span></a>
				<a class="vbmodelink<?php echo $vmode == 6 ? ' vbmodelink-active' : ''; ?>" href="index.php?<?php echo http_build_query(array_merge($base_uri_data, ['vmode' => 6])); ?>"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo JText::translate('VBSIXMONTHS'); ?></span></a>
				<a class="vbmodelink<?php echo $vmode == 12 ? ' vbmodelink-active' : ''; ?>" href="index.php?<?php echo http_build_query(array_merge($base_uri_data, ['vmode' => 12])); ?>"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo JText::translate('VBTWELVEMONTHS'); ?></span></a>
				<a class="vbmodelink<?php echo $vmode == 24 ? ' vbmodelink-active' : ''; ?>" href="index.php?<?php echo http_build_query(array_merge($base_uri_data, ['vmode' => 24])); ?>"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo JText::translate('VBTWOYEARS'); ?></span></a>
			</div>
			<?php
		}
		?>
			<div class="vbo-calendar-cals-container">
				<?php
				$arr = getdate();
				$mon = $arr['mon'];
				$year = $arr['year'];
				$dayts = mktime(0, 0, 0, $mon, 1, $year);
				$newarr = getdate($dayts);

				$firstwday = (int)VikBooking::getFirstWeekDay();
				$days_labels = array(
						JText::translate('VBSUN'),
						JText::translate('VBMON'),
						JText::translate('VBTUE'),
						JText::translate('VBWED'),
						JText::translate('VBTHU'),
						JText::translate('VBFRI'),
						JText::translate('VBSAT')
				);
				$days_indexes = array();
				for ($i = 0; $i < 7; $i++) {
					$days_indexes[$i] = (6-($firstwday-$i)+1)%7;
				}

				for ($jj = 1; $jj <= $vmode; $jj++) {
					$d_count = 0;
					echo '<div class="vbo-calendar-cal-container">';
					$cal = "";
					?>
					<table class="vbadmincaltable">
						<tr class="vbadmincaltrmon">
							<td colspan="7" align="center"><?php echo VikBooking::sayMonth($newarr['mon'])." ".$newarr['year']; ?></td>
						</tr>
						<tr class="vbadmincaltrmdays">
						<?php
						for ($i = 0; $i < 7; $i++) {
							$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
							echo '<td>'.$days_labels[$d_ind].'</td>';
						}
						?>
						</tr>
						<tr>
					<?php
					for ($i=0, $n = $days_indexes[$newarr['wday']]; $i < $n; $i++, $d_count++) {
						$cal .= "<td align=\"center\">&nbsp;</td>";
					}
					while ($newarr['mon'] == $mon) {
						if ($d_count > 6) {
							$d_count = 0;
							$cal .= "</tr>\n<tr>";
						}
						$dclass = "free";
						$dalt = "";
						$bid = "";
						$totfound = 0;
						if ($check) {
							foreach ($busy as $b) {
								$tmpone = getdate($b['checkin']);
								$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
								$tmptwo = getdate($b['checkout']);
								$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
								if ($newarr[0] >= $ritts && $newarr[0] < $conts) {
									$dclass = "busy";
									$bid = $b['idorder'];
									if ((int)$b['closure']) {
										$dclass .= " busy-closure";
										$dalt = JText::translate('VBDBTEXTROOMCLOSED');
									} elseif ($newarr[0] == $ritts) {
										$dalt = JText::translate('VBPICKUPAT')." ".date('H:i', $b['checkin']);
									} elseif ($newarr[0] == $conts) {
										$dalt = JText::translate('VBRELEASEAT')." ".date('H:i', $b['checkout']);
									}
									$totfound++;
								}
							}
						}
						$useday = ($newarr['mday'] < 10 ? "0".$newarr['mday'] : $newarr['mday']);
						if ($totfound > 0 && $totfound < $room['units']) {
							$dclass .= " vbo-partially";
						}
						if ($totfound == 1) {
							$dlnk = "<a href=\"index.php?option=com_vikbooking&task=editbusy&cid[]=".$bid."\"".($ptmpl == 'component' ? ' target="_blank"' : '').">".$useday."</a>";
							$cal .= "<td align=\"center\" data-daydate=\"".date($df, $newarr[0])."\" class=\"".$dclass."\"".(!empty($dalt) ? " title=\"".$dalt."\"" : "").">".$dlnk."</td>\n";
						} elseif ($totfound > 1) {
							$dlnk = "<a href=\"index.php?option=com_vikbooking&task=choosebusy&idroom=".$room['id']."&ts=".$newarr[0]."\"".($ptmpl == 'component' ? ' target="_blank"' : '').">".$useday."</a>";
							$cal .= "<td align=\"center\" data-daydate=\"".date($df, $newarr[0])."\" class=\"".$dclass."\">".$dlnk."</td>\n";
						} else {
							$dlnk = $useday;
							$cal .= "<td align=\"center\" data-daydate=\"".date($df, $newarr[0])."\" class=\"".$dclass."\">".$dlnk."</td>\n";
						}
						$next = $newarr['mday'] + 1;
						$dayts = mktime(0, 0, 0, $newarr['mon'], $next, $newarr['year']);
						$newarr = getdate($dayts);
						$d_count++;
					}
					
					for ($i = $d_count; $i <= 6; $i++) {
						$cal .= "<td align=\"center\">&nbsp;</td>";
					}
					
					echo $cal;
					?>
						</tr>
					</table>
					<?php
					echo "</div>";
					if ($mon == 12) {
						$mon = 1;
						$year += 1;
						$dayts = mktime(0, 0, 0, $mon, 01, $year);
					} else {
						$mon += 1;
						$dayts = mktime(0, 0, 0, $mon, 01, $year);
					}
					$newarr = getdate($dayts);
				}
				?>
			</div>
		</div>
	</div>

</div>

<div class="vbo-calendar-modal-helper" style="display: none;">
	<div class="vbo-calendar-cfields-filler">
		<div class="vbo-calendar-cfields-topcont">
			<div class="vbo-calendar-cfields-search">
				<label for="vbo-searchcust"><?php echo JText::translate('VBOSEARCHEXISTCUST'); ?></label>
				<span id="vbo-searchcust-loading">
					<i class="vboicn-hour-glass"></i>
				</span>
				<input type="text" id="vbo-searchcust" autocomplete="off" value="" placeholder="<?php echo htmlspecialchars(JText::translate('VBOSEARCHCUSTBY')); ?>" size="35" />
				<div id="vbo-searchcust-res"></div>
			</div>
		</div>
		<div class="vbo-calendar-cfields-inner">
	<?php
	foreach ($all_cfields as $cfield) {
		if ($cfield['type'] == 'text' && $cfield['isphone'] == 1) {
			?>
			<div class="vbo-calendar-cfield-entry">
				<label for="cfield<?php echo $cfield['id']; ?>" data-fieldid="<?php echo $cfield['id']; ?>"><?php echo JText::translate($cfield['name']); ?></label>
				<span>
					<?php
					echo $vbo_app->printPhoneInputField([
						'id' => 'cfield' . $cfield['id'],
						'class' => 'vbo-calendar-cfield-phone',
						'data-isemail' => '0',
						'data-isnominative' => '0',
						'data-isphone' => '1'
					], [
						'fullNumberOnBlur' => true
					]);
					?>
				</span>
			</div>
			<?php
		} elseif ($cfield['type'] == 'text') {
			?>
			<div class="vbo-calendar-cfield-entry">
				<label for="cfield<?php echo $cfield['id']; ?>" data-fieldid="<?php echo $cfield['id']; ?>"><?php echo JText::translate($cfield['name']); ?></label>
				<span>
					<input type="text" id="cfield<?php echo $cfield['id']; ?>" data-isemail="<?php echo ($cfield['isemail'] == 1 ? '1' : '0'); ?>" data-isnominative="<?php echo ($cfield['isnominative'] == 1 ? '1' : '0'); ?>" data-isphone="0" value="" size="35"/>
				</span>
			</div>
			<?php
		} elseif ($cfield['type'] == 'textarea') {
			?>
			<div class="vbo-calendar-cfield-entry">
				<label for="cfield<?php echo $cfield['id']; ?>" data-fieldid="<?php echo $cfield['id']; ?>"><?php echo JText::translate($cfield['name']); ?></label>
				<span>
					<textarea id="cfield<?php echo $cfield['id']; ?>" rows="4" cols="35"></textarea>
				</span>
			</div>
			<?php
		} elseif ($cfield['type'] == 'country') {
			?>
			<div class="vbo-calendar-cfield-entry">
				<label for="cfield<?php echo $cfield['id']; ?>" data-fieldid="<?php echo $cfield['id']; ?>"><?php echo JText::translate($cfield['name']); ?></label>
				<span>
					<select id="cfield<?php echo $cfield['id']; ?>" class="vbo-calendar-cfield-country">
						<option value=""> </option>
					<?php
					foreach ($all_countries as $country) {
						?>
						<option value="<?php echo $country['country_name']; ?>" data-ccode="<?php echo $country['country_3_code']; ?>" data-c2code="<?php echo $country['country_2_code']; ?>"><?php echo $country['country_name']; ?></option>
						<?php
					}
					?>
					</select>
				</span>
			</div>
			<?php
		} elseif ($cfield['type'] == 'state') {
			?>
			<div class="vbo-calendar-cfield-entry">
				<label for="cfield<?php echo $cfield['id']; ?>" data-fieldid="<?php echo $cfield['id']; ?>"><?php echo JText::translate($cfield['name']); ?></label>
				<span>
					<select id="cfield<?php echo $cfield['id']; ?>" class="vbo-calendar-cfield-state">
						<option value="">-----</option>
					</select>
				</span>
			</div>
			<?php
		}
	}
	?>
		</div>
	</div>
</div>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_vikbooking" />
</form>

<script type="text/javascript">
<?php echo ($poverview_change > 0 ? 'window.parent.hasNewBooking = true;' . "\n" : ''); ?>
var vbo_glob_sel_nights = 0;
var customers_search_vals = "";
var prev_tareat = null;
var booknowmade = false;
var vbo_quickres_has_custdetails = false;

function vbCloseRoom() {
	var ckbox = jQuery('input[name="setclosed"]');
	if (ckbox.length && ckbox.prop('checked')) {
		jQuery('#vbo-close-all-rooms-sel').show();
		jQuery('#vbo-row-people').hide();
		if (jQuery('#vbo-row-numrooms').length) {
			jQuery('#vbo-row-numrooms').hide();
		}
		jQuery('#vbo-row-bstat').hide();
		jQuery('#vbo-row-custcost').hide();
		jQuery('#vbo-row-cmail').hide();
		jQuery('#vbo-row-fillcustfields').hide();
		jQuery('#vbo-row-bpay').hide();
		jQuery("#vbo-website-rates-row").hide();
		if (prev_tareat === null) {
			// save the previous customer information
			prev_tareat = jQuery('#vbcustdatatxtarea').val();
		}
		jQuery('#vbcustdatatxtarea').val(Joomla.JText._('VBDBTEXTROOMCLOSED'));
		jQuery('#vbcustdatalbl').text(Joomla.JText._('ORDER_NOTES'));
		jQuery("#quickbsubmit").removeClass("btn-success").addClass("btn-danger").find("span").text(Joomla.JText._('VBSUBMCLOSEROOM'));
		// hide force booking and untick checkbox
		jQuery('#vbo-force-bookingdates').hide();
		jQuery('input[name="forcebooking"]').prop('checked', false);
	} else {
		jQuery('#vbo-close-all-rooms-sel').hide();
		jQuery('#vbo-row-people').show();
		if (jQuery('#vbo-row-numrooms').length) {
			jQuery('#vbo-row-numrooms').show();
		}
		jQuery('#vbo-row-bstat').show();
		jQuery('#vbo-row-custcost').show();
		jQuery('#vbo-row-cmail').show();
		jQuery('#vbo-row-fillcustfields').show();
		jQuery('#vbo-row-bpay').show();
		jQuery('#vbcustdatatxtarea').val(prev_tareat + "");
		jQuery('#vbcustdatalbl').text(Joomla.JText._('VBCUSTINFO'));
		jQuery("#quickbsubmit").removeClass("btn-danger").addClass("btn-success").find("span").text(Joomla.JText._('VBMAKERESERV'));
	}
}

function showCustomFields() {
	let modal_body = VBOCore.displayModal({
		suffix: 		'calendar-cfields',
		extra_class: 	'vbo-modal-tall',
		title: 			Joomla.JText._('VBFILLCUSTFIELDS'),
		body_prepend: 	true,
		footer_left: 	'<button type="button" class="btn" onclick="hideCustomFields();">' + Joomla.JText._('VBANNULLA') + '</button>',
		footer_right: 	'<button type="button" class="btn btn-success" onclick="applyCustomFieldsContent();"><i class="icon-edit"></i> ' + Joomla.JText._('VBAPPLY') + '</button>',
		dismiss_event: 	'vbo-calendar-cfields-dismiss',
		onDismiss: 		() => {
			jQuery('.vbo-calendar-cfields-filler').appendTo('.vbo-calendar-modal-helper');
		},
	});

	jQuery('.vbo-calendar-cfields-filler').appendTo(modal_body);

	setTimeout(function() {
		jQuery('#vbo-searchcust').focus();
	}, 500);
}

function hideCustomFields() {
	VBOCore.emitEvent('vbo-calendar-cfields-dismiss');
}

function applyCustomFieldsContent() {
	var cfields_cont = "";
	var cfields_labels = new Array;
	var nominatives = new Array;
	var tot_rows = 1;
	jQuery(".vbo-calendar-cfields-inner .vbo-calendar-cfield-entry").each(function() {
		var cfield_name = jQuery(this).find("label").text();
		var cfield_input = jQuery(this).find("span").find("input");
		var cfield_textarea = jQuery(this).find("span").find("textarea");
		var cfield_select = jQuery(this).find("span").find("select.vbo-calendar-cfield-country");
		var cfield_state = jQuery(this).find("span").find("select.vbo-calendar-cfield-state");
		var cfield_cont = "";
		if (cfield_input.length) {
			cfield_cont = cfield_input.val();
			if (cfield_input.attr("data-isemail") == "1" && cfield_cont && cfield_cont.length) {
				jQuery("#custmailfield").val(cfield_cont);
			}
			if (cfield_input.attr("data-isphone") == "1") {
				jQuery("#phonefield").val(cfield_cont);
			}
			if (cfield_input.attr("data-isnominative") == "1") {
				nominatives.push(cfield_cont);
			}
		} else if (cfield_textarea.length) {
			cfield_cont = cfield_textarea.val();
		} else if (cfield_select.length) {
			cfield_cont = cfield_select.val();
			if (cfield_cont && cfield_cont.length) {
				var country_code = jQuery("option:selected", cfield_select).attr("data-ccode");
				if (country_code.length) {
					jQuery("#ccode_inpfield").val(country_code);
				}
			}
		} else if (cfield_state.length) {
			cfield_cont = cfield_state.val();
			jQuery("#state_inpfield").val(cfield_cont);
		}
		if (cfield_cont && cfield_cont.length) {
			cfields_cont += cfield_name + ": " + cfield_cont + "\r\n";
			tot_rows++;
			cfields_labels.push(cfield_name + ":");
		}
	});
	if (cfields_cont.length) {
		cfields_cont = cfields_cont.replace(/\r\n+$/, "");
	}
	if (nominatives.length > 1) {
		jQuery("#t_first_name_inpfield").val(nominatives[0]);
		jQuery("#t_last_name_inpfield").val(nominatives[1]);
	}
	jQuery("#vbcustdatatxtarea").val(cfields_cont);
	jQuery("#vbcustdatatxtarea").attr("rows", tot_rows);
	// highlight custom fields labels
	if (jQuery('#vbo-highlight-cfields').length) {
		jQuery("#vbcustdatatxtarea").highlightTextarea('destroy');
	}
	jQuery("#vbcustdatatxtarea").highlightTextarea({
		words: cfields_labels,
		color: "#ddd",
		id: "vbo-highlight-cfields"
	});
	// end highlight
	hideCustomFields();
	// toggle var to detect some values were entered
	vbo_quickres_has_custdetails = true;
}

function vbCalcNights() {
	vbo_glob_sel_nights = 0;
	var vbcheckin = document.getElementById("checkindate").value;
	var vbcheckout = document.getElementById("checkoutdate").value;
	if (vbcheckin.length > 0 && vbcheckout.length > 0) {
		var vbcheckinp = vbcheckin.split("/");
		var vbcheckoutp = vbcheckout.split("/");
		var vbo_df = "<?php echo $vbo_df; ?>";
		if (vbo_df == "%d/%m/%Y") {
			var vbinmonth = parseInt(vbcheckinp[1]);
			vbinmonth = vbinmonth - 1;
			var vbinday = parseInt(vbcheckinp[0], 10);
			var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
			var vboutmonth = parseInt(vbcheckoutp[1]);
			vboutmonth = vboutmonth - 1;
			var vboutday = parseInt(vbcheckoutp[0], 10);
			var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
		}else if (vbo_df == "%m/%d/%Y") {
			var vbinmonth = parseInt(vbcheckinp[0]);
			vbinmonth = vbinmonth - 1;
			var vbinday = parseInt(vbcheckinp[1], 10);
			var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
			var vboutmonth = parseInt(vbcheckoutp[0]);
			vboutmonth = vboutmonth - 1;
			var vboutday = parseInt(vbcheckoutp[1], 10);
			var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
		} else {
			var vbinmonth = parseInt(vbcheckinp[1]);
			vbinmonth = vbinmonth - 1;
			var vbinday = parseInt(vbcheckinp[2], 10);
			var vbcheckind = new Date(vbcheckinp[0], vbinmonth, vbinday);
			var vboutmonth = parseInt(vbcheckoutp[1]);
			vboutmonth = vboutmonth - 1;
			var vboutday = parseInt(vbcheckoutp[2], 10);
			var vbcheckoutd = new Date(vbcheckoutp[0], vboutmonth, vboutday);
		}
		var vbdivider = 1000 * 60 * 60 * 24;
		var vbints = vbcheckind.getTime();
		var vboutts = vbcheckoutd.getTime();
		if (vboutts > vbints) {
			//var vbnights = Math.ceil((vboutts - vbints) / (vbdivider));
			var utc1 = Date.UTC(vbcheckind.getFullYear(), vbcheckind.getMonth(), vbcheckind.getDate());
			var utc2 = Date.UTC(vbcheckoutd.getFullYear(), vbcheckoutd.getMonth(), vbcheckoutd.getDate());
			var vbnights = Math.ceil((utc2 - utc1) / vbdivider);
			if (vbnights > 0) {
				vbo_glob_sel_nights = vbnights;
				jQuery('#vbjstotnights').show();
				jQuery("#vbo-numnights").val(vbnights);
				// update average cost per night
				vbCalcDailyCost(document.getElementById("cust_cost").value);
			} else {
				jQuery('#vbjstotnights').hide();
			}
		} else {
			jQuery('#vbjstotnights').hide();
		}
	} else {
		jQuery('#vbjstotnights').hide();
	}
}

function vbCalcDailyCost(cur_val) {
	// trigger calculation of website rates
	vboCalcWebsiteRates();
	//
	if (cur_val.length && !isNaN(cur_val) && vbo_glob_sel_nights > 0) {
		var cur_float = parseFloat(cur_val);
		var selopts = jQuery("#avg-daycost").find("select").find("option");
		// total cost with average cost per night
		var avg_cost = (cur_float / vbo_glob_sel_nights).toFixed(<?php echo (int)$formatparts[0]; ?>);
		var avg_cost_str = "<?php echo $currencysymb; ?> " + avg_cost + "/" + Joomla.JText._('VBDAY') + " = <?php echo $currencysymb; ?> " + cur_float.toFixed(<?php echo (int)$formatparts[0]; ?>);
		selopts.first().text(avg_cost_str);
		// cost multiplied by number of nights
		var final_cost = (cur_float * vbo_glob_sel_nights).toFixed(<?php echo (int)$formatparts[0]; ?>);
		var final_cost_str = "<?php echo $currencysymb; ?> " + cur_float.toFixed(<?php echo (int)$formatparts[0]; ?>) + "/" + Joomla.JText._('VBDAY') + " = <?php echo $currencysymb; ?> " + final_cost;
		selopts.last().text(final_cost_str);
		// show drop down
		jQuery("#avg-daycost").show();
	} else {
		jQuery("#avg-daycost").hide();
	}
}

function vboCalcWebsiteRates() {
	// unset previously selected rates, if any
	vboUnsetWebsiteRate();
	// hide force booking and untick checkbox
	jQuery('#vbo-force-bookingdates').hide();
	jQuery('input[name="forcebooking"]').prop('checked', false);
	//
	var checkinfdate = jQuery("#checkindate").val();
	var adults = jQuery("#vbo-sel-adults").val();
	var children = jQuery("#vbo-sel-children").val();
	var units = jQuery("#vbo-sel-numrooms").val();
	if (!checkinfdate.length || vbo_glob_sel_nights < 1 || jQuery("input[name=\"setclosed\"]").is(":checked") || <?php echo !empty($split_stay) ? 'true' : 'false'; ?>) {
		jQuery("#vbo-website-rates-row").hide();
		return false;
	}
	var jqxhr = jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=calc_rates'); ?>",
		data: {
			id_room: <?php echo $room['id']; ?>,
			checkinfdate: checkinfdate,
			num_nights: vbo_glob_sel_nights,
			num_adults: adults,
			num_children: children,
			units: units,
			only_rates: 1,
			tmpl: "component"
		}
	}).done(function(resp) {
		var obj_res = null;
		try {
			obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
		} catch(err) {
			console.error("could not parse JSON response", resp);
		}
		if (obj_res === null || !Array.isArray(obj_res)) {
			jQuery("#vbo-website-rates-row").hide();
			console.info("invalid JSON response", resp);
			return false;
		}
		if (jQuery("input[name=\"setclosed\"]").is(":checked")) {
			jQuery("#vbo-website-rates-row").hide();
			return false;
		}
		if (obj_res.hasOwnProperty(1) && obj_res[1] == -1 && !jQuery('input[name="setclosed"]').prop('checked')) {
			// the room is not available or has no rates, display force booking toggle
			jQuery('#vbo-force-bookingdates').show();
			if (typeof obj_res[0] == 'string' && obj_res[0].indexOf('e4j.error') >= 0) {
				jQuery('#vbo-force-bookingdates').find('.vbo-param-setting-comment').text(obj_res[0].replace('e4j.error.', ''));
			} else {
				jQuery('#vbo-force-bookingdates').find('.vbo-param-setting-comment').text('');
			}
		}
		if (!obj_res[0].hasOwnProperty("idprice")) {
			jQuery("#vbo-website-rates-row").hide();
			console.log("error in response", resp);
			return false;
		}
		// check whether rates are inclusive of taxes
		var vbo_tax_included = <?php echo $prices_vat_included; ?>;
		// display the rates obtained
		var wrhtml = "";
		for (var i in obj_res) {
			if (!obj_res.hasOwnProperty(i)) {
				continue;
			}
			if (!vbo_tax_included && obj_res[i].hasOwnProperty('net') && obj_res[i].hasOwnProperty('fnet')) {
				obj_res[i]['tot'] = obj_res[i]['net'];
				obj_res[i]['ftot'] = obj_res[i]['fnet'];
			}
			wrhtml += "<div class=\"vbo-cal-wbrate-wrap\" onclick=\"vboSelWebsiteRate(this);\">";
			wrhtml += "<div class=\"vbo-cal-wbrate-inner\">";
			wrhtml += "<span class=\"vbo-cal-wbrate-name\" data-idprice=\"" + obj_res[i]['idprice'] + "\">" + obj_res[i]['name'] + "</span>";
			wrhtml += "<span class=\"vbo-cal-wbrate-cost\" data-cost=\"" + obj_res[i]['tot'] + "\">" + obj_res[i]['ftot'] + "</span>";
			wrhtml += "</div>";
			wrhtml += "</div>";
		}
		jQuery("#vbo-website-rates-cont").html(wrhtml);
		jQuery("#vbo-website-rates-row").fadeIn();
		if (<?php echo $pidprice > 0 && $pbooknow > 0 ? 'true' : 'false'; ?> && !booknowmade) {
			// we get here by clicking the book-now button from the rates calculator only once
			booknowmade = true;
			// trigger the click for the requested rate plan ID
			jQuery('.vbo-cal-wbrate-name[data-idprice="<?php echo $pidprice; ?>"]').closest('.vbo-cal-wbrate-wrap').trigger('click');
		}
	}).fail(function() {
		jQuery("#vbo-website-rates-row").hide();
		console.error("Error calculating the rates");
	});
}

function vboSelWebsiteRate(elem) {
	var rate = jQuery(elem);
	var idprice = rate.find('.vbo-cal-wbrate-name').attr('data-idprice');
	var cost = rate.find('.vbo-cal-wbrate-cost').attr('data-cost');
	var prev_idprice = jQuery('#booking-idprice').val();
	// reset all selected classes
	jQuery('.vbo-cal-wbrate-wrap').removeClass('vbo-cal-wbrate-wrap-selected');
	if (prev_idprice.length && prev_idprice == idprice) {
		// rate plan has been de-selected
		jQuery('#booking-idprice').val("");
		jQuery('#booking-roomcost').val("");
		jQuery('#cust_cost').attr('readonly', false);
	} else {
		// rate plan has been selected
		rate.addClass('vbo-cal-wbrate-wrap-selected');
		jQuery('#booking-idprice').val(idprice);
		jQuery('#booking-roomcost').val(cost);
		jQuery('#cust_cost').attr('readonly', true);
	}
}

function vboUnsetWebsiteRate() {
	jQuery('#booking-idprice').val("");
	jQuery('#booking-roomcost').val("");
	jQuery('.vbo-cal-wbrate-wrap').removeClass('vbo-cal-wbrate-wrap-selected');
	jQuery('#cust_cost').attr('readonly', false);
}

function vboReloadStates(country_3_code) {
	var states_elem = jQuery('select.vbo-calendar-cfield-state');

	// unset the current states/provinces
	states_elem.html('');

	if (!country_3_code || !country_3_code.length) {
		return;
	}

	// make a request to load the states/provinces of the selected country
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=states.load_from_country'); ?>",
		{
			country_3_code: country_3_code,
			tmpl: "component"
		},
		(response) => {
			try {
				var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
				if (!obj_res) {
					console.error('Unexpected JSON response', obj_res);
					return false;
				}

				// append empty value
				states_elem.append('<option value="">-----</option>');

				for (var i = 0; i < obj_res.length; i++) {
					// append state
					states_elem.append('<option value="' + obj_res[i]['state_2_code'] + '">' + obj_res[i]['state_name'] + '</option>');
				}
			} catch(err) {
				console.error('could not parse JSON response', err, response);
			}
		},
		(error) => {
			console.error(error);
		}
	);
}

jQuery(function() {

	jQuery('.vbo-calendar-cfield-country').on('change', function() {
		// trigger event for phone number
		jQuery('.vbo-calendar-cfield-phone').trigger('vboupdatephonenumber', jQuery(this).find('option:selected').attr('data-c2code'));
		// reload state/province
		vboReloadStates(jQuery(this).find('option:selected').attr('data-ccode'));
	});

	jQuery('td.free').click(function() {
		var indate = jQuery('#checkindate').val();
		var outdate = jQuery('#checkoutdate').val();
		var clickdate = jQuery(this).attr('data-daydate');
		if (!(indate.length > 0)) {
			jQuery('#checkindate').datepicker("setDate", clickdate);
		} else if (!(outdate.length > 0) && clickdate != indate) {
			jQuery('#checkoutdate').datepicker("setDate", clickdate);
		} else {
			jQuery('#checkoutdate').datepicker("setDate", '');
			jQuery('#checkindate').datepicker("setDate", clickdate);
		}
		jQuery(".ui-datepicker-current-day").click();
	});

	jQuery("#quickbsubmit").click(function() {
		setTimeout(function() {
			jQuery(this).prop('disabled', true);
		}, 200);
	});

	jQuery('#vbo-calendar-newb-form').on('submit', function() {
		if (!document.newb.checkindate.value.match(/\S/)) {
			alert(Joomla.JText._('VBMSGTHREE'));
			jQuery('#quickbsubmit').prop('disabled', false);
			return false;
		}

		if (!document.newb.checkoutdate.value.match(/\S/)) {
			alert(Joomla.JText._('VBMSGFOUR'));
			jQuery('#quickbsubmit').prop('disabled', false);
			return false;
		}

		jQuery('#quickbsubmit').prop('disabled', true);

		if (!jQuery(this).find('[name="setclosed"]').prop('checked') && !jQuery(this).find('[name="custmail"]').val() && !vbo_quickres_has_custdetails && <?php echo $room['units'] > 1 ? 'true' : 'false'; ?>) {
			// no closure, no customer email, multiple units, ask if the units should be marked as closed
			if (confirm(Joomla.JText._('VBO_MARK_UNITS_CLOSED'))) {
				// append hidden field to form
				jQuery(this).append('<input type="hidden" name="setunitsclosed" value="1" />');
				// check if "room closed" should be added
				if (!jQuery('#vbcustdatatxtarea').val()) {
					jQuery('#vbcustdatatxtarea').val(Joomla.JText._('VBDBTEXTROOMCLOSED'));
				}
			}
		}

		return true;
	});

	jQuery("#vbo-calendar-changeroom").select2();
	if (jQuery("#vbo-calendar-closeall").length) {
		jQuery("#vbo-calendar-closeall").select2({placeholder: "- " + Joomla.JText._('VBOCALCLOSEOTHERROOMS') + " -", width: "300px"});
	}

	jQuery("#vbo-sel-numrooms, #vbo-sel-adults").change(function() {
		vboCalcWebsiteRates();
	});

	jQuery('.vbo-calendar-cfield-entry input[data-isemail="1"]').first().blur(function() {
		var curemail = jQuery(this).val();
		if (curemail.length) {
			var jqxhr = jQuery.ajax({
				type: "POST",
				url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=searchcustomer'); ?>",
				data: {
					kw: curemail,
					email: 1,
					tmpl: "component"
				}
			}).done(function(cont) {
				try {
					var obj_res = typeof cont === 'string' ? JSON.parse(cont) : cont;
					alert(Joomla.JText._('VBERRCUSTOMEREMAILEXISTS') + " (" + obj_res['first_name'] + " " + obj_res['last_name'] + ")");
				} catch(e) {
					console.error(e);
				}
			}).fail(function() {
				console.log("Error searching for existing email");
			});
		}
	});

	jQuery("#vbfillcustfields").click(function() {
		showCustomFields();
	});

	// search customer - start
	var vbocustsdelay = (function() {
		var timer = 0;
		return function(callback, ms) {
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();

	function vboCustomerSearch(words) {
		jQuery("#vbo-searchcust-res").hide().html("");
		jQuery("#vbo-searchcust-loading").show();
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=searchcustomer'); ?>",
			data: {
				kw: words,
				tmpl: "component"
			}
		}).done(function(cont) {
			if (cont) {
				var obj_res = typeof cont === 'string' ? JSON.parse(cont) : cont;
				customers_search_vals = obj_res[0];
				jQuery("#vbo-searchcust-res").html(obj_res[1]);
			} else {
				customers_search_vals = "";
				jQuery("#vbo-searchcust-res").html("----");
			}
			jQuery("#vbo-searchcust-res").show();
			jQuery("#vbo-searchcust-loading").hide();
		}).fail(function() {
			jQuery("#vbo-searchcust-loading").hide();
			alert("Error searching");
		});
	}

	jQuery("#vbo-searchcust").keyup(function(event) {
		vbocustsdelay(function() {
			var keywords = jQuery("#vbo-searchcust").val();
			var chars = keywords.length;
			if (chars > 1) {
				if ((event.which > 96 && event.which < 123) || (event.which > 64 && event.which < 91) || event.which == 13) {
					vboCustomerSearch(keywords);
				}
			} else {
				if (jQuery("#vbo-searchcust-res").is(":visible")) {
					jQuery("#vbo-searchcust-res").hide();
				}
			}
		}, 600);
	});
	// search customer - end

	// datepickers - start
	jQuery("#checkindate").datepicker({
		showOn: "focus",
		dateFormat: "<?php echo $juidf; ?>",
		numberOfMonths: 1,
		onSelect: function(selectedDate) {
			var nownights = parseInt(jQuery("#vbo-numnights").val());
			var nowcheckin = jQuery("#checkindate").datepicker("getDate");
			var nowcheckindate = new Date(nowcheckin.getTime());
			nowcheckindate.setDate(nowcheckindate.getDate() + nownights);
			jQuery("#checkoutdate").datepicker("option", "minDate", nowcheckindate);
			jQuery("#checkoutdate").datepicker("setDate", nowcheckindate);
			vbCalcNights();
		}
	});
	jQuery("#checkoutdate").datepicker({
		showOn: "focus",
		dateFormat: "<?php echo $juidf; ?>",
		numberOfMonths: 1,
		onSelect: function(selectedDate) {
			vbCalcNights();
		}
	});
	jQuery(".vbodatepicker-trig-icon").click(function(){
		var jdp = jQuery(this).prev("input.hasDatepicker");
		if (jdp.length) {
			jdp.focus();
		}
	});
	jQuery("#vbo-numnights").on("change keyup", function() {
		if (!jQuery("#checkindate").length) {
			return;
		}
		var nownights = parseInt(jQuery(this).val());
		var nowcheckin = jQuery("#checkindate").datepicker("getDate");
		var nowcheckindate = new Date(nowcheckin.getTime());
		nowcheckindate.setDate(nowcheckindate.getDate() + nownights);
		jQuery("#checkoutdate").datepicker("option", "minDate", nowcheckindate);
		jQuery("#checkoutdate").datepicker("setDate", nowcheckindate);
		vbo_glob_sel_nights = nownights;
		// update average cost per night
		vbCalcDailyCost(document.getElementById("cust_cost").value);
	});
	// datepickers - end
	<?php echo (!empty($pcheckin) ? 'jQuery("#checkindate").datepicker("setDate", "'.$pcheckin.'");'."\n" : ''); ?>
	<?php echo (!empty($pcheckout) ? 'jQuery("#checkoutdate").datepicker("setDate", "'.$pcheckout.'");'."\n" : ''); ?>
	<?php echo (!empty($pcheckin) || !empty($pcheckout) ? 'jQuery(".ui-datepicker-current-day").click();'."\n" : ''); ?>
});

jQuery(document).on("click", ".vbo-custsearchres-entry", function() {
	var custid = jQuery(this).attr("data-custid");
	var custemail = jQuery(this).attr("data-email");
	var custphone = jQuery(this).attr("data-phone");
	var custcountry = jQuery(this).attr("data-country");
	var custfirstname = jQuery(this).attr("data-firstname");
	var custlastname = jQuery(this).attr("data-lastname");
	jQuery("#customer_id_inpfield").val(custid);
	if (customers_search_vals.hasOwnProperty(custid)) {
		jQuery.each(customers_search_vals[custid], function(cfid, cfval) {
			var fill_field = jQuery("#cfield"+cfid);
			if (fill_field.length) {
				fill_field.val(cfval);
			}
		});
	} else {
		jQuery("input[data-isnominative=\"1\"]").each(function(k, v) {
			if (k == 0) {
				jQuery(this).val(custfirstname);
				return true;
			}
			if (k == 1) {
				jQuery(this).val(custlastname);
				return true;
			}
			return false;
		});
		jQuery("input[data-isemail=\"1\"]").val(custemail);
		jQuery("input[data-isphone=\"1\"]").val(custphone).trigger('vboupdatephonenumber');
		// populate main calendar form
		jQuery("#custmailfield").val(custemail);
		jQuery("#t_first_name_inpfield").val(custfirstname);
		jQuery("#t_last_name_inpfield").val(custlastname);
		//
	}
	applyCustomFieldsContent();
	if (custcountry.length) {
		jQuery("#ccode_inpfield").val(custcountry);
	}
	if (custphone.length) {
		jQuery("#phonefield").val(custphone);
	}
});

var vborefreshingsel = false;
function vboCheckCloseOthers() {
	if (!jQuery("#vbo-calendar-closeall").length || vborefreshingsel) {
		// avoid recursion loop by triggering the change event for select2
		return;
	}
	var selall = jQuery('#vbo-calendar-closeall option[value="-1"]').is(':selected');
	// always select the current room unless all get de-selected
	var optselected = jQuery('#vbo-calendar-closeall option:selected');
	var none_sel = !optselected.length ? true : false;
	if (optselected.length === 1 && optselected.first().attr('data-currentr')) {
		none_sel = true;
	}
	var curropt = jQuery('#vbo-calendar-closeall option[data-currentr="true"]');
	if (!none_sel) {
		curropt.prop('selected', true);
		curropt.prop('disabled', true);
	} else {
		curropt.prop('selected', false);
		curropt.prop('disabled', true);
	}
	//
	jQuery('#vbo-calendar-closeall').find('option').each(function() {
		var curval = jQuery(this).val();
		var curroom = jQuery(this).attr('data-currentr');
		if (!curval.length || curval == '-1' || curroom) {
			return true;
		}
		if (selall) {
			jQuery(this).prop('disabled', true);
			jQuery(this).prop('selected', false);
		} else {
			jQuery(this).prop('disabled', false);
		}
	});
	vboRefreshCloseOthers();
}

function vboRefreshCloseOthers() {
	// prevent recursion loop
	vborefreshingsel = true;
	jQuery("#vbo-calendar-closeall").trigger('change');
	setTimeout(function() {
		vborefreshingsel = false;
	}, 200);
}
</script>
