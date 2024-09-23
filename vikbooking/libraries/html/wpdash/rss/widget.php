<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	html.wpdash
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2020 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  JRegistry         $config  The configuration registry
 * @var  JDashboardWidget  $widget  The widget instance.
 * @var  array             $feeds   A list of feeds.
 */

$document = JFactory::getDocument();

$internalFilesOptions = array('version' => VIKBOOKING_SOFTWARE_VERSION);

// system.js must be loaded on both front-end and back-end for tmpl=component support
$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/system.js', $internalFilesOptions, array('id' => 'vbo-sys-script'));
$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/admin.js', $internalFilesOptions, array('id' => 'vbo-admin-script'));
$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/bootstrap.min.js', $internalFilesOptions, array('id' => 'bootstrap-script'));
$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/system.css', $internalFilesOptions, array('id' => 'vbo-sys-style'));
$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/bootstrap.lite.css', $internalFilesOptions, array('id' => 'bootstrap-lite-style'));

// prepare modal to display opt-in
echo JHtml::fetch(
	'bootstrap.renderModal',
	'jmodal-vbo-rss-feed',
	array(
		'title'       => '',
		'closeButton' => true,
		'keyboard'    => true,
		'top'         => true,
		'width'       => 70,
		'height'      => 80,
	),
	'{placeholder}'
);

?>

<style>
	#vik_booking_rss .inside {
		padding: 0 !important;
		margin: 0 !important;
	}
	#vik_booking_rss .modal-header h3 {
		margin: 0;
		line-height: 50px;
		font-weight: normal;
		font-size: 22px;
	}
	#vik_booking_rss .modal-header h3 .dashicons-before:before {
		line-height: 50px;
	}
	#vik_booking_rss img {
		max-width: 100%;
	}

	.vbo-rss-widget ul {
		margin: 0;
		padding: 0;
	}
	.vbo-rss-widget ul li {
		list-style: none;
		display: flex;
		align-items: center;
		justify-content: space-between;
		flex-wrap: wrap;
		margin: 0;
		padding: 8px 12px;
		border-bottom: 1px solid #eee;
	}
	.vbo-rss-widget ul li:last-child {
		border-bottom: 0;
	}
	.vbo-rss-widget ul li:nth-child(odd) {
		background: #fafafa;
	}

	.vbo-rss-widget ul li .feed-icon {
		width: 32px;
	}
	.vbo-rss-widget ul li .feed-details {
		flex: 1;
	}
	.vbo-rss-widget ul li .feed-date-time {
		text-align: right;
	}

	.vbo-rss-widget .rss-missing-optin {
		padding: 10px 10px 0 10px;
	}
</style>

<div class="vbo-rss-widget">

	<?php
	// make sure the RSS service is enabled
	if (!$config->get('optin'))
	{
		// service not enabled
		?>
		<div class="rss-missing-optin">
			<div class="notice notice-error inline">
				<p>
					<?php _e('<b>You haven\'t opted-in to the RSS service!</b><br />Click the following button to start reading RSS feeds.', 'vikbooking'); ?>
				</p>

				<p>
					<a href="admin.php?page=vikbooking&task=config#rss" class="button button-primary">
						<?php _e('Activate RSS Feeds', 'vikbooking'); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
	else
	{
		?>
		<ul>
			<?php
			foreach ($feeds as $i => $feed)
			{
				switch (strtolower($feed->category))
				{
					case 'promo':
						$icon = 'star-filled';
						break;

					case 'tips':
						$icon = 'welcome-learn-more';
						break;

					case 'news':
						$icon = 'megaphone';
						break;

					default:
						$icon = 'rss';
				}

				?>
				<li data-id="<?php echo $feed->id; ?>">
					<div class="feed-icon">
						<span class="dashicons-before dashicons-<?php echo $icon; ?>"></span>
					</div>

					<div class="feed-details" data-title="<?php echo $this->escape($feed->title); ?>" data-category="<?php echo $this->escape($feed->category); ?>">
						<div class="feed-title">
							<a href="javascript: void(0);">
								<b><?php echo $feed->title; ?></b>
							</a>
						</div>
						<div class="feed-category"><?php echo $feed->category; ?></div>
					</div>

					<div class="feed-date-time">
						<div class="feed-date">
							<?php echo JHtml::fetch('date', $feed->date, get_option('date_format')); ?>
						</div>
						<div class="feed-time">
							<?php echo JHtml::fetch('date', $feed->date, get_option('time_format')); ?>
						</div>
					</div>

					<div style="display: none;" class="rss-content">
						<?php echo $feed->content; ?>
					</div>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
	?>

</div>

<script>

	jQuery(document).ready(function() {

		jQuery('#vik_booking_rss .feed-details a').on('click', function() {
			// get parent <li>
			var li = jQuery(this).closest('li');
			// find feed details
			var details = li.find('.feed-details');
			// find feed content
			var content = li.find('.rss-content').html();

			// get modal
			var modal = jQuery('#jmodal-vbo-rss-feed');

			// register feed ID
			modal.attr('data-feed-id', li.data('id'));

			// update modal title
			modal.find('.modal-header h3').html(
				li.find('.feed-icon').html() + ' ' +
				details.data('category') + ' - ' +
				details.data('title')
			);

			// update modal content
			modal.find('.modal-body').html(content);

			// display modal
			wpOpenJModal('vbo-rss-feed');
		});

	});

</script>
