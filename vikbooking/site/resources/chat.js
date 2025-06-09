(function($, w) {
    'use strict';

    /**
     * VBOChat class.
     * Singleton used to handle a CHAT client.
     */
    w['VBOChat'] = class VBOChat {

        /**
         * Returns a new chat instance.
         * 
         * @param   object  data    The environment options.
         *
         * @return  VBOChat
         */
        static getInstance(data) {
            if (data && VBOChat?.instance?.data) {
                // the chat was already initialized, preserve the previous data in a static queue
                VBOChat.queue.push(VBOChat.instance.data);
            }

            if (VBOChat.instance === undefined || typeof data !== 'undefined') {
                VBOChat.instance = new VBOChat(data);
            }

            if (!VBOChat.instance.data && data) {
                // instantiate a new object with the injected data
                VBOChat.instance = new VBOChat(data);
            }

            return VBOChat.instance;
        }

        /**
         * Class constructor.
         *
         * @param  object  data  The environment options.
         */
        constructor(data) {
            this.data = data;

            if (this.data) {
                if (this.data.environment.users === undefined) {
                    this.data.environment.users = {};
                }

                if (this.data.environment.messages === undefined) {
                    this.data.environment.messages = [];
                }

                // initialise thread
                this.initThread();

                this.data.environment.id = this.data.environment.messages.length;
                this.data.environment.datetime = new Date();
                this.data.environment.attachments = [];

                // use default values if not provided
                this.data.environment.options = Object.assign({
                    syncTime: 10,
                    limit: 20,
                    autoread: true,
                }, this.data.environment.options || {});

                if (!this.data.element) {
                    this.data.element = {
                        conversation: $(this.data.environment.selector).find('.chat-conversation'),
                        progressBar: $(this.data.environment.selector).find('.chat-progress-wrap'),
                        uploadsBar: $(this.data.environment.selector).find('.chat-uploads-tab'),
                        inputBox: $(this.data.environment.selector).find('.textarea-input'),
                    };
                }
            }

            this.timers = [];
        }

        /**
         * Initialises the specified thread.
         *
         * @return  self
         */
        initThread() {
            const thread = {};

            // keep thread initial date time
            thread.initialDatetime = this.data.environment.messages[0]?.createdon;
            // keep thread initial messages length
            thread.messagesLength  = this.data.environment.messages.length;

            this.data.environment.thread = thread;

            return this;
        }

        /**
         * Prepares the chat client to be up and running by initializing the last conversation made.
         * The synchronization with the server is made here.
         *
         * @return  self
         */
        prepare() {
            if (this.isPrepared) {
                // do not execute again
                return this;
            }

            this.isPrepared = true;

            // register e-mail content parser
            this.attachContentParser('email', function(content) {
                // wrap any e-mail addresses within a "mailto" link
                content = content.replace(/[a-z0-9][a-z0-9._\-]{1,63}@(?:[a-z][a-z0-9\-]{1,62}\.?){1,3}\.[a-z][a-z0-9]{1,62}/gi, function(mail) {
                    return '<a href="mailto:' + mail + '">' + mail + '</a>';
                });

                return content;
            });

            // register phone content parser
            this.attachContentParser('phone', function(content) {
                // wrap any potential phone numbers within a "tel" link
                content = content.replace(/(^|\s)(?:\+[\d]{1,5})?[\d][\d \-]{3,}[\d](\s|$)/gm, function(phone) {
                    return '<a href="tel:' + phone + '">' + phone + '</a>';
                });

                return content;
            });

            // register URL content parser
            this.attachContentParser('url', function(content) {
                // wrap any plain URLs within a link
                content = content.replace(/https?:\/\/(www\.)?[a-zA-Z0-9@:%._\+~#=\-]{2,256}\.[a-z]{2,6}\b([a-zA-Z0-9@:%_\+.~#?&\/\/=\-;]*)/gi, function(url) {
                    return '<a href="' + url + '" target="_blank">' + url + '</a>';
                });

                return content;
            });

            this.renderInput()
                .startConversation()
                .buildConversation()
                .readNotifications();

            // calculate interval duration between each sync (use at least 1000 ms)
            const syncDuration = Math.max(1000, this.data.environment.options.syncTime * 1000);

            // sync messages when page loads
            this.synchronizeMessages();

            // try to check if we have new messages to push
            this.timers.push(
                setInterval(() => {
                    this.synchronizeMessages();
                }, syncDuration)
            );

            /**
             * We should run here an interval that re-build the date separators.
             * For example, if we open the chat @ 23:57, the date separators should
             * be changed after the midnight ("Today, 23:56" should become "Yesterday, 23:56").
             *
             * The date of the last message sent/received should be updated too.
             */
            this.timers.push(
                setInterval(() => {
                    const now = new Date();

                    const chat = this;

                    // check if the date has changed since the last check
                    if (!DateHelper.isSameDay(now, chat.data.environment.datetime)) {
                        // update environment datetime
                        chat.data.environment.datetime = now;

                        // iterate all the separators
                        $('.is-a-separator').each(function() {
                            // get separator UTC date
                            let dt  = $(this).attr('data-datetime');
                            let utc = DateHelper.stringToDate(dt);
                            
                            // replace separator with new one
                            $(this).replaceWith(chat.getDateSeparator(utc));
                        });
                    }
                }, 10000)
            );

            return this;
        }

        /**
         * Build the chat conversation.
         * The conversation may not contain all messages.
         *
         * @param   mixed   start   The initial offset of the messages to display.
         *                          If not provided, 0 will be used.
         * @param   mixed   end     The ending offset of the messages to display.
         *                          If not provided, all the cached messages will be shown.
         *
         * @return  self
         */
        buildConversation(start, end) {
            if (start === undefined) {
                start = 0;
            }

            if (end === undefined) {
                end = this.data.environment.messages.length;
            }

            let messages = $('');

            // define queue for failed messages
            let failedQueue = [];

            for (let i = end - 1; i >= start; i--) {
                let message = this.data.environment.messages[i];

                if (message === undefined) {
                    // we reached the end of the list before the expected limit
                    continue;
                }

                if (message.hasError) {
                    // push the message within the failed queue, then go to next item
                    failedQueue.push(message.id);

                    // unset error to avoid sending it twice
                    message.hasError = false;
                    continue;
                }

                // get message template (message, false: no animation, true: get buffer)
                messages = messages.add(this.drawMessage(message, false, true));
            }

            $(this.data.element.conversation).prepend(messages);

            if (start == 0) {
                // in case the chat was empty, auto-scroll conversation to the last message
                this.scrollToBottom();
            }

            // we need to iterate all the failed messages and retry to send them
            for (let i = 0; i < failedQueue.length; i++) {
                // remove message from list
                let mess = this.removeMessage(failedQueue[i]);
                
                if (mess) {
                    // re-send message content
                    this.send(mess.message);
                }
            }

            return this;
        }

        /**
         * Initializes the conversation.
         *
         * @return  self
         */
        startConversation() {
            const chat = this;

            $(this.data.element.conversation).html('');

            // setup environment vars
            this.data.environment.isLoadingOlderMessages = false;

            // clear scroll event
            $(this.data.element.conversation).off('scroll');

            // do not register scroll event in case the number of messages is equal or
            // higher then the total number of messages under this context
            if (this.data.environment.messages.length >= this.data.environment.options.limit) {
                // setup scroll event to load older messages
                $(this.data.element.conversation).on('scroll', function() {
                    if (chat.data.environment.isLoadingOlderMessages) {
                        // ignore if we are currently loading older messages
                        return;
                    }

                    // get scrollable pixel
                    const scrollHeight = this.scrollHeight - $(this).outerHeight();
                    // get scroll top
                    const scrollTop = this.scrollTop;

                    // start loading older messages only when scrollbar
                    // hits the first half of the whole scrollable height
                    if (scrollTop / scrollHeight < 0.5) {
                        // load older chat messages
                        chat.loadPreviousMessages();
                    }

                });
            }

            return this;
        }

        /**
         * Scrolls the chat conversation to the most recent message.
         *
         * @return  self
         */
        scrollToBottom() {
            const convo = $(this.data.element.conversation);

            if (!convo.length) {
                return this;
            }

            convo.scrollTop(convo[0].scrollHeight + 200);

            return this;
        }

        /**
         * Checks whether the chat should scroll.
         * If we are reading older messages, the chat should not scroll.
         * Contrarily, if we are keeping an eye on the latest messages,
         * the chat should scroll to the bottom.
         *
         * @param   integer  threshold  An optional threshold (30px by default).
         *
         * @return  boolean
         */
        shouldScroll(threshold) {
            const conversation = $(this.data.element.conversation)[0];

            // total scrollable amount (we need to exclude the chat height from the scroll height)
            let scrollable = conversation.scrollHeight - $(conversation).outerHeight();
            // get difference between current scroll top and total scroll top
            let diff = Math.abs(scrollable - conversation.scrollTop);

            // scroll only in case we are already at the bottom position,
            // with a maximum threshold of 30 pixel
            return diff <= (threshold || 30);
        }

        /**
         * Returns the index of the message object that matches the specified ID.
         *
         * @param   mixed    id  The message ID.
         *
         * @return  integer  The index of the matching object, otherwise -1.
         */
        getMessageIndex(id) {
            for (let i = 0; i < this.data.environment.messages.length; i++) {
                let message = this.data.environment.messages[i];

                if (message.id == id) {
                    return i;
                }
            }

            return -1;
        }

        /**
         * Returns the message object that matches the specified ID.
         *
         * @param   mixed  id  The message ID.
         *
         * @return  mixed  The matching object, otherwise null.
         */
        getMessage(id) {
            const index = this.getMessageIndex(id);

            if (index != -1) {
                return this.data.environment.messages[index];
            }

            return null;
        }

        /**
         * Removes the message object that matches the specified ID.
         * 
         * @param   mixed    id         The message ID.
         * @param   boolean  strict     True to remove the message from the chat too.
         *
         * @return  mixed   The removed object on success, otherwise false.
         */
        removeMessage(id, strict) {
            const index = this.getMessageIndex(id);

            if (index == -1) {
                return false;
            } 

            // check if the chat message should be removed
            if (strict) {
                if ($('#'+ id).prev().hasClass('is-a-separator')) {
                    // remove previous separator too
                    $('#' + id).prev().remove();
                }

                // remove chat element
                $('#' + id).remove();
            }

            return this.data.environment.messages.splice(index, 1)[0];
        }

        /**
         * Returns the latest message sent/received.
         In this case "latest" means "most recent".
         *
         * @return  mixed  The latest message if any, otherwise null.
         */
        getLatestMessage() {
            return this.data.environment.messages[0] ?? null;
        }

        /**
         * Returns the latest message received that needs to be read.
         * In this case "latest" means "most recent".
         *
         * @return  mixed   The latest unread message if any, otherwise null.
         */
        getLatestUnreadMessage() {
            for (let i = 0; i < this.data.environment.messages.length; i++) {
                let msg = this.data.environment.messages[i];

                if (!msg.read) {
                    return msg;
                }
            }

            return null;
        }

        /**
         * Counts the total number of unread messages.
         * In case there are some unread messages outside the current pagination,
         * they won't be counted, obviously.
         *
         * @return  int  The total count of unread messages.
         */
        getUnreadMessagesCount() {
            let count = 0;

            for (let i = 0; i < this.data.environment.messages.length; i++) {
                let msg = this.data.environment.messages[i];

                if (!msg.read) {
                    count++;
                }
            }

            return count;
        }

        /**
         * Helper function used to calculate the exact position that the
         * new message should occupy.
         * 
         * @param   object  message  The message to add.
         * 
         * @return  int     The correct position.
         */
        findMessagePosition(message) {
            for (let i = 0; i < this.data.environment.messages.length; i++) {
                if (this.data.environment.messages[i].createdon <= message.createdon) {
                    return i;
                }
            }

            return this.data.environment.messages.length;
        }

        /**
         * Returns the next identifier to use for DOM chat messages.
         *
         * @string
         */
        getNextID() {
            return 'msg-' + (++this.data.environment.id);
        }

        /**
         * Checks if the current client is the sender of the message.
         *
         * @return  boolean
         */
        isSender(message) {
            return message.id_sender == this.data.environment.user.id;
        }

        /**
         * Collects the specified message within the internal state and
         * pushes it within the chat conversation.
         *
         * @param   integer  id       The message ID.
         * @param   string   message  The message content.
         * @param   object   sender   The sender details.
         *
         * @return  mixed    The collected object on success, otherwise false.
         */
        collect(id, message, sender) {
            // build dummy object
            const dummy = {
                id: id,
                message: message,
                id_sender: sender.id || 0,
                sender_name: sender.name,
                createdon: DateHelper.toStringUTC(new Date()),
            };

            if (this.data.environment.attachments.length) {
                // push attachments within data
                dummy.attachments = this.data.environment.attachments;
            }

            // draw message within the chat
            this.drawMessage(dummy, true);

            // push dummy data within the messages
            this.data.environment.messages.unshift(dummy);

            return dummy;
        }

        /**
         * Draws the given message within the chat conversation.
         * The method doesn't check whether the message matches
         * the current one.
         *
         * @param   object   message  The message to draw.
         * @param   boolean  animate  True to animate the message entrance.
         * @param   boolean  buffer   True to return the message template.
         *
         * @return  mixed    In case of buffer, the template string will be returned
         *                   instead of being appended within the DOM. Otherwise,
         *                   this object will be returned for chaining.
         */
        drawMessage(message, animate, buffer) {
            // get index of message
            let index = this.getMessageIndex(message.id);

            if (index != -1) {
                // get next message
                index++;
            } else {
                // use first index available as our message might be not yet in the list
                index = 0;
            }

            let template = $('');

            // get last message sent/received
            const prev = this.data.environment.messages.length ? this.data.environment.messages[index] : null;

            let hasSeparator = false;

            if (!prev || DateHelper.diff(message.createdon, prev.createdon, 'minutes') > 10) {
                // write date separator because have passed more than 10 minutes since the previous message
                const dateSeparator = this.getDateSeparator(message.createdon);
                hasSeparator = true;

                template = template.add(dateSeparator);
            }

            // use custom element ID
            const elem_id = isFinite(message.id) ? 'delivered-' + message.id : message.id;

            // make content HTML-safe
            let content = message.message;

            if (!isFinite(message.id)) {
                // make content HTML-safe only for new messages
                content = content.htmlentities();
            }

            // fetch message content
            content = this.renderMessageContent(content);

            // determine sender/recipient type
            const is_sender = this.isSender(message);

            // obtain the details of the user that wrote the message
            const user = this.getMessageUser(message);

            // create the avatar node
            const avatar = this.drawUserAvatar(user);

            // create message content
            const messageContent = $('<div class="speech-bubble"></div>').addClass('message-content ' + (animate ? 'need-animation ' : '') + (is_sender ? 'sent' : 'received'));

            // add message author
            messageContent.append(
                $('<div class="content-author"></div>')
                    .text(user.name || message.sender_name)
                    .attr('data-sender-id', message.id_sender)
                    .attr('data-sender-name', message.sender_name)
            );

            // change name to "You" in case the sender is equal to the logged in user
            if (this.data.environment.user.id == message.id_sender && this.data.environment.user.name == message.sender_name) {
                messageContent.find('.content-author').text(Joomla.JText._('VBO_CHAT_YOU'));
            }

            // fetch last message author
            const lastMessageAuthor = $(this.data.element.conversation).children().last().find('.content-author');
               
            // hide sender name if equal to the previous message (ignore in case the message needs to display a separator above)
            if (!hasSeparator && message.id_sender == lastMessageAuthor.attr('data-sender-id') && message.sender_name == lastMessageAuthor.attr('data-sender-name')) {
                messageContent.find('.content-author').hide();
            }

            // add message text
            messageContent.append($('<div class="content-text"></div>').html(content.replace(/\n/g, '<br />')));

            const messageTemplate = $('<div class="chat-message"></div>').attr('id', elem_id).append(
                    $('<div class="speech-user-avatar"></div>').addClass(is_sender ? 'speech-sender-avatar' : 'speech-recipient-avatar').html(avatar)
                ).append(messageContent);

            if (!content.length) {
                messageTemplate.addClass('message-empty');
            }

            template = template.add(messageTemplate);

            if (typeof message.attachments === 'string') {
                try {
                    // try to decode the JSON attachments
                    message.attachments = JSON.parse(message.attachments);
                } catch (err) {
                    // malformed string, use empty array
                    message.attachments = [];
                }
            }

            const hasAttachments = message.attachments && message.attachments.length;

            // check if the message has some attachments
            if (hasAttachments) {
                // iterate attachments
                message.attachments.forEach((attachment, i) => {
                    const attachmentTemplate = messageTemplate.clone();

                    attachmentTemplate.attr('id', attachmentTemplate.attr('id') + '-attachment-' + (i + 1));
                    attachmentTemplate.addClass('is-attachment');
                    attachmentTemplate.removeClass('message-empty');

                    // get proper media element
                    const media = this.getMedia(attachment);

                    // check if we have something to show
                    if (media) {
                        attachmentTemplate.find('.message-content').html(media);
                        template = template.add(attachmentTemplate);
                    }
                });
            }

            if (buffer) {
                // return template in case of no animation
                return template;
            }

            // append HTML to conversation box
            $(this.data.element.conversation).append(template);
            
            if (animate) {
                // setup timeout to perform entrance animation
                setTimeout(() => {
                    // in case of attachments, we need to find all the messages that
                    // starts with the message ID
                    const selector = hasAttachments ? '*[id^="' + elem_id + '"]' : '#' + elem_id;
                    // ease in message
                    $(selector).find('*.need-animation').removeClass('need-animation');

                    this.scrollToBottom();
                }, 32);
            }

            return this;
        }

        /**
         * Returns the details of the user that sent the specified message.
         * 
         * @param   object  message  The details of the message.
         * 
         * @return  object  The user details.
         */
        getMessageUser(message) {
            if (!this.data.environment.users.hasOwnProperty(message.id_sender)) {
                // user not found, register it right now
                this.data.environment.users[message.id_sender] = {
                    id: message.id_sender,
                    name: message.sender_name,
                    avatar: '',
                }
            }

            // obtain a copy of the user instance
            const user = Object.assign({}, this.data.environment.users[message.id_sender])

            if (!user.name) {
                // in case of empty name, use the one specified by the message
                user.name = message.sender_name
            }

            return user;
        }

        /**
         * Creates the avatar for the specified user.
         * 
         * @param   object  user  The user details.
         * 
         * @return  mixed   The HTML node.
         */
        drawUserAvatar(user) {
            let avatar = null;

            if (user.avatar) {
                avatar = $('<img decoding="async" loading="lazy" />')
                    .attr('alt', user.name)
                    .attr('title', user.name)
                    .attr('src', user.avatar);
            } else {
                let names = user.name.split(/\s+/);

                avatar = $('<span></span>')
                    .attr('title', user.name)
                    .text(((names.shift() || '').substr(0, 1) + (names.pop() || '').substr(0, 1)).toUpperCase());
            }

            return avatar;
        }

        /**
         * Renders the message content in order to replace certain tokens
         * with a user-friendly representation.
         * For example, an e-mail address could be wrapped within a link to
         * open the mail app.
         *
         * @param   string  content     The text to fetch.
         *
         * @return  string  The resulting string.
         */
        renderMessageContent(content) {
            // get parsers list and sort by priority DESC
            const pool = Object.values(this.contentParsers || {}).sort((a, b) => {
                if (a.priority < b.priority) {
                    return 1;
                }

                if (a.priority > b.priority) {
                    return -1;
                }

                return 0;
            });

            pool.forEach((parser) => {
                // keep a temporary flag
                let tmp = content.toString();

                // run parser callback (use tmp in case the callback forgot to the return the value)
                content = parser['callback'](tmp) || tmp;
            });

            return content;
        }

        /**
         * Attaches a callback that will be used to parse the contents.
         * In case a function with the same ID already exists, that function
         * will be replaced with this one.
         *
         * @param   string    id        The parser identifier.
         * @param   function  callback  The function to run while parsing the contents.
         * @param   integer   priority  The callback priority. The higher the value, the
         *                              higher the priority.
         *
         * @return  self
         */
        attachContentParser(id, callback, priority) {
            if (this.contentParsers === undefined) {
                this.contentParsers = {};
            }

            // register callback
            this.contentParsers[id] = {
                callback: callback,
                priority: priority || 10,
            };

            return this;
        }

        /**
         * Tries to detach the parser that matches the specified id.
         *
         * @param   string   id  The parser identifier.
         *
         * @return  boolean  True on success, false otherwise.
         */
        detachContentParser(id) {
            // make sure the pool contains the id
            if (this.contentParsers !== undefined && this.contentParsers.hasOwnProperty(id)) {
                // detach parser
                delete this.contentParsers[id];

                return true;
            }

            return false;
        }

        /**
         * Returns the most appropriate DOM element according to the specified URL.
         * In case of a media file, the URL will be wrapped within a <img> tag.
         * Otherwise a Font Icon will be used instead.
         *
         * @param   object   file  The file object (name and url properties required).
         *
         * @return  string   The HTML media string.
         */
        getMedia(file) {
            // always get a string
            if (!file?.url?.toString) {
                return '';
            }

            const url = file.url.toString();

            if (!file.name) {
                // there is something wrong with the attachment, use a broken icon
                return '<i class="fas fa-unlink" title="' + url + '"></i>';
            }

            const onclick = "window.open('" + url + "', '_blank')";
            const onload  = "VBOChat.getInstance().onMediaLoaded(this)";

            // check for images
            if (url.match(/\.(a?png|bmp|gif|ico|jpe?g|svg|heic|webp)$/i)) {
                return '<img src="' + url + '" onclick="' + onclick + '" onload="' + onload + '" title="' + file.name + '" />';
            }

            // check for playable video files
            if (url.match(/\.(mp4|mov|ogm|webm)$/i)) {
                return '<video controls onloadeddata="' + onload + '" title="' + file.name + '">\n' +
                    '<source src="' + url + '" />\n' +
                '</video>';
            }

            // check for non-playable video files
            if (url.match(/\.(3gp|asf|avi|divx|flv|mkv|mp?g|wmv|xvid)$/i)) {
                return '<i class="fas fa-file-video" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for playable audio files
            if (url.match(/\.(aac|m4a|mp3|opus|wave?)$/i)) {
                return '<audio controls onloadeddata="' + onload + '" title="' + file.name + '">\n' +
                    '<source src="' + url + '" />\n' +
                '</audio>';
            }

            // check for non-playable audio files
            if (url.match(/\.(ac3|aiff|flac|midi?|wma)$/i)) {
                return '<i class="fas fa-file-audio" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for archives
            if (url.match(/\.(zip|tar|rar|gz|bzip2)$/i)) {
                return '<i class="fas fa-file-archive" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for PDF
            if (url.match(/\.pdf$/i)) {
                return '<i class="fas fa-file-pdf" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for documents
            if (url.match(/\.(docx?|rtf|odt|pages)$/i)) {
                return '<i class="fas fa-file-word" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for excel-like sheets
            if (url.match(/\.(xlsx?|csv|ods|numbers)$/i)) {
                return '<i class="fas fa-file-excel" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for presentations
            if (url.match(/\.(ppsx?|odp|keynote)$/i)) {
                return '<i class="fas fa-file-powerpoint" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // check for plain text documents
            if (url.match(/\.(txt|md|markdown)$/i)) {
                return '<i class="fas fa-file-alt" onclick="' + onclick + '" title="' + file.name + '"></i>';
            }

            // use standard file
            return '<i class="fas fa-file" onclick="' + onclick + '" title="' + file.name + '"></i>';
        }

        /**
         * Handler invoked every time a media file has been loaded.
         *
         * @param   mixed   element  The media element.
         *
         * @return  void
         */
        onMediaLoaded(element) {
            // check if we should scroll after loading a media file,
            // because if we are loading previous file, we don't
            // need to scroll down
            if (this.shouldScroll(element.offsetHeight + 30)) {
                // scroll down
                this.scrollToBottom();
            }
        }

        /**
         * Creates a new progress bar.
         *
         * @return  string  The progress bar ID.
         */
        createProgressBar() {
            if (this.data.environment.idProgress === undefined) {
                this.data.environment.idProgress = 0;
            }

            // increment ID
            const id = 'progress-bar-' + (++this.data.environment.idProgress);

            // create progress bar
            $(this.data.element.progressBar)
                .append('<div class="chat-progress-bar" id="' + id + '"><div>&nbsp;</div></div>')
                .parent()
                    .show();

            return id;
        }

        /**
         * Removes the specified progress bar.
         *
         * @param   string  id  The progress bar ID.
         *
         * @return  self
         */
        removeProgressBar(id) {
            $(this.data.element.progressBar).find('#' + id).remove();

            return this;
        }

        /**
         * Updates the progress value of the specified bar.
         *
         * @param   string   id        The progress bar ID.
         * @param   integer  progress  The progress amount.
         *
         * @return  self
         */
        updateProgressBar(id, progress) {
            progress = Math.max(0, progress);
            progress = Math.min(100, progress);

            $(this.data.element.progressBar).find('#' + id + ' > div').width(progress + '%').html(progress + '%');

            return this;
        }

        /**
         * Reads the pending notifications.
         *
         * @return  self
         */
        readNotifications() {
            // ignore in case the chat shouldn't auto-read the unread messages
            if (!this.data.environment.options.autoread) {
                return this;
            }

            const unread = this.getLatestUnreadMessage();

            if (!unread) {
                return this;
            }

            // make AJAX request to read all the messages under this context
            VBOChatAjax.do(
                // end-point URL
                this.data.environment.url,
                // POST data
                {
                    task: 'chat.read_messages',
                    id_context: this.data.environment.context.id,
                    context: this.data.environment.context.alias,
                    datetime: unread.createdon,
                },
                (messages) => {
                    // flag all the messages as read
                    messages.forEach((msgId) => {
                        const msg = this.getMessage(msgId);

                        if (msg) {
                            msg.read = true;
                        }
                    });

                    // trigger event
                    this.triggerEvent('chat.read', {
                        chat: this,
                    });
                }
            );

            return this;
        }

        /**
         * Returns the HTML to use for a date separator.
         *
         * @param   string|object   datetime    The datetime to use.
         *
         * @return  string  The HTML separator.
         */
        getDateSeparator(datetime) {
            let dt_str = '';

            if (DateHelper.isToday(datetime)) {
                // current day: get formatted time
                dt_str = Joomla.JText._('VBTODAY');
            } else if (DateHelper.isYesterday(datetime)) {
                // previous day: use "yesterday"
                dt_str = Joomla.JText._('VBOYESTERDAY');
            } else if ((dt_str = DateHelper.diff(datetime, new Date(), 'days')) < 7) {
                let tmp = new Date();
                tmp.setDate(tmp.getDate() - dt_str);
                dt_str = tmp.toLocaleDateString([], {weekday: 'long'});
            } else {
                // use formatted date
                dt_str = DateHelper.getFormattedDate(datetime);
            }

            return $('<div class="chat-datetime-separator is-a-separator"</div>')
                .attr('data-datetime', DateHelper.toStringUTC(datetime))
                .text(dt_str + ', ' + DateHelper.getFormattedTime(datetime));
        }

        /**
         * Merges the messages with the ones stored within the internal state.
         *
         * @param   array  resp  The messages.
         *
         * @return  array  Returns a list containing all the new messages.
         */
        mergeMessages(resp) {
            let newMessages = [], missedMessages = [];

            // update messages list
            for (let i = 0; i < resp.length; i++) {
                // get message that matches the current ID
                let message = this.getMessage(resp[i].id);

                if (message) {
                    continue;
                }

                // detect the correct position of the message
                let messageIndex = this.findMessagePosition(resp[i]);

                // do not add the message in case it exceeds the static pagination limit, otherwise
                // the system might add it twice while loading older messages
                if (messageIndex >= this.data.environment.options.limit) {
                    continue;
                }

                // insert message within the list
                this.data.environment.messages.splice(messageIndex, 0, resp[i]);

                if (messageIndex == 0) {
                    // register message only if new
                    newMessages.push(resp[i]);
                } else {
                    missedMessages.push(resp[i]);
                }
            }

            return {
                newMessages: newMessages,
                missedMessages: missedMessages,
            };
        }

        /**
         * Uploads the given files.
         *
         * @param   mixed   files   The files list.
         *
         * @return  self
         */
        uploadAttachments(files) {
            // create form data for upload
            const formData = new FormData();

            // inject order data
            formData.append('id_context', this.data.environment.context.id);
            formData.append('context', this.data.environment.context.alias);
            formData.append('task', 'chat.upload_attachments');

            // iterate files and append to form data
            for (let i = 0; i < files.length; i++) {
                formData.append('attachments[]', files[i]);
            }

            // create progress bar
            const id_progress = this.createProgressBar();

            VBOChatAjax.upload(
                // end-point URL
                this.data.environment.url,
                // file post data
                formData,
                // success callback
                (resp) => {
                    // remove progress bar
                    this.removeProgressBar(id_progress);

                    for (let i = 0; i < resp.length; i++) {
                        // register uploaded attachment
                        this.registerAttachment(resp[i]);
                    }
                },
                // failure callback
                (error) => {
                    // remove progress bar
                    this.removeProgressBar(id_progress);

                    // raise alert
                    alert(error.responseText);
                },
                // progress callback
                (progress) => {
                    // update progress bar
                    this.updateProgressBar(id_progress, progress);
                },
            ).critical();

            return this;
        }

        /**
         * Registers the file within the attachments bar.
         *
         * @param   object  file    The file to attach.
         *
         * @return  self
         */
        registerAttachment(file) {
            // push file within the list
            this.data.environment.attachments.push(file);

            file.id = file.filename.replace(/\.[^.]*$/, '');

            $(this.data.element.uploadsBar)
                .append('<span class="chat-attachment" id="' + file.id + '">' + file.name + '<i class="fas fa-times"></i></span>')
                .parent()
                    .show();

            // register event to remove attachment after clicking the TIMES icon
            $('#' + file.id).find('i.fa-times').on('click', (event) => {
                // remove attachment
                this.removeAttachment(file);
            });

            return this;
        }

        /**
         * Returns the index of the specified attachment.
         *
         * @param   mixed    file   The file object or its ID.
         *
         * @return  integer  The file index on success, otherwise -1.
         */
        getAttachmentIndex(file) {
            let id = null

            if (typeof file === 'object') {
                id = file.id;
            } else {
                id = file;
            }

            for (let i = 0; i < this.data.environment.attachments.length; i++) {
                if (this.data.environment.attachments[i].id === id) {
                    return i;
                }
            }

            return -1;
        }

        /**
         * Removes the specified attachment by unlinking it too.
         *
         * @param   object   file   The file object to remove.
         *
         * @return  self
         */
        removeAttachment(file) {
            // get attachment index
            const index = this.getAttachmentIndex(file);

            if (index != -1) {
                // remove attachment box
                $('#' + file.id).remove();

                if (this.data.environment.attachments.length > 1) {
                    // splice attachments array
                    this.data.environment.attachments.splice(index, 1);
                } else {
                    // clear all as we are going to have an empty list
                    this.clearAttachments();
                }

                // do not attempt deleting the file in case it is marked as "custom"
                if (!file.custom) {
                    // make AJAX request to unlink the specified attachment
                    VBOChatAjax.do(
                        // end-point URL
                        this.data.environment.url,
                        // POST data
                        {
                            task: 'chat.remove_attachment',
                            id_context: this.data.environment.context.id,
                            context: this.data.environment.context.alias,
                            attachment: file,
                        }
                    );
                }
            }

            return this;
        }

        /**
         * Clears the attachments list.
         *
         * @return  self
         */
        clearAttachments() {
            // clear attachments
            this.data.environment.attachments = [];

            // hide uploads bar
            $(this.data.element.uploadsBar)
                .html('')
                .parent()
                    .hide();

            return this;
        }

        /**
         * Triggers the specified event.
         *
         * @param   string  name    The event name.
         * @param   mixed   data    The data to inject within event.detail property.
         *
         * @return  self
         */
        triggerEvent(name, data) {
            // create CustomEvent by injecting our own data
            const event = new CustomEvent(name, {detail: data});

            // dispatch event from window
            window.dispatchEvent(event);
            
            return this;
        }

        /**
         * AJAX call used to load older messages of the current context.
         * This function is usually invoked when the scroll hits the first half
         * of the conversation.
         *
         * @return  self
         */
        loadPreviousMessages() {
            if (this.data.environment.isLoadingOlderMessages) {
                // do not proceed in case we are already loading something
                return this;
            }

            // mark loading flag
            this.data.environment.isLoadingOlderMessages = true;

            let limit = this.data.environment.options.limit;

            // make AJAX request to load older messages
            VBOChatAjax.do(
                // end-point URL
                this.data.environment.url,
                // POST data
                {
                    task: 'chat.load_older_messages',
                    id_context: this.data.environment.context.id,
                    context: this.data.environment.context.alias,
                    start: this.data.environment.thread.messagesLength,
                    limit: limit,
                    /**
                     * We need to pass the initial date time in order to exclude 
                     * all the messages that are newer than the latest message we got 
                     * when the page was loaded.
                     *
                     * This will avoid errors while retriving older messages
                     * as new records would shift the current limits.
                     */
                    datetime: this.data.environment.thread.initialDatetime,
                },
                // success callback
                (resp) => {
                    // make loading available again
                    this.data.environment.isLoadingOlderMessages = false;

                    const conversation = $(this.data.element.conversation)[0];

                    // keep current scroll
                    let currentScrollTop    = conversation.scrollTop;
                    let currentScrollHeight = conversation.scrollHeight;

                    // update count of loaded messages
                    this.data.environment.thread.messagesLength += resp.length;

                    // get current index
                    let start = this.data.environment.messages.length;
                    let end   = start + resp.length;

                    // push messages within the list
                    for (let i = 0; i < resp.length; i++) {
                        // add message only if it doesn't exist yet
                        if (!this.getMessage(resp[i].id)) {
                            // detect the correct position of the message
                            let messageIndex = this.findMessagePosition(resp[i]);

                            // insert message within the list
                            this.data.environment.messages.splice(messageIndex, 0, resp[i]);
                        }
                    }

                    // turn off scroll event in case we reached the limit
                    if (resp.length < limit) {
                        $(this.data.element.conversation).off('scroll');
                    }

                    // build conversation messages
                    this.buildConversation(start, end);

                    // Recalculate scroll position.
                    // The new scroll top position will be increased by the difference between
                    // the old scroll height and the new one.
                    let newScrollTop = currentScrollTop + (conversation.scrollHeight - currentScrollHeight);
                    $(conversation).scrollTop(newScrollTop);
                },
                // failure callback
                (error) => {
                    // make loading available again
                    this.data.environment.isLoadingOlderMessages = false;
                }
            );
        }

        /**
         * AJAX call used to synchronize the messages.
         * This should be used to load the messages that haven't been
         * downloaded by the system.
         *
         * @return self
         */
        synchronizeMessages() {
            // get latest message to evaluate a threshold
            const latest = this.getLatestMessage();

            // make request to synchronize the messages
            const xhr = VBOChatAjax.do(
                // end-point URL
                this.data.environment.url,
                // POST data
                {
                    task:      'chat.sync_messages',
                    id_context:  this.data.environment.context.id,
                    context:  this.data.environment.context.alias,
                    threshold: latest ? latest.id : 0,
                },
                // success callback
                (resp) => {
                    if (!resp.length) {
                        // do nothing in case the response is empty
                        return;
                    }

                    // check if the chat should scroll after collecting new messages
                    let should_scroll = this.shouldScroll();

                    // update messages
                    let {newMessages, missedMessages} = this.mergeMessages(resp);

                    if (!newMessages.length && !missedMessages.length) {
                        // stop process in case nothing has changed
                        return;
                    }

                    // collect new messages
                    for (var i = 0; i < newMessages.length; i++) {
                        // draw message (animation needed)
                        this.drawMessage(newMessages[i], true);
                    }

                    if (newMessages.length) {
                        // trigger event
                        this.triggerEvent('chat.sync', {
                            notifications: newMessages.length,
                            chat: this,
                        });
                    }

                    if (missedMessages.length) {
                        // rebuild the conversation to properly display the missed messages too
                        this.startConversation().buildConversation();
                        // ignore auto-scroll as it should have been already applied
                        should_scroll = false;
                    }

                    // flush notifications for active chat
                    this.readNotifications();

                    /**
                     * Use bottom scroll only in case the message is visible
                     * within the scroll. In this way, if we are reading older messages
                     * we won't pushed back at the page bottom. Contrarily, in case we
                     * are keeping an eye on the latest messages, the chat will be scrolled
                     * automatically.
                     */
                    if (should_scroll) {
                        // scroll conversation to bottom
                        this.scrollToBottom();
                    }

                    /**
                     * After registering the messages we
                     * need to fetch the payload in order to build the
                     * proper input for the response.
                     */
                    this.renderInput();
                }
            );
        
            // Callback must be invoked after chat.send request.
            // In case this process ends while send() request is still
            // running, the callback will be pushed within a queue of promises.
            // Promises are automatically flushed after the completion of the 
            // last running send() process.
            xhr.after('chat.send');
        }

        /**
         * AJAX call used to send the message to the recipients of the current context.
         * The message is always pushed within the chat even if the connection fails.
         * In that case, the message will report a button that could be used to re-send
         * the message.
         *
         * @param   mixed   message  The message to send.
         *
         * @return  self
         */
        send(message) {
            let id = null, data = null;

            if (typeof message === 'object') {
                // use passed data
                data = message;
                id   = message.id;
            } else {
                // validate message as string
                if (!message.length && this.data.environment.attachments.length == 0) {
                    return this;
                }

                // trim message
                message = message.trim();

                // generate temporary ID
                id = this.getNextID();

                // build chat bubble
                data = this.collect(id, message, this.data.environment.user);

                if (data) {
                    data.attachments = this.data.environment.attachments;

                    // clear attachments bar
                    this.clearAttachments();
                }
            }

            if (!data) {
                // something went wrong while collecting the message, abort
                return this;
            }

            // Always re-render input after sending a message.
            // At this point, a textarea should be always used.
            this.renderInput();

            this.input.disable();

            // make request to reply to an existing message (CRITICAL)
            const xhr = VBOChatAjax.do(
                // end-point URL
                this.data.environment.url,
                // POST data
                {
                    task: 'chat.send',
                    id_context: this.data.environment.context.id,
                    context: this.data.environment.context.alias,
                    message: data.message,
                    createdon: data.createdon,
                    attachments: data.attachments,
                },
                // success callback
                (message) => {
                    // get index of dummy message
                    const dummyIndex = this.getMessageIndex(data.id);

                    if (dummyIndex != -1) {
                        // update message with received response
                        this.data.environment.messages[dummyIndex] = message;
                    }

                    // always re-enable the textarea in case of success
                    this.input.enable();

                    // trigger event
                    this.triggerEvent('chat.send', {
                        message: message,
                        chat: this,
                    });
                },
                // failure callback
                (error) => {
                    const chat = this;

                    /**
                     * Something went wrong while trying to send the message.
                     * Place "re-try" button within the message box so that the user
                     * will be able to resend the message by clicking it.
                     */
                    $('#' + id).find('.message-content').append('<i class="fas fa-exclamation-circle"></i>')
                        .append($('<div class="message-error-result"></div>').text(error.responseText));

                    // register event to re-send the message after clicking the exclamation triangle
                    $('#' + id).find('.message-content i.fa-exclamation-circle').on('click', function(event) {
                        // remove any possible explanation of the error
                        $(this).next('.message-error-result').remove();

                        // remove icon from message
                        $(this).off('click').remove();

                        // re-send the message
                        chat.send(data);
                    });

                    // obtain message
                    const tmp = this.getMessage(data.id);

                    if (tmp) {
                        // mark error
                        tmp.hasError = true;
                    }

                    // always re-enable the textarea in case of success
                    this.input.enable();
                }
            );
        
            // mark request as critical
            xhr.critical();
            // set identifier to request
            xhr.identify('chat.send');

            return this;
        }

        /**
         * Renders the input according to the specified payload
         * of the latest received message.
         *
         * @return  self
         */
        renderInput() {
            // create default form field data
            const data = {
                type: 'text',
                hint: Joomla.JText._('VBO_CHAT_TEXTAREA_PLACEHOLDER'),
            };

            // make sure the input is different than the current one
            if (this.input && Object.equals(this.input.payload, data)) {
                return this;
            }

            let input = null;

            try {
                // get input class
                input = VBOChatField.getInstance(data);
            } catch (err) {
                // the given type seems to be not supported, try to use the default one
                Object.assign(data, def);
                input = VBOChatField.getInstance(data);
            }

            if (this.input) {
                // destroy input set previously
                this.input.onDestroy(this);
            }

            // keep reference to new input
            this.input = input;

            // render input HTML
            const html = this.input.render();

            // set rendered HTML into input box
            $(this.data.element.inputBox).html(html);

            // init new input
            this.input.onInit(this);

            return this;
        }

        /**
         * Clears all the intervals previously registered.
         * 
         * @return  self
         */
        destroy() {
            if (!this.data) {
                // chat never initialized, immediately abort
                return this;
            }

            this.timers.forEach((interval_id, index) => {
                clearInterval(interval_id);
            });

            this.timers = [];

            // unregister conversation scroll event to load older messages
            $(this.data.element.conversation).off('scroll');
            
            if (this.input) {
                // destroy input previously set
                this.input.onDestroy(this);
                this.input = null;
            }

            // clear conversation messages
            $(this.data.element.conversation).html('');

            // the chat requires to be prepared again
            this.isPrepared = false;

            // delete internal data
            delete this.data;

            // check whether we previously has another chat
            const lastData = VBOChat.queue.pop();

            if (lastData) {
                // restore the behavior of the previous chat
                VBOChat.getInstance(lastData);
            }

            return this;
        }
    }

    /**
     * Holds the environment details of the previously initialized chats.
     * A chat environment is pushed here when a new chat is created without
     * destroying the previous one first.
     */
    VBOChat.queue = [];

    /**
     * VBOChatField class.
     * Abstract representation of a form field.
     * This class acts also as a field factory, as the fields
     * should be instantiated by using the getInstance() static method:
     * var field = VBOChatField.getInstance({type: 'text'});
     */
    w['VBOChatField'] = class VBOChatField {

        /**
         * Returns an instance of the requested field.
         * The field will be recognized by checking the
         * type property contained within data argument.
         *
         * @param   object  data  The field attributes.
         *
         * @return  mixed   The new field.
         */
        static getInstance(data) {
            // make sure the type exists
            if (!data.hasOwnProperty('type') || !data.type) {
                throw 'Missing type property';
            }

            // fetch field class name
            const className = 'VBOChatField' + data.type.charAt(0).toUpperCase() + data.type.substr(1);

            // make sure the class exists
            if (!VBOChatField.classMap.hasOwnProperty(data.type)) {
                throw 'Form field [' + className + '] not found';
            }

            // find class
            const _class = VBOChatField.classMap[data.type];

            // instantiate field
            return new _class(data);
        }

        /**
         * Class constructor.
         *
         * @param   object  data  The field attributes.
         */
        constructor(data) {
            this.data = data;
            // keep a copy of the payload which shouldn't be altered
            this.payload = Object.assign({}, data);

            if (!this.data.id) {
                // generate an incremental ID
                if (!VBOChatField.incrementalId) {
                    VBOChatField.incrementalId = 0;
                }

                this.data.id = 'chat-answer-field-' + (++VBOChatField.incrementalId);
            }
        }

        /**
         * Binds the given data.
         *
         * @param   string  k   The attribute name.
         * @param   mixed   v   The attribute value.
         *
         * @return  self
         */
        bind(k, v) {
            this.data[k] = v;
            
            return this;
        }

        /**
         * Method used to return the field value.
         *
         * @return  mixed   The value.
         */
        getValue() {
            return $('#' + this.data.id).val();
        }

        /**
         * Method used to set the field value.
         *
         * @param   mixed  val  The value to set.
         *
         * @return  mixed  The source element.
         */
        setValue(val) {
            return $('#' + this.data.id).val(val);  
        }

        /**
         * Enables the input.
         *
         * @return  self
         */
        enable() {
            $('#' + this.data.id).prop('disabled', false).focus();

            return this;
        }

        /**
         * Disables the input.
         *
         * @return  self
         */
        disable() {
            $('#' + this.data.id).prop('disabled', true);

            return this;
        }

        /**
         * Method used to return the field selector.
         *
         * @return  mixed   The field selector.
         */
        getSelector() {
            return '#' + this.data.id;
        }

        /**
         * Abstract method used to obtain the input HTML.
         *
         * @return  string  The input html.
         */
        render() {
            // inherit in children classes
        }

        /**
         * Abstract method used to initialise the field.
         * This method is called once the field has been
         * added within the document.
         *
         * @param   VBOChat  chat   The chat instance.
         *
         * @return  void
         */
        onInit(chat) {
            // inherit in children classes

            if (this.data.onInit) {
                // invoke also custom initialize
                this.data.onInit(chat);
            }
        }

        /**
         * Abstract method used to destroy the field.
         * This method is called before removing the field
         * from the document.
         *
         * @param   VBOChat  chat   The chat instance.
         *
         * @return  void
         */
        onDestroy(chat) {
            // inherit in children classes

            if (this.data.onDestroy) {
                // invoke also custom destroy
                this.data.onDestroy(chat);
            }
        }

    }

    /**
     * Form fields classes lookup.
     */
    VBOChatField.classMap = {};

    /**
     * VBOChatFieldText class.
     * This field is used to display a HTML input textarea.
     */
    w['VBOChatFieldText'] = class VBOChatFieldText extends VBOChatField {

        /**
         * @override
         * Method used to obtain the input HTML.
         *
         * @return  string  The input html.
         */
        render() {
            // fetch attributes
            let attrs = '';

            if (this.data.name) {
                attrs += 'name="' + this.data.name.escape() + '" ';
            }

            if (this.data.id) {
                attrs += 'id="' + this.data.id.escape() + '" ';
            }

            if (this.data.class) {
                attrs += 'class="' + this.data.class.escape() + '" ';
            }

            if (this.data.hint) {
                attrs += 'placeholder="' + this.data.hint.escape() + '" ';
            }

            if (this.data.value === undefined) {
                this.data.value = this.data.default !== undefined ? this.data.default : '';
            }

            // define default ID for context menu trigger
            this.data.idContextMenu = this.data.id + '-ctxmenu'; 

            // define default ID for attachment input
            this.data.idAttachment = this.data.id + '-attachment-input';

            // define default ID for manual send message button
            this.data.idManualSend = this.data.id + '-manual-send';

            // return input
            return '<div class="send-message-actions">\n'+
                '<a href="javascript:void(0)" class="chat-action-btn ctx-actions" id="' + this.data.idContextMenu + '" aria-label="more actions"><i class="fas fa-ellipsis-v"></i></a>\n'+
                '</div>\n'+
                '<textarea rows="1" ' + attrs.trim() + '>' + this.data.value + '</textarea>\n' +
                '<span id="' + this.data.idManualSend + '" class="manual-send-message"><i class="fas fa-paper-plane"></i></span>\n'+
                '<input type="file" id="' + this.data.idAttachment + '" multiple="multiple" style="display:none;" />\n';
        }

        /**
         * Method used to initialise the field.
         * This method is called once the field has been
         * added within the document.
         *
         * @param   VBOChat  chat   The chat instance.
         *
         * @return  void
         */
        onInit(chat) {
            // invoke parent first
            super.onInit(chat);

            const target = $('#' + this.data.id);
            if (!target.length) {
                return;
            }

            let padding = 0;
            padding += parseFloat(target.css('padding-top').replace(/[^0-9.]/g, ''));
            padding += parseFloat(target.css('padding-bottom').replace(/[^0-9.]/g, ''));
            
            // init textarea events
            target.on('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight - padding) + 'px';
            });

            // init manual send message listener
            $('#' + this.data.idManualSend).on('click', () => {
                chat.send(target.val());
                target.val('');
                target.css('height', 'auto');
            });

            // init attachments upload
            $('#' + this.data.idAttachment).on('change', function(event) {
                // get selected files
                const files = $(this)[0].files;

                // upload attachments
                chat.uploadAttachments(files);

                // unset input file value
                $(this).val(null);
            });

            let actions = [];

            chat.data.environment.context.actions.forEach((button) => {
                if (button.namespace) {
                    if (!button.icon) {
                        button.icon = (...args) => {
                            const event = $.Event('chat.' + button.namespace + '.icon');
                            event.args = args.concat([button, chat]);
                            event.displayIcon = null;
                            $(window).trigger(event);
                            return event.displayIcon;
                        }
                    }

                    if (!button.action) {
                        button.action = (...args) => {
                            const event = $.Event('chat.' + button.namespace + '.action');
                            event.args = args.concat([button, chat]);
                            $(window).trigger(event);
                        };
                    }

                    if (!button.disabled) {
                        button.disabled = (...args) => {
                            const event = $.Event('chat.' + button.namespace + '.disabled');
                            event.args = args.concat([button, chat]);
                            event.shouldDisable = false;
                            $(window).trigger(event);
                            return event.shouldDisable;
                        }
                    }

                    if (!button.visible) {
                        button.visible = (...args) => {
                            const event = $.Event('chat.' + button.namespace + '.visible');
                            event.args = args.concat([button, chat]);
                            event.shouldDisplay = true;
                            $(window).trigger(event);
                            return event.shouldDisplay;
                        }
                    }
                }

                actions.push(button);
            });

            // init context menu
            $('#' + this.data.idContextMenu).vboContextMenu({
                placement: 'top-left',
                buttons: actions.concat([
                    // File upload
                    {
                        text: 'Upload a file',
                        icon: 'fas fa-paperclip',
                        separator: true,
                        action: (root, event) => {
                            event.preventDefault();

                            setTimeout(() => {
                                $('#' + this.data.idAttachment).trigger('click');
                            }, 16);
                        },
                    }
                ]),
            });
        }

        /**
         * Method used to destroy the field.
         * This method is called before removing the field
         * from the document.
         *
         * @param   VBOChat  chat   The chat instance.
         *
         * @return  void
         */
        onDestroy(chat) {
            // invoke parent first
            super.onInit(chat);

            // turn off textarea events before destroying it
            $('#' + this.data.id).off('input').off('keydown');
            // turn off manual send event
            $('#' + this.data.idManualSend).off('click');
            // turn off attachments events
            $('#' + this.data.idAttachment).off('change');
            // turn off context menu
            $('#' + this.data.idContextMenu).vboContextMenu('destroy');
        }
        
    }

    // Register class within the lookup
    VBOChatField.classMap.text = VBOChatFieldText;

    /**
     * DateHelper class.
     * Helper class used to handle date objects.
     */
    w['DateHelper'] = class DateHelper {

        /**
         * Checks if the specified date matches the current day.
         *
         * @param   string|Date  dt  The date to check.
         *
         * @return  boolean      True if today, otherwise false.
         */
        static isToday(dt) {
            // compare specified date with current day
            return DateHelper.isSameDay(dt, new Date());
        }

        /**
         * Checks if the specified date matches the previous day.
         *
         * @param   string|Date  dt  The date to check.
         *
         * @return  boolean      True if yesterday, otherwise false.
         */
        static isYesterday(dt) {
            // get yesterday date object
            var yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);

            // compare specified date with previous day
            return DateHelper.isSameDay(dt, yesterday);
        }

        /**
         * Checks if the specified dates are equals without 
         * considering the related times.
         *
         * @param   string|Date  a  The first date to check.
         * @param   string|Date  b  The second date to check.
         *
         * @return  boolean      True if equals, otherwise false.
         */
        static isSameDay(a, b) {
            // convert string to date
            if (typeof a === 'string') {
                a = DateHelper.stringToDate(a);
            }

            // convert string to date
            if (typeof b === 'string') {
                b = DateHelper.stringToDate(b);
            }

            // check if the specified days are matching (exclude time)
            return (a.getDate() == b.getDate() && a.getMonth() == b.getMonth() && a.getFullYear() == b.getFullYear());
        }

        /**
         * Calculate the difference between the specified dates.
         * The difference is always an absolute value.
         *
         * @param   string|Date  a      The first date to check.
         * @param   string|Date  b      The second date to check.
         * @param   string       unit   The difference unit [seconds, minutes, hours, days].
         *
         * @return  integer      The related difference according to the specified unit.
         */
        static diff(a, b, unit) {
            // convert string to date
            if (typeof a === 'string') {
                a = DateHelper.stringToDate(a);
            } else {
                // create new instance in order to avoid manipulating the given object
                a = new Date(a);
            }

            // convert string to date
            if (typeof b === 'string') {
                b = DateHelper.stringToDate(b);
            } else {
                // create new instance in order to avoid manipulating the given object
                b = new Date(b);
            }

            // use default unit if not specified
            if (typeof unit === 'undefined') {
                unit = 'seconds';
            }

            // always divide by 1000 to convert milliseconds in seconds
            var div = 1000;

            if (unit.match(/days?/)) {
                // in case of "days" or "day", extract days from seconds
                div = div * 60 * 60 * 24;

                // unset hours, minutes and seconds in order to
                // get the exact difference in days
                a.setHours(0);
                a.setMinutes(0);
                a.setSeconds(0);

                b.setHours(0);
                b.setMinutes(0);
                b.setSeconds(0);
            } else if (unit.match(/hours?/)) {
                // in case of "hours" or "hour", extract hours from seconds
                div = div * 60 * 60;
            } else if (unit.match(/min|minutes?/)) {
                // in case of "min" or "minute" or "minutes", extract minutes from seconds
                div = div * 60;
            }

            // get dates timestamp
            a = a.getTime();
            b = b.getTime();

            // get milliseconds difference between 2 dates
            var diff = Math.abs(b - a);

            // divide difference by the calculated threshold
            return Math.floor(diff / div);
        }

        /**
         * Formats the specified date according to the browser locale.
         *
         * @param   string|Date  dt  The date to format.
         *
         * @return  string       The formatted date.
         */
        static getFormattedDate(dt) {
            // convert string to date
            if (typeof dt === 'string') {
                dt = DateHelper.stringToDate(dt);
            }

            // format locale date
            return dt.toLocaleDateString();
        }

        /**
         * Formats the specified time according to the browser locale.
         *
         * @param   string|Date  dt  The date to format.
         *
         * @return  string       The formatted time.
         */
        static getFormattedTime(dt) {
            // convert string to date
            if (typeof dt === 'string') {
                dt = DateHelper.stringToDate(dt);
            }

            // format locale time (no seconds)
            return dt.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        /**
         * Converts the specified date into a valid SQL (UTC) date format.
         *
         * @param   string|Date  dt  The date to format.
         *
         * @return  string       The resulting date string.
         */
        static toStringUTC(dt) {
            // convert string to date
            if (typeof dt === 'string') {
                dt = DateHelper.stringToDate(dt);
            }

            var year  = dt.getUTCFullYear();
            var month = dt.getUTCMonth() + 1;
            var day   = dt.getUTCDate();
            var hour  = dt.getUTCHours();
            var min   = dt.getUTCMinutes();
            var sec   = dt.getUTCSeconds();

            var date = year + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
            var time = (hour < 10 ? '0' : '') + hour + ':' + (min < 10 ? '0' : '') + min + ':' + (sec < 10 ? '0' : '') + sec;

            return date + ' ' + time;
        }

        /**
         * Converts the specified date string into a Date object.
         *
         * @param   string  str  The date to format.
         *
         * @return  Date    The date object.
         */
        static stringToDate(str) {
            return new Date(str.replace(/\s+/, 'T') + 'Z');
        }

    }

    /**
     * VBOChatAjax class.
     * Handles asynch server-side connections.
     */
    w['VBOChatAjax'] = class VBOChatAjax {
        
        /**
         * Normalizes the given argument to be sent via AJAX.
         *
         * @param   mixed   data  An object, an associative array or a serialized string.
         *
         * @return  object  The normalized object.
         */
        static normalizePostData(data) {

            if (data === undefined) {
                data = {};
            } else if (Array.isArray(data)) {
                // the form data is serialized @see $.serializeArray()
                var form = data;

                data = {};

                for (var i = 0; i < form.length; i++) {
                    // if the field ends with [] it should be an array
                    if (form[i].name.endsWith("[]")) {
                        // if the field doesn't exist yet, create a new list
                        if (!data.hasOwnProperty(form[i].name)) {
                            data[form[i].name] = new Array();
                        }

                        // append the value to the array
                        data[form[i].name].push(form[i].value);
                    } else {
                        // otherwise overwrite the value (if any)
                        data[form[i].name] = form[i].value;
                    }
                }
            }

            return data;
        }

        /**
         * Makes the connection.
         *
         * @param   mixed     url       The URL to reach or a configuration object.
         * @param   mixed     data      The data to post.
         * @param   function  success   The callback to invoke on success.
         * @param   function  failure   The callback to invoke on failure.
         * @param   integer   attempt   The current attempt (optional).
         *
         * @return  void
         */
        static do(url, data, success, failure, attempt) {

            if (attempt == 1 || attempt === undefined) {
                if (!VBOChatAjax.concurrent && VBOChatAjax.isDoing()) {
                    return false;
                }
            }

            if (attempt === undefined) {
                attempt = 1;
            }

            // return same object if data has been already normalized
            data = VBOChatAjax.normalizePostData(data);

            var config = {};

            if (typeof url === 'object') {
                // we have a configuration object, use it
                Object.assign(config, url);
            } else {
                // use the default configuration
                config.type = 'post';
                config.url  = url;
            }

            // inject data within config
            config.data = data;

            var xhr = $.ajax(
                // use fetched config
                config
            ).done(function(resp) {

                VBOChatAjax.pop(xhr);

                if (success !== undefined) {
                    // check if we should wait for another call
                    if (VBOChatAjax.isRunningProcess(xhr.idAfter)) {
                        // register promise
                        VBOChatAjax.registerPromise(xhr.idAfter, success, resp);
                    } else {
                        // execute callback directly
                        success(resp);
                    }
                }

                // process pending promises
                VBOChatAjax.processPromises(xhr.identify());

            }).fail(function(err) {
                // always pop XHR after failure
                VBOChatAjax.pop(xhr);

                // If the error has been raised by a connection failure, 
                // retry automatically the same request. Do not retry if the
                // number of attempts is higher than the maximum number allowed.
                if (attempt < VBOChatAjax.maxAttempts && VBOChatAjax.isConnectionLost(err)) {

                    // wait 256 milliseconds before launching the request
                    setTimeout(function() {
                        // relaunch same action and increase number of attempts by 1
                        VBOChatAjax.do(url, data, success, failure, attempt + 1);
                    }, 256);

                } else {

                    // otherwise raise the failure method
                    if (failure !== undefined) {
                        failure(err);
                    }

                }

                console.error(err);

                if (err.status.toString().match(/^5[\d]{2,2}$/)) {
                    console.error(err.responseText);
                }

            });

            VBOChatAjax.push(xhr);

            return xhr;
        }
        /**
         * Makes the connection with the server and start uploading the given data.
         *
         * @param   string    url       The URL to reach.
         * @param   mixed     data      The data to upload.
         * @param   function  done      The callback to invoke on success.
         * @param   function  failure   The callback to invoke on failure.
         * @param   function  upload    The callback to invoke to track the uploading progress.
         *
         * @return  void
         */
        static upload(url, data, done, failure, upload) {
            // define upload config
            var config = {
                url:         url,
                type:        "post",
                contentType: false,
                processData: false,
                cache:       false,
            };

            // define upload callback to keep track of progress
            if (typeof upload === 'function') {
                config.xhr = function() {
                    var xhrobj = $.ajaxSettings.xhr();

                    if (xhrobj.upload) {
                        // attach progress event
                        xhrobj.upload.addEventListener('progress', function(event) {
                            // calculate percentage
                            var percent  = 0;
                            var position = event.loaded || event.position;
                            var total    = event.total;
                            if (event.lengthComputable) {
                                percent = Math.ceil(position / total * 100);
                            }

                            // trigger callback
                            upload(percent);
                        }, false);
                    }

                    return xhrobj;
                };
            }

            // invoke default do() method by using custom config
            return VBOChatAjax.do(config, data, done, failure);
        }

        /**
         * Checks if we own at least an active connection.
         *
         * @return  boolean
         */
        static isDoing() {
            return VBOChatAjax.stack.length > 0 && VBOChatAjax.count > 0;
        }

        /**
         * Checks if the process with the specified ID is running.
         *
         * @param   mixed    id  The process identifier.
         *
         * @return  boolean  True if the process is running, false otherwise.
         */
        static isRunningProcess(id) {
            if (!id) {
                return false;
            }

            // iterate the stack
            for (var i = 0; i < VBOChatAjax.stack.length; i++) {
                // get XHR instance
                var xhr = VBOChatAjax.stack[i];

                if (typeof xhr === 'object' && xhr.identifier === id) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks if we are currently running a critical XHR.
         * XHRs can be marked in that way by using the prototyped
         * critical() function.
         *
         * @return  boolean  True if there is at least a critical XHR, otherwise false. 
         */
        static isRunningCritical() {
            // iterate the stack
            for (var i = 0; i < VBOChatAjax.stack.length; i++) {
                // get XHR instance
                var xhr = VBOChatAjax.stack[i];

                if (typeof xhr === 'object' && typeof xhr.isCritical === 'function' && xhr.isCritical()) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Registers a new promise for the specified identifier.
         *
         * @param   mixed     id        The identifier to check.
         * @param   function  callback  The callback to trigger.
         * @param   mixed     args      The argument of the callback.
         *
         * @return  void
         */
        static registerPromise(id, callback, args) {
            if (!id) {
                return;
            }

            if (!VBOChatAjax.promises.hasOwnProperty(id)) {
                // create list
                VBOChatAjax.promises[id] = [];
            }

            // register promise
            VBOChatAjax.promises[id].push({
                callback: callback,
                args:     args,
            });
        }

        /**
         * Processes all the pending promises for the specified ID.
         *
         * @param   mixed   id  The id to check.
         *
         * @return  void
         */
        static processPromises(id) {    
            if (!VBOChatAjax.promises.hasOwnProperty(id)) {
                return
            }

            // iterate promises lists
            while (VBOChatAjax.promises[id].length) {
                // get first callback available
                var tmp = VBOChatAjax.promises[id].shift();

                // trigger callback
                tmp.callback(tmp.args);
            }
        }

        /**
         * Pushes the opened connection within the stack.
         *
         * @param   mixed   xhr  The connection resource.
         *
         * @return  void
         */
        static push(xhr) {
            VBOChatAjax.stack.push(xhr);
            VBOChatAjax.count++;
        }

        /**
         * Removes the specified connection from the stack.
         *
         * @param   mixed   xhr  The connection resource.
         *
         * @return  void
         */
        static pop(xhr) {
            var index = VBOChatAjax.stack.indexOf(xhr);

            if (index != -1) {
                VBOChatAjax.stack.splice(index, 1);
            }

            VBOChatAjax.count--;
        }

        /**
         * Checks if the given error can be intended as a loss of connection:
         * generic error, no status and no response text.
         * 
         * @param   object  err     The error object.
         *
         * @return  boolean
         */
        static isConnectionLost(err) {
            return (
                err.statusText == 'error'
                && err.status == 0
                && err.readyState == 0
                && err.responseText == ''
            );
        }
    }

    VBOChatAjax.stack        = [];
    VBOChatAjax.promises     = {};
    VBOChatAjax.count        = 0;
    VBOChatAjax.concurrent   = true;
    VBOChatAjax.maxAttempts  = 5;

    /**
     * Checks if the specified elements are equal.
     *
     * @param   mixed   x
     * @param   mixed   y
     *
     * @return  boolean  True if identical, false otherwise. 
     */
    Object.equals = function(x, y) {
        // if both x and y are null or undefined and exactly the same
        if (x === y)
            return true;

        // if they are not strictly equal, they both need to be Objects
        if (!(x instanceof Object) || !(y instanceof Object))
            return false;

        // they must have the exact same prototype chain, the closest we can do is
        // test there constructor
        if (x.constructor !== y.constructor)
            return false;

        for (var p in x) {
            // make sure we are testing a valid property
            if (!x.hasOwnProperty(p))
                continue;

            // allows to compare x[p] and y[p] when set to undefined
            if (!y.hasOwnProperty(p))
                return false;

            // if they have the same strict value or identity then they are equal
            if (x[p] === y[p])
                continue;

            // Numbers, Strings, Functions, Booleans must be strictly equal
            if (typeof(x[p]) !== "object")
                return false;

            // Objects and Arrays must be tested recursively
            if (!Object.equals(x[p],  y[p]))
                return false;
        }

        for (p in y) {
            // allows x[p] to be set to undefined
            if (y.hasOwnProperty(p) && !x.hasOwnProperty(p))
                return false;
        }

        return true;
    }

    /**
     * Converts the most common special chars in their HTML entities.
     *
     * @return  string  The converted string.
     */
    String.prototype.htmlentities = function() {
        return this.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Escapes single quotes and double quotes by converting
     * them in the related HTML entity.
     *
     * @return  string  The escaped string
     */
    String.prototype.escape = function() {
        return this.toString().replace(/"/g, '&quot;');
    }

    /**
     * Whenever an Ajax request is about to be sent, jQuery triggers the ajaxSend event.
     * Any and all handlers that have been registered with the .ajaxSend() method are executed at this time.
     * We can implement here all the methods that jqXHR object should support.
     */
    $(window).ajaxSend(function(event, xhr, settings) {
        /**
         * Marks the jqXHR object as critical according to the specified argument.
         *
         * @param   mixed   is  Whether the XHR is critical or not. Undefined
         *                      argument is assumed as TRUE.
         *
         * @return  self
         */
        xhr.critical = function(is) {
            this.criticalFlag = (is === undefined ? true : is);

            return this;
        };

        /**
         * Checks whether the jqXHR object is critical or not.
         *
         * @return  boolean
         */
        xhr.isCritical = function(is) {
            return this.criticalFlag ? true : false;
        };

        /**
         * Sets/Gets the ID of the jqXHR object.
         *
         * @param   mixed   id  The identifier to set.
         *
         * @return  mixed   The identifier.
         */
        xhr.identify = function(id) {
            if (id !== undefined) {
                this.identifier = id;
            }

            return this.identifier;
        }

        /**
         * This method is used to push the callback of the request
         * in a queue to be executed once [id] request has finished.
         *
         * @param   mixed   id  The identifier of the process to observe.
         *
         * @return  self
         */
        xhr.after = function(id) {
            this.idAfter = id;

            return this;
        }
    });

    /**
     * The beforeunload event is fired when the window, the document and its resources are about to be unloaded.
     * The document is still visible and the event is still cancelable at this point.
     *
     * If a string is assigned to the returnValue Event property, a dialog appears asking the user for confirmation 
     * to leave the page (see example below). Some browsers display the returned string in the dialog box, but others
     * display their own message. If no value is provided, the event is processed silently.
     */
    $(window).on('beforeunload', function(event) {
        // check if we are running a XHR in background
        // that shouldn't be aborted
        if (VBOChatAjax.isRunningCritical()) {
            // cancel the event and prompt the confirmation alert
            event.preventDefault();
            // for some browsers it is needed to setup a return value
            event.returnValue = 'Do you want to leave the page?';
        }
    });
})(jQuery, window);