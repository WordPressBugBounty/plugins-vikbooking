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
 * Class handler for admin widget "operators chat".
 * 
 * @since 	1.18.0 (J) - 1.8.0 (WP)
 */
class VikBookingAdminWidgetOperatorsChat extends VikBookingAdminWidget
{
    /**
     * The instance counter of this widget.
     *
     * @var     int
     */
    protected static $instance_counter = -1;

    /**
     * Class constructor will define the widget name and identifier.
     */
    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        $this->widgetName = JText::translate('VBO_W_OPERATORSCHAT_TITLE');
        $this->widgetDescr = JText::translate('VBO_W_OPERATORSCHAT_DESCR');
        $this->widgetId = basename(__FILE__, '.php');

        $this->widgetIcon = '<i class="' . VikBookingIcons::i('comments') . '"></i>';
        $this->widgetStyleName = 'light-red';
    }

    /**
     * Beside loading the necessary assets, this widget preloads the
     * ID of the latest message for the administrators in order to
     * watch the new messages received and to be able to trigger notifications.
     * 
     * @return  ?object
     */
    public function preload()
    {
        // get the chat mediator
        $chat = VBOFactory::getChatMediator();

        // preload chat assets
        $chat->useAssets();

        // get the latest message(s) for the administrators
        $messages = $chat->getMessages(
            (new VBOChatSearch)
                ->sender(0, false)
                ->limit(1)
        );

        if ($messages) {
            $watch_data = [
                'message_id' => $messages[0]->getID(),
            ];

            // return the data to watch for notifications
            return (object) $watch_data;
        }

        return null;
    }

    /**
     * Checks for new notifications by using the previous preloaded watch-data.
     * 
     * @param   ?VBONotificationWatchdata   $watch_data The preloaded watch-data object.
     * 
     * @return  array                       Data object to watch next and notifications array.
     * 
     * @see     preload()
     */
    public function getNotifications(?VBONotificationWatchdata $watch_data = null)
    {
        // default empty values
        $watch_next    = null;
        $notifications = null;

        if (!$watch_data) {
            return [$watch_next, $notifications];
        }

        $latest_message_id = (int) $watch_data->get('message_id', 0);
        if (!$latest_message_id) {
            return [$watch_next, $notifications];
        }

        // get the latest message for the administrators
        $messages = VBOFactory::getChatMediator()->getMessages(
            (new VBOChatSearch)
                ->aggregate()
                ->sender(0, false)
                // search only the messages newer than the latest ID
                ->message($latest_message_id, '>')
                ->limit(3)
        );

        if (!$messages) {
            return [$watch_next, $notifications];
        }

        // build the next watch data for this widget
        $watch_next = new stdClass;
        $watch_next->message_id = $messages[0]->getID();

        // compose the notification(s) to dispatch
        $notifications = VBONotificationScheduler::getInstance()->buildOperatorsChatDataObjects($messages);

        return [$watch_next, $notifications];
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function render(?VBOMultitaskData $data = null)
    {
        // increase widget's instance counter
        static::$instance_counter++;

        // check whether the widget is being rendered via AJAX when adding it through the customizer
        $is_ajax = $this->isAjaxRendering();

        // generate a unique ID for the widget wrapper instance
        $wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
        $wrapper_id = 'vbo-widget-operators-chat-' . $wrapper_instance;

        // get the chat mediator
        $chat = VBOFactory::getChatMediator();

        // multitask data event identifier for clearing intervals
        $js_intvals_id = '';
        $chat_context  = null;
        if ($data && $data->isModalRendering()) {
            // access Multitask data
            $js_intvals_id = $data->getModalJsIdentifier();

            // access context alias and id, if any
            $context_alias = $data->get('context_alias', '') ?: $this->options()->get('context_alias', '');
            $context_id = $data->get('context_id', 0) ?: $this->options()->get('context_id', 0);

            if ($context_alias && $context_id) {
                // build chat for the given context
                $chat_context = $chat->createContext($context_alias, $context_id);
            }
        }

        /**
         * @see  Keep the inline styling on the HTML elements for the JS functions to work properly.
         */
        ?>
        <div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" style="height: 100%;">
            <div class="vbo-admin-widget-head" style="border-bottom: 0;">
                <div class="vbo-admin-widget-head-inline">
                    <h4 style="padding: 7px;"><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
                </div>
            </div>
        <?php
        if ($chat_context) {
            // display the chat for the identified context
            echo $chat->render($chat_context, [
                'assets' => false,
            ]);
        } else {
            // display the chat for all threads

            // take all the threads where the administrator is involved
            $threads = $chat->getMessages(
                (new VBOChatSearch)
                    ->aggregate()
                    ->reader($chat->getUser()->getID())
                    ->limit(20)
            );

            if ($threads) {
                // render chat threads
                echo JLayoutHelper::render('chat.threads', [
                    'threads' => $threads,
                    'options' => [
                        'dateformat' => str_replace('/', $this->datesep, $this->df),
                        'limit' => 20,
                        'compact' => true,
                    ],
                ]);
            } else {
                // render the blank layout
                echo JLayoutHelper::render('chat.blank', []);
            }
        }
        ?>
        </div>

        <script>
            (function($) {
                'use strict';

                <?php if ($js_intvals_id): ?>
                    $(function() {
                        /**
                         * Register callback function for the widget "resize" event (modal only)
                         */
                        const resize_fn = (e) => {
                            const modalContent = $('#<?php echo $wrapper_id; ?>');

                            if (modalContent.width() <= 940) {
                                modalContent.find('.vbo-chat-interface').addClass('compact');
                            } else {
                                modalContent.find('.vbo-chat-interface').removeClass('compact');
                            }
                        };

                        // add listener for the modal dismissed event
                        document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_intvals_id; ?>', (e) => {
                            // get rid of widget resizing events
                            document.removeEventListener('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>', resize_fn);
                            document.removeEventListener('vbo-admin-dock-restore-<?php echo $this->getIdentifier(); ?>', resize_fn);
                            window.removeEventListener('resize', resize_fn);

                            // destroy the chat
                            if (typeof VBOChat !== 'undefined') {
                                VBOChat.getInstance().destroy();
                            }
                        }, {once: true});

                        // register widget resizing events
                        document.addEventListener('vbo-resize-widget-modal<?php echo $js_intvals_id; ?>', resize_fn);
                        document.addEventListener('vbo-admin-dock-restore-<?php echo $this->getIdentifier(); ?>', resize_fn);
                        window.addEventListener('resize', resize_fn);
                    });
                <?php endif; ?>
            })(jQuery);
        </script>
        <?php
    }
}
