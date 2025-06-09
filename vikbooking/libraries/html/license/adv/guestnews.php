<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  html.license
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

?>

<div class="viwppro-cnt vcm-adv guest-news">

	<div class="vikwppro-header-notice">
		<p><?php _e('Available exclusively for users with Vik Channel Manager and an active e4jConnect subscription.', 'vikbooking'); ?></p>
	</div>

	<div class="vikwppro-header">

		<div class="vikwppro-header-inner">

			<div class="vikwppro-header-text">

				<h2>
					<?php _e('Stay in the Loop with Guest Activity', 'vikbooking'); ?>
				</h2>

				<h3>
					<?php _e('See the most recent guest messages and reviews all in one place.<br>Stay updated and never miss important feedback from your guests.', 'vikbooking'); ?>
				</h3>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=latest-from-guests&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/latest-guests.webp" alt="<?php $this->escape(__('Latest from your guests', 'vikbooking')); ?>" style="max-height: 700px" />
			</div>

		</div>

	</div>

</div>

<script>
	(function($) {
		'use strict';

		$('.viwppro-cnt.vcm-adv.guest-news').closest('.vbo-modal-overlay-content').addClass('vbo-modal-large');

		document.addEventListener('vbo-admin-dock-restore-aitools', () => {
			$('.viwppro-cnt.vcm-adv.guest-news').closest('.vbo-modal-overlay-content').addClass('vbo-modal-large');
		});
	})(jQuery);
</script>