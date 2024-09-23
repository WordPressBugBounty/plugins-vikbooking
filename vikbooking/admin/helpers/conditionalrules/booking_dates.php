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
 * Class handler for conditional rule "booking dates".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleBookingDates extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_CONDTEXT_RULE_BOOKDATES');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_BOOKDATES_DESCR');
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
		$book_time = $this->getPropVal('booking', 'ts');

		if (!$book_time) {
			return false;
		}

		$from_date = $this->getParam('from_date', '');

		// return true if booking date is inside the dates interval
		return (
			$book_time >= VikBooking::getDateTimestamp($from_date, 0, 0, 0) && 
			$book_time <= VikBooking::getDateTimestamp($this->getParam('to_date', $from_date), 23, 59, 59)
		);
	}

}
