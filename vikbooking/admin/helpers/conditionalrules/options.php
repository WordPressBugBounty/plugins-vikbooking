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
 * Class handler for conditional rule "options".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleOptions extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBOREPORTOPTIONSEXTRAS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_OPTS_DESCR');
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
		$options = $this->loadOptions();
		$current_options = $this->getParam('options', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOREPORTOPTIONSEXTRAS'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('options', true); ?>" id="<?php echo $this->inputID('options'); ?>" multiple="multiple">
				<?php
				foreach ($options as $odata) {
					?>
					<option value="<?php echo $odata['id']; ?>"<?php echo is_array($current_options) && in_array($odata['id'], $current_options) ? ' selected="selected"' : ''; ?>><?php echo $odata['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('options'); ?>').select2();
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
		$opt_booked = $this->getProperty('rooms', array());
		if (!is_array($opt_booked) || !count($opt_booked)) {
			return false;
		}

		$all_opt_ids = array();
		foreach ($opt_booked as $book) {
			if (empty($book['optionals'])) {
				continue;
			}
			$parts = explode(';', $book['optionals']);
			foreach ($parts as $optvals) {
				if (empty($optvals)) {
					continue;
				}
				$parts_two = explode(':', $optvals);
				array_push($all_opt_ids, (int)$parts_two[0]);
			}
		}

		if (!count($all_opt_ids)) {
			return false;
		}

		$allowed_options = $this->getParam('options', array());

		$one_found = false;
		foreach ($all_opt_ids as $idopt) {
			if (in_array($idopt, $allowed_options)) {
				$one_found = true;
				break;
			}
		}

		// return true if at least one option booked is in the parameters
		return $one_found;
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadOptions()
	{
		$options = array();

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`, `name` FROM `#__vikbooking_optionals` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$options = $dbo->loadAssocList();
		}

		return $options;
	}

}
