/**
 * VikBooking Core v1.8.0
 * Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * https://vikwp.com | https://e4j.com | https://e4jconnect.com
 */
(function($, w) {
	'use strict';

	/**
	 * AJAX loading of Views may reload the already existing options, so we cache them when available.
	 */
	var CORE_OPTIONS = typeof VBOCore !== 'undefined' ? VBOCore?.options : null;

	/**
	 * VBOCore class implementation.
	 */
	w['VBOCore'] = class VBOCore {

		/**
		 * Proxy to support static injection of params.
		 */
		constructor(params) {
			VBOCore.setOptions(params);
		}

		/**
		 * Inject options by overriding default properties.
		 * 
		 * @param 	object 	params
		 * 
		 * @return 	self
		 */
		static setOptions(params) {
			if (params != null && typeof params === 'object') {
				VBOCore.options = Object.assign(VBOCore.options, params);
			}

			return VBOCore;
		}

		/**
		 * Fires a given function when the DOM content has loaded.
		 * 
		 * @param 	function 	fireFn 	The function to fire when the document is ready.
		 * 
		 * @return 	undefined
		 */
		static DOMLoaded(fireFn) {
			if (typeof fireFn !== 'function') {
				throw new Error('Invalid argument provided');
			}

			if (document.readyState === 'loading') {
				// register event because DOMContentLoaded hasn't finished yet
				document.addEventListener('DOMContentLoaded', fireFn);
			} else {
				// DOMContentLoaded event has fired already
				fireFn();
			}
		}

		/**
		 * Getter for admin_widgets private options property.
		 * 
		 * @return 	array
		 */
		static get admin_widgets() {
			return VBOCore.options.admin_widgets;
		}

		/**
		 * Getter for multitask open event private property.
		 * 
		 * @return 	string
		 */
		static get multitask_open_event() {
			return VBOCore.options.multitask_open_event;
		}

		/**
		 * Getter for multitask close event private property.
		 * 
		 * @return 	string
		 */
		static get multitask_close_event() {
			return VBOCore.options.multitask_close_event;
		}

		/**
		 * Getter for multitask shortcut event private property.
		 * 
		 * @return 	string
		 */
		static get multitask_shortcut_event() {
			return VBOCore.options.multitask_shortcut_ev;
		}

		/**
		 * Getter for multitask seach focus shortcut event private property.
		 * 
		 * @return 	string
		 */
		static get multitask_searchfs_event() {
			return VBOCore.options.multitask_searchfs_ev;
		}

		/**
		 * Getter for multitask event widget modal rendered.
		 * 
		 * @return 	string
		 */
		static get widget_modal_rendered() {
			return VBOCore.options.widget_modal_rendered;
		}

		/**
		 * Getter for widget modal dismiss event.
		 * 
		 * @return 	string
		 */
		static get widget_modal_dismissed() {
			return VBOCore.options.widget_modal_dismissed;
		}

		/**
		 * Getter for the service worker file path.
		 * 
		 * @return 	string
		 */
		static get service_worker_path() {
			return VBOCore.options.service_worker_path;
		}

		/**
		 * Getter for the service worker scope.
		 * 
		 * @return 	string
		 */
		static get service_worker_scope() {
			return VBOCore.options.service_worker_scope;
		}

		/**
		 * Getter for the push application key.
		 * 
		 * @return 	string
		 */
		static get push_application_key() {
			return VBOCore.options.push.application_key;
		}

		/**
		 * Getter for the push storage endpoint identifier.
		 * 
		 * @return 	string
		 */
		static get push_storage_endpoint_id() {
			return VBOCore.options.push_options.storage_endp_id;
		}

		/**
		 * Parses an AJAX response error object.
		 * 
		 * @param 	object  err
		 * 
		 * @return  bool
		 */
		static isConnectionLostError(err) {
			if (!err || !err.hasOwnProperty('status')) {
				return false;
			}

			return (
				err.statusText == 'error'
				&& err.status == 0
				&& (err.readyState == 0 || err.readyState == 4)
				&& (!err.hasOwnProperty('responseText') || err.responseText == '')
			);
		}

		/**
		 * Ensures AJAX requests that fail due to connection errors are retried automatically.
		 * 
		 * @param 	string  	url
		 * @param 	object 		data
		 * @param 	function 	success
		 * @param 	function 	failure
		 * @param 	number 		attempt
		 */
		static doAjax(url, data, success, failure, attempt) {
			const AJAX_MAX_ATTEMPTS = 3;

			if (attempt === undefined) {
				attempt = 1;
			}

			return $.ajax({
				type: 'POST',
				url: url,
				data: data
			}).done(function(resp) {
				if (success !== undefined) {
					// launch success callback function
					success(resp);
				}
			}).fail(function(err) {
				/**
				 * If the error is caused by a site connection lost, and if the number
				 * of retries is lower than max attempts, retry the same AJAX request.
				 */
				if (attempt < AJAX_MAX_ATTEMPTS && VBOCore.isConnectionLostError(err)) {
					// delay the retry by half second
					setTimeout(function() {
						// re-launch same request and increase number of attempts
						console.log('Retrying previous AJAX request');
						VBOCore.doAjax(url, data, success, failure, (attempt + 1));
					}, 500);
				} else {
					// launch the failure callback otherwise
					if (failure !== undefined) {
						if (err.responseText === 'false') {
							// make the property empty to rely on others
							err.responseText = '';
						}
						failure(err);
					}
				}

				if (!err.status || err.status == 500) {
					// log the error in console
					console.error('AJAX request failed' + (err.status == 500 ? ' (' + err.responseText + ')' : ''), err);
				}
			});
		}

		/**
		 * Matches a keyword against a text.
		 * 
		 * @param 	string 	search 	the keyword to search.
		 * @param 	string 	text 	the text to compare.
		 * 
		 * @return 	bool
		 */
		static matchString(search, text) {
			return ((text + '').indexOf(search) >= 0);
		}

		/**
		 * Converts a base64 URL for a server key into an array of 8-bit unsigned integers.
		 * 
		 * @param 	string 	key 	the base64 encoded URL key.
		 * 
		 * @return 	Uint8Array
		 */
		static base64ToUint8Array(key) {
			const padding = ('=').repeat((4 - (key.length % 4)) % 4);
			const base64  = (key + padding).replace(/\-/g, '+').replace(/_/g, '/');

			const rawData   = window.atob(base64);
			const uint8_arr = new Uint8Array(rawData.length);

			for (let i = 0; i < rawData.length; ++i) {
				uint8_arr[i] = rawData.charCodeAt(i);
			}

			return uint8_arr;
		}

		/**
		 * Builds an object containing the Push subscription details and data.
		 * 
		 * @param 	PushSubscription 	pushSubscription 	the registration data.
		 * @param 	object 				data 				the extra data to merge.
		 * 
		 * @return 	object
		 */
		static buildPushSubscriptionData(pushSubscription, data) {
			if (!(pushSubscription instanceof PushSubscription)) {
				return data;
			}

			let key			  	= pushSubscription.getKey('p256dh');
			let token 		  	= pushSubscription.getKey('auth');
			let contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

			let details = {
				endpoint:  pushSubscription.endpoint,
				publicKey: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
				authToken: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
				encoding:  contentEncoding,
			};

			return Object.assign(details, data);
		}

		/**
		 * Attempts to install (and eventually update) the VikBooking's Service Worker.
		 * 
		 * @param 	bool 	update 	if defined, it will update the Service Worker Registration.
		 * 
		 * @return 	Promise
		 */
		static installServiceWorker(update) {
			// check if support for Web App badge is available
			if (typeof navigator.setAppBadge !== 'undefined') {
				// always clear the app badge
				navigator.clearAppBadge();
			}

			return new Promise((resolve, reject) => {
				if (!('serviceWorker' in navigator)) {
					reject('Service workers are not supported by this browser');
					return;
				}

				if (!('PushManager' in window)) {
					reject('Push notifications are not supported by this browser');
					return;
				}

				if (!VBOCore.service_worker_path) {
					reject('Service Worker path not configured');
					return;
				}

				if (!VBOCore.notificationsEnabled()) {
					reject('Notifications are disabled, and so the service worker will not be installed');
					return;
				}

				navigator.serviceWorker.register(VBOCore.service_worker_path, {scope: VBOCore.service_worker_scope}).then(
					(registration) => {
						if (typeof update !== 'undefined') {
							// attempt to update the registration for un-caching purposes
							registration.update().then((updated) => {
								// all good
							}).catch((e) => {
								// updating should never fail after registering with success
								console.error(`Service worker registration update failed: ${e}`);
							});
						}

						// listen to the ServiceWorker messages for the client
						VBOCore.listenServiceWorkerMessages();

						// resolve the Promise
						resolve(registration);
					},
					(e) => {
						console.error(`Service worker registration failed: ${e}`);
						reject('Service worker registration failed');
					}
				);
			});
		}

		/**
		 * Handles the Push subscription state. To be called after the
		 * Service Worker installation (registration) is ready.
		 * 
		 * @param 	ServiceWorkerRegistration 	registration 	sw registration object.
		 * 
		 * @return 	Promise
		 */
		static handlePushSubscription(registration) {
			return new Promise((resolve, reject) => {
				if (!(registration instanceof ServiceWorkerRegistration)) {
					reject('Missing service worker registration');
					return;
				}

				if (!VBOCore.push_application_key) {
					reject('Missing application key for Push because not supported');
					return;
				}

				// check if a push subscription is available
				registration.pushManager.getSubscription().then((pushSubscription) => {
					if (pushSubscription) {
						// we are subscribed to Push
						let previous_endpoint = VBOCore.storageGetItem(VBOCore.push_storage_endpoint_id);
						if (previous_endpoint && pushSubscription.endpoint != previous_endpoint) {
							// update subscription data
							VBOCore.doAjax(
								VBOCore.options.push.ajax_url,
								VBOCore.buildPushSubscriptionData(pushSubscription, {
									agent: window.navigator.userAgent,
									type: 'update',
								})
							);
						}

						// resolve the promise with the Push subscription data
						resolve(pushSubscription);
						return;
					}

					// push subscription options
					const pushOptions = {
						userVisibleOnly: true,
						applicationServerKey: VBOCore.base64ToUint8Array(VBOCore.push_application_key),
					};

					// subscribe to Push
					registration.pushManager.subscribe(pushOptions).then(
						(pushSubscription) => {
							// store subscription data
							VBOCore.storageSetItem(VBOCore.push_storage_endpoint_id, pushSubscription.endpoint);
							VBOCore.doAjax(
								VBOCore.options.push.ajax_url,
								VBOCore.buildPushSubscriptionData(pushSubscription, {
									agent: window.navigator.userAgent,
									type: 'new',
								})
							);

							// resolve the promise with the Push subscription data
							resolve(pushSubscription);
						},
						(e) => {
							console.error(`Error handling the Push subscription state: ${e}`);
							reject('Error handling the Push subscription state');
						}
					);
				})
				.catch((e) => {
					console.error(`Error getting the Push subscription state: ${e}`);
					reject('Error getting the Push subscription state');
				});
			});
		}

		/**
		 * Starts listening to the "message" events posted by the ServiceWorker.
		 * 
		 * @return 	void
		 */
		static listenServiceWorkerMessages() {
			if (!('serviceWorker' in navigator)) {
				return;
			}

			try {
				// add listener to the message event for the data posted by the ServiceWorker to the client
				navigator.serviceWorker.addEventListener('message', (event) => {
					VBOCore.handleServiceWorkerMessage(event.data);
				});
			} catch(e) {
				// do nothing
			}
		}

		/**
		 * Handles a message data posted by the ServiceWorker upon clicking a Push notification.
		 * 
		 * @param 	object 	data 	the event.notification.data object posted by the ServiceWorker.
		 * 
		 * @return 	void
		 */
		static handleServiceWorkerMessage(data) {
			let notif_type = data.type || '';

			if (!notif_type || !VBOCore.options.push_options.allowed_notif_types.includes(notif_type)) {
				console.error(data);
				throw new Error('Unsupported message type');
			}

			// determine the proper widget for dispatching the message data
			let widget_id = 'booking_details';
			if (notif_type == 'Chat') {
				widget_id = 'guest_messages';
			}

			// the raw notification content
			let raw_content = data.content || {};

			// multitask data options
			let options = Object.assign({
				_push: 	 1,
				title: 	 data.title || '',
				message: data.message || '',
			}, raw_content);

			// dispatch (Push) message data on widget
			VBOCore.handleDisplayWidgetNotification({widget_id: widget_id}, options, true);

			// update pushed data map for the next watching event in the current browsing context
			VBOCore.widgets_pushed_data.push(raw_content);

			// post message onto broadcast channel for any other browsing context
			if (VBOCore.broadcast_push_data) {
				// the next watch-data interval will receive the pushed data information
				VBOCore.broadcast_push_data.postMessage(raw_content);
			}

			// let the window reload the badge counter in case it must be updated
			VBOCore.emitEvent('vbo-badge-count-reload');
		}

		/**
		 * Ensures VBO is ready to support VCM.
		 * 
		 * @param 	any 	env 	optional data to evaluate.
		 * 
		 * @return 	bool
		 */
		static vcmMultitasking(env) {
			// tell VCM that VBOCore supports it
			return true;
		}

		/**
		 * Initializes the multitasking panel for the admin widgets.
		 * 
		 * @param 	object 	params 	the panel object params.
		 * 
		 * @return 	bool
		 */
		static prepareMultitasking(params) {
			var panel_opts = {
				selector: 		 "",
				sclass_l_small:  "vbo-sidepanel-right",
				sclass_l_large:  "vbo-sidepanel-large",
				btn_trigger: 	 "",
				search_selector: "#vbo-sidepanel-search-input",
				search_nores: 	 ".vbo-sidepanel-add-widgets-nores",
				close_selector:  ".vbo-sidepanel-dismiss-btn",
				t_layout_small:	 ".vbo-sidepanel-layout-small",
				t_layout_large:  ".vbo-sidepanel-layout-large",
				wclass_base_sel: ".vbo-admin-widgets-widget-output",
				wclass_l_small:  "vbo-admin-widgets-container-small",
				wclass_l_large:  "vbo-admin-widgets-container-large",
				addws_selector:	 ".vbo-sidepanel-add-widgets",
				addw_selector:	 ".vbo-sidepanel-add-widget",
				addw_modal_cls:  "vbo-widget-render-modal",
				addw_def_cls:    "vbo-widget-render-regular",
				addwfs_selector: ".vbo-sidepanel-add-widget-focussed",
				wtags_selector:	 ".vbo-sidepanel-widget-tags",
				wname_selector:	 ".vbo-sidepanel-widget-name",
				addw_data_attr:  "data-vbowidgetid",
				actws_selector:  ".vbo-sidepanel-active-widgets",
				editw_selector:  ".vbo-sidepanel-edit-widgets-trig",
				shortc_selector: ".vbo-sidepanel-shortcut",
				rmwidget_class:  "vbo-admin-widgets-widget-remove",
				rmwidget_icn: 	 "",
				dtcwidget_class: "vbo-admin-widgets-widget-detach",
				dtctarget_class: "vbo-admin-widget-head",
				dtcwidget_icn: 	 "",
				notif_selector:  ".vbo-sidepanel-notifications-btn",
				notif_on_class:  "vbo-sidepanel-notifications-on",
				notif_off_class: "vbo-sidepanel-notifications-off",
				open_class: 	 "vbo-sidepanel-open",
				close_class: 	 "vbo-sidepanel-close",
				cur_widget_cls:  "vbo-admin-widgets-container-small",
				sortable:        true,
				sort_save_ev:    "vbo-admin-widgets-updateposmp",
				sorting:         null,
			};

			if (typeof params === 'object') {
				panel_opts = Object.assign(panel_opts, params);
			}

			if (!panel_opts.btn_trigger || !panel_opts.selector) {
				console.error('Got no trigger or selector');
				return false;
			}

			// push panel options
			VBOCore.setOptions({
				panel_opts: panel_opts,
			});

			if (VBOCore.options.is_vbo) {
				// setup browser notifications
				VBOCore.setupNotifications();
			}

			// count active widgets on current page
			var tot_active_widgets = VBOCore.options.admin_widgets.length;
			if (tot_active_widgets > 0) {
				// hide add-widgets container
				$(panel_opts.addws_selector).hide();

				// register listener for input search blur
				VBOCore.registerSearchWidgetsBlur();
			}

			// register click event on trigger button
			$(VBOCore.options.panel_opts.btn_trigger).on('click', function() {
				var side_panel = $(VBOCore.options.panel_opts.selector);
				if (side_panel.hasClass(VBOCore.options.panel_opts.open_class)) {
					// hide panel
					VBOCore.side_panel_on = false;
					VBOCore.emitMultitaskEvent(VBOCore.multitask_close_event);
					side_panel.addClass(VBOCore.options.panel_opts.close_class).removeClass(VBOCore.options.panel_opts.open_class);
					// always hide add-widgets container
					$(VBOCore.options.panel_opts.addws_selector).hide();
					// check if we are currently editing
					var is_editing = ($('.' + VBOCore.options.panel_opts.editmode_class).length > 0);
					if (is_editing) {
						// deactivate editing mode
						VBOCore.toggleWidgetsPanelEditing(null);
					}
				} else {
					// show panel
					VBOCore.side_panel_on = true;
					VBOCore.emitMultitaskEvent(VBOCore.multitask_open_event);
					side_panel.addClass(VBOCore.options.panel_opts.open_class).removeClass(VBOCore.options.panel_opts.close_class);
					if (!VBOCore.options.admin_widgets.length) {
						// set focus on search widgets input with delay for the opening animation
						setTimeout(function() {
							$(VBOCore.options.panel_opts.search_selector).focus();
						}, 300);
					}
				}
			});

			// register close/dismiss button
			$(VBOCore.options.panel_opts.close_selector).on('click', function() {
				$(VBOCore.options.panel_opts.btn_trigger).trigger('click');
			});

			if (VBOCore.options.is_vbo) {
				// register toggle layout buttons
				$(VBOCore.options.panel_opts.t_layout_large).on('click', function() {
					// large layout
					$(VBOCore.options.panel_opts.selector).addClass(VBOCore.options.panel_opts.sclass_l_large).removeClass(VBOCore.options.panel_opts.sclass_l_small);
					$(VBOCore.options.panel_opts.wclass_base_sel).addClass(VBOCore.options.panel_opts.wclass_l_large).removeClass(VBOCore.options.panel_opts.wclass_l_small);
					VBOCore.options.panel_opts.cur_widget_cls = VBOCore.options.panel_opts.sclass_l_large;
				});
				$(VBOCore.options.panel_opts.t_layout_small).on('click', function() {
					// small layout
					$(VBOCore.options.panel_opts.selector).addClass(VBOCore.options.panel_opts.sclass_l_small).removeClass(VBOCore.options.panel_opts.sclass_l_large);
					$(VBOCore.options.panel_opts.wclass_base_sel).addClass(VBOCore.options.panel_opts.wclass_l_small).removeClass(VBOCore.options.panel_opts.wclass_l_large);
					VBOCore.options.panel_opts.cur_widget_cls = VBOCore.options.panel_opts.sclass_l_small;
				});
			}

			// register listener for esc key pressed
			$(document).keyup(function(e) {
				if (!VBOCore.side_panel_on) {
					return;
				}
				if ((e.key && e.key === "Escape") || (e.keyCode && e.keyCode == 27)) {
					$(VBOCore.options.panel_opts.btn_trigger).trigger('click');
				}
			});

			// register listener for input search focus
			$(VBOCore.options.panel_opts.search_selector).on('focus', function() {
				// always show add-widgets container
				var widget_focus_class = VBOCore.options.panel_opts.addwfs_selector.replace('.', '');
				$(VBOCore.options.panel_opts.addw_selector).removeClass(widget_focus_class);
				$(VBOCore.options.panel_opts.addws_selector).show();
			});

			// register listener on input search widget
			$(VBOCore.options.panel_opts.search_selector).keyup(function(e) {
				// get the keyword to look for
				var keyword = $(this).val();
				// counting matching widgets
				var matching = 0;
				var first_matched = null;
				var widget_focus_class = VBOCore.options.panel_opts.addwfs_selector.replace('.', '');

				// adjust widgets to be displayed
				if (!keyword.length) {
					// show all widgets for selection
					$(VBOCore.options.panel_opts.addw_selector).show();
					// hide "no results"
					$(VBOCore.options.panel_opts.search_nores).hide();
					// all widgets are matching
					matching = $(VBOCore.options.panel_opts.addw_selector).length;
				} else {
					// make the keyword lowercase
					keyword = (keyword + '').toLowerCase();
					// parse all widget's description tags
					$(VBOCore.options.panel_opts.addw_selector).each(function() {
						var elem  = $(this);
						var descr = elem.find(VBOCore.options.panel_opts.wtags_selector).text();
						if (VBOCore.matchString(keyword, descr)) {
							elem.show();
							matching++;
							if (!first_matched) {
								// store the first widget that matched
								first_matched = elem.attr(VBOCore.options.panel_opts.addw_data_attr);
							}
						} else {
							elem.hide();
						}
					});
					// check how many widget matched
					if (matching > 0) {
						// hide "no results"
						$(VBOCore.options.panel_opts.search_nores).hide();
					} else {
						// show "no results"
						$(VBOCore.options.panel_opts.search_nores).show();
					}
				}

				// check for shortcuts
				if (!e.key) {
					return;
				}

				// handle Enter key press to add a widget
				if (e.key === "Enter") {
					// on Enter key pressed, open/add the first matching widget or the focussed one
					var load_wid_id  = null;
					var focussed_wid = $(VBOCore.options.panel_opts.addwfs_selector + ':visible').first();

					if (focussed_wid.length) {
						load_wid_id = focussed_wid.attr(VBOCore.options.panel_opts.addw_data_attr);
					} else if (first_matched) {
						load_wid_id = first_matched;
					}

					if (!load_wid_id) {
						// no widget to render found
						return;
					}

					if (e.shiftKey === true && !VBOCore.options.is_vcm) {
						// widget multitask panel rendering
						VBOCore.addWidgetToPanel(load_wid_id);
						$(VBOCore.options.panel_opts.search_selector).trigger('blur');

						return;
					}

					// widget modal rendering is the default method
					if (VBOCore.options.is_vcm) {
						// register loading effect with automatic cancellation
						let orig_name = (focussed_wid.length ? focussed_wid : first_matched).find(VBOCore.options.panel_opts.wname_selector).text();
						(focussed_wid.length ? focussed_wid : first_matched).find(VBOCore.options.panel_opts.wname_selector).html(orig_name + ' ' + VBOCore.options.default_loading_body);
						setTimeout(() => {
							(focussed_wid.length ? focussed_wid : first_matched).find(VBOCore.options.panel_opts.wname_selector).text(orig_name);
						}, 1000);

						// assets will be loaded within VCM before rendering the modal widget
						VBOCore.handleDisplayWidgetNotification({widget_id: load_wid_id});
					} else {
						VBOCore.renderModalWidget(load_wid_id);
					}

					return;
				}

				// handle arrow keys selection
				if (matching > 0 && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
					// on arrow key pressed, select the next or prev widget
					var addws_element  = $(VBOCore.options.panel_opts.addws_selector);
					var addws_cont_pos = addws_element.offset().top;
					var addws_otheight = addws_element.outerHeight();
					var addws_scrolltp = addws_element.scrollTop();

					if (e.key === 'ArrowDown') {
						var default_widg = $(VBOCore.options.panel_opts.addw_selector + ':visible').first();
					} else {
						var default_widg = $(VBOCore.options.panel_opts.addw_selector + ':visible').last();
					}
					var focussed_wid = $(VBOCore.options.panel_opts.addwfs_selector + ':visible').first();
					var addw_height  = default_widg.outerHeight();
					var focussed_pos = default_widg.offset().top + addw_height;

					if (focussed_wid.length) {
						focussed_wid.removeClass(widget_focus_class);
						if (e.key === 'ArrowDown') {
							var goto_wid = focussed_wid.nextAll(VBOCore.options.panel_opts.addw_selector + ':visible').first();
						} else {
							var goto_wid = focussed_wid.prevAll(VBOCore.options.panel_opts.addw_selector + ':visible').first();
						}
						if (goto_wid.length) {
							goto_wid.addClass(widget_focus_class);
							focussed_pos = goto_wid.offset().top + addw_height;
						} else {
							default_widg.addClass(widget_focus_class);
						}
					} else {
						default_widg.addClass(widget_focus_class);
					}

					if (focussed_pos > (addws_cont_pos + addws_otheight)) {
						addws_element.scrollTop(focussed_pos - addws_cont_pos - addw_height + addws_scrolltp);
					} else if (focussed_pos < 0) {
						addws_element.scrollTop(0);
					}
				}
			});

			// register listener for adding widgets
			$(VBOCore.options.panel_opts.addw_selector).on('click', function(e) {
				var widget_id = $(this).attr(VBOCore.options.panel_opts.addw_data_attr);
				if (!widget_id || !widget_id.length) {
					return false;
				}

				if (VBOCore.options.panel_opts.sorting) {
					// prevent dropped widgets after sorting to be added to the panel
					return false;
				}

				// determine widget rendering method
				if (e && e.target) {
					let cktarget = $(e.target);
					let is_main_elem_clicked = cktarget.length && (cktarget.hasClass('vbo-sidepanel-widget-info-det') || cktarget.hasClass('vbo-sidepanel-widget-name'));
					if (e.shiftKey === true || VBOCore.options.is_vcm || is_main_elem_clicked || (cktarget.hasClass(VBOCore.options.panel_opts.addw_modal_cls) || cktarget.parent().hasClass(VBOCore.options.panel_opts.addw_modal_cls))) {
						// widget modal rendering
						if (VBOCore.options.is_vcm) {
							// register loading effect with automatic cancellation
							let orig_name = $(this).find(VBOCore.options.panel_opts.wname_selector).text();
							$(this).find(VBOCore.options.panel_opts.wname_selector).html(orig_name + ' ' + VBOCore.options.default_loading_body);
							setTimeout(() => {
								$(this).find(VBOCore.options.panel_opts.wname_selector).text(orig_name);
							}, 1000);

							// assets will be loaded within VCM before rendering the modal widget
							VBOCore.handleDisplayWidgetNotification({widget_id: widget_id});
						} else {
							VBOCore.renderModalWidget(widget_id);
						}

						return;
					}
				}

				// widget multitask panel rendering
				VBOCore.addWidgetToPanel(widget_id);
			});

			if (VBOCore.options.is_vbo) {
				// register listener for updating multitask sidepanel with debounce
				document.addEventListener(VBOCore.options.multitask_save_event, VBOCore.debounceEvent(VBOCore.saveMultitasking, 1000));
			}

			// subscribe to event for multitask shortcut
			document.addEventListener(VBOCore.multitask_shortcut_event, function() {
				// toggle multitask panel
				$(VBOCore.options.panel_opts.btn_trigger).trigger('click');
			});

			// subscribe to event for multitask search focus shortcut
			document.addEventListener(VBOCore.multitask_searchfs_event, function() {
				// focus search multitask widgets
				$(VBOCore.options.panel_opts.search_selector).trigger('focus');
			});

			if (VBOCore.options.is_vbo) {
				// register click event on edit widgets button
				$(VBOCore.options.panel_opts.editw_selector).on('click', function() {
					VBOCore.toggleWidgetsPanelEditing(null);
				});

				// register detach widget buttons
				$('.' + VBOCore.options.panel_opts.dtcwidget_class).each(function() {
					let widget_wrapper 	 = $(this).parent(VBOCore.options.panel_opts.wclass_base_sel);
					let detach_widget_id = widget_wrapper.attr(VBOCore.options.panel_opts.addw_data_attr);
					let detach_to_target = widget_wrapper.find('.' + VBOCore.options.panel_opts.dtctarget_class);
					if (!detach_to_target.length) {
						detach_to_target = widget_wrapper;
					}
					// move detach wrapper onto the target (widget head)
					$(this).prependTo(detach_to_target);

					if (!detach_widget_id) {
						return;
					}

					// register detach action
					$(this).on('click', function() {
						// detach widget, meaning do a modal rendering
						VBOCore.renderModalWidget(detach_widget_id);
					});
				});
			}

			if (VBOCore.options.panel_opts.sortable && typeof $.fn.sortable !== 'undefined') {
				// make the admin widgets sortable
				let sortable_env = VBOCore.options.is_vcm ? 'vcm-sidepanel-add-widgets' : 'vbo-sidepanel-add-widgets';
				let handle_env   = VBOCore.options.is_vcm ? 'vcm-sidepanel-widget-info-det' : 'vbo-sidepanel-widget-info-det';
				let item_env     = VBOCore.options.is_vcm ? 'vcm-sidepanel-add-widget' : 'vbo-sidepanel-add-widget';

				// default sorting flag
				VBOCore.options.panel_opts.sorting = null;

				// register listener for updating the widget sorting values on the multitask sidepanel with debounce
				document.addEventListener(VBOCore.options.panel_opts.sort_save_ev, VBOCore.debounceEvent(VBOCore.saveMultitaskingSorting, 500));

				// apply sorting capabilities
				$('.' + sortable_env).sortable({
					axix: 'x',
					cursor: 'move',
					handle: '.' + handle_env,
					items: '.' + item_env,
					containment: 'parent',
					revert: false,
					start: function(event, ui) {
						// update flag
						VBOCore.options.panel_opts.sorting = $(ui.item).attr('data-vbowidgetid');
						// set is-sorting class
						$(ui.item).addClass('is-sorting');
					},
					stop: function(event, ui) {
						// make sure no elements are being sorted
						$('.' + item_env).removeClass('is-sorting');
						// register delayed update flag to allow understanding a sorting was just completed
						setTimeout(() => {
							// restore default sorting flag
							VBOCore.options.panel_opts.sorting = null;
						}, 500);
					},
					update: function(event, ui) {
						// build the current widgets position list
						let pos_list = {};
						document.querySelectorAll('.' + item_env).forEach((elem, index) => {
							let curr_wid = elem.getAttribute('data-vbowidgetid');
							pos_list[curr_wid] = index;
						});
						// trigger the event to update the widget positions
						VBOCore.emitEvent(VBOCore.options.panel_opts.sort_save_ev, {
							pos_list: pos_list,
						});
					}
				});
			}
		}

		/**
		 * Registers the callback for saving the new widget sorting value in the Multitask panel.
		 */
		static saveMultitaskingSorting(e) {
			if (!e || !e.detail || !e.detail.pos_list) {
				return;
			}

			// update multitask widget position
			VBOCore.doAjax(
				VBOCore.options.multitask_ajax_uri,
				{
					call: 'setMultitaskingWidgetPos',
					call_args: [
						e.detail.pos_list,
					],
				},
				(response) => {
					// do nothing on success
				},
				(error) => {
					// silently log the error
					console.error(error.responseText);
				}
			);
		}

		/**
		 * Registers the blur event for the search widgets input.
		 */
		static registerSearchWidgetsBlur() {
			if (VBOCore.options.active_listeners.hasOwnProperty('registerSearchWidgetsBlur')) {
				// listener is already registered
				return true;
			}

			$(VBOCore.options.panel_opts.search_selector).on('blur', function(e) {
				if (e && e.relatedTarget) {
					if (e.relatedTarget.classList.contains(VBOCore.options.panel_opts.addw_selector.replace('.', ''))) {
						// add new widget was clicked, abort hiding process or click event won't fire on target element
						return;
					}
				}
				var keyword = $(this).val();
				if (!keyword.length) {
					// hide add-widgets container
					$(VBOCore.options.panel_opts.addws_selector).hide();
				}
			});

			// register flag for listener active
			VBOCore.options.active_listeners['registerSearchWidgetsBlur'] = 1;
		}

		/**
		 * Removes the blur event handler for the search widgets input.
		 */
		static unregisterSearchWidgetsBlur() {
			if (!VBOCore.options.active_listeners.hasOwnProperty('registerSearchWidgetsBlur')) {
				// nothing to unregister
				return true;
			}

			$(VBOCore.options.panel_opts.search_selector).off('blur');

			// delete flag for listener active
			delete VBOCore.options.active_listeners['registerSearchWidgetsBlur'];
		}

		/**
		 * Adds a widget identifier to the multitask panel.
		 * 
		 * @param 	string 	widget_id 	the widget identifier string to add.
		 */
		static addWidgetToPanel(widget_id) {
			if (!VBOCore.options.widget_ajax_uri || !VBOCore.options.panel_opts || !Object.keys(VBOCore.options.panel_opts).length) {
				throw new Error('Multitask panel options are missing');
			}
			// prepend container to panel
			var widget_classes = [VBOCore.options.panel_opts.wclass_base_sel.replace('.', ''), VBOCore.options.panel_opts.cur_widget_cls];
			var widget_div = '<div class="' + widget_classes.join(' ') + '" ' + VBOCore.options.panel_opts.addw_data_attr + '="' + widget_id + '" style="display: none;"></div>';
			var widget_elem = $(widget_div);
			$(VBOCore.options.panel_opts.actws_selector).prepend(widget_elem);

			// always hide add-widgets container
			$(VBOCore.options.panel_opts.addws_selector).hide();

			// trigger debounced map saving event
			VBOCore.emitMultitaskEvent();

			// register listener for input search blur
			VBOCore.registerSearchWidgetsBlur();

			// render widget
			var call_method = 'render';
			VBOCore.doAjax(
				VBOCore.options.widget_ajax_uri,
				{
					widget_id: widget_id,
					call: 	   call_method,
					vbo_page:  VBOCore.options.current_page,
					vbo_uri:   VBOCore.options.current_page_uri,
					multitask: 1,
				},
				(response) => {
					// display widgets editing button
					VBOCore.toggleWidgetsPanelEditing(true);
					// parse response
					try {
						var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
						if (obj_res.hasOwnProperty(call_method)) {
							// populate widget HTML content and display it
							widget_elem.html(obj_res[call_method]).fadeIn();

							// always scroll active widgets list to top
							$(VBOCore.options.panel_opts.actws_selector).scrollTop(0);

							// register detach widget button
							setTimeout(() => {
								let detach_elem = $('<div></div>').addClass(VBOCore.options.panel_opts.dtcwidget_class).html(VBOCore.options.panel_opts.dtcwidget_icn);
								let detach_to_target = widget_elem.find('.' + VBOCore.options.panel_opts.dtctarget_class);
								if (!detach_to_target.length) {
									detach_to_target = widget_elem;
								}
								// move detach wrapper onto the target (widget head)
								detach_elem.prependTo(detach_to_target);
								// register detach action
								detach_elem.on('click', function() {
									// detach widget, meaning do a modal rendering
									VBOCore.renderModalWidget(widget_id);
								});
							}, 500);
						} else {
							console.error('Unexpected JSON response', obj_res);
						}
					} catch(err) {
						console.error('could not parse JSON response', err, response);
					}
				},
				(error) => {
					console.error(error.responseText);
				}
			);
		}

		/**
		 * Toggles the edit mode of the multitask widgets panel.
		 * 
		 * @param 	bool 	added 	true if a widget was just added, false if it was just removed.
		 */
		static toggleWidgetsPanelEditing(added) {
			// check if we are currently editing
			var is_editing = ($('.' + VBOCore.options.panel_opts.editmode_class).length > 0);

			// the triggerer button
			var triggerer = $(VBOCore.options.panel_opts.editw_selector);

			// check added action status
			if (added === true) {
				// show button for edit mode
				triggerer.show();
				// hide shortcut element
				$(VBOCore.options.panel_opts.shortc_selector).hide();
				return;
			}

			// grab all widgets
			var editing_widgets = $(VBOCore.options.panel_opts.wclass_base_sel);

			if (added === false) {
				if (!editing_widgets.length) {
					// hide button for edit mode after removing the last widget
					triggerer.removeClass(VBOCore.options.panel_opts.editw_selector.substr(1) + '-active').hide();
					// show shortcut element
					$(VBOCore.options.panel_opts.shortc_selector).show();
				}
				return;
			}

			if (is_editing) {
				// deactivate editing mode
				editing_widgets.removeClass(VBOCore.options.panel_opts.editmode_class);
				$('.' + VBOCore.options.panel_opts.rmwidget_class).remove();
				// toggle triggerer button active class
				triggerer.removeClass(VBOCore.options.panel_opts.editw_selector.substr(1) + '-active');
			} else {
				// activate editing mode by looping through all widgets
				editing_widgets.each(function() {
					// build remove-widget element
					var rm_widget = $('<div></div>').addClass(VBOCore.options.panel_opts.rmwidget_class).on('click', function() {
						VBOCore.removeWidgetFromPanel(this);
					}).html(VBOCore.options.panel_opts.rmwidget_icn);
					// add editing class and prepend removing element
					$(this).addClass(VBOCore.options.panel_opts.editmode_class).prepend(rm_widget);
				});
				// toggle triggerer button active class
				triggerer.addClass(VBOCore.options.panel_opts.editw_selector.substr(1) + '-active');
			}
		}

		/**
		 * Handles the removal of a widget from the multitask panel.
		 * 
		 * @param 	object 	element
		 */
		static removeWidgetFromPanel(element) {
			if (!element) {
				console.error('Invalid widget element to remove', element);
				return false;
			}
			var widget_cont = $(element).parent(VBOCore.options.panel_opts.wclass_base_sel);
			if (!widget_cont || !widget_cont.length) {
				console.error('Could not find widget container to remove', element);
				return false;
			}
			var widget_id = widget_cont.attr(VBOCore.options.panel_opts.addw_data_attr);
			if (!widget_id || !widget_id.length) {
				console.error('Empty widget id to remove', element);
				return false;
			}
			// find the index of the widget to remove in the panel
			var widget_index = $(VBOCore.options.panel_opts.wclass_base_sel).index(widget_cont);
			if (widget_index < 0) {
				console.error('Empty widget index to remove', widget_cont);
				return false;
			}
			// make sure the index in the array matches the id
			if (!VBOCore.options.admin_widgets.hasOwnProperty(widget_index) || VBOCore.options.admin_widgets[widget_index]['id'] != widget_id) {
				console.error('Unmatching widget index or id', VBOCore.options.admin_widgets, widget_index, widget_id);
				return false;
			}
			// remove this widget from the array
			VBOCore.options.admin_widgets.splice(widget_index, 1);

			// remove element from document
			widget_cont.remove();

			// check widgets editing button status
			VBOCore.toggleWidgetsPanelEditing(false);

			// trigger debounced map saving event
			VBOCore.emitMultitaskEvent();

			if (!VBOCore.options.admin_widgets.length) {
				// unregister listener for input search blur
				VBOCore.unregisterSearchWidgetsBlur();
			}
		}

		/**
		 * Emits an event related to the multitask features or a custom event, with optional data.
		 */
		static emitMultitaskEvent(ev_name, ev_data) {
			var def_ev_name = VBOCore.options.multitask_save_event;
			if (typeof ev_name === 'string') {
				def_ev_name = ev_name;
			}

			if (typeof ev_data !== 'undefined' && ev_data) {
				// trigger the custom event
				document.dispatchEvent(new CustomEvent(def_ev_name, {bubbles: true, detail: ev_data}));
				return;
			}

			// trigger the event
			document.dispatchEvent(new Event(def_ev_name));
		}

		/**
		 * Proxy for dispatching an event to the document with optional data.
		 */
		static emitEvent(ev_name, ev_data) {
			if (typeof ev_name !== 'string' || !ev_name.length) {
				return;
			}

			return VBOCore.emitMultitaskEvent(ev_name, ev_data);
		}

		/**
		 * Attempts to save the multitask widgets for this page.
		 */
		static saveMultitasking() {
			// gather the list of active widgets
			var active_widgets = [];
			var cur_admin_widgets = [];
			$(VBOCore.options.panel_opts.actws_selector).find(VBOCore.options.panel_opts.wclass_base_sel).each(function() {
				var widget_id = $(this).attr(VBOCore.options.panel_opts.addw_data_attr);
				if (widget_id && widget_id.length) {
					// push id in list
					active_widgets.push(widget_id);
					// push object with dummy name for global widgets
					cur_admin_widgets.push({
						id: widget_id,
						name: widget_id,
					});
				}
			});

			// update multitask widgets map for this page
			VBOCore.doAjax(
				VBOCore.options.multitask_ajax_uri,
				{
					call: 'updateMultitaskingMap',
					call_args: [
						VBOCore.options.current_page,
						active_widgets,
						0
					],
				},
				(response) => {
					try {
						var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
						if (obj_res.hasOwnProperty('result') && obj_res['result']) {
							// set current widgets
							VBOCore.setOptions({
								admin_widgets: cur_admin_widgets
							});
						} else {
							console.error('Unexpected or invalid JSON response', response);
						}
					} catch(err) {
						console.error('could not parse JSON response', err, response);
					}
				},
				(error) => {
					console.error(error.responseText);
				}
			);
		}

		/**
		 * Sets up the browser notifications within the multitask panel, if supported.
		 */
		static setupNotifications() {
			if (!('Notification' in window)) {
				// browser does not support notifications
				$(VBOCore.options.panel_opts.notif_selector).hide();
				return false;
			}

			if (Notification.permission && Notification.permission === 'granted') {
				// permissions were granted already
				$(VBOCore.options.panel_opts.notif_selector)
					.addClass(VBOCore.options.panel_opts.notif_on_class)
					.attr('title', VBOCore.options.tn_texts.notifs_enabled)
					.on('click', function() {
						// show congratulations notification
						VBOCore.dispatchCongratulations();

						// attempt to update the service worker installation
						VBOCore.installServiceWorker(true).then(() => {
							// all good
						}).catch((error) => {
							console.error(error);
						});
					});
				return true;
			}

			// notifications supported, but perms not granted
			$(VBOCore.options.panel_opts.notif_selector)
				.addClass(VBOCore.options.panel_opts.notif_off_class)
				.attr('title', VBOCore.options.tn_texts.notifs_disabled);

			// register click-event listener on button to enable notifications
			$(VBOCore.options.panel_opts.notif_selector).on('click', function() {
				VBOCore.requestNotifPerms();
			});

			// subscribe to the multitask-panel-open event to show the status of the notifications
			document.addEventListener(VBOCore.multitask_open_event, function() {
				if (VBOCore.notificationsEnabled() === false) {
					// add "shaking" class to notifications button
					$(VBOCore.options.panel_opts.notif_selector).addClass('shaking');
				}
			});

			// subscribe to the multitask-panel-close event to update the status of the notifications
			document.addEventListener(VBOCore.multitask_close_event, function() {
				// always remove "shaking" class from notifications button
				$(VBOCore.options.panel_opts.notif_selector).removeClass('shaking');
			});
		}

		/**
		 * Sets up the browser notifications within the given selector, if supported.
		 * 
		 * @param 	string 	selector 	the element query selector.
		 */
		static suggestNotifications(selector) {
			if (!selector) {
				return false;
			}

			if (!('Notification' in window)) {
				// browser does not support notifications
				$(selector).hide();
				return false;
			}

			if (Notification.permission && Notification.permission === 'granted') {
				// permissions were granted already
				$(selector)
					.addClass(VBOCore.options.panel_opts.notif_on_class)
					.attr('title', VBOCore.options.tn_texts.notifs_enabled)
					.on('click', function() {
						VBOCore.dispatchCongratulations();
					});
				return true;
			}

			// notifications supported, but perms not granted
			$(selector)
				.addClass(VBOCore.options.panel_opts.notif_off_class)
				.attr('title', VBOCore.options.tn_texts.notifs_disabled);

			// register click-event listener on button to enable notifications
			$(selector).on('click', function() {
				VBOCore.requestNotifPerms(selector);
			});

			// add "shaking" class to make the selector more appealing
			$(selector).addClass('shaking');

			// remove the "shaking" class after some time
			setTimeout(() => {
				$(selector).removeClass('shaking');
			}, 2000);
		}

		/**
		 * Tells whether the notifications are enabled, disabled, not supported.
		 */
		static notificationsEnabled() {
			if (!('Notification' in window)) {
				// not supported
				return null;
			}

			if (Notification.permission && Notification.permission === 'granted') {
				// enabled
				return true;
			}

			// disabled
			return false;
		}

		/**
		 * Attempts to request the notifications permissions to the browser.
		 * For security reasons, this should run upon a user gesture (click).
		 * 
		 * @param 	string 	selector 	optional element query selector.
		 */
		static requestNotifPerms(selector) {
			if (!('Notification' in window)) {
				// browser does not support notifications
				return false;
			}

			// run permissions request in a try-catch statement to support all browsers
			try {
				// handle promise-based version to request permissions
				Notification.requestPermission().then((permission) => {
					VBOCore.handleNotifPerms(permission, selector);
				});
			} catch(e) {
				// run the callback-based version
				Notification.requestPermission(function(permission) {
					VBOCore.handleNotifPerms(permission, selector);
				});
			}
		}

		/**
		 * Handles the notifications permission response (from callback or promise resolved).
		 * 
		 * @param 	any 	permission 	permission status string or NotificationPermission object.
		 * @param 	string 	selector 	optional element query selector.
		 */
		static handleNotifPerms(permission, selector) {
			// check the permission status from the Notification object interface
			if ((Notification.permission && Notification.permission === 'granted') || (typeof permission === 'string' && permission === 'granted')) {
				// permissions granted!
				$((selector || VBOCore.options.panel_opts.notif_selector))
					.removeClass(VBOCore.options.panel_opts.notif_off_class)
					.addClass(VBOCore.options.panel_opts.notif_on_class)
					.attr('title', VBOCore.options.tn_texts.notifs_enabled);

				// dispatch an immediate notification to congratulate with the activation
				VBOCore.dispatchCongratulations();

				return true;
			} else {
				// permissions denied :(
				$((selector || VBOCore.options.panel_opts.notif_selector))
					.removeClass(VBOCore.options.panel_opts.notif_on_class)
					.addClass(VBOCore.options.panel_opts.notif_off_class)
					.attr('title', VBOCore.options.tn_texts.notifs_disabled);

				// show alert message
				console.error('Permission denied for enabling browser notifications', permission);
				alert(VBOCore.options.tn_texts.notifs_disabled_help);
			}

			return false;
		}

		/**
		 * Dispatches an immediate notification with a congratulations text.
		 * 
		 * @return 	void
		 */
		static dispatchCongratulations() {
			try {
				let notif = new Notification(VBOCore.options.tn_texts.congrats, {
					body: VBOCore.options.tn_texts.notifs_enabled,
					tag:  'vbo_notification_congrats',
					silent: false
				});
			} catch(error) {
				alert(error);
			}
		}

		/**
		 * Given a date-time string, returns a Date object representation.
		 * 
		 * @param 	string 	dtime_str 	the date-time string in "Y-m-d H:i:s" format.
		 */
		static getDateTimeObject(dtime_str) {
			// instantiate a new date object
			var date_obj = new Date();

			// parse date-time string
			let dtime_parts = dtime_str.split(' ');
			let date_parts  = dtime_parts[0].split('-');
			if (dtime_parts.length != 2 || date_parts.length != 3) {
				// invalid military format
				return date_obj;
			}
			let time_parts = dtime_parts[1].split(':');

			// set accurate date-time values
			date_obj.setFullYear(date_parts[0]);
			date_obj.setMonth((parseInt(date_parts[1]) - 1));
			date_obj.setDate(parseInt(date_parts[2]));
			date_obj.setHours(parseInt(time_parts[0]));
			date_obj.setMinutes(parseInt(time_parts[1]));
			date_obj.setSeconds(0);
			date_obj.setMilliseconds(0);

			// return the accurate date object
			return date_obj;
		}

		/**
		 * Given a list of schedules, enqueues notifications to watch.
		 * 
		 * @param 	array|object 	schedules 	list of or one notification object(s).
		 * 
		 * @return 	bool
		 */
		static enqueueNotifications(schedules) {
			if (!Array.isArray(schedules) || !schedules.length) {
				if (typeof schedules === 'object' && schedules.hasOwnProperty('dtime')) {
					// convert the single schedule to an array
					schedules = [schedules];
				} else {
					// invalid argument passed
					return false;
				}
			}

			for (var i in schedules) {
				if (!schedules.hasOwnProperty(i) || typeof schedules[i] !== 'object') {
					continue;
				}
				VBOCore.notifications.push(schedules[i]);
			}

			// setup the timeouts to schedule the notifications
			return VBOCore.scheduleNotifications();
		}

		/**
		 * Schedule the trigger timings for each notification.
		 */
		static scheduleNotifications() {
			if (!VBOCore.notifications.length) {
				// no notifications to be scheduled
				return false;
			}
			if (VBOCore.notificationsEnabled() !== true) {
				// notifications not enabled
				console.info('Browser notifications disabled or unsupported.');
				return false;
			}

			// gather current date-timing information
			const now_date = new Date();
			const now_time = now_date.getTime();

			// parse all notifications to schedule the timers if not set
			for (let i = 0; i < VBOCore.notifications.length; i++) {
				let notif = VBOCore.notifications[i];

				if (typeof notif !== 'object' || !notif.hasOwnProperty('dtime')) {
					// invalid notification object, unset it
					VBOCore.notifications.splice(i, 1);
					continue;
				}

				// check if timer has been set
				if (!notif.hasOwnProperty('id_timer')) {
					// estimate trigger timing
					let in_ms = 0;
					// check for imminent notifications
					if (typeof notif.dtime === 'string' && notif.dtime.indexOf('now') >= 0) {
						// imminent ones will be delayed by one second
						in_ms = 1000;
					} else {
						// check overdue date-time (notif.dtime can also be a Date object instance)
						let nexp = VBOCore.getDateTimeObject(notif.dtime);
						in_ms = nexp.getTime() - now_time;
					}
					if (in_ms > 0) {
						// schedule notification trigger
						let id_timer = setTimeout(() => {
							VBOCore.dispatchNotification(notif);
						}, in_ms);
						// set timer on notification object
						VBOCore.notifications[i]['id_timer'] = id_timer;
					}
				}
			}

			return true;
		}

		/**
		 * Deregister all scheduled notifications.
		 */
		static unscheduleNotifications() {
			if (!VBOCore.notifications.length) {
				// no notifications scheduled
				return false;
			}

			for (let i = 0; i < VBOCore.notifications.length; i++) {
				let notif = VBOCore.notifications[i];

				if (typeof notif === 'object' && notif.hasOwnProperty('id_timer')) {
					// unset timeout for this notification
					clearTimeout(notif['id_timer']);
				}
			}

			// reset pool
			VBOCore.notifications = [];
		}

		/**
		 * Update or delete a previously scheduled notification.
		 * 
		 * @param 	object 			match_props  map of properties to match.
		 * @param 	string|number  	newdtime 	 the new date time to schedule (0 for deleting).
		 * 
		 * @return 	null|bool 					 true only if a notification matched.
		 */
		static updateNotification(match_props, newdtime) {
			if (!VBOCore.notifications.length) {
				// no notifications set, terminate
				return null;
			}

			if (typeof match_props !== 'object') {
				// no properties to match the notification
				return null;
			}

			// gather current date-timing information
			const now_date = new Date();
			const now_time = now_date.getTime();

			// parse all notifications scheduled
			for (let i = 0; i < VBOCore.notifications.length; i++) {
				let notif = VBOCore.notifications[i];

				let all_matched = true;
				let to_matching = false;
				for (let prop in match_props) {
					if (!match_props.hasOwnProperty(prop)) {
						continue;
					}
					to_matching = true;
					if (!notif.hasOwnProperty(prop) || notif[prop] != match_props[prop]) {
						all_matched = false;
						break;
					}
				}

				if (all_matched && to_matching) {
					// notification object found
					if (notif.hasOwnProperty('id_timer')) {
						// unset previous timeout for this notification
						clearTimeout(notif['id_timer']);
					}
					// update or delete scheduled notification
					if (newdtime === 0) {
						// delete notification from queue
						VBOCore.notifications.splice(i, 1);
					} else {
						// update timing scheduler
						let in_ms = 0;
						// check for imminent notifications
						if (typeof newdtime === 'string' && newdtime.indexOf('now') >= 0) {
							// imminent ones will be delayed by one second
							in_ms = 1000;
						} else {
							// check overdue date-time (newdtime can also be a Date object instance)
							let nexp = VBOCore.getDateTimeObject(newdtime);
							in_ms = nexp.getTime() - now_time;
						}
						if (in_ms > 0) {
							// schedule notification trigger
							let id_timer = setTimeout(() => {
								VBOCore.dispatchNotification(notif);
							}, in_ms);
							// set timer on notification object
							VBOCore.notifications[i]['id_timer'] = id_timer;
						}
						// update date-time value on notification object
						VBOCore.notifications[i]['dtime'] = newdtime;
					}

					// terminate parsing and return true
					return true;
				}
			}

			// notification object not found
			return false;
		}

		/**
		 * Dispatch the notification object.
		 * Expected notification properties:
		 * 
		 * {
		 *		id: 		number
		 * 		type: 		string
		 * 		dtime: 		string|Date
		 *		build_url: 	string|null
		 * }
		 * 
		 * @param 	object 	notif 	the notification object.
		 */
		static dispatchNotification(notif) {
			if (typeof notif !== 'object') {
				return false;
			}

			// subscribe to building notification data
			VBOCore.buildNotificationData(notif).then((data) => {
				// dispatch the notification

				// check if the click event should be registered
				let func_nodes;
				if (data.onclick && typeof data.onclick === 'string') {
					let callback_parts = data.onclick.split('.');
					while (callback_parts.length) {
						// compose window static method string to avoid using eval()
						let tmp = callback_parts.shift();
						if (!func_nodes) {
							func_nodes = window[tmp];
						} else {
							func_nodes = func_nodes[tmp];
						}
					}
				}

				// prepare properties to delete the notification from queue
				let match_props = {};
				for (let prop in notif) {
					if (!notif.hasOwnProperty(prop) || prop == 'id_timer') {
						continue;
					}
					match_props[prop] = notif[prop];
				}

				// check browser Notifications API
				if (VBOCore.notificationsEnabled() !== true) {
					// notifications not enabled, fallback to toast message
					let toast_notif_data = {
						title: 	data.title,
						body:  	data.message,
						icon:  	data.icon,
						delay: 	{
							min: 6000,
							max: 20000,
							tolerance: 4000,
						},
						action: () => {
							VBOToast.dispose(true);
						},
						sound: VBOCore.options.notif_audio_url
					};
					if (func_nodes) {
						toast_notif_data.action = function() {
							func_nodes(data);
							VBOToast.dispose(true);
						};
					} else if (typeof data.onclick === 'function') {
						toast_notif_data.action = function() {
							data.onclick.call(data);
							VBOToast.dispose(true);
						};
					}
					VBOToast.enqueue(new VBOToastMessage(toast_notif_data));

					// delete dispatched notification from queue
					VBOCore.updateNotification(match_props, 0);

					return;
				}

				// use the browser's native Notifications API
				let browser_notif = new Notification(data.title, {
					body: data.message,
					icon: data.icon,
					tag:  'vbo_notification',
					silent: false
				});

				// check if support for Web App badge is available
				if (typeof navigator.setAppBadge !== 'undefined') {
					navigator.setAppBadge(1);
				}

				browser_notif.addEventListener('click', (e) => {
					// check if support for Web App badge is available
					if (typeof navigator.setAppBadge !== 'undefined') {
						navigator.clearAppBadge();
					}
					// close the notification
					e.target.close();
				});

				if (func_nodes) {
					// register notification click event
					browser_notif.addEventListener('click', () => {
						func_nodes(data);
					});
				} else if (typeof data.onclick === 'function') {
					browser_notif.addEventListener('click', () => {
						data.onclick.call(data);
					});
				}

				// delete dispatched notification from queue
				VBOCore.updateNotification(match_props, 0);

			}).catch((error) => {
				console.error(error);
			});
		}

		/**
		 * Asynchronous build of the notification data object for dispatch.
		 * Minimum expected notification display data properties:
		 * 
		 * {
		 *		title: 	 string
		 * 		message: string
		 * 		icon: 	 string
		 *		onclick: function
		 * }
		 * 
		 * @param 	object 	notif 	the scheduled notification object.
		 * 
		 * @return 	Promise
		 */
		static buildNotificationData(notif) {
			return new Promise((resolve, reject) => {
				// notification object validation
				if (typeof notif !== 'object') {
					reject('Invalid notification object');
					return;
				}

				if (!notif.hasOwnProperty('build_url') || !notif.build_url) {
					// building callback not necessary
					if (!notif.title && !notif.message) {
						reject('Unexpected notification object');
						return;
					}
					// we expect the notification to be built already
					resolve(notif);
					return;
				}

				// build the notification data
				VBOCore.doAjax(
					notif.build_url,
					{
						payload: JSON.stringify(notif)
					},
					(response) => {
						// parse response
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (obj_res.hasOwnProperty('title')) {
								resolve(obj_res);
							} else {
								reject('Unexpected JSON response');
							}
						} catch(err) {
							reject('could not parse JSON response');
						}
					},
					(error) => {
						reject(error.responseText);
					}
				);
			});
		}

		/**
		 * Handle a navigation towards a given URL.
		 * Common handler for browser notifications click.
		 * 
		 * @param 	object 	data 	notification display data payload.
		 */
		static handleGoto(data) {
			if (typeof data !== 'object' || !data.hasOwnProperty('gotourl') || !data.gotourl) {
				console.error('Could not handle the goto operation', data);
				return;
			}

			if (typeof data.openWindow !== 'undefined' || typeof document === 'undefined') {
				// open a new window
				window.open(data.gotourl);
				return;
			}

			// redirect
			document.location.href = data.gotourl;
		}

		/**
		 * Opens a new window with the URI to render the Push notification data.
		 * 
		 * @param 	object 	options  admin widget rendering options to bind.
		 * 
		 * @return 	void
		 */
		static openAdminPushURI(options) {
			if (typeof options !== 'object' || !options) {
				throw new Error('Unable to build Admin Push URI');
			}

			let vbo_admin_push_uri = VBOCore.options.root_uri;

			if (VBOCore.options.cms === 'wordpress') {
				vbo_admin_push_uri += 'wp-admin/admin.php?page=vikbooking';
			} else {
				vbo_admin_push_uri += 'administrator/index.php?option=com_vikbooking';
			}

			// build Push notification payload for rendering
			let push_data = {
				type: 	 options.type || '',
				title: 	 options.title || '',
				message: options.message || '',
				content: options,
			};

			// open page in a new window
			window.open(vbo_admin_push_uri + '&push_notification=' + btoa(JSON.stringify(push_data)), '_blank');
		}

		/**
		 * Handle the display of a notification through a widget.
		 * Common handler for browser notifications displayed through
		 * a widget modal rendering.
		 * 
		 * @param 	object 	data 	 notification display data payload.
		 * @param 	object 	options  optional extra options for the admin widget.
		 * @param 	boolean is_push  whether we are coming from a Push notification.
		 * 
		 * @return 	void
		 */
		static handleDisplayWidgetNotification(data, options, is_push) {
			try {
				// validate handler
				if (!VBOCore.options.is_vbo && !VBOCore.options.is_vcm) {
					throw new Error('Unable to handle the notification display from the current page');
				}

				// validate payload
				if (typeof data !== 'object' || !data.hasOwnProperty('widget_id') || !data['widget_id']) {
					throw new Error('Invalid widget payload');
				}

				// parse handler
				if (VBOCore.options.is_vcm || (is_push && !VBOCore.options.is_vbo)) {
					// the operation is handled by VCM (or by an external admin resource, if it's a Push notification)
					VBOCore.loadAdminWidgetAssets(data).then((assets) => {
						// append assets to DOM
						assets.forEach((asset) => {
							if (!$('link#' + asset['id']).length) {
								$('head').append('<link rel="stylesheet" id="' + asset['id'] + '" href="' + asset['href'] + '" media="all" />');
							}
						});
						// widget modal rendering handled by VCM (or an external admin resource)
						let hide_panel = VBOCore.options.is_vcm && $('.' + VBOCore.options.panel_opts.open_class).length ? true : false;
						let modal_data = VBOCore.renderModalWidget(data['widget_id'], data, options, hide_panel);
						if (modal_data.hasOwnProperty('dismissed_event') && (modal_data['suffix'] || '').indexOf('inner') < 0) {
							// register event to unload all assets (only if not from an inner modal)
							document.addEventListener(modal_data['dismissed_event'], () => {
								assets.forEach((asset) => {
									if ($('link#' + asset['id']).length) {
										$('link#' + asset['id']).remove();
									}
								});
							});
						}
					}).catch((error) => {
						throw new Error(error);
					});
				} else {
					// widget modal rendering handled by Vik Booking
					VBOCore.renderModalWidget(data['widget_id'], data, options, false);
				}
			} catch(e) {
				if (is_push && options.type) {
					// fallback to opening a new window to render the clicked Push notification
					VBOCore.openAdminPushURI(options);
				} else {
					// fallback to a regular link opening, if set
					VBOCore.handleGoto(data);
				}
			}
		}

		/**
		 * Asynchronous loading of CSS assets required to render
		 * an admin widget outside Vik Booking.
		 * 
		 * @param 	object 	data 	the optional widget payload data.
		 * 
		 * @return 	Promise
		 */
		static loadAdminWidgetAssets(data) {
			return new Promise((resolve, reject) => {
				// the remote assets URI must be set
				if (!VBOCore.options.assets_ajax_uri) {
					reject('Missing remote assets URI');
					return;
				}

				if (typeof data !== 'object') {
					data = {};
				}

				// make the request
				VBOCore.doAjax(
					VBOCore.options.assets_ajax_uri,
					data,
					(response) => {
						// parse response
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!Array.isArray(obj_res)) {
								reject('Unexpected JSON response');
							}
							resolve(obj_res);
						} catch(err) {
							reject('could not parse JSON response');
						}
					},
					(error) => {
						reject(error.responseText);
					}
				);
			});
		}

		/**
		 * Register the latest data to watch for the preloaded admin widgets.
		 * 
		 * @param 	object 	watch_data
		 */
		static registerWatchData(watch_data) {
			if (typeof watch_data !== 'object' || watch_data == null) {
				VBOCore.widgets_watch_data = null;
				return false;
			}

			// set watch-data map
			VBOCore.widgets_watch_data = watch_data;

			// schedule watching interval
			if (VBOCore.watch_data_interval == null) {
				VBOCore.watch_data_interval = window.setInterval(VBOCore.watchWidgetsData, 60000);
			}

			// set up broadcast channels to connect all browsing contexts
			if (typeof BroadcastChannel !== 'undefined') {
				// set up watch-data broadcast channel
				if (!VBOCore.broadcast_watch_data) {
					// start broadcast channel
					VBOCore.broadcast_watch_data = new BroadcastChannel('vikbooking_watch_data');

					// register to the "on broadcast message received" event
					VBOCore.broadcast_watch_data.onmessage = (event) => {
						if (event && event.data) {
							// update watch data map for next schedule to avoid dispatching duplicate notifications
							VBOCore.widgets_watch_data = event.data;
						}
					};
				}

				// set up push-data broadcast channel
				if (!VBOCore.broadcast_push_data) {
					// start broadcast channel
					VBOCore.broadcast_push_data = new BroadcastChannel('vikbooking_push_data');

					// register to the "on broadcast message received" event
					VBOCore.broadcast_push_data.onmessage = (event) => {
						if (event && event.data) {
							// update pushed data map for the next watching event
							VBOCore.widgets_pushed_data.push(event.data);
						}
					};
				}

				// set up watch-events broadcast channel
				if (!VBOCore.broadcast_watch_events) {
					// start broadcast channel
					VBOCore.broadcast_watch_events = new BroadcastChannel('vikbooking_watch_events');

					// register to the "on broadcast message received" event
					VBOCore.broadcast_watch_events.onmessage = (event) => {
						if (event && event.data && typeof event.data === 'object') {
							// scan and dispatch the events data received
							let events_data = event.data;
							for (let ev_name in events_data) {
								if (!events_data.hasOwnProperty(ev_name)) {
									continue;
								}
								// emit the event
								VBOCore.emitEvent(ev_name, events_data[ev_name]);
							}
						}
					};
				}
			}
		}

		/**
		 * Periodic widgets data watching for new events.
		 */
		static watchWidgetsData() {
			if (typeof VBOCore.widgets_watch_data !== 'object' || VBOCore.widgets_watch_data == null) {
				return;
			}

			// call on new events
			VBOCore.doAjax(
				VBOCore.options.watchdata_ajax_uri,
				{
					watch_data:  JSON.stringify(VBOCore.widgets_watch_data),
					pushed_data: JSON.stringify(VBOCore.widgets_pushed_data),
				},
				(response) => {
					try {
						var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
						if (obj_res.hasOwnProperty('watch_data')) {
							// update watch data map for next schedule
							VBOCore.widgets_watch_data = obj_res['watch_data'];

							// check for notifications
							if (obj_res.hasOwnProperty('notifications') && Array.isArray(obj_res['notifications'])) {
								// dispatch notifications
								for (var i = 0; i < obj_res['notifications'].length; i++) {
									VBOCore.dispatchNotification(obj_res['notifications'][i]);
								}

								// post message onto broadcast channel for any other browsing context
								if (VBOCore.broadcast_watch_data && obj_res['notifications'].length) {
									// this will avoid dispatching duplicate notifications
									VBOCore.broadcast_watch_data.postMessage(VBOCore.widgets_watch_data);
								}
							}

							// check for notification events to dispatch
							if (obj_res.hasOwnProperty('events') && Array.isArray(obj_res['events'])) {
								// parse and dispatch all the events
								obj_res['events'].forEach((events_data) => {
									if (typeof events_data !== 'object') {
										return;
									}

									// parse all the events for this widget watched data
									for (let ev_name in events_data) {
										if (!events_data.hasOwnProperty(ev_name)) {
											continue;
										}
										// emit the event
										VBOCore.emitEvent(ev_name, events_data[ev_name]);
									}

									// post message onto broadcast channel for any other browsing context
									if (VBOCore.broadcast_watch_events) {
										// this will trigger the events on any other browsing context
										VBOCore.broadcast_watch_events.postMessage(events_data);
									}
								});
							}
						} else {
							console.error('Unexpected or invalid JSON response', response);
						}
					} catch(err) {
						console.error('could not parse JSON response', err, response);
					}
				},
				(error) => {
					console.error(error.responseText);
				}
			);
		}

		/**
		 * Widget modal rendering.
		 * 
		 * @param 	string 	widget_id 	the widget identifier to render.
		 * @param 	any 	data 		the optional multitask data to inject.
		 * @param 	object 	options 	optional list of extra options to render the widget with Multitask data.
		 * @param 	bool 	hide_panel 	if false, the multitask panel elements will remain unchanged.
		 * 
		 * @return 	object 				the multitask data injected object merged with modal options.
		 */
		static renderModalWidget(widget_id, data, options, hide_panel) {
			/**
			 * Adjust arguments for BC with previous ordering between options
			 * and hide_panel, which used to be inverted. Useful also to shorten
			 * the way the method can be invoked in case no options are needed.
			 */
			if (typeof options === 'boolean') {
				// switch argument values
				hide_panel = options;
				options    = null;
			}

			if (typeof data !== 'object' || data == null) {
				// always treat data as an object
				data = {};
			}

			if (typeof options !== 'object' || options == null) {
				// always treat options as an object
				options = data._options || {};
			}

			// build the default widget payload
			let modal_js_id = Math.floor(Math.random() * 100000);
			let modal_title = options._push && options.title ? options.title : VBOCore.options.tn_texts.admin_widget;
			let widget_data = {
				_modalRendering: 1,
				_modalJsId: modal_js_id,
				_modalTitle: modal_title,
				_options: options,
			};

			// merge default payload options with given options
			data = Object.assign(widget_data, data);

			// define unique modal event names to avoid conflicts
			let dismiss_event = 'vbo-dismiss-widget-modal' + modal_js_id;
			let loading_event = 'vbo-loading-widget-modal' + modal_js_id;
			let resize_event  = 'vbo-resize-widget-modal' + modal_js_id;

			// define the modal options
			let modal_options = {
				suffix: 	     'widget_modal',
				extra_class:     'vbo-modal-widget vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
				title: 		     data._modalTitle,
				body_prepend: 	 true,
				lock_scroll: 	 true,
				draggable: 	 	 true,
				enlargeable:     true,
				minimizeable:    VBOCore.options.is_vbo,
				resize_event:    resize_event,
				dismiss_event:   dismiss_event,
				loading_event:   loading_event,
				loading_body:    VBOCore.options.default_loading_body,
				dismissed_event: VBOCore.options.widget_modal_dismissed + modal_js_id,
				event_data: 	 widget_data,
			};

			// check if options should rewrite some modal-options
			if (options.modal_options) {
				modal_options = Object.assign(modal_options, options.modal_options);
			}

			// display modal
			let widget_modal = VBOCore.displayModal(modal_options);

			if (hide_panel !== false) {
				// blur search widget input, hide multitask panel
				$(VBOCore.options.panel_opts.search_selector).trigger('blur');
				VBOCore.emitEvent(VBOCore.multitask_shortcut_event);
			}

			// start loading
			VBOCore.emitEvent(loading_event);

			// render admin widget
			VBOCore.renderAdminWidget(widget_id, data).then((content) => {
				// stop loading and append widget content to modal
				VBOCore.emitEvent(loading_event);
				widget_modal.append(content);

				// register an admin menu action for the rendered widget
				VBOCore.fetchWidgetDetails(widget_id).then((details) => {
					// update modal title, if default title is present
					if (modal_options.title == VBOCore.options.tn_texts.admin_widget) {
						widget_modal
							.closest('.vbo-modal-overlay-content')
							.find('.vbo-modal-overlay-content-head-title')
							.text(data._modalTitle + ' - ' + details.name);
					}

					// check if this widget returned a particular modal setup
					if (details?.modal) {
						if (details.modal?.add_class) {
							// add custom modal class
							widget_modal
								.closest('.vbo-modal-overlay-content')
								.addClass(details.modal.add_class);

							// fire the "resize" event with a delay to support the CSS transition
							setTimeout(() => {
								VBOCore.emitEvent(resize_event, {
									content: widget_modal,
								});
							}, 400);
						}
					}

					// set data for widget details
					widget_modal.data('details', JSON.stringify(details));

					// register admin menu action
					try {
						// work on Local Storage to register the widget data
						VBOCore.registerAdminMenuAction({
							name:    details.name,
							href:    'JavaScript: void(0);',
							widget:  widget_id,
							icon:    details.icon,
							style:   details.style,
						}, 'widgets');
					} catch(e) {
						console.error(e);
					}
				}).catch((error) => {
					console.error(error);
				});
			}).catch((error) => {
				// dismiss modal and display error
				VBOCore.emitEvent(dismiss_event);
				alert(error);
			});

			return Object.assign(data, modal_options);
		}

		/**
		 * Renders an admin widget.
		 * 
		 * @param 	string 	widget_id 	the widget identifier string to add.
		 * @param 	any 	data 		the optional multitask data to inject.
		 * 
		 * @return 	Promise
		 */
		static renderAdminWidget(widget_id, data) {
			return new Promise((resolve, reject) => {
				if (!VBOCore.options.widget_ajax_uri) {
					reject('Could not load admin widget');
					return;
				}

				var call_method = 'render';
				VBOCore.doAjax(
					VBOCore.options.widget_ajax_uri,
					{
						widget_id: 		widget_id,
						call: 	   		call_method,
						vbo_page:  		VBOCore.options.current_page,
						vbo_uri:   		VBOCore.options.current_page_uri,
						multitask: 		1,
						multitask_data: data,
					},
					(response) => {
						// parse response
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								reject('Unexpected JSON response');
								return;
							}
							resolve(obj_res[call_method]);
						} catch(err) {
							reject('could not parse JSON response');
						}
					},
					(error) => {
						reject(error.responseText);
					}
				);
			});
		}

		/**
		 * Fetches the details for a specific widget.
		 * 
		 * @param 	string 	widget_id 	the widget identifier string to fetch.
		 * 
		 * @return 	Promise
		 * 
		 * @since 	1.6.7
		 */
		static fetchWidgetDetails(widget_id) {
			return new Promise((resolve, reject) => {
				if (!VBOCore.options.widget_ajax_uri) {
					reject('Could not load admin widget');
					return;
				}

				var call_method = 'getWidgetDetails';
				VBOCore.doAjax(
					VBOCore.options.widget_ajax_uri,
					{
						widget_id: widget_id,
						call: 	   call_method,
						return:    1,
					},
					(response) => {
						// parse response
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								reject('Unexpected JSON response');
								return;
							}
							resolve(obj_res[call_method]);
						} catch(err) {
							reject('could not parse JSON response');
						}
					},
					(error) => {
						reject(error.responseText);
					}
				);
			});
		}

		/**
		 * Helper method used to copy the text of an
		 * input element within the clipboard.
		 *
		 * Clipboard copy will take effect only in case the
		 * function is handled by a DOM event explicitly
		 * triggered by the user, such as a "click".
		 *
		 * @param 	any 	input 	The input containing the text to copy.
		 *
		 * @return 	Promise
		 */
		static copyToClipboard(input) {
			// register and return promise
			return new Promise((resolve, reject) => {
				// define a fallback function
				var fallback = function(input) {
					// focus the input
					input.focus();
					// select the text inside the input
					input.select();

					try {
						// try to copy with shell command
						var copy = document.execCommand('copy');

						if (copy) {
							// copied successfully
							resolve(copy);
						} else {
							// unable to copy
							reject(copy);
						}
					} catch (error) {
						// unable to exec the command
						reject(error);
					}
				};

				// look for navigator clipboard
				if (!navigator || !navigator.clipboard) {
					// navigator clipboard not supported, use fallback
					fallback(input);
					return;
				}

				// try to copy within the clipboard by using the navigator
				navigator.clipboard.writeText((input.val ? input.val() : input.value)).then(() => {
					// copied successfully
					resolve(true);
				}).catch((error) => {
					// revert to the fallback
					fallback(input);
				});
			});
		}

		/**
		 * Helper method used to display a modal window dinamycally.
		 *
		 * @param   object  options     The options to render the modal.
		 * @param   object  bindDetails Optional data to bind to the modal content.
		 *
		 * @return  object              The modal content element wrapper.
		 */
		static displayModal(options, bindDetails) {
			var def_options = {
				suffix:          (Math.floor(Math.random() * 100000)) + '',
				extra_class:     null,
				header:          true,
				title:           '',
				body:            '',
				body_prepend:    false,
				lock_scroll:     false,
				draggable:       false,
				enlargeable:     false,
				minimizeable:    false,
				escape_dismiss:  true,
				footer_left:     null,
				footer_right:    null,
				resize_event:    null,
				dismiss_event:   null,
				dismissed_event: 'vbo-modal-dismissed',
				onDismiss:       null,
				onMinimize:      null,
				onRestore:       null,
				loading_event:   null,
				loading_body:    VBOCore.options.default_loading_body,
				progress_event:  null,
				event_data:      null,
			};

			if (options.event_data && typeof options.event_data === 'object') {
				// get rid of possible cyclic object references
				options.event_data = Object.assign({}, options.event_data);
			}

			// merge default options with given options
			options = Object.assign(def_options, options);

			// create the modal destroy function
			const modal_destroy_fn = (e) => {
				// invoke callback for onDismiss
				if (typeof options.onDismiss === 'function') {
					options.onDismiss.call(custom_modal, e);
				}

				// check if modal did register to the loading event
				if (options.loading_event) {
					// we can now un-register from the loading event until a new modal is displayed and will register to it again
					document.removeEventListener(options.loading_event, modal_handle_loading_event_fn);
					if (options.progress_event) {
						// we can now un-register from the progress event
						document.removeEventListener(options.progress_event, modal_handle_progress_event_fn);
					}
				}

				// check if we should fire the given modal dismissed event
				if (options.dismissed_event) {
					VBOCore.emitEvent(options.dismissed_event, options.event_data);
				}

				// check if body scroll lock should be removed
				if (options.lock_scroll) {
					$('body').removeClass('vbo-modal-lock-scroll');
				}

				// remove modal from DOM
				custom_modal.remove();
			};

			// create the modal dismiss function
			const modal_dismiss_fn = (e) => {
				custom_modal.fadeOut(400, () => {
					// destroy the modal
					modal_destroy_fn.call(custom_modal, e);
				});
			};

			// create the modal loading event handler function
			const modal_handle_loading_event_fn = (e) => {
				// toggle modal loading
				if ($('.vbo-modal-overlay-content-backdrop').length) {
					// hide loading
					$('.vbo-modal-overlay-content-backdrop').remove();

					// do not proceed
					return;
				}

				// show loading
				var modal_loading = $('<div></div>').addClass('vbo-modal-overlay-content-backdrop');
				var modal_loading_body = $('<div></div>').addClass('vbo-modal-overlay-content-backdrop-body');
				if (options.loading_body) {
					modal_loading_body.append(options.loading_body);
				}
				modal_loading.append(modal_loading_body);

				// append backdrop loading to modal content
				modal_content.prepend(modal_loading);
			};

			// create the modal progress event handler function
			const modal_handle_progress_event_fn = (e) => {
				if (!e || !e.detail || !e.detail.progress_content) {
					// do not proceed
					return;
				}

				// identify the current loading backdrop body
				let backdrop_body = modal_content.find('.vbo-modal-overlay-content-backdrop-body');
				if (!backdrop_body.length) {
					// loading body not found
					return;
				}

				let backdrop_txt_el = $('<div></div>').addClass('vbo-modal-overlay-content-backdrop-text');
				if (typeof e.detail.progress_content === 'string') {
					backdrop_txt_el.html(e.detail.progress_content);
				} else {
					backdrop_txt_el.append(e.detail.progress_content);
				}

				let backdrop_txt_old = backdrop_body.find('.vbo-modal-overlay-content-backdrop-text');
				if (backdrop_txt_old.length) {
					// remove the previous text element
					backdrop_txt_old.remove();
				}

				// set the loading progress content
				backdrop_body.append(backdrop_txt_el);
			};

			// create the modal enlarge (toggle) function
			const modal_enlarge_fn = (e) => {
				// toggle modal fullscreen class
				modal_content.toggleClass('vbo-modal-fullscreen');

				// check if modal did register a resize event
				if (options.resize_event) {
					// fire the requested event with a delay to support the CSS transition
					setTimeout(() => {
						VBOCore.emitEvent(options.resize_event, {
							content: modal_content,
						});
					}, 400);
				}
			};

			// create the modal minimize function
			const modal_minimize_fn = (e) => {
				// invoke callback for onMinimize
				if (typeof options.onMinimize === 'function') {
					options.onMinimize.call(custom_modal, e);
				}

				// start minimizing animation
				custom_modal.addClass('vbo-minimizing');
				modal_content.addClass('vbo-minimizing');

				// access the modal body details data, if any
				let details_data = modal_content_wrapper.data('details');
				try {
					if (details_data && typeof details_data === 'string') {
						details_data = JSON.parse(details_data);
					}
				} catch(e) {
					details_data = {};
				}

				// register delayed partial-distruction
				setTimeout(() => {
					// hide modal
					custom_modal.hide();

					// check if body scroll lock should be removed
					if (options.lock_scroll) {
						$('body').removeClass('vbo-modal-lock-scroll');
					}

					// add the minimized widget to dock
					VBOCore.getAdminDock().addWidget(details_data, options, modal_content_wrapper, modal_destroy_fn);

					// remove modal from DOM without firing the dismiss events (do not destroy the modal)
					custom_modal.remove();
				}, 400);
			};

			// start the modal position variables
			var modal_pos_x = 0, modal_pos_y = 0;

			// create the modal drag-start (mousedown) event handler function
			const modal_dragstart_fn = (e) => {
				e = e || window.event;
				e.preventDefault();

				if (typeof e.clientX === 'undefined' || typeof e.clientY === 'undefined') {
					// unsupported
					return;
				}

				if (e.target && !e.target.matches('.vbo-modal-overlay-cmd')) {
					e.target.style.cursor = 'move';
				}

				// store the initial modal (cursor) position
				modal_pos_x = e.clientX;
				modal_pos_y = e.clientY;

				// register mouseup and mousemove events
				document.onmouseup   = modal_dragstop_fn;
				document.onmousemove = modal_dragmove_fn;
			};

			// create the modal drag-stop (mouseup) event handler function
			const modal_dragstop_fn = (e) => {
				e = e || window.event;

				if (e.target && !e.target.matches('.vbo-modal-overlay-cmd')) {
					e.target.style.cursor = 'auto';
				}

				// unregister mousemove event
				if (document.onmousemove == modal_dragmove_fn) {
					document.onmousemove = null;
				}

				// unregister mouseup event
				if (document.onmouseup == modal_dragstop_fn) {
					document.onmouseup = null;
				}
			};

			// create the modal drag-move (mousemove) event handler function
			const modal_dragmove_fn = (e) => {
				e = e || window.event;
				e.preventDefault();

				if (typeof e.clientX === 'undefined' || typeof e.clientY === 'undefined') {
					// unsupported
					return;
				}

				// calculate the new modal (cursor) position
				let new_modal_pos_x = modal_pos_x - e.clientX;
				let new_modal_pos_y = modal_pos_y - e.clientY;

				// update current modal (cursor) position
				modal_pos_x = e.clientX;
				modal_pos_y = e.clientY;

				// find the modal element
				let modal_element = e.target.closest('.vbo-modal-overlay-content');
				if (!modal_element) {
					return;
				}

				// set the modal position
				modal_element.style.top  = (modal_element.offsetTop - new_modal_pos_y) + 'px';
				modal_element.style.left = (modal_element.offsetLeft - new_modal_pos_x) + 'px';
			};

			// build modal content
			const custom_modal = $('<div></div>').addClass('vbo-modal-overlay-block vbo-modal-overlay-' + options.suffix).css('display', 'block');
			var modal_dismiss = $('<a></a>').addClass('vbo-modal-overlay-close');
			modal_dismiss.on('click', modal_dismiss_fn);
			custom_modal.append(modal_dismiss);

			const modal_content = $('<div></div>').addClass('vbo-modal-overlay-content vbo-modal-overlay-content-' + options.suffix);
			if (options.extra_class && typeof options.extra_class === 'string') {
				modal_content.addClass(options.extra_class);
			}

			// modal head and title
			const modal_head = $('<div></div>').addClass('vbo-modal-overlay-content-head');
			if (options.title) {
				let modal_title = $('<span></span>').addClass('vbo-modal-overlay-content-head-title').html(options.title);
				modal_head.append(modal_title);
			} else {
				modal_head.addClass('vbo-modal-head-no-title');
			}
			// modal head commands
			var modal_head_cmds = $('<span></span>').addClass('vbo-modal-overlay-cmds');
			var modal_head_close = $('<span></span>').addClass('vbo-modal-overlay-cmd vbo-modal-overlay-close-times').html('&times;');
			modal_head_close.on('click', modal_dismiss_fn);
			modal_head_cmds.append(modal_head_close);
			if (options.enlargeable) {
				var modal_head_enlarge = $('<span></span>').addClass('vbo-modal-overlay-cmd vbo-modal-overlay-cmd-enlarge').html('&square;');
				modal_head_enlarge.on('click', modal_enlarge_fn);
				modal_head_cmds.append(modal_head_enlarge);
				modal_head.on('dblclick', modal_enlarge_fn);
			}
			if (options.minimizeable && options.event_data) {
				// set up the minimize command
				var modal_head_minimize = $('<span></span>').addClass('vbo-modal-overlay-cmd vbo-modal-overlay-cmd-minimize').html('&minus;');
				modal_head_minimize.on('click', modal_minimize_fn);
				modal_head_cmds.append(modal_head_minimize);
			}
			// set commands
			modal_head.append(modal_head_cmds);

			// check if the modal head should be draggable
			if (options.draggable) {
				// register the event(s) to allow dragging
				modal_head.addClass('vbo-modal-head-draggable');
				modal_head.on('contextmenu', (e) => {
					e.preventDefault();
				});
				modal_head.on('mousedown', modal_dragstart_fn);
			}

			const modal_body = $('<div></div>').addClass('vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll');
			const modal_content_wrapper = $('<div></div>').addClass('vbo-modal-' + options.suffix + '-wrap');
			if (options.suffix != 'widget_modal') {
				modal_content_wrapper.addClass('vbo-modal-widget_modal-wrap');
			}
			if (typeof options.body === 'string') {
				modal_content_wrapper.html(options.body);
			} else {
				modal_content_wrapper.append(options.body);
			}
			modal_body.append(modal_content_wrapper);

			// modal footer
			let modal_footer = null;
			if (options.footer_left || options.footer_right) {
				modal_footer = $('<div></div>').addClass('vbo-modal-overlay-content-footer');
				if (options.footer_left) {
					let modal_footer_left = $('<div></div>').addClass('vbo-modal-overlay-content-footer-left').append(options.footer_left);
					modal_footer.append(modal_footer_left);
				}
				if (options.footer_right) {
					let modal_footer_right = $('<div></div>').addClass('vbo-modal-overlay-content-footer-right').append(options.footer_right);
					modal_footer.append(modal_footer_right);
				}

			}

			// finalize modal contents
			if (options.header) {
				// append header
				modal_content.append(modal_head);
			}
			// append body
			modal_content.append(modal_body);
			if (modal_footer) {
				// append footer
				modal_content.append(modal_footer);
			}
			custom_modal.append(modal_content);

			// register to the dismiss event
			if (options.dismiss_event) {
				// listen to the event that will dismiss the modal
				document.addEventListener(options.dismiss_event, function vbo_core_handle_dismiss_event(e) {
					// make sure the same event won't propagate again, unless a new modal is displayed (multiple displayModal calls)
					e.target.removeEventListener(e.type, vbo_core_handle_dismiss_event);

					// invoke the modal dismiss function
					modal_dismiss_fn(e);
				});

				// declare the function to detect the Escape key pressed
				const vbo_core_dismiss_event_modal_escape = (e) => {
					if (!e.key || e.key != 'Escape') {
						return;
					}

					// immediately unregister from this event once fired
					window.removeEventListener(e.type, vbo_core_dismiss_event_modal_escape);

					// trigger the actual dismiss event
					VBOCore.emitEvent(options.dismiss_event);
				};

				if (options.escape_dismiss) {
					// listen to the Escape keyup event to dismiss the modal
					// listen on window to allow admin-widgets to prevent the default behavior and stop the propagation
					window.addEventListener('keyup', vbo_core_dismiss_event_modal_escape);
				}
			}

			// register to the toggle-loading event
			if (options.loading_event) {
				// let a function handle it so that removing the event listener will be doable
				document.addEventListener(options.loading_event, modal_handle_loading_event_fn);
				if (options.progress_event) {
					// let a function handle the progress event so that removing the listener will be doable
					document.addEventListener(options.progress_event, modal_handle_progress_event_fn);
				}
			}

			// append (or prepend) modal to body
			if ($('.vbo-modal-overlay-' + options.suffix).length) {
				$('.vbo-modal-overlay-' + options.suffix).remove();
			}
			if (options.body_prepend) {
				// prepend to body
				if ($('body > .vbo-modal-overlay-block').length) {
					// we've got other modals prepended to the body, so go after the last one
					$('body > .vbo-modal-overlay-block').last().after(custom_modal);
				} else {
					// place the modal right as the first child node of body
					$('body').prepend(custom_modal);
				}
			} else {
				// append to body
				$('body').append(custom_modal);
			}

			// check if scroll should be locked on the whole page body for a "sticky" modal
			if (options.lock_scroll) {
				$('body').addClass('vbo-modal-lock-scroll');
			}

			if (typeof bindDetails === 'object') {
				try {
					// bind widget details data
					modal_content_wrapper.data('details', JSON.stringify(bindDetails));
				} catch(e) {
					// do nothing
				}
			}

			// return the content wrapper element of the new modal
			return modal_content_wrapper;
		}

		/**
		 * Debounce technique to group a flurry of events into one single event.
		 */
		static debounceEvent(func, wait, immediate) {
			var timeout;
			return function() {
				var context = this, args = arguments;
				var later = function() {
					timeout = null;
					if (!immediate) func.apply(context, args);
				};
				var callNow = immediate && !timeout;
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
				if (callNow) {
					func.apply(context, args);
				}
			}
		}

		/**
		 * Throttle guarantees a constant flow of events at a given time interval.
		 * Runs immediately when the event takes place, but can be delayed.
		 */
		static throttleEvent(method, delay) {
			var time = Date.now();
			return function() {
				if ((time + delay - Date.now()) < 0) {
					method();
					time = Date.now();
				}
			}
		}

		/**
		 * Alternative throttle technique for event listeners.
		 * 
		 * @param 	function 	callback 	the callback to invoke.
		 * @param 	int 		time 		the time (in ms) to throttle.
		 * 
		 * @return 	void
		 * 
		 * @since 	1.6.8
		 */
		static throttleTimer(callback, time) {
			if (VBOCore.throttle_timer) {
				// prevent more executions
				return;
			}

			// turn throttle timer on
			VBOCore.throttle_timer = true;

			setTimeout(() => {
				// run the callback with the given delay
				callback();

				// turn throttle timer off
				VBOCore.throttle_timer = false;
			}, time);
		}

		/**
		 * Tells whether localStorage is supported.
		 * 
		 * @return 	boolean
		 */
		static storageSupported() {
			return typeof localStorage !== 'undefined';
		}

		/**
		 * Gets an item from localStorage.
		 * 
		 * @param 	string 	keyName 	the storage key identifier.
		 * 
		 * @return 	any
		 */
		static storageGetItem(keyName) {
			if (!VBOCore.storageSupported()) {
				return null;
			}

			return localStorage.getItem(keyName);
		}

		/**
		 * Sets an item to localStorage.
		 * 
		 * @param 	string 	keyName 	the storage key identifier.
		 * @param 	any 	value 		the value to store.
		 * 
		 * @return 	boolean
		 */
		static storageSetItem(keyName, value) {
			if (!VBOCore.storageSupported()) {
				return false;
			}

			try {
				if (typeof value === 'object') {
					value = JSON.stringify(value);
				}

				localStorage.setItem(keyName, value);
			} catch(e) {
				console.error(e);
				return false;
			}

			return true;
		}

		/**
		 * Removes an item from localStorage.
		 * 
		 * @param 	string 	keyName 	the storage key identifier.
		 * 
		 * @return 	boolean
		 */
		static storageRemoveItem(keyName) {
			if (!VBOCore.storageSupported()) {
				return false;
			}

			localStorage.removeItem(keyName);

			return true;
		}

		/**
		 * Returns the name of the storage identifier for the given scope.
		 * 
		 * @param 	string 	scope 	the admin menu scope.
		 * 
		 * @return 	string 			the requested admin menu storage identifier.
		 */
		static getStorageScopeName(scope) {
			let storage_scope_name = VBOCore.options.admin_menu_actions_nm;

			if (typeof scope === 'string' && scope.length) {
				if (scope.indexOf('.') !== 0) {
					scope = '.' + scope;
				}
				storage_scope_name += scope;
			}

			return storage_scope_name;
		}

		/**
		 * Returns a list of admin menu action objects or an empty array.
		 * 
		 * @param 	string 	scope 	the admin menu scope.
		 * 
		 * @return 	Array
		 */
		static getAdminMenuActions(scope) {
			let menu_actions = VBOCore.storageGetItem(VBOCore.getStorageScopeName(scope));

			if (!menu_actions) {
				return [];
			}

			try {
				menu_actions = JSON.parse(menu_actions);
				if (!Array.isArray(menu_actions) || !menu_actions.length) {
					menu_actions = [];
				}
			} catch(e) {
				return [];
			}

			return menu_actions;
		}

		/**
		 * Builds an admin menu action object with a proper href property.
		 * 
		 * @param 	object 	action 	the action to build.
		 * 
		 * @return 	object
		 */
		static buildAdminMenuAction(action) {
			if (typeof action !== 'object' || !action || !action.hasOwnProperty('name')) {
				throw new Error('Invalid action object');
			}

			var action_base = action.hasOwnProperty('href') && typeof action['href'] == 'string' ? action['href'] : window.location.href;
			var action_url;

			if (action_base.toLowerCase().indexOf('javascript') === 0 && action.hasOwnProperty('widget')) {
				// no need to prepare the action URL ("JavaScript: void(0)") as the link will use a JS callback
				return action;
			}

			if (action_base.indexOf('http') !== 0) {
				// relative URL
				action_url = new URL(action_base, window.location.href);
			} else {
				// absolute URL
				action_url = new URL(action_base);
			}

			// build proper href with a relative URL
			action['href'] = action_url.pathname + action_url.search;

			return action;
		}

		/**
		 * Registers an admin menu action object.
		 * 
		 * @param 	object 	action 	the action to build.
		 * @param 	string 	scope 	the admin menu scope.
		 * 
		 * @return 	boolean
		 */
		static registerAdminMenuAction(action, scope) {
			// build menu action object
			let menu_action_entry = VBOCore.buildAdminMenuAction(action);

			let menu_actions = VBOCore.getAdminMenuActions(scope);

			// make sure we are not pushing a duplicate and count pinned actions
			let pinned_actions = 0;
			let unpinned_index = [];
			for (let i = 0; i < menu_actions.length; i++) {
				// avoid duplicate entries
				if (!menu_action_entry.hasOwnProperty('widget') && menu_actions[i]['href'] == menu_action_entry['href']) {
					// duplicate link
					return false;
				}
				if (menu_action_entry.hasOwnProperty('widget') && menu_actions[i].hasOwnProperty('widget') && menu_actions[i]['widget'] == menu_action_entry['widget']) {
					// duplicate widget
					return false;
				}
				if (menu_actions[i].hasOwnProperty('pinned') && menu_actions[i]['pinned']) {
					pinned_actions++;
				} else {
					unpinned_index.push(i);
				}
			}

			if (pinned_actions >= VBOCore.options.admin_menu_maxactions) {
				// no more space to register a new menu action for this admin menu
				return false;
			}

			// splice or pop before prepending to keep current indexes
			let tot_menu_actions = menu_actions.length;
			if (++tot_menu_actions > VBOCore.options.admin_menu_maxactions) {
				if (unpinned_index.length) {
					menu_actions.splice(unpinned_index[unpinned_index.length - 1], 1);
				} else {
					menu_actions.pop();
				}
			}

			// prepend new admin menu action
			menu_actions.unshift(menu_action_entry);

			return VBOCore.storageSetItem(VBOCore.getStorageScopeName(scope), menu_actions);
		}

		/**
		 * Updates an existing admin menu action object.
		 * 
		 * @param 	object 	action 	the action to build.
		 * @param 	string 	scope 	the admin menu scope.
		 * @param 	number 	index 	optional menu action index.
		 * 
		 * @return 	boolean
		 */
		static updateAdminMenuAction(action, scope, index) {
			// build menu action object
			let menu_action_entry = VBOCore.buildAdminMenuAction(action);

			let menu_actions = VBOCore.getAdminMenuActions(scope);

			if (!menu_actions.length) {
				return false;
			}

			if (typeof index === 'undefined') {
				// find the proper index to update by href
				for (let i = 0; i < menu_actions.length; i++) {
					if (menu_actions[i].hasOwnProperty('widget') && menu_action_entry.hasOwnProperty('widget') && menu_actions[i]['widget'] == menu_action_entry['widget']) {
						// existing entry for widget found
						index = i;
						break;
					} else if (!menu_action_entry.hasOwnProperty('widget') && menu_actions[i]['href'] == menu_action_entry['href']) {
						// existing entry for link found
						index = i;
						break;
					}
				}
			}

			if (isNaN(index) || !(index in menu_actions)) {
				// menu entry index not found
				return false;
			}

			menu_actions[index] = menu_action_entry;

			return VBOCore.storageSetItem(VBOCore.getStorageScopeName(scope), menu_actions);
		}

		/**
		 * Checks if the current menu actions should be filled with some other actions.
		 * No update is made over the Local Storage through this method.
		 * 
		 * @param 	array 	menu_actions  	 menu actions to fill.
		 * @param 	array 	widgets_actions  default actions to use for filling.
		 * @param 	string 	scope 			 the admin menu scope.
		 * 
		 * @return 	array 					 the menu actions eventually filled.
		 */
		static fillAdminMenuActions(menu_actions, widgets_actions, scope) {
			if (!Array.isArray(menu_actions) || !Array.isArray(widgets_actions)) {
				return [];
			}

			if (!menu_actions.length || !widgets_actions.length || menu_actions.length >= VBOCore.options.admin_menu_maxactions) {
				return menu_actions;
			}

			var current_widgets = [];
			menu_actions.forEach((action, index) => {
				current_widgets.push(action['widget']);
			});

			for (var i = 0; i < widgets_actions.length; i++) {
				if (menu_actions.length >= VBOCore.options.admin_menu_maxactions) {
					// abort for filling completed
					break;
				}

				if (!widgets_actions[i].hasOwnProperty('widget')) {
					// action must be for a widget
					continue;
				}

				if (current_widgets.includes(widgets_actions[i]['widget'])) {
					// there cannot be duplicate entries
					continue;
				}

				// fill menu action
				menu_actions.push(widgets_actions[i]);
			}

			return menu_actions;
		}

		/**
		 * Formats a date object by accepting "Y", "m" and "d".
		 * 
		 * @param 	Date 	date 	The date object to format.
		 * @param 	string 	format 	The date format to use.
		 * 
		 * @return 	string
		 */
		static formatDate(date, format) {
			if (!date instanceof Date) {
				throw new Error('Invalid date object given.');
			}

			if (!format || typeof format !== 'string') {
				// default to military format
				format = 'Y-m-d';
			}

			let year = date.getFullYear();
			let month = (date.getMonth() + 1) + '';
			let mday = (date.getDate()) + '';

			month = month.length < 2 ? '0' + month : month;
			mday = mday.length < 2 ? '0' + mday : mday;

			// use a regex to capture the date separator and format identifiers
			const regexp = /^([ymd])([\s\.\-\/])([ymd])([\s\.\-\/])([ymd])$/gi;

			// turn the iterable iterator object into an array by using the spread syntax
			let formatArray = [...format.matchAll(regexp)];

			if (!formatArray.length || !Array.isArray(formatArray[0]) || formatArray[0].length != 6) {
				throw new Error('Invalid date format given.');
			}

			let firstIdentifier = (formatArray[0][1] + '').toLowerCase();
			let separatorChar = formatArray[0][2];
			let secondIdentifier = (formatArray[0][3] + '').toLowerCase();
			let thirdIdentifier = (formatArray[0][5] + '').toLowerCase();

			if (!separatorChar) {
				throw new Error('Invalid date format separator given.');
			}

			let dateParts = [];

			if (firstIdentifier == 'y') {
				dateParts.push(year);
			} else if (firstIdentifier == 'm') {
				dateParts.push(month);
			} else {
				dateParts.push(mday);
			}

			if (secondIdentifier == 'y') {
				dateParts.push(year);
			} else if (secondIdentifier == 'm') {
				dateParts.push(month);
			} else {
				dateParts.push(mday);
			}

			if (thirdIdentifier == 'y') {
				dateParts.push(year);
			} else if (thirdIdentifier == 'm') {
				dateParts.push(month);
			} else {
				dateParts.push(mday);
			}

			return dateParts.join(separatorChar);
		}

		/**
		 * Proxy to access the VBOCurrency object.
		 * 
		 * @param 	object 	options The currency options.
		 * 
		 * @return 	VBOCurrency
		 */
		static getCurrency(options) {
			return VBOCurrency.getInstance(options);
		}

		/**
		 * Proxy to access the VBOAdminDock object.
		 * 
		 * @return 	VBOAdminDock
		 */
		static getAdminDock() {
			return VBOAdminDock.getInstance();
		}
	}

	/**
	 * These used to be private static properties (static #options),
	 * but they are only supported by quite recent browsers (especially Safari).
	 * It's too premature, so we decided to keep the class properties public
	 * without declaring them as static inside the class declaration.
	 * 
	 * @var  object
	 */
	VBOCore.options = CORE_OPTIONS || {
		is_vbo: 				false,
		is_vcm: 				false,
		cms: 					'wordpress',
		widget_ajax_uri: 		null,
		assets_ajax_uri: 		null,
		multitask_ajax_uri: 	null,
		watchdata_ajax_uri: 	null,
		current_page: 			null,
		current_page_uri: 		null,
		root_uri: 				'/',
		client: 				'admin',
		panel_opts: 			{},
		admin_widgets: 			[],
		notif_audio_url: 		'',
		active_listeners: 		{},
		tn_texts: 				{
			notifs_enabled: 		'Browser notifications are enabled!',
			notifs_disabled: 		'Browser notifications are disabled',
			notifs_disabled_help: 	"Could not enable browser notifications.\nThis feature is available only in secure contexts (HTTPS).",
			admin_widget: 			'Admin widget',
			congrats: 				'Congratulations!',
		},
		default_loading_body: 	'....',
		multitask_save_event: 	'vbo-admin-multitask-save',
		multitask_open_event: 	'vbo-admin-multitask-open',
		multitask_close_event: 	'vbo-admin-multitask-close',
		multitask_shortcut_ev: 	'vbo_multitask_shortcut',
		multitask_searchfs_ev: 	'vbo_multitask_search_focus',
		widget_modal_rendered: 	'vbo-admin-widget-modal-rendered',
		widget_modal_dismissed: 'vbo-widget-modal-dismissed',
		admin_menu_maxactions: 	3,
		admin_menu_actions_nm: 	'vikbooking.admin_menu.actions',
		service_worker_path: 	'',
		service_worker_scope: 	'./',
		push: 					{
			application_key: '',
			ajax_url: 		 '',
		},
		push_options: {
			storage_endp_id: 	 'vbo_push_subscr_endp',
			allowed_notif_types: [
				'Book',
				'Modify',
				'Cancel',
				'Request',
				'CancelRequest',
				'Inquiry',
				'CancelInquiry',
				'Chat',
				'Info',
			],
		},
	};

	/**
	 * @var  bool
	 */
	VBOCore.side_panel_on = false;

	/**
	 * @var  	bool
	 * @since 	1.6.8
	 */
	VBOCore.throttle_timer = false;

	/**
	 * @var  array
	 */
	VBOCore.notifications = [];

	/**
	 * @var  object
	 */
	VBOCore.widgets_watch_data = null;

	/**
	 * @var  number
	 */
	VBOCore.watch_data_interval = null;

	/**
	 * @var  	object
	 * @since 	1.6.3
	 */
	VBOCore.broadcast_watch_data = null;

	/**
	 * @var  	object
	 * @since 	1.6.5
	 */
	VBOCore.broadcast_push_data = null;

	/**
	 * @var  	object
	 * @since 	1.6.8
	 */
	VBOCore.broadcast_watch_events = null;

	/**
	 * @var  	array<object>
	 * @since 	1.6.5
	 */
	VBOCore.widgets_pushed_data = [];

	/**
	 * Checks if the KeyBoard event matches the given shortcut.
	 *
	 * @param 	array 	 keys 	The shortcut representation.
	 *
	 * @return 	boolean  True if matches, otherwise false.
	 * 
	 * @since 	1.7.0
	 */
	KeyboardEvent.prototype.shortcut = function(keys) {
		// get modifiers list
		var modifiers = keys.slice(0);
		// pop character from modifiers
		var keyCode = modifiers.pop();

		if (typeof keyCode === 'string') {
			// get ASCII
			keyCode = keyCode.toUpperCase().charCodeAt(0);
		}

		// make sure the modifiers are lower case
		modifiers = modifiers.map(function(mod) {
			return mod.toLowerCase();
		});

		var ok = false;

		// validate key code
		if (this.keyCode == keyCode) {
			// validate modifiers
			ok = true;
			var lookup = ['meta', 'shift', 'alt', 'ctrl'];

			for (var i = 0; i < lookup.length && ok; i++) {
				// check if modifiers is pressed
				var mod = this[lookup[i] + 'Key'];

				if (mod) {
					// if pressed, the shortcut must specify it
					ok &= modifiers.indexOf(lookup[i]) !== -1;
				} else {
					// if not pressed, the shortcut must not include it
					ok &= modifiers.indexOf(lookup[i]) === -1;
				}
			}
		}

		return ok;
	}

	/**
	 * VBOCurrency class implementation.
	 */
	w['VBOCurrency'] = class VBOCurrency {
		/**
		 * Singleton entry-point.
		 * 
		 * @see construct()
		 */
		static getInstance(options) {
			if (typeof VBOCurrency.instance === 'undefined') {
				VBOCurrency.instance = new VBOCurrency(options);
			}

			return VBOCurrency.instance;
		}

		/**
		 * Class constructor.
		 * 
		 * @param  object  options  The currency options object:
		 *                          - symbol           string  The currency symbol (such as €, $, £ and so on).
		 *                          - position         int     The position of the currency (1 before, 2 after). In case the amount is negative
		 *                                                     the space between the currency and the amount won't be used.
		 *                          - decimals         string  The decimals separator character ("." or ",").
		 *                          - thousands        string  The thousands separator character ("," or ".").
		 *                          - digits           int     The number of decimal digits.
		 *                          - noDecimals       int     Whether empty decimals should be omitted (1 true, 0 false).
		 *                          - conversionRate   float   The currency conversion rate.
		 */
		constructor(options) {
			this.setOptions(options);
		}

		/**
		 * Sets the currency options.
		 * 
		 * @param  object  options  The currency options object.
		 * 
		 * @see    construct()
		 */
		setOptions(options) {
			if (options === undefined) {
				options = {};
			}

			// define default currency options for eventually merging with new options
			let def_symbol      = this.symbol !== undefined ? this.symbol : '$';
			let def_position    = this.position !== undefined ? this.position : 1;
			let def_decimals    = this.decimals !== undefined ? this.decimals : '.';
			let def_thousands   = this.thousands !== undefined ? this.thousands : ',';
			let def_digits      = this.digits !== undefined ? this.digits : 2;
			let def_no_decimals = this.noDecimals !== undefined ? this.noDecimals : 1;
			let def_conv_rate   = this.conversionRate !== undefined ? this.conversionRate : 1;

			// set currency options
			this.symbol      = (options.hasOwnProperty('symbol')      ? options.symbol    : def_symbol);
			this.position    = (options.hasOwnProperty('position')    ? options.position  : def_position);
			this.decimals    = (options.hasOwnProperty('decimals')    ? options.decimals  : def_decimals);
			this.thousands   = (options.hasOwnProperty('thousands')   ? options.thousands : def_thousands);
			this.digits      = (options.hasOwnProperty('digits')      ? parseInt(options.digits) : def_digits);
			this.noDecimals  = (options.hasOwnProperty('noDecimals')  ? parseInt(options.noDecimals) : def_no_decimals);
			this.conversionRate = Math.abs((options.hasOwnProperty('conversionRate') ? parseFloat(options.conversionRate) : 1));
		}

		/**
		 * Gets the current currency options.
		 * 
		 * @return 	object
		 */
		getOptions() {
			let options = {};

			Object.keys(this).forEach((option) => {
				if (this.hasOwnProperty(option)) {
					options[option] = this[option];
				}
			});

			return options;
		}

		/**
		 * Formats the given price according to the configuration preferences.
		 * 
		 * @param   float   price    The price to format.
		 * @param   object  options  Temporarily overrides the currency options.
		 * 
		 * @return  string  The formatted price.
		 */
		format(price, options) {
			// merge currency settings
			options = Object.assign(this.getOptions(), (options || {}));

			let no_decimals = options.noDecimals;
			let dig = options.digits;

			if (no_decimals && parseInt(price) == price) {
				// no decimal digits in case of empty decimals
				dig = 0;
			}

			price = parseFloat(price) / options.conversionRate;

			// check whether the price is negative
			const isNegative = price < 0;

			// adjust to given decimals
			price = Math.abs(price).toFixed(dig);

			let _d = options.decimals;
			let _t = options.thousands;

			// make sure the decimal separator is a valid character
			if (!_d.match(/[.,\s]/)) {
				// revert to default one
				_d = '.';
			}

			// make sure the thousands separator is a valid character
			if (!_t.match(/[.,\s]/)) {
				// revert to default one
				_t = ',';
			}

			// make sure both the separators are not equals
			if (_d == _t) {
				_t = _d == ',' ? '.' : ',';
			}

			price = price.split('.');

			price[0] = price[0].replace(/./g, function(c, i, a) {
				return i > 0 && (a.length - i) % 3 === 0 ? _t + c : c;
			});

			if (isNegative) {
				// re-add negative sign
				price[0] = '-' + price[0];
			}

			if (price.length > 1) {
				price = price[0] + _d + price[1];
			} else {
				price = price[0];
			}

			if (Math.abs(options.position) == 1) {
				// do not use space in case the position is "-1"
				return options.symbol + (options.position == 1 ? ' ' : '') + price;
			}

			// do not use space in case the position is "-2"
			return price + (options.position == 2 ? ' ' : '') + options.symbol;
		}

		/**
		 * Safely sums 2 prices (a + b).
		 * 
		 * @param   float  a
		 * @param   float  b
		 * 
		 * @return  The resulting sum.
		 */
		sum(a, b) {
			// get rid of decimals for higher precision
			a *= Math.pow(10, this.digits);
			b *= Math.pow(10, this.digits);

			// do sum and go back to decimal
			return (Math.round(a) + Math.round(b)) / Math.pow(10, this.digits);
		}

		/**
		 * Safely subtracts 2 prices (a - b).
		 * 
		 * @param   float  a
		 * @param   float  b
		 * 
		 * @return  The resulting difference.
		 */
		diff(a, b) {
			// get rid of decimals for higher precision
			a *= Math.pow(10, this.digits);
			b *= Math.pow(10, this.digits);

			// do difference and go back to decimal
			return (Math.round(a) - Math.round(b)) / Math.pow(10, this.digits);
		}

		/**
		 * Safely multiplies 2 prices (a * b).
		 * 
		 * @param   float  a
		 * @param   float  b
		 * 
		 * @return  The resulting multiplication.
		 */
		multiply(a, b) {
			// get rid of decimals for higher precision
			a *= Math.pow(10, this.digits);
			b *= Math.pow(10, this.digits);

			// do multiplication and go back to decimal
			return (Math.round(a) * Math.round(b)) / Math.pow(10, this.digits * 2);
		}
	}

	/**
	 * VBOAdminDock class implementation.
	 */
	w['VBOAdminDock'] = class VBOAdminDock {
		/**
		 * Singleton entry-point.
		 * 
		 * @see construct()
		 */
		static getInstance(options) {
			if (typeof VBOAdminDock.instance === 'undefined') {
				VBOAdminDock.instance = new VBOAdminDock(options);
			}

			return VBOAdminDock.instance;
		}

		/**
		 * Class constructor.
		 * 
		 * @param  object  options  The admin dock options object.
		 */
		constructor(options) {
			// set options
			this.setOptions(options);

			// start empty dock elements property
			this._elements = [];

			// set dock storage identifier property
			this._storageId = 'vikbooking.admin_dock.elements';

			// set event name for updating the dock element badge counters
			this._updateBadgeEv = 'vbo-admin-dock-update-badge';

			// build dock node
			this.buildDockNode();

			// load dock elements
			this.loadDockElements();
		}

		/**
		 * Sets the options.
		 * 
		 * @param  object  options  The dock options object.
		 * 
		 * @see    construct()
		 */
		setOptions(options) {
			if (options === undefined) {
				options = {};
			}

			// default options
			let defaultOptions = {
				dockDisplayStyle: 'flex',
			};

			// set options
			this._options = Object.assign(defaultOptions, options);
		}

		/**
		 * Gets the current dock options.
		 * 
		 * @return 	object
		 */
		getOptions() {
			let options = {};

			Object.keys(this._options).forEach((option) => {
				if (this._options.hasOwnProperty(option)) {
					options[option] = this._options[option];
				}
			});

			return options;
		}

		/**
		 * Builds the admin dock and adds it to the DOM.
		 * 
		 * @return 	HTMLElement
		 */
		buildDockNode() {
			if (this._dock) {
				return;
			}

			// create element
			this._dock = document.createElement('div');
			this._dock.classList.add('vbo-admin-dock-wrapper');

			// listen to the event for updating the element badge counters
			document.addEventListener(this._updateBadgeEv, this.updateElementsBadgeCounter);

			// append element to body
			document.querySelector('body').appendChild(this._dock);

			return this._dock;
		}

		/**
		 * Returns the admin dock node.
		 * 
		 * @return 	HTMLElement
		 */
		getDockNode() {
			return this._dock || this.buildDockNode();
		}

		/**
		 * Removes a given element index from the dock and saves on local storage.
		 * This method should be called after having removed the element from DOM.
		 * 
		 * @param 	number 	index 	The element's array index to remove.
		 * 
		 * @return 	bool 			True if the localStorage was updated or false.
		 */
		removeDockElement(index) {
			// access the previous element
			let previousElement = this._elements[index];

			// splice the elements list
			this._elements.splice(index, 1);

			if (!this._elements.length) {
				// hide the dock
				this.hideDock();
			} else {
				// reset dock element indexes
				let dom_elements = this.getDockNode().querySelectorAll('.vbo-admin-dock-element');
				this._elements.forEach((element, new_index) => {
					let widget_id = element.id;
					if (!dom_elements[new_index] || dom_elements[new_index].getAttribute('data-id') != widget_id) {
						return;
					}
					dom_elements[new_index].setAttribute('data-index', new_index);
				});
			}

			if (previousElement && previousElement.id == '_tmp' && typeof previousElement?.details?.persist_id === 'string') {
				// we have removed a temporary dock element with persisting data so try to clear the localStorage
				VBOCore.storageRemoveItem(this._storageId + '.' + previousElement.details.persist_id);
			}

			// update dock elements on localStorage
			let updated = VBOCore.storageSetItem(this._storageId, this._elements);

			return updated;
		}

		/**
		 * Removes the first dock element found from the given ID.
		 * 
		 * @param 	number 	id 	The dock element type ID.
		 * 
		 * @return 	bool 	True if element was found, removed and updated on localStorage.
		 */
		removeDockElementById(id) {
			let remove_index = this.getDockElementById(id);

			if (remove_index >= 0) {
				return this.removeDockElement(remove_index);
			}

			return false;
		}

		/**
		 * Returns the index of the first dock element found from the given ID.
		 * 
		 * @param 	string 	id 	The dock element type ID.
		 * 
		 * @return 	number 		The index found or -1 if nothing is found.
		 */
		getDockElementById(id) {
			let index_found = -1;

			this._elements.forEach((element, index) => {
				if (index_found >= 0) {
					return;
				}

				if (element.id == id) {
					index_found = index;
					return;
				}
			});

			return index_found;
		}

		/**
		 * Builds a dock element node.
		 * 
		 * @param   object       details    The element details.
		 * @param   object       data 	    The element data.
		 * @param   number       index      The element index number.
		 * @param   HTMLElement  body       Optional widget modal body to restore.
		 * @param   function     destroyFn  Optional dismiss callback.
		 * 
		 * @return  HTMLElement
		 */
		buildDockElement(details, data, index, body, destroyFn) {
			if (!details?.id) {
				throw new Error('Unknown widget id');
			}

			const widgetId = details.id;

			const element = document.createElement('div');
			element.classList.add('vbo-admin-dock-element');
			element.setAttribute('data-id', widgetId);
			element.setAttribute('data-index', index);
			element.setAttribute('data-badge-count', '');

			let content = document.createElement('div');
			content.classList.add('vbo-admin-dock-element-cont');
			content.addEventListener('click', (e) => {
				// get content target
				let target = e.target;
				if (!target.matches('.vbo-admin-dock-element-cont')) {
					target = target.closest('.vbo-admin-dock-element-cont');
				}

				// reset badge count
				target.closest('.vbo-admin-dock-element').setAttribute('data-badge-count', '');

				// get widget data, if any
				let widgetData = data?.event_data?._options || data || {};

				// get modal body if NOT from localStorage
				let prevBody = target.querySelector('.vbo-admin-dock-element-modalbody');

				if (!Object.keys(widgetData).length && prevBody && prevBody.children && prevBody.children[0] && prevBody.children[0]?.children?.length) {
					// unset dock-minimized attribute on main element with class "vbo-modal-widget_modal-wrap"
					prevBody.children[0].setAttribute('data-dock-minimized', 0);
					// get the previous style attribute
					let prevBodyStyle = prevBody.children[0].getAttribute('style');

					// restore admin widget body previously minimized
					let prevTitle = data?.title || '';
					let nameSuffix = ' - ' + (details?.name || '');
					let modalRestore = {
						title: prevTitle + (prevTitle.indexOf(nameSuffix) < 0 ? nameSuffix : ''),
						// do not immediately set the restored modal body to prevBody.children[0] in order
						// to avoid getting duplicate nested elements with class "vbo-modal-widget_modal-wrap"
						// body: prevBody.children[0],
					};

					if (details?.modal?.add_class && data?.extra_class && (data.extra_class + '').indexOf((details.modal.add_class) + '') < 0) {
						// restore widget custom class
						modalRestore.extra_class = data.extra_class + ' ' + details.modal.add_class;
					}

					// display modal with previous content and details to bind
					let restoredBody = VBOCore.displayModal(
						Object.assign(data, modalRestore),
						Object.assign({}, details)
					);

					if (prevBodyStyle) {
						(restoredBody[0] || restoredBody).setAttribute('style', prevBodyStyle);
					}

					// iterate all children elements of main modal body element with class "vbo-modal-widget_modal-wrap"
					// to append and restore the original child nodes and to avoid duplicate container elements
					Array.from(prevBody.children[0].children).forEach((child) => {
						// append child node to the restored modal body
						(restoredBody[0] || restoredBody).append(child);
					});

					try {
						if (typeof data?.onRestore === 'function') {
							// call the restoring function from the original modal
							data.onRestore.call(restoredBody, e);
						}

						// always emit the native restoring event for the current widget
						VBOCore.emitEvent('vbo-admin-dock-restore-' + widgetId, {
							data: data?.event_data || {},
						});
					} catch(e) {
						// do nothing
					}
				} else {
					// re-render admin widget from scratch
					VBOCore.handleDisplayWidgetNotification({
						widget_id: widgetId,
					}, widgetData);
				}

				// remove element from DOM
				let elementIndex = element.getAttribute('data-index');
				element.remove();

				// remove element index from dock
				this.removeDockElement(elementIndex);
			});

			let content_icon = document.createElement('span');
			content_icon.classList.add('vbo-admin-dock-element-icn');
			if (details?.style) {
				content_icon.classList.add('vbo-admin-widget-style-' + details.style);
			}
			if (details?.icon) {
				content_icon.innerHTML = details.icon;
			}

			if (body) {
				// create hidden node
				let modal_body = document.createElement('div');
				modal_body.classList.add('vbo-admin-dock-element-modalbody');
				modal_body.style.display = 'none';

				try {
					// move modal body to hidden node
					modal_body.append((body[0] || body));

					// set dock-minimized attribute
					(body[0] || body).setAttribute('data-dock-minimized', 1);
				} catch(e) {
					// do nothing
				}

				// append hidden node to content
				content.appendChild(modal_body);
			}

			let content_name = document.createElement('span');
			content_name.classList.add('vbo-admin-dock-element-name');
			content_name.innerText = details?.name || '';

			let dismiss = document.createElement('span');
			dismiss.classList.add('vbo-admin-dock-element-dismiss');
			dismiss.innerHTML = '&times;';
			dismiss.addEventListener('click', (e) => {
				try {
					if (typeof destroyFn === 'function') {
						// destroy the original modal by firing any dismiss event
						destroyFn.call(body, e);
					}
				} catch(e) {
					// do nothing
				}

				// remove element from DOM
				let elementIndex = element.getAttribute('data-index');
				element.remove();

				// remove element index from dock
				this.removeDockElement(elementIndex);
			});

			// append to content
			content.appendChild(content_icon);
			content.appendChild(content_name);

			// append content to element
			element.appendChild(content);

			// append dismiss to element
			element.appendChild(dismiss);

			// return the HTMLElement object
			return element;
		}

		/**
		 * Builds a temporary dock element node.
		 * 
		 * @param   object      details 	The element details.
		 * @param   object      data 		The element data, usually an array of objects.
		 * @param   number      index 		The element index number.
		 * @param   function    restoreFn 	The callback for restoring the data.
		 * @param 	function 	destroyFn 	The callback for destroying the data.
		 * @param   number  	badge 		Optional badge number to set.
		 * 
		 * @return  HTMLElement
		 */
		buildDockTemporaryElement(details, data, index, restoreFn, destroyFn, badge) {
			if (!details?.id || details.id != '_tmp') {
				throw new Error('Invalid temporary element data.');
			}

			const dataId = '_tmp';

			const element = document.createElement('div');
			element.classList.add('vbo-admin-dock-element');
			element.setAttribute('data-id', dataId);
			element.setAttribute('data-index', index);
			element.setAttribute('data-badge-count', (badge || ''));

			let content = document.createElement('div');
			content.classList.add('vbo-admin-dock-element-cont');
			content.addEventListener('click', (e) => {
				// get content target
				let target = e.target;
				if (!target.matches('.vbo-admin-dock-element-cont')) {
					target = target.closest('.vbo-admin-dock-element-cont');
				}

				// reset badge count
				target.closest('.vbo-admin-dock-element').setAttribute('data-badge-count', '');

				if (typeof restoreFn === 'function') {
					this._elements.forEach((dockEl) => {
						if (dockEl.id == dataId) {
							// invoke the callback by passing the current element data
							restoreFn(dockEl?.data);

							// abort
							return;
						}
					});
				}

				// remove element from DOM
				let elementIndex = element.getAttribute('data-index');
				element.remove();

				// remove element index from dock
				this.removeDockElement(elementIndex);
			});

			let content_icon = document.createElement('span');
			content_icon.classList.add('vbo-admin-dock-element-icn');
			if (details?.style) {
				content_icon.classList.add('vbo-admin-widget-style-' + details.style);
			}
			if (details?.icon) {
				content_icon.innerHTML = details.icon;
			}

			let content_name = document.createElement('span');
			content_name.classList.add('vbo-admin-dock-element-name');
			content_name.innerText = details?.name || '';

			let dismiss = document.createElement('span');
			dismiss.classList.add('vbo-admin-dock-element-dismiss');
			dismiss.innerHTML = '&times;';
			dismiss.addEventListener('click', (e) => {
				if (typeof destroyFn === 'function') {
					this._elements.forEach((dockEl) => {
						if (dockEl.id == dataId) {
							// invoke the callback by passing the current element data
							destroyFn(dockEl?.data);

							// abort
							return;
						}
					});
				}

				// remove element from DOM
				let elementIndex = element.getAttribute('data-index');
				element.remove();

				// remove element index from dock
				this.removeDockElement(elementIndex);
			});

			// append to content
			content.appendChild(content_icon);
			content.appendChild(content_name);

			// append content to element
			element.appendChild(content);

			// append dismiss to element
			element.appendChild(dismiss);

			// return the HTMLElement object
			return element;
		}

		/**
		 * Adds a widget to the dock.
		 * 
		 * @param  object       details    The widget details.
		 * @param  object       data 	   The widget restoring data.
		 * @param  HTMLElement  body       Optional widget modal body to restore.
		 * @param  function     destroyFn  Optional dismiss callback.
		 */
		addWidget(details, data, body, destroyFn) {
			if (!VBOCore.options.widget_ajax_uri) {
				throw new Error('Wrong environment');
			}

			if (typeof details !== 'object') {
				details = {};
			}

			if (!details?.id) {
				throw new Error('Unknown widget id');
			}

			// add element to dock in the DOM at first
			this.getDockNode().appendChild(
				this.buildDockElement(details, data, this._elements.length, body, destroyFn)
			);

			// push dock element after it was added to the DOM
			this._elements.push({
				id: details.id,
				details: Object.assign({}, details),
				data: Object.assign({}, (data?.event_data?._options || {})),
			});

			// ensure dock is visible
			this.showDock();

			// update dock elements on localStorage at last
			VBOCore.storageSetItem(this._storageId, this._elements);
		}

		/**
		 * Adds temporary data to the dock. Data will NOT persist on the same localStorage key.
		 * Originally introduced to handle the rates update requests within a queue.
		 * 
		 * @param 	object 		details 	The data details.
		 * @param 	object 		data 		The data to store, usually an array of objects.
		 * @param 	function 	restoreFn 	The callback for restoring the data.
		 * @param 	function 	destroyFn 	The callback for destroying the data.
		 * @param 	number 		badge 		Optional badge number to set.
		 */
		addTemporaryData(details, data, restoreFn, destroyFn, badge) {
			if (!VBOCore.options.widget_ajax_uri) {
				throw new Error('Wrong environment');
			}

			if (typeof details !== 'object') {
				details = {};
			}

			// temporary data will always get a private ID to avoid duplicate entries in the dock
			details.id = '_tmp';

			// check if the same temporary data element type exists
			let previousElement = null;
			let previousIndex = null;
			this.getElements().forEach((element, index) => {
				if (previousElement) {
					return;
				}
				if (element.id == details.id) {
					// previous temporary data element type found
					previousElement = element;
					previousIndex = index;
					return;
				}
			});

			if (!previousElement) {
				// add temporary element to dock in the DOM at first
				this.getDockNode().appendChild(
					this.buildDockTemporaryElement(details, data, this._elements.length, restoreFn, destroyFn, (badge ? badge : (Array.isArray(data) ? data.length : null)))
				);

				// push dock element after it was added to the DOM
				this._elements.push({
					id: details.id,
					details: Object.assign({}, details),
					data: Array.isArray(data) ? [...data] : Object.assign({}, data),
				});
			} else {
				// update previous temporary data element type
				let updatedData = Array.isArray(data) && Array.isArray(previousElement.data) ? [...previousElement.data].concat([...data]) : [...data];
				let updatedBadge = badge ? badge : (Array.isArray(updatedData) ? updatedData.length : previousElement.badge);

				// update existing dock element
				this._elements[previousIndex] = {
					id: details.id,
					details: Object.assign({}, previousElement.details, details),
					data: updatedData,
				};

				if (updatedBadge) {
					// update DOM element
					let dom_elements = this.getDockNode().querySelectorAll('.vbo-admin-dock-element');
					this._elements.forEach((element, cur_index) => {
						if (!dom_elements[cur_index] || dom_elements[cur_index].getAttribute('data-id') != element.id) {
							return;
						}
						if (dom_elements[cur_index].getAttribute('data-id') != details.id) {
							return;
						}
						// update badge attribute
						dom_elements[cur_index].setAttribute('data-badge-count', updatedBadge);
					});
				}
			}

			// ensure dock is visible
			this.showDock();

			if (details.persist_id && typeof details.persist_id === 'string') {
				// update dock element on a different localStorage key at last
				VBOCore.storageSetItem(this._storageId + '.' + details.persist_id, (previousIndex ? this._elements[previousIndex] : this._elements[this._elements.length - 1]));
			}
		}

		/**
		 * Loads temporary data onto the dock by using an ID and type identifier.
		 * Originally introduced to handle the rates update requests within a queue.
		 * 
		 * @param 	object 		details 	The data details to load.
		 * @param 	function 	restoreFn 	The callback for restoring the data.
		 * @param 	function 	destroyFn 	The callback for destroying the data.
		 * @param 	number 		badge 		Optional badge number to set.
		 * 
		 * @return 	any 					Data object loaded on success or null.
		 */
		loadTemporaryData(details, restoreFn, destroyFn, badge) {
			if (!VBOCore.options.widget_ajax_uri) {
				throw new Error('Wrong environment');
			}

			if (typeof details !== 'object' || !details.id || !details.persist_id || typeof details.persist_id !== 'string') {
				return null;
			}

			// temporary data will always get a private ID to avoid duplicate entries in the dock
			details.id = '_tmp';

			// check if the same temporary data element type exists
			let previousElement = null;
			this.getElements().forEach((element, index) => {
				if (previousElement) {
					return;
				}
				if (element.id == details.id) {
					// previous temporary data element type found
					previousElement = element;
					return;
				}
			});

			if (previousElement) {
				// temporary data already loaded in dock
				return previousElement;
			}

			// load the requested temporary and persisting data
			let storageElement = VBOCore.storageGetItem(this._storageId + '.' + details.persist_id);

			try {
				if (typeof storageElement === 'string') {
					storageElement = JSON.parse(storageElement);
				}
			} catch(e) {
				storageElement = null;
			}

			if (!storageElement || typeof storageElement !== 'object' || !storageElement.data) {
				return null;
			}

			// add the temporary data just loaded to the dock
			this.addTemporaryData(Object.assign({}, (storageElement.details || {}), details), storageElement.data, restoreFn, destroyFn, badge);

			// return the data loaded
			return storageElement;
		}

		/**
		 * Returns the current dock elements.
		 * 
		 * @return   Array
		 */
		getElements() {
			return this._elements;
		}

		/**
		 * Loads widget dock elements from localStorage and populates them, if any.
		 */
		loadDockElements() {
			let storageElements = VBOCore.storageGetItem(this._storageId) || [];

			try {
				if (typeof storageElements === 'string') {
					storageElements = JSON.parse(storageElements);
				}
			} catch(e) {
				storageElements = [];
			}

			if (Array.isArray(storageElements)) {
				// set current elements
				this._elements = storageElements;
			}

			// scan all elements to ensure no temporary data was stored upon deleting or adding new widgets
			this._elements.forEach((element, index) => {
				if (element?.id == '_tmp') {
					// splice the elements list
					this._elements.splice(index, 1);

					// update dock elements on localStorage by only keeping real widgets
					VBOCore.storageSetItem(this._storageId, this._elements);

					// abort
					return;
				}
			});

			if (this._elements.length) {
				// populate dock elements
				this.populateDockElements();

				// show the dock
				this.showDock();
			} else {
				// hide the dock
				this.hideDock();
			}
		}

		/**
		 * Populates the current dock elements.
		 */
		populateDockElements() {
			const dockNode = this.getDockNode();

			// empty the dock
			dockNode.innerHTML = '';

			// scan all elements
			this._elements.forEach((element, index) => {
				// add element to dock in the DOM at first
				dockNode.appendChild(
					this.buildDockElement(element?.details, element?.data, index)
				);
			});
		}

		/**
		 * Hides the dock node.
		 */
		hideDock() {
			this.getDockNode().style.display = 'none';
		}

		/**
		 * Shows the dock node.
		 */
		showDock() {
			if (!this.getDockNode().checkVisibility()) {
				this.getDockNode().style.display = this._options.dockDisplayStyle;
			}
		}

		/**
		 * Event callback fired to update the element(s) badge counter.
		 */
		updateElementsBadgeCounter(e) {
			if (!e || !e.detail || !e.detail?.widgetId) {
				return;
			}

			let elements = VBOAdminDock.getInstance().getElements();

			if (!elements.length) {
				return;
			}

			let widgetId = e.detail.widgetId;
			let badgeCount = parseInt(e.detail?.badgeCount || 0);
			let badgeValue = badgeCount > 0 ? badgeCount : '';

			elements.forEach((element) => {
				if (element.id == widgetId) {
					document.querySelectorAll('.vbo-admin-dock-element[data-id="' + widgetId + '"]').forEach((elNode) => {
						elNode.setAttribute('data-badge-count', badgeValue);
					});
				}
			});
		}
	}

})(jQuery, window);