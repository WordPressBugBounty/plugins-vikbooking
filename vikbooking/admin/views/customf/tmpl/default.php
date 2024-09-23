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

$rows = $this->rows;
$lim0 = $this->lim0;
$navbut = $this->navbut;

$canEdit = JFactory::getUser()->authorise('core.edit', 'com_vikbooking');

if (empty($rows)) {
	?>
	<p class="warn"><?php echo JText::translate('VBNOFIELDSFOUND'); ?></p>
	<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_vikbooking" />
	</form>
	<?php
} else {	
	?>
<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm" class="vbo-list-form">
<div class="table-responsive">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="table table-striped vbo-list-table">
		<thead>
		<tr>
			<th width="20">
				<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle">
			</th>
			<th class="title left" width="200"><?php echo JText::translate( 'VBPVIEWCUSTOMFONE' ); ?></th>
			<th class="title left" width="200"><?php echo JText::translate( 'VBPVIEWCUSTOMFTWO' ); ?></th>
			<th class="title center" width="100" align="center"><?php echo JText::translate( 'VBPVIEWCUSTOMFFOUR' ); ?></th>
			<th class="title center" width="100" align="center"><?php echo JText::translate( 'VBPVIEWCUSTOMFTHREE' ); ?></th>
		</tr>
		</thead>
	<?php
	$field_type_map = [
		'text' 		=> JText::translate('VBNEWCUSTOMFTHREE'),
		'textarea' 	=> JText::translate('VBNEWCUSTOMFTEN'),
		'select' 	=> JText::translate('VBNEWCUSTOMFFOUR'),
		'checkbox' 	=> JText::translate('VBNEWCUSTOMFFIVE'),
		'date' 		=> JText::translate('VBNEWCUSTOMFDATETYPE'),
		'country' 	=> JText::translate('VBNEWCUSTOMFCOUNTRY'),
		'state' 	=> JText::translate('VBO_STATE_PROVINCE'),
		'separator' => JText::translate('VBNEWCUSTOMFSEPARATOR'),
	];

	$k = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		$field_badge = '';
		if ($row['isnominative'] == 1) {
			$field_badge = ' <span class="badge">'.JText::translate('VBISNOMINATIVE').'</span>';
		}
		if ($row['isphone'] == 1) {
			$field_badge = ' <span class="badge">'.JText::translate('VBISPHONENUMBER').'</span>';
		}
		if (!empty($row['flag']) && in_array($row['flag'], array('address', 'city', 'zip', 'company', 'vat'))) {
			$field_badge = ' <span class="badge">'.JText::translate('VBIS'.strtoupper($row['flag'])).'</span>';
		}
		if (!empty($row['flag']) && $row['flag'] == 'fisccode') {
			$field_badge = ' <span class="badge">'.JText::translate('VBCUSTOMERFISCCODE').'</span>';
		}
		if (!empty($row['flag']) && $row['flag'] == 'pec') {
			$field_badge = ' <span class="badge">'.JText::translate('VBCUSTOMERPEC').'</span>';
		}
		if (!empty($row['flag']) && $row['flag'] == 'recipcode') {
			$field_badge = ' <span class="badge">'.JText::translate('VBCUSTOMERRECIPCODE').'</span>';
		}
		if ($row['isemail'] > 0) {
			$field_badge = ' <span class="badge">'.JText::translate('VBPVIEWCUSTOMFFIVE').'</span>';
		}
		if ($row['type'] == 'state') {
			$field_badge = ' <a href="index.php?option=com_vikbooking&view=states" class="label label-info"><i class="' . VikBookingIcons::i('edit') . '"></i> '.JText::translate('VBO_MANAGE').'</a>';
		}
		?>
		<tr class="row<?php echo $k; ?>">
			<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
			<td class="vbo-highlighted-td">
				<div class="name">
					<?php if ($canEdit): ?>
						<a href="index.php?option=com_vikbooking&amp;task=editcustomf&amp;cid[]=<?php echo $row['id']; ?>"><?php echo JText::translate($row['name']); ?></a>
					<?php else: ?>
						<?php echo JText::translate($row['name']); ?>
					<?php endif; ?>
				</div>
			</td>
			<td><?php echo (isset($field_type_map[$row['type']]) ? $field_type_map[$row['type']] : ucwords($row['type'])) . $field_badge; ?></td>
			<td class="center">
				<a href="<?php echo VBOFactory::getPlatform()->getUri()->addCSRF('index.php?option=com_vikbooking&task=sortfield&cid[]=' . $row['id'] . '&mode=up', true); ?>"><?php VikBookingIcons::e('arrow-up', 'vbo-icn-img'); ?></a> 
				<a href="<?php echo VBOFactory::getPlatform()->getUri()->addCSRF('index.php?option=com_vikbooking&task=sortfield&cid[]=' . $row['id'] . '&mode=down', true); ?>"><?php VikBookingIcons::e('arrow-down', 'vbo-icn-img'); ?></a>
			</td>
			<td class="center"><?php echo intval($row['required']) == 1 ? "<i class=\"".VikBookingIcons::i('check', 'vbo-icn-img')."\" style=\"color: #099909;\"></i>" : "<i class=\"".VikBookingIcons::i('times-circle', 'vbo-icn-img')."\" style=\"color: #ff0000;\"></i>"; ?></td>
		</tr>	
		<?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="customf" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $navbut; ?>
</form>
<?php
}
