(function($, w) {
    'use strict';

    const refreshBadgeCount = (chat, el) => {
        if (!chat) {
            // session not started
            return;
        }

        // observe only the current chat
        if (chat.data.environment.context.alias != 'session') {
            return;
        }

        $(el).find('.vbo-chat-widget-toggle [data-unread]').attr('data-unread', chat.getUnreadMessagesCount());
    }

    const startSession = (data) => {
        return new Promise((resolve, reject) => {
            VBOChatAjax.do(
                VBO_CHAT_WIDGET_CONFIG.startSessionUrl,
                data,
                (resp) => {
                    resolve(resp.id);
                },
                (error) => {
                    reject(error);
                }
            );
        })
    }

    const renderChat = (contextId) => {
        return new Promise((resolve, reject) => {
            VBOChatAjax.do(
                VBO_CHAT_WIDGET_CONFIG.renderChatUrl,
                {
                    context: 'session',
                    id_context: contextId,
                },
                (resp) => {
                    resolve(resp.html);
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    const getWidgetChat = () => {
        const chat = VBOChat.getInstance();
        return chat.data ? chat : null;
    }

    const toggleGreetingsPanel = (el, visible) => {
        if (visible) {
            $(el).find('.vbo-chat-session-placeholder').show();

            const chat = getWidgetChat();

            if (chat && (chat.input.getValue() || '').length == 0) {
                setTimeout(() => {
                    $(el).find('.vbo-chat-placeholder-faqs').addClass('slide-in');
                }, 400);
            }
        } else {
            $(el).find('.vbo-chat-session-placeholder')
                .hide()
                .find('.vbo-chat-placeholder-faqs')
                    .removeClass('slide-in');
        }
    }

    $(function() {
        const modEl = $(VBO_CHAT_WIDGET_CONFIG.selector);

        setTimeout(() => {
        	modEl.find('.vbo-chat-widget-toggle').addClass('bounce-in');
        }, 500);

        // refresh badge count on load
        refreshBadgeCount(getWidgetChat(), modEl);

        // open chat when clicking the show button
        modEl.find('a[data-role="widget.chat.show"]').on('click', () => {
            modEl.find('.vbo-chat-widget-toggle').hide().removeClass('bounce-in');
            modEl.find('.vbo-chat-widget-container').addClass('slide-in');

            const chat = getWidgetChat();

            if (chat) {
                // show greetings panel in case of empty messages
                toggleGreetingsPanel(modEl, chat.data.environment.messages.length == 0);

                chat.scrollToBottom();
                chat.input.focus();

                // auto-read the messages when the chat is open
                chat.data.environment.options.autoread = true;
                chat.readNotifications();
            }
        });

        // hide chat when clicking the dismiss button
        modEl.find('a[data-role="widget.chat.dismiss"]').on('click', () => {
            modEl.find('.vbo-chat-widget-container').removeClass('slide-in');
            modEl.find('.vbo-chat-widget-toggle').show().addClass('bounce-in');

            const chat = getWidgetChat();

            if (chat) {
                // do not auto-read the messages when the chat is closed
                chat.data.environment.options.autoread = false;
            }
        });

        // when the chat is prepared, choose whether the greetings panel should be displayed or not
        w.addEventListener('chat.prepare', (event) => {
            const chat = event.detail.chat;

            // observe only the current chat
            if (chat.data.environment.context.alias != 'session') {
                return;
            }

            toggleGreetingsPanel(modEl, chat.data.environment.messages.length == 0);
        });

        // refresh badge counter whenever the chat downloads new messages
        w.addEventListener('chat.sync', (event) => {
            refreshBadgeCount(event.detail.chat, modEl);
        });

        // refresh the badge counter whenever the chat reads the unread messages
        w.addEventListener('chat.read', (event) => {
            refreshBadgeCount(event.detail.chat, modEl);
        });

        // hide chat greetings after sending the first message
        w.addEventListener('chat.send.before', (event) => {
        	if (event.detail.chat.data.environment.context.alias == 'session') {
                toggleGreetingsPanel(modEl, false);
        	}
        });

        // start chat session after clicking the button
        modEl.find('.start-chat-session').on('click', async function() {
            const user = {};
            let valid = true;

            const form = modEl.find('.vbo-chat-session-start-form');

            form.find('input').filter('[name]').each(function() {
                if (!$(this).is(':valid')) {
                    $(this).addClass('invalid');
                    return valid = false;
                }

                $(this).removeClass('invalid');

                user[$(this).attr('name')] = $(this).val();
            });

            if (!valid) {
                return false;
            }

            // disable button to prevent duplicate requests
            $(this).prop('disabled', true).append('<span class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></span>');
            form.find('input').filter('[name]').prop('readonly', true);

            try {
                // start the session and obtain a unique ID
                const sessionId = await startSession(user);

                // load the chat contents
                const chatDOM = await renderChat(sessionId);

                // inject the HTML of the chat within the session target node
                modEl.find('.vbo-chat-session-start-form').remove();
                modEl.find('.chat-session-target').html(chatDOM);
            } catch (error) {
                // enable button again
                $(this).prop('disabled', false).find('.loading-spinner').remove();
                form.find('input').filter('[name]').prop('readonly', false);

                // display error to user
                VBOToast.dispatch(new VBOToastMessage({
                    body: error.responseText || error.statusText || 'Connection lost.',
                    status: VBOToast.ERROR_STATUS,
                }));
            }
        });

        // auto-populate the chat textarea with the message of the clicked FAQ
        modEl.find('a[data-question]').on('click', function() {
            const msg = $(this).data('question');

            // hide hints
            $(this).closest('.vbo-chat-placeholder-faqs').removeClass('slide-in');

            // set message into the textarea
            const chat = getWidgetChat();
            chat.input.setValue(msg);
            chat.input.focus();
        });
    });
})(jQuery, window);