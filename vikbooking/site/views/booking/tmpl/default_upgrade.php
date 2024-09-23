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

$document = JFactory::getDocument();

$currencysymb = VikBooking::getCurrencySymb();
$pitemid = VikRequest::getInt('Itemid', 0, 'request');
$room_upgrade_trigger = VikRequest::getInt('room_upgrade', 0, 'request');

$upgrade_options = VBORoomHelper::getInstance([
	'booking' => $this->ord,
	'rooms'   => $this->orderrooms,
], true)->getUpgradeOptions($this->vbo_tn);

if ($upgrade_options) {
	/**
	 * Include the VBOCore JS class.
	 */
	$document->addScript(VBO_ADMIN_URI . 'resources/vbocore.js');

	// load JS lang strings
	JText::script('VBO_UPGRADE_ROOMS');
	JText::script('VBO_DOUPGRADE_CONFIRM');
	JText::script('VBOREVIEWGENERROR');

	// find the first and last booking-room index suited for upgrade
	$first_oroom_index = 0;
	foreach ($upgrade_options['upgrade'] as $upgk => $upgv) {
		$first_oroom_index = $upgk;
		break;
	}
	$last_oroom_index = max(array_keys($upgrade_options['upgrade']));

	?>
<div class="vbo-booking-mod-container">
	<div class="vbo-booking-mod-inner">
		<div class="vbo-booking-mod-cmd vbo-booking-roomupgrade-cmd">
			<a href="JavaScript: void(0);" onclick="vboDisplayUpgradeOptions();"><?php VikBookingIcons::e('gem'); ?> <?php echo JText::translate('VBO_UPGRADE_ROOMS'); ?></a>
		</div>
	</div>
</div>

<div id="vbo-hidden-roomupgrade-target" style="display: none;">
	<div class="vbo-roomupgrade-wrapper">
	<?php
	foreach ($upgrade_options['upgrade'] as $upgk => $upg_data) {
		?>
		<div class="vbo-roomupgrade-booked-room" data-upgindex="<?php echo $upgk; ?>" style="<?php echo $upgk != $first_oroom_index ? 'display: none;' : ''; ?>">
			<div class="vbo-roomupgrade-booked-room-inner">
				<div class="vbo-roomupgrade-current-wrap">
					<div class="vbo-roomupgrade-current-cont">
						<div class="vbo-roomupgrade-current-info">
						<?php
						if (!empty($this->orderrooms[$upgk]['img'])) {
							?>
							<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $this->orderrooms[$upgk]['img']; ?>"/>
							<?php
						}
						?>
							<div class="vbo-roomupgrade-current-name"><?php echo $this->orderrooms[$upgk]['name']; ?></div>
						</div>
						<div class="vbo-roomupgrade-current-pricing">
							<div class="vbo-roomupgrade-current-pricing-stay">
								<div class="vbo-roomupgrade-current-pricing-det vbo-roomupgrade-current-pricing-nights"><?php VikBookingIcons::e('moon', 'vbo-pref-color-text'); ?> <?php echo $this->ord['days']; ?></div>
								<div class="vbo-roomupgrade-current-pricing-det vbo-roomupgrade-current-pricing-adults"><?php VikBookingIcons::e('male', 'vbo-pref-color-text'); ?> <?php echo $this->orderrooms[$upgk]['adults']; ?></div>
							<?php
							if ($this->orderrooms[$upgk]['children'] > 0) {
								?>
								<div class="vbo-roomupgrade-current-pricing-det vbo-roomupgrade-current-pricing-children"><?php VikBookingIcons::e('child', 'vbo-pref-color-text'); ?> <?php echo $this->orderrooms[$upgk]['children']; ?></div>
								<?php
							}
							?>
							</div>
							<?php
							// current room cost
							$room_cost = $this->orderrooms[$upgk]['cust_cost'] > 0 ? (float)$this->orderrooms[$upgk]['cust_cost'] : (float)$this->orderrooms[$upgk]['room_cost'];
							?>
							<div class="vbo-roomupgrade-current-pricing-det vbo-roomupgrade-current-pricing-cost">
								<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
								<span class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($room_cost); ?></span>
							</div>
						</div>
					</div>
					<div class="vbo-roomupgrade-current-keep">
						<button type="button" class="btn vbo-pref-color-btn-secondary" onclick="vboUpgradeNavigate();"><?php echo JText::translate('VBO_KEEP_ROOM'); ?></button>
					</div>
				</div>
				<div class="vbo-roomupgrade-solutions-wrap">
				<?php
				foreach ($upg_data['r_costs'] as $rid => $upgrade_sol) {
					?>
					<div class="vbo-roomupgrade-solution-cont">
						<div class="vbo-roomupgrade-solution-data">
							<div class="vbo-roomupgrade-solution-info">
								<div class="vbo-roomupgrade-solution-info-main">
								<?php
								if (!empty($upgrade_options['rooms'][$rid]['img'])) {
									?>
									<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $upgrade_options['rooms'][$rid]['img']; ?>"/>
									<?php
								}
								?>
									<div class="vbo-roomupgrade-solution-name">
										<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid=' . $rid . (!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" target="_blank"><?php echo $upgrade_options['rooms'][$rid]['name']; ?></a>
									</div>
								</div>
							<?php
							if (!empty($upgrade_options['rooms'][$rid]['smalldesc'])) {
								?>
								<div class="vbo-roomupgrade-solution-descr"><?php echo $upgrade_options['rooms'][$rid]['smalldesc']; ?></div>
								<?php
							}
							?>
							</div>
						<?php
						if ($room_cost < $upgrade_sol['upgrade_cost']) {
							?>
							<div class="vbo-roomupgrade-solution-pricing">
								<div class="vbo-roomupgrade-solution-upgrade-cost">
									<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
									<span class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($upgrade_sol['upgrade_cost']); ?></span>
								</div>
							<?php
							if (isset($upg_data['discount']) && $upg_data['discount'] > 0) {
								?>
								<div class="vbo-roomupgrade-solution-upgrade-saveamount">
									<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
									<del class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($upgrade_sol['cost']); ?></del>
								</div>
								<div class="vbo-roomupgrade-solution-upgrade-savepcent">
									<span><?php echo JText::sprintf('VBO_YOU_SAVE_PCENT', $upg_data['discount'] . '%'); ?></span>
								</div>
								<?php
							}
							?>
							</div>
							<?php
						}
						?>
						</div>
						<div class="vbo-roomupgrade-solution-confirm">
							<button type="button" class="btn vbo-pref-color-btn" onclick="vboDoUpgrade('<?php echo $rid; ?>');"><?php echo JText::translate('VBO_UPGRADE_CONFIRM'); ?></button>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</div>
		<?php
	}
	?>
		<?php
		// echo 'Debug<pre>' . print_r($upgrade_options, true) . '</pre>';
		?>
	</div>
</div>

	<?php
	// modal loading body
	$loading_body = '<i class="' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '"></i>';

	// total rooms supporting an upgrade
	$tot_upgrades = count($upgrade_options['upgrade']);

	// AJAX upgrade URL
	$ajax_upgrade_url = VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=upgrade_room' . (!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));

	$document->addScriptDeclaration(
<<<JS
var vbo_oroom_index = $first_oroom_index;
var vbo_upgrade_reload = false;

function vboDisplayUpgradeOptions() {
	// number of upgrade steps
	var vbo_tot_upgrade_steps = $tot_upgrades;
	var vbo_upgrade_steps_count = '';
	if (vbo_tot_upgrade_steps > 1) {
		vbo_upgrade_steps_count = jQuery('<span></span>').addClass('vbo-modal-upgrade-step-counter').html(' (<span class="vbo-modal-upgrade-step-current">1</span>/$tot_upgrades)');
	}

	// display modal
	var upgrade_modal_body = VBOCore.displayModal({
		suffix: 'roomupgrade',
		title: jQuery('<span></span>').text(Joomla.JText._('VBO_UPGRADE_ROOMS')).append(vbo_upgrade_steps_count),
		body: '',
		dismiss_event: 'vbo-dismiss-modal-roomupgrade',
		onDismiss: () => {
			vboDismissUpgradeOptions();
		},
		loading_event: 'vbo-loading-modal-roomupgrade',
		loading_body: '$loading_body',
	});

	// move content to modal body
	jQuery('.vbo-roomupgrade-wrapper').appendTo(upgrade_modal_body);
}

function vboHideUpgradeOptions() {
	// hide and dismiss modal
	VBOCore.emitEvent('vbo-dismiss-modal-roomupgrade');
}

function vboDismissUpgradeOptions() {
	// move modal content (modal wrapper already dismissed)
	jQuery('.vbo-roomupgrade-wrapper').appendTo(jQuery('#vbo-hidden-roomupgrade-target'));

	// reset index and display status
	vbo_oroom_index = $first_oroom_index;
	jQuery('.vbo-roomupgrade-booked-room').hide();
	jQuery('.vbo-roomupgrade-booked-room[data-upgindex="' + vbo_oroom_index + '"]').show();

	// check for page refresh flag
	if (vbo_upgrade_reload) {
		document.location.href = jQuery('.vbo-current-booking-uri').first().attr('href');
	}

	return;
}

function vboUpgradeNavigate() {
	if ($tot_upgrades < 2) {
		// just one room supporting the upgrade
		return vboHideUpgradeOptions();
	}

	// find the next upgrade index, if any
	for (let find_index = ++vbo_oroom_index; find_index <= $last_oroom_index; find_index++) {
		if (jQuery('.vbo-roomupgrade-booked-room[data-upgindex="' + find_index + '"]').length) {
			vbo_oroom_index = find_index;
			break;
		}
	}
	if (!jQuery('.vbo-roomupgrade-booked-room[data-upgindex="' + vbo_oroom_index + '"]').length) {
		// next upgrade not found
		return vboHideUpgradeOptions();
	}

	// display next slide
	jQuery('.vbo-roomupgrade-booked-room').hide();
	jQuery('.vbo-roomupgrade-booked-room[data-upgindex="' + vbo_oroom_index + '"]').fadeIn();

	// increase slide counter
	var current_step_index = jQuery('.vbo-roomupgrade-booked-room').index(jQuery('.vbo-roomupgrade-booked-room[data-upgindex="' + vbo_oroom_index + '"]'));
	jQuery('.vbo-modal-upgrade-step-current').text(++current_step_index);
}

function vboDoUpgrade(rid) {
	if (!confirm(Joomla.JText._('VBO_DOUPGRADE_CONFIRM')) || !rid) {
		return false;
	}

	// show loading
	VBOCore.emitEvent('vbo-loading-modal-roomupgrade');

	// perform the request
	VBOCore.doAjax(
		"$ajax_upgrade_url",
		{
			bid: '{$this->ord['id']}',
			sid: '{$this->ord['sid']}',
			ts: '{$this->ord['ts']}',
			room_index: vbo_oroom_index,
			room_id: rid,
			tmpl: "component"
		},
		(response) => {
			// hide loading
			VBOCore.emitEvent('vbo-loading-modal-roomupgrade');
			try {
				// make sure the response was successful
				var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
				if (!obj_res || !obj_res.hasOwnProperty('success') || !obj_res['success']) {
					alert(obj_res.hasOwnProperty('error') ? obj_res['error'] : Joomla.JText._('VBOREVIEWGENERROR'));
					return false;
				}

				// turn flag on for reloading the booking when the modal gets dismissed
				vbo_upgrade_reload = true;

				// navigate to next room, or close the modal if none
				return vboUpgradeNavigate();
			} catch(err) {
				// display an error
				alert(Joomla.JText._('VBOREVIEWGENERROR'));
				// log the error
				console.error('could not parse JSON response', err, response);
			}
		},
		(error) => {
			// hide loading
			VBOCore.emitEvent('vbo-loading-modal-roomupgrade');
			// display the error
			alert(error.responseText || Joomla.JText._('VBOREVIEWGENERROR'));
		}
	);
}
JS
	);

	if ($room_upgrade_trigger) {
		// trigger the opening of the room upgrade modal when page loads
?>
<script type="text/javascript">
	jQuery(function() {
		setTimeout(() => {
			vboDisplayUpgradeOptions();
		}, 500);
	});
</script>
<?php
	}
}
