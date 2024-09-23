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
 * Class handler for conditional rule "extra email".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleExtraEmail extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_CONDTEXT_RULE_EXTRAMAIL');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_EXTRAMAIL_DESCR');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBCUSTOMEREMAIL'); ?></div>
			<div class="vbo-param-setting">
				<input type="text" name="<?php echo $this->inputName('extra_email'); ?>" value="<?php echo $this->getParam('extra_email', ''); ?>" />
				<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_CONDTEXT_RULE_SEPEMAIL'); ?></span>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBO_CONDTEXT_RULE_BCCEMAIL'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('bcc'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('bcc', 0), 1, 0); ?>
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
		// this is not a real filter-rule, so we always return true
		return true;
	}

	/**
	 * Override callback action method to set the additional email addresses.
	 * 
	 * @return 	void
	 */
	public function callbackAction()
	{
		$extra_recipients = $this->getParam('extra_email', '');
		if (empty($extra_recipients)) {
			return;
		}
		
		if (strpos($extra_recipients, ',') !== false) {
			$extra_recipients = explode(',', $extra_recipients);
		} elseif (strpos($extra_recipients, ';') !== false) {
			$extra_recipients = explode(';', $extra_recipients);
		} else {
			$extra_recipients = array($extra_recipients);
		}
		
		// register additional email recipients
		VikBooking::addAdminEmailRecipient($extra_recipients, (bool)$this->getParam('bcc', 0));

		return;
	}

}
