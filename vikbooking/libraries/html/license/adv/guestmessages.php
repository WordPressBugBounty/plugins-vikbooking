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

<div class="viwppro-cnt vcm-adv guest-messages">

	<div class="vikwppro-header-notice">
		<p><?php _e('Available exclusively for users with Vik Channel Manager and an active e4jConnect subscription.', 'vikbooking'); ?></p>
	</div>

	<div class="vikwppro-header">

		<div class="vikwppro-header-inner">

			<div class="vikwppro-header-text">

				<h2>
					<?php _e('Unified Guest Messaging', 'vikbooking'); ?>
				</h2>

				<h3>
					<?php _e('Chat with all your guests from one single interface — whether they booked through <strong>Airbnb</strong>, <strong>Booking.com</strong>, or your <strong>own website</strong>.<br>No need to switch platforms: manage every conversation directly from your WordPress dashboard.', 'vikbooking'); ?>
				</h3>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=guest-messages&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/guest-messages.webp" alt="<?php $this->escape(__('Guest Messages', 'vikbooking')); ?>" />
			</div>

		</div>

	</div>

</div>