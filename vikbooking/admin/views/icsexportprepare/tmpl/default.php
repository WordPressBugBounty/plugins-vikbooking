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

JHtml::fetch('behavior.tooltip');

$vbo_app = VikBooking::getVboApplication();

$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
?>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBICSEXPORT'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBCSVEXPFILTDATES'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->getCalendar('', 'checkindate', 'checkindate', $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDTORANGE'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->getCalendar('', 'checkoutdate', 'checkoutdate', $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBCSVEXPFILTBSTATUS'); ?></div>
							<div class="vbo-param-setting">
								<select name="status">
									<option value="">----------</option>
									<option value="confirmed"><?php echo JText::translate('VBCSVSTATUSCONFIRMED'); ?></option>
									<option value="standby"><?php echo JText::translate('VBCSVSTATUSSTANDBY'); ?></option>
									<option value="cancelled"><?php echo JText::translate('VBCSVSTATUSCANCELLED'); ?></option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-setting">
								<button type="button" class="btn" name="csvsubmit" onclick="document.getElementById('adminForm').submit();"><i class="vboicn-cloud-download"></i> <?php echo JText::translate('VBICSGENERATE'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value="icsexportlaunch" />
	<input type="hidden" name="option" value="com_vikbooking" />
</form>
