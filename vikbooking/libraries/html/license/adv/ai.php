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

<div class="viwppro-cnt vcm-adv ai">

	<div class="vikwppro-header-notice">
		<p><?php _e('Available exclusively for users with Vik Channel Manager and an active e4jConnect subscription.', 'vikbooking'); ?></p>
	</div>

	<div class="vikwppro-header">

		<div class="vikwppro-header-inner">
			<div class="vikwppro-header-text">
				<h2><?php _e('Get your personal AI Assistant available 24/7', 'vikbooking'); ?></h2>

				<p><?php _e('Look after your guests throughout their journey with a human-oriented <strong>AI virtual agent operating 24/7</strong>.', 'vikbooking'); ?></p>
				<p><?php _e('Both you and your guest will always have full control of the situation, whether or not you decide to use the automatic generation. Your customers can always decide to look for a human interaction, keeping a <strong>seamless guest experience as the main goal</strong>.', 'vikbooking'); ?></p>

				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&amp;utm_medium=ai-assistant&amp;utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>
			</div>
			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ai-assistant-24hours.webp" alt="AI Assistant active 24/7" />
			</div>
		</div>

	</div>

	<div class="vikwppro-header">
		<div class="vikwppro-header-inner">
			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ai-assistant-translate.webp" alt="AI Assistant can translate" />
			</div>
			<div class="vikwppro-header-text">
				<h2><?php _e('Anticipate your guest needs to provide the finest experience', 'vikbooking'); ?></h2>

				<p><?php _e('Your AI assistant has access to all your previous customer conversations learning from real requests and necessities. Make the most of the AI data analysis potential and play ahead by taking advantage of your guests Most Frequent Topics to understand their needs.', 'vikbooking'); ?></p>
				<p><?php _e('Overcome language barriers, and meet your guests halfway with an enhanced <strong>AI that speaks every language.</strong>', 'vikbooking'); ?></p>		
			</div>
		</div>
	</div>

	<div class="vikwppro-header">
		<div class="vikwppro-header-inner">
			<div class="vikwppro-header-text">
				<h2><?php _e('Train your AI Assistant to get rid of tedious messages', 'vikbooking'); ?></h2>

				<p><?php _e('Tired of answering questions like: “What time is the breakfast?”, “Is the Wi-Fi available in the room? Which is the password?”.<br />Educate the AI about your property with customizable instructions to <strong>reply to time-consuming questions.</strong>', 'vikbooking'); ?></p>
				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&amp;utm_medium=ai-assistant&amp;utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>		
			</div>
			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ai-speed-guest-requests.webp" alt="AI Assistant messages auto-responder" />
			</div>
		</div>
	</div>

	<div class="vikwppro-header">
		<div class="vikwppro-header-inner">
			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ai-review-generation.webp" alt="AI Assistant review auto-responder" />
			</div>
			<div class="vikwppro-header-text">
				<h2><?php _e('Automatized generation of tailored reviews on Booking.com and Airbnb', 'vikbooking'); ?></h2>

				<p><?php _e('Your AI personal assistant works independently to create and publish individual reviews based on your guests feedback, while you focus on your priorities.<br />No pre-set answers, the AI technology is tailored to <strong>provide authentic and human-oriented reviews.</strong>', 'vikbooking'); ?></p>
				<p><?php _e('<strong>Boost your online reputation!</strong>', 'vikbooking'); ?></p>
			</div>
		</div>
	</div>

	<div class="vikwppro-header">
		<div class="vikwppro-header-inner">
			<div class="vikwppro-header-text">
				<h2><?php _e('Unmatched processing power at your service', 'vikbooking'); ?></h2>

				<p><?php _e('Give personalized commands to your AI Assistant and <strong>let it handle difficult and time-consuming activities for you</strong>.', 'vikbooking'); ?></p>
				<p><?php _e('With a dedicated chat-like interface, interacting with your AI-Assistant is easy and quick. Make the most of this cutting-edge technology and watch your AI-Assistant take care in the blink of an eye of tasks that once required several minutes.', 'vikbooking'); ?></p>
				<a href="https://e4jconnect.com/free-channel-manager-pro-trial?utm_source=vbo&amp;utm_medium=ai-assistant&amp;utm_campaign=trial" class="vikwp-btn-link" target="_blank"><?php _e('Start 30-day trial', 'vikbooking') ?></a>		
			</div>
			<div class="vikwppro-header-img">
				<img src="<?php echo VBO_ADMIN_URI; ?>resources/images/pro/ai-assistant.webp" alt="AI Assistant review auto-responder" />
			</div>
		</div>
	</div>

</div>

<script>
	(function($) {
		'use strict';

		$('.viwppro-cnt.vcm-adv.ai').closest('.vbo-modal-overlay-content').addClass('vbo-modal-large');

		document.addEventListener('vbo-admin-dock-restore-aitools', () => {
			$('.viwppro-cnt.vcm-adv.ai').closest('.vbo-modal-overlay-content').addClass('vbo-modal-large');
		});
	})(jQuery);
</script>