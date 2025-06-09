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
JToolbarHelper::title(__('Vik Booking - Task Manager', 'vikbooking'));

?>

<div class="viwppro-cnt vcm-adv task-manager">

	<div class="vikwppro-header">

		<div class="vikwppro-header-inner">

			<div class="vikwppro-header-text">

				<h2>
					<?php _e('Smart task scheduling for cleaning staff, turnovers, and maintenance.', 'vikbooking'); ?>
				</h2>

				<h3>
					<?php _e('Available exclusively for users with <strong>Vik Channel Manager</strong> and an active <strong>e4jConnect subscription</strong>.', 'vikbooking'); ?>
				</h3>

				<ul>
					<li>
						<?php VikBookingIcons::e('broom'); ?>
						<span>
							<?php _e('Automate housekeeping based on real bookings', 'vikbooking'); ?>
						</span>
					</li>
					<li>
						<?php VikBookingIcons::e('tools'); ?>
						<span>
							<?php _e('Create and assign maintenance tasks', 'vikbooking'); ?>
						</span>
					</li>
					<li>
						<?php VikBookingIcons::e('calendar-check'); ?>
						<span>
							<?php _e('Keep every room guest-ready, always on time', 'vikbooking'); ?>
						</span>
					</li>
				</ul>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=task-manager&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/task-manager.webp" alt="<?php $this->escape(__('Task Manager', 'vikbooking')); ?>" />
			</div>

		</div>

	</div>

</div>