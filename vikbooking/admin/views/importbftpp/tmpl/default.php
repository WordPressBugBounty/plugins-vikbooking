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

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();

if (!$this->setup_completed) {
	// no rooms in VBO
	?>
<p class="warn"><?php echo JText::translate('VBO_IMPBFTPP_NOVBOROOMS'); ?></p>
<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_vikbooking" />
</form>
	<?php
} else {
	?>
<p class="info vbo-importbftpp-disclaimer"><?php echo JText::translate('VBO_IMPBFTPP_DISCLAIMER'); ?></p>
<form action="index.php?option=com_vikbooking&view=importbftpp" method="post" name="adminForm" id="adminForm" class="vbo-list-form" onsubmit="return vboImportbftppSubmit();">
	<?php
	if (!is_array($this->plugins) || !count($this->plugins)) {
		?>
	<p class="err"><?php echo JText::translate('VBO_IMPBFTPP_NOPLUGINS'); ?></p>
	<a class="btn btn-primary" href="index.php?option=com_vikbooking"><?php echo JText::translate('VBBACK'); ?></a>
		<?php
	} else {
		?>
	<div class="vbo-importbftpp-plugins-list">
		<div class="vbo-importbftpp-plugins-list-inner">
			<label for="vbo-importbftpp-plugins-sel"><?php echo JText::translate('VBO_IMPBFTPP_AVAILPLUGINS'); ?></label>
			<select name="tpp" id="vbo-importbftpp-plugins-sel">
				<option value="">------</option>
			<?php
			foreach ($this->plugins as $k => $v) {
				?>
				<option value="<?php echo $k; ?>"<?php echo $this->tpp == $k ? ' selected="selected"' : ''; ?>><?php echo $v; ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="vbo-importbftpp-plugins-list-bottom">
			<span class="vbo-btn-label"><?php echo JText::translate('VBO_IMPBFTPP_READBFROMP'); ?></span>
			<button type="submit" class="btn btn-primary"><?php VikBookingIcons::e('search'); ?> <?php echo JText::translate('VBO_IMPBFTPP_READBFROMP_SHORT'); ?></button>
		</div>
	</div>
		<?php
		if (count($this->tprooms) && count($this->tpbookings)) {
			// render rooms mapping and all bookings
			$must_map_rooms = array();
			foreach ($this->tpbookings as $tpbooking) {
				foreach ($tpbooking['room_ids'] as $tprid) {
					if (!in_array($tprid, $must_map_rooms)) {
						// push room ID required for mapping
						array_push($must_map_rooms, $tprid);
					}
				}
			}
			?>
	<h3 class="vbo-importbftpp-title"><?php echo JText::sprintf('VBO_IMPBFTPP_TOTRESFOUND', count($this->tpbookings)); ?></h3>
	<div class="vbo-importbftpp-roomsmapping-wrap">
		<p class="info vbo-importbftpp-roomsmapping-info"><?php echo JText::translate('VBO_IMPBFTPP_MAKERMAPPING'); ?></p>
		<h4 class="vbo-importbftpp-subtitle">1. <?php echo JText::translate('VBO_IMPBFTPP_MAKERMAPPING_SHORT'); ?></h4>
		<div class="vbo-importbftpp-roomsmapping-inner">
			<?php
			foreach ($must_map_rooms as $tprid) {
			?>
			<div class="vbo-importbftpp-roomsmapping-block">
				<div class="vbo-importbftpp-roomsmapping-block-thirdparty">
					<label for="vbo-importbftpp-tpproom<?php echo $tprid; ?>"><?php echo JText::translate('VBO_IMPBFTPP_SELTPPROOM'); ?></label>
					<select name="tpp_rooms[]" id="vbo-importbftpp-tpproom<?php echo $tprid; ?>" class="vbo-importbftpp-mapsel vbo-importbftpp-mapsel-tpp">
						<option value="<?php echo $tprid; ?>"><?php echo $this->tprooms[$tprid]; ?></option>
					</select>
				</div>
				<div class="vbo-importbftpp-roomsmapping-block-vbo">
					<label for="vbo-importbftpp-vboroom<?php echo $tprid; ?>"><?php echo JText::translate('VBO_IMPBFTPP_SELVBOROOM'); ?></label>
					<select name="vbo_rooms[]" id="vbo-importbftpp-vboroom<?php echo $tprid; ?>" class="vbo-importbftpp-mapsel vbo-importbftpp-mapsel-vbo" onchange="vboToggleResEligible('<?php echo $tprid; ?>', this.value);">
						<option value="">------</option>
					<?php
					foreach ($this->vbo_rooms as $vborid => $vborname) {
						?>
						<option value="<?php echo $vborid; ?>"><?php echo $vborname; ?></option>
						<?php
					}
					?>
					</select>
				</div>
			</div>
			<?php
			}
			?>
		</div>
	</div>
	<h4 class="vbo-importbftpp-subtitle">2. <?php echo JText::translate('VBO_IMPBFTPP_SELRES_SHORT'); ?></h4>
	<div class="vbo-importbftpp-reservations-wrap">
		<div class="vbo-importbftpp-reservations-btns" style="display: none;">
			<span class="vbo-importbftpp-reservations-btn">
				<button type="button" class="btn btn-success" onclick="vboToggleAllResImport(true);"><?php echo JText::translate('VBINVSELECTALL'); ?></button>
			</span>
			<span class="vbo-importbftpp-reservations-btn">
				<button type="button" class="btn btn-danger" onclick="vboToggleAllResImport(false);"><?php echo JText::translate('VBINVDESELECTALL'); ?></button>
			</span>
		</div>
		<div class="vbo-importbftpp-reservations-inner">
			<?php
			foreach ($this->tpbookings as $tpbid => $tpbdata) {
				$booking_status_class = array();
				if (stripos($tpbdata['status'], 'confirmed') !== false) {
					array_push($booking_status_class, 'label-success');
				} elseif (stripos($tpbdata['status'], 'pending') !== false) {
					array_push($booking_status_class, 'label-warning');
				} elseif (stripos($tpbdata['status'], 'cancelled') !== false || $tpbdata['status'] == 'abandoned') {
					array_push($booking_status_class, 'label-error');
				}
				?>
			<div class="vbo-importbftpp-reservation-block">
				<div class="vbo-importbftpp-reservation-inner vbo-importbftpp-reservation-noteligible" data-tprooms="<?php echo implode(';', $tpbdata['room_ids']); ?>">
					<div class="vbo-importbftpp-reservation-details">
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBDASHBOOKINGID'); ?></span>
							<span class="label vbo-importbftpp-reservation-detail-val"><?php echo '#' . $tpbid . ' (' . $tpbdata['dt'] . ')'; ?></span>
						</div>
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBSTATUS'); ?></span>
							<span class="label<?php echo count($booking_status_class) ? ' ' . implode(' ', $booking_status_class) : ''; ?> vbo-importbftpp-reservation-detail-val"><?php echo ucfirst($tpbdata['status']); ?></span>
						</div>
					<?php
					if (!empty($tpbdata['last_import'])) {
						?>
						<div class="vbo-importbftpp-reservation-detail vbo-importbftpp-reservation-already-imported">
							<span class="vbo-importbftpp-reservation-detail-lbl">&nbsp;</span>
							<span class="vbo-importbftpp-reservation-detail-val label label-error"><?php echo JText::sprintf('VBO_IMPBFTPP_ALREADY_IMPORTED', $tpbdata['last_import']); ?></span>
						</div>
						<?php
					}
					if (!empty($tpbdata['infos']['first_name'])) {
						?>
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBCUSTOMERFIRSTNAME'); ?></span>
							<span class="vbo-importbftpp-reservation-detail-val"><?php echo $tpbdata['infos']['first_name']; ?></span>
						</div>
						<?php
					}
					if (!empty($tpbdata['infos']['last_name'])) {
						?>
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBCUSTOMERLASTNAME'); ?></span>
							<span class="vbo-importbftpp-reservation-detail-val"><?php echo $tpbdata['infos']['last_name']; ?></span>
						</div>
						<?php
					}
					if (!empty($tpbdata['infos']['email'])) {
						?>
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBCUSTOMEREMAIL'); ?></span>
							<span class="vbo-importbftpp-reservation-detail-val"><?php echo $tpbdata['infos']['email']; ?></span>
						</div>
						<?php
					}
					if (!empty($tpbdata['infos']['checkin'])) {
						?>
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBPICKUPAT'); ?></span>
							<span class="vbo-importbftpp-reservation-detail-val"><?php echo $tpbdata['infos']['checkin']; ?></span>
						</div>
						<?php
					}
					if (!empty($tpbdata['infos']['checkout'])) {
						?>
						<div class="vbo-importbftpp-reservation-detail">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBRELEASEAT'); ?></span>
							<span class="vbo-importbftpp-reservation-detail-val"><?php echo $tpbdata['infos']['checkout']; ?></span>
						</div>
						<?php
					}
					?>
					</div>
					<div class="vbo-importbftpp-reservation-import">
						<div class="vbo-importbftpp-reservation-import-toggle" style="display: none;">
							<?php echo $vbo_app->printYesNoButtons("tpp_bids[{$tpbid}]", JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0); ?>
						</div>
						<div class="vbo-importbftpp-reservation-import-rooms">
							<span class="vbo-importbftpp-reservation-detail-lbl"><?php echo JText::translate('VBPVIEWORDERSTHREE'); ?></span>
							<span class="vbo-importbftpp-reservation-detail-val">------</span>
						</div>
					</div>
				</div>
			</div>
				<?php
			}
			?>
		</div>
	</div>
	<div class="vbo-importbftpp-confirm-wrap">
		<span class="vbo-btn-label"><?php echo JText::translate('VBO_IMPBFTPP_DOIMPORT'); ?></span>
		<button type="submit" class="btn btn-large btn-success" onclick="return vboConfirmImport();"><?php VikBookingIcons::e('download'); ?> <?php echo JText::translate('VBO_IMPBFTPP_DOIMPORT_SHORT'); ?></button>
	</div>
			<?php
			// echo 'Debug<pre>' . print_r($this->tprooms, true) . '</pre>';
			// echo 'Debug<pre>' . print_r($this->tpbookings, true) . '</pre>';
			// echo 'Debug<pre>' . print_r($must_map_rooms, true) . '</pre>';
		} elseif (!empty($this->tpp)) {
			// no bookings found in third party plugin
			?>
	<p class="err"><?php echo JText::translate('VBO_IMPBFTPP_NORESINTPP'); ?></p>
	<a class="btn btn-primary" href="index.php?option=com_vikbooking"><?php echo JText::translate('VBBACK'); ?></a>
			<?php
		}
	}
	?>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="view" value="importbftpp" />
</form>
<script type="text/javascript">
function vboImportbftppSubmit() {
	if (!jQuery('#vbo-importbftpp-plugins-sel').length || !jQuery('#vbo-importbftpp-plugins-sel').val().length) {
		alert('Please select one third party plugin from the list');
		return false;
	}
	return true;
}

function vboConfirmImport() {
	if (confirm('<?php echo addslashes(JText::translate('VBO_IMPBFTPP_DOIMPORT_CONF')); ?>')) {
		if (!jQuery('#adminForm').find('#do_import').length) {
			jQuery('#adminForm').append('<input id="do_import" type="hidden" name="do_import" value="1" />');
		}
		return true;
	}
	return false;
}

function vboToggleResEligible(tprid, vborid) {
	if (!tprid || !tprid.length) {
		return;
	}
	jQuery('.vbo-importbftpp-reservation-inner').each(function() {
		var tprids = jQuery(this).attr('data-tprooms').split(';');
		if (tprids.indexOf(tprid) < 0) {
			// this booking is not interested into this room
			return;
		}
		if (!vborid || !vborid.length) {
			// unset made for corresponding room, make the block not-eligible
			jQuery(this).removeClass('vbo-importbftpp-reservation-eligible').addClass('vbo-importbftpp-reservation-noteligible');
			jQuery(this).find('.vbo-importbftpp-reservation-import-toggle').hide().find('input[type="checkbox"]').prop('checked', false);
			jQuery(this).find('.vbo-importbftpp-reservation-import-rooms').find('.vbo-importbftpp-reservation-detail-val').html('------');
		} else {
			// make the block eligible
			jQuery(this).removeClass('vbo-importbftpp-reservation-noteligible').addClass('vbo-importbftpp-reservation-eligible');
			jQuery(this).find('.vbo-importbftpp-reservation-import-toggle').show().find('input[type="checkbox"]').prop('checked', true);
			jQuery(this).find('.vbo-importbftpp-reservation-import-rooms').find('.vbo-importbftpp-reservation-detail-val').html(tprids.length);
			// if this booking was already imported, do not set the checkbox to checked
			if (jQuery(this).find('.vbo-importbftpp-reservation-already-imported').length) {
				jQuery(this).find('.vbo-importbftpp-reservation-import-toggle').find('input[type="checkbox"]').prop('checked', false);
			}
		}
	});
	// check whether all required rooms have been mapped to display the toggle reservation buttons
	var all_rooms_mapped = true;
	jQuery('.vbo-importbftpp-mapsel-vbo').each(function() {
		if (!jQuery(this).val().length) {
			all_rooms_mapped = false;
			return false;
		}
	});
	if (all_rooms_mapped) {
		jQuery('.vbo-importbftpp-reservations-btns').show();
	} else {
		jQuery('.vbo-importbftpp-reservations-btns').hide();
	}
}

function vboToggleAllResImport(doimport) {
	jQuery('.vbo-importbftpp-reservation-inner').each(function() {
		if (!jQuery(this).hasClass('vbo-importbftpp-reservation-eligible')) {
			// a corresponding room type must be selected first
			return;
		}
		if (doimport) {
			jQuery(this).find('.vbo-importbftpp-reservation-import-toggle').find('input[type="checkbox"]').prop('checked', true);
		} else {
			jQuery(this).find('.vbo-importbftpp-reservation-import-toggle').find('input[type="checkbox"]').prop('checked', false);
		}
	});
}

jQuery(document).ready(function() {
	if (jQuery('#vbo-importbftpp-plugins-sel').length) {
		jQuery('#vbo-importbftpp-plugins-sel').select2();
	}
	if (jQuery('.vbo-importbftpp-mapsel').length) {
		jQuery('.vbo-importbftpp-mapsel').select2();
	}
});
</script>
<?php
}
