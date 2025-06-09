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
 * Display data attributes.
 * 
 * @var array   $threads
 * @var array   $options
 * @var string  $id
 */
extract($displayData);

$id = 'vbo-chat-interface-' . ($options['id'] ?? uniqid());

?>

<div class="vbo-chat-interface<?php echo ($options['compact'] ?? false) ? ' compact' : ''; ?>" id="<?php echo $id; ?>">

    <div class="vbo-chat-threads">

        <?php
        foreach ($threads as $thread) {
            echo $this->sublayout('thread', [
                'thread' => $thread,
                'options' => $options ?? [],
            ]);
        }
        ?>

    </div>

    <div class="vbo-chat-target">
        <?php
        echo JLayoutHelper::render('chat.blank', [
            'title' => '',
            'subtitle' => JText::translate('VBO_CHAT_CONV_EMPTY_SUBTITLE'),
        ]);
        ?>
    </div>

    <a href="javascript:void(0)" class="vbo-chat-back"><?php VikBookingIcons::e('chevron-left'); ?>&nbsp;<?php echo JText::translate('VBBACK'); ?></a>

</div>

<script>
    (function($) {
        'use strict';

        const rearrangeThreads = () => {
            const threads = $('#<?php echo $id; ?>').find('.vbo-chat-threads').children().sort((a, b) => {
                // get values to compare
                const x = $(a).attr('data-date');
                const y = $(b).attr('data-date');

                // equal by default
                let delta = 0;

                if (x < y) {
                    // A is lower than B
                    delta = 1;
                } else if (x > y) {
                    // A is highet than B
                    delta = -1;
                }

                return delta;
            });

            $('#<?php echo $id; ?>').find('.vbo-chat-threads').html(threads);
        }

        const refreshThread = (chat) => {
            const context = chat.data.environment.context;
            const thread = $('#<?php echo $id; ?>').find('.chat-thread[data-context="' + context.alias + '"][data-id="' + context.id + '"]');

            if (!thread.length) {
                return;
            }

            const lastMessage = chat.getLatestMessage();

            // obtain the details of the user that wrote the last message
            const user = chat.getMessageUser(lastMessage);

            // refresh avatar
            thread.find('.chat-thread-avatar').html(chat.drawUserAvatar(user));

            // refresh user name
            thread.find('.message-author').text(user.name);

            // refresh date
            const date = DateHelper.stringToDate(lastMessage.createdon);
            thread.attr('data-date', lastMessage.createdon);
            thread.find('.last-update-time').text(date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));

            if (DateHelper.isToday(date)) {
                thread.find('.last-update-date').text(Joomla.JText._('VBTODAY'));
            } else if (DateHelper.isYesterday(date)) {
                thread.find('.last-update-date').text(Joomla.JText._('VBOYESTERDAY'));
            } else {
                thread.find('.last-update-date').text(date.toLocaleDateString());
            }

            // refresh message
            thread.find('.chat-thread-message-body').text(shortenText(lastMessage.message, 80)).attr('data-length', lastMessage.message.length);

            // refresh attachments
            const attachments = lastMessage.attachments.map(a => a.name).join(', ');
            thread.find('.chat-thread-message-attachments').attr('data-length', attachments.length).find('span').text(shortenText(attachments, 80));

            rearrangeThreads();
        }

        const shortenText = (text, max) => {
            // Check whether we should take a substring of the text.
            // Reserve an additional 25% of characters to avoid breaking the
            // text too close to the end of the string.
            if (max && text.length > max * 1.25) {
                // explode the string in words
                let chunks = text.split(' ');

                text = '';

                // keep adding words until we reach the maximum threshold
                while (chunks && text.length < max) {
                    text += chunks.shift() + ' ';
                }

                // get rid of trailing special characters and add the ellipsis
                text = text.replace(/[.,?!;:#'"([{ ]+$/, '') + '...';
            }

            return text;
        }

        $(document).on('click', '#<?php echo $id; ?> .chat-thread[data-context][data-id]', function() {
            if ($(this).hasClass('active')) {
                return false;
            }

            // always destroy and previously open chat
            VBOChat.getInstance().destroy();

            $('#<?php echo $id; ?>').find('.chat-thread[data-context][data-id].active').removeClass('active');
            $(this).addClass('active');

            // append loading box to the chat target
            $('#<?php echo $id; ?>').find('.vbo-chat-target').append(
                $('<div class="vbo-chat-loading"><?php VikBookingIcons::e('circle-notch', 'fa-spin fa-3x'); ?></div>')
            ).addClass('slide-in');

            VBOChatAjax.do(
                '<?php echo VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikbooking&task=chat.render_chat'); ?>',
                {
                    context: $(this).data('context'),
                    id_context: $(this).data('id'),
                },
                (resp) => {
                    $('#<?php echo $id; ?>').find('.vbo-chat-target').html(resp.html);
                },
                (err) => {
                    alert(err.responseText);
                }
            );
        });

        window.addEventListener('chat.sync', (event) => {
            refreshThread(event.detail.chat);
        });

        window.addEventListener('chat.send', (event) => {
            refreshThread(event.detail.chat);
        });

        window.addEventListener('chat.read', (event) => {
            const context = event.detail.chat.data.environment.context;
            const thread = $('#<?php echo $id; ?>').find('.chat-thread[data-context="' + context.alias + '"][data-id="' + context.id + '"]');

            if (!thread.length) {
                return;
            }

            // mark thread as read
            thread.attr('data-read', 1);   
        });

        let isLoadingOlderThreads = false;
        let totalThreads = <?php echo count($threads); ?>;
        let threadsLimit = <?php echo $options['limit'] ?? 20; ?>;

        const createLoadingSkeleton = (count) => {
            let skeleton = '';

            for (let i = 1; i <= count; i++) {
                skeleton += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton chat-thread">';
                skeleton += '   <div class="vbo-dashboard-guest-activity-avatar">';
                skeleton += '       <div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
                skeleton += '   </div>';
                skeleton += '   <div class="vbo-dashboard-guest-activity-content chat-thread-content">';
                skeleton += '       <div class="vbo-dashboard-guest-activity-content-head">';
                skeleton += '           <div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
                skeleton += '       </div>';
                skeleton += '       <div class="vbo-dashboard-guest-activity-content-subhead">';
                skeleton += '           <div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>';
                skeleton += '       </div>';
                skeleton += '       <div class="vbo-dashboard-guest-activity-content-info-msg">';
                skeleton += '           <div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
                skeleton += '       </div>';
                skeleton += '   </div>';
                skeleton += '</div>';
            }

            return skeleton;
        }

        const loadPreviousThreads = () => {
            if (isLoadingOlderThreads) {
                // do not proceed in case we are already loading something
                return this;
            }

            // mark loading flag
            isLoadingOlderThreads = true;

            const threadsList = $('#<?php echo $id; ?>').find('.vbo-chat-threads');
            threadsList.append(createLoadingSkeleton(5));

            // make AJAX request to load older threads
            VBOChatAjax.do(
                // end-point URL
                '<?php echo VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikbooking&task=chat.load_chats'); ?>',
                // POST data
                {
                    start: totalThreads,
                    limit: threadsLimit,
                    options: <?php echo json_encode($options ?? []); ?>,
                },
                // success callback
                (threads) => {
                    // keep current scroll
                    let currentScrollTop    = threadsList[0].scrollTop;
                    let currentScrollHeight = threadsList[0].scrollHeight;

                    // remove loading skeleton
                    threadsList.find('.vbo-dashboard-guest-activity-skeleton').remove();

                    threads.forEach((thread) => {
                        const existing = $('#<?php echo $id; ?>').find('.chat-thread[data-context="' + thread.alias + '"][data-id="' + thread.id + '"]');

                        if (!existing.length) {
                            // add thread only in case it is not already in the list
                            threadsList.append(thread.html);
                        }
                    });

                    // update count of loaded threads
                    totalThreads += threads.length;

                    // turn off scroll event in case we reached the limit
                    if (threads.length < threadsLimit) {
                        threadsList.off('scroll');
                    }

                    // make loading available again
                    isLoadingOlderThreads = false;
                },
                // failure callback
                (error) => {
                    // remove loading skeleton
                    threadsList.find('.vbo-dashboard-guest-activity-skeleton').remove();
                    // make loading available again
                    isLoadingOlderThreads = false;
                }
            );
        }
        
        $(function() {
            // do not register scroll event in case the number of messages is equal or
            // higher then the total number of messages under this context
            if (totalThreads >= threadsLimit) {
                // setup scroll event to load older messages
                $('#<?php echo $id; ?>').find('.vbo-chat-threads').on('scroll', function() {
                    if (isLoadingOlderThreads) {
                        // ignore if we are currently loading older messages
                        return;
                    }

                    // get scrollable pixel
                    const scrollHeight = this.scrollHeight - $(this).outerHeight();
                    // get scroll top
                    const scrollTop = this.scrollTop;

                    // start loading older threads only when scrollbar
                    // is 300px close to the end
                    if (scrollHeight - scrollTop <= 300) {
                        loadPreviousThreads();
                    }
                });
            }

            $('#<?php echo $id; ?>').find('.vbo-chat-back').on('click', function() {
                $(this).hide();
                $('#<?php echo $id; ?>').find('.chat-thread[data-context][data-id].active').removeClass('active');
                $(this).prev().removeClass('slide-in');

                setTimeout(() => {
                    $(this).show();
                }, 300);
            });
        });
    })(jQuery);
</script>