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
 * Class handler for conditional rule "number of guests".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleNumberOfGuests extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBOINVTOTGUESTS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_NOG_DESCR');
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
			<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMADULTS'); ?></div>
			<div class="vbo-param-setting">
				<div class="vbo-param-setting-group">
					<div class="vbplusminuscont">
						<span><?php echo JText::translate('VBNEWROOMMIN'); ?></span>
						<input type="number" min="0" name="<?php echo $this->inputName('from_adults'); ?>" value="<?php echo $this->getParam('from_adults', ''); ?>" style="width: 40px;"/>
					</div>
				</div>
				<div class="vbo-param-setting-group">
					<div class="vbplusminuscont">
						<span><?php echo JText::translate('VBNEWROOMMAX'); ?></span>
						<input type="number" min="0" name="<?php echo $this->inputName('to_adults'); ?>" value="<?php echo $this->getParam('to_adults', ''); ?>" style="width: 40px;"/>
					</div>
				</div>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBNEWROOMCHILDREN'); ?></div>
			<div class="vbo-param-setting">
				<div class="vbo-param-setting-group">
					<div class="vbplusminuscont">
						<span><?php echo JText::translate('VBNEWROOMMIN'); ?></span>
						<input type="number" min="0" name="<?php echo $this->inputName('from_children'); ?>" value="<?php echo $this->getParam('from_children', ''); ?>" style="width: 40px;"/>
					</div>
				</div>
				<div class="vbo-param-setting-group">
					<div class="vbplusminuscont">
						<span><?php echo JText::translate('VBNEWROOMMAX'); ?></span>
						<input type="number" min="0" name="<?php echo $this->inputName('to_children'); ?>" value="<?php echo $this->getParam('to_children', ''); ?>" style="width: 40px;"/>
					</div>
				</div>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></div>
			<div class="vbo-param-setting">
				<div class="vbo-param-setting-group">
					<div class="vbplusminuscont">
						<span><?php echo JText::translate('VBNEWROOMMIN'); ?></span>
						<input type="number" min="0" name="<?php echo $this->inputName('from_totguests'); ?>" value="<?php echo $this->getParam('from_totguests', ''); ?>" style="width: 40px;"/>
					</div>
				</div>
				<div class="vbo-param-setting-group">
					<div class="vbplusminuscont">
						<span><?php echo JText::translate('VBNEWROOMMAX'); ?></span>
						<input type="number" min="0" name="<?php echo $this->inputName('to_totguests'); ?>" value="<?php echo $this->getParam('to_totguests', ''); ?>" style="width: 40px;"/>
					</div>
				</div>
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
		$rooms_booked = $this->getProperty('rooms', array());
		if (!is_array($rooms_booked) || !count($rooms_booked)) {
			return false;
		}

		// count total guests booked
		$booked_adults = 0;
		$booked_children = 0;
		foreach ($rooms_booked as $rb) {
			$booked_adults += (int)$rb['adults'];
			$booked_children += (int)$rb['children'];
		}
		$booked_totguests = $booked_adults + $booked_children;

		$enough = true;

		$from_adults = $this->getParam('from_adults', null);
		$to_adults = $this->getParam('to_adults', $from_adults);
		if ($from_adults !== null && ($booked_adults < $from_adults || $booked_adults > $to_adults)) {
			$enough = false;
		}

		$from_children = $this->getParam('from_children', null);
		$to_children = $this->getParam('to_children', $from_children);
		if ($from_children !== null && ($booked_children < $from_children || $booked_children > $to_children)) {
			$enough = false;
		}

		$from_totguests = $this->getParam('from_totguests', null);
		$to_totguests = $this->getParam('to_totguests', $from_totguests);
		if ($from_totguests !== null && ($booked_totguests < $from_totguests || $booked_totguests > $to_totguests)) {
			$enough = false;
		}

		return $enough;
	}

}
