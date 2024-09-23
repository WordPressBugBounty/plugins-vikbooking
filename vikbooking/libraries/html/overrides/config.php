<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  html.overrides
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

?>

<div class="config-fieldset">

	<div class="config-fieldset-body">

		<p>
			<?php
			_e(
				'From this section it is possible to override the pages and the layouts of the plugin. Click the button below to open the file manager and start editing the pages.',
				'vikbooking'
			);
			?>
		</p>

		<div class="notice notice-warning inline">
			<p><?php _e('Go ahead only if you are able to deal with PHP and HTML code.', 'vikbooking'); ?></p>
		</div>

		<div>
			<a
				href="admin.php?page=vikbooking&view=overrides"
				class="button button-hero"
				target="_blank"
			><?php _e('Open Overrides Manager', 'vikbooking'); ?></a>
		</div>

	</div>

</div>