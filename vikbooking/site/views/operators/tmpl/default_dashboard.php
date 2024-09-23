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

// access the global operators object
$oper_obj = VikBooking::getOperatorInstance();

$pitemid = VikRequest::getInt('Itemid', 0, 'request');

?>
<div class="vbo-operator-dashboard">
	<h3>
	<?php
	if (!empty($this->operator['pic'])) {
		?>
		<span class="vbo-operator-pic">
			<img src="<?php echo strpos($this->operator['pic'], 'http') === 0 ? $this->operator['pic'] : VBO_SITE_URI . 'resources/uploads/' . $this->operator['pic']; ?>" />
		</span>
		<?php
	}
	?>
		<span class="vbo-operator-name"><?php echo $this->operator['first_name'] . ' ' . $this->operator['last_name']; ?></span>
	</h3>
	<div class="vbo-operator-dashboard-logout">
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=operatorlogout&Itemid=' . $pitemid); ?>" method="post">
			<input type="submit" name="logout" value="<?php echo JText::translate('VBOLOGOUT'); ?>" class="vbo-logout vbo-pref-color-btn-secondary" />
			<input type="hidden" name="option" value="com_vikbooking" />
			<input type="hidden" name="task" value="operatorlogout" />
			<?php
			if (!empty($pitemid)) {
				?>
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
				<?php
			}
			?>
		</form>
	</div>
	<div class="vbo-operator-dashboard-links">
		<ul>
		<?php
		foreach ($this->operator['perms'] as $perm) {
			// build tool link
			$tool_data = $oper_obj->getToolData($perm['type']);

			if (!$tool_data) {
				// tool is unknown
				continue;
			}

			// build tool link
			if (!strcasecmp(($tool_data['rendering_type'] ?? ''), 'view')) {
				// tool is an existing view
				$tool_link = JRoute::rewrite('index.php?option=com_vikbooking&view=' . $perm['type'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''));
			} else {
				// tool is either native (layout) or custom
				$tool_link = JRoute::rewrite('index.php?option=com_vikbooking&view=operators&tool=' . $perm['type'] . (!empty($pitemid) ? '&Itemid=' . $pitemid : ''));
			}
			?>
			<li>
				<div class="vbo-operator-dashboard-link-left">
					<a href="<?php echo $tool_link; ?>"><?php echo $oper_obj->getToolName($perm['type']); ?></a>
				</div>
				<div class="vbo-operator-dashboard-link-right">
					<a class="btn vbo-pref-color-btn" href="<?php echo $tool_link; ?>"><?php echo JText::translate('VBOOPERVIEWPG'); ?></a>
				</div>
			</li>
			<?php
		}
		?>
		</ul>
	</div>
</div>
