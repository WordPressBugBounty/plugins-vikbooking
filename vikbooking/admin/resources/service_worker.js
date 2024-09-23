/**
 * VikBooking Service Worker v1.0.0
 * Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * https://vikwp.com | https://e4j.com | https://e4jconnect.com
 */

class VBOWorker {

	/**
	 * @var  self  the singleton instance of the VBOWorker class.
	 */
	static instance;

	/**
	 * @var  ServiceWorkerGlobalScope
	 */
	worker;

	/**
	 * Class constructor sets the ServiceWorker global scope object.
	 * 
	 * @param 	ServiceWorkerGlobalScope 	worker 	the SW object to register.
	 */
	constructor(worker) {
		this.worker = worker;
	}

	/**
	 * Registers the global ServiceWorker object inside a new VBOWorker instance.
	 * 
	 * @param 	ServiceWorkerGlobalScope 	worker 	the SW object to bind.
	 * 
	 * @return 	undefined
	 */
	static register(worker) {
		if (!(worker instanceof ServiceWorkerGlobalScope)) {
			throw new Error('Invalid ServiceWorkerGlobalScope object');
		}

		VBOWorker.instance = new VBOWorker(worker);

		return;
	}

	/**
	 * Returns an instance of the VBOWorker object.
	 * 
	 * @param 	ServiceWorkerGlobalScope 	worker 	the SW object to bind.
	 * 
	 * @return 	VBOWorker
	 */
	static getInstance(worker) {
		if (!VBOWorker.instance) {
			// register the ServiceWorkerGlobalScope object in a new VBOWorker instance
			VBOWorker.register(worker);
		}

		return VBOWorker.instance;
	}

	/**
	 * Gets the ServiceWorker origin (base) domain.
	 * 
	 * @return 	string
	 */
	getOrigin() {
		return this.worker.location.origin || '';
	}

	/**
	 * Gets the ServiceWorker installation reference (full) URI to this file.
	 * 
	 * @return 	string
	 */
	getHref() {
		return this.worker.location.href || '';
	}

	/**
	 * Gets the ServiceWorker relative path to this file.
	 * 
	 * @return 	string
	 */
	getPath() {
		return this.worker.location.pathname || '';
	}

	/**
	 * Tells if the client App is running on the Joomla CMS.
	 * 
	 * @return 	bool
	 */
	isJoomla() {
		let match_admin_cms_path = new RegExp(/\/(administrator\/components)\//);

		return this.getHref().match(match_admin_cms_path) ? true : false;
	}

	/**
	 * Tells if the client App is running on the WordPress CMS.
	 * 
	 * @return 	bool
	 */
	isWordPress() {
		let match_admin_cms_path = new RegExp(/\/(wp-content\/plugins)\//);

		return this.getHref().match(match_admin_cms_path) ? true : false;
	}

	/**
	 * Builds the URI to the CMS back-end section to VikBooking.
	 * 
	 * @return 	string
	 */
	getVikBookingAdminURI() {
		let vbo_base_admin_uri = '/';

		if (this.isWordPress()) {
			vbo_base_admin_uri = this.getHref().split('wp-content/plugins/')[0] + 'wp-admin/admin.php?page=vikbooking';
		} else if (this.isJoomla()) {
			vbo_base_admin_uri = this.getHref().split('administrator/components/')[0] + 'administrator/index.php?option=com_vikbooking';
		}

		return vbo_base_admin_uri;
	}

	/**
	 * Builds the URI to allow VikBooking to render the data from a Push notification.
	 * 
	 * @param 	object 	payload 	the payload data to encode and add it to query string.
	 * 
	 * @return 	string 
	 */
	buildVikBookingAdminPushURI(payload) {
		// base64 encode the payload and append it to a query string value
		return this.getVikBookingAdminURI() + '&push_notification=' + btoa(JSON.stringify(payload || {}));
	}
}

// register the ServiceWorkerGlobalScope object
VBOWorker.register(self);

// install event listener
self.addEventListener('install', (event) => {
	/**
	 * Force the waiting ServiceWorker to become the active one
	 * by triggering the "activate" event.
	 */
	event.waitUntil(self.skipWaiting());
});

// activate event listener
self.addEventListener('activate', (event) => {
	/**
	 * Set this ServiceWorker as the active one for all clients that match the worker's
	 * scope and trigger the "controllerchange" event for the claimed clients (unused).
	 */
	event.waitUntil(self.clients.claim());
});

// push event listener
self.addEventListener('push', (event) => {
	if (!self.Notification || self.Notification.permission != 'granted') {
		// unable to proceed
		return;
	}

	// define how to dispatch the Push notification
	const dispatchNotification = (data) => {
		// get the notification data payload
		let payload = data.json();

		// check if support for Web App badge is available
		if (typeof navigator.setAppBadge !== 'undefined') {
			// set application badge count
			navigator.setAppBadge((payload.unreadCount || 1));
		}

		// show the Push notification
		return self.registration.showNotification((payload.title || 'Notification'), {
			body: 	payload.message || data.text(),
			data: 	payload,
			silent: false,
		});
	};

	if (event.data) {
		// asynchronously show the notification by passing the PushMessageData object
		event.waitUntil(dispatchNotification(event.data));
	}
});

// notification click event listener
self.addEventListener('notificationclick', (event) => {
	// clicked notification payload
	let payload = event.notification.data || {};

	// check if support for Web App badge is available
	if (typeof navigator.setAppBadge !== 'undefined') {
		// clear application badge count
		navigator.clearAppBadge();
	}

	// match client options
	let matchOptions = {
		includeUncontrolled: true,
		type: 'window',
	};

	// registration origin (website root URI)
	let sw_origin = self.location.origin || '';

	// valid client match regular expression
	let match_admin_regexp = new RegExp(/\/(wp-admin|administrator)\//);

	// close the notification
	event.notification.close();

	// check for open tabs/windows
	event.waitUntil(self.clients.matchAll(matchOptions).then((clientList) => {
		// parse claimed clients found, if any
		for (const client of clientList) {
			if (client.url.indexOf(sw_origin) !== 0) {
				// the client URL does not match the ServiceWorker origin
				continue;
			}

			if (client.url.match(match_admin_regexp)) {
				// client URL matched the admin section of the platform

				if (!client.focused) {
					// focus available client
					clientList[0].focus();
				}

				/**
				 * Post message to the application with the clicked notification payload
				 * to let the application handle the notification click action.
				 */
				clientList[0].postMessage(payload);

				// process completed
				return;
			}
		}

		// fallback to opening a new window

		if (payload.type && (payload.title || payload.message)) {
			/**
			 * Attempt to render the VikBooking admin URL by injecting
			 * the Push notification payload values via query string.
			 */
			self.clients.openWindow(VBOWorker.getInstance().buildVikBookingAdminPushURI(payload));
		} else if (payload.url) {
			// open the requested URL
			self.clients.openWindow(payload.url);
		} else {
			// open a blank new window by letting the browser detect the URL
			self.clients.openWindow('/');
		}
	}));
});
