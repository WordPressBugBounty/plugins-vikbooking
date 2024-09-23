<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2023 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "booking details".
 * 
 * @since 	1.16.5 (J) - 1.6.5 (WP)
 */
class VikBookingAdminWidgetBookingDetails extends VikBookingAdminWidget
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

		$this->widgetName = JText::translate('VBMAINORDEREDIT');
		$this->widgetDescr = JText::translate('JSEARCH_TOOLS');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('address-card') . '"></i>';
		$this->widgetStyleName = 'light-blue';
	}

	/**
	 * Preload the necessary assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// load assets for contextual menu
		$this->vbo_app->loadContextMenuAssets();

		// JS lang def
		JText::script('VBODASHSEARCHKEYS');
		JText::script('VIKLOADING');
		JText::script('VBDASHUPRESONE');
		JText::script('VBOCHANNEL');
		JText::script('VBCOUPON');
		JText::script('VBCUSTOMERNOMINATIVE');
		JText::script('VBO_COPY');
		JText::script('VBO_COPIED');
		JText::script('VBO_CONF_RM_OVERBOOKING_FLAG');
	}

	/**
	 * Custom method for this widget only to load the booking details.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 */
	public function loadBookingDetails()
	{
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();

		$wrapper = $app->input->getString('wrapper', '');

		$booking_key = $app->input->getString('booking_key', '');
		$booking_id  = $app->input->getString('bid', '');

		if (empty($booking_key) && empty($booking_id)) {
			// no bookings found
			VBOHttpDocument::getInstance()->close(400, JText::translate('VBOREPORTSERRNORESERV'));
		}

		$q = $dbo->getQuery(true)
			->select($dbo->qn('o') . '.*')
			->from($dbo->qn('#__vikbooking_orders', 'o'));

		if (!empty($booking_id)) {
			// search by booking ID or OTA booking ID only
			if (preg_match("/^[0-9]+$/", (string)$booking_id)) {
            	// only numbers could be both website and OTA
				$q->where([
					$dbo->qn('o.id') . ' = ' . (int)$booking_id,
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($booking_id),
				], $glue = 'OR');
			} else {
				// alphanumeric IDs can only belong to an OTA reservation
            	$q->where($dbo->qn('o.idorderota') . ' = ' . $dbo->q($booking_id));
			}
		} else {
			// search by different values
			$q->where(1);
			if (stripos($booking_key, 'id:') === 0) {
				// search by ID or OTA ID
				$seek_parts = explode('id:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->andWhere([
					$dbo->qn('o.id') . ' = ' . $dbo->q($seek_value),
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($seek_value),
				], $glue = 'OR');
			} elseif (stripos($booking_key, 'otaid:') === 0) {
				// search by OTA Booking ID
				$seek_parts = explode('otaid:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->where($dbo->qn('o.idorderota') . ' = ' . $dbo->q($seek_value));
			} elseif (stripos($booking_key, 'coupon:') === 0) {
				// search by coupon code
				$seek_parts = explode('coupon:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->where($dbo->qn('o.coupon') . ' LIKE ' . $dbo->q("%{$seek_value}%"));
			} elseif (stripos($booking_key, 'name:') === 0) {
				// search by customer nominative
				$seek_parts = explode('name:', $booking_key);
				$seek_value = trim($seek_parts[1]);
				$q->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'));
				$q->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'));
				$q->where('CONCAT_WS(\' \', ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') LIKE ' . $dbo->q("%{$seek_value}%"));
			} else {
				// seek for various values
				$q->andWhere([
					$dbo->qn('o.id') . ' = ' . $dbo->q($booking_key),
					$dbo->qn('o.confirmnumber') . ' = ' . $dbo->q($booking_key),
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($booking_key),
				], $glue = 'OR');
			}
		}

		$q->order($dbo->qn('id') . ' DESC');

		$dbo->setQuery($q, 0, 1);
		$details = $dbo->loadAssoc();

		if (!$details) {
			// no bookings found
			VBOHttpDocument::getInstance()->close(404, JText::translate('VBOREPORTSERRNORESERV'));
		}

		// get customer information
		$cpin = VikBooking::getCPinInstance();
		$customer = $cpin->getCustomerFromBooking($details['id']);
		if ($customer && !empty($customer['country'])) {
			if (is_file(implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'countries', $customer['country'] . '.png']))) {
				$customer['country_img'] = '<img src="' . VBO_ADMIN_URI . 'resources/countries/' . $customer['country'] . '.png' . '" title="' . $customer['country'] . '" class="vbo-country-flag vbo-country-flag-left"/>';
			}
		}

		// availability helper
		$av_helper = VikBooking::getAvailabilityInstance();

		// get rooms
		$booking_rooms = VikBooking::loadOrdersRoomsData($details['id']);

		// guests and tariffs information
		$tars 		  = [];
		$guests 	  = [];
		$tot_adults   = 0;
		$tot_children = 0;
		$tot_pets 	  = 0;
		foreach ($booking_rooms as $k => $booking_room) {
			$guests[] = [
				'adults' 	   => $booking_room['adults'],
				'children' 	   => $booking_room['children'],
				'pets' 		   => $booking_room['pets'],
				't_first_name' => $booking_room['t_first_name'],
				't_last_name'  => $booking_room['t_last_name'],
			];

			if (!$k || !$details['split_stay']) {
				$tot_adults   += $booking_room['adults'];
				$tot_children += $booking_room['children'];
				$tot_pets 	  += $booking_room['pets'];
			}

			$q = $dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikbooking_dispcost'))
				->where($dbo->qn('id') . ' = ' . (int)$booking_room['idtar']);

			$dbo->setQuery($q, 0, 1);
			$tars[($k + 1)] = $dbo->loadAssocList();
		}

		// room stay dates in case of split stay (or modified room nights)
		$room_stay_dates   = [];
		$room_stay_records = [];
		if ($details['split_stay']) {
			if ($details['status'] == 'confirmed') {
				$room_stay_dates = $av_helper->loadSplitStayBusyRecords($details['id']);
			} else {
				$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $details['id'], []);
			}
		} elseif (!$details['split_stay'] && $details['roomsnum'] > 1 && $details['days'] > 1 && $details['status'] == 'confirmed') {
			// load the occupied stay dates for each room in case they were modified
			$room_stay_records = $av_helper->loadSplitStayBusyRecords($details['id']);
		}

		// currency
		$otacurrency  = !empty($details['channel']) && !empty($details['chcurrency']) ? $details['chcurrency'] : '';
		$currencysymb = VikBooking::getCurrencySymb();

		// channel name and reservation ID
		$otachannel_name = JText::translate('VBORDFROMSITE');
		$otachannel_bid  = '';
		if (!empty($details['channel'])) {
			$channelparts = explode('_', $details['channel']);
			$otachannel = isset($channelparts[1]) && strlen((string)$channelparts[1]) ? $channelparts[1] : ucwords($channelparts[0]);
			$otachannel_name = $otachannel;
			$otachannel_bid  = !empty($details['idorderota']) ? $details['idorderota'] : '';
		}

		// readable dates
		$checkin_info 	  = getdate($details['checkin']);
		$checkin_wday 	  = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
		$checkout_info 	  = getdate($details['checkout']);
		$checkout_wday 	  = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
		$checkin_read_dt  = $checkin_wday . ', ' . implode(' ', [$checkin_info['mday'], VikBooking::sayMonth($checkin_info['mon']), $checkin_info['year'], date('H:i', $details['checkin'])]);
		$checkout_read_dt = $checkout_wday . ', ' . implode(' ', [$checkout_info['mday'], VikBooking::sayMonth($checkout_info['mon']), $checkout_info['year'], date('H:i', $details['checkout'])]);

		// check for special requests
		$special_requests = VBOModelReservation::getInstance($details)->extractSpecialRequests();

		// check for guest messaging
		$messaging_supported = class_exists('VCMChatMessaging');
		$tot_guest_messages  = 0;
		$tot_unread_messages = 0;
		if ($messaging_supported) {
			$messaging_handler  = VCMChatMessaging::getInstance($details);
			$tot_guest_messages = $messaging_handler->countBookingGuestMessages();
			if ($tot_guest_messages) {
				$tot_unread_messages = $messaging_handler->countBookingGuestMessages($unread = true);
			}
		}

		// load the Channel Manager notifications for this booking, if any
		$cm_notifications = [];
		$cm_channels 	  = [];
		if (class_exists('VikChannelManager') && VikChannelManager::isAvailabilityRequest($api_channel = true)) {
			list($cm_notifications, $cm_channels) = $this->loadChannelManagerNotifications($details);
		}

		// check if we got data by clicking on a push/web notification
		$from_push = $this->options()->gotPushData();

		// start output buffering
		ob_start();

		?>
		<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact" data-bookingid="<?php echo $details['id']; ?>">
			<div class="vbo-params-wrap">
				<div class="vbo-params-container">

					<div class="vbo-params-block">
						<div class="vbo-param-container">
							<div class="vbo-param-setting">
							<?php
							$ch_logo_obj  = VikBooking::getVcmChannelsLogo($details['channel'], true);
							$channel_logo = is_object($ch_logo_obj) ? $ch_logo_obj->getSmallLogoURL() : '';
							$custpic_used = false;
							?>
								<div class="vbo-customer-info-box">
									<div class="vbo-customer-info-box-avatar vbo-customer-avatar-medium">
										<span>
										<?php
										if (!empty($channel_logo)) {
											// channel logo has got the highest priority
											?>
											<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $otachannel_name); ?>">
												<img src="<?php echo $channel_logo; ?>" onclick="vboWidgetBookDetsOpenBooking('<?php echo $details['id']; ?>');" />
											</span>
											<?php
										} elseif (!empty($customer['pic'])) {
											// customer profile picture
											$custpic_used = true;
											?>
											<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $otachannel_name); ?>">
												<img src="<?php echo strpos($customer['pic'], 'http') === 0 ? $customer['pic'] : VBO_SITE_URI . 'resources/uploads/' . $customer['pic']; ?>" onclick="vboWidgetBookDetsOpenBooking('<?php echo $details['id']; ?>');" />
											</span>
											<?php
										} else {
											// we use an icon as fallback
											?>
											<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $otachannel_name); ?>" onclick="vboWidgetBookDetsOpenBooking('<?php echo $details['id']; ?>');"><?php VikBookingIcons::e('hotel', 'vbo-dashboard-guest-activity-avatar-icon'); ?></span>
											<?php
										}
										?>
										</span>
									</div>
								</div>
								<span class="label label-info"><?php echo JText::translate('VBDASHUPRESONE') . ' ' . $details['id']; ?></span>
							<?php
							if (!empty($otachannel_bid)) {
								?>
								<span class="label label-info"><?php echo $otachannel_name . ' ' . $otachannel_bid; ?></span>
								<?php
							}
							$status_type = '';
							if (!empty($details['type'])) {
								$status_type = JText::translate('VBO_BTYPE_' . strtoupper($details['type']));
								if (!strcasecmp($details['type'], 'overbooking')) {
									$status_type = '<span class="label label-error vbo-label-nested vbo-label-overbooking" onclick="vboWidgetBookDetsToggleOverbooking(\'' . $wrapper . '\');">' . $status_type . '</span>';
								}
								$status_type .= ' / ';
							}
							$extra_status = $details['refund'] > 0 ? ' / ' . JText::translate('VBO_STATUS_REFUNDED') : '';
							if ($details['status'] == "confirmed") {
								$saystaus = '<span class="label label-success">' . $status_type . JText::translate('VBCONFIRMED') . $extra_status . '</span>';
							} elseif ($details['status'] == "standby") {
								$saystaus = '<span class="label label-warning">' . $status_type . JText::translate('VBSTANDBY') . $extra_status . '</span>';
							} else {
								$saystaus = '<span class="label label-error">' . $status_type . JText::translate('VBCANCELLED') . $extra_status . '</span>';
							}
							echo $saystaus;
							if ($details['closure']) {
								?>
								<span class="label label-error"><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBDBTEXTROOMCLOSED'); ?></span>
								<?php
							}
							?>
							</div>
						</div>

					<?php
					if (!$customer && !$details['closure'] && !empty($details['custdata'])) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-setting"><?php echo $details['custdata']; ?></div>
						</div>
						<?php
					}
					?>

						<div class="vbo-param-container">
							<div class="vbo-param-label">
								<div class="vbo-customer-info-box">
									<div class="vbo-customer-info-box-name">
										<?php echo (isset($customer['country_img']) ? $customer['country_img'] . ' ' : '') . ($customer ? ltrim($customer['first_name'] . ' ' . $customer['last_name']) : JText::translate('VBPVIEWORDERSPEOPLE')); ?>
									</div>
								<?php
								if (!$custpic_used && !empty($customer['pic'])) {
									$customer_name = $customer ? ltrim($customer['first_name'] . ' ' . $customer['last_name']) : '';
									?>
									<div class="vbo-customer-info-box-avatar vbo-customer-avatar-small vbo-widget-bookdets-cpic-zoom">
										<span>
											<img src="<?php echo strpos($customer['pic'], 'http') === 0 ? $customer['pic'] : VBO_SITE_URI . 'resources/uploads/' . $customer['pic']; ?>" data-caption="<?php echo JHtml::fetch('esc_attr', $customer_name); ?>" />
										</span>
									</div>
									<?php
								}
								?>
								</div>
							</div>
							<div class="vbo-param-setting">
							<?php
							$guest_counters = [];
							if ($tot_adults) {
								$guest_counters[] = $tot_adults . ' ' . JText::translate(($tot_adults > 1 ? 'VBMAILADULTS' : 'VBMAILADULT'));
							}
							if ($tot_children) {
								$guest_counters[] = $tot_children . ' ' . JText::translate(($tot_children > 1 ? 'VBMAILCHILDREN' : 'VBMAILCHILD'));
							}
							if ($tot_pets) {
								$guest_counters[] = $tot_pets . ' ' . JText::translate(($tot_pets > 1 ? 'VBO_PETS' : 'VBO_PET'));
							}
							echo implode(', ', $guest_counters);
							?>	
							</div>
						</div>

						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBPICKUPAT'); ?></div>
							<div class="vbo-param-setting">
								<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $checkin_read_dt); ?>"><?php echo date(str_replace("/", $this->datesep, $this->df), $details['checkin']); ?></span>
							</div>
						</div>

						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBRELEASEAT'); ?></div>
							<div class="vbo-param-setting">
								<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $checkout_read_dt); ?>"><?php echo date(str_replace("/", $this->datesep, $this->df), $details['checkout']); ?></span>
								<span>(<?php echo $details['days'] . ' ' . ($details['days'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?>)</span>
							<?php
							if ($details['split_stay']) {
								?>
								<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_SPLIT_STAY')); ?>"><?php VikBookingIcons::e('random'); ?></span>
								<?php
							}
							?>
							</div>
						</div>

						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBOINVCREATIONDATE'); ?></div>
							<div class="vbo-param-setting">
								<?php echo date(str_replace("/", $this->datesep, $this->df) . ' H:i', $details['ts']); ?>
							</div>
						</div>

					<?php
					if ($special_requests) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-setting">
								<blockquote class="vbo-booking-special-requests"><?php echo $special_requests; ?></blockquote>
							</div>
						</div>
						<?php
					}

					if ($tot_unread_messages) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label">&nbsp;</div>
							<div class="vbo-param-setting">
								<button type="button" class="btn vbo-config-btn vbo-btn-icon-right" data-hasguestmessages="1">
									<span><?php echo JText::translate('VBO_GUEST_MESSAGING'); ?></span>
									<span class="vbo-bookings-guestmessages-bubble-cont vbo-admin-tipsicon"><i class="<?php echo VikBookingIcons::i('comments'); ?>" data-message-count="<?php echo $tot_unread_messages; ?>"></i></span>
								</button>
							</div>
						</div>
						<?php
					} elseif ($tot_guest_messages) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label">&nbsp;</div>
							<div class="vbo-param-setting">
								<button type="button" class="btn vbo-config-btn vbo-btn-icon-right" data-hasguestmessages="1">
									<span><?php echo JText::translate('VBO_GUEST_MESSAGING'); ?> <?php VikBookingIcons::e('comment-dots'); ?></span>
								</button>
							</div>
						</div>
						<?php
					}
					?>
					</div>

					<div class="vbo-params-block">
					<?php
					foreach ($booking_rooms as $ind => $booking_room) {
						$num = $ind + 1;
						$room_icon = $details['split_stay'] && $ind > 0 ? 'random' : 'bed';
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label">
								<span><?php VikBookingIcons::e($room_icon); ?> <?php echo $booking_room['room_name']; ?></span>
							<?php
							// room sub-unit
							$room_number = '';
							if (!empty($booking_room['roomindex']) && !$details['closure'] && !empty($booking_room['params'])) {
								$room_params  = json_decode($booking_room['params'], true);
								$arr_features = [];
								if (is_array($room_params) && !empty($room_params['features']) && is_array($room_params['features'])) {
									// parse distinctive features
									foreach ($room_params['features'] as $rind => $rfeatures) {
										if ($rind != $booking_room['roomindex']) {
											continue;
										}
										foreach ($rfeatures as $fname => $fval) {
											if (strlen((string)$fval)) {
												$room_number = '#' . $fval;
												break 2;
											}
										}
									}
								}
							}
							if ($room_number) {
								?>
								<div>
									<small><?php echo $room_number; ?></small>
								</div>
								<?php
							}
							if ($details['roomsnum'] > 1 && !$details['split_stay'] && isset($guests[$ind])) {
								// print guest details for this room
								$room_guest_counters = [];
								if (!empty($guests[$ind]['t_first_name']) || !empty($guests[$ind]['t_last_name'])) {
									$room_guest_counters[] = trim($guests[$ind]['t_first_name'] . ' ' . $guests[$ind]['t_last_name']);
								}
								if ($guests[$ind]['adults']) {
									$room_guest_counters[] = $guests[$ind]['adults'] . ' ' . JText::translate(($guests[$ind]['adults'] > 1 ? 'VBMAILADULTS' : 'VBMAILADULT'));
								}
								if ($guests[$ind]['children']) {
									$room_guest_counters[] = $guests[$ind]['children'] . ' ' . JText::translate(($guests[$ind]['children'] > 1 ? 'VBMAILCHILDREN' : 'VBMAILCHILD'));
								}
								if ($guests[$ind]['pets']) {
									$room_guest_counters[] = $guests[$ind]['pets'] . ' ' . JText::translate(($guests[$ind]['pets'] > 1 ? 'VBO_PETS' : 'VBO_PET'));
								}
								?>
								<div>
									<small><?php echo implode(', ', $room_guest_counters); ?></small>
								</div>
								<?php
							}
							?>
							</div>
							<div class="vbo-param-setting">
								<div class="vbo-widget-bookdets-roomrate">
								<?php
								$active_rplan_id = 0;
								if (!empty($details['pkg']) || $booking_room['cust_cost'] > 0) {
									if (!empty($booking_room['pkg_name'])) {
										// package
										echo $booking_room['pkg_name'];
									} else {
										// custom cost can have an OTA Rate Plan name
										if (!empty($booking_room['otarplan'])) {
											echo ucwords($booking_room['otarplan']);
										} else {
											echo JText::translate('VBOROOMCUSTRATEPLAN');
										}
									}
								} elseif (!empty($tars[$num]) && !empty($tars[$num][0]['idprice'])) {
									$active_rplan_id = $tars[$num][0]['idprice'];
									echo VikBooking::getPriceName($tars[$num][0]['idprice']);
								} elseif (!empty($booking_room['otarplan'])) {
									echo ucwords($booking_room['otarplan']);
								} elseif (!$details['closure']) {
									echo JText::translate('VBOROOMNORATE');
								}
								?>
								</div>
							<?php
							// meals included in the room rate
							if (!empty($booking_room['meals'])) {
								// display included meals defined at room-reservation record
								$included_meals = VBOMealplanManager::getInstance()->roomRateIncludedMeals($booking_room);
							} else {
								// fetch default included meals in the selected rate plan
								$included_meals = $active_rplan_id ? VBOMealplanManager::getInstance()->ratePlanIncludedMeals($active_rplan_id) : [];
							}
							if (!$included_meals && empty($booking_room['meals']) && !empty($details['idorderota']) && !empty($details['channel']) && !empty($details['custdata'])) {
								// attempt to fetch the included meal plans from the raw customer data or OTA reservation and room
								$included_meals = VBOMealplanManager::getInstance()->otaDataIncludedMeals($details, $booking_room);
							}
							if ($included_meals) {
								?>
								<div class="vbo-widget-bookdets-roommeals vbo-wider-badges-wrap">
								<?php
								foreach ($included_meals as $included_meal) {
									?>
									<span class="badge badge-info"><?php echo $included_meal; ?></span>
									<?php
								}
								?>
								</div>
								<?php
							}

							// check for split-stay or modified stay-dates
							if ($details['split_stay'] && $room_stay_dates && isset($room_stay_dates[$ind]) && $room_stay_dates[$ind]['idroom'] == $booking_room['idroom']) {
								// print split stay information for this room
								$room_stay_checkin  = !empty($room_stay_dates[$ind]['checkin_ts']) ? $room_stay_dates[$ind]['checkin_ts'] : $room_stay_dates[$ind]['checkin'];
								$room_stay_checkout = !empty($room_stay_dates[$ind]['checkout_ts']) ? $room_stay_dates[$ind]['checkout_ts'] : $room_stay_dates[$ind]['checkout'];
								$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
								?>
								<div class="vbo-cal-splitstay-details vbo-bookdet-splitstay-details">
									<div class="vbo-cal-splitstay-dates">
										<span class="vbo-cal-splitstay-room-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
										<span class="vbo-cal-splitstay-dates-in"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $this->datesep, $this->df), $room_stay_checkin); ?></span>
										<span class="vbo-cal-splitstay-dates-out"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo date(str_replace("/", $this->datesep, $this->df), $room_stay_checkout); ?></span>
									</div>
								</div>
								<?php
							} elseif (!$details['split_stay'] && $room_stay_records && isset($room_stay_records[$ind]) && $room_stay_records[$ind]['idroom'] == $booking_room['idroom']) {
								// print modified stay dates information for this room
								$room_stay_checkin  = $room_stay_records[$ind]['checkin'];
								$room_stay_checkout = $room_stay_records[$ind]['checkout'];
								$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
								if ($room_stay_checkin != $details['checkin'] || $room_stay_checkout != $details['checkout']) {
									?>
								<div class="vbo-cal-splitstay-details vbo-bookdet-splitstay-details vbo-bookdet-roomdatesmod-details">
									<div class="vbo-cal-splitstay-dates">
										<span class="vbo-cal-splitstay-room-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
										<span class="vbo-cal-splitstay-dates-in"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $this->datesep, $this->df), $room_stay_checkin); ?></span>
										<span class="vbo-cal-splitstay-dates-out"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo date(str_replace("/", $this->datesep, $this->df), $room_stay_checkout); ?></span>
									</div>
								</div>
								<?php
								}
							}
							?>
							</div>
						</div>
						<?php
					}
					?>
					</div>

				<?php
				if (!$details['closure']) {
					?>
					<div class="vbo-params-block">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBEDITORDERNINE'); ?></div>
							<div class="vbo-param-setting">
								<strong><?php echo ($otacurrency ? "({$otacurrency}) " : '') . $currencysymb . ' ' . VikBooking::numberFormat($details['total']); ?></strong>
							</div>
						</div>
					<?php
					if ($details['totpaid'] > 0) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBPEDITBUSYTOTPAID'); ?></div>
							<div class="vbo-param-setting">
								<span><?php echo $currencysymb . ' ' . VikBooking::numberFormat($details['totpaid']); ?></span>
							</div>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}

					// check for administrator notes
					if (!empty($details['adminnotes'])) {
						?>
					<div class="vbo-params-fieldset">
						<div class="vbo-params-fieldset-label"><?php echo JText::translate('VBADMINNOTESTOGGLE'); ?></div>
						<div class="vbo-params-block">
							<div class="vbo-param-container">
								<div class="vbo-param-setting">
									<blockquote class="vbo-booking-admin-notes"><?php echo nl2br($details['adminnotes']); ?></blockquote>
								</div>
							</div>
						</div>
					</div>
						<?php
					}

					// booking history
					$history_obj  = VikBooking::getBookingHistoryInstance($details['id']);
					$history_list = $history_obj->loadHistory();
					if ($history_list) {
						?>
					<div class="vbo-params-fieldset vbo-widget-bookdets-history-list">
						<div class="vbo-params-fieldset-label"><?php echo JText::translate('VBOBOOKHISTORYTAB'); ?></div>
						<div class="vbo-params-block">
						<?php
						$max_display_records = 3;
						foreach ($history_list as $hind => $hist) {
							$html_descr = strpos($hist['descr'], '<') !== false ? $hist['descr'] : nl2br($hist['descr']);
							$text_descr = strip_tags($hist['descr']);
							$text_lines = preg_split("/[\r\n]/", $text_descr);
							$read_more  = false;
							if ($text_lines && count($text_lines) > 1) {
								$first_line = $text_lines[0];
								unset($text_lines[0]);
								if (strlen($first_line) < strlen(implode('', $text_lines))) {
									$text_descr = $first_line;
									$read_more  = true;
								}
							}

							$tip_info = [
								JText::translate('VBOBOOKHISTORYLBLTOT') . ': ' . $currencysymb . ' ' . VikBooking::numberFormat($hist['total']),
								JText::translate('VBOBOOKHISTORYLBLTPAID') . ': ' . $currencysymb . ' ' . VikBooking::numberFormat($hist['totpaid']),
							];
							?>
							<div class="vbo-param-container vbo-widget-bookdets-history-record" style="<?php echo $hind >= $max_display_records ? 'display: none;' : ''; ?>">
								<div class="vbo-param-label">
									<div>
										<span class="vbo-tooltip vbo-tooltip-top vbo-widget-bookdets-history-evname" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', implode(', ', $tip_info)); ?>"><?php echo $history_obj->validType($hist['type'], true); ?></span>
									</div>
									<div>
										<span class="vbo-widget-bookdets-history-evdate"><?php echo JHtml::fetch('date', $hist['dt'], 'Y-m-d H:i:s'); ?></span>
									</div>
								</div>
								<div class="vbo-param-setting vbo-widget-bookdets-history-descr-wrap">
								<?php
								if ($read_more) {
									?>
									<div class="vbo-widget-bookdets-history-descr-txt">
										<span><?php echo $text_descr; ?></span>
										<div>
											<span class="vbo-tooltip vbo-tooltip-top vbo-widget-bookdets-history-descr-more" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_READ_MORE')); ?>" onclick="vboWidgetBookDetsReadFullHistory(this);">
												<a href="javascript:void(0);">[...]</a>
											</span>
										</div>
									</div>
									<?php
								}
								?>
									<div class="vbo-widget-bookdets-history-descr-html" style="<?php echo $read_more ? 'display: none;' : ''; ?>"><?php echo $html_descr; ?></div>
								</div>
							</div>
							<?php
						}

						if (count($history_list) > $max_display_records) {
							// display button to display all history records
							?>
							<div class="vbo-param-container vbo-widget-bookdets-history-showall">
								<div class="vbo-param-label">&nbsp;</div>
								<div class="vbo-param-setting">
									<button type="button" class="btn vbo-config-btn" onclick="vboWidgetBookDetsHistoryAll('<?php echo $wrapper; ?>');"><?php VikBookingIcons::e('history'); ?> <?php echo JText::translate('VBO_SEE_ALL'); ?></button>
								</div>
							</div>
							<?php
						}
						?>
						</div>
					</div>
						<?php
					}

					// channel manager notifications and channels involved
					if ($cm_notifications) {
						?>
					<div class="vbo-params-fieldset">
						<div class="vbo-params-fieldset-label"><?php echo JText::translate('VBOBOOKHISTORYTCM'); ?></div>
						<div class="vbo-params-block">
						<?php
						$max_display_notifications = 2;
						foreach ($cm_notifications as $notif_ind => $cm_notification) {
							$badge_class = 'success';
							$badge_icon  = 'check-circle';
							if ($cm_notification['type'] == 0) {
								$badge_class = 'error';
								$badge_icon  = 'times-circle';
							} elseif ($cm_notification['type'] == 2) {
								$badge_class = 'warning';
								$badge_icon  = 'exclamation-triangle';
							}
							?>
							<div class="vbo-params-fieldset vbo-widget-bookdets-cmnotifs-record" style="<?php echo $notif_ind >= $max_display_notifications ? 'display: none;' : ''; ?>">
								<div class="vbo-params-fieldset-label">
									<span class="label label-<?php echo $badge_class; ?>">
										<?php VikBookingIcons::e($badge_icon); ?>
										<span><?php echo date(str_replace("/", $this->datesep, $this->df) . ' H:i:s', $cm_notification['ts']); ?></span>
									</span>
								</div>
								<div class="vbo-params-block vbo-params-block-compact">
								<?php
								foreach ($cm_notification['children'] as $channel_key => $channel_notifs) {
									$logo_obj = VikBooking::getVcmChannelsLogo($cm_channels[$channel_key]['name'], true);
									$channel_logo = $logo_obj ? $logo_obj->getSmallLogoURL() : '';
									if (!$channel_logo) {
										continue;
									}
									// build readable channel name
									$raw_ch_name  = (string)$cm_channels[$channel_key]['name'];
									$lower_name   = strtolower($raw_ch_name);
									$lower_name   = preg_replace("/hotel$/", ' hotel', $lower_name);
									$channel_name = ucwords(preg_replace("/api$/", '', $lower_name));
									?>
									<div class="vbo-param-container">
										<div class="vbo-param-label">
											<div class="vbo-customer-info-box">
												<div class="vbo-customer-info-box-avatar vbo-customer-avatar-small">
													<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $channel_name); ?>">
														<img src="<?php echo $channel_logo; ?>" />
													</span>
												</div>
											</div>
										</div>
										<div class="vbo-param-setting">
										<?php
										$channel_responses = [];
										foreach ($channel_notifs as $resp_ind => $channel_notif) {
											// build update result string
											$upd_result = (string)$channel_notif['cont'];
											// strip "e4j." from the beginning of the string
											$upd_result = preg_replace("/^e4j./i", '', $upd_result);
											// strip ending hotel code "{hotelid 000000}"
											$upd_result = preg_replace("/\s?\{hotelid\s?[0-9A-Z]+\}$/i", '', $upd_result);

											// default values for extra logging
											$log_result = '';
											$ruid_codes = [];

											// we expect this to be a channel update result
											if ($resp_ind) {
												// parse the response type
												if (preg_match("/^OK\.[A-Z\.]+\.AR_RS/i", $upd_result, $matches)) {
													// successful response example ("OK.Airbnb.AR_RS")
													$upd_result = trim(str_replace($matches[0], "OK {$channel_name}", $upd_result));
												} elseif (preg_match("/^warning\.[A-Z\.]+\.AR_RS/i", $upd_result, $matches)) {
													// warning response example ("warning.Airbnb.AR_RS")
													$upd_result = trim(str_replace($matches[0], $channel_name, $upd_result));
													$log_result = 'warning';
												} elseif (preg_match("/^error\.[A-Z\.]+\.AR_RS/i", $upd_result, $matches)) {
													// error response example ("error.Airbnb.AR_RS")
													$upd_result = trim(str_replace($matches[0], $channel_name, $upd_result));
													$log_result = 'error';
												}

												// check for RUID codes (there could be more than one)
												if (preg_match_all("/RUID: \[.+\]/", $upd_result, $ruid_matches)) {
													$ruid_codes = is_array($ruid_matches[0]) ? $ruid_matches[0] : [];
													foreach ($ruid_matches[0] as $ruid_log) {
														// strip log from update result because it will be displayed separately
														$upd_result = str_replace($ruid_log, '', $upd_result);
													}
												}
											}

											if (in_array($upd_result, $channel_responses)) {
												// do not display duplicate responses from the same channel (i.e. duplicate unexpected notifications)
												continue;
											}

											// register channel response
											$channel_responses[] = $upd_result;

											?>
											<div class="vbo-widget-bookdets-cm-updresult">
											<?php
											// arrow icon
											VikBookingIcons::e(($resp_ind === 0 ? 'long-arrow-right' : 'long-arrow-left'));

											// print a label in case of errors or warning
											if ($log_result) {
												?>
												<span class="label label-<?php echo $log_result == 'warning' ? 'warning' : 'error'; ?>"><?php echo $log_result; ?></span>
												<?php
											}
											?>
												<span class="vbo-widget-bookdets-cm-updresult-log"><?php echo $upd_result; ?></span>
											<?php
											// check for extra logs (RUIDs)
											if ($ruid_codes) {
												?>
												<span class="vbo-widget-bookdets-cm-ruids">
													<button class="btn btn-small vbo-btn-icon-right" type="button" onclick="vboWidgetBookDetsCopyRuids(this);">
														<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_COPY')); ?>">RUID <?php VikBookingIcons::e('copy'); ?></span>
													</button>
												</span>
												<textarea class="vbo-textarea-copyable"><?php echo htmlentities(implode("\n", $ruid_codes)); ?></textarea>
												<?php
											}
											?>
											</div>
											<?php
										}

										// check if the channel was notified but did not respond
										if ($channel_responses && (count($channel_responses) % 2) != 0) {
											?>
											<div><?php echo str_repeat('-', 6); ?></div>
											<?php
										}
										?>
										</div>
									</div>
									<?php
								}
								?>
								</div>
							</div>
							<?php
						}

						// check if some notifications were hidden
						if (count($cm_notifications) > $max_display_notifications) {
							// display button to show all channel manager notifications
							?>
							<div class="vbo-param-container vbo-widget-bookdets-cmnotifs-showall">
								<div class="vbo-param-label">&nbsp;</div>
								<div class="vbo-param-setting">
									<button type="button" class="btn vbo-config-btn" onclick="vboWidgetBookDetsCmnotifsAll('<?php echo $wrapper; ?>');"><?php VikBookingIcons::e('network-wired'); ?> <?php echo JText::translate('VBO_SEE_ALL'); ?></button>
								</div>
							</div>
							<?php
						}
						?>
						</div>
					</div>
						<?php
					}

					// check if this booking has got a reminder
					$reminders_helper = VBORemindersHelper::getInstance();
					$has_reminders 	  = $reminders_helper->bookingHasReminder($details['id']);
					$few_reminders 	  = [];
					if ($has_reminders) {
						$few_reminders = $reminders_helper->loadReminders([
							'idorder' 	=> $details['id'],
							'onlyorder' => 1,
							'completed' => 1,
							'expired' 	=> 1,
						], 0, 5);
					}
					?>
					<div class="vbo-params-fieldset">
						<div class="vbo-params-fieldset-label"><?php echo JText::translate('VBO_W_REMINDERS_TITLE'); ?></div>
						<div class="vbo-params-block">
						<?php
						foreach ($few_reminders as $reminder) {
							$diff_data = [];
							if (!empty($reminder->duedate)) {
								// calculate distance to expiration date from today
								$diff_data = $reminders_helper->relativeDatesDiff($reminder->duedate);
							}
							?>
							<div class="vbo-param-container">
								<div class="vbo-param-setting">
									<div class="vbo-widget-reminders-record-info">
										<div class="vbo-widget-reminders-record-txt">
											<div class="vbo-widget-reminder-title"><?php echo htmlspecialchars($reminder->title); ?></div>
											<?php
											if (!empty($reminder->descr)) {
												?>
											<div class="vbo-widget-reminder-descr"><?php echo htmlspecialchars($reminder->descr); ?></div>
												<?php
											}
											?>
										</div>
										<div class="vbo-widget-reminders-record-due">
										<?php
										if (!empty($reminder->duedate)) {
											?>
											<div class="vbo-widget-reminders-record-due-datetime">
												<span class="vbo-widget-reminders-record-due-date">
													<span title="<?php echo $reminder->duedate; ?>"><?php echo $diff_data['relative']; ?></span>
												</span>
											<?php
											if ($reminder->usetime) {
												?>
												<span class="vbo-widget-reminders-record-due-time">
													<span><?php echo $diff_data['date_a']->format('H:i'); ?></span>
												</span>
												<?php
											}
											?>
											</div>
											<?php
										}
										?>
										</div>
									</div>
								</div>
							</div>
							<?php
						}
						?>
							<div class="vbo-param-container">
								<div class="vbo-param-label">&nbsp;</div>
								<div class="vbo-param-setting">
								<?php
								if ($has_reminders) {
									?>
									<button type="button" class="btn vbo-config-btn" data-hasreminders="1"><?php VikBookingIcons::e('bell'); ?> <?php echo JText::translate('VBO_SEE_ALL'); ?></button>
									<?php
								} else {
									?>
									<button type="button" class="btn btn-success" data-hasreminders="0"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_ADD_NEW'); ?></button>
									<?php
								}
								?>
								</div>
							</div>
						</div>
					</div>

					<div class="vbo-param-container">
						<div class="vbo-param-setting">
							<button type="button" class="btn vbo-config-btn" onclick="vboWidgetBookDetsOpenBooking('<?php echo $details['id']; ?>');"><?php echo JText::translate('VBOVIEWBOOKINGDET'); ?></button>
							<button type="button" class="btn" onclick="vboWidgetBookDetsOpenBooking('<?php echo $details['id']; ?>', 'edit');"><?php echo JText::translate('VBMODRES'); ?></button>
						</div>
					</div>

				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(function() {

				jQuery('#<?php echo $wrapper; ?>').find('button[data-hasreminders]').on('click', function() {
					// check if the clicked button indicates that there are reminders
					let has_reminders = jQuery(this).attr('data-hasreminders') == '1';

					// render the reminders widget by injecting the proper options
					VBOCore.handleDisplayWidgetNotification({widget_id: 'reminders'}, {
						bid: <?php echo $details['id']; ?>,
						action: has_reminders ? '' : 'add_new',
						completed: has_reminders ? 1 : 0,
						expired: has_reminders ? 1 : 0,
						modal_options: {
							/**
							 * Overwrite modal options for rendering the admin widget.
							 * We need to use a different suffix in case this current widget was
							 * also rendered within a modal, or it would get dismissed in favour
							 * of the newly opened admin widget. Prepending to body is needed
							 * because the reminders widget use the datepicker calendar.
							 */
							suffix: 'widget_modal_inner_reminders',
							body_prepend: true,
						},
					});
				});

				jQuery('#<?php echo $wrapper; ?>').find('button[data-hasguestmessages="1"]').on('click', function() {
					// render the guest messages widget by injecting the proper options
					VBOCore.handleDisplayWidgetNotification({widget_id: 'guest_messages'}, {
						bid: <?php echo $details['id']; ?>,
						modal_options: {
							/**
							 * Overwrite modal options for rendering the admin widget.
							 * We need to use a different suffix in case this current widget was
							 * also rendered within a modal, or it would get dismissed in favour
							 * of the newly opened admin widget.
							 */
							suffix: 'widget_modal_inner_guest_messages',
						},
					});
				});

				jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookdets-cpic-zoom').find('img').on('click', function() {
					// display modal
					VBOCore.displayModal({
						suffix: 'zoom-image',
						title: jQuery(this).attr('data-caption'),
						body: jQuery(this).clone(),
					});
				});

				// check for action button
				var vbo_widget_bookdets_action_btn = jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookdets-actionbtn');
				if (vbo_widget_bookdets_action_btn.length) {
					vbo_widget_bookdets_action_btn.attr('href', vbo_widget_bookdets_action_btn.attr('href').replace('%d', '<?php echo $details['id']; ?>'));
					vbo_widget_bookdets_action_btn.closest('.vbo-widget-push-notification-action').show();
				}

			});
		<?php
		if ($from_push) {
			// emit the event to read all notifications in the current context when clicking on a push/web notification
			?>
			setTimeout(() => {
				VBOCore.emitEvent('vbo-nc-read-notifications', {
					criteria: {
						group:   '<?php echo !empty($details['idorderota']) && !empty($details['channel']) ? 'otas' : 'website'; ?>',
						idorder: '<?php echo $details['id']; ?>',
					}
				});
			}, 200);
			<?php
		}
		?>
		</script>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return [
			'html' => $html_content,
		];
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
		$wrapper_id = 'vbo-widget-bookdets-' . $wrapper_instance;

		// get permissions
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_bookings) {
			// display nothing
			return;
		}

		// check multitask data
		$load_bid 			= 0;
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
			 * This widget should not get the current ID from Multitask data
			 * to avoid displaying duplicate contents, it should rather get
			 * the ID from the injected options (i.e. new Push notification).
			 */
			$load_bid = $this->options()->fetchBookingId();
		}

		// check if we got data by clicking on a push notification from the ServiceWorker
		$from_push = $this->options()->gotPushData();

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>" data-loadbid="<?php echo $load_bid; ?>">

			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
				</div>
			</div>

			<div class="vbo-widget-bookdets-wrap">
				<div class="vbo-widget-bookdets-inner">

					<div class="vbo-widget-element-filter-top">
						<div class="btn-group-inline">
							<button type="button" class="btn btn-secondary vbo-context-menu-btn vbo-widget-bookdets-searchtype">
								<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
							</button>
							<input type="text" name="booking_key" value="<?php echo $load_bid ? "id: {$load_bid}" : ''; ?>" placeholder="<?php echo htmlspecialchars(JText::translate('VBOFILTCONFNUMCUST')); ?>" autocomplete="off" />
							<button type="button" class="btn vbo-config-btn" onclick="vboWidgetBookDetsSearch('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('search'); ?> <span class="vbo-widget-bookdets-searchbtn"><?php echo JText::translate('VBODASHSEARCHKEYS'); ?></span></button>
						</div>
					</div>

				<?php
				// check if we need to display the Push notification message
				if ($from_push) {
					// get the title and message
					$push_title   = $this->getOption('title', '');
					$push_message = $this->getOption('message', '');

					// check for notification action and "from"
					$push_action = $this->getOption('action', '');
					$push_from 	 = $this->getOption('from', '');

					// notification severity
					$push_severity = $this->getOption('severity', 'info');

					// replace new line characters with a white space
			        $push_message = preg_replace("/[\r\n]/", ' ', $push_message);

			        // get rid of white space characters (only white space, no new lines or tabs like "\s") used more than once in a row
			        $push_message = preg_replace("/ {2,}/", ' ', $push_message);
					?>
					<div class="vbo-widget-push-notification vbo-widget-push-notification-<?php echo $push_severity; ?>">
					<?php
					if (!empty($push_title)) {
						?>
						<div class="vbo-widget-push-notification-title">
							<strong><?php echo $push_title; ?></strong>
						</div>
						<?php
					}
					if (!empty($push_message)) {
						?>
						<div class="vbo-widget-push-notification-message">
							<span><?php echo $push_message; ?></span>
						</div>
						<?php
					}
					if ($push_action && !strcasecmp($push_from, 'airbnb')) {
						// supported action
						if (!strcasecmp($push_action, 'host_guest_review')) {
							// prepare link to trigger the host-to-guest review action
							?>
						<div class="vbo-widget-push-notification-action" style="display: none;">
							<a class="btn vbo-config-btn vbo-widget-bookdets-actionbtn" href="<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=%d&notif_action=airbnb_host_guest_review', $xhtml = false); ?>" target="_blank"><?php VikBookingIcons::e('star'); ?> <?php echo JText::translate('VBO_REVIEW_YOUR_GUEST'); ?></a>
						</div>
							<?php
						} elseif (!strcasecmp($push_action, 'new_guest_review')) {
							// prepare link to display the guest review received
							?>
						<div class="vbo-widget-push-notification-action" style="display: none;">
							<a class="btn vbo-config-btn vbo-widget-bookdets-actionbtn" href="<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=%d&notif_action=see_guest_review', $xhtml = false); ?>" target="_blank"><?php VikBookingIcons::e('star'); ?> <?php echo JText::translate('VBOSEEGUESTREVIEW'); ?></a>
						</div>
							<?php
						}
					}
					?>
					</div>
					<?php
				}
				?>

					<div class="vbo-widget-element-body"></div>

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
		<a class="vbo-widget-bookdets-basenavuri" href="<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=%d', $xhtml = false); ?>" style="display: none;"></a>
		<a class="vbo-widget-editbook-basenavuri" href="<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editbusy&cid[]=%d', $xhtml = false); ?>" style="display: none;"></a>

		<script type="text/javascript">

			/**
			 * Open the booking details (or edit booking) page for the clicked reservation.
			 */
			function vboWidgetBookDetsOpenBooking(id, edit) {
				var open_url = jQuery((edit ? '.vbo-widget-editbook-basenavuri' : '.vbo-widget-bookdets-basenavuri')).first().attr('href');
				open_url = open_url.replace('%d', id);
				// navigate in a new tab
				window.open(open_url, '_blank');
			}

			/**
			 * Searches for a booking according to input filter.
			 */
			function vboWidgetBookDetsSearch(wrapper, options, bid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var booking_key = widget_instance.find('input[name="booking_key"]').val();

				// show loading
				widget_instance.find('.vbo-widget-bookdets-searchbtn').text(Joomla.JText._('VIKLOADING'));

				// the widget method to call
				var call_method = 'loadBookingDetails';

				// make a request to load the booking details
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						bid: bid,
						booking_key: booking_key,
						_options: options,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						// hide loading
						widget_instance.find('.vbo-widget-bookdets-searchbtn').text(Joomla.JText._('VBODASHSEARCHKEYS'));
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								widget_instance.find('.vbo-widget-element-body').html('');
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// replace HTML with new content
							widget_instance.find('.vbo-widget-element-body').html(obj_res[call_method]['html']);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// hide loading
						widget_instance.find('.vbo-widget-bookdets-searchbtn').text(Joomla.JText._('VBODASHSEARCHKEYS'));
						// display no bookings message
						widget_instance.find('.vbo-widget-element-body').html((error.status != 400 ? '<p class="err">' + error.responseText + '</p>' : ''));
					}
				);
			}

			/**
			 * Attempts to load a booking ID from the injected options.
			 */
			function vboWidgetBookDetsLoad(wrapper, options) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var load_bid = widget_instance.attr('data-loadbid');
				if (!load_bid || load_bid == '0') {
					return false;
				}

				// load booking
				vboWidgetBookDetsSearch(wrapper, options, load_bid);
			}

			/**
			 * Prepares the input search field with the proper type hint.
			 */
			function vboWidgetBookDetsSearchType(wrapper, search_hint) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				if (!search_hint) {
					search_hint = '';
				} else {
					search_hint += ': ';
				}

				widget_instance.find('input[name="booking_key"]').val(search_hint).focus();
			}

			/**
			 * Displays the full history record description.
			 */
			function vboWidgetBookDetsReadFullHistory(btn) {
				var descr_wrapper = jQuery(btn).closest('.vbo-widget-bookdets-history-descr-wrap');
				descr_wrapper.find('.vbo-widget-bookdets-history-descr-txt').hide();
				descr_wrapper.find('.vbo-widget-bookdets-history-descr-html').show();
			}

			/**
			 * Displays all booking history records.
			 */
			function vboWidgetBookDetsHistoryAll(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show all records
				widget_instance.find('.vbo-widget-bookdets-history-record').show();

				// hide button
				widget_instance.find('.vbo-widget-bookdets-history-showall').hide('.');
			}

			/**
			 * Display all channel manager notifications.
			 */
			function vboWidgetBookDetsCmnotifsAll(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show all records
				widget_instance.find('.vbo-widget-bookdets-cmnotifs-record').show();

				// hide button
				widget_instance.find('.vbo-widget-bookdets-cmnotifs-showall').hide('.');
			}

			/**
			 * Copies to clipboard the Booking.com RUID response codes, if any.
			 */
			function vboWidgetBookDetsCopyRuids(btn) {
				var tarea = btn.closest('.vbo-widget-bookdets-cm-updresult').querySelector('.vbo-textarea-copyable');
				VBOCore.copyToClipboard(tarea).then((success) => {
					jQuery(btn).find('.vbo-tooltip').attr('data-tooltiptext', Joomla.JText._('VBO_COPIED') + '!');
				}).catch((err) => {
					alert('Could not copy the logs');
				});
			}

			/**
			 * Toggles the overbooking status-type.
			 */
			function vboWidgetBookDetsToggleOverbooking(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var bid = widget_instance.find('[data-bookingid]').attr('data-bookingid');

				if (confirm(Joomla.JText._('VBO_CONF_RM_OVERBOOKING_FLAG'))) {
					VBOCore.doAjax(
						"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.delete_type_flag'); ?>",
						{
							bid: bid,
							flag: 'overbooking',
						},
						(success) => {
							// remove "overbooking" label
							let parent_label = widget_instance.find('.vbo-label-overbooking').parent('.label');
							widget_instance.find('.vbo-label-overbooking').remove();
							parent_label.text(parent_label.text().replace(' / ', ''));

							// turn flag on for the booking modified
							vbo_widget_book_dets_last_mod_bid = bid;
						},
						(error) => {
							alert(error.responseText);
						}
					);
				}
			}

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			// holds the lastly modified booking ID
			var vbo_widget_book_dets_last_mod_bid = null;

			jQuery(function() {

				// when document is ready, load contents for this widget's instance
				vboWidgetBookDetsLoad('<?php echo $wrapper_id; ?>', <?php echo json_encode($this->getOptions()); ?>);

				// register keyup event for auto-submit
				let input_search = document.querySelector('#<?php echo $wrapper_id; ?> input[name="booking_key"]');
				if (input_search) {
					input_search.addEventListener('keyup', (e) => {
						if (e.key === 'Enter') {
							return vboWidgetBookDetsSearch('<?php echo $wrapper_id; ?>');
						}
						if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
							// match a valid booking ID to increase or decrease
							let search_val = input_search.value;
							let rgx_id = new RegExp(/^(id:)?\s?[0-9]+$/);
							if (search_val && search_val.match(rgx_id)) {
								let rgx_num = new RegExp(/[^0-9]/g);
								let raw_idn = parseInt(search_val.replace(rgx_num, ''));
								if (!isNaN(raw_idn)) {
									// replace value and start search
									input_search.value = search_val.replace(raw_idn, (e.key === 'ArrowUp' ? (raw_idn + 1) : (raw_idn - 1)));
									return vboWidgetBookDetsSearch('<?php echo $wrapper_id; ?>');
								}
							}
						}
					});
				}

				// render context menu
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-bookdets-searchtype').vboContextMenu({
					placement: 'bottom-left',
					buttons: [
						{
							icon: '<?php echo VikBookingIcons::i('search'); ?>',
							text: Joomla.JText._('VBDASHUPRESONE'),
							separator: false,
							action: (root, config) => {
								vboWidgetBookDetsSearchType('<?php echo $wrapper_id; ?>', 'id');
							},
						},
						{
							icon: '<?php echo VikBookingIcons::i('search'); ?>',
							text: Joomla.JText._('VBDASHUPRESONE') + ' (' + Joomla.JText._('VBOCHANNEL') + ')',
							separator: false,
							action: (root, config) => {
								vboWidgetBookDetsSearchType('<?php echo $wrapper_id; ?>', 'otaid');
							},
						},
						{
							icon: '<?php echo VikBookingIcons::i('user-tag'); ?>',
							text: Joomla.JText._('VBCOUPON'),
							separator: false,
							action: (root, config) => {
								vboWidgetBookDetsSearchType('<?php echo $wrapper_id; ?>', 'coupon');
							},
						},
						{
							icon: '<?php echo VikBookingIcons::i('user'); ?>',
							text: Joomla.JText._('VBCUSTOMERNOMINATIVE'),
							separator: false,
							action: (root, config) => {
								vboWidgetBookDetsSearchType('<?php echo $wrapper_id; ?>', 'name');
							},
						},
					],
				});

				// subscribe to the multitask-panel-close event to emit the event for the lastly modified booking ID
				document.addEventListener(VBOCore.multitask_close_event, function() {
					if (vbo_widget_book_dets_last_mod_bid) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_booking_modified', {
							bid: vbo_widget_book_dets_last_mod_bid
						});
					}
				});

			<?php
			if ($is_modal_rendering) {
				// focus search input field & register to the event emitted when reminders have changed
				?>
				setTimeout(() => {
					jQuery('#<?php echo $wrapper_id; ?>').find('input[name="booking_key"]').focus();
				}, 400);

				var vbo_widget_bookdets_watch_reminders_fn = (e) => {
					if (!e || !e.detail || !e.detail.hasOwnProperty('bid') || !e.detail['bid']) {
						return;
					}
					let booking_element = jQuery('#<?php echo $wrapper_id; ?>').find('[data-bookingid]').first();
					if (booking_element.length && booking_element.attr('data-bookingid') == e.detail['bid']) {
						// reload current booking details
						vboWidgetBookDetsSearch('<?php echo $wrapper_id; ?>');
					}
				};

				document.addEventListener('vbo_reminders_changed', vbo_widget_bookdets_watch_reminders_fn);

				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_modal_id; ?>', (e) => {
					document.removeEventListener('vbo_reminders_changed', vbo_widget_bookdets_watch_reminders_fn);
				});

				// subscribe to the modal-dismissed event to emit the event for the lastly modified booking ID
				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_modal_id; ?>', function() {
					if (vbo_widget_book_dets_last_mod_bid) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_booking_modified', {
							bid: vbo_widget_book_dets_last_mod_bid
						});
					}
				});
				<?php
			}
			?>

			});

		</script>

		<?php
	}

	/**
	 * Loads a list of Channel Manager notifications and channels for the given booking.
	 * 
	 * @param 	array 	$booking 	the current booking record.
	 * 
	 * @return 	array 				list of notifications and channels involved.
	 */
	protected function loadChannelManagerNotifications(array $booking)
	{
		$dbo = JFactory::getDbo();

		$channels = [];

		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikchannelmanager_notifications'))
			->where($dbo->qn('from') . ' = ' . $dbo->q('VCM'))
			->where($dbo->qn('idordervb') . ' = ' . (int)$booking['id'])
			->order($dbo->qn('ts') . ' DESC');

		$dbo->setQuery($q);
		$parents = $dbo->loadAssocList();

		foreach ($parents as $k => $parent) {
			$q = $dbo->getQuery(true)
				->select($dbo->qn(['type', 'cont', 'channel']))
				->from($dbo->qn('#__vikchannelmanager_notification_child'))
				->where($dbo->qn('id_parent') . ' = ' . (int)$parent['id'])
				->order($dbo->qn('id') . ' ASC');

			$dbo->setQuery($q);
			$children = $dbo->loadAssocList();

			// set children notifications by grouping them under each channel
			$parents[$k]['children'] = [];

			// fetch the details for each channel involved
			foreach ($children as $ind => $child) {
				if (empty($child['channel'])) {
					continue;
				}

				$channel = VikChannelManager::getChannel($child['channel']);
				if (!$channel) {
					// we don't want to list a notification with no channel details
					unset($children[$ind]);
					continue;
				}

				// set channel details
				$channels[$child['channel']] = $channel;

				if (!isset($parents[$k]['children'][$child['channel']])) {
					// open channel container
					$parents[$k]['children'][$child['channel']] = [];
				}

				// push channel notification details
				$parents[$k]['children'][$child['channel']][] = $child;
			}

			if (!$children) {
				// we don't want a notification with no children (channels notified)
				unset($parents[$k]);
				continue;
			}
		}

		if (!$channels) {
			// unset all notifications in case of no channel details
			$parents = [];
		}

		return [array_values($parents), $channels];
	}
}
