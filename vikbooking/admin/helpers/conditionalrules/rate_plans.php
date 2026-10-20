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
 * Class handler for conditional rule "rate plans".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleRatePlans extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBMENURATEPLANS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_RPL_DESCR');
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
		$rplans = $this->loadRatePlans();
		$otarplans = $this->loadOTARatePlans();
		$current_rplans = $this->getParam('rplans', []);
		$cur_ota_rplans = $this->getParam('otarplans', []);

		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOSPTYPESPRICE'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('rplans', true); ?>" id="<?php echo $this->inputID('rplans'); ?>" multiple="multiple">
				<?php
				foreach ($rplans as $rdata) {
					?>
					<option value="<?php echo $rdata['id']; ?>"<?php echo is_array($current_rplans) && in_array($rdata['id'], $current_rplans) ? ' selected="selected"' : ''; ?>><?php echo $rdata['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>

		<?php
		if ($otarplans) {
			// this is an associative array with key=channel_name, value=channel_rate_plans
			?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBMENUCHANNELMANAGER'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('otarplans', true); ?>" id="<?php echo $this->inputID('otarplans'); ?>" multiple="multiple">
				<?php
				foreach ($otarplans as $ch_name => $ch_rplans) {
					?>
					<optgroup label="<?php echo addslashes($ch_name); ?>">
					<?php
					foreach ($ch_rplans as $ch_rplan) {
						?>
						<option value="<?php echo $ch_rplan; ?>"<?php echo is_array($cur_ota_rplans) && in_array($ch_rplan, $cur_ota_rplans) ? ' selected="selected"' : ''; ?>><?php echo $ch_rplan; ?></option>
						<?php
					}
					?>
					</optgroup>
					<?php
				}
				?>
				</select>
			</div>
		</div>
			<?php
		}
		?>
		
		<script type="text/javascript">
			jQuery(function() {
				jQuery('#<?php echo $this->inputID('rplans'); ?>').select2();
				jQuery('#<?php echo $this->inputID('otarplans'); ?>').select2();
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
		$rplans_booked = $this->getProperty('rooms', []);
		if (!is_array($rplans_booked) || !$rplans_booked) {
			return false;
		}

		// get filters
		$allowed_rplans = $this->getParam('rplans', []);
		$allowed_ota_rplans = $this->getParam('otarplans', []);

		$all_tariff_ids = [];
		$all_rateplan_ids = [];
		$ota_rplans_available = false;
		foreach ($rplans_booked as $rplan_book) {
			if (array_key_exists('otarplan', $rplan_book)) {
				// when accessing the "rooms" property, only a few columns may be available
				$ota_rplans_available = true;
			}
			$ratePlanId = (int) ($rplan_book['cust_cpolicy_id'] ?? null);
			if ($ratePlanId && !in_array($ratePlanId, $all_rateplan_ids)) {
				$all_rateplan_ids[] = $ratePlanId;
			}
			$tariffId = (int) ($rplan_book['idtar'] ?? null);
			if (!$tariffId || in_array($tariffId, $all_tariff_ids)) {
				continue;
			}
			array_push($all_tariff_ids, $tariffId);
		}

		if (!$all_rateplan_ids && !$all_tariff_ids && !$allowed_ota_rplans) {
			// useless to proceed
			return false;
		}

		// whether we have found a match
		$one_found = false;

		// get all rate plan IDs from tariffs
		$dbo = JFactory::getDbo();

		if ($all_tariff_ids) {
			$q = "SELECT `idprice` FROM `#__vikbooking_dispcost` WHERE `id` IN (" . implode(', ', $all_tariff_ids) . ")";
			$dbo->setQuery($q);
			$records = $dbo->loadAssocList();
			foreach ($records as $record) {
				array_push($all_rateplan_ids, $record['idprice']);
			}
		}

		// check if website rate plans are matching
		foreach ($all_rateplan_ids as $idprice) {
			if (in_array($idprice, $allowed_rplans)) {
				$one_found = true;
				break;
			}
		}

		if (!$one_found && $allowed_ota_rplans) {
			// check if the OTA rate plan matches with the ones allowed
			if (!$ota_rplans_available) {
				// load full reservation rooms data, including ota rate plans
				$full_rooms_data = VikBooking::loadOrdersRoomsData($this->getPropVal('booking', 'id'));
			} else {
				// it looks like the property accessed has got enough information
				$full_rooms_data = $rplans_booked;
			}
			foreach ($full_rooms_data as $rdata) {
				if (empty($rdata['otarplan'])) {
					continue;
				}
				foreach ($allowed_ota_rplans as $ota_rplan) {
					if (stripos($ota_rplan, $rdata['otarplan']) !== false) {
						$one_found = true;
						break 2;
					}
				}
			}
		}

		// return true if at least one rate plan booked is in the parameters
		return $one_found;
	}

	/**
	 * Internal method for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadRatePlans()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`, `name` FROM `#__vikbooking_prices` ORDER BY `name` ASC;";
		$dbo->setQuery($q);

		return $dbo->loadAssocList();
	}

	/**
	 * Internal method for this rule only.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function loadOTARatePlans()
	{
		$otarplans = [];

		if (!$this->isChannelManagerAvailable()) {
			return $otarplans;
		}

		$dbo = JFactory::getDbo();

		try {
			$q = "SELECT `channel`,`otapricing` FROM `#__vikchannelmanager_roomsxref` ORDER BY `channel` ASC;";
			$dbo->setQuery($q);
			$records = $dbo->loadAssocList();
			foreach ($records as $ch) {
				// make sure the channel name is readable
				$ch['channel'] = $ch['channel'] == 'airbnbapi' ? 'airbnb' : $ch['channel'];
				$ch_name = ucfirst($ch['channel']);
				// decode mapped pricing information
				$ch_pricing = json_decode($ch['otapricing'], true);
				if (!is_array($ch_pricing) || !isset($ch_pricing['RatePlan']) ) {
					continue;
				}
				if (!isset($otarplans[$ch_name])) {
					$otarplans[$ch_name] = [];
				}
				foreach ($ch_pricing['RatePlan'] as $rpid => $rpdata) {
					if (empty($rpdata['name'])) {
						continue;
					}
					$rplan_name = ucwords($rpdata['name']);
					if (in_array($rplan_name, $otarplans[$ch_name])) {
						continue;
					}
					array_push($otarplans[$ch_name], $rplan_name);
				}
				if (!$otarplans[$ch_name]) {
					unset($otarplans[$ch_name]);
				}
			}
		} catch (Exception $e) {
			// do nothing
		}

		return $otarplans;
	}
}
