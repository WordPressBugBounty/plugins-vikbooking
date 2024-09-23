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
 * Class handler for conditional rule "channels".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleChannels extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBOCHANNELS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_CHANNELS_DESCR');
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
		$channels = $this->loadChannels();
		$current_channels = $this->getParam('channels', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOCHANNELS'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('channels', true); ?>" id="<?php echo $this->inputID('channels'); ?>" multiple="multiple">
				<?php
				foreach ($channels as $chkey => $chval) {
					?>
					<option value="<?php echo $chkey; ?>"<?php echo is_array($current_channels) && in_array($chkey, $current_channels) ? ' selected="selected"' : ''; ?>><?php echo $chval; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('channels'); ?>').select2();
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
		$book_channel = $this->getPropVal('booking', 'channel');
		$allowed_channels = $this->getParam('channels', array());
		$sales_customer = (strpos($book_channel, 'customer') === 0 && strpos($book_channel, '_') !== false);

		if (empty($book_channel) || $sales_customer) {
			if (in_array('website', $allowed_channels)) {
				// website (or sales customer) booking allowed
				return true;
			}
			// useless to proceed
			return false;
		}

		$full_source = $book_channel;
		if (strpos($book_channel, '_') !== false) {
			$parts = explode('_', $book_channel);
			$full_source = $parts[0];
		}

		return in_array(strtolower($full_source), $allowed_channels);
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadChannels()
	{
		$channels = array(
			'website' => JText::translate('VBORDFROMSITE')
		);

		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			return $channels;
		}

		$dbo = JFactory::getDbo();

		try {
			$q = "SELECT `name` FROM `#__vikchannelmanager_channel` ORDER BY `name` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$records = $dbo->loadAssocList();
				foreach ($records as $ch) {
					$channels[$ch['name']] = ucwords($ch['name']);
				}
			}
		} catch (Exception $e) {
			// do nothing
		}

		return $channels;
	}

}
