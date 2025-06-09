<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "guest messages".
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingAdminWidgetGuestMessages extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Number of messages per page. Should be an even number.
	 * 
	 * @var 	int
	 */
	protected $messages_per_page = 6;

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
	 * The distance threshold in pixels between the current scroll
	 * position and the end of the list for triggering the loading
	 * of a next page within an infinite scroll mechanism.
	 *
	 * @var 	int
	 * 
	 * @since 	1.17.6 (J) - 1.7.6 (WP)
	 */
	protected $px_distance_threshold = 140;

	/**
	 * Number of minimum messages per page when using the inbox-style (modal only).
	 * 
	 * @var 	int
	 * 
	 * @since 	1.17.6 (J) - 1.7.6 (WP)
	 */
	protected $inbox_messages_per_page = 12;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_GUESTMESSAGES_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_GUESTMESSAGES_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('comment-dots') . '"></i>';
		$this->widgetStyleName = 'light-orange';

		// load widget's settings
		$this->widgetSettings = $this->loadSettings();
		if (!is_object($this->widgetSettings)) {
			$this->widgetSettings = new stdClass;
		}

		// today Y-m-d date
		$this->today_ymd = date('Y-m-d');

		// the path to the VCM library
		$this->vcm_lib_path = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php';

		// whether VCM is available
		if (is_file($this->vcm_lib_path)) {
			if (!class_exists('VikChannelManager') || !method_exists('VikChannelManager', 'getLatestFromGuests')) {
				// VCM is outdated
				$this->vcm_exists = false;
			}

			// attempt to require the chat handler
			try {
				VikBooking::getVcmChatInstance($oid = 0, $channel = null);
			} catch (Exception $e) {
				// do nothing
			}

			// make sure VCM is up to date for this widget
			if (!class_exists('VCMChatHandler') || !method_exists('VCMChatHandler', 'loadChatAssets')) {
				// VCM is outdated (>= 1.8.11 required)
				$this->vcm_exists = false;
			}
		} else {
			$this->vcm_exists = false;
		}

		// avoid queries on certain pages, as VCM may not have been activated yet
		if (VBOPlatformDetection::isWordPress() && $this->vcm_exists) {
			global $pagenow;
			if (isset($pagenow) && in_array($pagenow, ['update.php', 'plugins.php', 'plugin-install.php'])) {
				$this->vcm_exists = false;
			}
		}
	}

	/**
	 * Preload the necessary CSS/JS assets from VCM.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		if ($this->vcm_exists) {
			// load chat assets from VCM
			VCMChatHandler::loadChatAssets();

			// datepicker calendar
			$this->vbo_app->loadDatePicker();

			// additional language defs
			JText::script('VBO_NO_REPLY_NEEDED');
			JText::script('VBO_WANT_PROCEED');
			JText::script('VBOSIGNATURECLEAR');
			JText::script('VBODASHSEARCHKEYS');
		}
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.17.6 (J) - 1.7.6 (WP)
	 */
	public function getWidgetDetails()
	{
		// get common widget details from parent abstract class
		$details = parent::getWidgetDetails();

		// append the modal rendering information
		$details['modal'] = [
			'add_class' => 'vbo-modal-large',
		];

		return $details;
	}

	/**
	 * Custom method for this widget only to load the latest guest messages.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * an associative array is returned thanks to the request value "return":1.
	 * 
	 * It's the actual rendering of the widget which also allows navigation.
	 */
	public function loadMessages()
	{
		$input = JFactory::getApplication()->input;

		$bid_convo = $input->getString('bid_convo', '');
		$filters   = $input->get('filters', [], 'array');
		$offset    = $input->getInt('offset', 0);
		$length    = $input->getInt('length', $this->messages_per_page);
		$wrapper   = $input->getString('wrapper', '');

		if (!$this->vcm_exists) {
			VBOHttpDocument::getInstance()->close(500, 'Vik Channel Manager is either not available or outdated');
		}

		// check messages custom limit per page
		if ($length > 0 && $length != $this->messages_per_page) {
			// update widget settings
			$this->widgetSettings->limpage = $length;
			$this->updateSettings(json_encode($this->widgetSettings));
		}

		// build search filters
		$search_filters = [
			'guest_name' => $filters['guest_name'] ?? '',
			'message'    => $filters['message'] ?? '',
			'sender'     => $filters['sender'] ?? '',
			'fromdt'     => $filters['fromdt'] ?? '',
			'todt'       => $filters['todt'] ?? '',
			'unread'     => (int) ($filters['unread'] ?? 0),
			'ai_sort'    => (int) ($filters['ai_sort'] ?? 0),
		];

		// filter out empty search filter values
		$search_filters = array_filter($search_filters);

		if (!empty($search_filters['fromdt'])) {
			// convert the date string from local format to military
			$search_filters['fromdt'] = date('Y-m-d H:i:s', VikBooking::getDateTimestamp($search_filters['fromdt'], 0, 0, 0));
			// convert date from local timezone to UTC
			$search_filters['fromdt'] = JFactory::getDate($search_filters['fromdt'], date_default_timezone_get())->format('Y-m-d H:i:s');
		}

		if (!empty($search_filters['todt'])) {
			// convert the date string from local format to military
			$search_filters['todt'] = date('Y-m-d H:i:s', VikBooking::getDateTimestamp($search_filters['todt'], 23, 59, 59));
			// convert date from local timezone to UTC
			$search_filters['todt'] = JFactory::getDate($search_filters['todt'], date_default_timezone_get())->format('Y-m-d H:i:s');
		}

		// initiate the chat messaging object
		$chat_messaging = class_exists('VCMChatMessaging') ? VCMChatMessaging::getInstance() : null;

		// last error description
		$latest_error = '';

		// load latest messages
		$latest_messages = [];

		try {
			/**
			 * Search filters require an updated VCM version.
			 * 
			 * @since 		1.16.9 (J) - 1.6.9 (WP)
			 * @requires 	VCM >= 1.8.27 (1.9.5 "unread", "ai_sort")
			 */
			if ($search_filters && $chat_messaging && method_exists($chat_messaging, 'searchMessages')) {
				// search for specific messages with the specified search filters
				$latest_messages = $chat_messaging->searchMessages($search_filters, $offset, $length);
			} else {
				// regular loading of the latest guest messages with no search filters
				$latest_messages = VikChannelManager::getLatestFromGuests(['guest_messages'], $offset, $length);
			}
		} catch (Exception $e) {
			// do nothing, but populate the error description
			$latest_error = $e->getMessage();
		}

		// the multitask data and notifications can request a specific conversation to be opened
		$bubble_convo = null;
		if ($bid_convo) {
			// make sure the requested booking ID was fetched from the most recent guest messages
			foreach ($latest_messages as $gmessage) {
				if ($bid_convo == $gmessage->idorder) {
					// specific conversation to bubble found
					$bubble_convo = $bid_convo;
					break;
				}
				if ($bid_convo == $gmessage->idorderota && strcasecmp((string)$gmessage->channel, 'vikbooking')) {
					// specific OTA conversation to bubble found
					$bubble_convo = $bid_convo;
					break;
				}
			}
			if (!$bubble_convo && $chat_messaging) {
				// updated VCM versions will allow us to fetch one conversation by booking ID
				$booking_messages = $chat_messaging->loadBookingGuestThreads($bid_convo);
				if ($booking_messages) {
					// append the requested conversation so that it will bubble
					$latest_messages = array_merge($latest_messages, $booking_messages);
					// turn flag on
					$bubble_convo = $bid_convo;
				}
			}
		}

		// current year Y and timestamp
		$current_y  = date('Y');
		$current_ts = time();

		// start output buffering
		ob_start();

		if ($latest_error) {
			?>
			<p class="err"><?php echo $latest_error; ?></p>
			<?php
		}

		if (!$latest_messages) {
			?>
			<p class="info" data-no-messages="1" style="<?php echo $offset > 0 ? 'text-align: center;' : ''; ?>"><?php echo $offset > 0 ? JText::translate('VBO_NO_MORE_MESSAGES') : JText::translate('VBO_NO_RECORDS_FOUND'); ?></p>
			<?php
		}

		// count total messages
		$tot_messages = count($latest_messages);

		foreach ($latest_messages as $ind => $gmessage) {
			$gmessage_content = $gmessage->content;
			if (empty($gmessage_content)) {
				$gmessage_content = '.....';
			} elseif (strlen($gmessage_content) > 90) {
				if (function_exists('mb_substr')) {
					$gmessage_content = mb_substr($gmessage_content, 0, 90, 'UTF-8');
				} else {
					$gmessage_content = substr($gmessage_content, 0, 90);
				}
				$gmessage_content .= '...';
			}

			// build extra classes for main element
			$wrap_classes = [];
			if ($ind === 0) {
				$wrap_classes[] = 'vbo-w-guestmessages-message-first';
			} elseif ($ind == ($tot_messages - 1)) {
				$wrap_classes[] = 'vbo-w-guestmessages-message-last';
			}
			if (empty($gmessage->read_dt) && !strcasecmp($gmessage->sender_type, 'guest')) {
				$wrap_classes[] = 'vbo-w-guestmessages-message-new';
			}

			?>
			<div
				class="vbo-dashboard-guest-activity vbo-w-guestmessages-message<?php echo $wrap_classes ? ' ' . implode(' ', $wrap_classes) : ''; ?>"
				data-idorder="<?php echo $gmessage->idorder; ?>"
				data-idthread="<?php echo !empty($gmessage->id_thread) ? $gmessage->id_thread : ''; ?>"
				data-idmessage="<?php echo !empty($gmessage->id_message) ? $gmessage->id_message : ''; ?>"
				data-noreply-needed="<?php echo $gmessage->no_reply_needed ?: 0; ?>"
				onclick="vboWidgetGuestMessagesOpenChat('<?php echo $gmessage->idorder; ?>');"
			>
				<div class="vbo-dashboard-guest-activity-avatar">
				<?php
				if (!empty($gmessage->guest_avatar)) {
					// highest priority goes to the profile picture, not always available
					?>
					<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo $gmessage->guest_avatar; ?>" />
					<?php
				} elseif (!empty($gmessage->pic)) {
					// customer profile picture is not the same as the photo avatar
					?>
					<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo strpos($gmessage->pic, 'http') === 0 ? $gmessage->pic : VBO_SITE_URI . 'resources/uploads/' . $gmessage->pic; ?>" />
					<?php
				} elseif (!empty($gmessage->channel_logo)) {
					// channel logo goes as second option
					?>
					<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo $gmessage->channel_logo; ?>" />
					<?php
				} else {
					// we use an icon as fallback
					VikBookingIcons::e('user', 'vbo-dashboard-guest-activity-avatar-icon');
				}

				// check for AI priority enum
				if (!empty($gmessage->ai_priority)) {
					// display AI-calculated priority icon and apposite class (expected "high", "medium" or "low")
					$priority_icon  = 'flag';
					$priority_class = preg_replace("/[^a-z]/", '', strtolower((string) $gmessage->ai_priority));
					$priority_value = ucwords((string) $gmessage->ai_priority);
					if (!strcasecmp(trim((string) $gmessage->ai_priority), 'high')) {
						$priority_value = JText::translate('VBO_PRIORITY_HIGH');
					} elseif (!strcasecmp(trim((string) $gmessage->ai_priority), 'medium')) {
						$priority_value = JText::translate('VBO_PRIORITY_MEDIUM');
					} elseif (!strcasecmp(trim((string) $gmessage->ai_priority), 'low')) {
						$priority_value = JText::translate('VBO_PRIORITY_LOW');
					}
					?>
					<div class="vbo-w-guestmessages-message-aipriority">
						<span class="vbo-w-guestmessages-message-aipriority-icn <?php echo $priority_class; ?> vbo-tooltip vbo-tooltip-right" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $priority_value); ?>"><?php VikBookingIcons::e($priority_icon, $priority_class); ?></span>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-dashboard-guest-activity-content">
					<div class="vbo-dashboard-guest-activity-content-head">
						<div class="vbo-dashboard-guest-activity-content-info-details">
							<h4 class="vbo-w-guestmessages-message-gtitle"><span><?php
							if (!$gmessage->first_name && !$gmessage->last_name) {
								echo JText::translate('VBO_GUEST');
							} else {
								echo $gmessage->first_name . (!empty($gmessage->last_name) ? ' ' . $gmessage->last_name : '');
							}
							?></span><?php
							if (empty($gmessage->read_dt) && !strcasecmp($gmessage->sender_type, 'guest')) {
								// print also an icon to inform that the message was not read
								echo ' ';
								VikBookingIcons::e('envelope', 'message-new');
							} elseif (($gmessage->replied ?? 1) == 0 && !strcasecmp($gmessage->sender_type, 'guest')) {
								/**
								 * Display a label to show that the message was not replied.
								 * 
								 * @since 		1.16.9 (J) - 1.6.9 (WP)
								 * 
								 * @requires 	VCM >= 1.8.27
								 */
								echo ' <span class="label label-small message-unreplied">';
								VikBookingIcons::e('comments', 'message-reply');
								echo ' ' . JText::translate('VBO_REPLY') . '</span>';
							}

							/**
							 * Display the AI message category, if available.
							 * 
							 * @since 		1.17.3 (J) - 1.7.3 (WP)
							 * 
							 * @requires 	VCM >= 1.9.5
							 */
							if (!empty($gmessage->ai_category)) {
								echo ' <span class="label label-small message-ai-category">';
								VikBookingIcons::e('tag');
								echo ' ' . $gmessage->ai_category . '</span>';
							}
							?></h4>
							<div class="vbo-dashboard-guest-activity-content-info-icon">
							<?php
							if (!empty($gmessage->b_status)) {
								switch ($gmessage->b_status) {
									case 'standby':
										$badge_class = 'badge-warning';
										$badge_text  = JText::translate('VBSTANDBY');
										break;
									case 'cancelled':
										$badge_class = 'badge-danger';
										$badge_text  = JText::translate('VBCANCELLED');
										break;
									default:
										$badge_class = 'badge-success';
										$badge_text  = JText::translate('VBCONFIRMED');
										if (!empty($gmessage->b_checkout) && $gmessage->b_checkout < $current_ts) {
											$badge_text  = JText::translate('VBOCHECKEDSTATUSOUT');
										}
										break;
								}
								?>
								<span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
								<?php
							}
							if (!empty($gmessage->b_checkin)) {
								$stay_info_in  = getdate($gmessage->b_checkin);
								$stay_info_out = getdate($gmessage->b_checkout);
								$str_checkin = date('d', $gmessage->b_checkin);
								$str_checkin .= $stay_info_in['mon'] != $stay_info_out['mon'] ? ' ' . VikBooking::sayMonth($stay_info_in['mon'], $short = true) : '';
								$str_checkout = date('d', $gmessage->b_checkout) . ' ' . VikBooking::sayMonth($stay_info_out['mon'], $short = true);
								if ($stay_info_in['year'] != $stay_info_out['year'] || $stay_info_in['year'] != $current_y || $stay_info_out['year'] != $current_y) {
									$str_checkout .= ' ' . $stay_info_in['year'];
								}
								?>
								<span class="vbo-w-guestmessages-message-staydates">
									<span class="vbo-w-guestmessages-message-staydates-in"><?php echo $str_checkin; ?></span>
									<span class="vbo-w-guestmessages-message-staydates-sep">-</span>
									<span class="vbo-w-guestmessages-message-staydates-out"><?php echo $str_checkout; ?></span>
								</span>
								<?php
							}
							?>
							</div>
						</div>
						<div class="vbo-dashboard-guest-activity-content-info-date">
							<span><?php echo JHtml::fetch('date', $gmessage->last_updated, 'H:i'); ?></span>
						<?php
						if (JHtml::fetch('date', $gmessage->last_updated, 'Y-m-d') != $this->today_ymd) {
							// format and print the date
							?>
							<span><?php echo JHtml::fetch('date', $gmessage->last_updated, str_replace('/', $this->datesep, $this->df)); ?></span>
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
						<p><?php echo $gmessage_content; ?></p>
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
					<span class="vbo-guestactivitywidget-prev" onclick="vboWidgetGuestMessagesNavigate('<?php echo $wrapper; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
				</div>
				<?php
			}
			if ($latest_messages) {
				// count current page number
				$page_number = 1;
				if ($offset > 0) {
					$page_number = floor($offset / $length) + 1;
					$page_number = $page_number > 0 ? $page_number : 1;
				}
				?>
				<div class="vbo-guestactivitywidget-command-chevron vbo-guestactivitywidget-command-page">
					<span class="vbo-guestactivitywidget-page"><?php echo JText::sprintf('VBO_PAGE_NUMBER', $page_number); ?></span>
				</div>
				<div class="vbo-guestactivitywidget-command-chevron vbo-guestactivitywidget-command-next">
					<span class="vbo-guestactivitywidget-next" onclick="vboWidgetGuestMessagesNavigate('<?php echo $wrapper; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
				</div>
				<?php
			}
			?>
			</div>
		</div>

		<script type="text/javascript">
			setTimeout(() => {
			<?php
			// check if there were actually no filters applied
			if (!$search_filters || (count($search_filters) === 1 && ($search_filters['ai_sort'] ?? 0))) {
				?>
				jQuery('#<?php echo $wrapper; ?>-filters').hide();
				<?php
			}

			// check if results were sorted through AI
			if ($search_filters['ai_sort'] ?? 0) {
				?>
				jQuery('#<?php echo $wrapper; ?>-aipowered').fadeIn();
				<?php
			} else {
				?>
				jQuery('#<?php echo $wrapper; ?>-aipowered').hide();
				<?php
			}
			?>
			}, 200);
		</script>
		<?php

		// check if we should bubble a specific conversation
		if ($bubble_convo) {
			?>
		<script type="text/javascript">
			setTimeout(() => {
				vboWidgetGuestMessagesOpenChat('<?php echo $bubble_convo; ?>');
			}, 400);
		</script>
			<?php
		}

		// append the total number of messages displayed, the current offset and the latest message datetime
		$latest_datetime = !$search_filters && $tot_messages > 0 && $offset === 0 ? $latest_messages[0]->last_updated : null;

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return [
			'html' 		   => $html_content,
			'tot_messages' => $tot_messages,
			'offset' 	   => ($offset + $length),
			'latest_dt'    => $latest_datetime,
		];
	}

	/**
	 * Custom method for this widget only to watch the latest guest messages.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * 
	 * Outputs the new number of messages found from the latest datetime.
	 */
	public function watchMessages()
	{
		$latest_dt = VikRequest::getString('latest_dt', '', 'request');
		if (empty($latest_dt)) {
			echo '0';
			return;
		}

		if (!$this->vcm_exists) {
			VBOHttpDocument::getInstance()->close(500, 'Vik Channel Manager is either not available or outdated');
		}

		// load the latest guest message (one is sufficient)
		$latest_messages = [];
		try {
			$latest_messages = VikChannelManager::getLatestFromGuests(['guest_messages'], 0, 1);
		} catch (Exception $e) {
			// do nothing
		}

		if (!$latest_messages || $latest_messages[0]->last_updated == $latest_dt) {
			// no newest messages found
			echo '0';
			return;
		}

		// print 1 to indicate that new messages should be reloaded
		echo '1';
	}

	/**
	 * Custom method for this widget only to render the chat of a booking.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * no values should be returned, as the response must be sent to output
	 * in case the JS/CSS assets will be echoed within the response.
	 * 
	 * Returns the necessary HTML code to render the chat.
	 */
	public function renderChat()
	{
		$bid = VikRequest::getInt('bid', 0, 'request');

		$booking = VikBooking::getBookingInfoFromID($bid);

		if (!$booking) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find booking');
		}

		// initialize chat instance by getting the proper channel name
		if (empty($booking['channel'])) {
			// front-end reservation chat handler
			$chat_channel = 'vikbooking';
		} else {
			$channelparts = explode('_', $booking['channel']);
			// check if this is a meta search channel
			$is_meta_search = false;
			if (preg_match("/(customer).*[0-9]$/", $channelparts[0]) || !strcasecmp($channelparts[0], 'googlehotel') || !strcasecmp($channelparts[0], 'googlevr') || !strcasecmp($channelparts[0], 'trivago')) {
				$is_meta_search = empty($booking['idorderota']);
			}
			if ($is_meta_search) {
				// customer of type sales channel should use front-end reservation chat handler
				$chat_channel = 'vikbooking';
			} else {
				// let the getInstance method validate the channel chat handler
				$chat_channel = $booking['channel'];
			}
		}
		$messaging = VikBooking::getVcmChatInstance($booking['id'], $chat_channel);

		if (is_null($messaging)) {
			VBOHttpDocument::getInstance()->close(500, 'Could not render chat');
		}

		// send content to output
		echo $messaging->renderChat([
			'hideThreads' => 1,
		], $load_assets = false);
	}

	/**
	 * Custom method for this widget only to update the thread of a booking.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * no values should be returned, as the response must be sent to output.
	 * 
	 * Returns a successful string or throws an error.
	 */
	public function setNoReplyNeededThread()
	{
		$bid 	   = VikRequest::getInt('bid', 0, 'request');
		$id_thread = VikRequest::getInt('id_thread', 0, 'request');
		$status    = VikRequest::getInt('status', 0, 'request');

		$booking = VikBooking::getBookingInfoFromID($bid);

		if (!$booking || empty($id_thread)) {
			VBOHttpDocument::getInstance()->close(404, 'Could not find booking thread');
		}

		// build thread object for update
		$thread = new stdClass;
		$thread->id = $id_thread;
		$thread->idorder = $bid;
		$thread->no_reply_needed = !$status ? 1 : 0;

		$dbo = JFactory::getDbo();

		if (!$dbo->updateObject('#__vikchannelmanager_threads', $thread, ['id', 'idorder'])) {
			VBOHttpDocument::getInstance()->close(500, 'Could not update thread');
		}

		echo '1';
	}

	/**
	 * Custom method for this widget only to load the listing details for the booking.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * an associative array is returned thanks to the request value "return":1.
	 * 
	 * Needed to provide additional information to the host about the booked listing(s).
	 */
	public function loadListingDetails()
	{
		$bid = VikRequest::getInt('bid', 0, 'request');

		$booking_rooms = VikBooking::loadOrdersRoomsData($bid);

		if (!$this->vcm_exists || !$booking_rooms) {
			VBOHttpDocument::getInstance()->close(500, 'Could not obtain the listing information');
		}

		return [
			'listings' => array_column($booking_rooms, 'room_name'),
		];
	}

	public function render(?VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the guest messages wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-guest-messages-' . $wrapper_instance;

		// this widget will work only if VCM is available and updated, and if permissions are met
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$this->vcm_exists || !$vbo_auth_bookings) {
			return;
		}

		// multitask data event identifier for clearing intervals
		$js_intvals_id    = '';
		$wrap_extra_class = '';
		$bid_convo        = 0;
		$unread_filter    = false;
		$sort_by_ai       = false;
		$contains_filter  = '';
		if ($data && $data->isModalRendering()) {
			// access Multitask data
			$js_intvals_id = $data->getModalJsIdentifier();

			// check if a specific conversation should be opened
			if ($data->get('id_message', 0)) {
				$bid_convo = $data->getBookingId();
			} else {
				$bid_convo = $this->options()->fetchBookingId();
			}

			// set an extra class
			$wrap_extra_class = ' vbo-w-guestmessages-wrapmodal';

			// check for unread messages filter
			$unread_filter = (bool) $this->options()->get('unread', '');

			// check for AI sort filter
			$sort_by_ai = (bool) $this->options()->get('ai_sort', '');

			// check if a specific filter was set for searching guest messages
			$contains_filter = (string) $this->options()->get('message_contains', '');

			// check custom limit per page only when in modal rendering
			if (($this->widgetSettings->limpage ?? 0) > 0) {
				// set custom limit from widget settings
				$this->messages_per_page = (int) $this->widgetSettings->limpage;
			}

			// when modal rendering, we expect to adopt the inbox-style by default unless in small screens
			if ($this->messages_per_page < $this->inbox_messages_per_page) {
				// in order to facilitate the infinite scroll, we use the minimum number of messages per page
				$this->messages_per_page = $this->inbox_messages_per_page;
			}

			// check for limit filter injected
			$limpage_filter = (int) $this->options()->get('limpage', 0);
			if ($limpage_filter > 0) {
				// set custom limit
				$this->messages_per_page = $limpage_filter;
			}
		}

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
					<div class="vbo-admin-widget-head-commands">
						<div class="vbo-reportwidget-commands">
							<div class="vbo-reportwidget-commands-main">
								<div class="vbo-reportwidget-command-dates">
									<div id="<?php echo $wrapper_id; ?>-filters" class="vbo-reportwidget-period-name" style="<?php echo !$contains_filter && !$unread_filter ? 'display: none;' : ''; ?>"><?php VikBookingIcons::e('filter'); ?> <?php echo JText::translate('VBO_FILTERS_APPLIED'); ?></div>
								</div>
								<div id="<?php echo $wrapper_id; ?>-aipowered" class="vbo-admin-widget-head-ai-powered" style="display: none;">
									<span class="label label-info"><?php echo JText::translate('VBO_AI_LABEL_DEF'); ?></span>
								</div>
							</div>
							<div class="vbo-reportwidget-command-dots">
								<span class="vbo-widget-command-togglefilters vbo-widget-guest-messages-togglefilters" onclick="vboWidgetGuestMessagesOpenSettings('<?php echo $wrapper_id; ?>');">
									<?php VikBookingIcons::e('ellipsis-v'); ?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="<?php echo $wrapper_id; ?>" class="vbo-dashboard-guests-latest<?php echo $wrap_extra_class; ?>" data-offset="0" data-length="<?php echo $this->messages_per_page; ?>" data-eventsid="<?php echo $js_intvals_id; ?>" data-latestdt="">
				<div class="vbo-dashboard-guest-messages-inner">
					<div class="vbo-w-guestmessages-list-container">
						<div class="vbo-dashboard-guest-messages-list">
						<?php
						for ($i = 0; $i < $this->messages_per_page; $i++) {
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
					<div class="vbo-w-guestmessages-inboxstyle-chat"></div>
				</div>
				<div class="vbo-widget-guest-messages-filters-hidden" style="display: none;">
					<div class="vbo-widget-guest-messages-filters-wrap">
						<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
							<div class="vbo-params-wrap">
								<div class="vbo-params-container">
									<div class="vbo-params-block">

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></div>
											<div class="vbo-param-setting">
												<input type="text" class="vbo-widget-guest-messages-guestname" value="" autocomplete="off" />
												<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_FIRST_NAME_ACCURATE_HELP'); ?></span>
											</div>
										</div>

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBSENDEMAILCUSTCONT'); ?></div>
											<div class="vbo-param-setting">
												<input type="text" class="vbo-widget-guest-messages-messcontains" value="<?php echo JHtml::fetch('esc_attr', $contains_filter); ?>" autocomplete="off" />
												<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_MESSAGE_CONTAINS_HELP'); ?></span>
											</div>
										</div>

										<div class="vbo-param-container vbo-toggle-small">
											<div class="vbo-param-label"><?php echo JText::translate('VBO_UNREAD_MESSAGES'); ?></div>
											<div class="vbo-param-setting">
												<?php echo $this->vbo_app->printYesNoButtons('unread', JText::translate('VBYES'), JText::translate('VBNO'), (int) $unread_filter, 1, 0, '', ['blue']); ?>
											</div>
										</div>

										<div class="vbo-param-container vbo-toggle-small">
											<div class="vbo-param-label"><?php echo JText::translate('VBO_AI_LABEL_DEF') . ' ' . JText::translate('VBO_SORT_BY_PRIORITY'); ?></div>
											<div class="vbo-param-setting">
												<?php echo $this->vbo_app->printYesNoButtons('ai_sort', JText::translate('VBYES'), JText::translate('VBNO'), (int) $sort_by_ai, 1, 0, '', ['gold']); ?>
											</div>
										</div>

										<div class="vbo-param-container vbo-toggle-small">
											<div class="vbo-param-label"><?php echo JText::translate('VBO_SENDER'); ?></div>
											<div class="vbo-param-setting">
												<div class="vbo-widget-guest-messages-multistate">
													<?php
													echo $this->vbo_app->multiStateToggleSwitchField(
														'sender' . $wrapper_instance,
														'',
														[
															'guest',
															'hotel',
														],
														[
															[
																'value' => JText::translate('VBO_GUEST'),
															],
															[
																'value' => 'Hotel',
															],
														],
														[
															[
																'label_class' => 'vik-multiswitch-text vik-multiswitch-radiobtn-guest',
																'input' 	  => [
																	'class' => 'vbo-widget-guest-messages-filter-sender',
																],
															],
															[
																'label_class' => 'vik-multiswitch-text vik-multiswitch-radiobtn-hotel',
																'input' 	  => [
																	'class' => 'vbo-widget-guest-messages-filter-sender',
																],
															],
														],
														[
															'class' => 'vik-multiswitch-noanimation',
														]
													);
													?>
												</div>
											</div>
										</div>

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDFROMRANGE'); ?></div>
											<div class="vbo-param-setting">
												<div class="vbo-field-calendar">
													<div class="input-append">
														<input type="text" class="vbo-widget-guest-messages-fromdt" value="" autocomplete="off" />
														<button type="button" class="btn btn-secondary vbo-widget-guest-messages-fromdt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
													</div>
												</div>
												<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_FROM_DT_HELP'); ?></span>
											</div>
										</div>

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDTORANGE'); ?></div>
											<div class="vbo-param-setting">
												<div class="vbo-field-calendar">
													<div class="input-append">
														<input type="text" class="vbo-widget-guest-messages-todt" value="" autocomplete="off" />
														<button type="button" class="btn btn-secondary vbo-widget-guest-messages-todt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
													</div>
												</div>
												<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_TO_DT_HELP'); ?></span>
											</div>
										</div>

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBO_MESS_PER_PAGE'); ?></div>
											<div class="vbo-param-setting">
												<input type="number" class="vbo-widget-guest-messages-limpage" min="1" max="100" value="<?php echo $this->messages_per_page; ?>" />
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php
		if (static::$instance_counter === 0 || $is_ajax) {
			// HTML helper tag for URL routing and some JS functions should be loaded once per widget instance
			$admin_file_base = VBOPlatformDetection::isWordPress() ? 'admin.php' : 'index.php';
		?>

		<script type="text/javascript">

			/**
			 * Open the settings to search/filter the guest messages.
			 */
			function vboWidgetGuestMessagesOpenSettings(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// determine the messages rendering method
				var mess_rendering_meth = widget_instance.hasClass('vbo-w-guestmessages-inboxstyle') ? 'inbox' : 'paging';

				// define unique modal event name to avoid conflicts
				var eventsid = widget_instance.attr('data-eventsid') || (Math.floor(Math.random() * 100000));
				var modal_dismiss_event = 'dismiss-modal-wguestmessages-search' + eventsid;

				// the hidden container of the search input fields
				var search_elements = widget_instance.find('.vbo-widget-guest-messages-filters-wrap');

				// build the button element to apply the search filters
				var apply_search_btn = document.createElement('button');
				apply_search_btn.setAttribute('type', 'button');
				apply_search_btn.classList.add('btn', 'btn-success');
				apply_search_btn.append(document.createTextNode(Joomla.JText._('VBODASHSEARCHKEYS')));
				apply_search_btn.addEventListener('click', () => {
					VBOCore.emitEvent(modal_dismiss_event, JSON.stringify({applyfilters: 1, wrapper: wrapper}));
				});

				// build the button element to clear the search filters
				var clear_search_btn = document.createElement('button');
				clear_search_btn.setAttribute('type', 'button');
				clear_search_btn.classList.add('btn');
				clear_search_btn.append(document.createTextNode(Joomla.JText._('VBOSIGNATURECLEAR')));
				clear_search_btn.addEventListener('click', () => {
					VBOCore.emitEvent(modal_dismiss_event, JSON.stringify({clearfilters: 1, wrapper: wrapper}));
				});

				var search_modal_body = VBOCore.displayModal({
					suffix: 'wguestmessages-search',
					extra_class: 'vbo-modal-rounded vbo-modal-dialog',
					title: '<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_W_GUESTMESSAGES_TITLE')); ?> - ' + Joomla.JText._('VBODASHSEARCHKEYS'),
					body_prepend: true,
					draggable: true,
					footer_left: clear_search_btn,
					footer_right: apply_search_btn,
					dismiss_event: modal_dismiss_event,
					onDismiss: (e) => {
						// always move back the search input fields
						search_elements.appendTo(widget_instance.find('.vbo-widget-guest-messages-filters-hidden'));

						if (!e || !e.detail) {
							// no event data received, maybe the modal was simply dismissed
							jQuery('#' + wrapper + '-filters').hide();
							return;
						}

						// parse data received within the dismiss event
						try {
							let commands = JSON.parse(e.detail);

							if (!commands['wrapper']) {
								return;
							}

							// determine if we are resetting the widget
							let is_resetting = false;

							if (commands['applyfilters']) {
								if (mess_rendering_meth == 'inbox') {
									// turn flag on
									is_resetting = true;
									// empty messages list
									widget_instance.find('.vbo-dashboard-guest-messages-list').html('');
									// destroy any previous chat instance
									widget_instance.find('.vbo-w-guestmessages-inboxstyle-chat').html('');
									if (typeof VCMChat !== 'undefined') {
										VCMChat.getInstance().destroy();
									}
									// destroy inifite scroll events
									vboWidgetGuestMessagesDestroyInfiniteScroll(wrapper);
								}

								// display filters applied label
								jQuery('#' + wrapper + '-filters').show();
								// reset offset to 0
								widget_instance.attr('data-offset', 0);
								// show loading skeletons
								vboWidgetGuestMessagesSkeletons(commands['wrapper']);
								// reload guest messages for this widget's instance with filters set
								vboWidgetGuestMessagesLoad(commands['wrapper'], null, is_resetting);
							}

							if (commands['clearfilters']) {
								if (mess_rendering_meth == 'inbox') {
									// turn flag on
									is_resetting = true;
									// empty messages list
									widget_instance.find('.vbo-dashboard-guest-messages-list').html('');
									// destroy any previous chat instance
									widget_instance.find('.vbo-w-guestmessages-inboxstyle-chat').html('');
									if (typeof VCMChat !== 'undefined') {
										VCMChat.getInstance().destroy();
									}
									// destroy inifite scroll events
									vboWidgetGuestMessagesDestroyInfiniteScroll(wrapper);
								}

								// clear filters
								widget_instance.find('.vbo-widget-guest-messages-filters-wrap').find('input[type="text"]').val('');
								widget_instance.find('.vbo-widget-guest-messages-filters-wrap').find('input[type="number"]').val('');
								widget_instance.find('.vbo-widget-guest-messages-filters-wrap').find('input[type="checkbox"]').prop('checked', false);
								widget_instance.find('.vbo-widget-guest-messages-filters-wrap').find('input[type="radio"]').prop('checked', false);
								// hide filters applied label
								jQuery('#' + wrapper + '-filters').hide();
								// reset offset to 0
								widget_instance.attr('data-offset', 0);
								// show loading skeletons
								vboWidgetGuestMessagesSkeletons(commands['wrapper']);
								// reload guest messages for this widget's instance with filters cleared
								vboWidgetGuestMessagesLoad(commands['wrapper'], null, is_resetting);
							}
						} catch(e) {
							// abort
							return;
						}
					},
				});

				// move the search filter fields to the modal body
				search_elements.appendTo(search_modal_body);
			}

			/**
			 * Open the chat for the clicked booking guest message
			 */
			function vboWidgetGuestMessagesOpenChat(id) {
				// clicked message
				var message_el = jQuery('.vbo-w-guestmessages-message[data-idorder="' + id + '"]').first();

				if (message_el.hasClass('vbo-w-guestmessages-message-new')) {
					// get rid of the "new/unread" status
					message_el.removeClass('vbo-w-guestmessages-message-new');
					if (message_el.find('i.message-new').length) {
						message_el.find('i.message-new').remove();
					}
				}

				// set active message class only on the clicked message
				jQuery('.vbo-w-guestmessages-message').removeClass('vbo-inbox-active-message');
				message_el.addClass('vbo-inbox-active-message');

				// get widget's main block and chat container
				var message_block = message_el.closest('.vbo-dashboard-guests-latest');
				var chat_inline_container = message_block.find('.vbo-w-guestmessages-inboxstyle-chat');

				// determine the chat rendering method (modal or inline)
				var chat_rendering_meth = message_block.hasClass('vbo-w-guestmessages-inboxstyle') ? 'inline' : 'modal';

				// destroy any previous chat instance
				if (typeof VCMChat !== 'undefined') {
					VCMChat.getInstance().destroy();
				}

				// always empty chat container
				chat_inline_container.html('');

				// modal events unique id to avoid conflicts
				var eventsid = message_block.attr('data-eventsid') || (Math.floor(Math.random() * 100000));

				// define unique modal event names to avoid conflicts
				var modal_dismiss_event = 'dismiss-modal-wguestmessages-chat' + eventsid;
				var modal_loading_event = 'loading-modal-wguestmessages-chat' + eventsid;

				// check for multiple instances of this widget, maybe because of clicked notifications while another instance was displayed
				if (jQuery('.vbo-w-guestmessages-message[data-idorder="' + id + '"]').length > 1) {
					// multiple instances found
					if (message_block.attr('data-eventsid')) {
						// dismiss the previous modal and keep using the same event id to ensure a de-registration of the modal events
						VBOCore.emitEvent(modal_dismiss_event);
					} else {
						// fallback to using a random events id to avoid conflicts
						eventsid = Math.floor(Math.random() * 100000);
					}
				}

				// build modal content
				var chat_head_title = jQuery('<span></span>');
				var chat_head_title_wrap = jQuery('<span></span>').addClass('vbo-modal-wguestmessages-chat-info');
				var chat_head_title_top = jQuery('<span></span>').addClass('vbo-modal-wguestmessages-chat-info-customer');
				var chat_head_title_bot = jQuery('<span></span>').addClass('vbo-modal-wguestmessages-chat-info-booking');

				var chat_head_title_img = jQuery('<span></span>').addClass('vbo-modal-wguestmessages-chat-guestavatar');
				var chat_head_title_txt = jQuery('<span></span>').addClass('vbo-modal-wguestmessages-chat-guestname');
				var chat_head_title_bid = jQuery('<span></span>').addClass('vbo-modal-wguestmessages-chat-guestbid');
				var chat_head_open_bid = jQuery('<a></a>').addClass('badge badge-info').attr('href', 'JavaScript: void(0);').text(id).on('click', () => {
					VBOCore.handleDisplayWidgetNotification({
						widget_id: 'booking_details'
					}, {
						bid: id,
						modal_options: {
							suffix: 'widget_modal_inner_booking_details',
						}
					});
				});
				chat_head_title_bid.append(chat_head_open_bid);

				var guest_avatar = message_el.find('.vbo-dashboard-guest-activity-avatar img');
				var guest_name = message_el.find('.vbo-w-guestmessages-message-gtitle').find('span').first().text();
				chat_head_title_txt.text(guest_name);
				if (guest_avatar && guest_avatar.length) {
					chat_head_title_img.append(guest_avatar.clone());
					chat_head_title.append(chat_head_title_img);
				}
				chat_head_title_top.append(chat_head_title_txt);
				chat_head_title_top.append(chat_head_title_bid);

				chat_head_title_bot.append(message_el.find('.vbo-dashboard-guest-activity-content-info-icon').html());

				// register callback for no-reply-needed click
				var no_reply_needed_el = jQuery('<a></a>').addClass('label').attr('href', 'JavaScript: void(0);').text(Joomla.JText._('VBO_NO_REPLY_NEEDED'));
				if (message_el.attr('data-noreply-needed') == 1) {
					// this thread was marked as no-reply needed
					no_reply_needed_el.addClass('label-danger');
				}
				no_reply_needed_el.on('click', () => {
					if (confirm(Joomla.JText._('VBO_WANT_PROCEED'))) {
						var id_thread = message_el.attr('data-idthread');
						if (!id_thread || !id_thread.length) {
							return false;
						}

						// perform the request to toggle the thread as no-reply-needed
						var call_method = 'setNoReplyNeededThread';

						VBOCore.doAjax(
							"<?php echo $this->getExecWidgetAjaxUri(); ?>",
							{
								widget_id: "<?php echo $this->getIdentifier(); ?>",
								call: call_method,
								bid: id,
								id_thread: id_thread,
								status: message_el.attr('data-noreply-needed'),
								tmpl: "component"
							},
							(response) => {
								try {
									var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
									if (!obj_res.hasOwnProperty(call_method)) {
										console.error('Unexpected JSON response', obj_res);
										return false;
									}

									if (chat_rendering_meth == 'modal') {
										// emit the event to close (dismiss) the modal
										VBOCore.emitEvent(modal_dismiss_event);

										// reload the widget
										vboWidgetGuestMessagesLoad(message_block.attr('id'));
									} else {
										// update data attribute for toggle without destroying the chat
										if (message_el.attr('data-noreply-needed') == 1) {
											message_el.attr('data-noreply-needed', 0);
											no_reply_needed_el.removeClass('label-danger');
											// remove the reply button
											message_el.find('.message-unreplied').remove();
										} else {
											message_el.attr('data-noreply-needed', 1);
											no_reply_needed_el.addClass('label-danger');
										}
									}
								} catch(err) {
									console.error('could not parse JSON response', err, response);
								}
							},
							(error) => {
								console.error(error);
							}
						);
					}
				});

				// append element that will contain the listing details involved
				var listing_details_parent_el = jQuery('<span></span>').addClass('vbo-w-guestmessages-message-stayrooms');
				var listing_details_el = jQuery('<span></span>').addClass('vbo-w-guestmessages-message-listings').text('...');
				listing_details_parent_el.append(listing_details_el);
				chat_head_title_bot.append(listing_details_parent_el);

				// append no-reply-needed element
				chat_head_title_bot.append(no_reply_needed_el);

				// append additional elements
				chat_head_title_wrap.append(chat_head_title_top);
				chat_head_title_wrap.append(chat_head_title_bot);

				// finalize title
				chat_head_title.append(chat_head_title_wrap);

				if (chat_rendering_meth == 'modal') {
					// display modal
					var chat_modal_body = VBOCore.displayModal({
						suffix: 'wguestmessages-chat',
						extra_class: 'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
						title: chat_head_title,
						draggable: true,
						dismiss_event: modal_dismiss_event,
						onDismiss: () => {
							if (typeof VCMChat !== 'undefined') {
								VCMChat.getInstance().destroy();
							}
						},
						loading_event: modal_loading_event,
					});

					// start loading
					VBOCore.emitEvent(modal_loading_event);
				} else {
					// append head to chat container
					let chat_inline_head = jQuery('<div></div>').addClass('vbo-w-guestmessages-inboxstyle-chat-head');
					chat_inline_head.append(chat_head_title);
					chat_inline_container.append(chat_inline_head);

					// set inline loading body
					let chat_inline_loading = jQuery('<div></div>').addClass('vbo-w-guestmessages-inboxstyle-chat-loading');
					chat_inline_loading.html('<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>');
					chat_inline_container.append(chat_inline_loading);
				}

				// perform the request to render the chat in the apposite modal or inline wrapper
				var call_method = 'renderChat';

				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						bid: id,
						tmpl: "component"
					},
					(response) => {
						if (chat_rendering_meth == 'modal') {
							// stop loading
							VBOCore.emitEvent(modal_loading_event);
						} else {
							// unset inline loading body
							chat_inline_container.find('.vbo-w-guestmessages-inboxstyle-chat-loading').remove();
						}

						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// append HTML code to render the chat to the apposite container
							if (chat_rendering_meth == 'modal') {
								chat_modal_body.html(obj_res[call_method]);
							} else {
								let chat_inline_body = jQuery('<div></div>').addClass('vbo-w-guestmessages-inboxstyle-chat-body');
								chat_inline_body.html(obj_res[call_method]);
								chat_inline_container.append(chat_inline_body);
							}

							// register scroll to bottom with a small delay
							setTimeout(() => {
								if (typeof VCMChat !== 'undefined') {
									VCMChat.getInstance().scrollToBottom();
								}
							}, 150);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						if (chat_rendering_meth == 'modal') {
							// stop loading
							VBOCore.emitEvent(modal_loading_event);
						} else {
							// empty inline chat container
							chat_inline_container.html('');
						}

						// log and display error
						console.error(error);
						alert(error.responseText);
					}
				);

				// perform the request to load the listing details involved
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: 'loadListingDetails',
						return: 1,
						bid: id,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty('loadListingDetails')) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							if (!Array.isArray(obj_res['loadListingDetails']['listings']) || !obj_res['loadListingDetails']['listings'].length) {
								// remove listing details element
								chat_head_title_bot.find('.vbo-w-guestmessages-message-stayrooms').remove();

								return false;
							}

							// set listing details
							chat_head_title_bot.find('.vbo-w-guestmessages-message-listings').html('<?php VikBookingIcons::e('home'); ?> ' + obj_res['loadListingDetails']['listings'].join(', '));
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// log error
						console.error(error);
					}
				);
			}

			/**
			 * Display the loading skeletons.
			 */
			function vboWidgetGuestMessagesSkeletons(wrapper, howmany) {
				let widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// number of skeletons to build
				let numSkeletons = <?php echo $this->messages_per_page; ?>;
				if (howmany && !isNaN(howmany) && howmany > 0) {
					numSkeletons = howmany;
				}

				// determine the messages rendering method
				var mess_rendering_meth = widget_instance.hasClass('vbo-w-guestmessages-inboxstyle') ? 'inbox' : 'paging';

				if (mess_rendering_meth != 'inbox') {
					// empty the current messages list
					widget_instance.find('.vbo-dashboard-guest-messages-list').html('');
				}

				for (let i = 0; i < numSkeletons; i++) {
					// build skeleton element
					let skeleton = '';
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

					// append skeleton element to the messages list
					widget_instance.find('.vbo-dashboard-guest-messages-list').append(skeleton);
				}
			}

			/**
			 * Perform the request to load the latest messages.
			 */
			function vboWidgetGuestMessagesLoad(wrapper, bid_convo, resetting) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var current_offset  = parseInt(widget_instance.attr('data-offset'));
				var length_per_page = parseInt(widget_instance.attr('data-length'));

				// determine the messages rendering method (paging or inbox, where "inbox" will likely never be on the first loading)
				var mess_rendering_meth = widget_instance.hasClass('vbo-w-guestmessages-inboxstyle') ? 'inbox' : 'paging';

				// build search filter values
				var filters = {
					guest_name: widget_instance.find('input.vbo-widget-guest-messages-guestname').val(),
					message: widget_instance.find('input.vbo-widget-guest-messages-messcontains').val(),
					sender: widget_instance.find('input.vbo-widget-guest-messages-filter-sender[type="radio"]:checked').val(),
					fromdt: widget_instance.find('input.vbo-widget-guest-messages-fromdt').val(),
					todt: widget_instance.find('input.vbo-widget-guest-messages-todt').val(),
					unread: (widget_instance.find('input[name="unread"]').prop('checked') ? 1 : 0),
					ai_sort: (widget_instance.find('input[name="ai_sort"]').prop('checked') ? 1 : 0),
				};

				// custom messages limit per page
				let limpage = widget_instance.find('input.vbo-widget-guest-messages-limpage').val();
				if (!isNaN(limpage) && limpage > 0 && limpage != length_per_page) {
					// update value
					length_per_page = limpage;
					// set new limit
					widget_instance.attr('data-length', limpage);
				}

				// access the current messages list
				let messagesList = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-w-guestmessages-list-container');

				// the widget method to call
				var call_method = 'loadMessages';

				// make a request to load the messages
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						bid_convo: bid_convo,
						filters: filters,
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

							if (mess_rendering_meth == 'inbox' && parseInt(current_offset) > 0) {
								// inbox style should get rid of non-needed elements and append the new messages
								widget_instance.find('.vbo-guestactivitywidget-commands').remove();
								widget_instance.find('.vbo-dashboard-guest-messages-list').find('.vbo-dashboard-guest-activity-skeleton').remove();
								widget_instance.find('.vbo-dashboard-guest-messages-list').find('.info[data-no-messages="1"]').remove();
								widget_instance.find('.vbo-dashboard-guest-messages-list').append(obj_res[call_method]['html']);
								if (parseInt(obj_res[call_method]['tot_messages']) > 0) {
									// move the class to the new last guest message
									widget_instance.find('.vbo-w-guestmessages-message.vbo-w-guestmessages-message-last').removeClass('vbo-w-guestmessages-message-last');
									widget_instance.find('.vbo-w-guestmessages-message').last().addClass('vbo-w-guestmessages-message-last');
								}
							} else {
								// replace HTML with new messages
								widget_instance.find('.vbo-dashboard-guest-messages-list').html(obj_res[call_method]['html']);
							}

							if (mess_rendering_meth == 'inbox') {
								if (!messagesList.pageLoading && parseInt(current_offset) > 0) {
									// inbox style with messages not loaded through infinite scroll
									// on a page after the first, may indicate that we are on a very
									// long screen (high height) that did not detect the scrolling of
									// the messages list to be needed, so we trigger the resize event
									VBOCore.emitEvent('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>');
								} else if (resetting) {
									// applying or cancelling filters may require to re-configure the infinite scroll events
									VBOCore.emitEvent('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>');
								}

								// always turn custom property off for the page loading
								messagesList.pageLoading = false;
							}

							// check if latest datetime is set
							if (obj_res[call_method]['latest_dt']) {
								widget_instance.attr('data-latestdt', obj_res[call_method]['latest_dt']);
							}

							// check results
							if (parseInt(obj_res[call_method]['tot_messages']) < 1) {
								// no results can indicate the offset is invalid or too high
								if (!isNaN(current_offset) && parseInt(current_offset) > 0) {
									if (mess_rendering_meth == 'inbox') {
										// inbox style should simply destroy the infinite scroll
										vboWidgetGuestMessagesDestroyInfiniteScroll(wrapper);
									} else {
										// reset offset to 0
										widget_instance.attr('data-offset', 0);
										// show loading skeletons
										vboWidgetGuestMessagesSkeletons(wrapper);
										// reload the first page
										vboWidgetGuestMessagesLoad(wrapper);
									}
								}
							} else {
								if (bid_convo) {
									// emit the event to read all notifications in the current context
									VBOCore.emitEvent('vbo-nc-read-notifications', {
										criteria: {
											group:   'guests',
											idorder: bid_convo,
										}
									});
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// display and log the error
						alert(error.responseText);
						console.error(error);

						if (mess_rendering_meth == 'inbox') {
							// turn custom property off for the page loading
							messagesList.pageLoading = false;
						}

						// remove the skeleton loading
						widget_instance.find('.vbo-dashboard-guest-messages-list').find('.vbo-dashboard-guest-activity-skeleton').remove();
					}
				);
			}

			/**
			 * Navigate between the various pages of the messages.
			 */
			function vboWidgetGuestMessagesNavigate(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// current offset
				var current_offset = parseInt(widget_instance.attr('data-offset'));

				// steps per type
				var steps = parseInt(widget_instance.attr('data-length'));

				// determine the messages rendering method (paging or inbox, where "inbox" will likely never be on the first loading)
				var mess_rendering_meth = widget_instance.hasClass('vbo-w-guestmessages-inboxstyle') ? 'inbox' : 'paging';

				if (mess_rendering_meth != 'inbox') {
					// show loading skeletons only when in chat "modal" style
					vboWidgetGuestMessagesSkeletons(wrapper);
				}

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
				vboWidgetGuestMessagesLoad(wrapper);
			}

			/**
			 * Watch periodically if there are new messages to be displayed (inline rendering only).
			 */
			function vboWidgetGuestMessagesWatch(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var latest_dt = widget_instance.attr('data-latestdt');
				if (!latest_dt || !latest_dt.length) {
					return false;
				}

				// determine the messages rendering method (paging or inbox, where "inbox" will likely never be on the first loading)
				var mess_rendering_meth = widget_instance.hasClass('vbo-w-guestmessages-inboxstyle') ? 'inbox' : 'paging';

				// the widget method to call
				var call_method = 'watchMessages';

				// make a request to watch the messages
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

							// response will contain the number of new messages
							if (isNaN(obj_res[call_method]) || parseInt(obj_res[call_method]) < 1) {
								// do nothing
								return;
							}

							if (mess_rendering_meth == 'inbox') {
								// do nothing when inbox-style, hence in a modal window, because this interval
								// will not run and new messages are taken from the apposite events dispatched
								// this statement should never verify
							} else {
								// new messages found, reset the offset and re-load the first page
								widget_instance.attr('data-offset', 0);
								// show loading skeletons
								vboWidgetGuestMessagesSkeletons(wrapper);
								// reload the first page
								vboWidgetGuestMessagesLoad(wrapper);
							}
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

			/**
			 * Reloads chat messages when using inbox-style.
			 */
			function vboWidgetGuestMessagesReloadInbox(wrapper) {
				let widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if the admin was typing a reply message
				let is_typing = false;
				try {
					let reply_message = jQuery(VCMChat.getInstance().data.element.inputBox).find('textarea').val();
					if (reply_message) {
						// turn flag on
						is_typing = true;
					}
				} catch(e) {
					// do nothing
				}

				if (is_typing) {
					console.info('New messages not reloaded because the administrator is typing a reply to a guest message.');
					return false;
				}

				// destroy inifite scroll events
				vboWidgetGuestMessagesDestroyInfiniteScroll(wrapper);
				// empty messages list
				widget_instance.find('.vbo-dashboard-guest-messages-list').html('');
				// reset offset to 0
				widget_instance.attr('data-offset', 0);
				// show loading skeletons
				vboWidgetGuestMessagesSkeletons(wrapper);
				// reload guest messages for this widget's instance
				vboWidgetGuestMessagesLoad(wrapper, null, true);
				// restore infinite scroll loading, if needed
				VBOCore.emitEvent('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>');
			}

			/**
			 * Destroys the infinite scroll loading when using inbox-style.
			 */
			function vboWidgetGuestMessagesDestroyInfiniteScroll(wrapper) {
				let messagesList = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-w-guestmessages-list-container');

				// un-register infinite scroll event handler
				messagesList
					.removeEventListener('scroll', vboWidgetGuestMessagesInfiniteScroll);
			}

			/**
			 * Setups the infinite scroll loading when using inbox-style.
			 */
			function vboWidgetGuestMessagesSetupInfiniteScroll(wrapper) {
				let messagesList = document
					.querySelector('#' + wrapper)
					.querySelector('.vbo-w-guestmessages-list-container');

				// get wrapper dimensions
				let listViewHeight = messagesList.offsetHeight;
				let listGlobHeight = messagesList.scrollHeight;

				if (listViewHeight >= listGlobHeight) {
					// no scrolling detected, show manual loading of the next page
					document
						.querySelector('#' + wrapper)
						.querySelector('.vbo-guestactivitywidget-commands')
						.style
						.display = 'block';

					return;
				}

				// inject custom property to identify the wrapper ID
				messagesList.wrapperId = wrapper;

				// register infinite scroll event handler
				messagesList
					.addEventListener('scroll', vboWidgetGuestMessagesInfiniteScroll);
			}

			/**
			 * Infinite scroll event handler when using inbox-style.
			 */
			function vboWidgetGuestMessagesInfiniteScroll(e) {
				// access the injected wrapper ID property
				let wrapper = e.currentTarget.wrapperId;

				if (!wrapper) {
					return;
				}

				// register throttling callback
				VBOCore.throttleTimer(() => {
					// access the current messages list
					let messagesList = document
						.querySelector('#' + wrapper)
						.querySelector('.vbo-w-guestmessages-list-container');

					// make sure the loading of a next page isn't running
					if (messagesList.pageLoading) {
						// abort
						return;
					}

					// get wrapper dimensions
					let listViewHeight = messagesList.offsetHeight;
					let listGlobHeight = messagesList.scrollHeight;
					let listScrollTop  = messagesList.scrollTop;

					if (!listScrollTop || listViewHeight >= listGlobHeight) {
						// no scrolling detected at all
						return;
					}

					// calculate missing distance to the end of the list
					let listEndDistance = listGlobHeight - (listViewHeight + listScrollTop);

					if (listEndDistance < <?php echo $this->px_distance_threshold; ?>) {
						// inject custom property to identify a next page is loading
						messagesList.pageLoading = true;

						// show that we are loading more messages
						vboWidgetGuestMessagesSkeletons(wrapper, 3);

						// load the next page of messages
						vboWidgetGuestMessagesNavigate(wrapper, 1);
					}
				}, 500);
			}

			/**
			 * Subscribe to the event emitted by VCM's chat handler when replying to a guest message.
			 */
			document.addEventListener('vcm-guest-message-replied', (e) => {
				if (!e || !e.detail) {
					return;
				}

				// pool of messaging elements to scan
				let elements  = [];

				// gather the supported event detail properties
				let idorder   = e.detail.hasOwnProperty('idorder') ? e.detail['idorder'] : null;
				let idthread  = e.detail.hasOwnProperty('idthread') ? e.detail['idthread'] : null;
				let idmessage = e.detail.hasOwnProperty('idmessage') ? e.detail['idmessage'] : null;

				// check if some eligible elements can be fetched
				if (idorder) {
					elements = document.querySelectorAll('.vbo-w-guestmessages-message[data-idorder="' + idorder + '"]');
				} else if (idthread) {
					elements = document.querySelectorAll('.vbo-w-guestmessages-message[data-idthread="' + idthread + '"]');
				} else if (idmessage) {
					elements = document.querySelectorAll('.vbo-w-guestmessages-message[data-idmessage="' + idmessage + '"]');
				}

				// scan all the elements from which the "reply" label should be removed, if any
				elements.forEach((element) => {
					let unreplied = element.querySelector('.message-unreplied');
					if (unreplied) {
						// remove node stating that the guest message needs a reply
						unreplied.remove();
					}
				});
			});

		</script>
		<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// when document is ready, load latest messages for this widget's instance
				vboWidgetGuestMessagesLoad('<?php echo $wrapper_id; ?>', '<?php echo $bid_convo; ?>');

				// make sure we've got no other chat instances on the same page (editorder)
				if (jQuery('#vbmessagingdiv').length) {
					if (typeof VCMChat !== 'undefined') {
						VCMChat.getInstance().destroy();
					}
					jQuery('#vbmessagingdiv').html('<p class="info"><?php echo JHtml::fetch('esc_attr', JText::translate('VBO_W_GUESTMESSAGES_TITLE')); ?> - <?php echo JHtml::fetch('esc_attr', JText::translate('VBO_MULTITASK_PANEL')); ?></p>');
				}

				// render datepicker calendar for dates navigation
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-guest-messages-fromdt, .vbo-widget-guest-messages-todt').datepicker({
					maxDate: "+1d",
					yearRange: "<?php echo (date('Y') - 3); ?>:<?php echo date('Y'); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "<?php echo $this->getDateFormat('jui'); ?>",
					onSelect: function(selectedDate) {
						if (!selectedDate) {
							return;
						}
						if (jQuery(this).hasClass('vbo-widget-guest-messages-fromdt')) {
							let nowstart = jQuery(this).datepicker('getDate');
							let nowstartdate = new Date(nowstart.getTime());
							jQuery('.vbo-widget-guest-messages-todt').datepicker('option', {minDate: nowstartdate});
						}
					}
				});

				// triggering for datepicker calendar icon
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-guest-messages-fromdt-trigger, .vbo-widget-guest-messages-todt-trigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

				// register callback function for the widget "resize" event (modal only)
				const resize_fn = (e) => {
					let inboxStyleThreshold = 1000;

					let modalContent = e?.detail?.content ? (e?.detail?.content[0] || e?.detail?.content || null) : null;
					if (!modalContent || !modalContent.matches('#<?php echo $wrapper_id; ?>')) {
						modalContent = document.querySelector('#<?php echo $wrapper_id; ?>');
					}

					// always attempt to destroy infinite scroll
					vboWidgetGuestMessagesDestroyInfiniteScroll('<?php echo $wrapper_id; ?>');

					let overlayContent = modalContent.closest('.vbo-modal-overlay-content');
					let outerBody = modalContent.closest('.vbo-modal-overlay-content-body');
					if (!overlayContent || !outerBody) {
						// widget may be minimized on the admin-dock
						return;
					}

					// get messages list container
					let listContainer = modalContent.querySelector('.vbo-w-guestmessages-list-container');

					if (modalContent.offsetWidth > inboxStyleThreshold) {
						// enable inbox-style
						modalContent.classList.add('vbo-w-guestmessages-inboxstyle');
						overlayContent.classList.add('vbo-modal-body-no-scroll');
						// calculate the messages list container height to allow y-scrolling
						listContainer.style.height = (outerBody.offsetHeight - (listContainer.offsetTop - outerBody.offsetTop) - 4) + 'px';
						// set head-no-border class
						outerBody.classList.add('vbo-modal-head-no-border');
						// setup infinite scroll
						vboWidgetGuestMessagesSetupInfiniteScroll('<?php echo $wrapper_id; ?>');
					} else {
						// disable inbox-style
						modalContent.classList.remove('vbo-w-guestmessages-inboxstyle');
						overlayContent.classList.remove('vbo-modal-body-no-scroll');
						// reset the messages list container height property
						listContainer.style.height = 'initial';
						// reset head-no-border class
						outerBody.classList.remove('vbo-modal-head-no-border');
					}
				};

				// register callback for the new guest messages event (modal only)
				const new_messages_fn = (e) => {
					if (!e || !e.detail || !e.detail?.messages) {
						return;
					}

					let modalContent = document.querySelector('#<?php echo $wrapper_id; ?>');
					if (!modalContent || !Array.isArray(e.detail.messages) || !e.detail.messages.length) {
						return;
					}

					let modalWrap = modalContent.closest('.vbo-modal-widget_modal-wrap[data-dock-minimized]');
					if (!modalWrap) {
						return;
					}

					// check if the widget is minimized
					if (modalWrap.getAttribute('data-dock-minimized') == 1) {
						// register self-destructive event for reloading the inbox upon widget restoring from admin-dock
						document.addEventListener('vbo-admin-dock-restore-<?php echo $this->getIdentifier(); ?>', function vboWidgetGuestMessagesRestoreNReload(e) {
							// make sure the same event won't propagate again
							e.target.removeEventListener(e.type, vboWidgetGuestMessagesRestoreNReload);

							// reload chat messages
							vboWidgetGuestMessagesReloadInbox('<?php echo $wrapper_id; ?>');
						});
					} else {
						// reload chat messages
						vboWidgetGuestMessagesReloadInbox('<?php echo $wrapper_id; ?>');
					}
				};

			<?php
			if ($js_intvals_id) {
				// widget can be dismissed or "resized" through the modal
				?>
				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_intvals_id; ?>', (e) => {
					if (typeof watch_intv !== 'undefined') {
						// clear interval for notifications
						clearInterval(watch_intv);
					}

					if (jQuery('#vbmessagingdiv').length) {
						// reload the page for the previously removed chat in the editorder page
						location.reload();
					}

					// get rid of widget resizing events
					document.removeEventListener('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>', resize_fn);
					document.removeEventListener('vbo-admin-dock-restore-<?php echo $this->getIdentifier(); ?>', resize_fn);
					window.removeEventListener('resize', resize_fn);

					// get rid of new guest messages event
					document.removeEventListener('vbo-new-guest-messages', new_messages_fn);

					// if there was an inline chat, destroy it
					if (typeof VCMChat !== 'undefined') {
						VCMChat.getInstance().destroy();
					}
				});

				// register widget resizing events
				document.addEventListener('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>', resize_fn);
				document.addEventListener('vbo-admin-dock-restore-<?php echo $this->getIdentifier(); ?>', resize_fn);
				window.addEventListener('resize', resize_fn);

				/**
				 * Subscribe to the event emitted when new guest messages are received.
				 */
				document.addEventListener('vbo-new-guest-messages', new_messages_fn);
				<?php
			} else {
				// set interval for loading new messages automatically (only when not modal rendering)
				?>
				const watch_intv = setInterval(function() {
					vboWidgetGuestMessagesWatch('<?php echo $wrapper_id; ?>');
				}, 60000);
				<?php
			}
			?>

			});

		</script>

		<?php
	}
}
