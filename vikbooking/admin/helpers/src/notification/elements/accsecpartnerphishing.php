<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Notification elements registry (Notification Center) of type "Account Security Partner Phishing".
 * 
 * @since 1.18.6 (J) - 1.8.6 (WP)
 */
class VBONotificationElementsAccsecpartnerphishing extends VBONotificationElements
{
    /**
     * @inheritDoc
     */
    public function postflight()
    {
        // extract message ID from extra data payload
        $data = (array) $this->get('data', []);
        $messageId = $data['message_id'] ?? null;

        if (!$messageId) {
            // message not provided
            return;
        }

        $db = JFactory::getDbo();

        // flag the provided message as suspicious
        $query = $db->getQuery(true)
            ->update($db->qn('#__vikchannelmanager_threads_messages'))
            ->set($db->qn('suspicious') . ' = 1')
            ->where($db->qn('ota_message_id') . ' = ' . $db->q($messageId));

        $db->setQuery($query);
        $db->execute();
    }
}
