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

<div class="viwppro-cnt vcm-adv operators-chat">

	<div class="vikwppro-header">

		<div class="vikwppro-header-inner">

			<div class="vikwppro-header-text">

				<h2>
					<?php _e('Stay connected with your housekeeping staff.', 'vikbooking'); ?>
				</h2>

				<h3>
					<?php _e('Let operators chat with managers, report issues, and share photos directly from their tasks. Everything stays organized and linked to the right room.<br>Requires <strong>Vik Channel Manager</strong> and an active <strong>e4jConnect subscription</strong>.', 'vikbooking'); ?>
				</h3>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&utm_medium=operators-chat&utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			
			</div>

			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/operators-chat.webp" alt="<?php $this->escape(__('Operators Chat', 'vikbooking')); ?>" />
			</div>

		</div>

	</div>

</div>