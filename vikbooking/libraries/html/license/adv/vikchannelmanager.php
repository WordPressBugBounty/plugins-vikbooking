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

// set up toolbar title
JToolbarHelper::title(__('Vik Booking - Channel Manager', 'vikbooking'));

?>

<div class="viwppro-cnt vcm-adv channel-manager">

	<div class="vikwppro-header">

		<div class="vikwppro-header-inner">

			<div class="vikwppro-header-text">

				<h2>
					<?php _e('Manage Airbnb, Booking.com, Expedia, and more.<br>All from your WordPress website.', 'vikbooking'); ?>
				</h2>

				<h3>
					<?php _e('With Vik Channel Manager and an active e4jConnect subscription, you can instantly sync availability, rates, and restrictions across all your channels.<br><strong class="text-green">Forget manual updates and overbookings</strong> — the Channel Manager saves you time and gives you full control.', 'vikbooking'); ?>
				</h3>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=channel-manager&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/channel-manager.png" alt="Vik Channel Manager" />
			</div>

		</div>

		<div class="vikwppro-header-inner">

			<div class="vikwppro-header-img">
				<img class="img-shadow" src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/guest-messages-tool.png" alt="Vik Channel Manager" />
			</div>

			<div class="vikwppro-header-text">

				<h2>
					<?php _e('All your guest chats in one place: WhatsApp, Booking.com, and Airbnb.', 'vikbooking'); ?>
				</h2>

				<h3>
					<?php _e('Tired of jumping between different apps just to answer a guest? With our <strong class="text-green">Unified Inbox</strong>, you and your team can handle every message from one single dashboard. Whether it\'s a WhatsApp chat or an Airbnb inquiry, it\'s all right there.', 'vikbooking'); ?>
				</h3>

				<h3>
					<?php _e('If you want to go further, our <strong class="text-green">AI Services</strong> can take over the routine stuff. It handles FAQs and booking confirmations in over 100 languages, 24/7. It’s like having an extra pair of hands to deal with the repetitive questions, so you can focus on the actual hospitality.', 'vikbooking'); ?>
				</h3>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=channel-manager&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

		</div>

		<div class="vikwppro-bottom-img">
			<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ota-badges.png" alt="OTA Badges" />
		</div>

	</div>

</div>