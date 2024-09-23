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
 * Class handler for conditional rule "room upgrade".
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingConditionalRuleRoomUpgrade extends VikBookingConditionalRule
{
	/**
	 * @var  array
	 */
	protected $upgrade_options = [];

	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBO_ROOM_UPGRADE');
		$this->ruleDescr = JText::translate('VBO_ROOM_UPGRADE_CONDTEXT_H');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBO_ROOM_UPGRADE'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('can_upgrade'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('can_upgrade', 0), 1, 0); ?>
				<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_ROOM_UPGRADE_CONDTEXT_H'); ?></span>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBO_ROOM_UPGRADE_ADDCONTMSG'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('add_details'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('add_details', 0), 1, 0); ?>
				<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_CONDTEXT_MSG') . ' - ' . JText::translate('VBO_CONDTEXT_TKN') . ': '; ?><span onclick="vboRoomUpgradeAddContentEditor('{room_upgrade}');" style="cursor: pointer;">{room_upgrade}</span></span>
			</div>
		</div>

		<script type="text/javascript">
			function vboRoomUpgradeAddContentEditor(str) {
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
		$can_upgrade = (bool)$this->getParam('can_upgrade', 0);
		if (!$can_upgrade) {
			return true;
		}

		$booking = $this->getProperty('booking', []);
		if (!is_array($booking) || !count($booking)) {
			return false;
		}

		$rooms_booked = $this->getProperty('rooms', []);
		if (!is_array($rooms_booked) || !count($rooms_booked)) {
			return false;
		}

		// booking status must be confirmed
		if ($this->getPropVal('booking', 'status', '') != 'confirmed') {
			return false;
		}

		// translator object
		$vbo_tn = VikBooking::getTranslator();

		// look for available upgrade options
		$this->upgrade_options = VBORoomHelper::getInstance([
			'booking' => $booking,
			'rooms'   => $rooms_booked,
		], true)->getUpgradeOptions($vbo_tn);

		return ($this->upgrade_options && $this->upgrade_options['upgrade']);
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
		$add_details = (bool)$this->getParam('add_details', 0);
		if (!$add_details || !$this->upgrade_options || !$this->upgrade_options['upgrade']) {
			return $msg;
		}

		// build HTML content
		$upgrade_html_details = $this->buildHtmlUpgradeDetails();

		if (strpos($msg, '{room_upgrade}') !== false) {
			// exact placeholder tag found
			return str_replace('{room_upgrade}', $upgrade_html_details, $msg);
		}

		// append upgrade details content to message
		return $msg . $upgrade_html_details;
	}

	/**
	 * Builds the necessary HTML content with the upgrade details
	 * to be added to the conditional text message.
	 * 
	 * @return 	string
	 */
	protected function buildHtmlUpgradeDetails()
	{
		if (!$this->upgrade_options || !$this->upgrade_options['upgrade']) {
			return '';
		}

		// access booking information
		$booking 	  = $this->getProperty('booking', []);
		$rooms_booked = $this->getProperty('rooms', []);

		// build booking link
		$use_sid = !empty($booking['idorderota']) && !empty($booking['channel']) ? $booking['idorderota'] : $booking['sid'];
		$booking_link = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid={$use_sid}&ts={$booking['ts']}&room_upgrade=1", false);

		// get preferences
		$currencysymb = VikBooking::getCurrencySymb();
		$pref_colors = VikBooking::getPreferredColors();

		// default colors for button-links
		$btnlink_bgc = '#3d89d1';
		$btnlink_col = '#ffffff';
		if (!empty($pref_colors['bgcolor']) && !empty($pref_colors['fontcolor'])) {
			$btnlink_bgc = $pref_colors['bgcolor'];
			$btnlink_col = $pref_colors['fontcolor'];
		}

		// start output buffering
		ob_start();

		foreach ($this->upgrade_options['upgrade'] as $upgk => $upg_data) {
			?>
		<div style="margin: 4px 0; padding: 6px;">
			<?php
			foreach ($upg_data['r_costs'] as $rid => $upgrade_sol) {
				$upg_room_name = $this->upgrade_options['rooms'][$rid]['name'];
				?>
			<div style="display: inline-block; margin: 4px; padding: 4px; border: 1px solid #ddd; border-radius: 6px; vertical-align: top; width: 100%; box-sizing: border-box;">
				<div style="display: inline-block;width: 30%;float: left;">
				<?php
				if (!empty($this->upgrade_options['rooms'][$rid]['img'])) {
					?>
					<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $this->upgrade_options['rooms'][$rid]['img']; ?>" alt="<?php echo htmlspecialchars($upg_room_name); ?>" title="<?php echo htmlspecialchars($upg_room_name); ?>" style="border-radius: 4px; max-width: 100%; display: block;" />
					<?php
				}
				?>
				</div>
				<div style="font-weight: normal; display: inline-block; float: left; width: 68%; margin-left: 2%; padding: 4px 0;">
					<div style="font-weight: bold;"><?php echo $upg_room_name; ?></div>
				<?php
				if (!empty($this->upgrade_options['rooms'][$rid]['smalldesc'])) {
					?>
					<div style="margin: 3px 0 5px 0; font-size: 12px;"><?php echo $this->shortenDescr($this->upgrade_options['rooms'][$rid]['smalldesc']); ?></div>
					<?php
				}
				?>
					<div style="margin-top: 5px;">
						<span style="font-weight: bold;"><?php echo $currencysymb . ' ' . VikBooking::numberFormat($upgrade_sol['upgrade_cost']); ?></span><?php
					if (isset($upg_data['discount']) && $upg_data['discount'] > 0) {
						?><span style="margin-left: 10px; color: #52aa20;"><?php echo JText::sprintf('VBO_YOU_SAVE_PCENT', $upg_data['discount'] . '%'); ?></span><?php
					}
					?>
					</div><?php
				// do not touch empty PHP lines in order to avoid empty <p></p> tags with WordPress
				?></div>
				<div style="margin: 8px 0 0 2%;float: left;">
					<a href="<?php echo $booking_link; ?>" target="_blank" style="padding: 4px 6px; border: 1px solid #ddd; border-radius: 4px; background-color: <?php echo $btnlink_bgc; ?>; color: <?php echo $btnlink_col; ?>; text-decoration: none; font-weight:bold; display: inline-block;"><?php echo JText::translate('VBO_UPGRADE_CONFIRM'); ?></a>
				</div>
			</div>
				<?php
			}
			?>
		</div>
			<?php
		}

		// get the HTML buffer and return it
		$html_content = ob_get_contents();
		ob_end_clean();

		return $html_content;
	}

	/**
	 * Shortens a description string.
	 * 
	 * @param 	string 	$descr 	the original description string.
	 * @param 	int 	$chars 	the limit of chars to apply.
	 * 
	 * @return 	string 			the shorten description string.
	 */
	protected function shortenDescr($descr, $chars = 150)
	{
		if (function_exists('mb_strlen')) {
			// safe multi-byte environment
			if (mb_strlen($descr) > $chars) {
				// we are forced to strip any HTML tags when using a sub-string
				$descr = strip_tags($descr);
				if (mb_strlen($descr) > $chars) {
					// still exceeding the limit
					$descr = mb_substr($descr, 0, $chars, 'UTF-8') . '...';
				}
			}

			return $descr;
		}

		if (strlen($descr) > $chars) {
			// we are forced to strip any HTML tags when using a sub-string
			$descr = strip_tags($descr);
			if (strlen($descr) > $chars) {
				// still exceeding the limit
				$descr = substr($descr, 0, $chars) . '...';
			}
		}

		return $descr;
	}
}
