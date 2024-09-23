<?php

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$ignores = array('administrator');

?>

<form action="admin.php" method="post" name="adminForm" id="adminForm">

	<?php if (wp_doing_ajax()) { ?>
		<div class="btn-toolbar">
			<div class="btn-group pull-left">
				<button type="submit" class="page-title-action page-title-action" onclick="document.adminForm.task.value='acl.save';">
					<?php echo JText::translate('JTOOLBAR_APPLY'); ?>
				</button>
			</div>
		</div>
	<?php } ?>

	<div class="btn-toolbar">
		<div class="btn-group pull-left">
			<select name="activerole" id="role-select">
				<?php foreach ($this->roles as $role => $name) { 
					$selected = $role == $this->activeRole ? 'selected="selected"' : '';
					?>
					<option value="<?php echo $role; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
				<?php } ?>
			</select>
		</div>
	</div>

	<?php
	foreach ($this->roles as $role => $name)
	{
		?>
		<div class="acl-role-container" id="role-<?php echo $role; ?>" style="<?php echo ($this->activeRole == $role ? '' : 'display: none;'); ?>">
			<h2><?php echo $name; ?></h2>

			<table class="wp-list-table widefat fixed striped">

			<thead>
				<tr>
					<th width="50%"><?php echo JText::translate('JACTION'); ?></th>
					<th width="15%" style="text-align: center;"><?php echo JText::translate('JNEW_SETTING'); ?></th>
					<th width="15%" style="text-align: center;"><?php echo JText::translate('JCURRENT_SETTING'); ?></th>
				</tr>
			</thead>
			<?php

			foreach ($this->actions as $action)
			{
				$has = JAccess::checkGroup($role, $action->name, 'com_vikbooking');
				$cap = JAccess::adjustCapability($action->name, 'com_vikbooking');

				?>
				<tr>

					<td>
						<b><?php echo $action->title; ?></b><br /><?php echo $action->description; ?>
					</td>

					<td style="text-align: center;">
						<select name="acl[<?php echo $role; ?>][<?php echo $cap; ?>]" <?php echo (in_array($role, $ignores) ? 'disabled="disabled"' : ''); ?>>
							<option value="-1">--</option>
							<option value="1"><?php echo JText::translate('JALLOWED'); ?></option>
							<option value="0"><?php echo JText::translate('JDENIED'); ?></option>
						</select>
					</td>

					<td style="text-align: center">
						<span class="acl-rule-<?php echo ($has ? 'allowed' : 'denied'); ?>">
							<?php echo JText::translate($has ? 'JALLOWED' : 'JDENIED'); ?>
						</span>
					</td>

				</tr>
				<?php
			}

			?>
			</table>
		</div>
	<?php } ?>

	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="return" value="<?php echo $this->returnLink; ?>" />

</form>

<script>

	jQuery(document).ready(function() {

		jQuery('#role-select').on('change', function() {
			jQuery('.acl-role-container').hide();
			jQuery('#role-' + jQuery(this).val()).show();
		});

	});

</script>
