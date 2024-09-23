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
 * Class handler for conditional rule "payment status".
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingConditionalRulePaymentStatus extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_HISTORY_GPM');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_BOOKSTAT_DESCR');
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
		$statuses = $this->getStatuses();
		$current_status = $this->getParam('statuses', '');
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBSTATUS'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('statuses'); ?>" id="<?php echo $this->inputID('statuses'); ?>">
					<option value=""></option>
				<?php
				foreach ($statuses as $ks => $vs) {
					?>
					<option value="<?php echo $ks; ?>"<?php echo $current_status == $ks ? ' selected="selected"' : ''; ?>><?php echo $vs; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(function() {
				jQuery('#<?php echo $this->inputID('statuses'); ?>').select2();
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
		$book_status  = $this->getPropVal('booking', 'status', '');
		$book_total   = (float)$this->getPropVal('booking', 'total', 0);
		$book_totpaid = (float)$this->getPropVal('booking', 'totpaid', 0);

		$pay_status = $this->getParam('statuses', '');

		if (empty($pay_status) || empty($book_total)) {
			return false;
		}

		switch ($pay_status) {
			case 'fully_paid':
				return $book_status === 'confirmed' && $book_totpaid >= $book_total;

			case 'partially_paid':
				return $book_status === 'confirmed' && $book_totpaid > 0 && $book_totpaid < $book_total;

			case 'payment_received':
				return $book_status === 'confirmed' && $book_totpaid > 0;

			case 'to_be_paid':
				return $book_status === 'confirmed' && empty($book_totpaid);

			default:
				return false;
		}
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function getStatuses()
	{
		return [
			'fully_paid' 	   => JText::translate('VBOCOLORTAGRULECONFFIVE'),
			'partially_paid'   => JText::translate('VBOCOLORTAGRULECONFFOUR'),
			'payment_received' => JText::translate('VBOBOOKHISTORYTP0'),
			'to_be_paid' 	   => JText::translate('VBOCOLORTAGRULECONFTHREE'),
		];
	}
}
