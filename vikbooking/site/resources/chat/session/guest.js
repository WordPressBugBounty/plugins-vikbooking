(function($, w) {
    'use strict';

    /*****************
     * SHARE SESSION *
     *****************/

    /**
     * Share session disabled status handler.
     */
    $(w).on('chat.session.share.disabled', (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // disable the button in case we have no messages
        event.shouldDisable = chat.data.environment.messages.length ? false : true;
    });

    /**
     * Session session button action handler.
     */
    $(w).on('chat.session.share.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // remove the any textarea previously created
        $('textarea#chat-session-copy-area').remove();

        const copyArea = $('<textarea id="chat-session-copy-area"></textarea>')
            .val(button.url)
            .css('width', 0)
            .css('height', 0)
            .css('opacity', 0)
            .css('float', 'right');

        $('body').append(copyArea);

        // copy URL within the clipboard
        VBOCore.copyToClipboard(copyArea[0]).then((success) => {
            VBOToast.dispatch(new VBOToastMessage({
                body: 'URL copied to clipboard!',
                status: VBOToast.SUCCESS_STATUS,
                delay:  'auto',
                action: () => {
                    VBOToast.dispose(true);
                },
            }));

            copyArea.remove();
        }).catch((err) => {
            // make textarea visible on screen again
            copyArea.css('width', '100%');
            copyArea.css('height', '50px');
            copyArea.css('opacity', 1);
            copyArea.css('float', 'none');
            copyArea.css('resize', 'none');
            copyArea.css('margin', '10px 0 0 0');
            copyArea.prop('readonly', true);

            // auto-dispose the toast after copying the contents
            copyArea.on('copy', () => {
                setTimeout(() => VBOToast.dispose(true), 1000);
            });

            // prevent native event used to dispose the toast on click
            copyArea.on('click', (event) => {
                // reselect the whole text
                copyArea.select();

                // prevent the toast from closing
                event.preventDefault();
                event.stopPropagation();
                return false;
            });

            const body = $('<div>Unable to copy URL to clipboard! Please proceed manually.</div>')
                .append(copyArea);

            VBOToast.dispatch(new VBOToastMessage({
                body: body,
                status: VBOToast.ERROR_STATUS,
                delay:  0, // do not auto-dispose
                callback: () => {
                    // auto select textarea and focus it
                    copyArea.select().focus();
                },
            }));
        });
    });

    /*********************
     * START NEW SESSION *
     *********************/

    const restartSession = (chat) => {
        return new Promise((resolve, reject) => {
            // make the request
            VBOChatAjax.do(
                chat.data.environment.url,
                {
                    task: 'chat.end_session',
                    restart: 1,
                },
                (resp) => {
                    resolve(resp.id);
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    const refreshChat = (chat, contextId) => {
        return new Promise((resolve, reject) => {
            VBOChatAjax.do(
                chat.data.environment.url,
                {
                    task: 'chat.render_chat',
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

    /**
     * Restart session disabled status handler.
     */
    $(w).on('chat.session.restart.disabled', (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.shouldDisable = true;

        // iterate all the messages
        for (let i = 0; i < chat.data.environment.messages.length; i++) {
            let message = chat.data.environment.messages[i];

            // in case we have a message sent by another user, enable the button
            if (!chat.isSender(message)) {
                event.shouldDisable = false;
            }
        }
    });

    /**
     * Restart session button action handler.
     */
    $(w).on('chat.session.restart.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        if (!confirm(Joomla.JText._('VBO_WANT_PROCEED'))) {
            // ignored by the user
            return;
        }

        // identify the target chat element
        const chatTargetEl = $(chat.data.element.conversation).closest('.chat-session-target');

        try {
            // close current session and start a new one
            const sessionId = await restartSession(chat);
            // refresh chat
            const newChatHTML = await refreshChat(chat, sessionId);

            // destroy chat after renewing the session
            chat.destroy();
                
            // replace HTML
            chatTargetEl.html(newChatHTML);
        } catch (error) {
            console.error(error);

            chat.alert(error.responseText || error.statusText || 'Connection lost!');
        }
    });

    /***************
     * CHAT EVENTS *
     ***************/

    /**
     * Fires after preparing the conversation.
     * Changes the native alert behavior applied by the chat.
     * 
     * For guest only.
     */
    window.addEventListener('chat.prepare', (event) => {
        const {messages, chat} = event.detail;

        if (chat.data.environment.context.alias !== 'session') {
            // ignore if we are not visiting the chat for a different context
            return;
        }

        // prefer a toast message rather than a system alert
        chat.alert = (error) => {
            // display the error through toast
            VBOToast.dispatch(new VBOToastMessage({
                body: error,
                status: VBOToast.ERROR_STATUS,
                delay: 'auto',
            }));
        }
    });

    /**
     * Fires before the chat delivers the message.
     * Displays a "Thinking..." bubble in case the AI is enabled.
     * 
     * For guest only.
     */
    window.addEventListener('chat.send.before', (event) => {
        const {message, chat} = event.detail;

        if (chat.data.environment.context.alias !== 'session') {
            // ignore if we are not visiting the chat for a different context
            return;
        }

        if (chat.data.environment.users.hasOwnProperty(-2) == false) {
            // do not go ahead in case the AI is not involved
            return;
        }

        if (!chat.data.environment.context.metadata.use_ai) {
            // do not go ahead in case the AI has been manually blocked
            return;
        }

        if ($('#msg-ai-typing').length) {
            // do not add another placeholder
            return;
        }

        // build dummy object
        const dummy = {
            id: 'msg-ai-typing',
            message: Joomla.JText._('VBO_CHAT_AI_THINKING').replace(/\.+$/, '') + '<span class="ai-thinking-dots"><span></span><span></span><span></span></span>',
            id_sender: -2,
            sender_name: 'AI',
            createdon: DateHelper.toStringUTC(new Date()),
        };

        setTimeout(() => {
            // draw message within the chat
            chat.drawMessage(dummy);
            // force scroll to bottom
            setTimeout(() => chat.scrollToBottom(), 32);
        }, 512);
    });

    /**
     * Fires in case the chat fails to deliver a message.
     * Removes the "Thinking..." bubble previously displayed.
     * 
     * For guest only.
     */
    window.addEventListener('chat.send.failed', (event) => {
        const {message, chat, error} = event.detail;

        if (chat.data.environment.context.alias !== 'session') {
            // ignore if we are not visiting the chat for a different context
            return;
        }

        if (error.status == 403) {
            // in case of forbidden error, prevent replies by removing the message from the chat
            chat.removeMessage(message.id, true);

            // display the error through toast
            VBOToast.dispatch(new VBOToastMessage({
                body: error.responseText || error.statusText || 'Forbidden!',
                status: VBOToast.ERROR_STATUS,
            }));
        }

        setTimeout(() => {
            // remove placeholder on failure
            $('#msg-ai-typing').remove();
        }, 512);
    });

    /**
     * Fires before syncing the conversation.
     * Removes the "Thinking..." bubble previously displayed.
     * 
     * For guest only.
     */
    window.addEventListener('chat.sync.before', (event) => {
        const {messages, chat} = event.detail;

        if (chat.data.environment.context.alias !== 'session') {
            // ignore if we are not visiting the chat for a different context
            return;
        }

        // remove thinking bubble only if the response contains at least a message wrote by AI
        if (messages.some(message => message.id_sender == -2)) {
            // remove the "thinking" bubble before syncing the messages
            $('#msg-ai-typing').remove();
        }
    });

})(jQuery, window);