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
 * Class handler for conditional rule "returning customer".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleReturningCustomer extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_CONDTEXT_RULE_RETCUST');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_RETCUST_DESCR');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBO_CONDTEXT_RULE_RETCUST'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('returning'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('returning', 0), 1, 0); ?>
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
		$returning = (bool)$this->getParam('returning', 0);
		if (!$returning) {
			return true;
		}

		$book_id = $this->getPropVal('booking', 'id', '');
		if (empty($book_id)) {
			return true;
		}

		$cpin = VikBooking::getCPinIstance();
		$customer = $cpin->getCustomerFromBooking($book_id);
		if (!is_array($customer) || !count($customer)) {
			// customer not found
			return false;
		}

		$dbo = JFactory::getDbo();
		$q = "SELECT `co`.`idcustomer`, `co`.`idorder`, `o`.`id` FROM `#__vikbooking_customers_orders` AS `co` LEFT JOIN `#__vikbooking_orders` AS `o` ON `co`.`idorder`=`o`.`id` WHERE `co`.`idcustomer`=" . $customer['id'] . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();

		// we need at least two confirmed orders
		return ($dbo->getNumRows() > 1);
	}

}
