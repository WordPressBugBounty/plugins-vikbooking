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
 * Class handler for conditional rule "booking status".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleBookingStatus extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBCALBOOKINGSTATUS');
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
		$current_statuses = $this->getParam('statuses', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBSTATUS'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('statuses', true); ?>" id="<?php echo $this->inputID('statuses'); ?>" multiple="multiple">
				<?php
				foreach ($statuses as $ks => $vs) {
					?>
					<option value="<?php echo $ks; ?>"<?php echo is_array($current_statuses) && in_array($ks, $current_statuses) ? ' selected="selected"' : ''; ?>><?php echo $vs; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function() {
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
		$book_status = $this->getPropVal('booking', 'status', '');

		$allowed_statuses = $this->getParam('statuses', array());

		return (in_array($book_status, $allowed_statuses));
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function getStatuses()
	{
		return array(
			'confirmed' => JText::translate('VBCONFIRMED'),
			'standby' => JText::translate('VBSTANDBY'),
			'cancelled' => JText::translate('VBCANCELLED'),
		);
	}

}
