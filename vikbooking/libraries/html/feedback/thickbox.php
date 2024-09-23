<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	html.feedback
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$deactivate_url_js = !empty($displayData['url']) ? $displayData['url'] : '#';
$is_pro            = isset($displayData['pro'])  ? $displayData['pro'] : false;

$plain_deactivation_url = JUri::getInstance();
$plain_deactivation_url->setVar('feedback', 0);

$doc_url = 'https://vikwp.com/support/documentation/vik-booking/';

$options = array();

$options[] = array(
	'value'   => '0',
	'text'    => __('No, thanks', 'vikbooking'),
	'checked' => true,
);

$options[] = array(
	'value'  => 'The plugin is not working',
	'text'   => __('The plugin is not working', 'vikbooking'),
	'notes'  => true,
);

$options[] = array(
	'value'  => 'The plugin didn\'t work as expected',
	'text'   => __('The plugin didn\'t work as expected', 'vikbooking'),
	'notes'  => true,
);

if (!$is_pro)
{
	$options[] = array(
		'value'  => 'The FREE version is too limited',
		'text'   => __('The FREE version is too limited', 'vikbooking'),
		'notes'  => array(
			'required'    => false,
			'placeholder' => __('What features would you like to have?', 'vikbooking'),
		),
	);
}

$options[] = array(
	'value'  => 'The plugin suddenly stopped working',
	'text'   => __('The plugin suddenly stopped working', 'vikbooking'),
	'notes'  => true,
);

$options[] = array(
	'value'  => 'The plugin broke my site',
	'text'   => __('The plugin broke my site', 'vikbooking'),
	'notes'  => array(
		'placeholder' => __('Would you please explain what happened?', 'vikbooking'),
	),
);

$options[] = array(
	'value'  => 'I couldn\'t understand how to get it working',
	'text'   => __('I couldn\'t understand how to get it working', 'vikbooking'),
	'help'   => sprintf(__('Did you read the plugin documentation? You can find it <a href="%s" target="_blank">here</a>.', 'vikbooking'), $doc_url),
);

$options[] = array(
	'value'  => 'I no longer need the plugin',
	'text'   => __('I no longer need the plugin', 'vikbooking'),
);

$options[] = array(
	'value'  => 'I\'m just debugging an issue',
	'text'   => __('I\'m just debugging an issue', 'vikbooking'),
);

$options[] = array(
	'value'  => 'I\'m unable to deactivate this plugin!',
	'text'   => __('I\'m unable to deactivate this plugin!', 'vikbooking'),
	'help'   => sprintf(__('In case you are experiencing some issues with the deactivation of this plugin, try to relaunch the page by appending <code>&feedback=0</code> to the URL. Otherwise just click <a href="%s">HERE</a>.', 'vikbooking'), (string) $plain_deactivation_url),
);

$options[] = array(
	'value'  => 'Other',
	'text'   => __('Other', 'vikbooking'),
	'notes'  => array(
		'required'    => true,
		'placeholder' => __('Please give us more information', 'vikbooking'),
	),
);

?>

<style>
	.thickbox-loading {
		height: auto !important;
	}

	#TB_ajaxContent {
		width: calc(100% - 30px) !important;
		height: calc(100% - 45px) !important;
	}

	.th-box-body {
		height: calc(100% - 45px);
	}
	
	.th-box-footer {
		text-align: right;
	}

	.th-box-body div.form-field {
		margin: 5px 0;
	}

	.th-box-body blockquote {
		color: #7f8fa4;
		font-size: inherit;
		padding: 8px 24px;
		border-left: 4px solid #eaeaea;
		background: #fff;
	}

	.feedback-notes {
		margin-top: 7px;
		padding: 5px 10px;
	}

	textarea.invalid {
		border: 1px solid #900;
	}

	#vikbooking-deactivate-uemail {
		margin-bottom: 4px;
		display: inline-block;
	}
</style>

<div id="vikbooking-feedback" style="display: none;">

	<div class="th-box-body">
		<h2><?php echo __('Feedback', 'vikbooking'); ?></h2>
		<h3><?php echo __('If you have a moment, please let us know why you are deactivating.', 'vikbooking'); ?></h3>

		<form id="vikbooking-feedback-form">

		<?php
		foreach ($options as $i => $opt)
		{
			?>
			<div class="form-field">
				<input
					type="radio"
					id="vbo-feed-opt-<?php echo $i; ?>"
					name="feedback_type"
					value="<?php echo esc_attr($opt['value']); ?>"
					<?php echo !empty($opt['checked']) ? 'checked="checked"' : ''; ?>
				/>
				<label for="vbo-feed-opt-<?php echo $i; ?>"><?php echo $opt['text']; ?></label>

				<?php
				if (!empty($opt['help']))
				{
					?>
					<blockquote
						class="feedback-help"
						style="<?php echo !empty($opt['checked']) ? '' : 'display:none;'; ?>"
					>
						<?php echo $opt['help']; ?>
					</blockquote>
					<?php
				}

				if (!empty($opt['notes']))
				{
					$opt['notes'] = (array) $opt['notes'];

					$placeholder = !empty($opt['notes']['placeholder'])
						? $opt['notes']['placeholder']
						: __('Could you please write some extra notes?', 'vikbooking');

					?>
					<textarea
						class="feedback-notes<?php echo !empty($opt['notes']['required']) ? ' required' : ''; ?>"
						placeholder="<?php echo $placeholder; ?>"
						style="<?php echo !empty($opt['checked']) ? '' : 'display:none;'; ?>"
					></textarea>
					<?php
				}
				?>

			</div>
			<?php
		}
		?>

			<div class="form-field">
				<p style="margin-bottom: 2px;">
					<strong>
						<?php echo __('Would you like to be contacted by our team for a clarification?', 'vikbooking'); ?>
					</strong>
				</p>
				<span>
					<small>
						<?php echo __('If you believe you could have missed a feature in the plugin, please drop us your email and we will get back to you with <u>one</u> message.', 'vikbooking'); ?>
					</small>
				</span>
				<div>
					<input type="email" value="" placeholder="your@email.com" id="vikbooking-deactivate-uemail" />
				</div>
			</div>

			<p>
				<small>
					<span class="dashicons dashicons-editor-help"></span>&nbsp;
					<?php echo __('In addition to the specified information, the feedback will contain your IP address (for security reasons), your PHP version, your WordPress version and the plugin version. If provided, we will not use your email address for any kind of newsletter, nor will we share it with anyone. We will only answer to your questions.', 'vikbooking'); ?>
				</small>
			</p>

		</form>
	</div>

	<div class="th-box-footer">
		<a href="javascript: void(0);" id="vikbooking-deactivate" class="button button-primary">
			<?php echo __('Deactivate'); ?>
		</a>
	</div>

</div>

<script>
	jQuery(document).ready(function() {

		jQuery('#vikbooking-feedback-form .form-field input[name="feedback_type"]').on('change', function() {
			var radio = jQuery('#vikbooking-feedback-form input[name="feedback_type"]:checked');

			jQuery('.feedback-help,.feedback-notes').hide();
			radio.siblings('.feedback-help,.feedback-notes').show().focus();
		});

		jQuery('#vikbooking-deactivate').on('click', function() {
			var radio = jQuery('#vikbooking-feedback-form input[name="feedback_type"]:checked');

			// get selected feedback type
			var type = radio.val();

			if (type == '0') {
				// deactivate automatically
				document.location.href = '<?php echo $deactivate_url_js; ?>';
				return;
			}

			// get textarea
			var textarea = radio.siblings('.feedback-notes');
			var comment  = textarea.length ? textarea.val() : null;

			if (textarea.length && !textarea.val().length && textarea.hasClass('required')) {
				textarea.addClass('invalid');
				return;
			}

			// email address (optional)
			var uemail = jQuery('#vikbooking-deactivate-uemail').val();

			// disable button
			jQuery(this).attr('disabled', true);

			textarea.removeClass('invalid');

			// do ajax and redirect on success/failure
			jQuery.ajax({
				url: 'admin-ajax.php?action=vikbooking&task=feedback.submit',
				type: 'post',
				data: {
					type:  type,
					notes: comment,
					email: uemail,
				},
			}).done(function(resp) {
				// keep a cookie for 7 days in order to stop showing the feedback
				// modal again and again
				var date = new Date();
				date.setDate(date.getDate() + 7);
				document.cookie = 'vikbooking_feedback=1; expires=' + date.toUTCString() + '; path=/; SameSite=Lax';
				// complete deactivation
				document.location.href = '<?php echo $deactivate_url_js; ?>';
			}).fail(function(err) {
				console.log(err);
				// complete deactivation
				document.location.href = '<?php echo $deactivate_url_js; ?>';
			});
		});

	});
</script>
