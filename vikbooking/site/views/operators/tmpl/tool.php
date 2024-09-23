<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

$input = JFactory::getApplication()->input;

// access the global operators object
$oper_obj = VikBooking::getOperatorInstance();

// get tool data
$tool_data = $oper_obj->getToolData($this->tool);

if (!$tool_data) {
	// tool is unknown
	VBOHttpDocument::getInstance()->close(404, sprintf('Operator tool not found (%s)', $this->tool));
}

if (!$oper_obj->checkPermissions($this->operator, $this->tool)) {
	// no permission to access this tool
	VBOHttpDocument::getInstance()->close(403, sprintf('Not enough permissions to access the tool(%s)', $this->tool));
}

/**
 * Wrap operator tool permissions into a registry.
 * NOTICE: checkPermissions() called above will modify
 * by reference the "perms" key on the operator array record.
 */
$permissions = new JObject(($this->operator['perms'] ?: []));

// build URI to current tool
$tool_uri = JRoute::rewrite(
	sprintf(
		'index.php?option=com_vikbooking&view=operators&tool=%s&Itemid=%d',
		$this->tool,
		JFactory::getApplication()->input->getInt('Itemid', 0)
	)
);

?>
<div class="vbo-operator-tool-container">
	<div class="vbo-operator-tool-breadcrumbs">
		<span class="vbo-operator-tool-breadcrumb vbo-operator-tool-breadcrumb-home">
			<a class="vbo-pref-color-text" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=operators&Itemid=' . $input->getInt('Itemid', 0)); ?>">
				<?php VikBookingIcons::e('th-list'); ?>
				<span class="vbo-operator-tool-breadcrumb-name"><?php echo JText::translate('VBMENUDASHBOARD'); ?></span>
				<span class="vbo-operator-tool-breadcrumb-separator"><?php VikBookingIcons::e('chevron-right'); ?></span>
			</a>
		</span>
		<!-- use .vbo-operator-tool-breadcrumb-step-current to define a previous breadcrump-step, not the home, not the current -->
		<span class="vbo-operator-tool-breadcrumb vbo-operator-tool-breadcrumb-step vbo-operator-tool-breadcrumb-step-current">
			<span class="vbo-operator-tool-breadcrumb-name"><?php echo $oper_obj->getToolName($this->tool); ?></span>
		</span>
	</div>
	<div class="vbo-operator-tool-wrap">
<?php
// determine how to render the requested tool
if (is_callable(($tool_data['rendering_callback'] ?? null))) {
	// render the tool through the callback ("layout", unless a custom tool defined a custom rendering callback)
	$tool_data['rendering_callback']($this->tool, $this->operator, $permissions, $tool_uri);
} else {
	// render the tool by dispatching the dedicated event/hook
	VBOFactory::getPlatform()->getDispatcher()->trigger('onRenderOperatorTool' . ucfirst($this->tool), [$this->tool, $this->operator, $permissions, $tool_uri]);
}
?>
	</div>
</div>
