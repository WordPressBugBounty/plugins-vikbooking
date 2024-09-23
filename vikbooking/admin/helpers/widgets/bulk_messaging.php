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
 * Class handler for admin widget "bulk messaging".
 * 
 * @since 	1.16.7 (J) - 1.6.7 (WP)
 */
class VikBookingAdminWidgetBulkMessaging extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_BULKMESSAGING_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_BULKMESSAGING_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('bullhorn') . '"></i>';
		$this->widgetStyleName = 'yellow';

		// load widget's settings
		$this->widgetSettings = $this->loadSettings();
		if (!is_object($this->widgetSettings)) {
			$this->widgetSettings = new stdClass;
		}
	}

	/**
	 * Custom method for this widget only to load the reservations.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 */
	public function loadBookings()
	{
		// get today's date
		$today_ymd = date('Y-m-d');

		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$type 	 = VikRequest::getString('type', 'stayover', 'request');
		$from_dt = VikRequest::getString('from_dt', $today_ymd, 'request');
		$to_dt 	 = VikRequest::getString('to_dt', '', 'request') ?: $from_dt;

		if (empty($from_dt)) {
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBO_PLEASE_FILL_FIELDS'));
		}

		// get date timestamps
		$from_ts = VikBooking::getDateTimestamp($from_dt, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($to_dt, 23, 59, 59);

		// query the db
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select($dbo->qn('o') . '.*')
			->select($dbo->qn('co.idcustomer'))
			->select('CONCAT_WS(" ", ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') AS ' . $dbo->qn('customer_fullname'))
			->select($dbo->qn('c.country', 'customer_country'))
			->select($dbo->qn('c.pic'))
			->from($dbo->qn('#__vikbooking_orders', 'o'))
			->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'))
			->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'))
			->where($dbo->qn('o.closure') . ' = 0');

		if ($type == 'arrival') {
			$q->where($dbo->qn('o.checkin') . ' >= ' . $from_ts);
			$q->where($dbo->qn('o.checkin') . ' <= ' . $to_ts);
		} elseif ($type == 'departure') {
			$q->where($dbo->qn('o.checkout') . ' >= ' . $from_ts);
			$q->where($dbo->qn('o.checkout') . ' <= ' . $to_ts);
		} elseif ($type == 'bookdate') {
			$q->where($dbo->qn('o.ts') . ' >= ' . $from_ts);
			$q->where($dbo->qn('o.ts') . ' <= ' . $to_ts);
		} else {
			// stayover
			$q->where('(
				(' . $dbo->qn('o.checkin') . ' >= ' . $from_ts . ' AND ' . $dbo->qn('o.checkin') . ' <= ' . $to_ts . ')
				OR
				(' . $dbo->qn('o.checkout') . ' >= ' . $from_ts . ' AND ' . $dbo->qn('o.checkout') . ' <= ' . $to_ts . ')
			)');
		}

		$dbo->setQuery($q);
		$bookings = $dbo->loadAssocList();

		// total checkboxes checked
		$tot_checked = 0;

		// first booking ID "checked"
		$first_checked_bid = 0;
		$widget_id = $this->widgetId;

		// start output buffering
		ob_start();

		if (!$bookings) {
			?>
			<p class="info"><?php echo JText::translate('VBNOORDERSFOUND'); ?></p>
			<?php
		} else {
			// display all bookings of this day
			foreach ($bookings as $ind => $booking) {
				// get channel logo and other details
				$ch_logo_obj  = VikBooking::getVcmChannelsLogo($booking['channel'], true);
				$channel_logo = is_object($ch_logo_obj) ? $ch_logo_obj->getSmallLogoURL() : '';
				$nights_lbl = $booking['days'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY');
				$rooms_lbl = !empty($booking['roomsnum']) && $booking['roomsnum'] > 1 ? ', ' . $booking['roomsnum'] . ' ' . JText::translate('VBPVIEWORDERSTHREE') : '';

				// compose customer name
				$customer_name = !empty($booking['customer_fullname']) ? $booking['customer_fullname'] : '';
				if ($booking['closure'] > 0 || !strcasecmp($booking['custdata'], JText::translate('VBDBTEXTROOMCLOSED'))) {
					$customer_name = '<span class="vbordersroomclosed"><i class="' . VikBookingIcons::i('ban') . '"></i> ' . JText::translate('VBDBTEXTROOMCLOSED') . '</span>';
				}
				if (empty($customer_name)) {
					$customer_name = VikBooking::getFirstCustDataField($booking['custdata']);
				}

				// customer country flag
				$customer_country = '';
				$customer_cflag   = '';
				if (!empty($booking['customer_country'])) {
					$customer_country = $booking['customer_country'];
				} elseif (!empty($booking['country'])) {
					$customer_country = $booking['country'];
				}
				if ($customer_country && is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'countries' . DIRECTORY_SEPARATOR . $customer_country . '.png')) {
					$customer_cflag = '<img src="'.VBO_ADMIN_URI.'resources/countries/' . $customer_country . '.png'.'" title="' . htmlspecialchars($customer_country) . '" class="vbo-country-flag vbo-country-flag-left"/>';
				}

				// check for any previous event triggered by this widget for the current reservation
				$last_notified = null;
				$history_obj   = VikBooking::getBookingHistoryInstance($booking['id']);
				$prev_ev_data  = $history_obj->getEventsWithData('CE', function($data) use ($widget_id) {
					return (is_object($data) && !empty($data->widget) && $data->widget == $widget_id);
				}, $onlydata = false);
				if (is_array($prev_ev_data) && $prev_ev_data) {
					$last_notified = $prev_ev_data[0]['dt'];
				}

				// default checked status
				$booking_is_checked = ($booking['status'] == 'confirmed' && !$last_notified);
				if ($booking_is_checked) {
					$tot_checked++;
					if (!$first_checked_bid) {
						$first_checked_bid = $booking['id'];
					}
				}

				?>
				<div class="vbo-dashboard-guest-activity vbo-widget-bulkmess-reservation" data-type="<?php echo $booking['status']; ?>" data-resid="<?php echo $booking['id']; ?>">
					<div class="vbo-widget-bulkmess-ckbox">
						<input type="checkbox" value="<?php echo $booking['id']; ?>" <?php echo $booking_is_checked ? 'checked ' : ''; ?>/>
					</div>
					<div class="vbo-dashboard-guest-activity-avatar">
					<?php
					if (!empty($channel_logo)) {
						// channel logo has got the highest priority
						?>
						<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo $channel_logo; ?>" />
						<?php
					} elseif (!empty($booking['pic'])) {
						// customer profile picture
						?>
						<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo strpos($booking['pic'], 'http') === 0 ? $booking['pic'] : VBO_SITE_URI . 'resources/uploads/' . $booking['pic']; ?>" />
						<?php
					} else {
						// we use an icon as fallback
						VikBookingIcons::e('hotel', 'vbo-dashboard-guest-activity-avatar-icon');
					}
					?>
					</div>
					<div class="vbo-dashboard-guest-activity-content">
						<div class="vbo-dashboard-guest-activity-content-head">
							<div class="vbo-dashboard-guest-activity-content-info-details">
								<h4><?php echo $customer_name . $customer_cflag; ?></h4>
								<div class="vbo-dashboard-guest-activity-content-info-icon">
								<?php
								if ($booking['status'] == 'cancelled') {
									?>
									<span class="badge badge-danger"><?php echo JText::translate('VBCANCELLED'); ?></span>
									<?php
								} elseif ($booking['status'] == 'standby') {
									?>
									<span class="badge badge-warning"><?php echo JText::translate('VBSTANDBY'); ?></span>
									<?php
								}
								?>
									<span><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $this->datesep, $this->df), $booking['checkin']); ?> - <?php echo $booking['days'] . ' ' . $nights_lbl . $rooms_lbl; ?></span>
								<?php
								if ($last_notified) {
									?>
									<span class="vbo-widget-bulkmess-notified vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBOBOOKHISTORYTCE')); ?>"><?php VikBookingIcons::e('envelope'); ?> <?php echo JHtml::fetch('date', $hist['dt'], 'Y-m-d H:i:s'); ?></span>
									<?php
								}
								?>
								</div>
							</div>
							<div class="vbo-dashboard-guest-activity-content-info-date">
								<span class="vbo-widget-bulkmess-openbook" onclick="vboWidgetBulkMessOpenBooking('<?php echo $booking['id']; ?>');">
									<span class="label label-info"><?php VikBookingIcons::e('eye'); ?> <?php echo $booking['id']; ?></span>
								</span>
								<span><?php echo date(str_replace("/", $this->datesep, $this->df) . ' H:i', $booking['ts']); ?></span>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
		}

		?>
		<script type="text/javascript">

			jQuery(function() {

				/**
				 * Register the first checked booking ID to let the mail preview work.
				 */
				if (typeof window['vbo_current_bid'] === 'undefined') {
					window['vbo_current_bid'] = '<?php echo $first_checked_bid; ?>';
				}

				/**
				 * Prepare the elements to toggle the checkbox on click.
				 */
				jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bulkmess-reservation').on('click', function(e) {
					if (jQuery(e.target).hasClass('.vbo-widget-bulkmess-openbook') || jQuery(e.target).closest('.vbo-widget-bulkmess-openbook').length) {
						return;
					}

					if (!jQuery(e.target).is('input[type="checkbox"]')) {
						var ckbox = jQuery(this).find('input[type="checkbox"]');
						if (ckbox.prop('checked')) {
							ckbox.prop('checked', false);
						} else {
							ckbox.prop('checked', true);
						}
					}

					// update total checked count
					var checked_stats = vboWidgetBulkMessCountChecked('<?php echo $wrapper; ?>');

					// update status on bookings step
					jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bulkmess-step-bookings').find('.vbo-widget-bulkmess-step-status').text(checked_stats['tot_checked'] + ' / ' + checked_stats['tot_bookings']);
				});

			});

		</script>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return [
			'html' 		   => $html_content,
			'tot_bookings' => count($bookings),
			'tot_checked'  => $tot_checked,
		];
	}

	/**
	 * Custom method for this widget only to send the communication message.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 */
	public function sendOneMessage()
	{
		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$bid 	 = VikRequest::getInt('bid', 0, 'request');
		$index 	 = VikRequest::getInt('index', 0, 'request');
		$message = VikRequest::getString('message', '', 'request', VIKREQUEST_ALLOWHTML);
		$subject = VikRequest::getString('subject', '', 'request');

		$booking = VikBooking::getBookingInfoFromID($bid);

		if (!$booking) {
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBPEDITBUSYONE'));
		}

		if (empty($message)) {
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBO_PLEASE_FILL_FIELDS'));
		}

		if ($index === 0) {
			// update widget's settings with last message and subject
			$this->widgetSettings->last_subject = $subject;
			$this->widgetSettings->last_message = $message;
			$this->updateSettings(json_encode($this->widgetSettings));
		}

		// determine the message dispatching method for this booking
		$sending_method = 'eMail';

		// check if support for OTA messaging is available
		$ota_messaging_supported = class_exists('VCMChatMessaging');

		if ($ota_messaging_supported && VCMChatMessaging::getInstance($booking)->supportsOtaMessaging($mandatory = true)) {
			// set flag to identify an OTA messaging notification over the regular email
			$sending_method = 'Message';
		} elseif (preg_match("/^no-email-[^@]+@[a-z0-9\.]+\.com$/i", $booking['custmail'])) {
			// false email address, typical of Airbnb, ignore reservation record
			// this statement should be entered only if VCM is outdated, or the Guest Messaging API will be used
			VBOHttpDocument::getInstance()->close(500, 'The booking ID ' . $booking['id'] . ' does not have a valid email address that can be notified.');
		}

		if ($sending_method === 'eMail' && empty($booking['custmail'])) {
			VBOHttpDocument::getInstance()->close(500, 'The booking ID ' . $booking['id'] . ' is missing the guest email address.');
		}

		// language translation
		$lang = JFactory::getLanguage();
		$vbo_tn = VikBooking::getTranslator();
		$vbo_tn::$force_tolang = null;
		$website_def_lang = $vbo_tn->getDefaultLang();
		if (!empty($booking['lang'])) {
			if ($lang->getTag() != $booking['lang']) {
				if (VBOPlatformDetection::isWordPress()) {
					// wp
					$lang->load('com_vikbooking', VIKBOOKING_SITE_LANG, $booking['lang'], true);
					$lang->load('com_vikbooking', VIKBOOKING_ADMIN_LANG, $booking['lang'], true);
				} else {
					// J
					$lang->load('com_vikbooking', JPATH_SITE, $booking['lang'], true);
					$lang->load('com_vikbooking', JPATH_ADMINISTRATOR, $booking['lang'], true);
					$lang->load('joomla', JPATH_SITE, $booking['lang'], true);
					$lang->load('joomla', JPATH_ADMINISTRATOR, $booking['lang'], true);
				}
			}
			if ($website_def_lang != $booking['lang']) {
				// force the translation to start because contents should be translated
				$vbo_tn::$force_tolang = $booking['lang'];
			}
		}

		// dispatch the message to the guest reservation
		if ($sending_method === 'eMail') {
			// regular email notification
			$message = $this->notifyThroughEmail($booking, $message, $subject);
		} else {
			// notification through guest messaging API
			$message = $this->notifyThroughMessage($booking, $message);
		}

		// update history for this booking with the information about the communication sent
		VikBooking::getBookingHistoryInstance($booking['id'])
			->setExtraData(['widget' => $this->widgetId])
			->store('CE', $message);

		// return an associative array of values
		return [
			'result' => 1,
		];
	}

	/**
	 * Preload the necessary CSS/JS assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// load assets
		$this->vbo_app->loadDatePicker();

		if (VBOPlatformDetection::isJoomla()) {
			// load assets
			$this->vbo_app->loadVisualEditorAssets();
		}

		// JS lang defs
		JText::script('VBO_PLEASE_SELECT');
		JText::script('VBPVIEWORDERSPEOPLE');
		JText::script('VBO_WANT_PROCEED');
		JText::script('VBO_CONT_WRAPPER');
		JText::script('VBO_CONT_WRAPPER_HELP');
	}

	/**
	 * Main method to invoke the widget. Contents will be loaded
	 * through AJAX requests, not via PHP when the page loads.
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
		$wrapper_id = 'vbo-widget-bulkmess-' . $wrapper_instance;

		// get permissions
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_bookings) {
			// display nothing
			return;
		}

		// date format
		$dtpicker_df = $this->getDateFormat('jui');

		// build the default message subject
		$def_subject = !empty($this->widgetSettings->last_subject) ? $this->widgetSettings->last_subject : JText::sprintf('VBOMAILSUBJECT', VikBooking::getFrontTitle());

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
				</div>
			</div>
			<div class="vbo-widget-bulkmess-wrap">
				<div class="vbo-widget-bulkmess-steps">

					<div class="vbo-widget-bulkmess-step vbo-widget-bulkmess-step-search">
						<div class="vbo-widget-bulkmess-step-title" onclick="vboWidgetBulkMessToggleStep('<?php echo $wrapper_id; ?>', 'search');">
							<h4><?php VikBookingIcons::e('calendar'); ?> <?php echo JText::translate('VBODASHSEARCHKEYS'); ?></h4>
							<span class="vbo-widget-bulkmess-step-status"></span>
						</div>
						<div class="vbo-widget-bulkmess-step-content">
							<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
								<div class="vbo-params-wrap">
									<div class="vbo-params-container">

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDFROMRANGE'); ?></div>
											<div class="vbo-param-setting">
												<div class="vbo-field-calendar">
													<div class="input-append">
														<input type="text" class="vbo-widget-bulkmess-fromdt" value="" autocomplete="off" />
														<button type="button" class="btn btn-secondary vbo-widget-bulkmess-fromdt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
													</div>
												</div>
											</div>
										</div>

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDTORANGE'); ?></div>
											<div class="vbo-param-setting">
												<div class="vbo-field-calendar">
													<div class="input-append">
														<input type="text" class="vbo-widget-bulkmess-todt" value="" autocomplete="off" />
														<button type="button" class="btn btn-secondary vbo-widget-bulkmess-todt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
													</div>
												</div>
											</div>
										</div>

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBPSHOWSEASONSTHREE'); ?></div>
											<div class="vbo-param-setting">
												<select class="vbo-widget-bulkmess-type">
													<option value="stayover"><?php echo JText::translate('VBOTYPESTAYOVER'); ?></option>
													<option value="arrival"><?php echo JText::translate('VBOTYPEARRIVAL'); ?></option>
													<option value="departure"><?php echo JText::translate('VBOTYPEDEPARTURE'); ?></option>
													<option value="bookdate"><?php echo JText::translate('VBPEDITBUSYTWO'); ?></option>
												</select>
											</div>
										</div>

										<div class="vbo-param-container vbo-param-confirm-btn">
											<div class="vbo-param-label"></div>
											<div class="vbo-param-setting">
												<button type="button" class="btn btn-primary vbo-btn-wide vbo-widget-bulkmess-loadbtn" onclick="vboWidgetBulkMessLoadBookings('<?php echo $wrapper_id; ?>');"><span><?php echo JText::translate('VBJQCALNEXT'); ?></span> <?php VikBookingIcons::e('chevron-right'); ?></button>
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="vbo-widget-bulkmess-step vbo-widget-bulkmess-step-hidden vbo-widget-bulkmess-step-bookings">
						<div class="vbo-widget-bulkmess-step-title" onclick="vboWidgetBulkMessToggleStep('<?php echo $wrapper_id; ?>', 'bookings');">
							<h4><?php VikBookingIcons::e('users'); ?> <?php echo JText::translate('VBMENUCUSTOMERS'); ?></h4>
							<span class="vbo-widget-bulkmess-step-status"></span>
						</div>
						<div class="vbo-widget-bulkmess-step-content">
							<div class="vbo-widget-bulkmess-bookings-actions">
								<button type="button" class="btn btn-small" onclick="vboWidgetBulkMessSelectAll('<?php echo $wrapper_id; ?>');"><?php echo JText::translate('VBINVSELECTALL'); ?></button>
							</div>
							<div class="vbo-dashboard-guests-latest vbo-widget-bulkmess-bookings-list">
								<p class="info"><?php echo JText::translate('VBNOORDERSFOUND'); ?></p>
							</div>
							<div class="vbo-widget-bulkmess-gonext" style="display: none;">
								<button type="button" class="btn btn-primary vbo-btn-wide" onclick="vboWidgetBulkMessGoNext('<?php echo $wrapper_id; ?>', 'message');"><span><?php echo JText::translate('VBJQCALNEXT'); ?></span> <?php VikBookingIcons::e('chevron-right'); ?></button>
							</div>
						</div>
					</div>

					<div class="vbo-widget-bulkmess-step vbo-widget-bulkmess-step-hidden vbo-widget-bulkmess-step-message">
						<div class="vbo-widget-bulkmess-step-title" onclick="vboWidgetBulkMessToggleStep('<?php echo $wrapper_id; ?>', 'message');">
							<h4><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBSENDEMAILCUSTCONT'); ?></h4>
							<span class="vbo-widget-bulkmess-step-status"></span>
						</div>
						<div class="vbo-widget-bulkmess-step-content">
							<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
								<div class="vbo-params-wrap">
									<div class="vbo-params-container">

										<div class="vbo-param-container">
											<div class="vbo-param-label"><?php echo JText::translate('VBSENDEMAILCUSTSUBJ'); ?></div>
											<div class="vbo-param-setting">
												<input type="text" class="vbo-widget-bulkmess-subject" value="<?php echo JHtml::fetch('esc_attr', $def_subject); ?>" autocomplete="off" />
											</div>
										</div>

									</div>
								</div>
							</div>

							<div class="vbo-widget-bulkmess-message">
								<?php echo $this->renderEditor(); ?>
							</div>
							<div class="vbo-widget-bulkmess-gonext">
								<button type="button" class="btn btn-primary vbo-btn-wide" onclick="vboWidgetBulkMessGoNext('<?php echo $wrapper_id; ?>', 'send');"><span><?php echo JText::translate('VBO_SEND'); ?></span> <?php VikBookingIcons::e('chevron-right'); ?></button>
							</div>
						</div>
					</div>

					<div class="vbo-widget-bulkmess-step vbo-widget-bulkmess-step-hidden vbo-widget-bulkmess-step-send">
						<div class="vbo-widget-bulkmess-step-title" onclick="vboWidgetBulkMessToggleStep('<?php echo $wrapper_id; ?>', 'send');">
							<h4><?php VikBookingIcons::e('rocket'); ?> <?php echo JText::translate('VBO_SEND_MESSAGES'); ?></h4>
							<span class="vbo-widget-bulkmess-step-status"></span>
						</div>
						<div class="vbo-widget-bulkmess-step-content">
							<div class="vbo-widget-bulkmess-progress">
								<div class="vbo-widget-bulkmess-progress-inner">
									<progress value="0" max="100">0 / 0</progress>
								</div>
							</div>
						</div>
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

			var vbo_widget_bulkmess_icn_load = '<?php echo VikBookingIcons::i('spinner', 'fa-spin fa-fw'); ?>';
			var vbo_widget_bulkmess_icn_next = '<?php echo VikBookingIcons::i('chevron-right'); ?>';

			/**
			 * Perform the request to load the bookings.
			 */
			function vboWidgetBulkMessLoadBookings(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get vars for making the request
				var from_dt = widget_instance.find('.vbo-widget-bulkmess-fromdt').val();
				var to_dt   = widget_instance.find('.vbo-widget-bulkmess-todt').val();
				var type    = widget_instance.find('.vbo-widget-bulkmess-type').val();
				var typelbl = widget_instance.find('.vbo-widget-bulkmess-type').find('option:selected').text();
				typelbl = typelbl ? typelbl : 'Stayover';

				// set loading icon
				widget_instance.find('.vbo-widget-bulkmess-step-search')
					.find('.vbo-widget-bulkmess-loadbtn')
					.prop('disabled', true)
					.find('i')
					.attr('class', vbo_widget_bulkmess_icn_load);

				// the widget method to call
				var call_method = 'loadBookings';

				// make a request to load the bookings
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: 	   call_method,
						return:    1,
						from_dt:   from_dt,
						to_dt: 	   to_dt,
						type: 	   type,
						wrapper:   wrapper,
						tmpl: 	   "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// replace HTML with new bookings calendar
							widget_instance.find('.vbo-widget-bulkmess-bookings-list').html(obj_res[call_method]['html']);

							// update search step status
							widget_instance.find('.vbo-widget-bulkmess-step-search').find('.vbo-widget-bulkmess-step-status').text(typelbl);

							// check if we got results
							if (obj_res[call_method]['tot_bookings'] > 0) {
								// hide all steps except the bookings list
								widget_instance.find('.vbo-widget-bulkmess-step').not('.vbo-widget-bulkmess-step-bookings').addClass('vbo-widget-bulkmess-step-hidden');
								// show the button to go next
								widget_instance.find('.vbo-widget-bulkmess-step-bookings').find('.vbo-widget-bulkmess-gonext').show();
							} else {
								// hide the button to go next
								widget_instance.find('.vbo-widget-bulkmess-step-bookings').find('.vbo-widget-bulkmess-gonext').hide();
							}

							// display the bookings list step
							widget_instance.find('.vbo-widget-bulkmess-step-bookings').removeClass('vbo-widget-bulkmess-step-hidden');

							// update bookings step status
							widget_instance.find('.vbo-widget-bulkmess-step-bookings').find('.vbo-widget-bulkmess-step-status').text(obj_res[call_method]['tot_checked'] + ' / ' + obj_res[call_method]['tot_bookings']);

							// restore next icon
							widget_instance.find('.vbo-widget-bulkmess-step-search')
								.find('.vbo-widget-bulkmess-loadbtn')
								.prop('disabled', false)
								.find('i')
								.attr('class', vbo_widget_bulkmess_icn_next);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
							widget_instance.find('.vbo-widget-bulkmess-bookings-list').html('');
						}
					},
					(error) => {
						widget_instance.find('.vbo-widget-bulkmess-bookings-list').html('');
						console.error(error);
						alert(error.responseText);
					}
				);
			}

			/**
			 * Renders the booking details widget.
			 */
			function vboWidgetBulkMessOpenBooking(bid) {
				VBOCore.handleDisplayWidgetNotification({widget_id: 'booking_details'}, {
					booking_id: bid,
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
			}

			/**
			 * Completes a step.
			 */
			function vboWidgetBulkMessGoNext(wrapper, stepname) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				if (stepname == 'message' || stepname == 'send') {
					// count the number of selected bookings
					var checked_stats = vboWidgetBulkMessCountChecked(wrapper);
					if (!checked_stats['tot_checked']) {
						alert(Joomla.JText._('VBO_PLEASE_SELECT'));
						return false;
					}

					// update step status if about to build the message
					if (stepname == 'message') {
						widget_instance.find('.vbo-widget-bulkmess-step-message').find('.vbo-widget-bulkmess-step-status').text(Joomla.JText._('VBPVIEWORDERSPEOPLE') + ': ' + checked_stats['tot_checked']);
					}

					// ask for confirmation before sending
					if (stepname == 'send') {
						if (!confirm(Joomla.JText._('VBO_WANT_PROCEED'))) {
							return false;
						} else {
							// start sending
							vboWidgetBulkMessDoSend(wrapper);
						}
					}
				}

				// hide all steps except the next one
				widget_instance.find('.vbo-widget-bulkmess-step').not('.vbo-widget-bulkmess-step-' + stepname).addClass('vbo-widget-bulkmess-step-hidden');

				// display the next step
				widget_instance.find('.vbo-widget-bulkmess-step-' + stepname).removeClass('vbo-widget-bulkmess-step-hidden');
			}

			/**
			 * Starts the process to send the communication messages.
			 */
			function vboWidgetBulkMessDoSend(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// gather the list of booking IDs to notify
				var bids_pool = [];
				widget_instance.find('.vbo-widget-bulkmess-step-bookings')
					.find('input[type="checkbox"]:checked')
					.each(function() {
						bids_pool.push(jQuery(this).val());
					});

				if (!bids_pool.length) {
					alert(Joomla.JText._('VBO_PLEASE_SELECT'));
					return false;
				}

				// hide all buttons to go next
				widget_instance.find('.vbo-widget-bulkmess-gonext').hide();

				// update step status
				widget_instance.find('.vbo-widget-bulkmess-step-send').find('.vbo-widget-bulkmess-step-status').html('<?php VikBookingIcons::e('spinner', 'fa-spin fa-fw'); ?>');

				// trigger the recursive async sending process
				vboWodgetBulkMessRecursiveAsyncSend(0, bids_pool, wrapper);
			}

			/**
			 * Recursive function to asynchronously dispatch the messages.
			 */
			function vboWodgetBulkMessRecursiveAsyncSend(i, pool, wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					throw new Error('Widget instance not found');
				}

				if (i >= pool.length) {
					// process completed, update status
					widget_instance.find('.vbo-widget-bulkmess-step-send')
						.find('.vbo-widget-bulkmess-step-status')
						.html('<?php VikBookingIcons::e('check-circle'); ?>');

					// do not continue
					return;
				}

				if (!pool.hasOwnProperty(i) || !pool[i]) {
					throw new Error('Invalid index argument');
				}

				if (i === 0) {
					// start the progress bar
					widget_instance.find('.vbo-widget-bulkmess-step-send')
						.find('progress')
						.attr('value', 0)
						.attr('max', pool.length)
						.text('0 / ' + pool.length);
				}

				// gather message subject and content
				var subject = widget_instance.find('.vbo-widget-bulkmess-subject').val();
				var message = widget_instance.find('.vbo-widget-bulkmess-messagecont').val();

				// the widget method to call
				var call_method = 'sendOneMessage';

				// make a request to send the message
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: 	   call_method,
						return:    1,
						bid:   	   pool[i],
						index: 	   i,
						message:   message,
						subject:   subject,
						wrapper:   wrapper,
						tmpl: 	   "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// update progress bar
							widget_instance.find('.vbo-widget-bulkmess-step-send')
								.find('progress')
								.attr('value', (i + 1))
								.text((i + 1) + ' / ' + pool.length);

							// go next
							vboWodgetBulkMessRecursiveAsyncSend(i + 1, pool, wrapper);

						} catch(err) {
							// log and display error
							console.error('could not parse JSON response', err, response);
							alert('could not parse JSON response');

							// go next either way
							vboWodgetBulkMessRecursiveAsyncSend(i + 1, pool, wrapper);
						}
					},
					(error) => {
						// log and display error
						console.error(error);
						alert(error.responseText);

						// update progress bar
						widget_instance.find('.vbo-widget-bulkmess-step-send')
							.find('progress')
							.attr('value', (i + 1))
							.text((i + 1) + ' / ' + pool.length);

						// go next either way
						vboWodgetBulkMessRecursiveAsyncSend(i + 1, pool, wrapper);
					}
				);
			}

			/**
			 * Toggles the visibility of a step.
			 */
			function vboWidgetBulkMessToggleStep(wrapper, stepname) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// display the current step
				widget_instance.find('.vbo-widget-bulkmess-step-' + stepname).toggleClass('vbo-widget-bulkmess-step-hidden');
			}

			/**
			 * Counts the number of bookings checked.
			 */
			function vboWidgetBulkMessCountChecked(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var container = widget_instance.find('.vbo-widget-bulkmess-step-bookings');

				var tot_bookings = container.find('input[type="checkbox"]').length;
				var tot_checked  = container.find('input[type="checkbox"]:checked').length;

				if (tot_checked) {
					// overwrite the first checked booking ID to let the mail preview work
					window['vbo_current_bid'] = container.find('input[type="checkbox"]:checked').first().attr('value');
				}

				return {
					tot_checked:  tot_checked,
					tot_bookings: tot_bookings
				};
			}

			/**
			 * Toggles the checked status for all bookings.
			 */
			function vboWidgetBulkMessSelectAll(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var checked_stats = vboWidgetBulkMessCountChecked(wrapper);
				if (!checked_stats['tot_bookings']) {
					return false;
				}

				var container 	 = widget_instance.find('.vbo-widget-bulkmess-step-bookings');
				var tot_elements = checked_stats['tot_bookings'];

				if (checked_stats['tot_checked'] < checked_stats['tot_bookings']) {
					// select all
					container.find('input[type="checkbox"]').prop('checked', true);
				} else {
					// select none
					container.find('input[type="checkbox"]').prop('checked', false);
					tot_elements = 0;
				}

				// update status on bookings step
				container.find('.vbo-widget-bulkmess-step-status').text(tot_elements + ' / ' + checked_stats['tot_bookings']);
			}

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// render datepicker calendar for dates navigation
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-bulkmess-fromdt, .vbo-widget-bulkmess-todt').datepicker({
					minDate: "-1y",
					maxDate: "+3y",
					yearRange: "<?php echo (date('Y') - 2); ?>:<?php echo (date('Y') + 3); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "<?php echo $dtpicker_df; ?>",
					onSelect: function(selectedDate) {
						if (!selectedDate) {
							return;
						}
						if (jQuery(this).hasClass('vbo-widget-bulkmess-fromdt')) {
							let nowstart = jQuery(this).datepicker('getDate');
							let nowstartdate = new Date(nowstart.getTime());
							jQuery('.vbo-widget-bulkmess-todt').datepicker('option', {minDate: nowstartdate});
						}
					}
				});

				// triggering for datepicker calendar icon
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-bulkmess-fromdt-trigger, .vbo-widget-bulkmess-todt-trigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

			});

		</script>

		<?php
	}

	/**
	 * Renders the editor to compose the message.
	 * 
	 * @return 	string
	 */
	private function renderEditor()
	{
		// load assets
		$this->vbo_app->loadVisualEditorAssets();

		// build a list of all special tags for the visual editor
		$special_tags_base = [
			'{customer_name}',
			'{customer_pin}',
			'{booking_id}',
			'{checkin_date}',
			'{checkout_date}',
			'{num_nights}',
			'{rooms_booked}',
			'{tot_adults}',
			'{tot_children}',
			'{tot_guests}',
			'{total}',
			'{total_paid}',
			'{remaining_balance}',
			'{booking_link}',
		];

		// load all conditional text special tags
		$condtext_tags = array_keys(VikBooking::getConditionalRulesInstance()->getSpecialTags());

		// join special tags with conditional texts to construct a list of editor buttons,
		// displayed within the toolbar of Quill editor
		$editor_btns = array_merge($special_tags_base, $condtext_tags);

		// convert special tags into HTML buttons, displayed under the text editor
		$special_tags_base = array_map(function($tag)
		{
			return '<button type="button" class="btn" onclick="setCronTplTag(\'tpl_text\', \'' . $tag . '\');">' . $tag . '</button>';
		}, $special_tags_base);

		// convert conditional texts into HTML buttons, displayed under the text editor
		$condtext_tags = array_map(function($tag)
		{
			return '<button type="button" class="btn vbo-condtext-specialtag-btn" onclick="setCronTplTag(\'tpl_text\', \'' . $tag . '\');">' . $tag . '</button>';
		}, $condtext_tags);

		// build the default message value
		$def_message = !empty($this->widgetSettings->last_message) ? $this->widgetSettings->last_message : $this->getDefaultMessage();

		return $this->vbo_app->renderVisualEditor(
			'vbo_widget_bulkmess_mess',
			$def_message,
			[
				'class' => 'vbo-widget-bulkmess-messagecont',
				'style' => 'width: 96%; height: 150px;',
			],
			[
				'modes' => [
					'text',
					'visual',
				],
			],
			$editor_btns
		);
	}

	/**
	 * Returns the default message.
	 * 
	 * @return 	string
	 */
	private function getDefaultMessage()
	{
		$message 	  = '';
		$logo_html 	  = '';
		$sitelogo 	  = VBOFactory::getConfig()->get('sitelogo');
		$company_name = VikBooking::getFrontTitle();

		if ($sitelogo && is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources'. DIRECTORY_SEPARATOR . $sitelogo)) {
			$logo_html = '<p style="text-align: center;">'
				. '<img src="' . VBO_ADMIN_URI . 'resources/' . $sitelogo . '" alt="' . htmlspecialchars($company_name) . '" /></p>'
				. "\n";
		}

		$message = 
<<<HTML
$logo_html
<h1 style="text-align: center;">
	<span style="font-family: verdana;">$company_name</span>
</h1>
<hr class="vbo-editor-hl-mailwrapper">
<h4>Dear {customer_name},</h4>
<p><br></p>
<p>This is a message for your stay from {checkin_date} to {checkout_date}.</p>
<p><br></p>
<p><br></p>
<p>Thank you.</p>
<p>$company_name</p>
<hr class="vbo-editor-hl-mailwrapper">
<p><br></p>
HTML
		;

		return $message;
	}

	/**
	 * Sends a message to the guest through a regular email.
	 * 
	 * @param 	array 	$booking 	the reservation record to notify.
	 * @param 	string 	$message 	the message to send.
	 * @param 	string 	$subject 	the email subject.
	 * 
	 * @return 	string 				the message built and sent.
	 */
	private function notifyThroughEmail(array $booking, $message, $subject)
	{
		// fetch booked room details
		$booking_rooms = VikBooking::loadOrdersRoomsData($booking['id']);

		// translate contents
		$vbo_tn = VikBooking::getTranslator();
		$vbo_tn->translateContents($booking_rooms, '#__vikbooking_rooms', ['id' => 'idroom', 'name' => 'room_name']);

		$message = $this->parseCustomerEmailTemplate($message, $booking, $booking_rooms, $vbo_tn);

		$is_html = (strpos($message, '<') !== false || strpos($message, '</') !== false);
		if ($is_html && !preg_match("/(<\/?br\/?>)+/", $message)) {
			// when no br tags found, apply nl2br
			$message = nl2br($message);
		}

		// get sender email address
		$admin_sendermail = VikBooking::getSenderMail();

		if (!$this->vbo_app->sendMail($admin_sendermail, $admin_sendermail, $booking['custmail'], $admin_sendermail, $subject, $message, $is_html)) {
			VBOHttpDocument::getInstance()->close(500, 'Sending the email message to the booking ID ' . $booking['id'] . ' failed.');
		}

		return $message;
	}

	/**
	 * Sends a message to the guest through a regular email.
	 * 
	 * @param 	array 	$booking 	the reservation record to notify.
	 * @param 	string 	$message 	the message to send.
	 * 
	 * @return 	string 				the message built and sent.
	 */
	private function notifyThroughMessage(array $booking, $message)
	{
		// fetch booked room details
		$booking_rooms = VikBooking::loadOrdersRoomsData($booking['id']);

		// translate contents
		$vbo_tn = VikBooking::getTranslator();
		$vbo_tn->translateContents($booking_rooms, '#__vikbooking_rooms', ['id' => 'idroom', 'name' => 'room_name']);

		$message = $this->parseCustomerEmailTemplate($message, $booking, $booking_rooms, $vbo_tn);
		if (empty($message)) {
			VBOHttpDocument::getInstance()->close(500, 'Message for the booking ID ' . $booking['id'] . ' is empty.');
		}

		$messaging = VCMChatMessaging::getInstance($booking);
		$result = $messaging->setMessage($message)
			->sendGuestMessage();

		if (!$result && $error = $messaging->getError()) {
			// terminate with the error description
			VBOHttpDocument::getInstance()->close(500, "Message could not be sent to guest - Booking ID {$booking['id']} ({$booking['customer_name']}): {$error}");
		}

		return $message;
	}

	/**
	 * Composes the actual email message by parsing special tokens.
	 * 
	 * @param 	string 	$message 		the message content for the email.
	 * @param   array 	$booking 		booking array record.
	 * @param   array 	$booking_rooms 	list of rooms booked.
	 * @param 	array 	$vbo_tn 		translator object.
	 * 
	 * @return  string 					the email message content ready to be sent.
	 */
	private function parseCustomerEmailTemplate($message, $booking, $booking_rooms, $vbo_tn = null)
	{
		$tpl = $message;

		/**
		 * Parse all conditional text rules.
		 */
		VikBooking::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($tpl);
		//

		$vbo_df = VikBooking::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$tpl = str_replace('{customer_name}', $booking['customer_name'], $tpl);
		$tpl = str_replace('{booking_id}', $booking['id'], $tpl);
		$tpl = str_replace('{checkin_date}', date($df, $booking['checkin']), $tpl);
		$tpl = str_replace('{checkout_date}', date($df, $booking['checkout']), $tpl);
		$tpl = str_replace('{num_nights}', $booking['days'], $tpl);
		$rooms_booked = [];
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $broom) {
			if (array_key_exists($broom['room_name'], $rooms_booked)) {
				$rooms_booked[$broom['room_name']] += 1;
			} else {
				$rooms_booked[$broom['room_name']] = 1;
			}
			$tot_adults += (int)$broom['adults'];
			$tot_children += (int)$broom['children'];
			$tot_guests += ((int)$broom['adults'] + (int)$broom['children']);
		}
		$tpl = str_replace('{tot_adults}', $tot_adults, $tpl);
		$tpl = str_replace('{tot_children}', $tot_children, $tpl);
		$tpl = str_replace('{tot_guests}', $tot_guests, $tpl);
		$rooms_booked_quant = [];
		foreach ($rooms_booked as $rname => $quant) {
			$rooms_booked_quant[] = ($quant > 1 ? $quant.' ' : '').$rname;
		}
		$tpl = str_replace('{rooms_booked}', implode(', ', $rooms_booked_quant), $tpl);
		$tpl = str_replace('{total}', VikBooking::numberFormat($booking['total']), $tpl);
		$tpl = str_replace('{total_paid}', VikBooking::numberFormat($booking['totpaid']), $tpl);
		$remaining_bal = $booking['total'] - $booking['totpaid'];
		$tpl = str_replace('{remaining_balance}', VikBooking::numberFormat($remaining_bal), $tpl);
		$tpl = str_replace('{customer_pin}', $booking['customer_pin'], $tpl);

		$use_sid = empty($booking['sid']) && !empty($booking['idorderota']) ? $booking['idorderota'] : $booking['sid'];

		$bestitemid  = VikBooking::findProperItemIdType(['booking'], (!empty($booking['lang']) ? $booking['lang'] : null));
		$lang_suffix = $bestitemid && !empty($booking['lang']) ? '&lang=' . $booking['lang'] : '';
		$book_link 	 = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . $use_sid . "&ts=" . $booking['ts'] . $lang_suffix, false, (!empty($bestitemid) ? $bestitemid : null));

		$tpl = str_replace('{booking_link}', $book_link, $tpl);

		/**
		 * Rooms Distinctive Features parsing
		 */
		preg_match_all('/\{roomfeature ([a-zA-Z0-9 ]+)\}/U', $tpl, $matches);
		if (isset($matches[1]) && $matches[1]) {
			foreach ($matches[1] as $reqf) {
				$rooms_features = [];
				foreach ($booking_rooms as $broom) {
					$distinctive_features = [];
					$rparams = json_decode($broom['params'], true);
					if (array_key_exists('features', $rparams) && count($rparams['features']) > 0 && array_key_exists('roomindex', $broom) && !empty($broom['roomindex']) && array_key_exists($broom['roomindex'], $rparams['features'])) {
						$distinctive_features = $rparams['features'][$broom['roomindex']];
					}
					if (!count($distinctive_features)) {
						continue;
					}
					$feature_found = false;
					foreach ($distinctive_features as $dfk => $dfv) {
						if (stripos($dfk, $reqf) !== false) {
							$feature_found = $dfk;
							if (strlen(trim($dfk)) == strlen(trim($reqf))) {
								break;
							}
						}
					}
					if ($feature_found !== false && strlen((string)$distinctive_features[$feature_found])) {
						$rooms_features[] = $distinctive_features[$feature_found];
					}
				}
				if ($rooms_features) {
					$rpval = implode(', ', $rooms_features);
				} else {
					$rpval = '';
				}
				$tpl = str_replace("{roomfeature ".$reqf."}", $rpval, $tpl);
			}
		}

		return $tpl;
	}
}
