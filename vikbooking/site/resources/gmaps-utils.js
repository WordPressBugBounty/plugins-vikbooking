/**
 * VikBooking - Google Maps Utils v1.8.3
 * Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * https://vikwp.com | https://e4j.com | https://e4jconnect.com
 */
class VBOGMapsUtils {
	/**
	 * Can be used as callback to inform the subscribers that Google Maps is ready.
	 * 
	 * @return  void
	 */
	static ready() {
		if (VBOGMapsUtils.isReady) {
			// already loaded
			return;
		}

		VBOGMapsUtils.isReady = true;

		// trigger event to inform the subscribers that Google Maps has been fully loaded
		document.dispatchEvent(new Event('vbo-googlemaps-ready'));
	}
}

/**
 * Proxy (shortcut) for VBOGMapsUtils.ready(), common Google Maps callback.
 */
function vbo_gm_ready() {
	VBOGMapsUtils.ready();
}