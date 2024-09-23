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
 * Class handler for conditional rule "rooms".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleRooms extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBPVIEWORDERSTHREE');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_ROOMS_DESCR');
		$this->ruleId = basename(__FILE__);
	}

	/**
	 * Displays the rule parameters.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added support for sub-units.
	 */
	public function renderParams()
	{
		$this->vbo_app->loadSelect2();
		$rooms = $this->loadRooms();
		$current_rooms = $this->getParam('rooms', array());
		$current_rooms = !is_array($current_rooms) ? array() : $current_rooms;

		// check if we've got rooms with sub-units defined
		$sub_units = [];
		foreach ($rooms as $rdata) {
			if ($rdata['units'] < 2) {
				continue;
			}
			$room_features = VikBooking::getRoomParam('features', $rdata['params']);
			if (is_array($room_features) && count($room_features)) {
				$sub_units[$rdata['id']] = [
					'name' 	   => $rdata['name'],
					'units'    => $rdata['units'],
					'features' => $room_features,
				];
			}
		}

		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOROOMSASSIGNED'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('rooms', true); ?>" id="<?php echo $this->inputID('rooms'); ?>" multiple="multiple" onchange="vboChangeSubUnits();">
				<?php
				foreach ($rooms as $rdata) {
					?>
					<option value="<?php echo $rdata['id']; ?>"<?php echo in_array($rdata['id'], $current_rooms) ? ' selected="selected"' : ''; ?>><?php echo $rdata['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>

		<?php
		if (count($sub_units)) {
			?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMNINE'); ?></div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('use_sub_units'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('use_sub_units', 0), 1, 0, 'vboToggleUseSubUnits();'); ?>
			</div>
		</div>
			<?php
			$init_display = (int)$this->getParam('use_sub_units', 0);
			foreach ($sub_units as $rid => $rdata) {
				$display_runits = ($init_display && in_array($rid, $current_rooms));
				?>
		<div class="vbo-param-container vbo-rule-rooms-rsubunits" data-rid="<?php echo $rid; ?>" style="<?php echo !$display_runits ? 'display: none;' : ''; ?>">
			<div class="vbo-param-label"><?php echo $rdata['name']; ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName("sub_unit_$rid"); ?>">
					<option value=""></option>
				<?php
				$cur_val = (int)$this->getParam("sub_unit_$rid", 0);
				for ($i = 1; $i <= $rdata['units']; $i++) {
					?>
					<option value="<?php echo $i; ?>"<?php echo $cur_val == $i ? ' selected="selected"' : ''; ?>><?php echo $this->getFirstFeature($i, $rdata['features']); ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
				<?php
			}
		}
		?>
		
		<script type="text/javascript">
			jQuery(function() {
				jQuery('#<?php echo $this->inputID('rooms'); ?>').select2();
			});

			function vboToggleUseSubUnits() {
				jQuery('.vbo-rule-rooms-rsubunits').hide();
				var use_sub_units = jQuery('input[name="<?php echo $this->inputName('use_sub_units'); ?>"]').prop('checked');
				if (use_sub_units) {
					var rooms_selected = jQuery('#<?php echo $this->inputID('rooms'); ?>').val();
					jQuery('.vbo-rule-rooms-rsubunits').each(function() {
						var rid = jQuery(this).attr('data-rid');
						if (rooms_selected && rooms_selected.length && rooms_selected.indexOf(rid) >= 0) {
							jQuery(this).show();
						} else {
							jQuery(this).hide().find('select').val('');
						}
					});
				} else {
					// hide all sub-units and make them empty
					jQuery('.vbo-rule-rooms-rsubunits').hide().find('select').val('');
				}
			}

			function vboChangeSubUnits() {
				var rooms_selected = jQuery('#<?php echo $this->inputID('rooms'); ?>').val();
				if (!rooms_selected || !rooms_selected.length) {
					// hide all sub-units and make them empty
					jQuery('.vbo-rule-rooms-rsubunits').hide().find('select').val('');
				} else {
					// hide all sub-units, but don't touch their values
					jQuery('.vbo-rule-rooms-rsubunits').hide();
					// check if the use of sub-units is enabled
					var use_sub_units = jQuery('input[name="<?php echo $this->inputName('use_sub_units'); ?>"]').prop('checked');
					// display only the sub-units for the selected rooms
					if (use_sub_units) {
						for (var i = 0; i < rooms_selected.length; i++) {
							var sub_units_cont = jQuery('.vbo-rule-rooms-rsubunits[data-rid="' + rooms_selected[i] + '"]');
							if (sub_units_cont && sub_units_cont.length) {
								// show the sub-units for this room
								sub_units_cont.show();
							}
						}
					}
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
		$rooms_booked = $this->getProperty('rooms', array());
		if (!is_array($rooms_booked) || !count($rooms_booked)) {
			return false;
		}

		$allowed_rooms = $this->getParam('rooms', array());

		$one_found = false;
		foreach ($rooms_booked as $rb) {
			if (!isset($rb['idroom'])) {
				continue;
			}
			if (in_array($rb['idroom'], $allowed_rooms)) {
				$one_found = true;
				break;
			}
		}

		// check sub-units
		$use_sub_units = (int)$this->getParam('use_sub_units', 0);
		if ($use_sub_units) {
			// parse again the rooms booked
			foreach ($rooms_booked as $rb) {
				// grab the sub-unit index for this room id
				$room_sub_unit_filt = (int)$this->getParam('sub_unit_' . $rb['idroom'], 0);
				if (empty($room_sub_unit_filt)) {
					// no sub-unit defined for this room ID
					continue;
				}
				if ($rb['roomindex'] != $room_sub_unit_filt) {
					// the index of the room booked is not the one in the params
					return false;
				}
			}
		}

		// return true if at least one room booked is in the parameters
		return $one_found;
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadRooms()
	{
		$rooms = array();

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`, `name`, `units`, `params` FROM `#__vikbooking_rooms` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$rooms = $dbo->loadAssocList();
		}

		return $rooms;
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @param 	int 	$i 			the room unit index to get.
	 * @param 	array 	$features 	the list of room features.
	 * 
	 * @return 	array
	 */
	protected function getFirstFeature($i, $features = [])
	{
		if (!is_array($features) || !isset($features[$i])) {
			return $i;
		}

		foreach ($features[$i] as $fkey => $fval) {
			if (!empty($fkey) && !empty($fval)) {
				return "#$i - " . JText::translate($fkey) . ': ' . $fval;
			}
		}

		return "#$i";
	}
}
