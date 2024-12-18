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
 * Class handler for conditional rule "request payment".
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
class VikBookingConditionalRuleRequestPayment extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_AMOUNT_PAYABLE_RQ');
		$this->ruleDescr = sprintf('%s (%s)', JText::translate('VBO_AMOUNT_PAYABLE_RQ'), JText::translate('VBO_AMOUNT_PAYABLE'));
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
			<div class="vbo-param-label"><?php echo JText::translate('VBO_AMOUNT_PAYABLE_RQ'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('has_amount_payable'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('has_amount_payable', 0), 1, 0); ?>
				<span class="vbo-param-setting-comment">Use the tag <strong onclick="vboCtrRequestPaymentAddContentEditor('{payment_requested_amount}');" style="cursor: pointer;">{payment_requested_amount}</strong> if you would like to display the actual <i>amount payable</i> in your text.</span>
			</div>
		</div>

		<script type="text/javascript">
			function vboCtrRequestPaymentAddContentEditor(str) {
				if (!str) {
					return;
				}

				try {
					// "msg" is the name of the WYSIWYG editor of the conditional text
					Joomla.editors.instances.msg.replaceSelection(str);
				} catch(e) {
					// do nothing
				}
			}
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
		$has_amount_payable = (bool)$this->getParam('has_amount_payable', 0);
		if (!$has_amount_payable) {
			return true;
		}

		return $this->getPropVal('booking', 'payable', 0) > 0;
	}

	/**
	 * Allows to manipulate the message of the conditional text with dynamic contents.
	 * 
	 * @override
	 * 
	 * @param 	string 	$msg 	the current conditional text message.
	 * 
	 * @return 	string 			the manipulated conditional text message.
	 */
	public function manipulateMessage($msg)
	{
		// check if the special and internal tag was used
		if (strpos($msg, '{payment_requested_amount}') !== false) {
			// build the amount payable string
			$amout_payable_str = sprintf('%s %s', VikBooking::getCurrencySymb(), VikBooking::numberFormat($this->getPropVal('booking', 'payable', 0)));

			// exact placeholder tag found, so use the plain mark string
			$msg = str_replace('{payment_requested_amount}', $amout_payable_str, $msg);
		}

		// return the eventually manipulated message
		return $msg;
	}
}
