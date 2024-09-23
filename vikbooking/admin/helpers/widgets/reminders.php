<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2022 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "reminders".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingAdminWidgetReminders extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Default number of reminders per page.
	 * 
	 * @var 	int
	 */
	protected $reminders_per_page = 6;

	/**
	 * Tells whether VCM is installed.
	 * 
	 * @var 	bool
	 */
	protected $vcm_exists = false;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_REMINDERS_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_REMINDERS_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('bell') . '"></i>';
		$this->widgetStyleName = 'green';

		// load widget's settings
		$this->widgetSettings = $this->loadSettings();
		if (!is_object($this->widgetSettings)) {
			$this->widgetSettings = new stdClass;
		}

		// check if VCM is available
		if (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			$this->vcm_exists = true;
		}
	}

	/**
	 * This widget does not actually need to preload data to be watched for
	 * dispatching notifications, and the reminders due are automatically
	 * scheduled for being dispatched as a browser notification. However,
	 * we make use of this method to periodically check if some reminders
	 * should be automatically created for specific reasons, such as the
	 * Airbnb host-to-guest reviews to remind the host to review the guest.
	 * We also preload the necessary CSS/JS assets and schedule the imminent
	 * reminders for notifications, if any.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// declare JS lang vars
		JText::script('VBO_PLEASE_FILL_FIELDS');
		JText::script('VBDELCONFIRM');
		JText::script('VBCONFIGCLOSINGDATEADD');
		JText::script('VBADMINNOTESUPD');

		// load assets
		$this->vbo_app->loadDatePicker();

		// check if VCM is available
		if ($this->vcm_exists) {
			$today_dt 	  = date('Y-m-d');
			$yesterday_dt = date('Y-m-d', strtotime('yesterday'));

			// check the last time this check was made
			$last_check_dt = isset($this->widgetSettings->last_check_dt) ? $this->widgetSettings->last_check_dt : $yesterday_dt;

			if ($last_check_dt != $today_dt) {
				// update last check date
				$this->widgetSettings->last_check_dt = $today_dt;
				$this->updateSettings(json_encode($this->widgetSettings));

				// check for any Channel Manager reservation that requires an auto-reminder
				$this->scheduleChannelManagerReminders();
			}
		}

		// load the 10 next (imminent) reminders
		$overdue = VBORemindersHelper::getInstance()->getImminents(10);
		if ($overdue) {
			// schedule browser notifications for the next reminders
			VBONotificationScheduler::getInstance()->enqueueReminders($overdue);
		}

		// nothing should be actually watched, ever
		return null;
	}

	/**
	 * Custom method for this widget only to load the reminder records.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * It's the actual rendering of the widget which also allows navigation.
	 * 
	 * @return 	array
	 */
	public function loadReminders()
	{
		$offset = VikRequest::getInt('offset', 0, 'request');
		$length = VikRequest::getInt('length', $this->reminders_per_page, 'request');
		$completed = VikRequest::getInt('completed', 0, 'request');
		$expired = VikRequest::getInt('expired', 0, 'request');
		$bid = VikRequest::getInt('bid', 0, 'request');
		$wrapper = VikRequest::getString('wrapper', '', 'request');

		// load reminders starting from today at 00:00:00
		$now_info = getdate();
		$lim_min_date = date('Y-m-d H:i:s', mktime(0, 0, 0, $now_info['mon'], $now_info['mday'], $now_info['year']));

		// prepare fetch options
		$fetch = [
			'after' 	=> $lim_min_date,
			'completed' => $completed,
			'expired' 	=> $expired,
			'idorder' 	=> $bid,
		];

		// load reminders
		$helper = VBORemindersHelper::getInstance();
		$reminders = $helper->loadReminders($fetch, $offset, $length);

		// check if a next page can be available
		$has_next_page = (count($reminders) >= $length);

		// compose bookings base URI
		$bookings_base_uri = VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=%d', $xhtml = false);

		// start output buffering
		ob_start();

		if (!$reminders) {
			?>
		<p class="info"><?php echo JText::translate('VBONORESULTS'); ?></p>
			<?php
		} else {
			// update widget's settings with the lastly used filters
			$this->widgetSettings->show_completed = $completed;
			$this->widgetSettings->show_expired   = $expired;
			$this->updateSettings(json_encode($this->widgetSettings));
		}

		foreach ($reminders as $reminder) {
			$use_icn_status = $reminder->completed ? 'dot-circle' : 'far fa-circle';
			$hidden_date = '';
			$hidden_hours = '';
			$hidden_minutes = '';
			if (!empty($reminder->duedate)) {
				$date_parts = explode(' ', $reminder->duedate);
				$hidden_date = date($this->df, strtotime($date_parts[0]));
				if ($reminder->usetime) {
					$time_parts = explode(':', $date_parts[1]);
					$hidden_hours = (int)$time_parts[0];
					$hidden_minutes = (int)$time_parts[1];
				}
			}
			$is_past_reminder = false;
			if (!empty($reminder->duedate)) {
				// calculate distance to expiration date from today
				$diff_data = $helper->relativeDatesDiff($reminder->duedate);
				$is_past_reminder = $diff_data['past'];
			}

			// list of extra classes for the reminder
			$extra_classes = [];
			if ($is_past_reminder) {
				$extra_classes[] = 'vbo-reminder-past';
			} else {
				$extra_classes[] = 'vbo-reminder-future';
			}
			if ($reminder->completed) {
				$extra_classes[] = 'vbo-reminder-ok';
			} else {
				$extra_classes[] = 'vbo-reminder-nok';
			}

			?>
		<div class="vbo-widget-reminders-record <?php echo implode(' ', $extra_classes); ?>" data-reminderid="<?php echo $reminder->id; ?>">
			<div class="vbo-widget-reminders-record-status">
				<button type="button" class="vbo-reminder-status-dot" onclick="vboWidgetRemindersComplete('<?php echo $wrapper; ?>', '<?php echo $reminder->id; ?>');"><?php VikBookingIcons::e($use_icn_status); ?></button>
			<?php
			if ($reminder->important) {
				?>
				<div class="vbo-widget-reminders-record-important">
					<?php VikBookingIcons::e('exclamation-triangle'); ?>
				</div>
				<?php
			}
			?>
			</div>
			<div class="vbo-widget-reminders-record-info">
				<div class="vbo-widget-reminders-record-txt">
					<span class="vbo-widget-reminder-title"><?php echo htmlspecialchars($reminder->title); ?></span>
					<?php
					if (!empty($reminder->descr)) {
						?>
					<span class="vbo-widget-reminder-descr"><?php echo htmlspecialchars($reminder->descr); ?></span>
						<?php
					}
					?>
				</div>
				<div class="vbo-widget-reminders-record-due">
					<span class="vbo-widget-reminder-date" style="display: none;"><?php echo $hidden_date; ?></span>
					<span class="vbo-widget-reminder-hours" style="display: none;"><?php echo $hidden_hours; ?></span>
					<span class="vbo-widget-reminder-minutes" style="display: none;"><?php echo $hidden_minutes; ?></span>
				<?php
				if (!empty($reminder->idorder) && $reminder->idorder > 0) {
					?>
					<span class="vbo-widget-reminder-idorder" style="display: none;"><?php echo $reminder->idorder; ?></span>
					<div class="vbo-widget-reminders-record-due-booking">
						<a class="label label-info" href="<?php echo sprintf($bookings_base_uri, $reminder->idorder); ?>" target="_blank"><?php echo $reminder->idorder; ?></a>
					</div>
					<?php
				}
				if (!empty($reminder->duedate)) {
					?>
					<div class="vbo-widget-reminders-record-due-datetime">
						<div class="vbo-widget-reminders-record-due-date">
							<span title="<?php echo $reminder->duedate; ?>"><?php echo $diff_data['relative']; ?></span>
						</div>
					<?php
					if ($reminder->usetime) {
						?>
						<div class="vbo-widget-reminders-record-due-time">
							<span><?php echo $diff_data['date_a']->format('H:i'); ?></span>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vbo-widget-reminders-record-edit">
				<button type="button" class="vbo-reminder-record-edit" onclick="vboWidgetRemindersEdit('<?php echo $wrapper; ?>', '<?php echo $reminder->id; ?>');"><?php VikBookingIcons::e('edit'); ?></button>
			</div>
		</div>
			<?php
		}

		// append navigation
		?>
		<div class="vbo-widget-commands vbo-widget-commands-right">
			<div class="vbo-widget-commands-main">
			<?php
			if ($offset > 0) {
				// show backward navigation button
				?>
				<div class="vbo-widget-command-chevron vbo-widget-command-prev">
					<span class="vbo-widget-command-chevron-prev" onclick="vboWidgetRemindersNavigate('<?php echo $wrapper; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
				</div>
				<?php
			}
			if ($has_next_page) {
				// show forward navigation button
				?>
				<div class="vbo-widget-command-chevron vbo-widget-command-next">
					<span class="vbo-widget-command-chevron-next" onclick="vboWidgetRemindersNavigate('<?php echo $wrapper; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
				</div>
			<?php
			}
			?>
			</div>
		</div>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return array(
			'html' 		  => $html_content,
			'tot_records' => count($reminders),
			'next_page'   => (int)$has_next_page,
		);
	}

	/**
	 * Custom method for this widget only.
	 * Creates a new reminder.
	 * 
	 * @return 	array
	 */
	public function saveReminder()
	{
		$title 	   = VikRequest::getString('title', '', 'request');
		$descr 	   = VikRequest::getString('descr', '', 'request');
		$date 	   = VikRequest::getString('date', '', 'request');
		$hours 	   = VikRequest::getInt('hours', 0, 'request');
		$minutes   = VikRequest::getInt('minutes', 0, 'request');
		$bid 	   = VikRequest::getInt('bid', 0, 'request');
		$important = VikRequest::getInt('important', 0, 'request');

		// create reminder object
		$reminder = new stdClass;
		$reminder->title = $title;
		$reminder->descr = $descr;
		$use_time = 0;
		if (!empty($date)) {
			if ($hours >= 0) {
				$use_time = 1;
				$minutes = $minutes >= 0 ? $minutes : 0;
				$due_ts = VikBooking::getDateTimestamp($date, $hours, $minutes);
			} else {
				$due_ts = VikBooking::getDateTimestamp($date);
			}
			$reminder->duedate = date('Y-m-d H:i:s', $due_ts);
		}
		$reminder->usetime = $use_time;
		if (!empty($bid)) {
			$reminder->idorder = $bid;
		}
		$reminder->important = $important;

		if (!VBORemindersHelper::getInstance()->saveReminder($reminder)) {
			VBOHttpDocument::getInstance()->close(400, 'Could not create the reminder');
		}

		// compose the notification payload for the newly created reminder
		$notif_data = VBONotificationScheduler::getInstance()->getReminderDataObject($reminder, $max_future_hours = 12);

		return [
			'status' 	   => true,
			'notification' => $notif_data,
			'debug' => print_r($reminder, true)
		];
	}

	/**
	 * Custom method for this widget only.
	 * Updates an existing reminder.
	 * 
	 * @return 	array
	 */
	public function updateReminder()
	{
		$rid 	   = VikRequest::getInt('rid', 0, 'request');
		$title 	   = VikRequest::getString('title', '', 'request');
		$descr 	   = VikRequest::getString('descr', '', 'request');
		$date 	   = VikRequest::getString('date', '', 'request');
		$hours 	   = VikRequest::getInt('hours', 0, 'request');
		$minutes   = VikRequest::getInt('minutes', 0, 'request');
		$bid 	   = VikRequest::getInt('bid', 0, 'request');
		$important = VikRequest::getInt('important', 0, 'request');

		if (empty($rid)) {
			VBOHttpDocument::getInstance()->close(400, 'Missing reminder ID to update');
		}

		// create reminder object
		$reminder = new stdClass;
		$reminder->id = $rid;
		$reminder->title = $title;
		$reminder->descr = $descr;
		$use_time = 0;
		if (!empty($date)) {
			if ($hours >= 0) {
				$use_time = 1;
				$minutes = $minutes >= 0 ? $minutes : 0;
				$due_ts = VikBooking::getDateTimestamp($date, $hours, $minutes);
			} else {
				$due_ts = VikBooking::getDateTimestamp($date);
			}
			$reminder->duedate = date('Y-m-d H:i:s', $due_ts);
		}
		$reminder->usetime = $use_time;
		$reminder->idorder = !empty($bid) ? $bid : 0;

		if (!VBORemindersHelper::getInstance()->updateReminder($reminder)) {
			VBOHttpDocument::getInstance()->close(400, 'Could not update the reminder');
		}

		// compose the notification paylod for the newly created reminder
		$notif_data = VBONotificationScheduler::getInstance()->getReminderDataObject($reminder, $max_future_hours = 12);

		return [
			'status' 	   => true,
			'notification' => $notif_data,
		];
	}

	/**
	 * Custom method for this widget only.
	 * Toggles the completed status for an existing reminder.
	 * 
	 * @return 	array
	 */
	public function toggleReminderCompleted()
	{
		$rid = VikRequest::getInt('rid', 0, 'request');

		if (empty($rid)) {
			VBOHttpDocument::getInstance()->close(400, 'Missing reminder ID to update');
		}

		$helper = VBORemindersHelper::getInstance();
		$reminder = $helper->getReminder($rid);
		if (!$reminder) {
			VBOHttpDocument::getInstance()->close(400, 'Record not found');
		}

		// toggle completed status
		$reminder->completed = (int) !$reminder->completed;

		if (!$helper->updateReminder($reminder)) {
			VBOHttpDocument::getInstance()->close(400, 'Could not update the reminder');
		}

		$notif_data = null;
		if (!$reminder->completed) {
			// compose the notification paylod for the uncompleted reminder
			$notif_data = VBONotificationScheduler::getInstance()->getReminderDataObject($reminder, $max_future_hours = 12);
		}

		return [
			'status' 	   => $reminder->completed,
			'notification' => $notif_data,
		];
	}

	/**
	 * Custom method for this widget only.
	 * Deletes an existing reminder.
	 * 
	 * @return 	bool
	 */
	public function deleteReminder()
	{
		$rid = VikRequest::getInt('rid', 0, 'request');

		if (empty($rid)) {
			VBOHttpDocument::getInstance()->close(400, 'Missing reminder ID to delete');
		}

		$helper = VBORemindersHelper::getInstance();
		$reminder = $helper->getReminder($rid);
		if (!$reminder) {
			VBOHttpDocument::getInstance()->close(400, 'Record not found');
		}

		if (!$helper->deleteReminder($reminder)) {
			VBOHttpDocument::getInstance()->close(400, 'Could not delete the reminder');
		}

		return true;
	}

	/**
	 * Main method to invoke the widget. Contents will be loaded
	 * through AJAX requests, not via PHP when the page loads.
	 * 
	 * @param 	VBOMultitaskData 	$data 	multitask data object.
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
		$wrapper_id = 'vbo-widget-reminders-' . $wrapper_instance;

		// default filters
		$def_completed = 0;
		$def_expired   = 0;
		$def_duedate   = time();
		$def_duehours  = -1;
		$def_duemins   = -1;

		// check multitask data
		$page_bid 	 = '';
		$auto_action = '';
		$js_modal_id = '';
		if ($data) {
			// access Multitask data
			$page_bid = $data->getBookingID();
			if ($data->isModalRendering()) {
				// get modal JS identifier
				$js_modal_id = $data->getModalJsIdentifier();
			}

			// we allow the page booking ID to be passed as an option as well
			$page_bid = !$page_bid ? $this->options()->fetchBookingId() : $page_bid;

			// check for options
			$auto_action   = $this->getOption('action', '');
			$def_completed = (int)$this->getOption('completed', $def_completed);
			$def_expired   = (int)$this->getOption('expired', $def_expired);

			// attempt to define values for this precise booking
			$booking_info = $page_bid ? VikBooking::getBookingInfoFromID($page_bid) : [];
			if ($booking_info) {
				$def_time_info = [];
				if ($booking_info['checkin'] > $def_duedate) {
					$def_duedate   = $booking_info['checkin'];
					$def_time_info = getdate($def_duedate);
				} elseif ($booking_info['checkout'] > $def_duedate) {
					$def_duedate   = $booking_info['checkout'];
					$def_time_info = getdate($def_duedate);
				}
				if ($def_time_info) {
					$def_duehours = (int)$def_time_info['hours'];
					$def_duemins  = (int)$def_time_info['minutes'];
				}
			}
		} else {
			// load settings
			$def_completed = isset($this->widgetSettings->show_completed) ? (int)$this->widgetSettings->show_completed : $def_completed;
			$def_expired   = isset($this->widgetSettings->show_expired) ? (int)$this->widgetSettings->show_expired : $def_expired;
		}

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>" data-offset="0" data-pagebid="<?php echo $page_bid; ?>" data-length="<?php echo $this->reminders_per_page; ?>">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
					<div class="vbo-admin-widget-head-commands">

						<div class="vbo-reportwidget-commands">
							<div class="vbo-reportwidget-commands-main">
								<div class="vbo-reportwidget-command-add">
									<span class="vbo-widget-command-addnew" onclick="vboWidgetRemindersAdd('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('plus-circle'); ?></span>
								</div>
							</div>
							<div class="vbo-reportwidget-command-dots">
								<span class="vbo-widget-command-togglefilters vbo-widget-reminders-togglefilters" onclick="vboWidgetRemindersToggleFilters('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
							</div>
						</div>
						<div class="vbo-reportwidget-filters">
							<div class="vbo-reportwidget-filter">
								<label for="show_completed<?php echo $wrapper_instance; ?>-on"><?php echo JText::translate('VBO_SHOW_COMPLETED'); ?></label>
								<?php echo $this->vbo_app->printYesNoButtons('show_completed' . $wrapper_instance, JText::translate('VBYES'), JText::translate('VBNO'), $def_completed, 1, 0); ?>
							</div>
							<div class="vbo-reportwidget-filter">
								<label for="show_expired<?php echo $wrapper_instance; ?>-on"><?php echo JText::translate('VBO_SHOW_EXPIRED'); ?></label>
								<?php echo $this->vbo_app->printYesNoButtons('show_expired' . $wrapper_instance, JText::translate('VBYES'), JText::translate('VBNO'), $def_expired, 1, 0); ?>
							</div>
							<div class="vbo-reportwidget-filter vbo-reportwidget-filter-confirm">
								<button type="button" class="btn vbo-config-btn" onclick="vboWidgetRemindersChangeParams('<?php echo $wrapper_id; ?>');"><?php echo JText::translate('VBADMINNOTESUPD'); ?></button>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="vbo-widget-reminders-wrap">
				<div class="vbo-widget-reminders-inner">
					<div class="vbo-widget-reminders-manage" style="display: none;">

						<div class="vbo-widget-reminders-goback">
							<a href="JavaScript: void(0);" onclick="vboWidgetRemindersToggleManage('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('chevron-left'); ?> <?php echo JText::translate('VBBACK'); ?></a>
						</div>

						<div class="vbo-widget-reminders-manage-wrap">
							<div class="vbo-widget-reminders-filter vbo-widget-reminders-filter-title">
								<input type="text" class="vbo-widget-reminders-ftitle" value="" placeholder="<?php echo htmlspecialchars(JText::translate('VBO_REMINDER_TITLE')); ?>" />
							</div>
							<div class="vbo-widget-reminders-filter vbo-widget-reminders-filter-descr">
								<textarea class="vbo-widget-reminders-fdescr" placeholder="<?php echo htmlspecialchars(JText::translate('VBPVIEWOPTIONALSTWO')); ?>" maxlength="2000"></textarea>
							</div>
							<div class="vbo-widget-reminders-filter vbo-widget-reminders-filter-booking vbo-toggle-small" style="<?php echo empty($page_bid) ? 'display: none;' : ''; ?>">
								<span class="vbo-widget-reminders-filter-tobooking">
									<label for="assign_booking<?php echo $wrapper_instance; ?>-on"><span class="label label-info vbo-widget-reminders-filter-booking-id"><?php echo $page_bid; ?></span> <?php echo JText::translate('VBO_ASSIGN_TO_BOOKING'); ?></label>
									<?php echo $this->vbo_app->printYesNoButtons('assign_booking' . $wrapper_instance, JText::translate('VBYES'), JText::translate('VBNO'), (!empty($page_bid) ? 1 : 0), 1, 0); ?>
								</span>
								<span class="vbo-widget-reminders-filter-importance">
									<label for="is_important<?php echo $wrapper_instance; ?>-on"><?php VikBookingIcons::e('exclamation-triangle'); ?> <?php echo JText::translate('VBO_IMPORTANT'); ?></label>
									<?php echo $this->vbo_app->printYesNoButtons('is_important' . $wrapper_instance, JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0); ?>
								</span>
							</div>
							<div class="vbo-widget-reminders-filter-datetime">
								<div class="vbo-widget-reminders-filter vbo-widget-reminders-filter-date">
									<div class="vbo-field-calendar">
										<div class="input-append">
											<input type="text" class="vbo-reminders-dtpicker" value="<?php echo date($this->df, $def_duedate); ?>" placeholder="<?php echo htmlspecialchars(JText::translate('VBO_REMINDER_DUE_DATE')); ?>" />
											<button type="button" class="btn btn-secondary vbo-widget-reminders-caltrigger"><?php VikBookingIcons::e('calendar'); ?></button>
										</div>
									</div>
								</div>
								<div class="vbo-widget-reminders-filter vbo-widget-reminders-filter-time">
									<select class="vbo-widget-reminders-fh">
										<option value=""><?php echo ucfirst(JText::translate('VBCONFIGONETENEIGHT')); ?></option>
									<?php
									for ($i = 0; $i < 24; $i++) { 
										?>
										<option value="<?php echo $i; ?>"<?php echo $i == $def_duehours ? ' selected="selected"' : ''; ?>><?php echo $i < 10 ? "0{$i}" : $i; ?></option>
										<?php
									}
									?>
									</select>
									<select class="vbo-widget-reminders-fm">
										<option value=""><?php echo ucfirst(JText::translate('VBTRKDIFFMINS')); ?></option>
									<?php
									for ($i = 0; $i < 60; $i++) { 
										?>
										<option value="<?php echo $i; ?>"<?php echo $i == $def_duemins ? ' selected="selected"' : ''; ?>><?php echo $i < 10 ? "0{$i}" : $i; ?></option>
										<?php
									}
									?>
									</select>
								</div>
							</div>
							<div class="vbo-widget-reminders-filter vbo-widget-reminders-filter-save">
								<input type="hidden" class="vbo-widget-reminders-rid" value="" />
								<button type="button" class="btn btn-success vbo-widget-reminder-confirm" onclick="vboWidgetRemindersSaveNew('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('bell'); ?> <span><?php echo JText::translate('VBCONFIGCLOSINGDATEADD'); ?></span></button>
								<button type="button" class="btn btn-danger vbo-widget-reminder-delete" onclick="vboWidgetRemindersDelete('<?php echo $wrapper_id; ?>');" style="display: none;"><?php VikBookingIcons::e('trash'); ?> <?php echo JText::translate('VBELIMINA'); ?></button>
							</div>
						</div>

					</div>
					<div class="vbo-widget-reminders-list">
					<?php
					for ($i = 0; $i < floor($this->reminders_per_page / 2); $i++) {
						?>
						<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">
							<div class="vbo-dashboard-guest-activity-avatar">
								<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>
							</div>
							<div class="vbo-dashboard-guest-activity-content">
								<div class="vbo-dashboard-guest-activity-content-head">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>
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
			/**
			 * Print the JS code only once for all instances of this widget.
			 * The real rendering is made through AJAX, not when the page loads.
			 */
			?>
		<script type="text/javascript">

			/**
			 * Display the loading skeletons.
			 */
			function vboWidgetRemindersSkeletons(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				widget_instance.find('.vbo-widget-reminders-list').html('');
				for (var i = 0; i < <?php echo floor($this->reminders_per_page / 2); ?>; i++) {
					var skeleton = '';
					skeleton += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">';
					skeleton += '	<div class="vbo-dashboard-guest-activity-avatar">';
					skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
					skeleton += '	</div>';
					skeleton += '	<div class="vbo-dashboard-guest-activity-content">';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-head">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
					skeleton += '		</div>';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-info-msg">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
					skeleton += '		</div>';
					skeleton += '	</div>';
					skeleton += '</div>';
					// append skeleton
					jQuery(skeleton).appendTo(widget_instance.find('.vbo-widget-reminders-list'));
				}
			}

			/**
			 * Toggles the layout to add or edit a reminder.
			 */
			function vboWidgetRemindersToggleManage(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var mng_cont = widget_instance.find('.vbo-widget-reminders-manage');
				if (mng_cont.is(':visible')) {
					mng_cont.hide();
					widget_instance.find('.vbo-widget-reminders-list').show();
				} else {
					widget_instance.find('.vbo-widget-reminders-list').hide();
					mng_cont.show();
				}
			}

			/**
			 * Toggle filters.
			 */
			function vboWidgetRemindersToggleFilters(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				widget_instance.find('.vbo-reportwidget-filters').toggle();
			}

			/**
			 * Reload records with new params.
			 */
			function vboWidgetRemindersChangeParams(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// hide filters when making a new request
				widget_instance.find('.vbo-reportwidget-filters').hide();

				// reset offset to 0
				widget_instance.attr('data-offset', 0);

				// show loading skeletons
				vboWidgetRemindersSkeletons(wrapper);

				// reload the first page
				vboWidgetRemindersLoad(wrapper);
			}

			/**
			 * Prepare the layout to add a new reminder.
			 */
			function vboWidgetRemindersAdd(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// always reset the reminder id in the hidden input and hide delete
				widget_instance.find('.vbo-widget-reminders-rid').val('');
				widget_instance.find('.vbo-widget-reminder-delete').hide();
				widget_instance.find('.vbo-widget-reminder-confirm span').text(Joomla.JText._('VBCONFIGCLOSINGDATEADD'));

				// check if we need to hide the assign-to-booking option
				var page_bid = widget_instance.attr('data-pagebid');
				if (page_bid.length && page_bid > 0) {
					// show assign-to-booking option and important reminder
					widget_instance.find('.vbo-widget-reminders-filter-booking-id').text(page_bid);
					widget_instance.find('input[name^="assign_booking"]').prop('checked', true);
					widget_instance.find('input[name^="is_important"]').prop('checked', false);
					widget_instance.find('.vbo-widget-reminders-filter-booking').show();
				} else {
					// hide assign-to-booking option and important reminder
					widget_instance.find('input[name^="assign_booking"]').prop('checked', false);
					widget_instance.find('input[name^="is_important"]').prop('checked', false);
					widget_instance.find('.vbo-widget-reminders-filter-booking').hide();
				}

				// reset the rest of the fields in case of previous editing
				if (widget_instance.find('.vbo-widget-reminders-ftitle').val() || widget_instance.find('.vbo-widget-reminders-fdescr').val()) {
					widget_instance.find('.vbo-widget-reminders-ftitle').val('');
					widget_instance.find('.vbo-widget-reminders-fdescr').val('');
					widget_instance.find('.vbo-reminders-dtpicker').val('');
					widget_instance.find('.vbo-widget-reminders-fh').val('');
					widget_instance.find('.vbo-widget-reminders-fm').val('');
				}

				// toggle edit mode
				vboWidgetRemindersToggleManage(wrapper);
			}

			/**
			 * Prepare the reminder edit mode by populating data.
			 */
			function vboWidgetRemindersEdit(wrapper, rid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				var reminder_instance = widget_instance.find('[data-reminderid="' + rid + '"]');
				if (!reminder_instance.length) {
					return false;
				}

				// populate fields for edit mode
				widget_instance.find('.vbo-widget-reminder-confirm span').text(Joomla.JText._('VBADMINNOTESUPD'));
				widget_instance.find('.vbo-widget-reminder-delete').show();
				widget_instance.find('.vbo-widget-reminders-rid').val(rid);
				widget_instance.find('.vbo-widget-reminders-ftitle').val(reminder_instance.find('.vbo-widget-reminder-title').text());
				widget_instance.find('.vbo-widget-reminders-fdescr').val(reminder_instance.find('.vbo-widget-reminder-descr').text());
				widget_instance.find('.vbo-reminders-dtpicker').val(reminder_instance.find('.vbo-widget-reminder-date').text());
				widget_instance.find('.vbo-widget-reminders-fh').val(reminder_instance.find('.vbo-widget-reminder-hours').text());
				widget_instance.find('.vbo-widget-reminders-fm').val(reminder_instance.find('.vbo-widget-reminder-minutes').text());
				// handle booking ID assignment
				var booking_assigned = null;
				var booking_container = reminder_instance.find('.vbo-widget-reminder-idorder');
				if (booking_container.length) {
					booking_assigned = booking_container.text();
				}
				if (booking_assigned) {
					// show assign-to-booking option
					widget_instance.find('.vbo-widget-reminders-filter-booking-id').text(booking_assigned);
					widget_instance.find('input[name^="assign_booking"]').prop('checked', true);
					widget_instance.find('.vbo-widget-reminders-filter-booking').show();
				} else {
					// hide assign-to-booking option
					widget_instance.find('input[name^="assign_booking"]').prop('checked', false);
					widget_instance.find('.vbo-widget-reminders-filter-booking').hide();
				}

				// handle important reminder flag
				if (booking_assigned && reminder_instance.find('.vbo-widget-reminders-record-important').length) {
					widget_instance.find('input[name^="is_important"]').prop('checked', true);
				} else {
					widget_instance.find('input[name^="is_important"]').prop('checked', false);
				}

				// enter edit mode
				vboWidgetRemindersToggleManage(wrapper);
			}

			/**
			 * Toggle reminder completed status.
			 */
			function vboWidgetRemindersComplete(wrapper, rid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				var reminder_instance = widget_instance.find('[data-reminderid="' + rid + '"]');
				if (!reminder_instance.length) {
					return false;
				}

				// the widget method to call
				var call_method = 'toggleReminderCompleted';

				// make a request to update the reminder
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						rid: rid,
						wrapper: wrapper,
						tmpl: "component"
					},
					function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected or invalid JSON response', obj_res);
								return false;
							}

							if (obj_res[call_method]['status'] > 0) {
								// new status is completed
								reminder_instance
									.removeClass('vbo-reminder-nok')
									.addClass('vbo-reminder-ok')
									.find('.vbo-reminder-status-dot').html('<?php VikBookingIcons::e('dot-circle'); ?>');
								// check current params
								var show_completed = widget_instance.find('input[name^="show_completed"]').prop('checked');
								if (!show_completed) {
									reminder_instance.fadeOut();
								}
								// attempt to unset any scheduled notification for this reminder
								VBOCore.updateNotification({
									id: rid,
									type: "reminder"
								}, 0);
							} else {
								// new status is un-completed
								reminder_instance
									.removeClass('vbo-reminder-ok')
									.addClass('vbo-reminder-nok')
									.find('.vbo-reminder-status-dot').html('<?php VikBookingIcons::e('far fa-circle'); ?>');
								if (obj_res[call_method]['notification'] != null) {
									// attempt to schedule a notification for this reminder
									VBOCore.enqueueNotifications(obj_res[call_method]['notification']);
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					function(error) {
						// log the error and display the message
						console.error(error);
						alert(error.responseText);
					}
				);
			}

			/**
			 * Delete one reminder record.
			 */
			function vboWidgetRemindersDelete(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var rid = widget_instance.find('.vbo-widget-reminders-rid').val();
				if (!rid || !rid.length) {
					return false;
				}

				if (!confirm(Joomla.JText._('VBDELCONFIRM'))) {
					return false;
				}

				// disable delete button
				var del_btn = widget_instance.find('button.vbo-widget-reminder-delete');
				del_btn.prop('disabled', true);

				// the widget method to call
				var call_method = 'deleteReminder';

				// make a request to update the reminder
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						rid: rid,
						wrapper: wrapper,
						tmpl: "component"
					},
					function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method) || !obj_res[call_method]) {
								console.error('Unexpected or invalid JSON response', obj_res);
								return false;
							}

							// turn flag on
							vbo_reminders_changed = true;

							// toggle edit mode
							vboWidgetRemindersToggleManage(wrapper);

							// enable delete button
							del_btn.prop('disabled', false);

							// reset offset to 0
							widget_instance.attr('data-offset', 0);

							// show loading skeletons
							vboWidgetRemindersSkeletons(wrapper);

							// reload the first page
							vboWidgetRemindersLoad(wrapper);

							// attempt to unset any scheduled notification for this reminder
							VBOCore.updateNotification({
								id: rid,
								type: "reminder"
							}, 0);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					function(error) {
						// log the error and display the message
						console.error(error);
						alert(error.responseText);
						// enable delete button
						del_btn.prop('disabled', false);
					}
				);
			}

			/**
			 * Perform the request to load the reminders.
			 */
			function vboWidgetRemindersLoad(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get vars for making the request
				var current_offset  = parseInt(widget_instance.attr('data-offset'));
				var length_per_page = parseInt(widget_instance.attr('data-length'));
				var show_completed = widget_instance.find('input[name^="show_completed"]').prop('checked');
				var show_expired = widget_instance.find('input[name^="show_expired"]').prop('checked');
				show_completed = show_completed ? 1 : 0;
				show_expired = show_expired ? 1 : 0;
				var page_bid = widget_instance.attr('data-pagebid');

				// the widget method to call
				var call_method = 'loadReminders';

				// make a request to load the reminder records
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						offset: current_offset,
						length: length_per_page,
						completed: show_completed,
						expired: show_expired,
						bid: page_bid,
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

							// replace HTML with month-day reservations
							widget_instance.find('.vbo-widget-reminders-list').html(obj_res[call_method]['html']);

							// check results
							var tot_records = obj_res[call_method]['tot_records'] || 0;
							if (!isNaN(tot_records) && parseInt(tot_records) < 1) {
								// no results can indicate the offset is invalid or too high
								if (!isNaN(current_offset) && current_offset > 0) {
									// reset offset to 0
									widget_instance.attr('data-offset', 0);
									// show loading skeletons
									vboWidgetRemindersSkeletons(wrapper);
									// reload the first page
									vboWidgetRemindersLoad(wrapper);
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-reminders-list').find('.vbo-dashboard-guest-activity-skeleton').remove();
						console.error(error);
					}
				);
			}

			/**
			 * Save a new reminder or update an existing one.
			 */
			function vboWidgetRemindersSaveNew(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// either save or update
				var mode = 'saveReminder';

				// collect new reminder details
				var r_title = widget_instance.find('.vbo-widget-reminders-ftitle').val();
				var r_descr = widget_instance.find('.vbo-widget-reminders-fdescr').val();
				var r_date  = widget_instance.find('.vbo-reminders-dtpicker').val();
				var r_hours = widget_instance.find('.vbo-widget-reminders-fh').val();
				var r_mins  = widget_instance.find('.vbo-widget-reminders-fm').val();
				// adjust time
				if (!r_hours.length) {
					r_hours = -1;
					r_mins  = 0;
				}
				// check if we are editing an existing reminder id
				var r_id = widget_instance.find('.vbo-widget-reminders-rid').val();
				if (r_id && r_id.length) {
					mode = 'updateReminder';
				} else {
					r_id = 0;
				}

				if ((!r_title.length && !r_descr.length) || !r_date.length) {
					alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));
					return false;
				}

				// check if we are saving/updating a reminder for a booking ID
				var r_bid = null;
				var assign_to_bid = widget_instance.find('input[name^="assign_booking"]').prop('checked');
				if (assign_to_bid) {
					// validate the booking id depending on save/update mode
					if (mode == 'saveReminder') {
						// the booking id must have been set from the current page
						var validate_bid = widget_instance.attr('data-pagebid');
						if (validate_bid && validate_bid.length) {
							// valid
							r_bid = validate_bid;
						}
					} else {
						// the booking id must be part of a hidden field
						var reminder_instance = widget_instance.find('[data-reminderid="' + r_id + '"]');
						if (reminder_instance.length) {
							var validate_bid = reminder_instance.find('.vbo-widget-reminder-idorder').text();
							if (validate_bid && validate_bid.length) {
								// valid
								r_bid = validate_bid;
							}
						}
					}
				}

				// check importance
				var is_important = 0;
				if (r_bid && widget_instance.find('input[name^="is_important"]').prop('checked')) {
					is_important = 1;
				}

				// disable save button
				var save_btn = widget_instance.find('.vbo-widget-reminders-filter-save button');
				save_btn.prop('disabled', true);

				// the widget method to call
				var call_method = mode;

				// make a request to save a new reminder or to update it
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						rid: r_id,
						bid: r_bid,
						title: r_title,
						descr: r_descr,
						date: r_date,
						hours: r_hours,
						minutes: r_mins,
						important: is_important,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method) || !obj_res[call_method]['status']) {
								console.error('Unexpected or invalid JSON response', obj_res);
								return false;
							}

							// turn flag on & store booking involved, if any
							vbo_reminders_changed = true;
							vbo_reminders_changed_bid = r_bid;

							// toggle edit mode
							vboWidgetRemindersToggleManage(wrapper);

							// enable save button
							save_btn.prop('disabled', false);

							// reset offset to 0
							widget_instance.attr('data-offset', 0);

							// show loading skeletons
							vboWidgetRemindersSkeletons(wrapper);

							// reload the first page
							vboWidgetRemindersLoad(wrapper);

							// handle browser notifications
							if (call_method == 'saveReminder') {
								if (obj_res[call_method]['notification'] != null) {
									// attempt to schedule a notification for the new reminder
									VBOCore.enqueueNotifications(obj_res[call_method]['notification']);
								}
							} else {
								if (obj_res[call_method]['notification'] != null) {
									// attempt to update the notification scheduling time for this reminder
									let updated = VBOCore.updateNotification({
										id: r_id,
										type: "reminder"
									}, obj_res[call_method]['notification']['dtime']);
									if (updated !== true) {
										// attempt to schedule this anticipated reminder as a new notification
										VBOCore.enqueueNotifications(obj_res[call_method]['notification']);
									}
								} else {
									// the notification may have been set to a far-ahead date, delete it
									VBOCore.updateNotification({
										id: r_id,
										type: "reminder"
									}, 0);
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// log the error and display the message
						console.error(error);
						alert(error.responseText);
						// enable save button
						save_btn.prop('disabled', false);
					}
				);
			}

			/**
			 * Navigate between the various pages of the reminders.
			 */
			function vboWidgetRemindersNavigate(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetRemindersSkeletons(wrapper);

				// current offset
				var current_offset = parseInt(widget_instance.attr('data-offset'));

				// records per page
				var steps = parseInt(widget_instance.attr('data-length'));

				// check direction and update offsets for nav
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
				vboWidgetRemindersLoad(wrapper);
			}
			
		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			// keep track of any changes related to reminders
			var vbo_reminders_changed = false;

			// keep track of changes to reminders related to a bookind ID
			var vbo_reminders_changed_bid = null;

			jQuery(function() {

				// render datepicker calendar
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-reminders-dtpicker').datepicker({
					minDate: "0",
					maxDate: "+5y",
					yearRange: "<?php echo date('Y'); ?>:<?php echo (date('Y') + 5); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "<?php echo $this->getDateFormat('jui'); ?>",
					defaultDate: "<?php echo date($this->df, $def_duedate); ?>"
				});

				// triggering for datepicker calendar icons
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-reminders-caltrigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

				// when document is ready, load reminders for this widget's instance
				vboWidgetRemindersLoad('<?php echo $wrapper_id; ?>');

				// subscribe to the multitask-panel-close event to emit the event for the reminders changed
				document.addEventListener(VBOCore.multitask_close_event, function() {
					if (vbo_reminders_changed) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_reminders_changed', {
							bid: vbo_reminders_changed_bid
						});
					}
				});

			<?php
			if ($js_modal_id) {
				// widget can be dismissed through the modal
				?>
				// subscribe to the modal-dismissed event to emit the event for the reminders changed
				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_modal_id; ?>', function() {
					if (vbo_reminders_changed) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_reminders_changed', {
							bid: vbo_reminders_changed_bid
						});
					}
				});
				<?php
			}

			if ($auto_action == 'add_new') {
				// injected optinos require to add a new reminder
				?>
				setTimeout(() => {
					vboWidgetRemindersAdd('<?php echo $wrapper_id; ?>');
				}, 300);
				<?php
			}
			?>

			});
			
		</script>

		<?php
	}

	/**
	 * Checks if any reminders should be automatically scheduled due to
	 * important events related to reservations downloaded through VCM.
	 * 
	 * @return 	int 	number of reminders that were automatically scheduled.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 */
	protected function scheduleChannelManagerReminders()
	{
		$scheduled = 0;

		if (!method_exists('VikChannelManager', 'hostToGuestReviewSupported')) {
			// prevent server errors
			return $scheduled;
		}

		// get the reminders helper object
		$helper = VBORemindersHelper::getInstance();

		// get a list of Airbnb reservations with a proper checkout date
		$airbnb_checkouts = $helper->gatherAirbnbReservationsCheckedOut();

		// default due-date for the newly added reminders
		$def_due_date = date('Y-m-d H:i:s', strtotime("+1 minute"));

		// default reminder payload
		$payload = [
			'airbnb_host_guest_review' => 1,
		];

		// parse all eligible reservations
		foreach ($airbnb_checkouts as $airbnb_res) {
			// make sure this airbnb reservation requires a host-to-guest review
			if (!$helper->bookingHasReminder($airbnb_res['id'], $payload) && VikChannelManager::hostToGuestReviewSupported($airbnb_res)) {
				// build and schedule an automatic reminder
				$reminder = new stdClass;
				$reminder->title 	 = JText::translate('VBO_REVIEW_YOUR_GUEST');
				$reminder->descr 	 = 'Airbnb - ' . JText::translate('VBDASHBOOKINGID') . ' ' . $airbnb_res['id'];
				$reminder->duedate 	 = $def_due_date;
				$reminder->usetime 	 = 1;
				$reminder->idorder 	 = $airbnb_res['id'];
				$reminder->payload 	 = $payload;
				$reminder->important = 1;

				if ($helper->saveReminder($reminder)) {
					// increase counter
					$scheduled++;
				}
			}
		}

		return $scheduled;
	}
}
