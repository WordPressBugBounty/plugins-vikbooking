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
 * Class handler for conditional rule "days to arrival".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleDaysToArrival extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_CONDTEXT_RULE_DTA');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_DTA_DESCR');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEDAYS'); ?></div>
			<div class="vbo-param-setting">
				<input type="number" name="<?php echo $this->inputName('days'); ?>" value="<?php echo $this->getParam('days', ''); ?>" min="0" />
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
		$book_ts = $this->getPropVal('booking', 'ts', 0);
		$book_in = $this->getPropVal('booking', 'checkin', 0);

		$dta = (int)$this->getParam('days', 0);

		$from_dt = new DateTime(date('Y-m-d H:i:s', $book_ts));
		$to_dt = new DateTime(date('Y-m-d H:i:s', $book_in));

		$dates_diff = $from_dt->diff($to_dt);
		if (!$dates_diff || $dates_diff->days === false) {
			return false;
		}

		return ($dta <= $dates_diff->days);

	}

}
