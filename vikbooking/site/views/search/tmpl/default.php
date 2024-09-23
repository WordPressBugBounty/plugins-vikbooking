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

$vbdateformat = VikBooking::getDateFormat();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();

//load jQuery lib e jQuery UI
$document = JFactory::getDocument();
if (VikBooking::loadJquery()) {
	//JHtml::fetch('jquery.framework', true, true);
	JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-1.12.4.min.js');
}
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery-ui.min.css');
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery.fancybox.js');
$document->addStyleSheet(VBO_SITE_URI . 'resources/vik-dots-slider.css');
JHtml::fetch('script', VBO_SITE_URI . 'resources/vik-dots-slider.js');
//

// category_id must be a STRING, not an INT, for Shortcode compatibility (forced category in View params)
$pcategory_id = VikRequest::getString('category_id', '', 'request');
// single category filter
$pcategories = VikRequest::getString('categories', '', 'request');
// multiple category filters
$pcategory_ids = VikRequest::getVar('category_ids', array());
// current menu item ID or page
$pitemid = VikRequest::getInt('Itemid', 0, 'request');

$totadults = 0;
$totchildren = 0;

foreach ($this->arrpeople as $aduchild) {
	$totadults += $aduchild['adults'];
	$totchildren += $aduchild['children'];
}

?>
<script type="text/javascript">
var vbdialog_on = false;

/**
 * Other template files can turn off the display of the modal/dialog for multiple rooms booking.
 * 
 * @var 	bool
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
var vbo_multirooms_dialog = true;

jQuery(document).ready(function() {
	jQuery(".vbmodalframe").fancybox({
		"helpers": {
			"overlay": {
				"locked": false
			}
		},
		"width": "45%",
		"height": "80%",
		"autoScale": true,
		"transitionIn": "none",
		"transitionOut": "none",
		"padding": 0,
		"fitToView" : false,
		"autoSize" : false,
		"type": "iframe" 
	});
	jQuery(document).mouseup(function(e) {
		if (!vbdialog_on) {
			return false;
		}
		var vbdialog_cont = jQuery(".vbdialog-inner");
		if (!vbdialog_cont.is(e.target) && vbdialog_cont.has(e.target).length === 0) {
			vbDialogClose();
		}
	});
});
<?php
if (count($this->js_overcounter) > 0) {
	echo 'var r_counter = '.json_encode($this->js_overcounter).";\n";
} else {
	echo 'var r_counter = {};'."\n";
}
?>
var arridroom = new Array();
<?php
$arr_nr = array();
$disp_rooms = array();
for ($ir = 1; $ir <= $this->roomsnum; $ir++) {
	$arr_nr[$ir] = '';
	$nowrooms = array();
	foreach ($this->res[$ir] as $room) {
		$nowrooms[] = '"'.$room[0]['idroom'].'"';
		$disp_rooms[$ir][] = (int)$room[0]['idroom'];
	}
?>
arridroom[<?php echo $ir; ?>] = new Array(<?php echo implode(",", $nowrooms); ?>);
<?php
}
echo 'var sel_rooms = '.json_encode($arr_nr).";\n";
echo 'var disp_rooms = '.json_encode($disp_rooms).";\n";
?>
function vbDialogClose() {
	jQuery("#vbdialog-overlay").fadeOut();
	vbdialog_on = false;
}
function vbDialog(totr, selr, roomnum, idroom) {
	var roomimg = jQuery("#vbroomimg"+roomnum+"_"+idroom).attr("src");
	var roomname = jQuery("#vbroomname"+roomnum+"_"+idroom).text();
	jQuery("#vbdialogrimage").attr("src", roomimg);
	jQuery("#vbdialogrname").text(roomname);
	if (totr == selr) {
		jQuery("#vbdialog-confirm").attr("onclick", "Javasript: vbDialogClose();document.getElementById('vbselectroomform').submit();");
	} else {
		var nextr = selr + 1;
		jQuery("#vbdialog-confirm").attr("onclick", "Javasript: vbDialogClose();jQuery('html,body').animate({ scrollTop: (jQuery('#vbpositionroom"+nextr+"').offset().top - 5) }, { duration: 'slow' });");
	}
	jQuery("#vbdialog-overlay").fadeIn();
	vbdialog_on = true;
}
function vbhasClass(ele, cls) {
	if (ele == null) {
		return false;
	} else {
		return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
	}
}
function vbaddClass(ele, cls) {
	if (!this.vbhasClass(ele,cls)) ele.className += " "+cls;
}
function vbremoveClass(ele, cls) {
	if (vbhasClass(ele,cls)) {
		var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
		ele.className=ele.className.replace(reg,' ').replace(/\s+/g,' ').replace(/^\s|\s$/,'');
	}
}
function vbinArray(needle, haystack) {
	var arrpos;
	if (typeof jQuery != 'undefined') {
		arrpos = jQuery.inArray(needle, haystack);
	} else {
		arrpos = haystack.indexOf(needle);
	}
	return arrpos >= 0 ? true : false;
}
function vbSelectRoom(roomnum, idroom) {
	var totrooms = <?php echo $this->roomsnum; ?>;
	if (r_counter.hasOwnProperty(idroom) && totrooms > 1) {
		if (r_counter[idroom]['used'] >= r_counter[idroom]['unitsavail']) {
			alert('<?php echo addslashes(JText::translate('VBERRJSNOUNITS')); ?>');
			return false;
		} else {
			if ((r_counter[idroom]['used'] + 1) >= r_counter[idroom]['unitsavail']) {
				var excess = r_counter[idroom]['count'] - r_counter[idroom]['unitsavail'];
				var unselected = new Array();
				for (var x = totrooms; x >= 1; x--) {
					if (sel_rooms[x].length == 0) {
						unselected.push(x);
					}
				}
				for (var x = totrooms; x >= 1 && excess > 0; x--) {
					if (unselected.length == 1 && vbinArray(parseInt(roomnum), unselected) && ((r_counter[idroom]['used'] + 1) == r_counter[idroom]['unitsavail'])) {
						break;
					}
					if (x != roomnum && vbinArray(parseInt(idroom), disp_rooms[x]) && (vbhasClass(document.getElementById('vbcontainer'+x+'_'+idroom), 'room_selected') || document.getElementById('roomopt'+x).value.length == 0)) {
						if (typeof jQuery != 'undefined') {
							jQuery('#vbcontainer'+x+'_'+idroom).fadeOut();
						} else {
							document.getElementById('vbcontainer'+x+'_'+idroom).style.display = 'none';
						}
						document.getElementById('roomopt'+x).value = '';
						vbremoveClass(document.getElementById('vbcontainer'+x+'_'+idroom), 'room_selected');
						excess--;
					}
				}
			}
			if (sel_rooms[roomnum] != idroom && (sel_rooms[roomnum].length > 0 || sel_rooms[roomnum] > 0) && r_counter[sel_rooms[roomnum]]['used'] > 0) {
				for (var x = 1; x <= totrooms; x++) {
					if (x == roomnum || r_counter[sel_rooms[roomnum]]['used'] < r_counter[sel_rooms[roomnum]]['unitsavail']) {
						continue;
					}
					if (typeof jQuery != 'undefined') {
						jQuery('#vbcontainer'+x+'_'+sel_rooms[roomnum]).fadeIn();
					} else {
						document.getElementById('vbcontainer'+x+'_'+sel_rooms[roomnum]).style.display = 'block';
					}
				}
				r_counter[sel_rooms[roomnum]]['used']--;
			}
			if (sel_rooms[roomnum] != idroom) {
				r_counter[idroom]['used']++;
			}
			sel_rooms[roomnum] = idroom;
		}
	}
	vbaddClass(document.getElementById('vbcontainer'+roomnum+'_'+idroom), 'room_selected');
	document.getElementById('vbselector'+roomnum+'_'+idroom).innerHTML = '<?php echo addslashes(JText::translate('VBSELECTEDR')); ?>';
	for (val in arridroom[roomnum]) {
		if (arridroom[roomnum][val] != idroom) {
			if (vbhasClass(document.getElementById('vbcontainer'+roomnum+'_'+arridroom[roomnum][val]), 'room_selected')) {
				vbremoveClass(document.getElementById('vbcontainer'+roomnum+'_'+arridroom[roomnum][val]), 'room_selected');
				document.getElementById('vbselector'+roomnum+'_'+arridroom[roomnum][val]).innerHTML = '<?php echo addslashes(JText::translate('VBSELECTR')); ?>';
			}
		}
	}
	document.getElementById('roomopt'+roomnum).value = idroom;
	var selectedrooms = 0;
	for (var x = 1; x <= totrooms; x++) {
		var roomsel = document.getElementById('roomopt'+x).value;
		if (roomsel.length > 0) {
			selectedrooms++;
		}
	}
	if (totrooms == selectedrooms) {
		document.getElementById('vbsearchmainsbmt').style.display = 'block';
	}
	if (!(totrooms >= 2)) {
		//print the dialog message for at least two rooms booked
		document.getElementById('vbselectroomform').submit();
		return true;
	}
	if (vbo_multirooms_dialog && typeof jQuery != 'undefined') {
    	vbDialog(totrooms, selectedrooms, roomnum, idroom);  
	}
}
</script>

<div id="vbdialog-overlay" style="display: none;">
	<a class="vbdialog-overlay-close" href="javascript: void(0);"></a>
	<div class="vbdialog-inner">
		<div class="vbdialog-left">
			<div class="vbdialogrimage"><img id="vbdialogrimage" src=""/></div>
		</div>
		<div class="vbdialog-right">
			<div class="vbdialog-right-top">
				<span class="vbdialog-intro"><?php echo JText::translate('VBDIALOGMESSONE'); ?></span>
				<h3 id="vbdialogrname" class="vbdialogrname"></h3>
			</div>
			<div class="vbdialog-right-bottom">
				<button type="button" class="btn vbo-pref-color-btn-secondary" id="vbdialog-cancel" onclick="Javascript: vbDialogClose();"><?php echo JText::translate('VBDIALOGBTNCANCEL'); ?></button>
				<button type="button" class="btn vbo-pref-color-btn" id="vbdialog-confirm" onclick="Javascript: void(0);"><?php echo JText::translate('VBDIALOGBTNCONTINUE'); ?></button>
			</div>
		</div>
	</div>
</div>

<?php
if (count($this->mod_booking)) {
	//booking modification
	?>
<div class="vbo-booking-modification-helper">
	<div class="vbo-booking-modification-helper-inner">
		<div class="vbo-booking-modification-msg">
			<span><?php echo JText::translate('VBOMODBOOKHELPROOMS'); ?></span>
		</div>
		<div class="vbo-booking-modification-canc">
			<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=cancelmodification&sid='.$this->mod_booking['sid'].'&id='.$this->mod_booking['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>">
				<?php VikBookingIcons::e('times-circle'); ?>
				<?php echo JText::translate('VBOMODBOOKCANCMOD'); ?>
			</a>
		</div>
	</div>
</div>
	<?php
}
?>

<div class="vbstepsbarcont">
	<ol class="vbo-stepbar" data-vbosteps="4">
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking&checkin='.$this->checkin.'&checkout='.$this->checkout.'&category_id='.$pcategory_id.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBSTEPDATES'); ?></a></li>
		<li class="vbo-step vbo-step-current"><span><?php echo JText::translate('VBSTEPROOMSELECTION'); ?></span></li>
		<li class="vbo-step vbo-step-next"><span><?php echo JText::translate('VBSTEPOPTIONS'); ?></span></li>
		<li class="vbo-step vbo-step-next"><span><?php echo JText::translate('VBSTEPCONFIRM'); ?></span></li>
	</ol>
</div>

<div class="vbo-results-wrapper">
	<div class="vbo-results-head">
		<div class="vbo-results-checkin">
			<?php VikBookingIcons::e('sign-in', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBPICKUP'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo date(str_replace("/", $datesep, $df), $this->checkin); ?></span>
			</div>
		</div>
		<div class="vbo-results-nights">
			<?php VikBookingIcons::e('calendar', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo ($this->days == 1 ? JText::translate('VBSEARCHRESNIGHT') : JText::translate('VBSEARCHRESNIGHTS')); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $this->days; ?></span>
			</div>
		</div>
	<?php
	if ($this->roomsnum > 1) {
		?>
		<div class="vbo-results-numrooms">
			<?php VikBookingIcons::e('bed', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo $this->roomsnum == 1 ? JText::translate('VBSEARCHRESROOM') : JText::translate('VBSEARCHRESROOMS'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $this->roomsnum; ?></span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-results-numadults">
			<?php VikBookingIcons::e('male', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo ($totadults == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $totadults; ?></span>
			</div>
		</div>
	<?php
	if ($this->showchildren && $totchildren > 0) {
		?>
		<div class="vbo-results-numchildren">
			<?php VikBookingIcons::e('child', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo $totchildren == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $totchildren; ?></span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-results-chdates">
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-val">
					<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking&checkin='.$this->checkin.'&checkout='.$this->checkout.'&category_id='.$pcategory_id.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" class="vbo-pref-color-btn-secondary"><?php echo JText::translate('VBCHANGEDATES'); ?></a>
				</span>
			</div>
		</div>
	</div>
<?php
$search_tpl = VikBooking::searchResultsTmpl();
if ($this->roomsnum < 2) {
	/**
	 * By default, we load the classic template file when just one room-party was requested,
	 * or when the classic template file was chosen from the configuration settings.
	 * To always load the compact template file, just override this file default.php to call
	 * echo $this->loadTemplate($search_tpl);
	 * all the times, no matter of how many rooms were requested.
	 */
	echo $this->loadTemplate('classic');
} else {
	echo $this->loadTemplate($search_tpl);
}
?>
</div>

<?php
/**
 * @wponly 	if the Itemid is missing, maybe because of a redirect, then using JRoute::rewrite('index.php?option=com_vikbooking')
 * 			generated an empty URL (the home page), by losing the navigation and by rendering an invalid page.
 * 			So we need to use JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking') like the link above.
 */
?>
<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking'.(!empty($pcategory_id) ? '&category_id=' . $pcategory_id : '').(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" id="vbselectroomform">
	<input type="hidden" name="option" value="com_vikbooking"/>
	<input type="hidden" name="task" value="showprc"/>
	<input type="hidden" id="roomsnum" name="roomsnum" value="<?php echo $this->roomsnum; ?>"/>
	<?php
	for ($ir = 1; $ir <= $this->roomsnum; $ir++) {
		?>
		<input type="hidden" id="roomopt<?php echo $ir; ?>" name="roomopt[]" value=""/>
		<?php
	}
	foreach ($this->arrpeople as $indroom => $aduch) {
		?>
		<input type="hidden" name="adults[]" value="<?php echo $aduch['adults']; ?>"/>
		<?php
		if ($this->showchildren) {
			?>
			<input type="hidden" name="children[]" value="<?php echo $aduch['children']; ?>"/>
			<?php	
		}
	}
	?>
	<input type="hidden" name="days" value="<?php echo $this->days; ?>"/>
	<input type="hidden" name="checkin" value="<?php echo $this->checkin; ?>"/>
	<input type="hidden" name="checkout" value="<?php echo $this->checkout; ?>"/>
	<input type="hidden" name="category_id" value="<?php echo $pcategory_id; ?>"/>
	<input type="hidden" name="categories" value="<?php echo $pcategories; ?>"/>
	<?php
	if (is_array($pcategory_ids) && count($pcategory_ids)) {
		foreach ($pcategory_ids as $pcid) {
			?>
	<input type="hidden" name="category_ids[]" value="<?php echo $pcid; ?>"/>
			<?php
		}
	}
	if (!empty ($pitemid)) {
	?>
	<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
	<?php
	}
	?>
	<div class="goback">
		<a class="vbo-goback-link vbo-pref-color-btn-secondary" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking&checkin='.$this->checkin.'&checkout='.$this->checkout.'&category_id='.$pcategory_id.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBCHANGEDATES'); ?></a>
	</div>
	<div id="vbsearchmainsbmt" class="vbsearchmainsbmt" style="display: none;">
		<input type="submit" name="continue" value="<?php echo JText::translate('VBSEARCHCONTINUESUBM'); ?>" class="btn vbsubmit vbo-pref-color-btn"/>
	</div>
</form>
