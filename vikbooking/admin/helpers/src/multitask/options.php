<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Defines a registry of options to be binded to an admin widget. Options
 * are available as multitask data, but they live on a separate layer.
 * 
 * @since 	1.16.5 (J) - 1.6.5 (WP)
 */
final class VBOMultitaskOptions extends JObject
{
    /**
     * Proxy for immediately accessing the object and bind data.
     * 
     * @param   array|object    $data   the options data to bind.
     */
    public static function getInstance($data = null)
    {
        return new static($data);
    }

    /**
     * Attempts to fetch the booking id from the options set. The
     * booking ID could be a VikBooking ID or an OTA ID as well.
     * 
     * @param   string  $key    optional option key to fetch from.
     * 
     * @return  int|string      the booking id found, or 0.
     */
    public function fetchBookingId($key = '')
    {
        $values = [
            $this->get('id'),
            $this->get('bid'),
            $this->get('booking_id'),
            $this->get('id_order'),
        ];

        if ($key) {
            // prepend option value for the given key
            array_unshift($values, $this->get($key));
        }

        foreach (array_filter($values) as $id) {
            // do not cast to integer if the booking contains non-number characters
            return preg_match("/^[0-9]+$/", $id) ? (int)$id : $id;
        }

        return 0;
    }

    /**
     * Detects if the options come from a Push notification message posted
     * by the ServiceWorker to the client application during rendering.
     * Can also detect if it's a Web Notification payload.
     * 
     * @param   bool    $web_or_push    true for both, false only for Push.
     * 
     * @return  bool
     */
    public function gotPushData($web_or_push = true)
    {
        if ($web_or_push && !$this->get('_push') && !$this->get('_web')) {
            // not a Push notification, nor a Web notification
            return false;
        }

        if (!$web_or_push && !$this->get('_push')) {
            // not a Push notification
            return false;
        }

        $title   = $this->get('title');
        $message = $this->get('message');

        return (isset($title) || isset($message));
    }
}
