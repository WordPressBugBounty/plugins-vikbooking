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

?>

<style>
	/* make background color of checkbox darker */
	#rss_optin_status1:not(:checked) + label:before {
		background-color: #ccc; 
	}
</style>

<!-- RSS intro -->

<p>
	<?php
	_e(
		'This plugin supports the possibility of subscribing to RSS channels, and we are wondering if you are interested into using this service.', 
		'vikbooking'
	);
	?>
</p>

<!-- explain RSS usage -->

<p>
	<b>
		<?php
		_e(
			'Why should I opt-in to this service?',
			'vikbooking'
		);
		?>
	</b>
</p>

<p>
	<?php
	_e(
		'The RSS service mainly covers these macro sections: <b>news</b>, <b>tips</b> and <b>offers</b>. In the wp-admin section of your website you may see important news about this plugin or anything else that has to do with the WordPress world. Sometimes you could receive notifications about tips or features that you didn\'t even think they could exist. Any notification will be displayed within a modal window just like this message. We can guarantee that this service is not an annoying advertising system, it is just a notification center.', 
		'vikbooking'
	);
	?>
</p>

<!-- privacy policy -->

<p>
	<b>
		<?php
		_e(
			'What kind of personal data do we collect?',
			'vikbooking'
		);
		?>
	</b>
</p>

<p>
	<?php
	_e(
		'Our company does NOT collect any personal information. The syndication URLs never include sensitive data that may be linked back to you.',
		'vikbooking'
	);
	?>
</p>

<!-- opt-in checkbox -->

<p>
	<?php
	_e(
		'We need you to explicitly opt-in to the RSS service for GDPR compliance. You are free to change your settings at any time from the Configuration page of this plugin.',
		'vikbooking'
	);
	?>
</p>

<p style="display: inline-block; width: 100%; margin: 0 auto;">
	<?php
	echo VikBooking::getVboApplication()->printYesNoButtons('rss_optin_status', __('Yes', 'vikbooking'), __('No', 'vikbooking'), 1, 1, 0);
	?>
	<span style="margin-left: 6px;">
		<?php
		_e(
			'I want to opt-in to the RSS service',
			'vikbooking'
		);
		?>
	</span>
</p>

<!-- finalisation -->

<p>
	<?php
	_e(
		'Hit the <b>Save</b> button to confirm your choice and close this modal window.',
		'vikbooking'
	);
	?>
</p>
