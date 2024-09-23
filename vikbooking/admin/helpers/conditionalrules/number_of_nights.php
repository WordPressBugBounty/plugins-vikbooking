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
 * Class handler for conditional rule "number of nights".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleNumberOfNights extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBOINVTOTNIGHTS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_NON_DESCR');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBDAYSFROM'); ?></div>
			<div class="vbo-param-setting">
				<input type="number" name="<?php echo $this->inputName('from_nights'); ?>" value="<?php echo $this->getParam('from_nights', ''); ?>" min="1" />
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBDAYSTO'); ?></div>
			<div class="vbo-param-setting">
				<input type="number" name="<?php echo $this->inputName('to_nights'); ?>" value="<?php echo $this->getParam('to_nights', ''); ?>" min="1" />
			</div>
		</div>
		<?php
	}

	/**
	 * Tells whether the rule is compliant.
	 * 
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function isCompliant()
	{
		$num_nights = (int)$this->getPropVal('booking', 'days', 0);

		$from_nights = (int)$this->getParam('from_nights', 1);
		$to_nights = (int)$this->getParam('to_nights', $from_nights);

		// return true if number of nights is inside the range of nights
		return $num_nights >= $from_nights && $num_nights <= $to_nights;
	}

}
