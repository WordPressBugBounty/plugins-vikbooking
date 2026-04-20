<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

?>

<div class="vbo-chat-session-view-container">

	<?php
	// display session chat through layout
	echo JLayoutHelper::render(
		'chat.session',
		[
			'session' => $this->session,
			'suffix' => 'view',
		],
		null,
		[
			'component' => 'com_vikbooking',
			'client' => 'site',
		]
	);
	?>

</div>