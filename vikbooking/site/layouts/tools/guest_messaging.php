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
 * Obtain vars from arguments received in the layout file.
 * This is the layout file for the "guest_messaging" operator tool.
 * 
 * @var string 	$tool 		   The tool identifier.
 * @var array 	$operator      The operator record accessing the tool.
 * @var object 	$permissions   The operator-tool permissions registry.
 * @var string 	$tool_uri 	   The base URI for rendering this tool.
 */
extract($displayData);

// messages per page
$messages_per_page = 20;

// distance threshold in pixels between the current scroll position and the end of the list
$px_distance_threshold = 140;

// get listings assigned to the current operator (no listings equals to all listings)
$listings = array_filter(
	array_map('intval', (array) $permissions->get('rooms', []))
);

// attempt to require the chat handler from VCM
try {
	VikBooking::getVcmChatInstance($oid = 0, $channel = null);
} catch (Throwable $e) {
	// propagate the error code
	VBOHttpDocument::getInstance()->close($e->getCode(), 'Channel Manager not available.');
}

// make sure VCM is available and up to date
if (!class_exists('VCMChatHandler') || !method_exists('VCMChatHandler', 'loadChatAssets')) {
	// raise an error
	VBOHttpDocument::getInstance()->close(500, 'Channel Manager not available.');
}

/**
 * Load the VBOCore JS class and chat assets from VCM.
 */
VikBooking::getVboApplication()->loadCoreJS();
VCMChatHandler::loadChatAssets();

// load JS lang definitions
JText::script('VBTODAY');
JText::script('VBSTANDBY');
JText::script('VBCONFIRMED');
JText::script('VBCANCELLED');
JText::script('VBOCHECKEDSTATUSIN');
JText::script('VBOCHECKEDSTATUSOUT');
JText::script('VBOCHECKEDSTATUSNOS');
JText::script('VBO_REPLY');
JText::script('VBO_NO_MORE_MESSAGES');
JText::script('VBO_NO_REPLY_NEEDED');
JText::script('VBO_WANT_PROCEED');
JText::script('VBO_NEW_MESSAGES');

?>
<div class="vbo-optool-gmessaging-wrap">
	<div class="vbo-optool-gmessaging-inner">
		<div class="vbo-optool-gmessaging-list-container" data-offset="0">
		<?php
		for ($i = 0; $i < $messages_per_page; $i++) {
			?>
			<div class="vbo-optool-gmessaging-guest-activity vbo-optool-gmessaging-guest-activity-skeleton">
				<div class="vbo-optool-gmessaging-guest-activity-avatar">
					<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>
				</div>
				<div class="vbo-optool-gmessaging-guest-activity-content">
					<div class="vbo-optool-gmessaging-guest-activity-content-head">
						<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>
					</div>
					<div class="vbo-optool-gmessaging-guest-activity-content-info-msg">
						<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<div class="vbo-optool-gmessaging-inboxstyle-chat"></div>
	</div>
</div>

<script type="text/javascript">

	const vbo_media_base_uri = '<?php echo VBO_SITE_URI . 'resources/uploads/'; ?>';

	/**
	 * Loads and renders the chat for a given booking ID.
	 * 
	 * @note 	do NOT use an arrow function because the clicked message is passed as "thisArg".
	 */
	const vbo_optool_gmessaging_load_chat_fn = function(bid) {
		// define the clicked message
		let clickedMessage = this;

		// gather thread id
		let threadId = 0;
		try {
			threadId = clickedMessage.getAttribute('data-idthread');
		} catch(err) {
			// silently catch the error
			console.error(err);
		}

		// empty current chat
		let chatContainer = document.querySelector('.vbo-optool-gmessaging-inboxstyle-chat');
		if (typeof VCMChat !== 'undefined') {
			VCMChat.getInstance().destroy();
		}
		chatContainer.innerHTML = '';

		// start loading
		let loadingElement = document.createElement('div');
		loadingElement.classList.add('vbo-optool-gmessaging-chat-loading');
		loadingElement.innerHTML = '<span><?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?></span>';
		chatContainer.append(loadingElement);

		// make the request to the controller
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operatortool.renderGuestBookingChat') ?>",
			{
				bid: bid,
			},
			(response) => {
				response = typeof response === 'string' ? JSON.parse(response) : response;

				// remove loading element
				loadingElement.remove();

				// create chat head element
				let chatHead = document.createElement('div');
				chatHead.classList.add('vbo-optool-gmessaging-inboxstyle-chat-head');

				// build chat head-inner element
				let chatHeadInner = document.createElement('div');
				try {
					// clone and set avatar
					let guestAvatar = clickedMessage.querySelector('.vbo-optool-gmessaging-message-avatar').cloneNode(true);
					chatHeadInner.append(guestAvatar);

					// build head details element
					let chatHeadDetails = document.createElement('div');
					chatHeadDetails.classList.add('vbo-optool-gmessaging-inboxstyle-chat-head-details');

					// get guest name
					let guestName = clickedMessage.querySelector('.vbo-optool-gmessaging-message-guestname-txt').innerText;

					// build head info element
					let chatHeadInfo = document.createElement('div');
					chatHeadInfo.classList.add('vbo-optool-gmessaging-inboxstyle-chat-head-info');

					// append guest name
					let guestNameEl = document.createElement('span');
					guestNameEl.classList.add('vbo-optool-gmessaging-inboxstyle-chat-head-info-gname');
					guestNameEl.innerText = guestName;
					chatHeadInfo.append(guestNameEl);

					// append booking ID
					let bidEl = document.createElement('span');
					bidEl.classList.add('vbo-badge');
					bidEl.classList.add('badge-info');
					bidEl.classList.add('vbo-optool-gmessaging-inboxstyle-chat-head-info-bid');
					bidEl.innerText = bid;
					chatHeadInfo.append(bidEl);

					// set head info
					chatHeadDetails.append(chatHeadInfo);

					// build head summary element
					let chatHeadSummary = document.createElement('div');
					chatHeadSummary.classList.add('vbo-optool-gmessaging-inboxstyle-chat-head-summary');

					// clone and build booking summary
					let bookingSummary = clickedMessage.querySelector('.vbo-optool-gmessaging-message-bookinginfo').cloneNode(true);

					// listing details
					let listingDetails = document.createElement('span');
					listingDetails.classList.add('vbo-optool-gmessaging-message-listings');
					bookingSummary.append(listingDetails);
					vbo_optool_gmessaging_set_listingdets_fn(bid, listingDetails);

					// no-reply needed
					let noReplyNeededEl = document.createElement('a');
					noReplyNeededEl.classList.add('vbo-label');
					noReplyNeededEl.classList.add('vbo-optool-gmessaging-message-noreplyneeded');
					noReplyNeededEl.innerText = Joomla.JText._('VBO_NO_REPLY_NEEDED');
					noReplyNeededEl.addEventListener('click', () => {
						if (confirm(Joomla.JText._('VBO_WANT_PROCEED'))) {
							// get no-reply-needed status on thread
							let threadNoReplyNeededStatus = clickedMessage ? (clickedMessage.getAttribute('data-noreply-needed') || 0) : 0;

							// toggle no-reply-needed status on thread
							VBOCore.doAjax(
								"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operatortool.toggleGuestThreadNoReplyNeeded') ?>",
								{
									bid: bid,
									status: threadNoReplyNeededStatus,
									id_thread: threadId,
								},
								(response) => {
									if (threadNoReplyNeededStatus == 1) {
										noReplyNeededEl.classList.remove('label-danger');
										clickedMessage.setAttribute('data-noreply-needed', '0');
									} else {
										noReplyNeededEl.classList.add('label-danger');
										clickedMessage.setAttribute('data-noreply-needed', '1');
									}
								},
								(error) => {
									console.error(error);
								}
							);
						}
					});
					bookingSummary.append(noReplyNeededEl);

					// set booking summary
					chatHeadSummary.append(bookingSummary);

					// set head summary
					chatHeadDetails.append(chatHeadSummary);

					// set the whole head details block
					chatHeadInner.append(chatHeadDetails);
				} catch(err) {
					// silently catch the error
					console.error(err);
				}

				// append chat head
				chatHead.append(chatHeadInner);
				chatContainer.append(chatHead);

				// create chat body element
				let chatBody = document.createElement('div');
				chatBody.classList.add('vbo-optool-gmessaging-inboxstyle-chat-body');

				// append chat body
				chatContainer.append(chatBody);

				// render chat through jQuery
				jQuery('.vbo-optool-gmessaging-inboxstyle-chat-body').html(response.html);
				// rendering through pure JS will NOT work
				// chatContainer.setHTMLUnsafe(response['html']);

				// register scroll to bottom with a small delay
				setTimeout(() => {
					if (typeof VCMChat !== 'undefined') {
						VCMChat.getInstance().scrollToBottom();
					}
				}, 150);
			},
			(error) => {
				// log and display error
				console.error(error);
				alert(error.responseText || 'Unknown error');
				// abort loading
				loadingElement.remove();
			}
		);
	};

	/**
	 * Loads the listing details for a booking.
	 */
	const vbo_optool_gmessaging_set_listingdets_fn = (bid, detailsNode) => {
		// perform the request
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operatortool.loadGuestBookingListings') ?>",
			{
				bid: bid,
			},
			(response) => {
				response = typeof response === 'string' ? JSON.parse(response) : response;

				if (!detailsNode) {
					detailsNode = document.querySelector('.vbo-optool-gmessaging-message-listings');
				}

				detailsNode.innerHTML = '<?php VikBookingIcons::e('home'); ?> ' + response.listings.join(', ');
			},
			(error) => {
				console.error(error);
			}
		);
	};

	/**
	 * Loads a pagination of thread messages.
	 */
	const vbo_optool_gmessaging_load_threads_fn = (offset, limit) => {
		// validate offset and limit
		offset = parseInt((isNaN(offset) ? 0 : offset));
		limit = parseInt((isNaN(limit) || limit < 1 ? <?php echo $messages_per_page; ?> : limit));

		// perform the request
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operatortool.loadGuestThreads') ?>",
			{
				start: offset,
				limit: limit,
			},
			(response) => {
				let threads = typeof response === 'string' ? JSON.parse(response) : response;
				let container = document.querySelector('.vbo-optool-gmessaging-list-container');
				let messagesList = container.querySelector('.vbo-optool-gmessaging-messages-list');
				let lastMessage = container.querySelector('.vbo-optool-gmessaging-message-last');

				if (lastMessage && threads.length) {
					// remove the "last message" class
					lastMessage.classList.remove('vbo-optool-gmessaging-message-last');
				}

				if (!offset) {
					// first page loading will clean up the container with the skeletons
					container.innerHTML = '';

					if (threads.length) {
						// first page loading should also set the most recent message (thread) date
						container.setAttribute('data-last-updated', (threads[0]?.last_updated || ''));
					}
				} else {
					// scan and remove all skeletons
					container.querySelectorAll('.vbo-optool-gmessaging-guest-activity-skeleton').forEach((skeleton) => {
						skeleton.remove();
					});
				}

				// update offset for next page loading
				container.setAttribute('data-offset', offset + limit);

				if (!threads.length) {
					if (offset > 0) {
						// attempt to destroy the infinite scroll loading
						vbo_optool_gmessaging_destroy_infinite_scroll_fn();
					}

					// display the "no more messages" element
					let noMessagesEl = document.createElement('p');
					noMessagesEl.classList.add('info');
					noMessagesEl.setAttribute('data-no-messages', '1');
					noMessagesEl.innerText = Joomla.JText._('VBO_NO_MORE_MESSAGES');

					if (messagesList && !messagesList.querySelector('.info[data-no-messages="1"]')) {
						// no more messages
						messagesList.append(noMessagesEl);
					} else if (!messagesList) {
						// no messages at all
						container.append(noMessagesEl);
					}
				}

				if (!messagesList) {
					// create the messages list element
					messagesList = document.createElement('div');
					messagesList.classList.add('vbo-optool-gmessaging-messages-list');
				} else {
					// turn custom property off for the page loading
					container.pageLoading = false;
				}

				// scan all thread messages
				threads.forEach((thread, index) => {
					// create message element
					let messageElement = document.createElement('div');
					messageElement.classList.add('vbo-optool-gmessaging-message');

					if (!thread?.read_dt && (thread?.sender_type + '').toLowerCase() == 'guest') {
						messageElement.classList.add('vbo-optool-gmessaging-message-new');
					}

					if (thread?.replied == 0 && (thread?.sender_type + '').toLowerCase() == 'guest') {
						messageElement.classList.add('vbo-optool-gmessaging-message-unreplied');
					}

					if (!offset && !index) {
						messageElement.classList.add('vbo-optool-gmessaging-message-first');
					} else if (offset && (index + 1) == threads.length) {
						messageElement.classList.add('vbo-optool-gmessaging-message-last');
					}

					// set attributes
					messageElement.setAttribute('data-idorder', thread.idorder);
					messageElement.setAttribute('data-idthread', thread.id_thread);
					messageElement.setAttribute('data-idmessage', thread.id_message);
					messageElement.setAttribute('data-noreply-needed', (thread?.no_reply_needed || 0));

					// register click event
					messageElement.addEventListener('click', () => {
						document.querySelectorAll('.vbo-optool-gmessaging-message-active').forEach((activeMess) => {
							activeMess.classList.remove('vbo-optool-gmessaging-message-active');
						});
						vbo_optool_gmessaging_load_chat_fn.call(messageElement, thread.idorder);
						messageElement.classList.add('vbo-optool-gmessaging-message-active');
						messageElement.classList.remove('vbo-optool-gmessaging-message-new');
						messageElement.classList.remove('vbo-optool-gmessaging-message-unreplied');
						let unrepliedIcn = messageElement.querySelector('.message-unreplied');
						if (unrepliedIcn) {
							unrepliedIcn.remove();
						}
					});

					// build message avatar
					let avatarPic = '';
					let avatarPicEl = document.createElement('div');
					let messageAvatar = document.createElement('div');
					messageAvatar.classList.add('vbo-optool-gmessaging-message-avatar');
					if (thread?.pic) {
						avatarPic = thread.pic.indexOf('http') === 0 ? thread.pic : vbo_media_base_uri + thread.pic;
					} else if (thread?.channel_logo) {
						avatarPic = thread.channel_logo;
					}
					if (!avatarPic) {
						avatarPicEl.classList.add('vbo-optool-gmessaging-message-avatar-icon');
						avatarPicEl.innerHTML = '<?php VikBookingIcons::e('user'); ?>';
					} else {
						let avatarPicImg = document.createElement('img');
						avatarPicImg.setAttribute('src', avatarPic);
						avatarPicEl.classList.add('vbo-optool-gmessaging-message-avatar-profile');
						avatarPicEl.append(avatarPicImg);
					}
					messageAvatar.append(avatarPicEl);

					// build message content elements
					let messageContent = document.createElement('div');
					messageContent.classList.add('vbo-optool-gmessaging-message-content');
					let messContHead = document.createElement('div');
					messContHead.classList.add('vbo-optool-gmessaging-message-content-head');

					// message content head details
					let messHeadDetails = document.createElement('div');
					messHeadDetails.classList.add('vbo-optool-gmessaging-message-content-details');
					let messGuestName = document.createElement('div');
					messGuestName.classList.add('vbo-optool-gmessaging-message-guestname');
					let fullGuestName = ((thread?.first_name || '') + ' ' + (thread?.last_name || '')).trim();
					if (!fullGuestName.length) {
						fullGuestName = 'Guest';
					}
					let fullGuestNameEl = document.createElement('span');
					fullGuestNameEl.classList.add('vbo-optool-gmessaging-message-guestname-txt');
					fullGuestNameEl.innerText = fullGuestName;
					messGuestName.append(fullGuestNameEl);
					if (thread?.replied == 0 && (thread?.sender_type + '').toLowerCase() == 'guest') {
						let unrepliedEl = document.createElement('span');
						unrepliedEl.classList.add('label');
						unrepliedEl.classList.add('label-small');
						unrepliedEl.classList.add('message-unreplied');
						unrepliedEl.innerHTML = '<?php VikBookingIcons::e('comments', 'message-reply') ?> ' + Joomla.JText._('VBO_REPLY');
						messGuestName.append(unrepliedEl);
					}
					messHeadDetails.append(messGuestName);
					let messageBookInfo = document.createElement('div');
					messageBookInfo.classList.add('vbo-optool-gmessaging-message-bookinginfo');
					let bookStatus = document.createElement('span');
					bookStatus.classList.add('vbo-badge');
					if (thread?.b_status) {
						if (thread.b_status == 'standby') {
							bookStatus.classList.add('badge-warning');
							bookStatus.innerText = Joomla.JText._('VBSTANDBY');
						} else if (thread.b_status == 'cancelled') {
							bookStatus.classList.add('badge-danger');
							bookStatus.innerText = Joomla.JText._('VBCANCELLED');
						} else {
							bookStatus.classList.add('badge-success');
							if (thread?.b_checkout && thread.b_checkout < thread.message_info.current_ts) {
								bookStatus.innerText = Joomla.JText._('VBOCHECKEDSTATUSOUT');
							} else {
								bookStatus.innerText = Joomla.JText._('VBCONFIRMED');
							}
						}
					}
					messageBookInfo.append(bookStatus);
					let stayDates = document.createElement('span');
					stayDates.classList.add('vbo-optool-gmessaging-message-staydates');
					let stayDatesIn = document.createElement('span');
					stayDatesIn.classList.add('vbo-optool-gmessaging-message-stayin');
					stayDatesIn.innerText = thread.message_info.str_checkin;
					let stayDatesSep = document.createElement('span');
					stayDatesSep.classList.add('vbo-optool-gmessaging-message-staysep');
					stayDatesSep.innerText = ' - ';
					let stayDatesOut = document.createElement('span');
					stayDatesOut.classList.add('vbo-optool-gmessaging-message-stayout');
					stayDatesOut.innerText = thread.message_info.str_checkout;
					stayDates.append(stayDatesIn);
					stayDates.append(stayDatesSep);
					stayDates.append(stayDatesOut);
					messageBookInfo.append(stayDates);
					messHeadDetails.append(messageBookInfo);

					// message content head date
					let messHeadDate = document.createElement('div');
					messHeadDate.classList.add('vbo-optool-gmessaging-message-content-date');
					let messHeadTimeSpan = document.createElement('span');
					messHeadTimeSpan.innerText = thread.message_info.time;
					messHeadDate.append(messHeadTimeSpan);
					let messHeadDateSpan = document.createElement('span');
					messHeadDateSpan.innerText = thread.message_info.is_today ? Joomla.JText._('VBTODAY') : thread.message_info.date;
					messHeadDate.append(messHeadDateSpan);

					// finalize head element
					messContHead.append(messHeadDetails);
					messContHead.append(messHeadDate);

					// message content text element
					let messContMess = document.createElement('div');
					messContMess.classList.add('vbo-optool-gmessaging-message-content-msg');
					let messContMsg = document.createElement('p');
					messContMsg.innerHTML = (thread.content + '').substring(0, 90);
					if ((thread.content + '').length > 90) {
						messContMsg.innerText = messContMsg.innerText + '...';
					}
					messContMess.append(messContMsg);

					// append message content elements
					messageContent.append(messContHead);
					messageContent.append(messContMess);

					// append message elements
					messageElement.append(messageAvatar);
					messageElement.append(messageContent);

					// append message element to list
					messagesList.append(messageElement);
				});

				if (!container.querySelector('.vbo-optool-gmessaging-messages-list')) {
					// messages list element must be appended to main container
					container.append(messagesList);
				}

				if (!offset && threads.length) {
					// attempt to register the infinite scroll loading
					setTimeout(() => {
						vbo_optool_gmessaging_setup_infinite_scroll_fn();
					}, 150);
				}
			},
			(error) => {
				// log and display error
				console.error(error);
				alert(error.responseText || 'Unknown error');
			}
		);
	};

	/**
	 * Destroys the infinite scroll loading.
	 */
	const vbo_optool_gmessaging_destroy_infinite_scroll_fn = () => {
		let messagesList = document.querySelector('.vbo-optool-gmessaging-list-container');

		// un-register infinite scroll event handler
		messagesList.removeEventListener('scroll', vbo_optool_gmessaging_handle_infinite_scroll_fn);
	};

	/**
	 * Sets up the infinite scroll loading.
	 */
	const vbo_optool_gmessaging_setup_infinite_scroll_fn = () => {
		let messagesList = document.querySelector('.vbo-optool-gmessaging-list-container');

		// get wrapper dimensions
		let listViewHeight = messagesList.offsetHeight;
		let listGlobHeight = messagesList.scrollHeight;

		if (listViewHeight >= listGlobHeight) {
			// no scrolling detected, probably because of too few messages
			return;
		}

		// register infinite scroll event handler
		messagesList.addEventListener('scroll', vbo_optool_gmessaging_handle_infinite_scroll_fn);
	};

	/**
	 * Handles the infinite scroll loading.
	 */
	const vbo_optool_gmessaging_handle_infinite_scroll_fn = (e) => {
		// register throttling callback
		VBOCore.throttleTimer(() => {
			// access the current messages container
			let messagesList = document.querySelector('.vbo-optool-gmessaging-list-container');

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

			if (listEndDistance < <?php echo $px_distance_threshold; ?>) {
				// inject custom property to identify a next page is loading
				messagesList.pageLoading = true;

				// show that we are loading more messages
				vbo_optool_gmessaging_draw_skeletons_fn(3);

				// load the next page of messages
				vbo_optool_gmessaging_load_more_fn();
			}
		}, 500);
	};

	/**
	 * Draws skeletons in the messages list container.
	 */
	const vbo_optool_gmessaging_draw_skeletons_fn = (num) => {
		let messagesPool = document.querySelector('.vbo-optool-gmessaging-messages-list');
		if (!messagesPool) {
			return;
		}

		num = parseInt((isNaN(num) || num < 1 ? 3 : num));

		for (let i = 0; i < num; i++) {
			let skeleton = '';
			skeleton += '<div class="vbo-optool-gmessaging-guest-activity vbo-optool-gmessaging-guest-activity-skeleton">';
			skeleton += '	<div class="vbo-optool-gmessaging-guest-activity-avatar">';
			skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
			skeleton += '	</div>';
			skeleton += '	<div class="vbo-optool-gmessaging-guest-activity-content">';
			skeleton += '		<div class="vbo-optool-gmessaging-guest-activity-content-head">';
			skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
			skeleton += '		</div>';
			skeleton += '		<div class="vbo-optool-gmessaging-guest-activity-content-info-msg">';
			skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
			skeleton += '		</div>';
			skeleton += '	</div>';
			skeleton += '</div>';

			messagesPool.insertAdjacentHTML('beforeend', skeleton);
		}
	};

	/**
	 * Loads the next page of messages.
	 */
	const vbo_optool_gmessaging_load_more_fn = () => {
		// access the current messages container
		let messagesList = document.querySelector('.vbo-optool-gmessaging-list-container');

		// get the current offset to load a new page of messages
		let offset = messagesList.getAttribute('data-offset');

		// load the next page of thread messages
		vbo_optool_gmessaging_load_threads_fn(offset, <?php echo $messages_per_page; ?>);
	};

	/**
	 * Checks for new thread messages.
	 */
	const vbo_optool_gmessaging_check_new_threads_fn = () => {
		let container = document.querySelector('.vbo-optool-gmessaging-list-container');
		let last_date = container.getAttribute('data-last-updated');

		// perform the request
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operatortool.checkNewGuestThreads') ?>",
			{
				last_date: last_date,
			},
			(response) => {
				response = typeof response === 'string' ? JSON.parse(response) : response;

				if (response.newThreads.length) {
					if (!container.querySelector('.info[data-new-messages="1"]')) {
						// display message
						let newMessEl = document.createElement('p');
						newMessEl.classList.add('info');
						newMessEl.setAttribute('data-new-messages', '1');

						let reloadLink = document.createElement('a');
						reloadLink.setAttribute('href', '<?php echo $tool_uri; ?>');
						reloadLink.innerText = Joomla.JText._('VBO_NEW_MESSAGES');

						newMessEl.append(reloadLink);

						container.prepend(newMessEl);
					}

					// update attribute
					container.setAttribute('data-last-updated', (response.newThreads[0]?.last_updated || last_date));
				}
			},
			(error) => {
				console.error(error);
			}
		);
	};

	/**
	 * Configure the DOM-ready execution.
	 */
	jQuery(function() {

		// load the first page of threads on page load
		vbo_optool_gmessaging_load_threads_fn(0, <?php echo $messages_per_page; ?>);

		// register a periodic interval to watch for new messages
		setInterval(() => {
			vbo_optool_gmessaging_check_new_threads_fn();
		}, 120000);

	});
</script>
