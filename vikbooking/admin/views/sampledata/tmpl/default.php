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

?>

<form action="admin.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="" />
</form>

<div class="vbo-dashboard-firstsetup-container vbo-sampledata-container">
	<div class="vbo-dashboard-firstsetup-head">
		<h3><?php echo JText::translate('VBO_SAMPLEDATA_INSTALL'); ?></h3>
		<h4><?php echo JText::translate('VBO_SAMPLEDATA_INTRO_DESCR'); ?></h4>
		<h4><?php echo JText::translate('VBO_SAMPLEDATA_INTRO_SUBDESCR'); ?></h4>
	</div>
	<div class="vbo-dashboard-firstsetup-body">
		<div class="vbo-dashboard-firstsetup-task">
			<div class="vbo-dashboard-firstsetup-task-wrap">
				<div class="vbo-dashboard-firstsetup-task-details">
					<select id="vik-sample-data-list">
						<option value="">- <?php echo JText::translate('VBODASHINSTSAMPLEDBTN'); ?></option>
					</select>
				</div>
				<div class="vbo-dashboard-firstsetup-task-action">
					<button type="button" class="btn vbo-sampledata-btn" id="vik-sample-data-install" disabled><?php echo JText::translate('VBO_SAMPLEDATA_INSTALL'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">

	jQuery(document).ready(function() {

		// populate sample data options
		jQuery.ajax({
			type: "POST",
			url: "admin-ajax.php",
			data: {
				action: "vikbooking",
				task: "sampledata.load"
			}
		}).done(function(res) {
			try {
				var obj_res = JSON.parse(res);
				if (obj_res && obj_res.length) {
					for (var i in obj_res) {
						if (!obj_res.hasOwnProperty(i)) {
							continue;
						}
						jQuery('#vik-sample-data-list').append('<option value="' + obj_res[i]['id'] + '">' + obj_res[i]['title'] + '</option>');
					}
				} else {
					alert('No Sample Data available for installation.');
					console.info('No Sample Data available for installation.', obj_res);
				}
			} catch(err) {
				alert('An error occurred loading the Sample Data available.');
				console.error('Sample Data: could not parse JSON response', err, res);
			}
		}).fail(function(err) {
			alert('An error occurred. Please reload the page.');
			console.error(err);
		});
		//

		jQuery('#vik-sample-data-list').on('change', function() {
			jQuery('#vik-sample-data-install').prop('disabled', (!jQuery(this).val().length));
		});

		jQuery('#vik-sample-data-install').click(function() {
			if (jQuery(this).prop('disabled') === true) {
				return false;
			}

			// start installation
			jQuery(this).prop('disabled', true).prepend('<?php VikBookingIcons::e('refresh', 'fa-spin'); ?> ');

			jQuery.ajax({
				type: "POST",
				url: "admin-ajax.php",
				data: {
					action: "vikbooking",
					task: "sampledata.install",
					sample_data_id: jQuery('#vik-sample-data-list').val()
				}
			}).done(function(res) {
				try {
					var obj_res = JSON.parse(res);
					if (!obj_res || !obj_res.status) {
						console.error(res);
						if (obj_res && obj_res.error) {
							alert(obj_res.error);
						} else {
							alert('Could not install sample data. Please check your console for the full error description.');
						}
					}
				} catch(err) {
					console.error('Unable to install Sample Data.', err, res);
					alert('Unable to install Sample Data.');
				}

				// always redirect to the dashboard page
				document.location.href = 'admin.php?page=vikbooking';
			}).fail(function(err) {
				console.error(err);
				alert('An error occurred installing the sample data. Please try again.');
				document.location.href = 'admin.php?page=vikbooking&view=sampledata';
			});
		});

	});

</script>
