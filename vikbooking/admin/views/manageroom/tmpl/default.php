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

$row = $this->row;
$cats = $this->cats;
$carats = $this->carats;
$optionals = $this->optionals;
$adultsdiff = $this->adultsdiff;

JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.sortable.min.js');

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$currencysymb = VikBooking::getCurrencySymb(true);
$arrcats = array();
$arrcarats = array();
$arropts = array();
$oldcats = count($row) ? explode(";", (string)$row['idcat']) : array();
foreach ($oldcats as $oc) {
	if (!empty($oc)) {
		$arrcats[$oc] = $oc;
	}
}
$oldcarats = count($row) ? explode(";", (string)$row['idcarat']) : array();
foreach ($oldcarats as $ocr) {
	if (!empty($ocr)) {
		$arrcarats[$ocr] = $ocr;
	}
}
$oldopts = count($row) ? explode(";", (string)$row['idopt']) : array();
foreach ($oldopts as $oopt) {
	if (!empty($oopt)) {
		$arropts[$oopt] = $oopt;
	}
}
$wcats = "";
$wcarats = "";
$woptionals = "";
if (is_array($cats)) {
	$wcats = "<select name=\"ccat[]\" multiple=\"multiple\" id=\"categories_sel\" size=\"".(count($cats) + 1)."\">";
	foreach ($cats as $cat) {
		$wcats .= "<option value=\"".$cat['id']."\"".(array_key_exists($cat['id'], $arrcats) ? " selected=\"selected\"" : "").">".$cat['name']."</option>\n";
	}
	$wcats .= "</select>\n";
}
if (is_array($carats)) {
	$wcarats = "<div class=\"vbo-roomentries-cont\">";
	$nn = 0;
	foreach ($carats as $kcarat => $carat) {
		$wcarats .= "<div class=\"vbo-roomentry-cont\"><input type=\"checkbox\" name=\"ccarat[]\" id=\"carat".$kcarat."\" value=\"".$carat['id']."\"".(array_key_exists($carat['id'], $arrcarats) ? " checked=\"checked\"" : "")."/> <label for=\"carat".$kcarat."\">".$carat['name']."</label></div>\n";
		$nn++;
		if (($nn % 3) == 0) {
			$wcarats .= "</div>\n<div class=\"vbo-roomentries-cont\">";
		}
	}
	$wcarats .= "</div>\n";
}
if (is_array($optionals)) {
	$woptionals = "<div class=\"vbo-roomentries-cont\">";
	$nn = 0;
	foreach ($optionals as $kopt => $optional) {
		$woptionals .= "<div class=\"vbo-roomentry-cont\"><input type=\"checkbox\" name=\"coptional[]\" id=\"opt".$kopt."\" value=\"".$optional['id']."\"".(array_key_exists($optional['id'], $arropts) ? " checked=\"checked\"" : "")."/> <label for=\"opt".$kopt."\">".$optional['name']." ".(empty($optional['ageintervals']) ? ((int)$optional['pcentroom'] ? $optional['cost'].'%' : $currencysymb." ".$optional['cost']) : "")."</label></div>\n";
		$nn++;
		if (($nn % 3) == 0) {
			$woptionals .= "</div>\n<div class=\"vbo-roomentries-cont\">";
		}
	}
	$woptionals .= "</div>\n";
}
//more images
$morei = count($row) ? explode(';;', (string)$row['moreimgs']) : array();
$totmorei = count($morei);
$actmoreimgs = "";
if ($totmorei > 0) {
	$notemptymoreim = false;
	$imgcaptions = json_decode((string)$row['imgcaptions'], true);
	$usecaptions = empty($imgcaptions) || !is_array($imgcaptions) || !(count($imgcaptions) > 0) ? false : true;
	foreach ($morei as $ki => $mi) {
		if (!empty($mi)) {
			$notemptymoreim = true;
			$actmoreimgs .= '<li class="vbo-editroom-currentphoto">';
			$actmoreimgs .= '<a href="'.VBO_SITE_URI.'resources/uploads/big_'.$mi.'" target="_blank" class="vbomodal"><img src="'.VBO_SITE_URI.'resources/uploads/thumb_'.$mi.'" class="maxfifty"/></a>';
			$actmoreimgs .= '<a class="vbo-toggle-imgcaption" href="javascript: void(0);" onclick="vbOpenImgDetails(\''.$ki.'\', this)"><i class="'.VikBookingIcons::i('cog').'"></i></a>';
			$actmoreimgs .= '<div id="vbimgdetbox'.$ki.'" class="vbimagedetbox" style="display: none;"><div class="captionlabel"><span>'.JText::translate('VBIMGCAPTION').'</span><input type="text" name="caption'.$ki.'" value="'.($usecaptions === true && isset($imgcaptions[$ki]) ? $imgcaptions[$ki] : "").'" size="40"/></div><input type="hidden" name="imgsorting[]" value="'.$mi.'"/><input class="captionsubmit" type="button" name="updcatpion" value="'.JText::translate('VBIMGUPDATE').'" onclick="javascript: updateCaptions();"/><div class="captionremoveimg"><a class="vbimgrm btn btn-danger" href="index.php?option=com_vikbooking&task=removemoreimgs&roomid='.$row['id'].'&imgind='.$ki.'" title="'.JText::translate('VBREMOVEIMG').'"><i class="icon-remove"></i>'.JText::translate('VBREMOVEIMG').'</a></div></div>';
			$actmoreimgs .= '</li>';
		}
	}
}
//end more images
//num adults charges/discounts only if the max numb of adults allowed is > than 1 and the minimum is less than the maximum 
$writeadultsdiff = false;
if (count($row) && $row['toadult'] > 1 && $row['fromadult'] < $row['toadult']) {
	$writeadultsdiff = true;
	$stradultsdiff = "";
	$startadind = $row['fromadult'] > 0 ? $row['fromadult'] : 1;
	$parseadultsdiff = array();
	if (@is_array($adultsdiff)) {
		foreach ($adultsdiff as $adiff) {
			$parseadultsdiff[$adiff['adults']] = $adiff;
		}
	}
	for ($adi = $startadind; $adi <= $row['toadult']; $adi++) {
		$stradultsdiff .= "<p>";
		$stradultsdiff .= '<span class="vbo-adults-usage">' . JText::sprintf('VBADULTSDIFFNUM', $adi) . "</span><select name=\"adultsdiffchdisc[]\"><option value=\"1\"".(array_key_exists($adi, $parseadultsdiff) && $parseadultsdiff[$adi]['chdisc'] == 1 ? " selected=\"selected\"" : "").">".JText::translate('VBADULTSDIFFCHDISCONE')."</option><option value=\"2\"".(array_key_exists($adi, $parseadultsdiff) && $parseadultsdiff[$adi]['chdisc'] == 2 ? " selected=\"selected\"" : "").">".JText::translate('VBADULTSDIFFCHDISCTWO')."</option></select>\n";
		$stradultsdiff .= "<input type=\"number\" step=\"any\" name=\"adultsdiffval[]\" value=\"".(array_key_exists($adi, $parseadultsdiff) ? $parseadultsdiff[$adi]['value'] : "")."\" size=\"3\" style=\"width: 40px;\"/><input type=\"hidden\" name=\"adultsdiffnum[]\" value=\"".$adi."\"/>\n";
		$stradultsdiff .= "<select name=\"adultsdiffvalpcent[]\"><option value=\"1\"".(array_key_exists($adi, $parseadultsdiff) && $parseadultsdiff[$adi]['valpcent'] == 1 ? " selected=\"selected\"" : "").">".$currencysymb."</option><option value=\"2\"".(array_key_exists($adi, $parseadultsdiff) && $parseadultsdiff[$adi]['valpcent'] == 2 ? " selected=\"selected\"" : "").">%</option></select>\n";
		$stradultsdiff .= "<select name=\"adultsdiffpernight[]\"><option value=\"1\"".(array_key_exists($adi, $parseadultsdiff) && $parseadultsdiff[$adi]['pernight'] == 1 ? " selected=\"selected\"" : "").">".JText::translate('VBADULTSDIFFONPERNIGHT')."</option><option value=\"0\"".(array_key_exists($adi, $parseadultsdiff) && $parseadultsdiff[$adi]['pernight'] == 0 ? " selected=\"selected\"" : "").">".JText::translate('VBADULTSDIFFONTOTAL')."</option></select>\n";
		$stradultsdiff .= "</p>\n";
	}
}
//
$editor = JEditor::getInstance(JFactory::getApplication()->get('editor'));
?>
<script type="text/javascript">
//Code to debug the size of the form to be submitted in case it will exceed the PHP post_max_size
/*
Joomla.submitbutton = function(task) {
	console.log(jQuery("#adminForm").not("[type='file']").serialize().length);
	Joomla.submitform(task, document.adminForm);
}
*/
//
function showResizeSel() {
	if (document.adminForm.autoresize.checked == true) {
		document.getElementById('resizesel').style.display='inline-block';
	} else {
		document.getElementById('resizesel').style.display='none';
	}
	return true;
}
function showResizeSelMore() {
	if (document.adminForm.autoresizemore.checked == true) {
		document.getElementById('resizeselmore').style.display='inline-block';
	} else {
		document.getElementById('resizeselmore').style.display='none';
	}
	return true;
}
function addMoreImages() {
	var ni = document.getElementById('myDiv');
	var numi = document.getElementById('moreimagescounter');
	var num = (document.getElementById('moreimagescounter').value -1)+ 2;
	numi.value = num;
	var newdiv = document.createElement('div');
	var divIdName = 'my'+num+'Div';
	newdiv.setAttribute('id', divIdName);
	newdiv.setAttribute('class', 'vbo-first-imgup');
	newdiv.innerHTML = '<input type=\'file\' name=\'cimgmore[]\' size=\'35\'/><div class=\'vbo-imgup-caption\'><span><?php echo addslashes(JText::translate('VBIMGCAPTION')); ?></span> <input type=\'text\' name=\'cimgcaption[]\' size=\'30\' value=\'\'/></div>';
	ni.appendChild(newdiv);
}
function vbPlusMinus(what, how) {
	var inp = document.getElementById(what);
	var actval = inp.value;
	var newval = 0;
	if (how == 'plus') {
		newval = parseInt(actval) + 1;
	} else {
		if (parseInt(actval) >= 1) {
			newval = parseInt(actval) - 1;
		}
	}
	inp.value = newval;
	<?php
	if ($writeadultsdiff == true) {
		?>
		var origfrom = <?php echo $row['fromadult']; ?>;
		var origto = <?php echo $row['toadult']; ?>;
		if (what == 'fromadult') {
			if (newval == origfrom) {
				document.getElementById('vbadultsdiffsavemess').style.display = 'none';
				document.getElementById('vbadultsdiffbox').style.display = 'block';
			} else {
				document.getElementById('vbadultsdiffbox').style.display = 'none';
				document.getElementById('vbadultsdiffsavemess').style.display = 'block';
			}
		}
		if (what == 'toadult') {
			if (newval == origto) {
				document.getElementById('vbadultsdiffsavemess').style.display = 'none';
				document.getElementById('vbadultsdiffbox').style.display = 'block';
			} else {
				document.getElementById('vbadultsdiffbox').style.display = 'none';
				document.getElementById('vbadultsdiffsavemess').style.display = 'block';
			}
		}
		<?php
	}
	?>
	if (what == 'toadult' || what == 'tochild') {
		vbMaxTotPeople();
	}
	if (what == 'fromadult' || what == 'fromchild') {
		vbMinTotPeople();
	}
	return true;
}
function vbMaxTotPeople() {
	var toadu = document.getElementById('toadult').value;
	var tochi = document.getElementById('tochild').value;
	document.getElementById('totpeople').value = parseInt(toadu) + parseInt(tochi);
	var suggocc = document.getElementById('suggocc').value;
	if (!suggocc.length || (parseInt(suggocc) < 2 && parseInt(toadu) > 1)) {
		document.getElementById('suggocc').value = toadu;
	}
	return true;
}
function vbMinTotPeople() {
	var fadu = document.getElementById('fromadult').value;
	var fchi = document.getElementById('fromchild').value;
	document.getElementById('mintotpeople').value = parseInt(fadu) + parseInt(fchi);
	return true;
}
function togglePriceCalendarParam() {
	if (parseInt(document.getElementById('pricecal').value) == 1) {
		document.getElementById('defcalcostp').style.display = 'flex';
	} else {
		document.getElementById('defcalcostp').style.display = 'none';
	}
}
function toggleSeasonalCalendarParam() {
	if (parseInt(document.getElementById('seasoncal').value) > 0) {
		jQuery('.param-seasoncal').show();
	} else {
		jQuery('.param-seasoncal').each(function(k, v) {
			if (k > 0) {
				jQuery(this).hide();
			}
		});
	}
}
var vbo_details_on = false;
function vbOpenImgDetails(key, el) {
	if (vbo_details_on === true) {
		jQuery('.vbimagedetbox').not('#vbimgdetbox'+key).hide();
		jQuery('.vbo-toggle-imgcaption.vbo-toggle-imgcaption-on').removeClass('vbo-toggle-imgcaption-on');
	}
	if (document.getElementById('vbimgdetbox'+key).style.display == 'none') {
		document.getElementById('vbimgdetbox'+key).style.display = 'block';
		jQuery(el).addClass('vbo-toggle-imgcaption-on');
		vbo_details_on = true;
	} else {
		document.getElementById('vbimgdetbox'+key).style.display = 'none';
		jQuery(el).removeClass('vbo-toggle-imgcaption-on');
		vbo_details_on = false;
	}
}
function updateCaptions() {
	var ni = document.adminForm;
	var newdiv = document.createElement('div');
	newdiv.innerHTML = '<input type=\'hidden\' name=\'updatecaption\' value=\'1\'/>';
	ni.appendChild(newdiv);
	document.adminForm.task.value='updateroom';
	document.adminForm.submit();
}
function vboToggleRoomUpgrade() {
	if (jQuery('input[name="room_upgrade"]').is(':checked')) {
		jQuery('.vbo-roomupgrade-param').show();
	} else {
		jQuery('.vbo-roomupgrade-param').hide();
	}
}
function vboToggleMaxAdvNotice(is_checked) {
	if (!is_checked) {
		jQuery('.vbo-room-level-max_adv-notice').hide();
	} else {
		jQuery('.vbo-room-level-max_adv-notice').show();
	}
}
/* Start - Room Disctinctive Features */
var cur_units = <?php echo count($row) ? $row['units'] : '1'; ?>;

jQuery(function() {
	jQuery('#share_with_sel, #categories_sel, #upgrade-rooms').select2();
	jQuery(".vbo-sortable").sortable({
		helper: 'clone'
	});
	jQuery(".vbo-sortable").disableSelection();
	jQuery('#vbo-distfeatures-toggle').click(function() {
		jQuery(this).toggleClass('btn-primary');
		jQuery('.vbo-distfeatures-cont').fadeToggle();
	});
	jQuery('#room_units').change(function() {
		var to_units = parseInt(jQuery(this).val());
		if (to_units > 1) {
			jQuery('.param-multiunits').show();
			jQuery('.vbo-distfeature-row').css('display', 'flex');
		<?php
		if (!count($row)) {
			// suggest last available param to 1
			?>
			if (!jQuery('#lastavail').val().length || parseInt(jQuery('#lastavail').val()) < 1) {
				jQuery('#lastavail').val('1');
			}
			<?php
		}
		?>
		} else {
			jQuery('.param-multiunits').hide();
			jQuery('.vbo-distfeature-row').css('display', 'none');
		}
		if (to_units > cur_units) {
			var diff_units = (to_units - cur_units);
			for (var i = 1; i <= diff_units; i++) {
				var unit_html = "<div class=\"vbo-runit-features-cont\" id=\"runit-features-"+(i + cur_units)+"\">"+
								"	<span class=\"vbo-runit-num\"><?php echo addslashes(JText::translate('VBODISTFEATURERUNIT')); ?>"+(i + cur_units)+"</span>"+
								"	<div class=\"vbo-runit-features\">"+
								"		<div class=\"vbo-runit-feature\">"+
								"			<input type=\"text\" name=\"feature-name"+(i + cur_units)+"[]\" value=\"\" size=\"20\" placeholder=\"<?php echo JText::translate('VBODISTFEATURETXT'); ?>\"/>"+
								"			<input type=\"hidden\" name=\"feature-lang"+(i + cur_units)+"[]\" value=\"\"/>"+
								"			<input type=\"text\" name=\"feature-value"+(i + cur_units)+"[]\" value=\"\" size=\"20\" placeholder=\"<?php echo JText::translate('VBODISTFEATUREVAL'); ?>\"/>"+
								"			<span class=\"vbo-feature-remove\"><i class=\"<?php echo VikBookingIcons::i('far fa-minus-square'); ?>\"></i></span>"+
								"		</div>"+
								"		<span class=\"vbo-feature-add btn vbo-config-btn\"><i class=\"icon-new\"></i><?php echo addslashes(JText::translate('VBODISTFEATUREADD')); ?></span>"+
								"	</div>"+
								"</div>";
				jQuery('.vbo-distfeatures-cont').append(unit_html);
			}
			cur_units = to_units;
		} else if (to_units < cur_units) {
			for (var i = cur_units; i > to_units; i--) {
				jQuery('#runit-features-'+i).remove();
			}
			cur_units = to_units;
		}
	});

	jQuery(document.body).on('click', '.vbo-feature-add', function() {
		var cfeature_id = jQuery(this).parent('div').parent('div').attr('id').split('runit-features-');
		if (cfeature_id[1].length) {
			jQuery(this).before("<div class=\"vbo-runit-feature\">"+
								"	<input type=\"text\" name=\"feature-name"+cfeature_id[1]+"[]\" value=\"\" size=\"20\" placeholder=\"<?php echo JText::translate('VBODISTFEATURETXT'); ?>\"/>"+
								"	<input type=\"hidden\" name=\"feature-lang"+cfeature_id[1]+"[]\" value=\"\"/>"+
								"	<input type=\"text\" name=\"feature-value"+cfeature_id[1]+"[]\" value=\"\" size=\"20\" placeholder=\"<?php echo JText::translate('VBODISTFEATUREVAL'); ?>\"/>"+
								"	<span class=\"vbo-feature-remove\"><i class=\"<?php echo VikBookingIcons::i('far fa-minus-square'); ?>\"></i></span>"+
								"</div>"
								);
		}
	});

	jQuery(document.body).on('click', '.vbo-feature-remove', function() {
		jQuery(this).parent('div').remove();
	});
});
/* End - Room Disctinctive Features */
</script>
<?php
$vbo_app->prepareModalBox('.vbomodal', '', true);
?>
<input type="hidden" value="0" id="moreimagescounter" />

<form name="adminForm" id="adminForm" action="index.php" method="post" enctype="multipart/form-data">
<?php
if (count($row)) {
	?>
	<div class="vbo-mngroom-rtitle">
		<h3><?php echo $row['name']; ?></h3>
	</div>
	<?php
}
?>
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOROOMLEGUNITOCC'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMFIVE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="cname" value="<?php echo count($row) ? htmlspecialchars($row['name']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMEIGHT'); ?></div>
							<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('cavail', JText::translate('VBYES'), JText::translate('VBNO'), ((count($row) && intval($row['avail']) == 1) || !count($row) ? 'yes' : 0), 'yes', 0); ?></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMNINE'); ?></div>
							<div class="vbo-param-setting"><input type="number" min="1" name="units" id="room_units" value="<?php echo count($row) ? $row['units'] : '1'; ?>" size="3" onfocus="this.select();" /></div>
						</div>
						<?php
						$room_features = count($row) ? VikBooking::getRoomParam('features', $row['params']) : array(1 => VikBooking::getDefaultDistinctiveFeatures());
						if (!is_array($room_features)) {
							$room_features = array();
						}
						if (!(count($room_features) > 0)) {
							$default_features = VikBooking::getDefaultDistinctiveFeatures();
							for ($i = 1; $i <= $row['units']; $i++) {
								$room_features[$i] = $default_features;
							}
						}
						?>
						<div class="vbo-param-container vbo-distfeature-row" style="display: <?php echo count($row) && $row['units'] > 1 ? 'flex' : 'none'; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBOROOMUNITSDISTFEAT'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-distfeatures-toggle-cont">
									<span id="vbo-distfeatures-toggle" class="btn vbo-config-btn"><?php VikBookingIcons::e('binoculars'); ?><?php echo JText::translate('VBOROOMUNITSDISTFEATTOGGLE'); ?></span>
								</div>
								<div class="vbo-distfeatures-cont">
								<?php
								$unitslim = count($row) ? $row['units'] : 1;
								for ($i=1; $i <= $unitslim; $i++) {
									?>
									<div class="vbo-runit-features-cont" id="runit-features-<?php echo $i; ?>">
										<span class="vbo-runit-num"><?php echo JText::translate('VBODISTFEATURERUNIT'); ?><?php echo $i; ?></span>
										<div class="vbo-runit-features">
									<?php
									if (array_key_exists($i, $room_features)) {
										foreach ($room_features[$i] as $fkey => $fval) {
											?>
											<div class="vbo-runit-feature">
												<input type="text" name="feature-name<?php echo $i; ?>[]" value="<?php echo JText::translate($fkey); ?>" size="20"/>
												<input type="hidden" name="feature-lang<?php echo $i; ?>[]" value="<?php echo $fkey; ?>"/>
												<input type="text" name="feature-value<?php echo $i; ?>[]" value="<?php echo $fval; ?>" size="20"/>
												<span class="vbo-feature-remove"><?php VikBookingIcons::e('far fa-minus-square'); ?></span>
											</div>
											<?php
										}
									}
									?>
											<span class="vbo-feature-add btn vbo-config-btn"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBODISTFEATUREADD'); ?></span>
										</div>
									</div>
									<?php
								}
								?>
								</div>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMADULTS'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-param-setting-group">
									<div class="vbplusminuscont">
										<span><?php echo JText::translate('VBNEWROOMMIN'); ?></span>
										<input type="number" min="0" id="fromadult" name="fromadult" value="<?php echo count($row) ? $row['fromadult'] : '1'; ?>" size="4" onchange="vbMinTotPeople();" style="width: 40px;"/>
									</div>
									<div class="vbplusminus-btns">
										<span class="vbplusminus" onclick="vbPlusMinus('fromadult', 'plus');"><?php VikBookingIcons::e('far fa-plus-square'); ?></span>
										<span class="vbminus vbplusminus" onclick="vbPlusMinus('fromadult', 'minus');"><?php VikBookingIcons::e('far fa-minus-square'); ?></span>
									</div>
								</div>
								<div class="vbo-param-setting-group">
									<div class="vbplusminuscont">
										<span><?php echo JText::translate('VBNEWROOMMAX'); ?></span>
										<input type="number" min="0" id="toadult" name="toadult" value="<?php echo count($row) ? $row['toadult'] : '1'; ?>" size="3" onchange="vbMaxTotPeople();" style="width: 40px;"/>
									</div>
									<div class="vbplusminus-btns">
										<span class="vbplusminus" onclick="vbPlusMinus('toadult', 'plus');"><?php VikBookingIcons::e('far fa-plus-square'); ?></span>
										<span class="vbminus vbplusminus" onclick="vbPlusMinus('toadult', 'minus');"><?php VikBookingIcons::e('far fa-minus-square'); ?></span>
									</div>
								</div>
							</div>
						</div>
					<?php
					if ($writeadultsdiff == true) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWROOMADULTSDIFF'), 'content' => JText::translate('VBNEWROOMADULTSDIFFHELP'))); ?> <?php echo JText::translate('VBNEWROOMADULTSDIFF'); ?></div>
							<div class="vbo-param-setting">
								<div id="vbadultsdiffsavemess" style="display: none;">
									<span class="vbo-param-setting-comment"><?php echo JText::translate('VBNEWROOMNOTCHANGENUMMESS'); ?></span>
								</div>
								<div id="vbadultsdiffbox" style="display: block;"><?php echo $stradultsdiff; ?></div>
							</div>
						</div>
						<?php
					} else {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWROOMADULTSDIFF'), 'content' => JText::translate('VBNEWROOMADULTSDIFFHELP'))); ?> <?php echo JText::translate('VBNEWROOMADULTSDIFF'); ?></div>
							<div class="vbo-param-setting">
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBNEWROOMADULTSDIFFBEFSAVE'); ?></span>
							</div>
						</div>
						<?php
					}
					?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMCHILDREN'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-param-setting-group">
									<div class="vbplusminuscont">
										<span><?php echo JText::translate('VBNEWROOMMIN'); ?></span>
										<input type="number" min="0" id="fromchild" name="fromchild" value="<?php echo count($row) ? $row['fromchild'] : '0'; ?>" size="3" onchange="vbMinTotPeople();" style="width: 40px;"/>
									</div>
									<div class="vbplusminus-btns">
										<span class="vbplusminus" onclick="vbPlusMinus('fromchild', 'plus');"><?php VikBookingIcons::e('far fa-plus-square'); ?></span>
										<span class="vbminus vbplusminus" onclick="vbPlusMinus('fromchild', 'minus');"><?php VikBookingIcons::e('far fa-minus-square'); ?></span>
									</div>
								</div>
								<div class="vbo-param-setting-group">
									<div class="vbplusminuscont">
										<span><?php echo JText::translate('VBNEWROOMMAX'); ?></span>
										<input type="number" min="0" id="tochild" name="tochild" value="<?php echo count($row) ? $row['tochild'] : '0'; ?>" size="3" onchange="vbMaxTotPeople();" style="width: 40px;"/>
									</div>
									<div class="vbplusminus-btns">
										<span class="vbplusminus" onclick="vbPlusMinus('tochild', 'plus');"><?php VikBookingIcons::e('far fa-plus-square'); ?></span>
										<span class="vbminus vbplusminus" onclick="vbPlusMinus('tochild', 'minus');"><?php VikBookingIcons::e('far fa-minus-square'); ?></span>
									</div>
								</div>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></div>
							<div class="vbo-param-setting">
								<input type="number" min="1" name="totpeople" id="totpeople" value="<?php echo count($row) ? $row['totpeople'] : '1'; ?>" size="3" style="width: 40px;"/>
								<span class="vbo-param-setting-comment-inline"><?php echo JText::translate('VBMAXTOTPEOPLEDESC'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBMINTOTPEOPLE'); ?></div>
							<div class="vbo-param-setting">
								<input type="number" min="1" name="mintotpeople" id="mintotpeople" value="<?php echo count($row) ? $row['mintotpeople'] : '1'; ?>" size="3" style="width: 40px;"/>
								<span class="vbo-param-setting-comment-inline"><?php echo JText::translate('VBMINTOTPEOPLEDESC'); ?></span>
							</div>
						</div>
					</div>
				</div>
			</fieldset>

			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOROOMLEGCARATCATOPT'); ?></legend>
					<div class="vbo-params-container">
					<?php
					if (!empty($wcats)) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMONE'); ?></div>
							<div class="vbo-param-setting"><?php echo $wcats; ?></div>
						</div>
						<?php
					}
					if (!empty($wcarats)) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMTHREE'); ?></div>
							<div class="vbo-param-setting"><?php echo $wcarats; ?></div>
						</div>
						<?php
					}
					if (!empty($woptionals)) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMFOUR'); ?></div>
							<div class="vbo-param-setting"><?php echo $woptionals; ?></div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</fieldset>

			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBNEWROOMPARAMS'); ?></legend>
					<div class="vbo-params-container">
						<?php
						$multi_units = count($row) ? VikBooking::getRoomParam('multi_units', $row['params']) : 0;
						?>
						<div class="vbo-param-container param-multiunits" style="display: <?php echo (count($row) && $row['units'] > 0 ? 'flex' : 'none'); ?>;">
							<div class="vbo-param-label"><label for="multi_units"><?php echo JText::translate('VBPARAMROOMMULTIUNITS'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="multi_units" id="multi_units">
									<option value="0"><?php echo JText::translate('VBPARAMROOMMULTIUNITSDISABLE'); ?></option>
									<option value="1"<?php echo intval($multi_units) == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMROOMMULTIUNITSENABLE'); ?></option>
								</select>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMROOMMULTIUNITSHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="lastavail"><?php echo JText::translate('VBPARAMLASTAVAIL'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="number" min="0" name="lastavail" id="lastavail" value="<?php echo count($row) ? (int)VikBooking::getRoomParam('lastavail', $row['params']) : '0'; ?>"/>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMLASTAVAILHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="suggocc"><?php echo JText::translate('VBOPARAMSUGGOCC'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="number" min="0" name="suggocc" id="suggocc" value="<?php echo count($row) ? (int)VikBooking::getRoomParam('suggocc', $row['params']) : '1'; ?>"/>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="custprice"><?php echo JText::translate('VBPARAMCUSTPRICE'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="text" name="custprice" id="custprice" value="<?php echo count($row) ? VikBooking::getRoomParam('custprice', $row['params']) : ''; ?>" size="5"/>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMCUSTPRICEHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="custpricetxt"><?php echo JText::translate('VBPARAMCUSTPRICETEXT'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="text" name="custpricetxt" id="custpricetxt" value="<?php echo count($row) ? VikBooking::getRoomParam('custpricetxt', $row['params']) : ''; ?>" size="9"/>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMCUSTPRICETEXTHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="custpricesubtxt"><?php echo JText::translate('VBPARAMCUSTPRICESUBTEXT'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="text" name="custpricesubtxt" id="custpricesubtxt" value="<?php echo count($row) ? htmlentities(VikBooking::getRoomParam('custpricesubtxt', $row['params'])) : ''; ?>" size="31"/>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMCUSTPRICESUBTEXTHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="reqinfo"><?php echo JText::translate('VBORPARAMREQINFO'); ?></label></div>
							<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('reqinfo', JText::translate('VBYES'), JText::translate('VBNO'), (count($row) && intval(VikBooking::getRoomParam('reqinfo', $row['params'])) == 1 ? 1 : 0), 1, 0); ?></div>
						</div>
						<?php
						$paramshowpeople = count($row) ? VikBooking::getRoomParam('maxminpeople', $row['params']) : '';
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="maxminpeople"><?php echo JText::translate('VBPARAMSHOWPEOPLE'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="maxminpeople" id="maxminpeople">
									<option value="0"><?php echo JText::translate('VBPARAMSHOWPEOPLENO'); ?></option>
									<option value="1"<?php echo ($paramshowpeople == "1" ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBPARAMSHOWPEOPLEADU'); ?></option>
									<option value="2"<?php echo ($paramshowpeople == "2" ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBPARAMSHOWPEOPLECHI'); ?></option>
									<option value="3"<?php echo ($paramshowpeople == "3" ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBPARAMSHOWPEOPLEADUTOT'); ?></option>
									<option value="4"<?php echo ($paramshowpeople == "4" ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBPARAMSHOWPEOPLECHITOT'); ?></option>
									<option value="5"<?php echo ($paramshowpeople == "5" ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBPARAMSHOWPEOPLEALLTOT'); ?></option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="pricecal"><?php echo JText::translate('VBPARAMPRICECALENDAR'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="pricecal" id="pricecal" onchange="togglePriceCalendarParam();">
									<option value="0"><?php echo JText::translate('VBPARAMPRICECALENDARDISABLED'); ?></option>
									<option value="1"<?php echo (count($row) && intval(VikBooking::getRoomParam('pricecal', $row['params'])) == 1 ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBPARAMPRICECALENDARENABLED'); ?></option>
								</select>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMPRICECALENDARHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" id="defcalcostp" style="display: <?php echo (count($row) && intval(VikBooking::getRoomParam('pricecal', $row['params'])) == 1 ? 'flex' : 'none'); ?>;">
						<?php
						/**
						 * The pricing calendar now relies on a default rate plan ID rather than on
						 * a default cost per night in order to have a better accuracy on the result.
						 * 
						 * @since 	1.16.3 (J) - 1.6.3 (WP)
						 */
						$room_rate_plans = $row ? VBORoomHelper::getInstance($row)->getRatePlans() : [];
						if ($row && $room_rate_plans) {
							$def_rplan_id = VikBooking::getRoomParam('defrplan', $row['params']);
							?>
							<div class="vbo-param-label"><label for="defrplan"><?php echo JText::translate('VBOROVWSELRPLAN'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="defrplan" id="defrplan">
								<?php
								foreach ($room_rate_plans as $rplan) {
									?>
									<option value="<?php echo $rplan['id']; ?>"<?php echo $def_rplan_id == $rplan['id'] ? ' selected="selected"' : ''; ?>><?php echo $rplan['name']; ?></option>
									<?php
								}
								?>
								</select>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMDEFCALCOSTHELP'); ?></span>
							</div>
							<?php
						} else {
							// default to the old calendar default (custom) cost
							?>
							<div class="vbo-param-label"><label for="defcalcost"><?php echo JText::translate('VBPARAMDEFCALCOST'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="number" min="0" step="any" name="defcalcost" id="defcalcost" value="<?php echo count($row) ? VikBooking::getRoomParam('defcalcost', $row['params']) : ''; ?>" placeholder="50.00"/>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMDEFCALCOSTHELP'); ?></span>
							</div>
							<?php
						}
						?>
						</div>
						<?php
						$season_cal = count($row) ? VikBooking::getRoomParam('seasoncal', $row['params']) : 0;
						$season_cal_prices = count($row) ? VikBooking::getRoomParam('seasoncal_prices', $row['params']) : 0;
						$season_cal_restr = count($row) ? VikBooking::getRoomParam('seasoncal_restr', $row['params']) : 0;
						?>
						<div class="vbo-param-container param-seasoncal">
							<div class="vbo-param-label"><label for="seasoncal"><?php echo JText::translate('VBPARAMSEASONCALENDAR'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="seasoncal" id="seasoncal" onchange="toggleSeasonalCalendarParam();">
									<option value="0"><?php echo JText::translate('VBPARAMSEASONCALENDARDISABLED'); ?></option>
									<optgroup label="<?php echo JText::translate('VBPARAMSEASONCALENDARENABLED'); ?>">
										<option value="1"<?php echo intval($season_cal) == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMSEASONCALENDARENABLEDALL'); ?></option>
										<option value="2"<?php echo intval($season_cal) == 2 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMSEASONCALENDARENABLEDCHARGEDISC'); ?></option>
										<option value="3"<?php echo intval($season_cal) == 3 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMSEASONCALENDARENABLEDCHARGE'); ?></option>
									</optgroup>
								</select>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested param-seasoncal" style="display: <?php echo (intval($season_cal) > 0 ? 'flex' : 'none'); ?>;">
							<div class="vbo-param-label"><label for="seasoncal_nights"><?php echo JText::translate('VBPARAMSEASONCALNIGHTS'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="text" name="seasoncal_nights" id="seasoncal_nights" size="10" value="<?php echo count($row) ? VikBooking::getRoomParam('seasoncal_nights', $row['params']) : ''; ?>" placeholder="1, 3, 7, 14"/>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBPARAMSEASONCALNIGHTSHELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested param-seasoncal" style="display: <?php echo (intval($season_cal) > 0 ? 'flex' : 'none'); ?>;">
							<div class="vbo-param-label"><label for="seasoncal_prices"><?php echo JText::translate('VBPARAMSEASONCALENDARPRICES'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="seasoncal_prices" id="seasoncal_prices">
									<option value="0"><?php echo JText::translate('VBPARAMSEASONCALENDARPRICESANY'); ?></option>
									<option value="1"<?php echo intval($season_cal_prices) == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMSEASONCALENDARPRICESLOW'); ?></option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested param-seasoncal" style="display: <?php echo (intval($season_cal) > 0 ? 'flex' : 'none'); ?>;">
							<div class="vbo-param-label"><label for="seasoncal_restr"><?php echo JText::translate('VBPARAMSEASONCALENDARLOS'); ?></label></div>
							<div class="vbo-param-setting">
								<select name="seasoncal_restr" id="seasoncal_restr">
									<option value="0"><?php echo JText::translate('VBPARAMSEASONCALENDARLOSHIDE'); ?></option>
									<option value="1"<?php echo intval($season_cal_restr) == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMSEASONCALENDARLOSSHOW'); ?></option>
								</select>
							</div>
						</div>

						<?php
						/**
						 * Maximum advance booking offset can be defined at room-level.
						 * 
						 * @since 	1.16.3 (J) - 1.6.3 (WP)
						 */
						if ($row) {
							// only in edit mode, not when creating a new room
							$room_level_max_adv_notice = VBOFactory::getConfig()->get("room_{$row['id']}_max_adv_notice");
							// build default or current values
							$maxdate_val = 0;
							$maxdate_interval = 'y';
							if (!empty($room_level_max_adv_notice)) {
								$maxdatefuture 	  = $room_level_max_adv_notice;
								$maxdate_val 	  = intval(substr($maxdatefuture, 1, (strlen($maxdatefuture) - 1)));
								$maxdate_interval = substr($maxdatefuture, -1, 1);
							}
							?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_MAX_ADV_BOOK_NOTICE'); ?></div>
							<div class="vbo-param-setting">
								<div>
									<?php
									echo $vbo_app->printYesNoButtons('max_adv_notice_room', JText::translate('VBYES'), JText::translate('VBNO'), (empty($room_level_max_adv_notice) || empty($maxdate_val) ? 0 : 1), 1, 0, 'vboToggleMaxAdvNotice(this.checked);');
									?>
								</div>
								<div class="vbo-room-level-max_adv-notice" style="<?php echo empty($room_level_max_adv_notice) || empty($maxdate_val) ? 'display: none;' : ''; ?>">
									<input type="number" name="maxdate" value="<?php echo $maxdate_val; ?>" min="0"/>
									<select name="maxdateinterval">
										<option value="d"<?php echo $maxdate_interval == 'd' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEDAYS'); ?></option>
										<option value="w"<?php echo $maxdate_interval == 'w' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEWEEKS'); ?></option>
										<option value="m"<?php echo $maxdate_interval == 'm' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEMONTHS'); ?></option>
										<option value="y"<?php echo $maxdate_interval == 'y' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEYEARS'); ?></option>
									</select>
								</div>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_MAX_ADV_BOOK_NOTICE_HELP'); ?></span>
							</div>
						</div>
							<?php
						}
						?>
					</div>
				</div>
			</fieldset>

		</div>

		<div class="vbo-config-maintab-right">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOROOMLEGPHOTODESC'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMSIX'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-param-setting-block">
									<?php echo (count($row) && !empty($row['img']) && file_exists(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$row['img']) ? '<a href="'.VBO_SITE_URI.'resources/uploads/'.$row['img'].'" class="vbomodal vbo-room-img-modal" target="_blank"><i class="' . VikBookingIcons::i('image') . '"></i> ' . $row['img'] . '</a>' : ""); ?>
									<input type="file" name="cimg" size="35"/>
								</div>
								<div class="vbo-param-setting-block">
									<span class="vbo-resize-lb-cont">
										<label style="display: inline;" for="autoresize"><?php echo JText::translate('VBNEWOPTNINE'); ?></label> 
										<input type="checkbox" id="autoresize" name="autoresize" value="1" onclick="showResizeSel();"/> 
									</span>
									<span id="resizesel" style="display: none;"><span><?php echo JText::translate('VBNEWOPTTEN'); ?></span><input type="number" name="resizeto" value="250" min="0" class="vbo-medium-input"/> px</span>
								</div>
							</div>
						</div>
					<?php
					if (count($row)) {
					?>
						<div class="vbo-param-container">
							<div class="vbo-param-label">
								<div class="vbo-param-label-top">
									<span><?php echo JText::translate('VBMOREIMAGES'); ?></span>
									<a href="javascript: void(0);" onclick="addMoreImages();" class="btn vbo-config-btn">
										<?php VikBookingIcons::e('plus-circle'); ?>
										<?php echo JText::translate('VBADDIMAGES'); ?>
									</a>
								</div>
								<div class="vbo-bulkupload-cont">
									<div class="vbo-bulkupload-inner">
										<a href="javascript: void(0);" onclick="showBulkUpload();" class="btn vbo-config-btn">
											<i class="icon-image" style="float: none;"></i><?php echo JText::translate('VBOBULKUPLOAD'); ?>
										</a>
									</div>
								</div>
							</div>
							<div class="vbo-param-setting">
								<div class="vbo-editroom-currentphotos">
									<ul class="vbo-sortable"><?php echo $actmoreimgs; ?></ul>
								</div>
								<div class="vbo-rmphotos-cont">
									<a class="btn btn-danger" href="index.php?option=com_vikbooking&amp;task=removemoreimgs&amp;roomid=<?php echo $row['id']; ?>&amp;imgind=-1" onclick="return confirm('<?php echo addslashes(JText::translate('VBORMALLPHOTOS')); ?>?');">
										<i class="icon-cancel"></i><?php echo JText::translate('VBORMALLPHOTOS'); ?>
									</a>
								</div>
								<div class="vbo-first-imgup">
									<input type="file" name="cimgmore[]" size="35"/> 
									<div class="vbo-imgup-caption">
										<span><?php echo JText::translate('VBIMGCAPTION'); ?></span> 
										<input type="text" name="cimgcaption[]" size="30" value=""/>
									</div>
								</div>
								<div id="myDiv" style="display: block;"></div>
								<div class="vbo-param-setting-block">
									<span class="vbo-resize-lb-cont">
										<label style="display: inline;" for="autoresizemore"><?php echo JText::translate('VBRESIZEIMAGES'); ?></label> 
										<input type="checkbox" id="autoresizemore" name="autoresizemore" value="1" onclick="showResizeSelMore();"/> 
									</span>
									<span id="resizeselmore" style="display: none;"><span><?php echo JText::translate('VBNEWOPTTEN'); ?></span><input type="number" name="resizetomore" value="600" min="0" class="vbo-medium-input"/> px</span>
								</div>
							</div>
						</div>
					<?php
					} else {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label">
								<div class="vbo-param-label-top">
									<span><?php echo JText::translate('VBMOREIMAGES'); ?></span>
									<a class="btn vbo-config-btn" href="javascript: void(0);" onclick="addMoreImages();">
										<?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBADDIMAGES'); ?>
									</a>
								</div>
								<p class="vbo-small-p-info"><?php echo JText::translate('VBOBULKUPLOADAFTERSAVE'); ?></p>
							</div>
							<div class="vbo-param-setting">
								<div class="vbo-first-imgup">
									<input type="file" name="cimgmore[]" size="35"/>
									<div class="vbo-imgup-caption">
										<span><?php echo JText::translate('VBIMGCAPTION'); ?></span>
										<input type="text" name="cimgcaption[]" size="30" value=""/>
									</div>
								</div>
								<div id="myDiv" style="display: block;"></div>
								<div class="vbo-param-setting-block">
									<label style="display: inline;" for="autoresizemore"><?php echo JText::translate('VBRESIZEIMAGES'); ?></label> 
									<input type="checkbox" id="autoresizemore" name="autoresizemore" value="1" onclick="showResizeSelMore();"/> 
									<span id="resizeselmore" style="display: none;"><span><?php echo JText::translate('VBNEWOPTTEN'); ?></span><input type="text" name="resizetomore" value="600" size="3"/> px</span>
								</div>
							</div>
						</div>
						<?php
					}
					?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMSMALLDESC'); ?></div>
							<div class="vbo-param-setting"><textarea name="smalldesc" rows="6" cols="50"><?php echo count($row) ? $row['smalldesc'] : ''; ?></textarea></div>
						</div>
						<div class="vbo-param-container vbo-param-container-full">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMSEVEN'); ?></div>
							<div class="vbo-param-setting">
								<?php
								if (interface_exists('Throwable')) {
									/**
									 * With PHP >= 7 supporting throwable exceptions for Fatal Errors
									 * we try to avoid issues with third party plugins that make use
									 * of the WP native function get_current_screen().
									 * 
									 * @wponly
									 */
									try {
										echo $editor->display( "cdescr", (count($row) ? $row['info'] : ""), '100%', 300, 70, 20 );
									} catch (Throwable $t) {
										echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
									}
								} else {
									// we cannot catch Fatal Errors in PHP 5.x
									echo $editor->display( "cdescr", (count($row) ? $row['info'] : ""), '100%', 300, 70, 20 );
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</fieldset>

		<?php
		if (count($this->rooms_map)) {
		?>
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php VikBookingIcons::e('calendar'); ?> <?php echo JText::translate('VBOROOMCALXREFSETTINGS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOROOMSHARECALENDAR') . '...', 'content' => JText::translate('VBOROOMSHARECALENDARHELP'))); ?> <?php echo JText::translate('VBOROOMSHARECALENDAR'); ?></div>
							<div class="vbo-param-setting">
								<select name="share_with[]" multiple="multiple" size="7" id="share_with_sel">
								<?php
								foreach ($this->rooms_map as $k => $v) {
									?>
									<option value="<?php echo $k; ?>"<?php echo in_array($k, $this->cal_xref['shared_with']) ? ' selected="selected"' : ''; ?>><?php echo $v; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
					<?php
					if (count($this->cal_xref['shared_by'])) {
					?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBOROOMCALENDARSHAREDBY'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-room-calxref-sharedby">
								<?php
								foreach ($this->cal_xref['shared_by'] as $v) {
									if (!isset($this->rooms_map[$v])) {
										continue;
									}
									?>
									<a href="index.php?option=com_vikbooking&task=editroom&cid[]=<?php echo $v; ?>" target="_blank"><?php VikBookingIcons::e('calendar'); ?> <?php echo $this->rooms_map[$v]; ?></a>
									<?php
								}
								?>
								</div>
							</div>
						</div>
					<?php
					}
					?>
					</div>
				</div>
			</fieldset>
		<?php
		}

		/**
		 * Room's Geocoding information.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		echo $this->loadTemplate('geocoding_info');

		/**
		 * Room upgrade relations.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		if (count($this->rooms_map)) {
			$room_upgrade_options = count($row) ? VBOFactory::getConfig()->getArray('room_upgrade_options_' . $row['id'], []) : [];
			$room_upgrade_options['rooms'] = !empty($room_upgrade_options['rooms']) ? $room_upgrade_options['rooms'] : [];
			$room_upgrade_enabled = count($room_upgrade_options['rooms']) ? 1 : 0;
			?>
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php VikBookingIcons::e('gem'); ?> <?php echo JText::translate('VBO_ROOM_UPGRADE'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBO_ROOM_UPGRADE'), 'content' => JText::translate('VBO_ROOM_UPGRADE_HELP'))); ?> <?php echo JText::translate('VBPARAMPRICECALENDARENABLED'); ?></div>
							<div class="vbo-param-setting">
								<?php
								echo $vbo_app->printYesNoButtons('room_upgrade', JText::translate('VBYES'), JText::translate('VBNO'), $room_upgrade_enabled, 1, 0, 'vboToggleRoomUpgrade();');
								?>
							</div>
						</div>
					</div>
					<div class="vbo-params-container vbo-roomupgrade-param" style="<?php echo !$room_upgrade_enabled ? 'display: none;' : ''; ?>">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_ELIGIBLE_ROOMS'); ?></div>
							<div class="vbo-param-setting">
								<select name="upgrade_rooms[]" id="upgrade-rooms" multiple="multiple">
								<?php
								foreach ($this->rooms_map as $rid => $rname) {
									?>
									<option value="<?php echo $rid; ?>"<?php echo in_array($rid, $room_upgrade_options['rooms']) ? ' selected="selected"' : ''; ?>><?php echo $rname; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
					</div>
					<div class="vbo-params-container vbo-roomupgrade-param" style="<?php echo !$room_upgrade_enabled ? 'display: none;' : ''; ?>">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBAPPLYDISCOUNT'); ?></div>
							<div class="vbo-param-setting">
								<div class="input-append">
									<input type="number" name="upgrade_discount" value="<?php echo isset($room_upgrade_options['discount']) ? (float)$room_upgrade_options['discount'] : 0; ?>" min="0" max="100" step="any" />
									<button type="button" class="btn" disabled>%</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
			<?php
		}
		?>

			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDSETTINGS'); ?></legend>
					<div class="vbo-params-container">
						<?php
						$custptitle = count($row) ? VikBooking::getRoomParam('custptitle', $row['params']) : '';
						$custptitlew = count($row) ? VikBooking::getRoomParam('custptitlew', $row['params']) : '';
						$metakeywords = count($row) ? VikBooking::getRoomParam('metakeywords', $row['params']) : '';
						$metadescription = count($row) ? VikBooking::getRoomParam('metadescription', $row['params']) : '';
						if (defined('_JEXEC')) {
							/**
							 * @wponly  removed SEF alias
							 */
							?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="sefalias"><?php echo JText::translate('VBROOMSEFALIAS'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="text" id="sefalias" name="sefalias" value="<?php echo count($row) ? $row['alias'] : ''; ?>" placeholder="double-room-superior"/>
							</div>
						</div>
							<?php
						}
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="custptitle"><?php echo JText::translate('VBPARAMPAGETITLE'); ?></label></div>
							<div class="vbo-param-setting">
								<input type="text" id="custptitle" name="custptitle" value="<?php echo $custptitle; ?>"/> 
							</div>
						</div>
						<div class="vbo-param-container vbo-param-child">
							<div class="vbo-param-label"></div>
							<div class="vbo-param-setting">
								<select name="custptitlew">
									<option value="before"<?php echo $custptitlew == 'before' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMPAGETITLEBEFORECUR'); ?></option>
									<option value="after"<?php echo $custptitlew == 'after' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMPAGETITLEAFTERCUR'); ?></option>
									<option value="replace"<?php echo $custptitlew == 'replace' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMPAGETITLEREPLACECUR'); ?></option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="metakeywords"><?php echo JText::translate('VBPARAMKEYWORDSMETATAG'); ?></label></div>
							<div class="vbo-param-setting">
								<textarea name="metakeywords" id="metakeywords" rows="3" cols="40"><?php echo $metakeywords; ?></textarea>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><label for="metadescription"><?php echo JText::translate('VBPARAMDESCRIPTIONMETATAG'); ?></label></div>
							<div class="vbo-param-setting">
								<textarea name="metadescription" id="metadescription" rows="4" cols="40"><?php echo $metadescription; ?></textarea>
							</div>
						</div>
					</div>
				</div>
			</fieldset>

		</div>
	</div>

	<input type="hidden" name="task" value="">
<?php
if (count($row)) {
	?>
	<input type="hidden" name="whereup" value="<?php echo $row['id']; ?>">
	<input type="hidden" name="actmoreimgs" id="actmoreimgs" value="<?php echo $row['moreimgs']; ?>">
	<?php
}
?>
	<input type="hidden" name="option" value="com_vikbooking">
	<?php echo JHtml::fetch('form.token'); ?>
</form>

<?php
/**
 * Room's Geocoding information - modal.
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
echo $this->loadTemplate('geocoding_modal');
?>

<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content">
		<!-- The fileinput-button span is used to style the file input field as button -->
		<span class="btn vbo-config-btn fileinput-button">
			<?php VikBookingIcons::e('plus-circle'); ?>
			<span><?php echo JText::translate('VBOSELORDRAGFILES'); ?></span>
			<!-- The file input field used as target for the file upload widget -->
			<input id="fileupload" type="file" name="bulkphotos[]" multiple>
		</span>
		<!-- The global progress bar -->
		<div id="progress" class="progress">
			<div class="progress-bar"></div>
		</div>
		<!-- The container for the uploaded files -->
		<div id="files" class="files"></div>
		<div class="vbo-upload-done">
			<button type="button" class="btn btn-success" onclick="vboCloseModal();"><i class="icon-save"></i><?php echo JText::translate('VBOUPLOADFILEDONE'); ?></button>
		</div>
	</div>
</div>

<?php
// the Load Image plugin is included for the preview images and image resizing functionality
$vbo_app->addScript(VBO_ADMIN_URI . 'resources/js_upload/load-image.all.min.js');
// the Iframe Transport is required for browsers without support for XHR file uploads
$vbo_app->addScript(VBO_ADMIN_URI . 'resources/js_upload/jquery.iframe-transport.js');
// the basic File Upload plugin
$vbo_app->addScript(VBO_ADMIN_URI . 'resources/js_upload/jquery.fileupload.js');
// the File Upload processing plugin
$vbo_app->addScript(VBO_ADMIN_URI . 'resources/js_upload/jquery.fileupload-process.js');
// the File Upload image preview & resize plugin
$vbo_app->addScript(VBO_ADMIN_URI . 'resources/js_upload/jquery.fileupload-image.js');
// the File Upload validation plugin
$vbo_app->addScript(VBO_ADMIN_URI . 'resources/js_upload/jquery.fileupload-validate.js');
?>

<script type="text/javascript">
var vbo_overlay_on = false;
function showBulkUpload() {
	jQuery(".vbo-info-overlay-block").fadeIn();
	vbo_overlay_on = true;
}
function vboCloseModal() {
	jQuery(".vbo-info-overlay-block").fadeOut(400, function() {
		jQuery(this).attr("class", "vbo-info-overlay-block");
	});
	vbo_overlay_on = false;
}
jQuery(document).ready(function() {
	togglePriceCalendarParam();
	toggleSeasonalCalendarParam();

	jQuery(document).mouseup(function(e) {
		if (!vbo_overlay_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
			vboCloseModal();
		}
	});
	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27) {
			if (vbo_overlay_on) {
				vboCloseModal();
			}
			if (vbo_details_on) {
				vbo_details_on = false;
				jQuery('.vbimagedetbox').hide();
			}
		}
	});
});
jQuery(function() {
	var url = 'index.php?option=com_vikbooking&task=multiphotosupload&roomid=<?php echo count($row) ? $row['id'] : '0'; ?>',
		uploadButton = jQuery('<button/>')
			.addClass('btn btn-primary')
			.prop('disabled', true)
			.text('Processing...')
			.on('click', function () {
				var $this = jQuery(this),
					data = $this.data();
				$this
					.off('click')
					.text('Abort')
					.on('click', function () {
						$this.remove();
						data.abort();
					});
				data.submit().always(function () {
					$this.remove();
				});
			});
	jQuery('#fileupload').fileupload({
		url: url,
		dataType: 'json',
		autoUpload: true,
		acceptFileTypes: /(\.|\/)(gif|jpe?g|png|webp)$/i,
		maxFileSize: 999000,
		disableImageResize: true,
		previewMaxWidth: 100,
		previewMaxHeight: 100,
		previewCrop: true
	}).on('fileuploadadd', function (e, data) {
		data.context = jQuery('<div/>').addClass('vbo-upload-photo').appendTo('#files');
		jQuery.each(data.files, function (index, file) {
			var node = jQuery('<p/>')
					.append(jQuery('<span/>').text(file.name));
			if (!index) {
				node.append(uploadButton.clone(true).data(data));
			}
			node.appendTo(data.context);
		});
	}).on('fileuploadprocessalways', function (e, data) {
		var index = data.index,
			file = data.files[index],
			node = jQuery(data.context.children()[index]);
		if (file.preview) {
			node.prepend(file.preview);
		}
		if (file.error) {
			node.append(jQuery('<span class="text-danger"/>').text(file.error));
		}
		if (index + 1 === data.files.length) {
			data.context.find('button')
				.text('Upload')
				.prop('disabled', !!data.files.error);
		}
	}).on('fileuploadprogressall', function (e, data) {
		var progress = parseInt(data.loaded / data.total * 100, 10);
		jQuery('#progress .progress-bar').css(
			'width',
			progress + '%'
		);
		if (progress > 99) {
			jQuery('#progress .progress-bar').addClass("progress-bar-success");
		} else {
			if (jQuery('#progress .progress-bar').hasClass("progress-bar-success")){
				jQuery('#progress .progress-bar').removeClass("progress-bar-success");
			} 
		}
	}).on('fileuploaddone', function (e, data) {
		jQuery.each(data.result.files, function (index, file) {
			if (file.url) {
				var link = jQuery('<a>')
					.attr('target', '_blank')
					.attr('class', 'vbomodal')
					.prop('href', file.url);
				jQuery(data.context.children()[index])
					.wrap(link);
				data.context.find('button')
					.hide();
				jQuery('.vbo-upload-done')
					.fadeIn();
			} else if (file.error) {
				var error = jQuery('<span class="text-danger"/>').text(file.error);
				jQuery(data.context.children()[index])
					.append('<br>')
					.append(error);
			} else {
				jQuery(data.context.children()[index])
					.append('<br>')
					.append('Generic Error.');
			}
		});
		if (data.result.hasOwnProperty('actmoreimgs')) {
			jQuery('#actmoreimgs').val(data.result.actmoreimgs);
		}
		if (data.result.hasOwnProperty('currentthumbs')) {
			jQuery('.vbo-editroom-currentphotos').html(data.result.currentthumbs);
		}
		if (typeof reloadFancybox === 'function') {
			reloadFancybox();
		}
	}).on('fileuploadfail', function (e, data) {
		jQuery.each(data.files, function (index) {
			var error = jQuery('<span class="text-danger"/>').text('File upload failed.');
			jQuery(data.context.children()[index])
				.append('<br>')
				.append(error);
		});
	}).prop('disabled', !jQuery.support.fileInput)
		.parent().addClass(jQuery.support.fileInput ? undefined : 'disabled');
});
</script>
