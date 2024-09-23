<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "notifications center".
 * 
 * @since 	1.16.8 (J) - 1.6.8 (WP)
 */
class VikBookingAdminWidgetNotificationsCenter extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * The number of notifications to show per page.
	 *
	 * @var 	int
	 */
	protected $records_per_page = 15;

	/**
	 * The maximum number of groups to display.
	 *
	 * @var 	int
	 */
	protected $max_groups = 4;

	/**
	 * The total number of skeleton loading elements.
	 *
	 * @var 	int
	 */
	protected $tot_skeletons = 4;

	/**
	 * The distance threshold in pixels between the current scroll
	 * position and the end of the list for triggering the loading
	 * of a next page within an infinite scroll mechanism.
	 *
	 * @var 	int
	 */
	protected $px_distance_threshold = 140;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_NOTIFSCENTER_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_NOTIFSCENTER_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		$this->widgetIcon = '<i class="' . VikBookingIcons::i('bell') . '"></i>';
		$this->widgetStyleName = 'light-orange';
	}

	/**
	 * This widget monitors the latest notification record ID to schedule
	 * periodic watch data in order to be able to update the badge counter.
	 * 
	 * @return 	object
	 */
	public function preload()
	{
		// JS lang defs
		JText::script('VBO_NOMORE_NOTIFS');
		JText::script('VBO_NO_NOTIFS');

		// load assets for datepicker
		$this->vbo_app->loadDatePicker();

		// count the number of unread notifications
		$watch_data = new stdClass;
		$watch_data->badge_count = (new VBONotificationCenter)
			->countUnread();

		return $watch_data;
	}

	/**
	 * Checks for new notifications by using the previous preloaded watch-data.
	 * This widget will actually never dispatch any notifications, but only events.
	 * 
	 * @param 	VBONotificationWatchdata 	$watch_data 	the preloaded watch-data object.
	 * 
	 * @return 	array 						data object to watch next and notifications array.
	 * 
	 * @see 	preload()
	 */
	public function getNotifications(VBONotificationWatchdata $watch_data = null)
	{
		// default empty values
		$watch_next    = null;
		$notifications = [];

		if (!$watch_data) {
			return [$watch_next, $notifications];
		}

		// get the number of unread notifications
		$unread = (new VBONotificationCenter)
			->countUnread();

		// build the next watch data for this widget
		$watch_next = new stdClass;
		$watch_next->badge_count = $unread;

		// no notifications to dispatch ever, we simply update the next watch data
		return [$watch_next, $notifications];
	}

	/**
	 * Checks for new events to be dispatched by using the previous preloaded watch-data.
	 * 
	 * @param 	VBONotificationWatchdata 	$watch_data 	the preloaded watch-data object.
	 * 
	 * @return 	array 						list of event objects to dispatch, if any.
	 * 
	 * @see 	preload()
	 */
	public function getNotificationEvents(VBONotificationWatchdata $watch_data = null)
	{
		if (!$watch_data) {
			return [];
		}

		// check the number of unread notifications
		$unread = (new VBONotificationCenter)
			->countUnread();

		if ((int) $watch_data->get('badge_count', 0) == $unread) {
			// nothing has changed
			return [];
		}

		// return the notification events to dispatch
		return [
			'vbo-badge-count' => [
				'badge_count' => $unread,
			],
		];
	}

	/**
	 * Custom method for this widget only to load the notifications from a new group.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * @return 	array
	 */
	public function loadGroupNotifications()
	{
		$input = JFactory::getApplication()->input;

		$wrapper   = $input->getString('wrapper', '');
		$group     = $input->getString('group', '');
		$from_date = $input->getString('from_date', '');
		$to_date   = $input->getString('to_date', '');
		$unread    = $input->getBool('unread', false);

		// access the notification-center object
		$notifCenter = new VBONotificationCenter;

		// build query filters
		$filters = [];

		if ($group) {
			$filters['group'] = $group;
		}

		if ($from_date || $to_date) {
			// make the filter a non-scalar value
			$filters['createdon'] = [];

			if ($from_date) {
				// push filter with operand
				$filters['createdon'][] = [
					'operand' => '>=',
					'value'   => $from_date . ' 00:00:00',
				];
			}
			if ($to_date) {
				// push filter with operand
				$filters['createdon'][] = [
					'operand' => '<=',
					'value'   => $to_date . ' 23:59:59',
				];
			}
		}

		if ($unread) {
			// make the filter a non-scalar value
			$filters['read'] = [
				[
					'operand' => '=',
					'value'   => '0',
				],
			];
		}

		// get and build the latest notifications for the requested group
		$html_content = $this->buildNotificationsHTML(
			$notifCenter->loadNotifications(0, $this->records_per_page, $filters)
		);

		// return an associative array of values
		return [
			'html' 		  => $html_content,
			'pages_count' => ceil($notifCenter->countFoundNotifications() / $this->records_per_page),
		];
	}

	/**
	 * Custom method for this widget only to load the next page of notifications.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * @return 	array
	 */
	public function loadNextNotifications()
	{
		$input = JFactory::getApplication()->input;

		$wrapper   = $input->getString('wrapper', '');
		$group     = $input->getString('group', '');
		$from_date = $input->getString('from_date', '');
		$to_date   = $input->getString('to_date', '');
		$unread    = $input->getBool('unread', false);
		$page_num  = $input->getUInt('page_num', 1);
		$page_num  = $page_num ?: 1;

		// access the notification-center object
		$notifCenter = new VBONotificationCenter;

		// build query filters
		$filters = [];

		if ($group) {
			$filters['group'] = $group;
		}

		if ($from_date || $to_date) {
			// make the filter a non-scalar value
			$filters['createdon'] = [];

			if ($from_date) {
				// push filter with operand
				$filters['createdon'][] = [
					'operand' => '>=',
					'value'   => $from_date . ' 00:00:00',
				];
			}
			if ($to_date) {
				// push filter with operand
				$filters['createdon'][] = [
					'operand' => '<=',
					'value'   => $to_date . ' 23:59:59',
				];
			}
		}

		if ($unread) {
			// make the filter a non-scalar value
			$filters['read'] = [
				[
					'operand' => '=',
					'value'   => '0',
				],
			];
		}

		// determine the query limit start
		$lim_start = ($page_num - 1) * $this->records_per_page;

		// get and build the latest notifications for the requested group
		$html_content = $this->buildNotificationsHTML(
			$notifCenter->loadNotifications($lim_start, $this->records_per_page, $filters),
			($page_num - 1)
		);

		// return an associative array of values
		return [
			'html' 		  => $html_content,
			'page_number' => $page_num,
			'pages_count' => ceil($notifCenter->countFoundNotifications() / $this->records_per_page),
		];
	}

	/**
	 * Custom method for this widget only to mark some/all notifications as read.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * @return 	array
	 */
	public function markNotificationsRead()
	{
		$input = JFactory::getApplication()->input;

		$notification_ids = $input->getUInt('notif_ids', [], 'array');
		$mark_all         = $input->getUInt('mark_all', 0);

		if (!$notification_ids && !$mark_all) {
			// do not proceed or all notifications would be marked as read
			VBOHttpDocument::getInstance()->close(400, 'No notification IDs to mark as read');
		}

		// access the notification-center object
		$notifCenter = new VBONotificationCenter;

		// update the given notification IDs as read and obtain the groups involved
		$groups = $notifCenter->readNotifications($notification_ids);

		if (!$groups) {
			VBOHttpDocument::getInstance()->close(404, 'No notifications found for marking as read');
		}

		// build a list of badge counters per group
		$group_badge_counters = [];
		foreach ($groups as $group) {
			// determine the group key for dispatching the dedicated event
			$group_key = 'vbo-badge-count' . ($group ? '-' . $group : '');

			// count the unread notifications for this group identifier
			$group_badge_counters[$group_key] = $notifCenter->countUnread($group ?: '');
		}

		// return an associative array of group-badge counters
		return $group_badge_counters;
	}

	/**
	 * Custom method for this widget only to count the unread notifications per group.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * @return 	array
	 */
	public function countUnreadNotifications()
	{
		// access the notification-center object
		$notifCenter = new VBONotificationCenter;

		// return an associative array of group-badge counters
		return [
			'vbo-badge-count' => $notifCenter->countUnread(),
		];
	}

	/**
	 * Custom method for this widget only to read some criteria-matching notifications.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * @return 	array
	 */
	public function readMatchingNotifications()
	{
		$input = JFactory::getApplication()->input;

		$criteria = $input->get('criteria', [], 'array');

		$read_count = 0;

		if (is_array($criteria) && $criteria) {
			// access the notification-center object
			$notifCenter = new VBONotificationCenter;

			// read the matching notifications
			$read_count = $notifCenter->readMatchingNotifications($criteria);
		}

		return [
			'read_count' => $read_count,
		];
	}

	/**
	 * Main method to invoke the widget.
	 * 
	 * @param 	VBOMultitaskData 	$data
	 * 
	 * @return 	void
	 */
	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the sticky notes wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-notifscenter-' . $wrapper_instance;

		// check permissions
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_bookings) {
			// permissions are not met
			return;
		}

		// check multitask data
		$in_menu 		    = false;
		$suggest_push       = false;
		$js_modal_id 		= '';
		$is_modal_rendering = false;
		if ($data) {
			// access Multitask data
			$is_modal_rendering = $data->isModalRendering();
			if ($is_modal_rendering) {
				// get modal JS identifier
				$js_modal_id = $data->getModalJsIdentifier();
			}

			/**
			 * Check the MultitaskOptions to see if the widget is rendered within
			 * the admin-menu through the Notifications Center trigger button.
			 */
			$in_menu = (bool) $this->options()->get('inMenu');
			$suggest_push = $in_menu && $this->options()->get('suggestPush');
		}

		// get minimum and maximum dates for datepicker filters
		list($mindate, $maxdate) = $this->getMinDatesNotifications();
		$mindate = empty($mindate) ? time() : $mindate;
		$maxdate = empty($maxdate) ? $mindate : $maxdate;

		// access the notification-center object
		$notifCenter = new VBONotificationCenter;

		// load the latest notifications
		$notifications = $notifCenter->loadNotifications(0, $this->records_per_page);

		// immediately count the number of pages to show all notifications
		$pages_count = ceil($notifCenter->countFoundNotifications() / $this->records_per_page);

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper<?php echo $in_menu ? ' vbo-notifications-center-inmenu-widget' : ''; ?>" data-instance="<?php echo $wrapper_instance; ?>">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
					<div class="vbo-admin-widget-head-commands">

						<div class="vbo-reportwidget-commands">
						<?php
						if ($suggest_push) {
							// suggest to subscribe to push notifications
							?>
							<div class="vbo-widget-notifscenter-suggest-push">
								<button class="vbo-widget-notifscenter-suggest-push-btn vbo-suggest-notifications-btn" type="button"><?php VikBookingIcons::e('bell', 'can-shake') ?></button>
							</div>
							<?php
						}
						?>
							<div class="vbo-reportwidget-commands-main" style="display: none;">
								<div class="vbo-reportwidget-command-dates">
									<div class="vbo-reportwidget-period-name"><?php echo JText::translate('VBNEWRESTRICTIONDATERANGE'); ?></div>
									<div class="vbo-widget-notifscenter-filter-info"></div>
								</div>
							</div>
							<div class="vbo-reportwidget-command-dots">
								<span class="vbo-widget-command-togglefilters vbo-widget-notifscenter-togglefilters" onclick="vboWidgetNotifsCenterToggleFilters('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
							</div>
						</div>
						<div class="vbo-reportwidget-filters">
							<div class="vbo-reportwidget-filter vbo-widget-notifscenter-markread">
								<span class="vbo-widget-notifscenter-read-all"><?php echo JText::translate('VBO_MARK_ALL_READ'); ?></span>
							</div>
							<div class="vbo-reportwidget-filter vbo-widget-notifscenter-onlyunread">
								<span class="vbo-widget-notifscenter-filter-lbl"><?php echo JText::translate('VBO_ONLY_UNREAD'); ?></span>
								<span class="vbo-widget-notifscenter-filter-val"><?php echo $this->vbo_app->printYesNoButtons('onlyunread', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0); ?></span>
							</div>
							<div class="vbo-reportwidget-filter">
								<span class="vbo-reportwidget-datepicker">
									<?php VikBookingIcons::e('calendar', 'vbo-widget-notifscenter-caltrigger'); ?>
									<input type="text" class="vbo-notifscenter-dtpicker-from" value="" placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBNEWRESTRICTIONDFROMRANGE')); ?>" />
								</span>
							</div>
							<div class="vbo-reportwidget-filter">
								<span class="vbo-reportwidget-datepicker">
									<?php VikBookingIcons::e('calendar', 'vbo-widget-notifscenter-caltrigger'); ?>
									<input type="text" class="vbo-notifscenter-dtpicker-to" value="" placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBNEWRESTRICTIONDTORANGE')); ?>" />
								</span>
							</div>
							<div class="vbo-reportwidget-filter vbo-reportwidget-filter-confirm vbo-widget-notifscenter-filter-confirm">
								<button type="button" class="btn btn-secondary" onclick="vboWidgetNotifsCenterClearFilters('<?php echo $wrapper_id; ?>');" title="<?php echo JHtml::fetch('esc_attr', JText::translate('VBOSIGNATURECLEAR')); ?>"><?php VikBookingIcons::e('broom'); ?></button>
								<button type="button" class="btn vbo-config-btn" onclick="vboWidgetNotifsCenterApplyFilters('<?php echo $wrapper_id; ?>');"><?php echo JText::translate('VBADMINNOTESUPD'); ?></button>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="vbo-widget-notifscenter-wrap">
				<div class="vbo-widget-notifscenter-groups">
				<?php
				foreach ($notifCenter->getGroups() as $k => $group) {
					$group_badge_val  = $group['badge_count'] ?: '';
					$group_badge_node = $group_badge_val ? '<span class="vbo-widget-notifscenter-group-badge">' . $group_badge_val . '</span>' : '';
					?>
					<div class="vbo-widget-notifscenter-group<?php echo ($k === 0 ? ' vbo-widget-notifscenter-group-active' : ''); ?>">
						<span class="vbo-widget-notifscenter-group-name" data-badge-count="<?php echo $group_badge_val; ?>" data-group-id="<?php echo $group['id']; ?>"><?php echo $group['name'] . $group_badge_node; ?></span>
					</div>
					<?php
					if (($k + 1) >= $this->max_groups) {
						break;
					}
				}
				?>
				</div>
				<div class="vbo-widget-notifscenter-list" data-group-id="" data-page-number="1" data-pages-count="<?php echo $pages_count; ?>">
					<?php
					// output all notifications
					echo $this->buildNotificationsHTML($notifications);
					?>
				</div>
				<div class="vbo-widget-notifscenter-loadmore-hidden" style="display: none;">
					<button type="button" class="btn vbo-widget-notifscenter-loadmore-manual"><?php VikBookingIcons::e('chevron-right'); ?></button>
				</div>
			</div>
		<?php
		if (!$notifications) {
			?>
			<div class="vbo-widget-notifscenter-loadmore-info">
				<p><?php echo JText::translate('VBO_NO_NOTIFS'); ?></p>
			</div>
			<?php
		}
		?>
		</div>
		<?php

		if (static::$instance_counter === 0 || $is_ajax) {
			/**
			 * Print the JS code only once for all instances of this widget.
			 */
			?>
		<script type="text/javascript">

			/**
			 * @var  array
			 */
			var vbo_widget_nc_badge_evs = [];

			/**
			 * Toggle filters.
			 */
			function vboWidgetNotifsCenterToggleFilters(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				widget_instance.find('.vbo-reportwidget-filters').toggle();
			}

			/**
			 * Datepicker dates selection.
			 */
			function vboWidgetNotifsCenterCheckDates(selectedDate, inst) {
				if (selectedDate === null || inst === null) {
					return;
				}
				var cur_from_date = jQuery(this).val();
				if (jQuery(this).hasClass('vbo-notifscenter-dtpicker-from') && cur_from_date.length) {
					var nowstart = jQuery(this).datepicker('getDate');
					var nowstartdate = new Date(nowstart.getTime());
					jQuery('.vbo-notifscenter-dtpicker-to').datepicker('option', {minDate: nowstartdate});
				}
			}

			/**
			 * Applies the selected filters and reloads the notifications.
			 */
			function vboWidgetNotifsCenterApplyFilters(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get the notifications list element
				let notificationsList = widget_instance
					.find('.vbo-widget-notifscenter-list');

				// find the currently active group
				let group = notificationsList
					.attr('data-group-id');

				group = group ? group : '';

				// get the current filter values
				let from_date = widget_instance
					.find('.vbo-notifscenter-dtpicker-from')
					.val();
				let to_date = widget_instance
					.find('.vbo-notifscenter-dtpicker-to')
					.val();
				let only_unread = widget_instance
					.find('.vbo-widget-notifscenter-onlyunread')
					.find('input[type="checkbox"]')
					.prop('checked');

				// apply the filter attributes
				notificationsList
					.attr('data-date-from', from_date);
				notificationsList
					.attr('data-date-to', to_date);
				notificationsList
					.attr('data-only-unread', (only_unread ? '1' : ''));

				// update filter information node
				if (from_date || to_date) {
					let dfilter_info = '';
					if (from_date == to_date) {
						dfilter_info = from_date;
					} else {
						dfilter_info = from_date + ' ' + (from_date && to_date ? '- ' : '') + to_date;
					}
					widget_instance
						.find('.vbo-widget-notifscenter-filter-info')
						.html(dfilter_info);
					widget_instance
						.find('.vbo-reportwidget-commands-main')
						.show();
				} else {
					widget_instance
						.find('.vbo-reportwidget-commands-main')
						.hide();
					widget_instance
						.find('.vbo-widget-notifscenter-filter-info')
						.html('');
				}

				// toggle filters
				vboWidgetNotifsCenterToggleFilters(wrapper);

				// reload the notifications for the current group
				vboWidgetNotifsCenterLoadGroupNotifs(wrapper, group);
			}

			/**
			 * Clears the current filters and reloads the notifications.
			 */
			function vboWidgetNotifsCenterClearFilters(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get the notifications list element
				let notificationsList = widget_instance
					.find('.vbo-widget-notifscenter-list');

				// find the currently active group
				let group = notificationsList
					.attr('data-group-id');

				group = group ? group : '';

				// unset current filters
				widget_instance
					.find('.vbo-notifscenter-dtpicker-from')
					.val('');
				widget_instance
					.find('.vbo-notifscenter-dtpicker-to')
					.val('');
				widget_instance
					.find('.vbo-widget-notifscenter-onlyunread')
					.find('input[type="checkbox"]')
					.prop('checked', false);

				// update the filter attributes
				notificationsList
					.attr('data-date-from', '');
				notificationsList
					.attr('data-date-to', '');
				notificationsList
					.attr('data-only-unread', '');

				// update filter information node
				widget_instance
					.find('.vbo-reportwidget-commands-main')
					.hide();
				widget_instance
					.find('.vbo-widget-notifscenter-filter-info')
					.html('');

				// toggle filters
				vboWidgetNotifsCenterToggleFilters(wrapper);

				// reload the notifications for the current group
				vboWidgetNotifsCenterLoadGroupNotifs(wrapper, group);
			}

			/**
			 * Returns the skeletons loading HTML.
			 */
			function vboWidgetNotifsCenterGetSkeletons() {
				var skeletons = '<div class="vbo-dashboard-guests-latest vbo-widget-notifscenter-skeletons">' + "\n";

				for (var i = 0; i < <?php echo $this->tot_skeletons; ?>; i++) {
					skeletons += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">' + "\n";
					skeletons += '	<div class="vbo-dashboard-guest-activity-avatar">' + "\n";
					skeletons += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>' + "\n";
					skeletons += '	</div>' + "\n";
					skeletons += '	<div class="vbo-dashboard-guest-activity-content">' + "\n";
					skeletons += '		<div class="vbo-dashboard-guest-activity-content-head">' + "\n";
					skeletons += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>' + "\n";
					skeletons += '		</div>' + "\n";
					skeletons += '		<div class="vbo-dashboard-guest-activity-content-subhead">' + "\n";
					skeletons += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>' + "\n";
					skeletons += '		</div>' + "\n";
					skeletons += '		<div class="vbo-dashboard-guest-activity-content-info-msg">' + "\n";
					skeletons += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>' + "\n";
					skeletons += '		</div>' + "\n";
					skeletons += '	</div>' + "\n";
					skeletons += '</div>' + "\n";
				}

				skeletons += '</div>' + "\n";

				return skeletons;
			}

			/**
			 * Prepares the loading of the notifications for a group.
			 */
			function vboWidgetNotifsCenterLoadGroupNotifs(wrapper, group) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// remove the currently active group
				widget_instance
					.find('.vbo-widget-notifscenter-group-active')
					.removeClass('vbo-widget-notifscenter-group-active');

				// add the active class to the clicked group
				widget_instance
					.find('.vbo-widget-notifscenter-group-name[data-group-id="' + group + '"]')
					.closest('.vbo-widget-notifscenter-group')
					.addClass('vbo-widget-notifscenter-group-active');

				// make sure to hide the information block, if any
				widget_instance
					.find('.vbo-widget-notifscenter-loadmore-info')
					.remove();

				// set the new group identifier, reset page counters and populate skeletons
				widget_instance
					.find('.vbo-widget-notifscenter-list')
					.attr('data-group-id', group)
					.attr('data-page-number', 1)
					.attr('data-pages-count', 1)
					.html(vboWidgetNotifsCenterGetSkeletons());

				// reload notifications for the current group
				vboWidgetNotifsCenterReloadNotifs(wrapper);
			}

			/**
			 * Reloads the notifications for the current group.
			 */
			function vboWidgetNotifsCenterReloadNotifs(wrapper) {
				var notificationsList = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-widget-notifscenter-list');

				if (!notificationsList) {
					throw new Error('Could not find notifications list element');
				}

				// remove scroll listener for the current list
				if (notificationsList.wrapperId) {
					// unregister the infinite scroll
					notificationsList
						.removeEventListener('scroll', vboWidgetNotifsCenterInfiniteScroll);
				}

				// get the current group
				var group_id = notificationsList.getAttribute('data-group-id');

				// get the search filters, if any
				var from_date   = notificationsList.getAttribute('data-date-from');
				var to_date     = notificationsList.getAttribute('data-date-to');
				var only_unread = notificationsList.getAttribute('data-only-unread');

				// the widget method to call
				var call_method = 'loadGroupNotifications';

				// make a request to load the notifications for the current group
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call:      call_method,
						return:    1,
						group:     group_id,
						from_date: from_date,
						to_date:   to_date,
						unread:    only_unread,
						wrapper:   wrapper,
						tmpl:      "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// replace HTML with the reloaded notifications for the current group and set page infos
							notificationsList
								.setAttribute('data-page-number', 1);
							notificationsList
								.setAttribute('data-pages-count', obj_res[call_method]['pages_count']);
							notificationsList
								.innerHTML = obj_res[call_method]['html'];

							if (!obj_res[call_method]['html']) {
								// print message for no notifications found
								notificationsList
									.innerHTML = '<div class="vbo-widget-notifscenter-loadmore-info"><p>' + Joomla.JText._('VBO_NO_NOTIFS') + '</p></div>';
							} else {
								// schedule setup functions
								setTimeout(() => {
									// set up infinite scroll listener for the new list
									vboWidgetNotifsCenterSetupInfiniteScroll(wrapper);

									// set up notifications click listeners
									vboWidgetNotifsCenterRegisterClickListeners(wrapper);
								}, 100);
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// display the error
						alert(error.responseText);

						// remove loading skeletons
						notificationsList
							.querySelector('.vbo-widget-notifscenter-skeletons')
							.remove();
					}
				);
			}

			/**
			 * Loads the next page of notifications.
			 */
			function vboWidgetNotifsCenterLoadNextPage(wrapper) {
				var notificationsList = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-widget-notifscenter-list');

				if (!notificationsList) {
					throw new Error('Could not find notifications list element');
				}

				// ensure we've got other pages to load
				var pageNumber = notificationsList.getAttribute('data-page-number');
				var pagesCount = notificationsList.getAttribute('data-pages-count');

				if (pageNumber >= pagesCount) {
					// no more pages available, abort
					return;
				}

				// load the next page of notifications

				// append loading skeletons
				notificationsList
					.insertAdjacentHTML('beforeend', vboWidgetNotifsCenterGetSkeletons());

				// get the current group
				var group_id = notificationsList.getAttribute('data-group-id');

				// get the search filters, if any
				var from_date   = notificationsList.getAttribute('data-date-from');
				var to_date     = notificationsList.getAttribute('data-date-to');
				var only_unread = notificationsList.getAttribute('data-only-unread');

				// the widget method to call
				var call_method = 'loadNextNotifications';

				// make a request to load the next page of notifications for the current group
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call:      call_method,
						return:    1,
						group:     group_id,
						from_date: from_date,
						to_date:   to_date,
						unread:    only_unread,
						page_num:  parseInt(pageNumber) + 1,
						wrapper:   wrapper,
						tmpl:      "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// remove loading skeletons
							notificationsList
								.querySelector('.vbo-widget-notifscenter-skeletons')
								.remove();

							// append HTML with the new notifications for the current group and set page infos
							notificationsList
								.setAttribute('data-page-number', obj_res[call_method]['page_number']);
							notificationsList
								.setAttribute('data-pages-count', obj_res[call_method]['pages_count']);
							notificationsList
								.insertAdjacentHTML('beforeend', obj_res[call_method]['html']);

							// turn custom property off for the page loading
							notificationsList.pageLoading = false;

							// set up notifications click listeners for the new notifications read
							vboWidgetNotifsCenterRegisterClickListeners(wrapper);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// display the error
						alert(error.responseText);

						// turn custom property off for the page loading
						notificationsList.pageLoading = false;

						// remove loading skeletons
						notificationsList
							.querySelector('.vbo-widget-notifscenter-skeletons')
							.remove();
					}
				);
			}

			/**
			 * Setups the infinite scroll loading.
			 */
			function vboWidgetNotifsCenterSetupInfiniteScroll(wrapper) {
				var notificationsList = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-widget-notifscenter-list');

				if (!notificationsList) {
					throw new Error('Could not find notifications list element');
				}

				// ensure we've got more pages to load
				var pageNumber = notificationsList.getAttribute('data-page-number');
				var pagesCount = notificationsList.getAttribute('data-pages-count');

				if (pageNumber >= pagesCount) {
					// no pagination needed
					return;
				}

				// get wrapper dimensions
				var listViewHeight = notificationsList.offsetHeight;
				var listGlobHeight = notificationsList.scrollHeight;
				var listScrollTop  = notificationsList.scrollTop;

				if (listViewHeight >= listGlobHeight) {
					// no scrolling detected, show manual loading
					document
						.querySelector('#' + wrapper)
						.querySelector('.vbo-widget-notifscenter-loadmore-hidden')
						.style
						.display = 'block';

					return;
				}

				// inject custom property to identify the wrapper ID
				notificationsList.wrapperId = wrapper;

				// register infinite scroll event handler
				notificationsList
					.addEventListener('scroll', vboWidgetNotifsCenterInfiniteScroll);
			}

			/**
			 * Infinite scroll event handler.
			 */
			function vboWidgetNotifsCenterInfiniteScroll(e) {
				// access the injected wrapper ID property
				var wrapper = e.currentTarget.wrapperId;

				if (!wrapper) {
					return;
				}

				// register throttling callback
				VBOCore.throttleTimer(() => {
					// access the current notifications list
					var notificationsList = document
						.querySelector('#' + wrapper)
						.querySelector('.vbo-widget-notifscenter-list');

					// ensure we've got more pages to load
					var pageNumber = notificationsList.getAttribute('data-page-number');
					var pagesCount = notificationsList.getAttribute('data-pages-count');

					if (pageNumber >= pagesCount) {
						// unregister the infinite scroll
						notificationsList
							.removeEventListener('scroll', vboWidgetNotifsCenterInfiniteScroll);

						// display message for all notifications loaded
						if (pagesCount > 1) {
							let widget_content = document
								.querySelector('#' + wrapper);

							// hide the eventually displayed manual loading
							widget_content
								.querySelector('.vbo-widget-notifscenter-loadmore-hidden')
								.style
								.display = 'none';

							if (!widget_content.querySelector('.vbo-widget-notifscenter-loadmore-info')) {
								// append the message stating that all notifications have been displayed
								let infoDiv = document
									.createElement('div');
								infoDiv.classList
									.add('vbo-widget-notifscenter-loadmore-info');

								let infoTxt = document
									.createElement('p');
								infoTxt.append(Joomla.JText._('VBO_NOMORE_NOTIFS'));

								infoDiv.append(infoTxt);

								widget_content.append(infoDiv);
							}
						}

						return;
					}

					// make sure the loading of a next page isn't running
					if (notificationsList.pageLoading) {
						// abort
						return;
					}

					// get wrapper dimensions
					var listViewHeight = notificationsList.offsetHeight;
					var listGlobHeight = notificationsList.scrollHeight;
					var listScrollTop  = notificationsList.scrollTop;

					if (!listScrollTop || listViewHeight >= listGlobHeight) {
						// no scrolling detected at all
						return;
					}

					// calculate missing distance to the end of the list
					var listEndDistance = listGlobHeight - (listViewHeight + listScrollTop);

					if (listEndDistance < <?php echo $this->px_distance_threshold; ?>) {
						// inject custom property to identify a next page is loading
						notificationsList.pageLoading = true;

						// load the next page of notifications
						vboWidgetNotifsCenterLoadNextPage(wrapper);
					}
				}, 500);
			}

			/**
			 * Registers the click listener on all the eligible notification entries.
			 */
			function vboWidgetNotifsCenterRegisterClickListeners(wrapper) {
				var notifications = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-widget-notifscenter-list')
					.querySelectorAll('.vbo-widget-notifscenter-notif-wrap:not([data-listening])');

				notifications.forEach((notification) => {
					// immediately set attribute flag with listening enabled
					notification.setAttribute('data-listening', 1);

					// check if the notification has to be read
					if (notification.classList.contains('vbo-widget-notifscenter-notif-unread')) {
						// add click event listener to mark the notification as read
						notification.addEventListener('click', vboWidgetNotifsCenterReadNotification);
					}

					// get main attributes
					let idorder    = notification.getAttribute('data-idorder');
					let idorderota = notification.getAttribute('data-idorderota');

					if (idorder || idorderota) {
						// add click event listener to open the booking details admin-widget
						notification.addEventListener('click', (e) => {
							if (e.target && e.target.classList.contains('vbo-notifscenter-cta-btn')) {
								// do nothing on a call-to-action button
								return;
							}
							// this event listener will never need to be removed
							VBOCore.handleDisplayWidgetNotification({widget_id: 'booking_details'}, {
								bid: idorder || idorderota,
								modal_options: {
									/**
									 * Overwrite modal options for rendering the admin widget.
									 * We need to use a different suffix in case this current widget was
									 * also rendered within a modal, or it would get dismissed in favour
									 * of the newly opened admin widget.
									 */
									suffix: 'widget_modal_inner_booking_details',
								},
							});
						});
					}
				});
			}

			/**
			 * Read notification click event handler.
			 */
			function vboWidgetNotifsCenterReadNotification(e) {
				let notification = e.currentTarget || null;
				if (!notification) {
					throw new Error('This function requires an event target');
				}

				// immediately remove the click listener to read this notification
				notification.removeEventListener('click', vboWidgetNotifsCenterReadNotification);

				// add the "read" class
				notification.classList.add('vbo-widget-notifscenter-notif-read');

				// remove the "unread" class
				notification.classList.remove('vbo-widget-notifscenter-notif-unread');

				// the widget method to call
				var call_method = 'markNotificationsRead';

				// execute the request to update the notification as read
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call:      call_method,
						return:    1,
						notif_ids: [notification.getAttribute('data-notif-id')],
						tmpl:      "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// build events data object
							let events_data = {};

							// dispatch the events for the groups returned to update the badge counters
							for (var ev_name in obj_res[call_method]) {
								if (!obj_res[call_method].hasOwnProperty(ev_name)) {
									continue;
								}

								if (ev_name.indexOf('vbo-badge-count') !== 0) {
									// not a valid event name
									continue;
								}

								// build the event data object
								let event_data = {
									badge_count: obj_res[call_method][ev_name],
								};

								// dispatch the group badge count update event
								VBOCore.emitEvent(ev_name, event_data);

								// set event data property to object
								events_data[ev_name] = event_data;
							}

							// post message onto broadcast channel for any other browsing context
							if (VBOCore.broadcast_watch_events) {
								// this will trigger the events on any other browsing context
								VBOCore.broadcast_watch_events.postMessage(events_data);
							}
						} catch (err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// silently log the error
						console.error(error.responseText);
					}
				);
			}

			/**
			 * Mark all notifications as read.
			 */
			function vboWidgetNotifsCenterReadAllNotifs(wrapper) {
				// toggle filters
				vboWidgetNotifsCenterToggleFilters(wrapper);

				// remove the unread class from any occurrence
				document
					.querySelectorAll('.vbo-widget-notifscenter-notif-wrap.vbo-widget-notifscenter-notif-unread')
					.forEach((notification) => {
						notification.classList.add('vbo-widget-notifscenter-notif-read');
						notification.classList.remove('vbo-widget-notifscenter-notif-unread');
					});

				// the widget method to call
				var call_method = 'markNotificationsRead';

				// execute the request to update the notification as read
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call:      call_method,
						return:    1,
						notif_ids: [],
						mark_all:  1,
						tmpl:      "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// dispatch the events for the groups returned to update the badge counters
							for (var ev_name in obj_res[call_method]) {
								if (!obj_res[call_method].hasOwnProperty(ev_name)) {
									continue;
								}
								if (ev_name.indexOf('vbo-badge-count') !== 0) {
									// not a valid event name
									continue;
								}
								// dispatch the group badge count update event
								VBOCore.emitEvent(ev_name, {
									badge_count: obj_res[call_method][ev_name],
								});
							}
						} catch (err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// silently log the error
						console.error(error.responseText);
					}
				);
			}

			/**
			 * Registers the event listeners for updating the group badge counters.
			 */
			function vboWidgetNotifsCenterRegisterGroupBadgeListeners(wrapper) {
				var groups = document
					.querySelector('#' + wrapper)
					.querySelectorAll('.vbo-widget-notifscenter-group-name');

				groups.forEach((group) => {
					let group_id = group.getAttribute('data-group-id');
					let group_event = 'vbo-badge-count' + (group_id ? '-' + group_id : '');

					/**
					 * Register the listener with a precise handler so that it can be removed upon destruction,
					 * unlike the global event "vbo-badge-count" for the in-menu Notifications Center handler.
					 */
					document.addEventListener(group_event, vboWidgetNotifsCenterUpdateGroupBadge);

					// push event name for later destruction
					vbo_widget_nc_badge_evs.push(group_event);
				});
			}

			/**
			 * Update group badge counter event handler.
			 */
			function vboWidgetNotifsCenterUpdateGroupBadge(e) {
				if (!e || !e.detail || !e.detail.hasOwnProperty('badge_count') || isNaN(e.detail['badge_count'])) {
					return;
				}

				// get the current counter
				let badge_count = parseInt(e.detail['badge_count']);

				// get the event type to identify the group ID
				let group_nm_rgx = new RegExp(/^vbo-badge-count-?/);
				let group_id = e.type.replace(group_nm_rgx, '');

				// parse all group badges of this type-ID in the document
				var groups = document
					.querySelectorAll('.vbo-widget-notifscenter-group-name[data-group-id="' + group_id + '"]');

				groups.forEach((group) => {
					// find the child node for better supporting large numbers
					let group_badge_element = group.querySelector('.vbo-widget-notifscenter-group-badge');

					if (badge_count <= 0) {
						// no notifications to be read
						group.setAttribute('data-badge-count', '');

						// delete the badge element node, if any
						if (group_badge_element) {
							group_badge_element.remove();
						}
					} else {
						// update badge counter
						group.setAttribute('data-badge-count', badge_count);

						// append or update badge element node
						if (group_badge_element) {
							// update badge element node
							group_badge_element.innerText = badge_count;
						} else {
							// append badge element node
							let group_badge_node = document.createElement('span');
							group_badge_node.className = 'vbo-widget-notifscenter-group-badge';
							group_badge_node.innerText = badge_count;
							group.appendChild(group_badge_node);
						}
					}
				});
			}

			/**
			 * Removes the group badge update event listeners.
			 */
			function vboWidgetNotifsCenterRemoveGroupBadgeListeners(e) {
				e.stopPropagation();

				// remove event listener so that it will be re-registered
				document.removeEventListener(<?php echo $js_modal_id ? "VBOCore.widget_modal_dismissed + '{$js_modal_id}'" : "'vbo_widget_nc_destroy'"; ?>, vboWidgetNotifsCenterRemoveGroupBadgeListeners);

				if (vbo_widget_nc_badge_evs && Array.isArray(vbo_widget_nc_badge_evs)) {
					vbo_widget_nc_badge_evs.forEach((ev_name, index) => {
						// remove event listener for updating the group badge
						document.removeEventListener(ev_name, vboWidgetNotifsCenterUpdateGroupBadge);

						// remove the current element from the array
						vbo_widget_nc_badge_evs.splice(index, 1);
					});
				}
			}

			/**
			 * Register event listener that runs upon widget destruction.
			 */
			document.addEventListener(<?php echo $js_modal_id ? "VBOCore.widget_modal_dismissed + '{$js_modal_id}'" : "'vbo_widget_nc_destroy'"; ?>, vboWidgetNotifsCenterRemoveGroupBadgeListeners);

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// listen to the click event on the group names
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-notifscenter-group').on('click', function() {
					// get clicked group identifier
					var group_id = jQuery(this)
						.find('.vbo-widget-notifscenter-group-name')
						.attr('data-group-id');

					// load the notifications for the clicked group, even if it's active
					vboWidgetNotifsCenterLoadGroupNotifs('<?php echo $wrapper_id; ?>', group_id);
				});

				// listen to the "mark all as read" command
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-notifscenter-read-all').on('click', function() {
					vboWidgetNotifsCenterReadAllNotifs('<?php echo $wrapper_id; ?>');
				});

				// listen to the manual load-more button
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-notifscenter-loadmore-manual').on('click', function() {
					// load the next page of notifications
					vboWidgetNotifsCenterLoadNextPage('<?php echo $wrapper_id; ?>');
				});

				// set up infinite scroll loading
				vboWidgetNotifsCenterSetupInfiniteScroll('<?php echo $wrapper_id; ?>');

				// set up notifications click listeners
				vboWidgetNotifsCenterRegisterClickListeners('<?php echo $wrapper_id; ?>');

				// set up group badge update listeners
				vboWidgetNotifsCenterRegisterGroupBadgeListeners('<?php echo $wrapper_id; ?>');

				// render datepicker calendars for date filters
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-notifscenter-dtpicker-from, .vbo-notifscenter-dtpicker-to').datepicker({
					minDate: "<?php echo date('Y-m-d', $mindate); ?>",
					maxDate: "<?php echo date('Y-m-d', $maxdate); ?>",
					yearRange: "<?php echo date('Y', $mindate); ?>:<?php echo date('Y', $maxdate); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "yy-mm-dd",
					onSelect: vboWidgetNotifsCenterCheckDates
				});

				// triggering for datepicker calendar icons
				jQuery('i.vbo-widget-notifscenter-caltrigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

				if (<?php echo $suggest_push ? 'true' : 'false'; ?>) {
					// suggest to subscribe to push notifications
					VBOCore.suggestNotifications('.vbo-widget-notifscenter-suggest-push-btn');
				}

			});

		</script>

		<?php
	}

	/**
	 * Given a list of notification objects, builds and returns the HTML rendering code.
	 * 
	 * @param 	array 	$notifications 	list of notification objects to render.
	 * @param 	int 	$page_num 		optional page number.
	 * 
	 * @return 	string
	 */
	protected function buildNotificationsHTML(array $notifications, int $page_num = 0)
	{
		if (!$notifications) {
			return '';
		}

		// get back-end logo URI
		$backlogo = VikBooking::getBackendLogo();

		// start output buffering
		ob_start();

		foreach ($notifications as $index => $notif) {
			// obtain notification date info
			$date_info = VBORemindersHelper::getInstance()->relativeDatesDiff(JHtml::fetch('date', $notif->createdon, 'Y-m-d H:i:s'));

			// build relative date
			$relative_dt = $date_info['relative'];
			if ($date_info['past'] && ($date_info['today'] || ($date_info['yesterday'] && !$date_info['days']))) {
				// recent date to be expressed as hours, minutes or seconds ago
				if ($date_info['hours']) {
					$rel_time_str = $date_info['hours'] . ' ' . JText::translate(($date_info['hours'] == 1 ? 'VBO_HOUR' : 'VBCONFIGONETENEIGHT'));
				} elseif ($date_info['minutes']) {
					$rel_time_str = $date_info['minutes'] . ' ' . JText::translate(($date_info['minutes'] == 1 ? 'VBO_MINUTE' : 'VBTRKDIFFMINS'));
				} else {
					$rel_time_str = $date_info['seconds'] . ' ' . JText::translate(($date_info['seconds'] == 1 ? 'VBO_SECOND' : 'VBTRKDIFFSECS'));
				}
				$relative_dt = JText::sprintf('VBO_REL_EXP_PAST', strtolower($rel_time_str));
			} elseif ($date_info['past'] && $date_info['yesterday'] && $date_info['days'] == 1 && $date_info['hours'] < 6) {
				// less than 30 hours ago
				$rel_time_str = ($date_info['hours'] + 24) . ' ' . JText::translate('VBCONFIGONETENEIGHT');
				$relative_dt = JText::sprintf('VBO_REL_EXP_PAST', strtolower($rel_time_str));
			}

			// build a human-readable date and time
			if ($date_info['today']) {
				$human_dtime = JHtml::fetch('date', $notif->createdon, 'H:i:s');
			} else {
				$human_dtime = JHtml::fetch('date', $notif->createdon, str_replace("/", $this->datesep, $this->df) . ' H:i:s');
			}

			// get channel logo and name
			$channel_logo = '';
			$channel_name = '';
			if (!empty($notif->channel)) {
				$ch_logo_obj  = VikBooking::getVcmChannelsLogo($notif->channel, true);
				$channel_logo = is_object($ch_logo_obj) ? $ch_logo_obj->getSmallLogoURL() : '';
				$channelparts = explode('_', $notif->channel);
				$channel_name = isset($channelparts[1]) && strlen((string)$channelparts[1]) ? $channelparts[1] : ucwords($channelparts[0]);
			}

			// check if the notification group belongs to specific types
			$group_website = !strcasecmp($notif->group, 'website');
			$group_guests  = !strcasecmp($notif->group, 'guests');

			// determine the notification group icon and color
			$group_badge_icon = '';
			$group_badge_cls  = '';
			if (!strcasecmp($notif->group, 'website')) {
				$group_badge_icon = 'clipboard-list';
				$group_badge_cls  = 'vbo-badge-group-green';
				if (in_array($notif->type, ['p0', 'pn'])) {
					$group_badge_icon = 'credit-card';
				} elseif ($notif->type == 'info') {
					$group_badge_icon = 'bullhorn';
				}
				if (in_array($notif->type, ['cr', 'cw', 'ob'])) {
					$group_badge_cls = 'vbo-badge-group-orange';
				}
			} elseif (!strcasecmp($notif->group, 'otas')) {
				$group_badge_icon = 'cloud';
				$group_badge_cls  = 'vbo-badge-group-purple';
				if (in_array($notif->type, ['po', 'vcc_balance', 'vcc_balance_updated'])) {
					$group_badge_icon = 'credit-card';
				}
				if (in_array($notif->type, ['cc', 'ob'])) {
					$group_badge_cls = 'vbo-badge-group-orange';
				}
			} elseif (!strcasecmp($notif->group, 'guests')) {
				$group_badge_icon = 'comment-dots';
				$group_badge_cls  = 'vbo-badge-group-lightblue';
			} elseif (!strcasecmp($notif->group, 'cm')) {
				$group_badge_icon = 'bullhorn';
				$group_badge_cls  = 'vbo-badge-group-lightblue';
			} elseif (!strcasecmp($notif->group, 'operators')) {
				$group_badge_icon = 'broom';
				$group_badge_cls  = 'vbo-badge-group-orange';
			}

			?>
			<div 
				class="vbo-widget-notifscenter-notif-wrap vbo-widget-notifscenter-notif-<?php echo $notif->read ? 'read' : 'unread'; ?>"
				data-notif-id="<?php echo $notif->id; ?>"
				data-idorder="<?php echo $notif->idorder; ?>"
				data-idorderota="<?php echo $notif->idorderota; ?>"
			>
				<div class="vbo-widget-notifscenter-notif-avatar">
					<div class="vbo-customer-info-box">
						<div class="vbo-customer-info-box-avatar vbo-customer-avatar-medium">
						<?php
						if (!empty($notif->customer_pic)) {
							// use customer profile picture
							?>
							<span class="vbo-widget-notifscenter-cpic-zoom">
								<img src="<?php echo strpos($notif->customer_pic, 'http') === 0 ? $notif->customer_pic : VBO_SITE_URI . 'resources/uploads/' . $notif->customer_pic; ?>" data-caption="<?php echo JHtml::fetch('esc_attr', (string) $notif->customer_name); ?>" />
							</span>
							<?php
						} elseif (!empty($channel_logo)) {
							// use channel logo
							?>
							<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $channel_name); ?>">
								<img src="<?php echo $channel_logo; ?>" />
							</span>
							<?php
						} elseif (!empty($notif->customer_name)) {
							// use customer initials
							?>
							<span>
								<span class="vbo-widget-notifscenter-customer-initials"><?php echo $this->getCustomerInitials($notif->customer_name); ?></span>
							</span>
							<?php
						} elseif (!empty($backlogo)) {
							// use back-end logo
							?>
							<span>
								<img src="<?php echo VBO_ADMIN_URI . "resources/{$backlogo}"; ?>" />
							</span>
							<?php
						} else {
							// fallback onto website icon
							?>
							<span><?php VikBookingIcons::e('hotel', 'vbo-dashboard-guest-activity-avatar-icon'); ?></span>
							<?php
						}

						// take care of the group badge icon and color, if any
						if ($group_badge_icon) {
							?>
							<span class="vbo-customer-avatar-badge <?php echo $group_badge_cls; ?>">
								<?php VikBookingIcons::e($group_badge_icon); ?>
							</span>
							<?php
						}
						?>
						</div>
					</div>
				</div>
				<div class="vbo-widget-notifscenter-notif-details">
				<?php
				if ($notif->title) {
					if (preg_match("/^VBO/", $notif->title)) {
						/**
						 * @todo  to be removed on next updates.
						 */
						$notif->title = JText::translate($notif->title);
					}
					?>
					<div class="vbo-widget-notifscenter-notif-head">
						<span class="vbo-widget-notifscenter-notif-title"><?php echo $notif->title; ?></span>
					<?php
					if (!$group_guests && $notif->customer_name) {
						?>
						<span class="vbo-widget-notifscenter-notif-subtitle">&bull; <?php echo $notif->customer_name; ?></span>
						<?php
					}
					?>
					</div>
					<?php
				}
				if ((!$group_website || !strcasecmp($notif->type, 'info')) && $notif->summary) {
					?>
					<div class="vbo-widget-notifscenter-notif-summary">
						<span><?php echo $notif->summary; ?></span>
					</div>
					<?php
				}
				?>
					<div class="vbo-widget-notifscenter-notif-dt">
					<?php
					if ($notif->idorder || $notif->idorderota) {
						?>
						<span class="label label-info"><?php echo $notif->idorder ?: $notif->idorderota; ?></span>
						<?php
					}
					?>
						<span class="vbo-tooltip vbo-tooltip-<?php echo !$index && !$page_num ? 'bottom' : 'top'; ?>" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $human_dtime); ?>"><?php echo $relative_dt; ?></span>
					</div>
				<?php
				if (is_object($notif->cta_data) && (!empty($notif->cta_data->url) || !empty($notif->cta_data->widget))) {
					// call-to-action data available
					$cta_btn_lbl = !empty($notif->cta_data->label) ? $notif->cta_data->label : JText::translate('VBO_TAKE_ACTION');
					?>
					<div class="vbo-widget-notifscenter-notif-cta">
					<?php
					if (!empty($notif->cta_data->url)) {
						// open "remote" URL
						?>
						<a class="btn btn-small vbo-notifscenter-cta-btn" href="<?php echo JHtml::fetch('esc_attr', $notif->cta_data->url); ?>" target="_blank"><?php echo $cta_btn_lbl; ?></a>
						<?php
					} elseif (!empty($notif->cta_data->widget)) {
						// open a specific admin-widget with the necessary options
						$def_widget_options = [
							'modal_options' => [
								'suffix' => 'widget_modal_inner_' . $notif->cta_data->widget,
							],
						];
						if (is_object($notif->cta_data->widget_options) && !isset($notif->cta_data->widget_options->modal_options)) {
							// inject the suffix property
							$notif->cta_data->widget_options->modal_options = new stdClass;
							$notif->cta_data->widget_options->modal_options->suffix = 'widget_modal_inner_' . $notif->cta_data->widget;
						}
						$js_options = !empty($notif->cta_data->widget_options) ? json_encode($notif->cta_data->widget_options) : json_encode($def_widget_options);
						$js_command = 'VBOCore.handleDisplayWidgetNotification({widget_id: "' . $notif->cta_data->widget . '"}, ' . $js_options . ');';
						?>
						<button type="button" class="btn btn-small vbo-notifscenter-cta-btn" onclick="<?php echo JHtml::fetch('esc_attr', $js_command); ?>"><?php echo $cta_btn_lbl; ?></button>
						<?php
					}
					?>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<?php
		}

		// get the HTML buffer
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Returns the initials for the given full name.
	 * 
	 * @param 	string 	$name 	the full name (first + last).
	 * 
	 * @return 	string 			the capitalized name initials.
	 */
	protected function getCustomerInitials(string $name)
	{
		$parts = explode(' ', trim($name));

		$first = $parts[0];
		$last  = end($parts);

		if (function_exists('mb_substr')) {
			if ($first != $last) {
				return strtoupper(mb_substr($first, 0, 1, 'UTF-8') . mb_substr($last, 0, 1, 'UTF-8'));
			}

			return strtoupper(mb_substr($first, 0, 2, 'UTF-8'));
		}

		if ($first != $last) {
			return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
		}

		return strtoupper(substr($first, 0, 2));
	}

	/**
	 * Returns an array with the minimum and maximum dates with notifications.
	 * 
	 * @return 	array 	to be used with list() to get the min/max notification date timestamps.
	 */
	protected function getMinDatesNotifications()
	{
		$dbo = JFactory::getDbo();

		$mindate = null;
		$maxdate = null;

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('MIN(' . $dbo->qn('createdon') . ') AS ' . $dbo->qn('mindate'))
				->select('MAX(' . $dbo->qn('createdon') . ') AS ' . $dbo->qn('maxdate'))
				->from($dbo->qn('#__vikbooking_notifications'))
		);

		$info_dates = $dbo->loadObject();

		if ($info_dates && !empty($info_dates->mindate)) {
			$mindate = strtotime(JHtml::fetch('date', $info_dates->mindate, 'Y-m-d'));
			$maxdate = strtotime(JHtml::fetch('date', $info_dates->maxdate, 'Y-m-d'));
		}

		return [$mindate, $maxdate];
	}
}
