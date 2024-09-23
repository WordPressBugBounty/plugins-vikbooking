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

if (empty($rows)) {
	?>
<div class="vbo-admin-wizard-container">
	<div class="vbo-admin-wizard-inner">
		<a class="btn vbo-wizard-btn" href="javascript: void(0);" onclick="showVboWizard();"><?php VikBookingIcons::e('magic'); ?> <?php echo JText::translate('VBOTOGGLEWIZARD'); ?></a>
	</div>
</div>	
<p class="warn"><?php echo JText::translate('VBNOPRICESFOUND'); ?></p>
<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_vikbooking" />
</form>
	<?php
	// load wizard template
	echo $this->loadTemplate('wizard');
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
			<th class="title left" width="200"><?php echo JText::translate( 'VBPVIEWPRICESONE' ); ?></th>
			<th class="title left" width="100"><?php echo JText::translate( 'VBPVIEWPRICESTWO' ); ?></th>
			<th class="title center" width="100"><?php echo JText::translate( 'VBPVIEWPRICESTHREE' ); ?></th>
			<th class="title left" width="150"><?php echo JText::translate( 'VBOPRICETYPESRESTR' ); ?></th>
			<th class="title center" width="100"><?php echo JText::translate( 'VBNEWPRICEBREAKFAST' ); ?></th>
			<th class="title center" width="100"><?php echo JText::translate( 'VBNEWPRICEFREECANC' ); ?></th>
		</tr>
		</thead>
	<?php
	$k = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		$restr = array();
		if ($row['minlos'] > 1) {
			$restr[] = JText::translate('VBOPRICETYPEMINLOS').': '.$row['minlos'];
		}
		if ($row['minhadv'] > 0) {
			$restr[] = JText::translate('VBOPRICETYPEMINHADV').': '.$row['minhadv'];
		}
		$aliq = VikBooking::getAliq($row['idiva']);
		?>
		<tr class="row<?php echo $k; ?>">
			<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
			<td class="vbo-highlighted-td"><a href="index.php?option=com_vikbooking&amp;task=editprice&cid[]=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
			<td><?php echo $row['attr']; ?></td>
			<td class="center"><?php echo !empty($aliq) ? $aliq.'%' : '----'; ?></td>
			<td><?php echo implode(', ', $restr); ?></td>
			<td class="center"><?php echo (intval($row['breakfast_included'])==1 ? "<i class=\"".VikBookingIcons::i('check', 'vbo-icn-img')."\" style=\"color: #099909;\"></i>" : "<i class=\"".VikBookingIcons::i('times-circle', 'vbo-icn-img')."\" style=\"color: #ff0000;\"></i>"); ?></td>
			<td class="center"><?php echo (intval($row['free_cancellation'])==1 ? "<i class=\"".VikBookingIcons::i('check', 'vbo-icn-img')."\" style=\"color: #099909;\" title=\"".JText::sprintf('VBNEWPRICEFREECANCDLINETIP', $row['canc_deadline'])."\"></i>" : "<i class=\"".VikBookingIcons::i('times-circle', 'vbo-icn-img')."\" style=\"color: #ff0000;\"></i>"); ?></td>
		</tr>
		  <?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="prices" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $navbut; ?>
</form>
<?php
}