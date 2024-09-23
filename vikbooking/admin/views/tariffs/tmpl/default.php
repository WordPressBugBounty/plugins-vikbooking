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

$roomrows = $this->roomrows;
$rows = $this->rows;
$prices = $this->prices;
$allc = $this->allc;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();

// access room helper to detect LOS rates
$room_helper = VBORoomHelper::getInstance();

$currencysymb = VikBooking::getCurrencySymb(true);
$idroom = $roomrows['id'];
$name = $roomrows['name'];
if (is_file(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$roomrows['img']) && getimagesize(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$roomrows['img'])) {
	$img = '<img align="middle" class="maxninety" alt="Room Image" src="' . VBO_SITE_URI . 'resources/uploads/'.$roomrows['img'].'" />';
} else {
	$img = '<i class="' . VikBookingIcons::i('image', 'vbo-enormous-icn') . '"></i>';
}

/**
 * We add the names of the categories next to the name of the room for better identification.
 * 
 * @since 	1.13.5
 */
if (!empty($roomrows['idcat'])) {
	$parts = explode(';', rtrim($roomrows['idcat'], ';'));
	$cat_names = array();
	foreach ($parts as $cat_id) {
		if (empty($cat_id)) {
			continue;
		}
		$cat_name = VikBooking::getCategoryName($cat_id);
		if (empty($cat_name)) {
			continue;
		}
		array_push($cat_names, $cat_name);
	}
	$name = count($cat_names) ? $name . ' (' . implode(', ', $cat_names) . ')' : $name;
}

if (!empty($prices)) {
?>
<div class="vbo-admin-wizard-container">
	<div class="vbo-admin-wizard-inner">
	<?php
	if (!empty($this->rows)) {
		?>
		<a class="btn vbo-wizard-btn" href="index.php?option=com_vikbooking"><?php VikBookingIcons::e('home'); ?> <?php echo JText::translate('VBMENUDASHBOARD'); ?></a>
		<?php
	}
	?>
		<a class="btn vbo-wizard-btn" href="javascript: void(0);" onclick="showVboWizard();"><?php VikBookingIcons::e('magic'); ?> <?php echo JText::translate('VBOTOGGLEWIZARD'); ?></a>
	</div>
</div>
<?php
}
?>

<div class="vbo-admin-container">
	<div class="vbo-config-maintab-left">
		<fieldset class="adminform">
			<div class="vbo-params-wrap">
				<legend class="adminlegend">
					<div class="vbo-quickres-head">
						<span><?php echo $name . " - " . JText::translate('VBINSERTFEE'); ?></span>
						<div class="vbo-quickres-head-right">
							<form name="vbchroom" method="post" action="index.php?option=com_vikbooking">
								<input type="hidden" name="task" value="tariffs"/>
								<select name="cid[]" id="roomsel" onchange="javascript: document.vbchroom.submit();">
								<?php
								foreach ($allc as $cc) {
									?>
									<option value="<?php echo $cc['id']; ?>"<?php echo $cc['id'] == $idroom ? ' selected="selected"' : ''; ?>><?php echo $cc['name']; ?></option>
									<?php
								}
								?>
								</select>
							</form>
						</div>
					</div>
				</legend>
				<div class="vbo-params-container vbo-tariffs-params-container">
					<div class="vbo-param-container">
						<div class="vbo-param-label">
							<div class="vbo-center">
								<?php echo $img; ?>
							</div>
						</div>
						<div class="vbo-param-setting">
							<h4><?php echo JText::translate('VBDAILYFARES'); ?></h4>
						<?php
						if (empty($prices)) {
							?>
							<p class="err">
								<span><?php echo JText::translate('VBMSGONE'); ?></span>
								<a href="index.php?option=com_vikbooking&task=newprice"><?php echo JText::translate('VBHERE'); ?></a>
							</p>
							<?php
						}
						?>
							<form name="newd" method="post" action="index.php?option=com_vikbooking" onsubmit="javascript: if (!document.newd.ddaysfrom.value.match(/\S/)){alert('<?php echo addslashes(JText::translate('VBMSGTWO')); ?>'); return false;} else {return true;}">
								<div class="vbo-insertrates-cont">
									<div class="vbo-insertrates-top">
										<div class="vbo-ratestable-lbl"><?php echo JText::translate('VBDAYS'); ?></div>
										<div class="vbo-ratestable-nights">
											<div class="vbo-ratestable-night-from">
												<span><?php echo JText::translate('VBDAYSFROM'); ?></span>
												<input type="number" name="ddaysfrom" id="ddaysfrom" value="<?php echo !is_array($prices) ? '1' : ''; ?>" min="1" />
											</div>
											<div class="vbo-ratestable-night-to">
												<span><?php echo JText::translate('VBDAYSTO'); ?></span>
												<input type="number" name="ddaysto" id="ddaysto" value="<?php echo !is_array($prices) ? '30' : ''; ?>" min="1" max="999" />
											</div>
										</div>
									</div>
									<div class="vbo-insertrates-bottom">
										<div class="vbo-ratestable-lbl"><?php echo JText::translate('VBDAILYPRICES'); ?></div>
										<div class="vbo-ratestable-newprices">
									<?php
									if (is_array($prices)) {
										foreach ($prices as $pr) {
											?>
											<div class="vbo-ratestable-newprice">
												<span class="vbo-ratestable-newprice-name"><?php echo $pr['name']; ?></span>
												<span class="vbo-ratestable-newprice-cost">
													<span class="vbo-ratestable-newprice-cost-currency"><?php echo $currencysymb; ?></span>
													<span class="vbo-ratestable-newprice-cost-amount">
														<input type="number" min="0" step="any" name="dprice<?php echo $pr['id']; ?>" value=""/>
													</span>
												</span>
											<?php
											if (!empty($pr['attr'])) {
												?>
												<div class="vbo-ratestable-newprice-attribute">
													<span class="vbo-ratestable-newprice-name"><?php echo $pr['attr']; ?></span>
													<span class="vbo-ratestable-newprice-cost">
														<input type="text" name="dattr<?php echo $pr['id']; ?>" value="" size="10"/>
													</span>
												</div>
												<?php
											}
											?>
											</div>
											<?php
										}
									}
									?>
										</div>
									</div>
								</div>
								<div class="vbo-insertrates-save">
									<input type="submit" class="btn vbo-config-btn" name="newdispcost" value="<?php echo JText::translate('VBINSERT'); ?>"/>
									<input type="hidden" name="cid[]" value="<?php echo $idroom; ?>"/>
									<input type="hidden" name="task" value="tariffs"/>
								</div>
							</form>

						</div>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<div class="vbo-config-maintab-right">
		<fieldset class="adminform">
			<div class="vbo-params-wrap">
				<div class="vbo-params-container vbo-list-table-container">
				<?php
				if (empty($rows)) {
					?>
					<p class="warn"><?php echo JText::translate('VBNOTARFOUND'); ?></p>
					<form name="adminForm" id="adminForm" action="index.php" method="post">
						<input type="hidden" name="task" value="">
						<input type="hidden" name="option" value="com_vikbooking">
					</form>
					<?php
				} else {
					$mainframe = JFactory::getApplication();
					$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', 15, 'int');
					$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
					$allpr = array();
					$tottar = array();
					foreach ($rows as $r) {
						if (!array_key_exists($r['idprice'], $allpr)) {
							$allpr[$r['idprice']] = VikBooking::getPriceAttr($r['idprice']);
						}
						$tottar[$r['days']][] = $r;
					}
					$prord = array();
					$prvar = '';
					foreach ($allpr as $kap => $ap) {
						$prord[] = $kap;

						// detect if this rate plan has got LOS rates
						$los_descr = '';
						$rplan_haslos = $room_helper->hasLosRecords($idroom, $kap, true);
						if ($rplan_haslos) {
							$los_descr .= "\n<div class=\"vbo-tariffs-los-info\">\n";
							$los_descr .= "<span class=\"badge badge-info vbo-tariffs-los-badge\">LOS &gt;= $rplan_haslos " . JText::translate('VBDAYS') . "</span>\n";
							$los_descr .= "</div>\n";
						}

						$prvar .= "<th class=\"title center\" width=\"150\"><span class=\"vbo-tariffs-rplan-name\">" . VikBooking::getPriceName($kap) . "</span>" . (!empty($ap) ? '<span class="vbo-tariffs-rplan-attr" title="' . $this->escape($ap) . '">' . $ap . '</span>' : '') . $los_descr . "</th>\n";
					}
					$totrows = count($tottar);
					$tottar = array_slice($tottar, $lim0, $lim, true);
					?>
					<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm" class="vbo-list-form">
						<div class="vbo-tariffs-updaterates-cont">
							<input type="submit" name="modtar" value="<?php echo JText::translate( 'VBPVIEWTARTWO' ); ?>" onclick="vbRateSetTask(event);" class="btn vbo-config-btn" />
						</div>
						<div class="table-responsive">
							<table cellpadding="4" cellspacing="0" border="0" width="100%" class="table table-striped vbo-list-table">
								<thead>
								<tr>
									<th width="20" class="title left">
										<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle">
									</th>
									<th class="title left" width="100" style="text-align: left;"><?php echo JText::translate( 'VBPVIEWTARONE' ); ?></th>
									<?php echo $prvar; ?>
								</tr>
								</thead>
							<?php
							$k = 0;
							$i = 0;
							foreach ($tottar as $kt => $vt) {
								$multiid = "";
								foreach ($prord as $ord) {
									foreach ($vt as $kkkt => $vvv) {
										if ($vvv['idprice'] == $ord) {
											$multiid .= $vvv['id'].";";
											break;
										}
									}
								}
								?>
								<tr class="row<?php echo $k; ?>">
									<td class="left">
										<input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $multiid; ?>" onclick="Joomla.isChecked(this.checked);">
									</td>
									<td class="left"><?php echo $kt; ?></td>
								<?php
								foreach ($prord as $ord) {
									$thereis = false;
									foreach ($vt as $kkkt => $vvv) {
										if ($vvv['idprice'] == $ord) {
											echo "<td class=\"center\"><input type=\"number\" min=\"0\" step=\"any\" name=\"cost".$vvv['id']."\" value=\"".$vvv['cost']."\" />".(!empty($vvv['attrdata'])? " - <input type=\"text\" name=\"attr".$vvv['id']."\" value=\"".$vvv['attrdata']."\" size=\"10\"/>" : "")."</td>\n";
											$thereis = true;
											break;
										}
									}
									if (!$thereis) {
										echo "<td></td>\n";
									}
									unset($thereis);
								}
								?>
								</tr>
								<?php
								unset($multiid);
								$k = 1 - $k;
								$i++;
							}
							?>
							</table>
						</div>
						<input type="hidden" name="roomid" value="<?php echo $roomrows['id']; ?>" />
						<input type="hidden" name="cid[]" value="<?php echo $roomrows['id']; ?>" />
						<input type="hidden" name="option" value="com_vikbooking" />
						<input type="hidden" name="task" id="vbtask" value="tariffs" />
						<input type="hidden" name="tarmod" id="vbtarmod" value="" />
						<input type="hidden" name="boxchecked" value="0" />
						<?php echo JHtml::fetch('form.token'); ?>
						<?php
						jimport('joomla.html.pagination');
						$pageNav = new JPagination( $totrows, $lim0, $lim );
						$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
						echo $navbut;
						?>
					</form>
					<?php
					}
					?>
				</div>
			</div>
		</fieldset>
	</div>
</div>

<?php
if (!empty($prices)) {
	// load wizard template
	echo $this->loadTemplate('wizard');
}
?>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('#ddaysfrom').change(function() {
		var fnights = parseInt(jQuery(this).val());
		if (!isNaN(fnights)) {
			jQuery('#ddaysto').attr('min', fnights);
			var tnights = jQuery('#ddaysto').val();
			if (!(tnights.length > 0)) {
				jQuery('#ddaysto').val(fnights);
			} else {
				if (parseInt(tnights) < fnights) {
					jQuery('#ddaysto').val(fnights);
				}
			}
		}
	});
	jQuery("#roomsel").select2();
});
function vbRateSetTask(event) {
	event.preventDefault();
	document.getElementById('vbtarmod').value = '1';
	document.getElementById('vbtask').value = 'rooms';
	document.adminForm.submit();
}
</script>
