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
 * Class handler for conditional rule "stay dates".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleStayDates extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_CONDTEXT_RULE_STAYDATES');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_STAYDATES_DESCR');
		$this->ruleId = basename(__FILE__);
	}

	/**
	 * Displays the rule parameters.
	 * 
	 * @return 	void
	 */
	public function renderParams()
	{
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDFROMRANGE'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->getCalendar($this->getParam('from_date', ''), $this->inputName('from_date'), $this->inputID('from_date'), $this->wdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDTORANGE'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->getCalendar($this->getParam('to_date', ''), $this->inputName('to_date'), $this->inputID('to_date'), $this->wdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true')); ?>
			</div>
		</div>
		<?php
		if ($this->getParams() !== null) {
			// some date-picker calendars may need to have their default value populated when the document is ready
			?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('from_date'); ?>').val('<?php echo $this->getParam('from_date', ''); ?>').attr('data-alt-value', '<?php echo $this->getParam('from_date', ''); ?>');
				jQuery('#<?php echo $this->inputID('to_date'); ?>').val('<?php echo $this->getParam('to_date', ''); ?>').attr('data-alt-value', '<?php echo $this->getParam('to_date', ''); ?>');
			});
		</script>
			<?php
		}
	}

	/**
	 * Tells whether the rule is compliant.
	 * 
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function isCompliant()
	{
		$checkin = $this->getPropVal('booking', 'checkin');
		$checkout = $this->getPropVal('booking', 'checkout');

		if (!$checkin || !$checkout) {
			return false;
		}

		$from_date = $this->getParam('from_date', '');
		$from_ts = VikBooking::getDateTimestamp($from_date, 0, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($this->getParam('to_date', $from_date), 23, 59, 59);

		// return true if check-in or check-out dates are inside the dates interval
		return (
			$checkin >= $from_ts && 
			$checkin <= $to_ts
		) || (
			$checkout >= $from_ts && 
			$checkout <= $to_ts
		);
	}

}
