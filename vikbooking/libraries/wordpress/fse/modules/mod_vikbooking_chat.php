<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	wordpress
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$app = JFactory::getApplication();

VikBooking::loadPreferredColorStyles();
VikBooking::loadFontAwesome(true);

$options = [
	'version' => '1.0',
];

JHtml::fetch('stylesheet', VBO_MODULES_URI . 'modules/mod_vikbooking_chat/assets/style.css', $options);

$params = new JRegistry($app->input->get('attributes', [], 'array'));

$faqs = [];

// prepare FAQs
for ($i = 1; $i <= 3; $i++) {
	$short = $params->get('faq_' . $i . '_short');
	$long = $params->get('faq_' . $i . '_long');

	if ($short && $long) {
		$faqs[] = (object) [
			'summary' => $short,
			'question' => $long,
		];
	}
}

?>

<style>
	.vbo-chat-session-widget {
		--vbo-chat-widget-btn-background: <?php echo $params->get('background'); ?>;
		--vbo-chat-widget-btn-color: <?php echo $params->get('color'); ?>;
	}
	.widget-block-preview {
		opacity: 0;
	}
	.widget-block-preview.fade-in {
		animation: fadeIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
	}
	@keyframes fadeIn {
		0% {
			opacity: 0;
		}
		50% {
			opacity: 0;
		}
		100% {
			opacity: 1;
		}
	}
	.widget-block-preview .vbo-chat-session-widget .vbo-chat-widget-container .vbo-chat-widget-body .vbo-chat-session-placeholder {
		display: block !important;
	}
	.widget-block-preview .vbo-chat-widget-toggle {
		text-align: right;
		margin-top: 10px;
	}
	.widget-block-preview .vbo-chat-widget-toggle a {
		display: inline-flex;
	}
	.widget-block-preview .vbo-chat-session-widget .vbo-chat-wrapper .chat-input-footer {
		position: absolute;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 10px;
		background: #fff; /* @todo */
	}
	.widget-block-preview .vbo-chat-wrapper textarea {
		resize: none;
		height: 40px;
		margin: 0;
	    border: 1px solid #999;
	    border-radius: 20px;
	    font-size: 14px;
	    padding: 10px 40px 10px 12px;
	    width: 100%;
	}
</style>

<div class="widget-block-preview fade-in">
	
	<div class="vbo-chat-session-widget">

		<div class="vbo-chat-widget-container slide-in">

			<div class="vbo-chat-widget-head">
				<span>
					<?php echo $params->get('title') ?: JText::translate('MOD_VIKBOOKING_CHAT_TITLE'); ?>
				</span>

				<a href="javascript:void(0)" onclick="document.getElementsByClassName('vbo-chat-widget-container')[0].classList.remove('slide-in')">
					<?php VikBookingIcons::e('chevron-down'); ?>
				</a>
			</div>

			<div class="vbo-chat-widget-body">

				<div class="vbo-chat-wrapper">
					<div class="chat-conversation">

					</div>
					<div class="chat-input-footer">
						<textarea readonly placeholder="<?php echo htmlspecialchars(JText::translate('VBO_CHAT_TEXTAREA_PLACEHOLDER')); ?>"></textarea>
					</div>
				</div>

				<div class="vbo-chat-session-placeholder">
					
					<div class="vbo-chat-session-placeholder-inner">

						<div class="vbo-chat-placeholder-greetings">
							<div class="greetings-title">
								<?php echo $params->get('greetings_title') ?: JText::translate('MOD_VIKBOOKING_CHAT_GREETINGS_TITLE'); ?>
							</div>
							
							<div class="greetings-subtitle">
								<?php echo $params->get('greetings_subtitle') ?: JText::translate('MOD_VIKBOOKING_CHAT_GREETINGS_SUBTITLE'); ?>
							</div>
						</div>

						<?php if ($faqs): ?>
							<div class="vbo-chat-placeholder-faqs slide-in">
								<ul>
									<?php foreach ($faqs as $faq): ?>
										<li>
											<a href="javascript:void(0)" data-question="<?php echo htmlspecialchars($faq->question); ?>">
												<?php VikBookingIcons::e('life-ring'); ?>
												<?php echo $faq->summary; ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

					</div>

				</div>

			</div>

		</div>

		<div class="vbo-chat-widget-toggle bounce-in">
			<a href="javascript:void(0)" onclick="document.getElementsByClassName('vbo-chat-widget-container')[0].classList.add('slide-in')">
				<?php VikBookingIcons::e('comment'); ?>
			</a>    
		</div>

	</div>

</div>
