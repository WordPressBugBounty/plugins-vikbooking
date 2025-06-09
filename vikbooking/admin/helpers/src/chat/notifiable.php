<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Chat notifiable interface.
 * 
 * @since 1.8
 */
interface VBOChatNotifiable
{
    /**
     * Schedules a message notification for the entity implementing this interface.
     * 
     * @param   VBOChatMessage  $message  The message sent.
     * @param   VBOChatUser     $user     The user that sent the message.
     * 
     * @return  void
     */
    public function scheduleNotification(VBOChatMessage $message, VBOChatUser $user);
}
