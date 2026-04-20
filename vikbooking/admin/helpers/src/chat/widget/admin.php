<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Abstract class handler for admin chat widgets.
 * 
 * @since 1.18.8 (J) - 1.8.8 (WP)
 */
abstract class VBOChatWidgetAdmin extends VikBookingAdminWidget
{
    /**
     * The instance counter of this widget.
     *
     * @var     int
     */
    protected static $instance_counter = -1;

    /**
     * A list holding all the supported chat categories.
     * 
     * @var string[]
     * @since 1.8.8
     */
    protected $involvedCategories = [];

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
                ->forCategories($this->involvedCategories)
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
        $wrapper_id = 'vbo-widget-admin-chat-' . $wrapper_instance;

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
                    ->forCategories($this->involvedCategories)
                    ->reader($chat->getUser()->getID())
                    ->limit(20)
            );

            if ($threads) {
                // render chat threads
                echo JLayoutHelper::render('chat.threads', [
                    'threads' => $threads,
                    'categories' => $this->involvedCategories,
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
