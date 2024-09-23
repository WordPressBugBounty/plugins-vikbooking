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

$config   = !empty($displayData['config'])   ? $displayData['config']   : null;
$channels = !empty($displayData['channels']) ? $displayData['channels'] : array();

?>

<a name="rss"></a>

<div class="vbo-params-container">
		
	<!-- OPT IN - Checkbox -->
	<div class="vbo-param-container">
		<div class="vbo-param-label"><?php echo __('Enable RSS Service', 'vikbooking'); ?></div>
		<div class="vbo-param-setting">
			<?php
			$cur_opt_in_status = (isset($config) && isset($config['optin']) ? (int)$config['optin'] : 0);
			echo VikBooking::getVboApplication()->printYesNoButtons('rss_optin_status', __('Yes', 'vikbooking'), __('No', 'vikbooking'), $cur_opt_in_status, 1, 0, 'rssOptinValueChanged(this.checked);');
			?>
		</div>
	</div>

	<!-- DISPLAY DASHBOARD - Select -->
	<div class="vbo-param-container rss-child-setting" style="<?php echo $config['optin'] ? '' : 'display:none;'; ?>">
		<div class="vbo-param-label"><?php echo __('Display on Dashboard', 'vikbooking'); ?></div>
		<div class="vbo-param-setting">
			<?php
			echo VikBooking::getVboApplication()->printYesNoButtons('rss_display_dashboard', __('Yes', 'vikbooking'), __('No', 'vikbooking'), (isset($config) && isset($config['dashboard']) ? (int)$config['dashboard'] : 0), 1, 0);
			?>
		</div>
	</div>

<?php
// allow channels management for PRO licenses
if (VikBookingLicense::isPro())
{
	// iterate supported channels
	foreach ($channels as $label => $url)
	{
		$checked = in_array($url, (array) $config['channels']);

		?>
	<div class="vbo-param-container rss-child-setting" style="<?php echo $config['optin'] ? '' : 'display:none;'; ?>">
		<div class="vbo-param-label"><?php echo ucwords($label); ?></div>
		<div class="vbo-param-setting">
			<?php
			echo VikBooking::getVboApplication()->printYesNoButtons('rss_channel_' . md5($url), __('Yes', 'vikbooking'), __('No', 'vikbooking'), (int)$checked, 1, 0, 'rssChannelValueChanged(this.checked, \'' . $url . '\');');

			if ($checked)
			{
				?>
				<input type="hidden" name="rss_channel_url[]" value="<?php echo $url; ?>" />
				<?php
			}
			?>
		</div>
	</div>
		<?php
		
	}
}
?>

</div>

<script>

	// toggle RSS settings according to the opt-in choice
	function rssOptinValueChanged(is) {
		if (is) {
			jQuery('.rss-child-setting').show();
		} else {
			jQuery('.rss-child-setting').hide();
		}
	}

	// toggle RSS channel according to the checkbox status
	function rssChannelValueChanged(is, url) {
		// get existing input URL
		var urlInput = jQuery('input[name="rss_channel_url[]"][value="' + url + '"]');

		if (is && urlInput.length == 0) {
			jQuery('#adminForm').append('<input type="hidden" name="rss_channel_url[]" value="' + url + '" />');
		} else {
			urlInput.remove();
		}
	}

</script>
