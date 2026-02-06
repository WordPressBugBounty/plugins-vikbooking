<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	html.update
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// define alert message
$__alert = __(
	"Your PRO license has expired. If you proceed with the update, you'll lose access to the features unlocked by the PRO version. We strongly recommend updating the plugin only after renewing your license.\nWould you like to proceed anyway?",
	'vikbooking'
);

?>

<script>
	(function($) {
		'use strict';

		$(function() {
			// retrieve VikBooking update link
			const link = $('#vikbooking-update a.update-link');

			// define callback to invoke when the update button gets clicked
			const implementor = (event) => {
				// prevent default event
				event.preventDefault();
				event.stopPropagation();

				// prompt alert message
				const r = confirm(<?php echo json_encode($__alert); ?>);

				if (r) {
					// turn off click event
					link.off('click', implementor);

					setTimeout(() => {
						// trigger click to auto-dispatch the update
						link.trigger('click');
					}, 32);
				}

				return false;
			};

			// override default click event
			link.on('click', implementor);
		});
	})(jQuery);
</script>