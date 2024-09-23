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

$package = $this->package;
$vbo_tn = $this->vbo_tn;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadDatePicker();

$is_mobile = VikBooking::detectUserAgent(false, false);
$currencysymb = VikBooking::getCurrencySymb();
$calendartype = VikBooking::calendarType();
$vbdateformat = VikBooking::getDateFormat();
$juidf = '';
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
	$juidf = 'dd/mm/yy';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
	$juidf = 'mm/dd/yy';
} else {
	$df = 'Y/m/d';
	$juidf = 'yy/mm/dd';
}
$datesep = VikBooking::getDateSeparator();
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

$pitemid = VikRequest::getInt('Itemid', '', 'request');
$start_info = getdate($package['dfrom']);
$end_info = getdate($package['dto']);
$exclude_dates = array();
if (!empty($package['excldates'])) {
	$excl_parts = explode(';', $package['excldates']);
	foreach ($excl_parts as $excl) {
		if (!empty($excl)) {
			$exclude_dates[] = "'".$excl."'";
		}
	}
}

$document = JFactory::getDocument();
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery.fancybox.js');

// datepicker helper
$vbo_js = "
var pkg_not_dates = [".(count($exclude_dates) > 0 ? implode(', ', $exclude_dates) : '')."];
function vbIsDayDisabled(date) {
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	if (pkg_not_dates.indexOf((m+1) + '-' + d + '-' + y) != -1) {
		return [false];
	}
	return [true];
}";
$document->addScriptDeclaration($vbo_js);

$costfor = array();
if ($package['perperson'] == 1) {
	$costfor[] = JText::translate('VBOPKGCOSTPERPERSON');
}
if ($package['pernight_total'] == 1) {
	$costfor[] = JText::translate('VBOPKGCOSTPERNIGHT');
}

$thumbs_rel = array();

?>
<h3 class="vbo-pkgdet-title"><?php echo $package['name']; ?></h3>
<div class="vbo-pkgdet-container">
	<div class="vbo-pkgdet-topwrap">
<?php
if (!empty($package['img'])) {
	?>
		<div class="vbo-pkgdet-img">
			<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/big_<?php echo $package['img']; ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" />
		</div>
	<?php
}
/**
 * @wponly 	we try to parse any shortcode inside the texts of the package
 */
$package['descr'] = do_shortcode($package['descr']);
$package['conditions'] = do_shortcode($package['conditions']);
//
?>
		<div class="vbo-pkgdet-descrprice-block">
			<div class="vbo-pkgdet-descr">
				<?php echo $package['descr'] ?>
			</div>
			<div class="vbo-pkgdet-cost">
				<span class="vbo-pkglist-pkg-price"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo VikBooking::numberFormat($package['cost']); ?></span></span>
				<span class="vbo-pkglist-pkg-priceper"><?php echo implode(', ', $costfor); ?></span>
			</div>
		</div>
		<div class="vbo-pkgdet-condsdates-block">
			<div class="vbo-pkgdet-conds">
				<?php echo $package['conditions'] ?>
			</div>
			<div class="vbo-pkgdet-dates">
				<?php VikBookingIcons::e('clock-o'); ?>
				<span class="vbo-pkgdet-dates-lbl"><?php echo JText::translate('VBOPKGVALIDATES'); ?></span>
				<span class="vbo-pkgdet-dates-ft"><?php echo date(str_replace("/", $datesep, $df), $package['dfrom']).($package['dfrom'] != $package['dto'] ? ' - '.date(str_replace("/", $datesep, $df), $package['dto']) : ''); ?></span>
			</div>
		</div>
<?php
if (!empty($package['benefits'])) {
	?>
		<div class="vbo-pkgdet-benefits">
			<?php echo $package['benefits']; ?>
		</div>
	<?php
}
?>
	</div>

	<div class="vbo-pkgdet-roomswrap">
		<h3 class="vbo-pkgdet-roomsttl"><?php echo JText::translate('VBOPKGBOOKNOWROOMS'); ?></h3>
<?php
if (array_key_exists('rooms', $package) && count($package['rooms'])) {
	?>
		<div class="vbo-pkgdet-roomslist">
	<?php
	foreach ($package['rooms'] as $rk => $room) {
		if (empty($room['name'])) {
			continue;
		}
		?>
			<div class="vbo-pkgdet-room-container">
				<div class="vbo-pkgdet-room-outer">
			<?php
			if (!empty($room['img'])) {
				?>
					<div class="vbo-pkgdet-room-img">
						<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room['img']; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" />
					</div>
				<?php
			}
			?>
					<div class="vbo-pkgdet-room-det">
						<h4 class="vbo-pkgdet-roomname"><?php echo $room['name']; ?></h4>
						<div class="vbo-pkgdet-room-shortdescr"><?php echo $room['smalldesc']; ?></div>
					</div>
					<div class="vbo-pkgdet-room-booknow">
						<button type="button" class="btn vbo-pkgdet-room-booknow-btn vbo-pref-color-btn" data-room="<?php echo $room['idroom']; ?>" onclick="vboToggleRoomBooking('<?php echo $room['idroom']; ?>');"><?php echo JText::translate('VBOPKGROOMCHECKAVAIL'); ?></button>
					</div>
				</div>
				<div class="vbo-pkgdet-room-inner" id="vbo-pkgdet-room<?php echo $room['idroom']; ?>-inner" style="display: none;">
			<?php
			if (!empty($room['moreimgs'])) {
				$moreimages = explode(';;', $room['moreimgs']);
				$imgcaptions = json_decode($room['imgcaptions'], true);
				$usecaptions = empty($imgcaptions) || is_null($imgcaptions) || !is_array($imgcaptions) || !(count($imgcaptions) > 0) ? false : true;
				$thumbs_ind = 0;
				$extra_photos = array();
				foreach($moreimages as $iind => $mimg) {
					if (!empty($mimg)) {
						$img_alt = $usecaptions === true && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : '';
						$extra_photos[$thumbs_ind] = array('big' => VBO_SITE_URI.'resources/uploads/big_'.$mimg, 'thumb' => VBO_SITE_URI.'resources/uploads/thumb_'.$mimg, 'alt' => $img_alt);
						$thumbs_ind++;
					}
				}
				if (count($extra_photos)) {
					$thumbs_rel[] = $room['idroom'];
					?>
					<div class="vbo-pkgdet-room-thumbs-cont">
					<?php
					foreach ($extra_photos as $extra_photo) {
						?>
						<div class="vbo-pkgdet-room-thumb">
							<a href="<?php echo $extra_photo['big']; ?>" title="<?php echo htmlspecialchars($extra_photo['alt']); ?>" rel="room<?php echo $room['idroom']; ?>" target="_blank"><img src="<?php echo $extra_photo['thumb']; ?>" alt="<?php echo htmlspecialchars($extra_photo['alt']); ?>" /></a>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}
			}
			?>
					<div class="vbo-seldates-cont vbo-pkgdet-room-form">
						<h4><?php echo JText::translate('VBSELECTPDDATES'); ?></h4>
					<?php
					$paramshowpeople = intval(VikBooking::getRoomParam('maxminpeople', $room['params']));
					if ($paramshowpeople > 0) {
						$maxadustr = ($room['fromadult'] != $room['toadult'] ? $room['fromadult'].' - '.$room['toadult'] : $room['toadult']);
						$maxchistr = ($room['fromchild'] != $room['tochild'] ? $room['fromchild'].' - '.$room['tochild'] : $room['tochild']);
						$maxtotstr = ($room['mintotpeople'] != $room['totpeople'] ? $room['mintotpeople'].' - '.$room['totpeople'] : $room['totpeople']);
						?>
						<div class="vbmaxminpeopleroom">
						<?php
						if ($paramshowpeople == 1) {
							?>
							<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
							<?php
						} elseif ($paramshowpeople == 2) {
							?>
							<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
							<?php
						} elseif ($paramshowpeople == 3) {
							?>
							<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
							<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
							<?php
						} elseif ($paramshowpeople == 4) {
							?>
							<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
							<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
							<?php
						} elseif ($paramshowpeople == 5) {
							?>
							<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
							<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
							<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
							<?php
						}
						?>
						</div>
						<?php
					}
					/* Begin room booking form */
					$selform = "<div class=\"vbdivsearch\"><form action=\"".JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''))."\" method=\"post\"><div class=\"vb-search-inner\">\n";
					$selform .= "<input type=\"hidden\" name=\"option\" value=\"com_vikbooking\"/>\n";
					$selform .= "<input type=\"hidden\" name=\"task\" value=\"search\"/>\n";
					$selform .= "<input type=\"hidden\" name=\"roomdetail\" value=\"".$room['idroom']."\"/>\n";
					$selform .= "<input type=\"hidden\" name=\"pkg_id\" value=\"".$package['id']."\"/>\n";
					
					if ($calendartype == "jqueryui") {
						$orig_start_info = $start_info;
						if ($package['dfrom'] < time()) {
							$start_info = getdate(time());
						}
						$sdecl = "
jQuery.noConflict();
jQuery(function(){
	jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ '' ] );
	jQuery('#checkindate".$room['idroom']."').datepicker({
		showOn: 'focus',
		numberOfMonths: ".($is_mobile ? '1' : '2').",
		beforeShowDay: vbIsDayDisabled,
		onSelect: function( selectedDate ) {
			vbSetGlobalMinCheckoutDate('".$room['idroom']."');
			vbCalcNights('".$room['idroom']."');
		}
	});
	jQuery('#checkindate".$room['idroom']."').datepicker( 'option', 'dateFormat', '".$juidf."');
	jQuery('#checkindate".$room['idroom']."').datepicker( 'option', 'minDate', new Date(".$start_info['year'].", ".((int)$start_info['mon'] - 1).", ".$start_info['mday'].") );
	jQuery('#checkindate".$room['idroom']."').datepicker( 'option', 'maxDate', new Date(".$end_info['year'].", ".((int)$end_info['mon'] - 1).", ".$end_info['mday'].") );
	jQuery('#checkoutdate".$room['idroom']."').datepicker({
		showOn: 'focus',
		numberOfMonths: ".($is_mobile ? '1' : '2').",
		beforeShowDay: vbIsDayDisabled,
		onSelect: function( selectedDate ) {
			vbCalcNights('".$room['idroom']."');
		}
	});
	jQuery('#checkoutdate".$room['idroom']."').datepicker( 'option', 'dateFormat', '".$juidf."');
	jQuery('#checkoutdate".$room['idroom']."').datepicker( 'option', 'minDate', new Date(".$start_info['year'].", ".((int)$start_info['mon'] - 1).", ".$start_info['mday'].") );
	jQuery('#checkoutdate".$room['idroom']."').datepicker( 'option', 'maxDate', new Date(".$end_info['year'].", ".((int)$end_info['mon'] - 1).", ".$end_info['mday'].") );
	jQuery('#checkindate".$room['idroom']."').datepicker( 'option', jQuery.datepicker.regional[ 'vikbooking' ] );
	jQuery('#checkoutdate".$room['idroom']."').datepicker( 'option', jQuery.datepicker.regional[ 'vikbooking' ] );
});";
						$document->addScriptDeclaration($sdecl);
						$start_info = $orig_start_info;
						$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkin\"><label for=\"checkindate".$room['idroom']."\">" . JText::translate('VBPICKUPROOM') . "</label><div class=\"input-group\"><input type=\"text\" name=\"checkindate\" id=\"checkindate".$room['idroom']."\" size=\"10\" autocomplete=\"off\" onfocus=\"this.blur();\" readonly/><i class=\"".VikBookingIcons::i('calendar', 'vbo-caltrigger')."\"></i></div><input type=\"hidden\" name=\"checkinh\" value=\"".$hcheckin."\"/><input type=\"hidden\" name=\"checkinm\" value=\"".$mcheckin."\"/></div>\n";
						$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkout\"><label for=\"checkoutdate".$room['idroom']."\">" . JText::translate('VBRETURNROOM') . "</label><div class=\"input-group\"><input type=\"text\" name=\"checkoutdate\" id=\"checkoutdate".$room['idroom']."\" size=\"10\" autocomplete=\"off\" onfocus=\"this.blur();\" readonly/><i class=\"".VikBookingIcons::i('calendar', 'vbo-caltrigger')."\"></i></div><input type=\"hidden\" name=\"checkouth\" value=\"".$hcheckout."\"/><input type=\"hidden\" name=\"checkoutm\" value=\"".$mcheckout."\"/></div>\n";
					} else {
						//default Joomla Calendar
						$vbo_app = VikBooking::getVboApplication();
						$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkin\"><label for=\"checkindate".$room['idroom']."\">" . JText::translate('VBPICKUPROOM') . "</label><div class=\"input-group\">" . $vbo_app->getCalendar('', 'checkindate', 'checkindate'.$room['idroom'], $vbdateformat, array ('class' => '','size' => '10','maxlength' => '19'));
						$selform .= "<input type=\"hidden\" name=\"checkinh\" value=\"".$hcheckin."\"/><input type=\"hidden\" name=\"checkinm\" value=\"".$mcheckin."\"/></div></div>\n";
						$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkout\"><label for=\"checkoutdate".$room['idroom']."\">" . JText::translate('VBRETURNROOM') . "</label><div class=\"input-group\">" . $vbo_app->getCalendar('', 'checkoutdate', 'checkoutdate'.$room['idroom'], $vbdateformat, array ('class' => '','size' => '10','maxlength' => '19')); 
						$selform .= "<input type=\"hidden\" name=\"checkouth\" value=\"".$hcheckout."\"/><input type=\"hidden\" name=\"checkoutm\" value=\"".$mcheckout."\"/></div></div>\n";
					}
					//rooms, adults, children
					$showchildren = VikBooking::showChildrenFront();
					$guests_label = VBOFactory::getConfig()->get('guests_label', 'adults');
					$use_guests_label = 'VBFORMADULTS';
					if (!$showchildren && !strcasecmp($guests_label, 'guests')) {
						$use_guests_label = 'VBOINVTOTGUESTS';
					}
					//max number of rooms
					$multi_units = (int)VikBooking::getRoomParam('multi_units', $room['params']);
					if ($multi_units === 1 && $room['units'] > 1) {
						$maxsearchnumrooms = (int)VikBooking::getSearchNumRooms();
						$maxsearchnumrooms = $room['units'] > $maxsearchnumrooms ? $maxsearchnumrooms : $room['units'];
						$roomsel = "<label>".JText::translate('VBFORMROOMSN')."</label><select name=\"roomsnum\" onchange=\"vbSetRoomsAdults(this.value, '".$room['idroom']."');\">\n";
						for ($r = 1; $r <= $maxsearchnumrooms; $r++) {
							$roomsel .= "<option value=\"".$r."\">".$r."</option>\n";
						}
						$roomsel .= "</select>\n";
					} else {
						$roomsel = "<input type=\"hidden\" name=\"roomsnum\" value=\"1\">\n";
					}
					//
					//max number of adults per room
					$suggocc = (int)VikBooking::getRoomParam('suggocc', $room['params']);
					$adultsel = "<select name=\"adults[]\">";
					for ($a = $room['fromadult']; $a <= $room['toadult']; $a++) {
						$adultsel .= "<option value=\"".$a."\"".($a == $suggocc ? " selected=\"selected\"" : "").">".$a."</option>";
					}
					$adultsel .= "</select>";
					//
					//max number of children per room
					$childrensel = "<select name=\"children[]\">";
					for($c = 0; $c <= $room['tochild']; $c++) {
						$childrensel .= "<option value=\"".$c."\"".(!empty($ch_num_children) && $ch_num_children == $c ? " selected=\"selected\"" : "").">".$c."</option>";
					}
					$childrensel .= "</select>";
					//

					$selform .= "<div class=\"vbo-search-num-racblock\">\n";
					$selform .= "	<div class=\"vbo-search-num-rooms\">".$roomsel."</div>\n";
					$selform .= "	<div class=\"vbo-search-num-aduchild-block\" id=\"vbo-search-num-aduchild-block".$room['idroom']."\">\n";
					$selform .= "		<div class=\"vbo-search-num-aduchild-entry\"><span class=\"vbo-search-roomnum\">".JText::translate('VBFORMNUMROOM')." 1</span>\n";
					$selform .= "			<div class=\"vbo-search-num-adults-entry\"><label class=\"vbo-search-num-adults-entry-label\">".JText::translate($use_guests_label)."</label><span class=\"vbo-search-num-adults-entry-inp\">".$adultsel."</span></div>\n";
					if ($showchildren) {
						$selform .= "		<div class=\"vbo-search-num-children-entry\"><label class=\"vbo-search-num-children-entry-label\">".JText::translate('VBFORMCHILDREN')."</label><span class=\"vbo-search-num-children-entry-inp\">".$childrensel."</span></div>\n";
					}
					$selform .= "		</div>\n";
					$selform .= "	</div>\n";
					//the tag <div id=\"vbjstotnights".$room['idroom']."\"></div> will be used by javascript to calculate the nights
					$selform .= "	<div id=\"vbjstotnights".$room['idroom']."\"></div>\n";
					$selform .= "</div>\n";
					$selform .= "<div class=\"vbo-search-submit\"><input type=\"submit\" name=\"search\" value=\"" . JText::translate('VBBOOKTHISROOM') . "\" class=\"btn vbdetbooksubmit vbo-pref-color-btn\"/></div>\n";
					$selform .= "</div>\n";
					$selform .= (!empty ($pitemid) ? "<input type=\"hidden\" name=\"Itemid\" value=\"" . $pitemid . "\"/>" : "") . "</form></div>";
					?>
						<input type="hidden" id="vbroomdethelper<?php echo $room['idroom']; ?>" value="1"/>
						<div class="vbo-room-details-booking-wrapper">
							<?php echo $selform; ?>
						</div>
					<?php
					/* End room booking form */
					?>
					</div>
				</div>
			</div>
		<?php
	}
	?>		
		</div>
	<?php
}
?>
	</div>

</div>

<script type="text/javascript">
function vboToggleRoomBooking(idr) {
	if (typeof jQuery != 'undefined') {
		var elem = document.getElementById("vbo-pkgdet-room"+idr+"-inner");
		if (elem.style.display == 'none') {
			elem.style.display = 'block';
		} else {
			elem.style.display = 'none';
		}
	} else {
		jQuery("#vbo-pkgdet-room"+idr+"-inner").slideToggle();
	}
}
function vbSetGlobalMinCheckoutDate(rid) {
	var nowcheckin = jQuery('#checkindate'+rid).datepicker('getDate');
	var nowcheckindate = new Date(nowcheckin.getTime());
	nowcheckindate.setDate(nowcheckindate.getDate() + <?php echo (int)$package['minlos']; ?>);
	jQuery('#checkoutdate'+rid).datepicker( 'option', 'minDate', nowcheckindate );
<?php
if ($package['maxlos'] > 0) {
	?>
	var scndcheckin = jQuery('#checkindate'+rid).datepicker('getDate');
	var scndcheckindate = new Date(scndcheckin.getTime());
	scndcheckindate.setDate(scndcheckindate.getDate() + <?php echo (int)$package['maxlos']; ?>);
	jQuery('#checkoutdate'+rid).datepicker( 'option', 'maxDate', scndcheckindate );
	<?php
}
?>
}
function vbCalcNights(rid) {
	var vbcheckin = document.getElementById('checkindate'+rid).value;
	var vbcheckout = document.getElementById('checkoutdate'+rid).value;
	if (vbcheckin.length > 0 && vbcheckout.length > 0) {
		var vbcheckinp = vbcheckin.split("/");
		var vbcheckoutp = vbcheckout.split("/");
	<?php
	if ($vbdateformat == "%d/%m/%Y") {
		?>
		var vbinmonth = parseInt(vbcheckinp[1]);
		vbinmonth = vbinmonth - 1;
		var vbinday = parseInt(vbcheckinp[0], 10);
		var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
		var vboutmonth = parseInt(vbcheckoutp[1]);
		vboutmonth = vboutmonth - 1;
		var vboutday = parseInt(vbcheckoutp[0], 10);
		var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
		<?php
	} elseif ($vbdateformat == "%m/%d/%Y") {
		?>
		var vbinmonth = parseInt(vbcheckinp[0]);
		vbinmonth = vbinmonth - 1;
		var vbinday = parseInt(vbcheckinp[1], 10);
		var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
		var vboutmonth = parseInt(vbcheckoutp[0]);
		vboutmonth = vboutmonth - 1;
		var vboutday = parseInt(vbcheckoutp[1], 10);
		var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
		<?php
	} else {
		?>
		var vbinmonth = parseInt(vbcheckinp[1]);
		vbinmonth = vbinmonth - 1;
		var vbinday = parseInt(vbcheckinp[2], 10);
		var vbcheckind = new Date(vbcheckinp[0], vbinmonth, vbinday);
		var vboutmonth = parseInt(vbcheckoutp[1]);
		vboutmonth = vboutmonth - 1;
		var vboutday = parseInt(vbcheckoutp[2], 10);
		var vbcheckoutd = new Date(vbcheckoutp[0], vboutmonth, vboutday);
		<?php
	}
	?>
		var vbdivider = 1000 * 60 * 60 * 24;
		var vbints = vbcheckind.getTime();
		var vboutts = vbcheckoutd.getTime();
		if (vboutts > vbints) {
			//var vbnights = Math.ceil((vboutts - vbints) / (vbdivider));
			var utc1 = Date.UTC(vbcheckind.getFullYear(), vbcheckind.getMonth(), vbcheckind.getDate());
			var utc2 = Date.UTC(vbcheckoutd.getFullYear(), vbcheckoutd.getMonth(), vbcheckoutd.getDate());
			var vbnights = Math.ceil((utc2 - utc1) / vbdivider);
			if (vbnights > 0) {
				document.getElementById('vbjstotnights'+rid).innerHTML = '<?php echo addslashes(JText::translate('VBJSTOTNIGHTS')); ?>: '+vbnights;
			} else {
				document.getElementById('vbjstotnights'+rid).innerHTML = '';
			}
		} else {
			document.getElementById('vbjstotnights'+rid).innerHTML = '';
		}
	} else {
		document.getElementById('vbjstotnights'+rid).innerHTML = '';
	}
}
function vbAddElement(rid) {
	var ni = document.getElementById('vbo-search-num-aduchild-block'+rid);
	var numi = document.getElementById('vbroomdethelper'+rid);
	var num = (document.getElementById('vbroomdethelper'+rid).value -1)+ 2;
	numi.value = num;
	var newdiv = document.createElement('div');
	var divIdName = 'vb'+num+'detracont';
	newdiv.setAttribute('id',divIdName);
	newdiv.innerHTML = '<div class=\'vbo-search-num-aduchild-entry\'><span class=\'vbo-search-roomnum\'><?php echo addslashes(JText::translate('VBFORMNUMROOM')); ?> '+ num +'</span><div class=\'vbo-search-num-adults-entry\'><label class=\'vbo-search-num-adults-entry-label\'><?php echo addslashes(JText::translate($use_guests_label)); ?></label><span class=\'vbo-search-num-adults-entry-inp\'><?php echo addslashes(str_replace('"', "'", $adultsel)); ?></span></div><?php if ($showchildren): ?><div class=\'vbo-search-num-children-entry\'><label class=\'vbo-search-num-children-entry-label\'><?php echo addslashes(JText::translate('VBFORMCHILDREN')); ?></label><span class=\'vbo-search-num-adults-entry-inp\'><?php echo addslashes(str_replace('"', "'", $childrensel)); ?></span></div><?php endif; ?></div>';
	ni.appendChild(newdiv);
}
function vbSetRoomsAdults(totrooms, rid) {
	var actrooms = parseInt(document.getElementById('vbroomdethelper'+rid).value);
	var torooms = parseInt(totrooms);
	var difrooms;
	if (torooms > actrooms) {
		difrooms = torooms - actrooms;
		for(var ir=1; ir<=difrooms; ir++) {
			vbAddElement(rid);
		}
	}
	if (torooms < actrooms) {
		for(var ir=actrooms; ir>torooms; ir--) {
			if (ir > 1) {
				var rmra = document.getElementById('vb' + ir + 'detracont');
				rmra.parentNode.removeChild(rmra);
			}
		}
		document.getElementById('vbroomdethelper'+rid).value = torooms;
	}
}
function vbFullObject(obj) {
	var jk;
	for(jk in obj) {
		return obj.hasOwnProperty(jk);
	}
}
jQuery(document).ready(function() {
	jQuery('.vb-cal-img, .vbo-caltrigger').click(function(){
		var jdp = jQuery(this).prev('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});
<?php
if (count($thumbs_rel)) {
	foreach ($thumbs_rel as $rel) {
		?>
	jQuery("a[rel=room<?php echo $rel; ?>]").fancybox({
		'helpers': {
			'overlay': {
				'locked': false
			}
		},
		'padding': 0,
		'transitionIn': 'none',
		'transitionOut': 'none',
		'titlePosition': 'outside'
	});
		<?php
	}
}
?>
});
</script>