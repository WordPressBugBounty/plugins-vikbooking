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

/**
 * Class handler for conditional rule "week days".
 * 
 * @since 	1.4.3
 */
class VikBookingConditionalRuleWeekDays extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBSEASONDAYS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_WDAYS_DESCR');
		$this->ruleId = basename(__FILE__);
	}

	/**
	 * Displays the rule parameters.
	 * 
	 * @return 	void
	 */
	public function renderParams()
	{
		$this->vbo_app->loadSelect2();
		$wdays = array(
			JText::translate('VBSUNDAY'),
			JText::translate('VBMONDAY'),
			JText::translate('VBTUESDAY'),
			JText::translate('VBWEDNESDAY'),
			JText::translate('VBTHURSDAY'),
			JText::translate('VBFRIDAY'),
			JText::translate('VBSATURDAY'),
		);
		$current_wdays = $this->getParam('wdays', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBWEEKDAYS'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('wdays', true); ?>" id="<?php echo $this->inputID('wdays'); ?>" multiple="multiple">
				<?php
				foreach ($wdays as $wdk => $wdv) {
					?>
					<option value="<?php echo $wdk; ?>"<?php echo is_array($current_wdays) && in_array($wdk, $current_wdays) ? ' selected="selected"' : ''; ?>><?php echo $wdv; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBPVIEWCUSTOMFTWO'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('type'); ?>" id="<?php echo $this->inputID('type'); ?>">
					<option value=""></option>
					<option value="checkin"<?php echo $this->getParam('type', '') == 'checkin' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPICKUPAT'); ?></option>
					<option value="checkout"<?php echo $this->getParam('type', '') == 'checkout' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBRELEASEAT'); ?></option>
					<option value="both"<?php echo $this->getParam('type', '') == 'both' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPICKUPAT') . ' | ' . JText::translate('VBRELEASEAT'); ?></option>
				</select>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('wdays'); ?>').select2();
			});
		</script>
		<?php
	}

	/**
	 * Tells whether the rule is compliant.
	 * 
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function isCompliant()
	{
		$book_in  = $this->getPropVal('booking', 'checkin', 0);
		$book_out = $this->getPropVal('booking', 'checkout', 0);

		if (empty($book_in) || empty($book_out)) {
			return false;
		}

		$info_in  = getdate($book_in);
		$info_out = getdate($book_out);

		$involved_wdays = $this->getParam('wdays', array());
		$involved_type  = $this->getParam('type', 'checkin');

		if ($involved_type == 'checkin') {
			return (in_array($info_in['wday'], $involved_wdays));
		}

		if ($involved_type == 'checkout') {
			return (in_array($info_out['wday'], $involved_wdays));
		}

		return (in_array($info_in['wday'], $involved_wdays) || in_array($info_out['wday'], $involved_wdays));
	}

}
