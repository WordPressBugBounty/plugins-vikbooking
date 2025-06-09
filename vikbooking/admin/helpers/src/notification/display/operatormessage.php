<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Browser notification displayer handler for a new operator message.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBONotificationDisplayOperatormessage extends JObject implements VBONotificationDisplayer
{
    /**
     * Proxy for immediately getting the object and bind data.
     * 
     * @param   array|object    $data   the notification payload data to bind.
     */
    public static function getInstance($data = null)
    {
        return new static($data);
    }

    /**
     * Composes an object with the necessary properties to display
     * the notification in the browser.
     * 
     * @return  null|object     The notification display data payload.
     * 
     * @throws  Exception
     */
    public function getData()
    {
        $id_sender = (int) $this->get('id_sender', 0);
        if (empty($id_sender)) {
            return null;
        }

        // get the operator record
        $operator = (array) VikBooking::getOperatorInstance()->getOne($id_sender);

        // operator picture
        $operator_pic = $this->get('pic', $this->get('avatar', '')) ?: $operator['pic'] ?? '';

        // the notification icon
        $notif_icon = '';
        if (!empty($operator_pic)) {
            $notif_icon = strpos($operator_pic, 'http') === 0 ? $operator_pic : VBO_SITE_URI . 'resources/uploads/' . $operator_pic;
        } else {
            $notif_icon = $this->getIconUrl();
        }

        // compose notification title
        $operator_name = $this->get('sender_name', trim(($operator['first_name'] ?? '') . ' ' . ($operator['last_name'] ?? '')));
        $notif_title   = JText::sprintf('VBO_MESSAGE_FROM', $operator_name);

        // compose the notification data to display
        $notif_data = new stdClass;
        $notif_data->title   = $notif_title;
        $notif_data->message = $this->get('message', '');
        $notif_data->icon    = $notif_icon;
        $notif_data->onclick = 'VBOCore.handleDisplayWidgetNotification';
        $notif_data->gotourl = VBOFactory::getPlatform()->getUri()->admin("index.php?option=com_vikbooking&view=taskmanager", false);

        // set additional properties to the notification payload related to the operator message
        $notif_data->widget_id     = 'operators_chat';
        $notif_data->id_sender     = $this->get('id_sender', null);
        $notif_data->id_message    = $this->get('id', null);
        $notif_data->context_id    = $this->get('id_context', null);
        $notif_data->context_alias = $this->get('context', null);
        // set additional data options to ensure the conversation will be found
        $notif_data->_options  = [
            '_web' => 1,
            'context_id'    => $this->get('id_context', null),
            'context_alias' => $this->get('context', null),
        ];

        return $notif_data;
    }

    /**
     * Returns the URL to the default icon for the history
     * browser notifications. Custom logos are preferred.
     * 
     * @return  ?string
     */
    private function getIconUrl()
    {
        $config = VBOFactory::getConfig();

        // back-end custom logo
        $use_logo = $config->get('backlogo');
        if (empty($use_logo) || !strcasecmp($use_logo, 'vikbooking.png')) {
            // fallback to company (site) logo
            $use_logo = $config->get('sitelogo');
        }

        if (!empty($use_logo) && strcasecmp($use_logo, 'vikbooking.png')) {
            // uploaded logo found
            $use_logo = VBO_ADMIN_URI . 'resources/' . $use_logo;
        } else {
            $use_logo = null;
        }

        return $use_logo;
    }
}
