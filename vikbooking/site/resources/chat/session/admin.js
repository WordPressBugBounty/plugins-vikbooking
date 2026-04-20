(function($, w) {
    'use strict';

    const updateMetadata = (chat, key, val) => {
        return new Promise((resolve, reject) => {
            VBOChatAjax.do(
                chat.data.environment.url,
                {
                    task: 'chat.update_metadata',
                    id_context: chat.data.environment.context.id,
                    context: chat.data.environment.context.alias,
                    key: key,
                    val: val,
                },
                (resp) => {
                    resolve();
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    const summarizeConversation = (chat) => {
        return new Promise((resolve, reject) => {
            // convert messages into an acceptable format
            const messages = chat.data.environment.messages.map((msg) => {
                return {
                    role: msg.id_sender == -1 ? 'user' : 'assistant',
                    content: msg.message,
                    name: msg.sender_name,
                };
            });

            VBOChatAjax.do(
                chat.data.environment.url,
                {
                    task: 'ai.summarize',
                    messages: messages.reverse(),
                },
                (resp) => {
                    resolve(resp.result);
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    const sendMessageTemplate = (chat, configId) => {
        return new Promise((resolve, reject) => {
            // make the request
            VBOCore.doAjax(
                chat.data.environment.url,
                {
                    task: 'chat.send_template',
                    session_id: chat.data.environment.context.id,
                    config_id: configId,
                },
                (resp) => {
                    resolve(resp);
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    const showContextToast = (chat, panel) => {
        // identify the target that should hold the info panel
        const targetEl = $(chat.data.element.conversation).closest('.chat-messages-panel');

        let toast = targetEl.find('.chat-session-toast');

        if (!toast.length) {
            // create toast template
            toast = $('<div class="chat-session-toast">\n'+
                '    <div class="chat-session-toast-message">\n'+
                '        <div class="chat-session-toast-message-content"></div>\n'+
                '    </div>\n'+
                '</div>');

            // append toast HTML to target only once
            targetEl.append(toast);
        }

        // update toast panel content
        toast.find('.chat-session-toast-message-content').html(panel);

        let dismissHandler = toast.find('[data-role="popup.dismiss"]');

        // disable click event
        toast.off('click');
        $(document).off('click', '.chat-session-toast [data-role="popup.dismiss"]');

        if (!dismissHandler.length) {
            // dismiss when clicking inside the toast
            toast.css('cursor', 'pointer').on('click', () => {
                toast.removeClass('slide-in');
            });
        }

        // dispose when clicked observer element
        $(document).on('click', '.chat-session-toast [data-role="popup.dismiss"]', () => {
            toast.removeClass('slide-in');
        });

        setTimeout(() => {
            toast.addClass('slide-in');
        }, 256);

        return toast;
    }

    const calcNights = (checkin, checkout) => {
        const start = new Date(checkin + 'T00:00:00Z');
        const end = new Date(checkout + 'T00:00:00Z');

        return Math.floor((end - start) / (1000 * 60 * 60 * 24));
    }

    /*********************
     * BAN/UNBAN SESSION *
     *********************/

    /**
     * Ban/unban session button text handler.
     */
    $(w).on('chat.session.ban.text', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.displayText = Joomla.JText._(chat.data.environment.context.metadata.banned ? 'VBO_CHAT_UNBAN_SESSION' : 'VBO_CHAT_BAN_SESSION');
    });

    /**
     * Ban/unban session button icon handler.
     */
    $(w).on('chat.session.ban.icon', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.displayIcon = 'fas fa-' + (chat.data.environment.context.metadata.banned ? 'check-circle' : 'ban');
    });

    /**
     * Ban/unban session button action handler.
     */
    $(w).on('chat.session.ban.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        const context = chat.data.environment.context;

        try {
            await updateMetadata(chat, 'banned', context.metadata.banned ? 0 : 1);
            context.metadata.banned = !context.metadata.banned;

            $(chat.data.element.conversation).parent().css('background', context.metadata.banned ? '#d003' : 'inherit');
        } catch (error) {
            console.error(error);

            alert(error.responseText || error.statusText || 'Connection lost!');
        }
    });

    /**************************
     * STOP/RESUME AI REPLIES *
     **************************/

    /**
     * Stop/resume AI session button text handler.
     */
    $(w).on('chat.session.ai.autoreply.text', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.displayText = Joomla.JText._(chat.data.environment.context.metadata.use_ai ? 'VBO_CHAT_STOP_AI_SESSION' : 'VBO_CHAT_RESUME_AI_SESSION');
    });

    /**
     * Stop/resume AI session button icon handler.
     */
    $(w).on('chat.session.ai.autoreply.icon', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.displayIcon = 'fas fa-' + (chat.data.environment.context.metadata.use_ai ? 'comment-slash' : 'comment');
    });

    /**
     * Stop/resume AI session button action handler.
     */
    $(w).on('chat.session.ai.autoreply.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        const context = chat.data.environment.context;

        try {
            await updateMetadata(chat, 'use_ai', context.metadata.use_ai ? 0 : 1);
            context.metadata.use_ai = !context.metadata.use_ai;
        } catch (error) {
            console.error(error);

            alert(error.responseText || error.statusText || 'Connection lost!');
        }
    });

    /**************************
     * SUMMARIZE CONVERSATION *
     **************************/

     /**
     * AI summarize conversation button disabled status handler.
     */
    $(w).on('chat.session.ai.summarize.disabled', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // disable in case the popup is already visible
        event.shouldDisable = $(chat.data.element.conversation)
            .closest('.chat-messages-panel')
                .find('.chat-session-toast')
                    .hasClass('slide-in');
    });

    /**
     * AI summarize conversation button action handler.
     */
    $(w).on('chat.session.ai.summarize.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        const popup = $('<div class="chat-ai-summary"></div>');

        const displaySummary = (summary) => {
            popup.html('');

            popup.append(
                $('<div class="query-summary-head"></div>')
                    .append($('<span></span>').text(Joomla.JText._('VBO_CHAT_SUMMARIZE_TITLE')))
                    .append('<a href="javascript:void(0)" data-role="popup.dismiss"><i class="fas fa-times"></i></a>')
            );

            popup.append($('<div class="ai-summary-area"></div>').html(summary));
        }

        // use cached value (if available)
        if (chat.data.environment.context.aiSummary) {
            displaySummary(chat.data.environment.context.aiSummary);
            showContextToast(chat, popup);
            return;
        }

        popup.html('<div class="chat-loading"><i class="fas fa-spinner fa-spin fa-3x"></i></div>');
        const toast = showContextToast(chat, popup);

        // wait until the toast is fully visible
        setTimeout(async () => {
            try {
                if (!button.supported) {
                    // VCM 1.9.19 required to support AI summarize service
                    throw new Error("Update VikChannelManager to the latest version first.");
                }

                const summary = await summarizeConversation(chat);

                // internally cache result to prevent duplicate requests
                chat.data.environment.context.aiSummary = summary;

                displaySummary(summary);

                // disable dismiss on toast click
                toast.off('click').css('cursor', 'default');
            } catch (error) {
                console.error(error);

                if (error instanceof Error) {
                    error = {responseText: error};
                }

                popup.addClass('error-response').text(error.responseText || error.statusText || 'Connection lost!');
            }
        }, 600);
    });

    /***********************
     * SESSION QUOTE QUERY *
     ***********************/

    /**
     * Quote session query button visibility handler.
     */
    $(w).on('chat.session.query.quote.visible', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.shouldDisplay = chat.data.environment.context.metadata?.query?.checkin
            && chat.data.environment.context.metadata?.query?.checkout;
    });

    /**
     * Quote session query button action handler.
     */
    $(w).on('chat.session.query.quote.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // open quotation maker on a blank page
        window.open(button.url, '_blank');
    });

    /*********************
     * SESSION SEE QUERY *
     *********************/

    /**
     * See session query button visibility handler.
     */
    $(w).on('chat.session.query.see.visible', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        event.shouldDisplay = chat.data.environment.context.metadata?.query?.checkin
            && chat.data.environment.context.metadata?.query?.checkout;
    });

    /**
     * See session query button disabled status handler.
     */
    $(w).on('chat.session.query.see.disabled', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // disable in case the popup is already visible
        event.shouldDisable = $(chat.data.element.conversation)
            .closest('.chat-messages-panel')
                .find('.chat-session-toast')
                    .hasClass('slide-in');
    });

    /**
     * See session query button action handler.
     */
    $(w).on('chat.session.query.see.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        const query = chat.data.environment.context.metadata.query;

        const popup = $('<div class="chat-query-summary"></div>');

        popup.append(
            $('<div class="query-summary-head"></div>')
                .append($('<span></span>').text(Joomla.JText._('VBO_CHAT_QUERY_SUMMARY_TITLE')))
                .append('<a href="javascript:void(0)" data-role="popup.dismiss"><i class="fas fa-times"></i></a>')
        );

        //////////////////////////////////////////////////

        const body = $('<div class="query-summary-body"></div>');

        //////////////////////////////////////////////////

        const datesBox = $('<div class="query-dates-box"></div>');

        datesBox.append(
            $('<div class="query-dates-title"></div>')
                .append('<i class="fas fa-calendar"></i>')
                .append($('<span></span>').text(Joomla.JText._('VBO_CONDTEXT_RULE_STAYDATES')))
        );

        datesBox.append(
            $('<div class="query-dates-main"></div>')
                .append($('<span></span>').text(query.checkin))
                .append('<i class="fas fa-arrow-right"></i>')
                .append($('<span></span>').text(query.checkout))
        );

        const nights = calcNights(query.checkin, query.checkout);
        datesBox.append(
            $('<div class="query-dates-sub"></div>').text(
                Joomla.JText._(nights > 1 ? 'VBOSEASONCALNUMNIGHTS' : 'VBOSEASONCALNUMNIGHT').replace(/%d/, nights).toLowerCase()
            )
        );

        body.append(datesBox);

        //////////////////////////////////////////////////

        const guestsBox = $('<div class="query-guests-box"></div>');

        guestsBox.append(
            $('<div class="query-guests-title"></div>')
                .append('<i class="fas fa-users"></i>')
                .append($('<span></span>').text(Joomla.JText._('VBO_NOTIFS_GROUP_GUESTS')))
        );

        guestsBox.append('<div class="query-guests-main"></div>');
        query.parties.forEach((party, index) => {
            const guestDetails = $('<strong></strong>');

            let components = [
                Joomla.JText._(party.adults > 1 ? 'VBO_N_ADULTS' : 'VBO_N_ADULTS_1').replace(/%d/, party.adults)
            ];

            if (party?.children) {
                components.push(Joomla.JText._(party.children > 1 ? 'VBO_N_CHILDREN' : 'VBO_N_CHILDREN_1').replace(/%d/, party.children));
            }

            guestDetails.text(components.join(', '));

            let guestBox = $('<div class="query-quest-info"></div>');
            guestBox.append($('<div class="query-quest-info-left"></div>').text(Joomla.JText._('VBMAILROOMNUM') + (index + 1)));
            guestBox.append($('<div class="query-quest-info-right"></div>').append(guestDetails));

            if (party?.pets) {
                guestBox.find('.query-quest-info-right').append('<br>').append(
                    $('<small></small>').text((party.pets + ' ' + Joomla.JText._(party.pets > 1 ? 'VBO_PETS' : 'VBO_PET').toLowerCase()))
                );
            }

            guestsBox.find('.query-guests-main').append(guestBox);
        });

        body.append(guestsBox);

        //////////////////////////////////////////////////

        if ((query?.rooms || []).length) {
            const roomsBox = $('<div class="query-rooms-box"></div>');

            roomsBox.append(
                $('<div class="query-rooms-title"></div>')
                    .append('<i class="fas fa-bed"></i>')
                    .append($('<span></span>').text(Joomla.JText._('VBO_CHAT_QUERY_SUMMARY_PREF_ROOMS')))
            );

            roomsBox.append($('<div class="query-rooms-main"></div>'));

            query.rooms.forEach((room) => {
                roomsBox.find('.query-rooms-main').append(
                    $('<span class="badge badge-info"></span>').text(room.name)
                );
            });
            

            body.append(roomsBox);
        }

        //////////////////////////////////////////////////

        popup.append(body);

        showContextToast(chat, popup);
    });

    /**************************
     * SEND WHATSAPP TEMPLATE *
     **************************/

    /**
     * See session query button action handler.
     */
    $(w).on('chat.session.messaging.sendtmpl.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        const popup = $('<div class="chat-query-summary"></div>');

        popup.append(
            $('<div class="query-summary-head"></div>')
                .append($('<span></span>').text(Joomla.JText._('VBO_MESSAGE_TEMPLATE')))
                .append('<a href="javascript:void(0)" data-role="popup.dismiss"><i class="fas fa-times"></i></a>')
        );

        //////////////////////////////////////////////////

        const body = $('<div class="query-summary-body"></div>');

        body.append('<div class="messaging-tmpl-preview" style="display: none;"></div>');
        body.append('<div class="messaging-tmpl-select"></div>');

        //////////////////////////////////////////////////

        const tmplSelect = $('<select></select>');

        // add placeholder option
        tmplSelect.append(
            $('<option value=""></option>').text(Joomla.JText._('JGLOBAL_SELECT_AN_OPTION'))
        );

        // build template select options
        (button.templates || []).forEach((tmpl) => {
            tmplSelect.append(
                $('<option></option>').text(`${tmpl.name} (${tmpl.lang})`).val(tmpl.identifier)
            );
        });

        // append dropdown to popup body
        body.find('.messaging-tmpl-select').append(tmplSelect);

        // build button to send the template
        const sendButton = $('<button type="button" class="btn btn-success" style="display: none;"><i class="fas fa-paper-plane no-margin"></i></button>');

        // append send button to popup body
        body.find('.messaging-tmpl-select').append(sendButton);

        //////////////////////////////////////////////////

        popup.append(body);

        const toast = showContextToast(chat, popup);

        // handle template change event
        tmplSelect.on('change', (event) => {
            // find selected template
            const tmpl = button.templates.find(tmpl => tmpl.identifier === tmplSelect.val());

            if (tmpl) {
                // display template preview
                body.find('.messaging-tmpl-preview').html(tmpl.preview_html).show();
                sendButton.show();
            } else {
                // hide template preview
                body.find('.messaging-tmpl-preview').hide().html('');
                sendButton.hide();
            }
        });

        // handle template send event
        sendButton.on('click', async (event) => {
            sendButton.prop('disabled', true).find('i').attr('class', 'fas fa-spinner fa-spin no-margin');

            // obtain selected template details
            let tplIdentifierParts = tmplSelect.val().split(':');

            try {
                // send template
                await sendMessageTemplate(chat, tplIdentifierParts[2]);

                // dismiss toast
                toast.find('[data-role="popup.dismiss"]').trigger('click');

                // download new message
                chat.synchronizeMessages();
            } catch (error) {
                console.error(error);

                alert(error.responseText || error.statusText || 'Connection lost!');

                // enable button again
                sendButton.prop('disabled', false).find('i').attr('class', 'fas fa-paper-plane no-margin');
            }
        });
    })

    /***************
     * CHAT EVENTS *
     ***************/

    /**
     * Fires when a chat is prepared and ready to be used.
     * Changes the background color depending on the "banned" status.
     */
    window.addEventListener('chat.prepare', (event) => {
        const {chat} = event.detail;

        if (chat.data.environment.user.id != 0 || chat.data.environment.context.alias !== 'session') {
            // ignore if we are not visiting the chat as admin
            return;
        }

        if (chat.data.environment.context.metadata.banned) {
            // change background for banned sessions
            $(chat.data.element.conversation).parent().css('background', '#d003');
        }
    });

})(jQuery, window);