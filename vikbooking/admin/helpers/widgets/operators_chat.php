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
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
class VikBookingAdminWidgetOperatorsChat extends VBOChatWidgetAdmin
{
    /**
     * A list holding all the supported chat categories.
     * 
     * @var string[]
     * @since 1.8.8
     */
    protected $involvedCategories = ['task'];

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
                ->forCategories($this->involvedCategories)
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
}
