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

/**
 * Class handler for admin widget "latest from guests".
 * 
 * @since 	1.14.0 (J) - 1.4.0 (WP)
 */
class VikBookingAdminWidgetLatestFromGuests extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Number of activities per page. Should be an even number.
	 * 
	 * @var 	int
	 */
	protected $activities_per_page = 6;

	/**
	 * Number of activities per type. Should be half of numer per page.
	 * 
	 * @var 	int
	 */
	protected $activities_per_type = 3;

	/**
	 * Today Y-m-d string
	 * 
	 * @var 	string
	 */
	protected $today_ymd = null;

	/**
	 * The path to the VCM lib to see if it's available.
	 * 
	 * @var 	string
	 */
	protected $vcm_lib_path = '';

	/**
	 * Tells whether VCM is installed and updated.
	 * 
	 * @var 	bool
	 */
	protected $vcm_exists = true;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_LATESTFROMGUESTS_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_LATESTFROMGUESTS_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('comments') . '"></i>';
		$this->widgetStyleName = 'green';

		// activities per type is half the total number of activities
		$this->activities_per_type = ceil($this->activities_per_page / 2);

		// today Y-m-d date
		$this->today_ymd = date('Y-m-d');

		// the path to the VCM library
		$this->vcm_lib_path = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php';

		// whether VCM is available
		$this->vcm_exists = true;
		if (is_file($this->vcm_lib_path)) {
			// require the dependencies
			if (!class_exists('VikChannelManager')) {
				require_once $this->vcm_lib_path;
			}
			if (!method_exists('VikChannelManager', 'getLatestFromGuests')) {
				// VCM is outdated
				$this->vcm_exists = false;
			}
		} else {
			$this->vcm_exists = false;
		}

		// avoid queries on certain pages, as VCM may not have been activated yet
		if (VBOPlatformDetection::isWordPress() && $this->vcm_exists) {
			global $pagenow;
			$skip_pages = ['update.php', 'plugins.php', 'plugin-install.php'];
			if (isset($pagenow) && in_array($pagenow, $skip_pages)) {
				$this->vcm_exists = false;
			}
		}
	}

	/**
	 * This widget returns the latest activity information to schedule
	 * periodic watch data in order to be able to trigger notifications.
	 * No CSS/JS assets are needed during preloading.
	 * 
	 * @return 	void|object
	 */
	public function preload()
	{
		if (!$this->vcm_exists) {
			return null;
		}

		// use VCM to load the latest guest activity ids
		$latest_activities = [];
		try {
			$latest_activities = VikChannelManager::getLatestFromGuests(['messages', 'reviews'], 0, 1);
		} catch (Exception $e) {
			// do nothing
		}

		if (!is_array($latest_activities) || !$latest_activities) {
			return null;
		}

		// default watch-data values to monitor
		$watch_data = new stdClass;
		$watch_data->guest_review  = 0;
		$watch_data->guest_message = '';
		$watch_data->message_id    = 0;
		$watch_data->message_bid   = 0;

		foreach ($latest_activities as $activity) {
			if (isset($activity->id_review) && empty($watch_data->guest_review)) {
				// this is a guest review
				$watch_data->guest_review = $activity->id_review;
			} elseif (!isset($activity->id_review) && empty($watch_data->message_id)) {
				// this is a guest message
				$watch_data->guest_message = $activity->content;
				$watch_data->message_id    = $activity->id_message;
				$watch_data->message_bid   = $activity->idorder;
			}
		}

		return $watch_data;
	}

	/**
	 * Checks for new notifications by using the previous preloaded watch-data.
	 * 
	 * @param 	VBONotificationWatchdata 	$watch_data 	the preloaded watch-data object.
	 * 
	 * @return 	array 						data object to watch next and notifications array.
	 * 
	 * @see 	preload()
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public function getNotifications(VBONotificationWatchdata $watch_data = null)
	{
		// default empty values
		$watch_next    = null;
		$notifications = null;

		if (!$this->vcm_exists || !$watch_data) {
			return [$watch_next, $notifications];
		}

		// grab latest watch data values
		$latest_guest_review  = (int)$watch_data->get('guest_review', 0);
		$latest_guest_message = $watch_data->get('guest_message', '');
		$latest_message_id    = (int)$watch_data->get('message_id', 0);
		$latest_message_bid   = (int)$watch_data->get('message_bid', 0);

		// use VCM to load the latest guest activity ids
		try {
			$latest_activities = VikChannelManager::getLatestFromGuests(['messages', 'reviews'], 0, 1);
		} catch (Exception $e) {
			// do nothing
			$latest_activities = [];
		}

		if (!$latest_activities) {
			return [$watch_next, $notifications];
		}

		// default watch-data next values to monitor
		$watch_next = new stdClass;
		$watch_next->guest_review  = $latest_guest_review;
		$watch_next->guest_message = $latest_guest_message;
		$watch_next->message_id    = $latest_message_id;
		$watch_next->message_bid   = $latest_message_bid;

		// new notifications pool (if any)
		$notifications = [];

		foreach ($latest_activities as $activity) {
			if (isset($activity->id_review)) {
				// this is a guest review
				if ($activity->id_review > $latest_guest_review) {
					// set the next watch data value
					$watch_next->guest_review = $activity->id_review;
					// compose the notification(s) to dispatch for the guest review(s)
					$review_notifications = VBONotificationScheduler::getInstance()->buildReviewDataObjects([$activity]);
					if ($review_notifications) {
						$notifications = array_merge($notifications, $review_notifications);
					}
				}
			} else {
				// this is a guest message
				if (isset($activity->sender_type) && (!strcasecmp($activity->sender_type, 'host') || !strcasecmp($activity->sender_type, 'hotel'))) {
					// we don't want threads started by the host/hotel
					continue;
				}

				// make sure this is a more recent activity than the last one
				if ($activity->id_message > $latest_message_id && ($activity->idorder != $latest_message_bid || $activity->content != $latest_guest_message)) {
					// overwrite properties to watch at the next scheduled interval
					$watch_next->guest_message = $activity->content;
					$watch_next->message_id    = $activity->id_message;
					$watch_next->message_bid   = $activity->idorder;

					// make sure the same message was not previously dispatched through a Push notification
					if (!$watch_data->matchPushedGuestMessage($activity)) {
						// compose the notification(s) to dispatch for the guest message(s)
						$guestmess_notifications = VBONotificationScheduler::getInstance()->buildGuestMessageDataObjects([$activity]);
						if ($guestmess_notifications) {
							$notifications = array_merge($notifications, $guestmess_notifications);
						}
					}
				}
			}
		}

		return [$watch_next, $notifications];
	}

	/**
	 * Custom method for this widget only to load the latest guest activities.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * 
	 * It's the actual rendering of the widget which also allows navigation.
	 */
	public function loadActivities()
	{
		$offset = VikRequest::getInt('offset', 0, 'request');
		$length = VikRequest::getInt('length', $this->activities_per_type, 'request');
		$wrapper = VikRequest::getString('wrapper', '', 'request');

		if (!$this->vcm_exists) {
			echo 'e4j.error.missing or outdated VCM';
			return;
		}

		// load latest activities
		$latest_activities = [];
		try {
			$latest_activities = VikChannelManager::getLatestFromGuests(['messages', 'reviews'], $offset, $length);
		} catch (Exception $e) {
			// do nothing
		}

		foreach ($latest_activities as $activity) {
			$activity_type = isset($activity->id_review) ? 'review' : 'message';
			$activity_content = $activity->content;
			if (empty($activity_content)) {
				$activity_content = '.....';
			} elseif (strlen($activity_content) > 90) {
				if (function_exists('mb_substr')) {
					$activity_content = mb_substr($activity_content, 0, 90, 'UTF-8');
				} else {
					$activity_content = substr($activity_content, 0, 90);
				}
				$activity_content .= '...';
			}
			// determine sender type in case of guest message
			$sender = 'Hotel';
			if ($activity_type == 'message' && isset($activity->sender_type)) {
				if (strcasecmp($activity->sender_type, 'Hotel') && strcasecmp($activity->sender_type, 'Host')) {
					$sender = 'Guest';
				}
			}

			?>
			<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-<?php echo $activity_type; ?>" onclick="vboWidgetLatestFromGuestsOpenBooking('<?php echo $activity->idorder; ?>', '<?php echo $activity_type == 'message' ? '#messaging' : ''; ?>');">
				<div class="vbo-dashboard-guest-activity-avatar">
				<?php
				if (!empty($activity->guest_avatar)) {
					// highest priority goes to the profile picture, not always available
					?>
					<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo $activity->guest_avatar; ?>" />
					<?php
				} elseif (!empty($activity->pic)) {
					// customer profile picture is not the same as the photo avatar
					?>
					<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo strpos($activity->pic, 'http') === 0 ? $activity->pic : VBO_SITE_URI . 'resources/uploads/' . $activity->pic; ?>" />
					<?php
				} elseif (!empty($activity->channel_logo)) {
					// channel logo goes as second option
					?>
					<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo $activity->channel_logo; ?>" />
					<?php
				} else {
					// we use an icon as fallback
					VikBookingIcons::e('user', 'vbo-dashboard-guest-activity-avatar-icon');
				}
				?>
				</div>
				<div class="vbo-dashboard-guest-activity-content">
					<div class="vbo-dashboard-guest-activity-content-head">
						<div class="vbo-dashboard-guest-activity-content-info-details">
							<h4><?php echo $activity->first_name . (!empty($activity->last_name) ? ' ' . $activity->last_name : ''); ?></h4>
							<div class="vbo-dashboard-guest-activity-content-info-icon">
							<?php
							if ($activity_type == 'review') {
								// we display an icon as well as the score
								VikBookingIcons::e('star');
								?>
								<span class="vbo-dashboard-guest-activity-content-info-rate"><?php echo round(($activity->score / 2), 1); ?></span>
								<?php
							} else {
								// we use just an icon to tell that it's a chat guest message
								if (empty($activity->read_dt) && $sender === 'Guest') {
									// print also an icon to inform that the message was not read
									VikBookingIcons::e('exclamation-circle');
									echo ' ';
								}
								VikBookingIcons::e('comment-dots');
							}
							?>
							</div>
						</div>
						<div class="vbo-dashboard-guest-activity-content-info-date">
						<?php
						$activity_ts = strtotime($activity->last_updated);
						?>
							<span><?php echo date('H:i', $activity_ts); ?></span>
						<?php
						if (date('Y-m-d', $activity_ts) != $this->today_ymd) {
							// format and print the date
							?>
							<span><?php echo date(str_replace('/', $this->datesep, $this->df), $activity_ts); ?></span>
							<?php
						} else {
							// print "today"
							?>
							<span><?php echo JText::translate('VBTODAY'); ?></span>
							<?php
						}
						?>
						</div>
					</div>
					<div class="vbo-dashboard-guest-activity-content-info-msg">
						<p><?php echo $activity_content; ?></p>
					</div>
				</div>
			</div>
			<?php
		}

		// append navigation
		?>
		<div class="vbo-guestactivitywidget-commands">
			<div class="vbo-guestactivitywidget-commands-main">
			<?php
			if ($offset > 0) {
				// show backward navigation button
				?>
				<div class="vbo-guestactivitywidget-command-chevron vbo-guestactivitywidget-command-prev">
					<span class="vbo-guestactivitywidget-prev" onclick="vboWidgetLatestFromGuestsNavigate('<?php echo $wrapper; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
				</div>
				<?php
			}
			?>
				<div class="vbo-guestactivitywidget-command-chevron vbo-guestactivitywidget-command-next">
					<span class="vbo-guestactivitywidget-next" onclick="vboWidgetLatestFromGuestsNavigate('<?php echo $wrapper; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
				</div>
			</div>
		</div>
		<?php

		// append the total number of activities displayed, the current offset and the latest activity datetime
		$tot_activities  = count($latest_activities);
		$latest_datetime = $tot_activities > 0 && $offset === 0 ? $latest_activities[0]->last_updated : null;

		echo ';' . __FUNCTION__ . ';' . $tot_activities . ';' . __FUNCTION__ . ';' . $offset . ';' . __FUNCTION__ . ';' . $latest_datetime;
	}

	/**
	 * Custom method for this widget only to watch the latest guest activities.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * 
	 * Outputs the new number of activities found from the latest datetime.
	 */
	public function watchActivities()
	{
		$latest_dt = VikRequest::getString('latest_dt', '', 'request');
		if (empty($latest_dt)) {
			echo '0';
			return;
		}

		if (!$this->vcm_exists) {
			echo 'e4j.error.missing or outdated VCM';
			return;
		}

		// load the latest activity (one is sufficient)
		$latest_activities = [];
		try {
			$latest_activities = VikChannelManager::getLatestFromGuests(['messages', 'reviews'], 0, 1);
		} catch (Exception $e) {
			// do nothing
		}

		if (!count($latest_activities) || $latest_activities[0]->last_updated == $latest_dt) {
			// no newest activities found
			echo '0';
			return;
		}

		// print 1 to indicate that new activities should be reloaded
		echo '1';
	}

	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the latest from guests wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-latest-from-guests-' . $wrapper_instance;

		// this widget will work only if VCM is available and updated, and if permissions are met
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$this->vcm_exists || !$vbo_auth_bookings) {
			return;
		}

		// multitask data event identifier for intervals
		$js_intvals_id = '';
		if ($data && $data->isModalRendering()) {
			$js_intvals_id = $data->getModalJsIdentifier();
		}

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
			</div>
			<div id="<?php echo $wrapper_id; ?>" class="vbo-dashboard-guests-latest" data-offset="0" data-length="<?php echo $this->activities_per_type; ?>" data-latestdt="">
				<div class="vbo-dashboard-guest-activities-inner">
					<div class="vbo-dashboard-guest-activities-list">
					<?php
					for ($i = 0; $i < $this->activities_per_page; $i++) {
						?>
						<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">
							<div class="vbo-dashboard-guest-activity-avatar">
								<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>
							</div>
							<div class="vbo-dashboard-guest-activity-content">
								<div class="vbo-dashboard-guest-activity-content-head">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>
								</div>
								<div class="vbo-dashboard-guest-activity-content-subhead">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>
								</div>
								<div class="vbo-dashboard-guest-activity-content-info-msg">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>
		</div>

		<?php
		if (static::$instance_counter === 0 || $is_ajax) {
			// HTML helper tag for URL routing and some JS functions should be loaded once per widget instance
			$admin_file_base = VBOPlatformDetection::isWordPress() ? 'admin.php' : 'index.php';
		?>
		<a class="vbo-widget-latest-from-guests-basenavuri" href="<?php echo $admin_file_base; ?>?option=com_vikbooking&task=editorder&cid[]=%d" style="display: none;"></a>

		<script type="text/javascript">

			/**
			 * Open the booking details page for the clicked activity
			 */
			function vboWidgetLatestFromGuestsOpenBooking(id, url_suffix) {
				var open_url = jQuery('.vbo-widget-latest-from-guests-basenavuri').first().attr('href');
				open_url = open_url.replace('%d', id) + url_suffix;
				// navigate
				document.location.href = open_url;
			}

			/**
			 * Display the loading skeletons.
			 */
			function vboWidgetLatestFromGuestsSkeletons(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				widget_instance.find('.vbo-dashboard-guest-activities-list').html('');
				for (var i = 0; i < <?php echo $this->activities_per_page; ?>; i++) {
					var skeleton = '';
					skeleton += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">';
					skeleton += '	<div class="vbo-dashboard-guest-activity-avatar">';
					skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
					skeleton += '	</div>';
					skeleton += '	<div class="vbo-dashboard-guest-activity-content">';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-head">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
					skeleton += '		</div>';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-subhead">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>';
					skeleton += '		</div>';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-info-msg">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
					skeleton += '		</div>';
					skeleton += '	</div>';
					skeleton += '</div>';
					// append skeleton
					jQuery(skeleton).appendTo(widget_instance.find('.vbo-dashboard-guest-activities-list'));
				}
			}

			/**
			 * Perform the request to load the latest activities.
			 */
			function vboWidgetLatestFromGuestsLoad(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				var current_offset  = parseInt(widget_instance.attr('data-offset'));
				var length_per_page = parseInt(widget_instance.attr('data-length'));

				// the widget method to call
				var call_method = 'loadActivities';

				// make a request to load the activities
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						offset: current_offset,
						length: length_per_page,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}
							// response must contain 4 values separated by ";call_method;"
							var activities_data = obj_res[call_method].split(';' + call_method + ';');
							if (activities_data.length != 4) {
								return;
							}
							// replace HTML with new activities
							widget_instance.find('.vbo-dashboard-guest-activities-list').html(activities_data[0]);
							// check if latest datetime is set
							if (activities_data[3].length) {
								widget_instance.attr('data-latestdt', activities_data[3]);
							}
							// check results
							if (!isNaN(activities_data[1]) && parseInt(activities_data[1]) < 1) {
								// no results can indicate the offset is invalid or too high
								if (!isNaN(activities_data[2]) && parseInt(activities_data[2]) > 0) {
									// reset offset to 0
									widget_instance.attr('data-offset', 0);
									// show loading skeletons
									vboWidgetLatestFromGuestsSkeletons(wrapper);
									// reload the first page
									vboWidgetLatestFromGuestsLoad(wrapper);
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove the skeleton loading
						widget_instance.find('.vbo-dashboard-guest-activities-list').find('.vbo-dashboard-guest-activity-skeleton').remove();
						console.error(error);
					}
				);
			}

			/**
			 * Navigate between the various pages of the activities.
			 */
			function vboWidgetLatestFromGuestsNavigate(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// current offset
				var current_offset = parseInt(widget_instance.attr('data-offset'));

				// steps per type
				var steps = <?php echo $this->activities_per_type; ?>;

				// show loading skeletons
				vboWidgetLatestFromGuestsSkeletons(wrapper);

				// check direction and update offset for next nav
				if (direction > 0) {
					// navigate forward
					widget_instance.attr('data-offset', (current_offset + steps));
				} else {
					// navigate backward
					var new_offset = current_offset - steps;
					new_offset = new_offset >= 0 ? new_offset : 0;
					widget_instance.attr('data-offset', new_offset);
				}
				
				// launch navigation
				vboWidgetLatestFromGuestsLoad(wrapper);
			}

			/**
			 * Watch periodically if there are new activities to be displayed.
			 */
			function vboWidgetLatestFromGuestsWatch(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var latest_dt = widget_instance.attr('data-latestdt');
				if (!latest_dt || !latest_dt.length) {
					return false;
				}

				// the widget method to call
				var call_method = 'watchActivities';

				// make a request to watch the activities
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						latest_dt: latest_dt,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}
							// response will contain the number of new activities
							if (isNaN(obj_res[call_method]) || parseInt(obj_res[call_method]) < 1) {
								// do nothing
								return;
							}
							// new activities found, reset the offset and re-load the first page
							widget_instance.attr('data-offset', 0);
							// show loading skeletons
							vboWidgetLatestFromGuestsSkeletons(wrapper);
							// reload the first page
							vboWidgetLatestFromGuestsLoad(wrapper);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// do nothing
						console.error(error);
					}
				);
			}
			
		</script>
		<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// when document is ready, load latest activities for this widget's instance
				vboWidgetLatestFromGuestsLoad('<?php echo $wrapper_id; ?>');

				// set interval for loading new activities automatically
				var watch_intv = setInterval(function() {
					vboWidgetLatestFromGuestsWatch('<?php echo $wrapper_id; ?>');
				}, 60000);

			<?php
			if ($js_intvals_id) {
				// widget can be dismissed through the modal
				?>
				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_intvals_id; ?>', (e) => {
					clearInterval(watch_intv);
				});
				<?php
			}
			?>

			});

		</script>

		<?php
	}
}
