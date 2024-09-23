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
 * Class handler for conditional rule "split stay".
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingConditionalRuleSplitStay extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_SPLIT_STAYS');
		$this->ruleDescr = JText::translate('VBO_BOOK_SPLIT_STAYS');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBO_SPLIT_STAY'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('is_split_stay'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('is_split_stay', 0), 1, 0); ?>
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
		$is_split_stay = (bool)$this->getParam('is_split_stay', 0);
		if (!$is_split_stay) {
			return true;
		}

		return (bool)$this->getPropVal('booking', 'split_stay', 0);
	}
}
