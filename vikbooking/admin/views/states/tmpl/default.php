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

$app = JFactory::getApplication();
$pidcountry = $app->getUserStateFromRequest("vbo.states.idcountry", 'idcountry', 0, 'int');
$pstatename = $app->getUserStateFromRequest("vbo.states.statename", 'statename', '', 'string');

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();

?>
<div class="vbo-list-form-filters vbo-btn-toolbar">
	<form action="index.php?option=com_vikbooking&amp;view=states" method="post" name="statesform">
		<div style="width: 100%; display: inline-block;" class="btn-toolbar" id="filter-bar">
			<div class="btn-group pull-left">
				<select name="idcountry" id="idcountry" onchange="document.statesform.submit();">
					<option value=""><?php echo JText::translate('VBNEWCUSTOMFCOUNTRY'); ?></option>
				<?php
				foreach (VikBooking::getCountriesArray($tn = true, $no_id = false) as $country) {
					?>
					<option value="<?php echo $country['id']; ?>"<?php echo $country['id'] == $pidcountry ? ' selected="selected"' : ''; ?>><?php echo $country['country_name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
			<div class="btn-group pull-left input-append">
				<input type="text" name="statename" id="statename" value="<?php echo JHtml::fetch('esc_attr', $pstatename); ?>" size="40" placeholder="<?php echo JText::translate('VBPVIEWROOMONE'); ?>"/>
				<button type="button" class="btn btn-secondary" onclick="document.statesform.submit();"><i class="icon-search"></i></button>
			</div>
			<div class="btn-group pull-left">
				<button type="button" class="btn btn-secondary" onclick="document.getElementById('statename').value='';document.getElementById('idcountry').value='';document.statesform.submit();"><?php echo JText::translate('JSEARCH_FILTER_CLEAR'); ?></button>
			</div>
		</div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>
</div>

<script type="text/javascript">
jQuery(function() {
	jQuery('#idcountry').select2();
});
</script>

<?php
if (empty($this->rows)) {
	?>
	<p class="warn"><?php echo JText::translate('JGLOBAL_NO_MATCHING_RESULTS'); ?></p>
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
					<th class="title left" width="150">
						<a href="index.php?option=com_vikbooking&amp;view=states&amp;vborderby=state_name&amp;vbordersort=<?php echo ($this->orderby == "state_name" && $this->ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($this->orderby == "state_name" && $this->ordersort == "ASC" ? "vbo-list-activesort" : ($this->orderby == "state_name" ? "vbo-list-activesort" : "")); ?>">
						<?php echo JText::translate('VBO_STATE_PROVINCE') . ($this->orderby == "state_name" && $this->ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($this->orderby == "state_name" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
						</a>
					</th>
					<th class="title left" width="150"><?php echo JText::translate( 'VBNEWCUSTOMFCOUNTRY' ); ?></th>
					<th class="title center" width="100" align="center">
						<a href="index.php?option=com_vikbooking&amp;view=states&amp;vborderby=state_3_code&amp;vbordersort=<?php echo ($this->orderby == "state_3_code" && $this->ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($this->orderby == "state_3_code" && $this->ordersort == "ASC" ? "vbo-list-activesort" : ($this->orderby == "state_3_code" ? "vbo-list-activesort" : "")); ?>">
							<?php echo JText::translate('VBO_3CHAR_CODE').($this->orderby == "state_3_code" && $this->ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($this->orderby == "state_3_code" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
						</a>
					</th>
					<th class="title center" width="100" align="center">
						<a href="index.php?option=com_vikbooking&amp;view=states&amp;vborderby=state_2_code&amp;vbordersort=<?php echo ($this->orderby == "state_2_code" && $this->ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($this->orderby == "state_2_code" && $this->ordersort == "ASC" ? "vbo-list-activesort" : ($this->orderby == "state_2_code" ? "vbo-list-activesort" : "")); ?>">
							<?php echo JText::translate('VBO_2CHAR_CODE').($this->orderby == "state_2_code" && $this->ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($this->orderby == "state_2_code" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
						</a>
					</th>
					<th class="title center" width="50" align="center">
						<a href="index.php?option=com_vikbooking&amp;view=states&amp;vborderby=published&amp;vbordersort=<?php echo ($this->orderby == "published" && $this->ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($this->orderby == "published" && $this->ordersort == "ASC" ? "vbo-list-activesort" : ($this->orderby == "published" ? "vbo-list-activesort" : "")); ?>">
							<?php echo JText::translate('VBPSHOWPAYMENTSFIVE').($this->orderby == "published" && $this->ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($this->orderby == "published" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
						</a>
					</th>
				</tr>
			</thead>
		<?php
		$k = 0;
		$i = 0;
		for ($i = 0, $n = count($this->rows); $i < $n; $i++) {
			$row = $this->rows[$i];
			?>
			<tr class="row<?php echo $k; ?>">
				<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
				<td class="vbo-highlighted-td"><a href="index.php?option=com_vikbooking&amp;task=states.edit&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['state_name']; ?></a></td>
				<td><?php echo $row['country_name']; ?></td>
				<td class="center"><?php echo $row['state_3_code']; ?></td>
				<td class="center"><?php echo $row['state_2_code']; ?></td>
				<td class="center"><?php echo ($row['published'] == 1 ? '<i class="'.VikBookingIcons::i('check', 'vbo-icn-img').'" style="color: #099909;"></i>' : '<i class="'.VikBookingIcons::i('times-circle', 'vbo-icn-img').'" style="color: #ff0000;"></i>'); ?></td>
			</tr>
			<?php
			$k = 1 - $k;
		}
		?>
		</table>
	</div>

	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="view" value="states" />
	<input type="hidden" name="idcountry" value="<?php echo $pidcountry; ?>" />

	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $this->navbut; ?>
</form>
<?php
}
?>
