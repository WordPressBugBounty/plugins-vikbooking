<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * This layout file should be called once at most per page.
 * 
 * @var string  $vbo_page 	   the name of the current View in VBO.
 * @var string 	$btn_trigger   the CSS selector of the button that opens the panel.
 * @var int     $badge_count   the total number of unread notifications.
 * @var string  $badge_c_attr  the handler data attribute for the badge counter.
 * @var string  $badge_r_attr  the handler data attribute for the readable badge counter.
 */
extract($displayData);

?>

<div class="vbo-notifications-center-wrap vbo-notifications-center-off">
	<div class="vbo-notifications-center-inner"></div>
</div>

<script type="text/javascript">

	/**
	 * @var  bool
	 */
	var vbo_notifs_center_on = false;

	/**
	 * @var  string
	 */
	var vbo_notifs_center_last_keydown = null;

	/**
	 * @var  number
	 */
	var vbo_notifs_center_widget_js_id = 0;
	
	jQuery(function() {

		/**
		 * Register the button trigger click handler.
		 */
		jQuery('<?php echo $btn_trigger; ?>').on('click', function() {
			let wrapper 	 = jQuery('.vbo-notifications-center-wrap');
			let suggest_push = (VBOCore.notificationsEnabled() === false);

			if (!vbo_notifs_center_widget_js_id) {
				vbo_notifs_center_widget_js_id = Math.floor(Math.random() * 100000);
			}

			if (wrapper.hasClass('vbo-notifications-center-off')) {
				// show notifications center
				vbo_notifs_center_on = true;

				wrapper.removeClass('vbo-notifications-center-off');
				wrapper.addClass('vbo-notifications-center-on');

				// build loading string
				let vbo_notifs_center_loading_html = '<div class="vbo-notifications-center-loading">' + "\n";
				vbo_notifs_center_loading_html += '<span><?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw fa-3x'); ?></span>' + "\n";
				vbo_notifs_center_loading_html += '</div>' + "\n";

				// display loading
				wrapper
					.find('.vbo-notifications-center-inner')
					.html(vbo_notifs_center_loading_html);

				// render admin-widget
				VBOCore.renderAdminWidget('notifications_center', {
					_modalRendering: 1,
					_modalJsId:      vbo_notifs_center_widget_js_id,
					_options: {
						inMenu:      true,
						suggestPush: suggest_push,
					},
				}).then((content) => {
					// populate content
					wrapper
						.find('.vbo-notifications-center-inner')
						.html(content);
				}).catch((error) => {
					// display error
					alert(error);
				});
			} else {
				// hide notifications center
				vbo_notifs_center_on = false;

				wrapper.removeClass('vbo-notifications-center-on');
				wrapper.addClass('vbo-notifications-center-off');

				// destroy the wrapper content
				wrapper
					.find('.vbo-notifications-center-inner')
					.html('');

				// emit the dismissed event
				VBOCore.emitEvent(VBOCore.options.widget_modal_dismissed + vbo_notifs_center_widget_js_id);
			}
		});

		// register listener for esc key pressed to close the notifications center
		jQuery(document).keyup(function(e) {
			if (!vbo_notifs_center_on) {
				return;
			}
			if ((e.key && e.key === "Escape") || (e.keyCode && e.keyCode == 27)) {
				jQuery('<?php echo $btn_trigger; ?>').trigger('click');
			}
		});

		// register listener for click on menu to close the notifications center
		jQuery('.vbo-menu-right').click(function(e) {
			if (!vbo_notifs_center_on || jQuery(e.target).closest('.vbo-menu-updates').length) {
				return;
			}
			jQuery('<?php echo $btn_trigger; ?>').trigger('click');
		});

		// register shortcut for toggling the notifications center (CMD + K, CMD + C)
		window.addEventListener('keydown', (e) => {
			e = e || window.event;
			if (!e.key) {
				return;
			}
			if (e.key === 'k' && e.metaKey) {
				// set special key listener for sequences of combos
				e.preventDefault();
				vbo_notifs_center_last_keydown = 'k';
				return;
			}
			if (vbo_notifs_center_last_keydown === 'k' && e.metaKey && e.key === 'c') {
				// trigger event to change color scheme preferences
				e.preventDefault();
				// unset special key
				vbo_notifs_center_last_keydown = '';
				// toggle notifications center
				jQuery('<?php echo $btn_trigger; ?>').trigger('click');
				return;
			}
			// always unset last key if this point gets reached
			vbo_notifs_center_last_keydown = '';
		}, true);

		/**
		 * Listen to the global event for updating the badge counter. This listener will never have to be removed.
		 */
		document.addEventListener('vbo-badge-count', (e) => {
			if (!e || !e.detail || !e.detail.hasOwnProperty('badge_count') || isNaN(e.detail['badge_count'])) {
				return;
			}

			// get the trigger button element
			let btn_trigger = document
				.querySelector('<?php echo $btn_trigger; ?>');

			// get the current badge counter value
			let badge_count_now = parseInt(btn_trigger.getAttribute('<?php echo $badge_c_attr; ?>'));

			// get the new badge counter value
			let badge_count_new = parseInt(e.detail['badge_count']);

			// update badge attributes
			if (badge_count_new <= 0) {
				// no notifications to be read
				btn_trigger.setAttribute('<?php echo $badge_c_attr; ?>', 0);
				btn_trigger.setAttribute('<?php echo $badge_r_attr; ?>', '');
			} else {
				// update badge counter
				btn_trigger.setAttribute('<?php echo $badge_c_attr; ?>', badge_count_new);
				btn_trigger.setAttribute('<?php echo $badge_r_attr; ?>', (badge_count_new > 99 ? '99+' : badge_count_new));
			}

			// check if we had new notifications
			if (badge_count_new > badge_count_now) {
				// add shaking class
				btn_trigger.classList.add('shaking');

				// remove shaking class after some time
				setTimeout(() => {
					btn_trigger.classList.remove('shaking');
				}, 2000);
			}

			// check if support for Web App badge is available
			if (typeof navigator.setAppBadge !== 'undefined') {
				if (badge_count_new > 0) {
					navigator.setAppBadge(badge_count_new);
				} else {
					navigator.clearAppBadge();
				}
			}
		});

		/**
		 * Listen to the event for reloading the badge counter. This listener will never have to be removed.
		 * Reloading the badge counter is helpful when clicking on a Push notification from the ServiceWorker.
		 */
		document.addEventListener('vbo-badge-count-reload', (e) => {
			// the widget method to call
			var call_method = 'countUnreadNotifications';

			// make a request to the admin-widget "notifications center" to count the unread notifications
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
				{
					widget_id: 'notifications_center',
					call:      call_method,
					return:    1,
				},
				(response) => {
					try {
						var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
						if (!obj_res.hasOwnProperty(call_method) || typeof obj_res[call_method] !== 'object') {
							console.error('Unexpected JSON response', obj_res);
							return false;
						}

						// scan all the events to dispatch
						for (let ev_name in obj_res[call_method]) {
							if (!obj_res[call_method].hasOwnProperty(ev_name)) {
								continue;
							}
							// emit the event
							VBOCore.emitEvent(ev_name, {
								badge_count: obj_res[call_method][ev_name],
							});
						}
					} catch(err) {
						console.error('could not parse JSON response', err, response);
					}
				},
				(error) => {
					// silently log the error
					console.error(error);
				}
			);
		});

		/**
		 * Listen to the event for reading notifications matching certain criterias.
		 * This listener will never have to be removed.
		 */
		document.addEventListener('vbo-nc-read-notifications', (e) => {
			if (!e || !e.detail || !e.detail.hasOwnProperty('criteria') || !e.detail['criteria']) {
				return;
			}

			// the widget method to call
			var call_method = 'readMatchingNotifications';

			// make a request to the admin-widget "notifications center" to read any matching notification
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
				{
					widget_id: 'notifications_center',
					call:      call_method,
					return:    1,
					criteria:  e.detail['criteria'],
				},
				(response) => {
					try {
						var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
						if (!obj_res.hasOwnProperty(call_method) || typeof obj_res[call_method] !== 'object') {
							console.error('Unexpected JSON response', obj_res);
							return false;
						}

						if (obj_res[call_method].hasOwnProperty('read_count') && obj_res[call_method]['read_count']) {
							// trigger the event to reload the badge count after having read some notifications
							VBOCore.emitEvent('vbo-badge-count-reload');
						}
					} catch(err) {
						console.error('could not parse JSON response', err, response);
					}
				},
				(error) => {
					// silently log the error
					console.error(error);
				}
			);
		});

	});

</script>
