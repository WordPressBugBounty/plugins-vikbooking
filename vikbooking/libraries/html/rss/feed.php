<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	html.rss
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$feed = !empty($displayData['feed']) ? $displayData['feed'] : null;

// prepare modal footer
$footer  = '<div style="float: left;">';
$footer .= '<input type="checkbox" value="1" id="rss-feed-remind">';
$footer .= '<label for="rss-feed-remind" style="line-height: 13px;">' . __('Remind me later') . '</label>';
$footer .= '</div>';
$footer .= '<button type="button" class="btn btn-danger" id="rss-feed-dismiss">' . __('Don\'t show again', 'vikbooking') . '</button>';

// prepare modal to display opt-in
echo JHtml::fetch(
	'bootstrap.renderModal',
	'jmodal-rss-feed',
	array(
		'title'       => '<i class="' . VikBookingIcons::i('rss-square') . '"></i> ' . $feed->category . ' - ' . $feed->title,
		'closeButton' => true,
		'keyboard'    => false,
		'top'         => true,
		'width'       => 70,
		'height'      => 80,
		'footer'      => $footer,
	),
	$feed->content
);

?>

<style>
	#jmodal-rss-feed img {
		max-width: 100%;
	}
</style>

<script>

	jQuery(document).ready(function() {

		var dismissed = false;
		
		if (typeof localStorage !== 'undefined') {
			dismissed = localStorage.getItem('vikbooking.rss.dismissed.<?php echo $feed->id; ?>') ? true : false;
		}

		if (!dismissed) {
			// open modal with a short delay
			setTimeout(function() {
				wpOpenJModal('rss-feed');
			}, 1500);
		}

		jQuery('#rss-feed-remind').on('change', function() {
			var btn = jQuery('#rss-feed-dismiss');

			if (jQuery(this).is(':checked')) {
				btn.removeClass('btn-danger').addClass('btn-success');
				btn.text('<?php echo addslashes(__('Close')); ?>');
			} else {
				btn.removeClass('btn-success').addClass('btn-danger');
				btn.text('<?php echo addslashes(__('Don\'t show again', 'vikbooking')); ?>');
			}
		});

		jQuery('#rss-feed-dismiss').on('click', function() {
			if (jQuery(this).prop('disabled')) {
				// already submitted
				return false;
			}

			jQuery(this).prop('disabled', true);

			// prepare request
			var url  = 'admin-ajax.php?action=vikbooking&task=rss.';
			var data = {
				id: '<?php echo $feed->id; ?>',
			};

			// look for reminder option
			if (jQuery('#rss-feed-remind').is(':checked')) {
				url += 'remind';
				// show again in 2 hours
				data.delay = 120;
			} else {
				url += 'dismiss';
			}

			doAjax(
				url,
				data,
				function(resp) {
					// auto-dismiss on save
					wpCloseJModal('rss-feed');
				},
				function(error) {
					if (!error.responseText) {
						// use default connection lost error
						error.responseText = Joomla.JText._('CONNECTION_LOST');
					}

					// alert error message
					alert(error.responseText);

					// avoid to spam the dialog again and again at every page load
					if (typeof localStorage !== 'undefined') {
						localStorage.setItem('vikbooking.rss.dismissed.<?php echo $feed->id; ?>', 1);
					}

					// auto-dismiss on failure
					wpCloseJModal('rss-feed');
				}
			);
		});
	});

</script>
