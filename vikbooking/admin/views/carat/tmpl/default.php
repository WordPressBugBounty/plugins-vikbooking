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
	<p class="warn"><?php echo JText::translate('VBNOCARATFOUND'); ?></p>
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
			<th class="title left" width="150"><?php echo JText::translate( 'VBPVIEWCARATONE' ); ?></th>
			<th class="title left" width="150"><?php echo JText::translate( 'VBPVIEWCARATTWO' ); ?></th>
			<th class="title left" width="250"><?php echo JText::translate( 'VBPVIEWCARATTHREE' ); ?></th>
			<th class="title center" align="center" width="100"><?php echo JText::translate( 'VBPVIEWOPTIONALSORDERING' ); ?></th>
		</tr>
		</thead>
	<?php
	$k = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		?>
		<tr class="row<?php echo $k; ?>">
			<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
			<td class="vbo-highlighted-td"><a href="index.php?option=com_vikbooking&amp;task=editcarat&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
			<td>
			<?php 
				echo (is_file(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$row['icon'] && !empty($row['icon'])) ? "<span>".$row['icon']." &nbsp;&nbsp;<img align=\"middle\" src=\"".VBO_SITE_URI."resources/uploads/".$row['icon']."\"/></span>" : $row['icon']); 
			?>
			</td>
			<td><?php echo $row['textimg']; ?></td>
			<td class="center">
				<a href="<?php echo VBOFactory::getPlatform()->getUri()->addCSRF('index.php?option=com_vikbooking&task=sortcarat&cid[]=' . $row['id'] . '&mode=up', true); ?>"><?php VikBookingIcons::e('arrow-up', 'vbo-icn-img'); ?></a> 
				<a href="<?php echo VBOFactory::getPlatform()->getUri()->addCSRF('index.php?option=com_vikbooking&task=sortcarat&cid[]=' . $row['id'] . '&mode=down', true); ?>"><?php VikBookingIcons::e('arrow-down', 'vbo-icn-img'); ?></a>
			</td>
		</tr>	
		  <?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="carat" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $navbut; ?>
</form>
<?php
}
