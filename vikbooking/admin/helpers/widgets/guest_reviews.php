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
 * Class handler for admin widget "guest reviews".
 * 
 * @since 	1.16.10 (J) - 1.6.10 (WP)
 */
class VikBookingAdminWidgetGuestReviews extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Tells whether VCM is installed and updated.
	 * 
	 * @var 	bool
	 */
	protected $vcm_exists = true;

	/**
	 * VCM language definitions map.
	 * 
	 * @var 	bool
	 */
	protected $vcm_lang_defs = [
		'channel_logo' 	    => 'VBOCHANNEL',
		'created_timestamp' => 'VCMPVIEWORDERSVBONE',
		'reply' 			=> 'VCMREVREPLYREV',
		'reviewee_response' => 'VCMREVREPLYREV',
		'reviewer' 			=> 'VCMPVIEWORDERSVBTWO',
		'country_code' 		=> 'VCMTACHOTELCOUNTRY',
		'name' 				=> 'VCMBCAHFIRSTNAME',
		'reservation_id' 	=> 'VCMSMARTBALBID',
		'review_id' 		=> 'VCMREVIEWID',
		'scoring' 			=> 'VCMREVIEWSCORE',
		'value' 			=> 'VCMGREVVALUE',
		'value_for_money' 	=> 'VCMGREVVALUE',
		'clean' 			=> 'VCMGREVCLEAN',
		'cleanliness' 		=> 'VCMGREVCLEAN',
		'comfort' 			=> 'VCMGREVCOMFORT',
		'location' 			=> 'VCMGREVLOCATION',
		'facilities' 		=> 'VCMGREVFACILITIES',
		'staff' 			=> 'VCMGREVSTAFF',
		'review_score' 		=> 'VCMTOTALSCORE',
		'content' 			=> 'VCMGREVCONTENT',
		'message' 			=> 'VCMGREVMESSAGE',
		'text' 				=> 'VCMGREVMESSAGE',
		'headline' 			=> 'VCMGREVMESSAGE',
		'language_code' 	=> 'VCMBCAHLANGUAGE',
		'negative' 			=> 'VCMGREVNEGATIVE',
		'positive' 			=> 'VCMGREVPOSITIVE',
		'public_review' 	=> 'VCMGREVPOSITIVE',
	];

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBOPANELREVIEWS');
		$this->widgetDescr = JText::translate('VBOGUESTREVSVCMREQ');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('star') . '"></i>';
		$this->widgetStyleName = 'yellow';

		// whether VCM is available and up to date
		$this->vcm_exists = class_exists('VCMReviewHelper');

		// avoid queries on certain pages, as VCM may not have been activated yet
		if (VBOPlatformDetection::isWordPress() && $this->vcm_exists) {
			global $pagenow;
			if (isset($pagenow) && in_array($pagenow, ['update.php', 'plugins.php', 'plugin-install.php'])) {
				$this->vcm_exists = false;
			}
		}

		if ($this->vcm_exists) {
			// load the VCM admin language file
			$vcm_admin_lang_path = '';
			if (VBOPlatformDetection::isJoomla()) {
				$vcm_admin_lang_path = JPATH_ADMINISTRATOR;
			} elseif (defined('VIKCHANNELMANAGER_ADMIN_LANG')) {
				$vcm_admin_lang_path = VIKCHANNELMANAGER_ADMIN_LANG;
			} elseif (VBOPlatformDetection::isWordPress()) {
				// if running within Vik Booking, the constant VIKCHANNELMANAGER_ADMIN_LANG may not be available
				$vcm_admin_lang_path = str_replace('vikbooking', 'vikchannelmanager', VIKBOOKING_ADMIN_LANG);
			}

			if ($vcm_admin_lang_path) {
				$lang = JFactory::getLanguage();
				$lang->load('com_vikchannelmanager', $vcm_admin_lang_path);
				if (VBOPlatformDetection::isWordPress() && defined('VIKCHANNELMANAGER_LIBRARIES')) {
					/**
					 * @wponly  load language admin handler as well for WP.
					 * 			We do this only because of WordPress, but in a way also compatible with Joomla as
					 * 			the constant VIKCHANNELMANAGER_LIBRARIES and method attachHandler are not in Joomla.
					 */
					$lang->attachHandler(VIKCHANNELMANAGER_LIBRARIES . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'admin.php', 'vikchannelmanager');
				}
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
			// VCM JS language defs
			foreach ($this->vcm_lang_defs as $vcm_lang_def) {
				JText::script($vcm_lang_def);
			}

			// JS language defs (VBO & VCM)
			JText::script('VBO_REPLY');
			JText::script('VBDASHBOOKINGID');
			JText::script('VBO_PLEASE_FILL_FIELDS');
			JText::script('VCM_AI_CHAT_TOOLTIP');
		}
	}

	/**
	 * AJAX endpoint to load a guest review.
	 * 
	 * @return 	void
	 */
	public function loadGuestReview()
	{
		$dbo   = JFactory::getDbo();
		$input = JFactory::getApplication()->input;

		$load_id  = $input->getInt('review_id', 0);
		$nav_type = $input->getAlnum('nav_type', '');
		$wrapper  = $input->getString('wrapper', '');

		$has_next = true;
		$has_prev = true;

		$operator = '=';
		$asc_desc = 'DESC';
		if (!strcasecmp($nav_type, 'gt')) {
			$operator = '>=';
			$asc_desc = 'ASC';
		} elseif (!strcasecmp($nav_type, 'lt')) {
			$operator = '<=';
			$asc_desc = 'DESC';
		}

		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikchannelmanager_otareviews'));

		if ($load_id) {
			$q->where($dbo->qn('id') . " {$operator} " . $load_id);
			$q->order($dbo->qn('id') . ' ' . $asc_desc);
		} else {
			$q->order($dbo->qn('dt') . ' DESC');
			// no next review
			$has_next = false;
		}

		$dbo->setQuery($q, 0, 1);

		try {
			$review = $dbo->loadObject();

			if (!$review && $load_id) {
				// the query did not produce any result
				$q->clear('order')->clear('where');
				$q->order($dbo->qn('dt') . ' DESC');
				// try to get the most recent review
				$dbo->setQuery($q, 0, 1);
				$review = $dbo->loadObject();
				// no next review
				$has_next = false;
			}

			if ($review && $review->id == 1) {
				// no prev review
				$has_prev = false;
			}
		} catch (Exception $e) {
			$review = null;
		}

		// start output buffering
		ob_start();

		$current_review_id = ($review ?? (new stdClass))->id ?? 0;
		$can_reply = 0;
		$has_reply = 0;
		$review_data = null;

		if (!$review) {
			?>
			<p class="info"><?php echo JText::translate('VBO_NO_RECORDS_FOUND'); ?></p>
			<?php
		} else {
			// let VCM parse the review object
			$review_helper = VCMReviewHelper::getInstance($review);
			$review_data = $review_helper->parseObject();

			// get the review channel details
			$channel_details = $review_helper->getChannelDetails();
			if (!empty($channel_details['logo'])) {
				// prepend channel logo property
				$review_data->{$review->id} = (object) array_merge(['channel_logo' => $channel_details['logo']], (array) $review_data->{$review->id});
			}

			// access additional details
			$can_reply = (int) $review_helper->canReply();
			$has_reply = (int) $review_helper->hasReply();

			if ($can_reply) {
				// print the HTML structure for the host reply to the guest review
				?>
			<div class="vbo-widget-guest-reviews-reply-wrap" data-review-id="<?php echo $review->id; ?>" data-otareview-id="<?php echo $review->review_id ?? ''; ?>">
				<div class="vbo-widget-guest-reviews-reply-inner">
					<label for="<?php echo $wrapper; ?>-reply-text"><?php echo JText::translate('VCMREVREPLYREV'); ?></label>
					<textarea id="<?php echo $wrapper; ?>-reply-text" class="vbo-widget-guest-reviews-reply-txt"></textarea>
					<div class="vbo-widget-guest-reviews-reply-actions">
						<button type="button" class="btn btn-primary vbo-widget-guest-reviews-reply-action-ai ai-write-review"><?php echo JText::translate('VCM_AI_CHAT_TOOLTIP'); ?></button>
						<button type="button" class="btn btn-success vbo-widget-guest-reviews-reply-action-submit"><?php echo JText::translate('VCMBCAHSUBMIT'); ?></button>
					</div>
				</div>
			</div>
				<?php
			}
		}

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return [
			'html' 		    => ($html_content ?: ''),
			'review_id'     => $current_review_id,
			'review_data'   => $review_data,
			'review_record' => $review ?? (new stdClass),
			'channel'       => $channel_details,
			'has_next' 		=> $has_next,
			'has_prev' 		=> $has_prev,
			'can_reply'     => $can_reply,
			'has_reply'     => $has_reply,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function render(?VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the guest reviews wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-guest-reviews-' . $wrapper_instance;

		// this widget will work only if VCM is available and updated, and if permissions are met
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$this->vcm_exists || !$vbo_auth_bookings) {
			return;
		}

		// multitask data and options
		$review_id  = 0;
		$booking_id = 0;
		if ($data && $data->isModalRendering()) {
			// check if a specific review ID was set
			$review_id = (int) $this->options()->get('review_id', 0);
			$ota_review_id = (string) $this->options()->get('ota_review_id', '');

			// check if a booking ID was set
			$booking_id = (int) $this->options()->fetchBookingId();

			if ($booking_id && !$review_id) {
				$review_id = $this->getReviewIDFromBooking($booking_id);
			}

			if (!$review_id && $ota_review_id) {
				$review_id = $this->getReviewIDFromOtaId($ota_review_id);
			}
		}

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
					<div class="vbo-admin-widget-head-commands">
					<?php
					if ($this->vcm_exists) {
						?>
						<div class="vbo-reportwidget-commands">
							<div class="vbo-reportwidget-commands-main">
								<div class="vbo-reportwidget-command-dates vbo-widget-guestreviews-topdets vbo-widget-guestreviews-topdets-reply" style="display: none;">
									<button type="button" class="btn btn-rounded btn-light-green vbo-btn-review-reply"><?php echo JText::translate('VBO_REPLY'); ?></button>
								</div>
								<div class="vbo-reportwidget-command-dates vbo-widget-guestreviews-topdets vbo-widget-guestreviews-topdets-info" style="display: none;">
									<div class="vbo-reportwidget-period-name vbo-widget-guestreviews-topdets-cname"></div>
									<div class="vbo-reportwidget-period-date">
										<span class="label label-info vbo-widget-guestreviews-topdets-bid" data-bid="0"></span>
									</div>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-prev">
									<span class="vbo-widget-guestreviews-go-prev" onclick="vboWidgetGuestReviewsNavigate('<?php echo $wrapper_id; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-next">
									<span class="vbo-widget-guestreviews-go-next" onclick="vboWidgetGuestReviewsNavigate('<?php echo $wrapper_id; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
								</div>
							</div>
						</div>
					<?php
					}
					?>
					</div>
				</div>
			</div>
			<div id="<?php echo $wrapper_id; ?>" class="vbo-widget-guestreviews-wrap" data-review-id="<?php echo $review_id; ?>">
				<div class="vbo-widget-guestreviews-content">
				<?php
				if (!$this->vcm_exists) {
					?>
					<p class="info"><?php echo JText::translate('VBOGUESTREVSVCMREQ'); ?></p>
					<?php
				}
				?>
				</div>
			</div>
		</div>

		<?php
		if (static::$instance_counter === 0 || $is_ajax) {
			// some JS functions should be loaded once per widget instance

			// attempt to load the OTA review category tags
			$ota_category_tags = [];
			if (class_exists('VCMAirbnbReview')) {
				// load the Airbnb review category tags that could have been
				// selected by the guest during the review for the host
				$ota_category_tags = VCMAirbnbReview::getCategoryTags('guest_review_host');
			}
			?>

		<script type="text/javascript">

			/**
			 * The review objects container.
			 */
			var vbo_wgr_review_objects = {};

			/**
			 * language definitions map.
			 */
			var vbo_wgr_langdefs_map = <?php echo json_encode($this->vcm_lang_defs); ?>;

			/**
			 * Review macro-group string.
			 */
			var vbo_wgr_json_macrogr = '';

			/**
			 * Review category tags.
			 */
			var vbo_wgr_category_tags = <?php echo json_encode((object) $ota_category_tags) ?>;

			/**
			 * Icons and values to render the review scoring.
			 */
			var vbo_wgr_full_staricn = '<?php VikBookingIcons::e('star', 'vbo-review-star vbo-review-star-full') ?>';
			var vbo_wgr_void_staricn = '<?php VikBookingIcons::e('star', 'vbo-review-star') ?>';
			var vbo_wgr_servicescore = false;

			/**
			 * Display the loading skeletons.
			 */
			function vboWidgetGuestReviewsSkeletons(wrapper) {
				let widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				let skeleton_html = '<div class="vbo-widget-spinnner-loading">';
				skeleton_html += '<div class="vbo-widget-spinnner-loading-inner">';
				skeleton_html += '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>';
				skeleton_html += '</div>';
				skeleton_html += '</div>';
				widget_instance.find('.vbo-widget-guestreviews-content').html(skeleton_html);
			}

			/**
			 * Starts a randomly timed answer typing.
			 */
			function vboWidgetGuestReviewsTypeAnswer(textarea, words, min, max) {
				if (isNaN(min) || min < 0) {
					min = 0;
				}

				if (isNaN(max) || max < 0) {
					max = 512;
				}

				return new Promise((resolve) => {
					vboWidgetGuestReviewsTypeAnswerRecursive(resolve, textarea, words, min, max);
				});
			}

			/**
			 * Recursively registers the next words to type with a random timer.
			 */
			function vboWidgetGuestReviewsTypeAnswerRecursive(resolve, textarea, words, min, max) {
				if (words.length == 0) {
					// typed all the provided words
					resolve();
				} else {
					// register timeout to append the next word
					setTimeout(() => {
						let val = textarea.val();
						// extract word and append it within the textarea value
						textarea.val((val.length ? val + ' ' : '') + words.shift());
						// keep going until we reach the end of the queue
						vboWidgetGuestReviewsTypeAnswerRecursive(resolve, textarea, words, min, max);
					}, Math.floor(Math.random() * (max - min + 1) + min));
				}
			}

			/**
			 * Navigate between the various reviews.
			 */
			function vboWidgetGuestReviewsNavigate(wrapper, direction) {
				let widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// current ID
				let current_id = parseInt(widget_instance.attr('data-review-id'));

				// ID to load and navigation type
				let load_id  = current_id;
				let nav_type = '';

				if (direction && direction > 0) {
					load_id += 1;
					nav_type = 'gt';
				} else if (direction && direction < 0 && load_id > 1) {
					load_id -= 1;
					nav_type = 'lt';
				}

				// set new ID
				widget_instance.attr('data-review-id', load_id);

				// show loading skeletons
				vboWidgetGuestReviewsSkeletons(wrapper);

				// launch navigation
				vboWidgetGuestReviewsLoad(wrapper, nav_type);
			}

			/**
			 * Perform the request to load the guest review.
			 */
			function vboWidgetGuestReviewsLoad(wrapper, nav_type) {
				let widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// hide top details before making the request
				widget_instance.parent().find('.vbo-widget-guestreviews-topdets-info, .vbo-widget-guestreviews-topdets-reply').hide();

				// reset values for parsing the review object
				vbo_wgr_json_macrogr = '';
				vbo_wgr_servicescore = false;

				let load_id = widget_instance.attr('data-review-id');

				// the widget method to call
				let call_method = 'loadGuestReview';

				// make a request to load the bookings calendar
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call:      call_method,
						return:    1,
						review_id: load_id,
						nav_type:  nav_type,
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

							let review_id = obj_res[call_method]['review_id'];

							// set current review ID
							widget_instance.attr('data-review-id', review_id);

							// merge review data objects (caching is not really needed as we always perform an AJAX request)
							vbo_wgr_review_objects = Object.assign(vbo_wgr_review_objects, obj_res[call_method]['review_data']);

							// the review language
							let review_lang = obj_res[call_method]['review_record']['lang'];

							// set and show top details
							let top_details = widget_instance.parent();
							let review_bid  = (obj_res[call_method]['review_record']['idorder'] || '0');
							top_details.find('.vbo-widget-guestreviews-topdets-cname').text((obj_res[call_method]['review_record']['customer_name'] || ' '));
							top_details.find('.vbo-widget-guestreviews-topdets-bid')
								.attr('data-bid', review_bid)
								.html('<?php VikBookingIcons::e('external-link'); ?> ' + Joomla.JText._('VBDASHBOOKINGID') + ' ' + review_bid);
							top_details.find('.vbo-widget-guestreviews-topdets-info').show();

							// check if a reply is allowed
							if (obj_res[call_method]['can_reply']) {
								top_details.find('.vbo-widget-guestreviews-topdets-reply').show();
							}

							// check for paging limits
							if (obj_res[call_method]['has_prev']) {
								top_details.find('.vbo-widget-guestreviews-go-prev').show();
							} else {
								top_details.find('.vbo-widget-guestreviews-go-prev').hide();
							}

							if (obj_res[call_method]['has_next']) {
								top_details.find('.vbo-widget-guestreviews-go-next').show();
							} else {
								top_details.find('.vbo-widget-guestreviews-go-next').hide();
							}

							// replace HTML with new data
							widget_instance.find('.vbo-widget-guestreviews-content').html(
								vboWidgetGuestReviewsStringifyObject(vbo_wgr_review_objects[review_id], review_id) + obj_res[call_method]['html']
							);

							if (obj_res[call_method]['can_reply']) {
								// register event to handle the reply functions, manual or through AI
								setTimeout(() => {
									// AI write review
									widget_instance.find('.vbo-widget-guest-reviews-reply-action-ai.ai-write-review').on('click', function() {
										// disable button and start animation
										jQuery(this).prop('disabled', true).html('<?php VikBookingIcons::e('spinner', 'fa-spin'); ?>');

										// build review object
										const review  = vbo_wgr_review_objects[review_id];
										const channel = obj_res[call_method]['channel'];

										let customer = review?.reviewer?.name;
										let score = review?.scoring?.review_score;
										let content = [];

										if (channel.uniquekey == '<?php echo VikChannelManagerConfig::BOOKING; ?>') {
											// Booking.com
											content.push(review?.content?.headline);
											content.push(review?.content?.negative);
											content.push(review?.content?.positive);
										} else if (channel.uniquekey == '<?php echo VikChannelManagerConfig::AIRBNBAPI; ?>') {
											// Airbnb API
											content.push(review?.content?.public_review);
											content = content.concat(Object.values(review?.comments || {}));
										} else if (channel.uniquekey == 0) {
											// Website
											content.push(review?.content?.message);
										}

										if (typeof score !== 'undefined') {
											content.push('Overall score: ' + score + '/10');
										}

										// get rid of the empty comments
										content = content.filter(s => s);

										new Promise((resolve, reject) => {
											if (!content.length) {
												reject(null);
												return false;
											}

											VBOCore.doAjax(
												'<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=ai.review'); ?>',
												{
													customer: customer,
													review: content.join("\n"),
													language: review_lang,
												},
												(response) => {
													resolve(response);
												},
												(error) => {
													reject(error.responseText || error.statusText || 'An error has occurred');
												}
											);
										}).then(async (response) => {
											const replyTextarea = widget_instance.find('textarea.vbo-widget-guest-reviews-reply-txt');
											replyTextarea.val('');
											await vboWidgetGuestReviewsTypeAnswer(replyTextarea, (response.review || '').split(/ +/), 32, 128);
										}).catch((error) => {
											if (error) {
												alert(error);
											}
										}).finally(() => {
											// enable button again
											jQuery(this).prop('disabled', false).text(Joomla.JText._('VCM_AI_CHAT_TOOLTIP'));
										});
									});

									// reply submit button
									widget_instance.find('.vbo-widget-guest-reviews-reply-action-submit').on('click', function() {
										// disable button
										jQuery(this).prop('disabled', true);

										// the text for the reply
										let replyText = widget_instance.find('textarea.vbo-widget-guest-reviews-reply-txt').val();

										if (!replyText) {
											alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));
											return false;
										}

										// perform the request
										VBOCore.doAjax(
											'<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=review.reply'); ?>',
											{
												review_id:     obj_res[call_method].review_record?.id,
												ota_review_id: obj_res[call_method].review_record?.review_id,
												uniquekey:     obj_res[call_method].channel?.uniquekey,
												reply_text:    replyText,
											},
											(response) => {
												// remove buttons
												widget_instance.find('.vbo-widget-guest-reviews-reply-actions').remove();

												// remove textarea
												widget_instance.find('textarea.vbo-widget-guest-reviews-reply-txt').remove();

												// append reply review text
												widget_instance.find('.vbo-widget-guest-reviews-reply-inner').append('<div class="vbo-widget-guest-reviews-replied-text">' + replyText + '</div>');

												// prepend success icon to label
												widget_instance.find('.vbo-widget-guest-reviews-reply-inner').find('label').prepend('<span class="badge badge-success"><?php VikBookingIcons::e('check-circle'); ?></span> ');

												// hide the top-details "reply" button
												widget_instance.parent().find('.vbo-widget-guestreviews-topdets-reply').hide();
											},
											(error) => {
												// enable button
												jQuery(this).prop('disabled', false);

												// display alert
												alert(error.responseText || error.statusText || 'An error has occurred');
											}
										);
									});
								}, 200);
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						console.error(error);
						alert(error.responseText);
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-guestreviews-content').find('.vbo-skeleton-loading').remove();
					}
				);
			}

			/**
			 * Ensures the argument is a non-empty object.
			 */
			function vboWidgetGuestReviewsIsObject(obj) {
				if (typeof obj != 'object') {
					return false;
				}

				for (var jk in obj) {
					return obj.hasOwnProperty(jk);
				}
			}

			/**
			 * Turns strings into words with the first letter upper case.
			 */
			function vboWidgetGuestReviewsUcWords(str) {
				return (str + '').replace(/^(.)|\s+(.)/g, function ($1) {
					return $1.toUpperCase();
				});
			}

			/**
			 * Recursively parse a review object to build the HTML structure for representation.
			 */
			function vboWidgetGuestReviewsStringifyObject(obj, idrev) {
				var stringified = '';
				var otareview = (vbo_wgr_review_objects.hasOwnProperty(idrev) && vbo_wgr_review_objects[idrev].hasOwnProperty('channel') && vbo_wgr_review_objects[idrev]['channel'] === null ? false : true);

				for (var jk in obj) {
					let objValue = obj[jk];

					if (!obj.hasOwnProperty(jk) || objValue === null || jk == 'can_reply') {
						continue;
					}

					var usekey = jk.replace(' ', '_').toLowerCase();
					var prop_name = vbo_wgr_langdefs_map.hasOwnProperty(usekey) ? Joomla.JText._(vbo_wgr_langdefs_map[usekey]) : vboWidgetGuestReviewsUcWords(jk.replace(/_/g, ' '));

					if (vboWidgetGuestReviewsIsObject(objValue)) {
						// update macrogroup
						vbo_wgr_json_macrogr = jk.replace(' ', '-').replace('_', '-').toLowerCase();
						// use this property container as a group-label
						stringified += '<div class="vbo-review-json-entry vbo-review-json-entry-group vbo-review-json-' + vbo_wgr_json_macrogr + '"><span class="vbo-review-json-key">' + prop_name + '</span></div>';
						// recursive call for inner object
						stringified += vboWidgetGuestReviewsStringifyObject(objValue, idrev);
					} else {
						if (jk === 'reviewee_response' && typeof objValue === 'string' && objValue) {
							// overwrite special property with just a string as a value, to be treated as an object
							stringified += '<div class="vbo-review-json-entry vbo-review-json-entry-group vbo-review-json-revieweeresponse">';
						} else {
							// regular property
							stringified += '<div class="vbo-review-json-entry vbo-review-json-' + vbo_wgr_json_macrogr + '">';
						}
						stringified += '<span class="vbo-review-json-key">' + prop_name + '</span>';
						if (!otareview && vbo_wgr_json_macrogr == 'scoring' && (jk != 'review_score' || (jk == 'review_score' && !vbo_wgr_servicescore))) {
							// star rating for website review to a service or global review
							vbo_wgr_servicescore = true;
							stringified += '<span class="vbo-review-json-value">';
							var starscount = Math.floor((parseInt(objValue) / 2));
							for (var s = 1; s <= starscount; s++) {
								stringified += vbo_wgr_full_staricn;
							}
							for (var s = (starscount + 1); s <= 5; s++) {
								stringified += vbo_wgr_void_staricn;
							}
							stringified += '</span>';
						} else {
							vbo_wgr_servicescore = false;
							// check if the value is an URL of the photo/logo
							if (jk == 'photo' && objValue.indexOf('http') >= 0) {
								objValue = '<span class="vbo-review-guest-avatar-wrap"><img class="vbo-review-guest-avatar" src="' + objValue + '" /></span>';
							} else if (jk == 'channel_logo' && objValue.indexOf('http') >= 0) {
								objValue = '<span class="vbo-review-channel-logo-wrap"><img class="vbo-review-channel-logo" src="' + objValue + '" /></span>';
							}

							// build additional element classes
							var value_classes = [];
							if (jk == 'review_score') {
								value_classes.push('vbo-review-json-totscore');
							}
							if (vbo_wgr_json_macrogr == 'scoring' && !isNaN(objValue)) {
								value_classes.push('vbo-review-json-servscore');
							}
							if (jk == 'reviewee_response') {
								value_classes.push('vbo-review-json-revieweeresponse');
							}

							// check if the value should be normalized
							if (vbo_wgr_json_macrogr == 'category-tags' && typeof objValue === 'string' && objValue) {
								// review category tags are expected to be comma separated
								let category_tags = [];
								objValue.split(',').forEach((tag) => {
									tag = tag.trim().toLowerCase();
									if (vbo_wgr_category_tags.hasOwnProperty(tag)) {
										// push normalized tag
										category_tags.push(vbo_wgr_category_tags[tag]['descr']);
									} else {
										// push raw (unknown) tag
										category_tags.push(tag);
									}
								});

								// replace object value
								objValue = category_tags.join(', ').toLowerCase() + '.';
								objValue = objValue.substr(0, 1).toUpperCase() + objValue.substr(1);
							}

							stringified += '<span class="vbo-review-json-value' + (value_classes.length ? ' ' + value_classes.join(' ') : '') + '">' + objValue + '</span>';
						}
						stringified += '</div>';
					}
				}

				return stringified;
			}

		</script>
		<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// when document is ready, load the latest guest review
				vboWidgetGuestReviewsLoad('<?php echo $wrapper_id; ?>', '');

				// register click event for the review ID
				jQuery('#<?php echo $wrapper_id; ?>').parent().find('.vbo-widget-guestreviews-topdets-bid').on('click', function() {
					let bid = jQuery(this).attr('data-bid');
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
				});

				// register click event for the reply button
				jQuery('#<?php echo $wrapper_id; ?>').parent().find('.vbo-btn-review-reply').on('click', function() {
					let reply_wrap = jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-guest-reviews-reply-wrap');
					let modal_body = jQuery('#<?php echo $wrapper_id; ?>').closest('.vbo-modal-overlay-content-body-scroll');
					if (modal_body.length) {
						modal_body.animate({scrollTop: reply_wrap.offset().top - 20}, {duration: 400});
					} else {
						jQuery('html,body').animate({scrollTop: reply_wrap.offset().top - 20}, {duration: 400});
					}
					reply_wrap.find('textarea').focus();
				});

			});

		</script>

		<?php
	}

	/**
	 * Attempts to find the review ID from the given booking ID.
	 * 
	 * @param 	int 	$booking_id 	The reservation ID.
	 * 
	 * @return 	int
	 */
	protected function getReviewIDFromBooking($booking_id)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('id'))
				->from($dbo->qn('#__vikchannelmanager_otareviews'))
				->where($dbo->qn('idorder') . ' = ' . (int) $booking_id)
		);

		try {
			return (int) $dbo->loadResult();
		} catch(Exception $e) {
			// do nothing
		}

		return 0;
	}

	/**
	 * Attempts to find the review ID from the given OTA review ID.
	 * 
	 * @param 	string 	$ota_id 	The OTA review ID.
	 * 
	 * @return 	int
	 */
	protected function getReviewIDFromOtaId($ota_id)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('id'))
				->from($dbo->qn('#__vikchannelmanager_otareviews'))
				->where($dbo->qn('review_id') . ' = ' . $dbo->q($ota_id))
		);

		try {
			return (int) $dbo->loadResult();
		} catch(Exception $e) {
			// do nothing
		}

		return 0;
	}
}
