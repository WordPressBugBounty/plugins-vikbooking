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
					<?php _e('With Vik Channel Manager and an active e4jConnect subscription, you can instantly sync availability, rates, and restrictions across all your channels.<br><strong>Forget manual updates and overbookings</strong> — the Channel Manager saves you time and gives you full control.', 'vikbooking'); ?>
				</h3>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=channel-manager&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/channel-manager.png" alt="Vik Channel Manager" />
			</div>

		</div>

		<div class="vikwppro-bottom-img">
			<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ota-badges.png" alt="OTA Badges" />
		</div>

	</div>

</div>