<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Observer design pattern implementation as trait for a better reusability.
 *
 * @since 1.18.15 (J) - 1.8.15 (WP)
 */
trait VBOEventObserver
{
    /**
     * A list of subscribers, grouped per event.
     * 
     * @var array
     */
    protected $listeners = [];

    /**
     * Subscribes the provided callback to the given event.
     * 
     * @param   string  $event     The event to observe.
     * @param   mixed   $callback  The callback to invoke.
     * 
     * @return  self
     */
    public function subscribe(string $event, $callback)
    {
        if (!isset($this->listeners[$event]))
        {
            // create event repository for the first time
            $this->listeners[$event] = [];
        }

        // make sure we have a valid callback
        if (is_callable($callback))
        {
            // generate callback ID
            $callbackId = $this->getCallbackId($callback);

            // internally register the callback
            $this->listeners[$event][$callbackId] = $callback;
        }

        return $this;
    }

    /**
     * Unsubscribes the provided callback from the given event.
     * 
     * @param   string  $event     The observed event.
     * @param   mixed   $callback  The registered callback.
     * 
     * @return  self
     */
    public function unsubscribe(string $event, $callback)
    {
        // make sure we have a valid callback
        if (is_callable($callback))
        {
            // generate callback ID
            $callbackId = $this->getCallbackId($callback);

            // unset registered callback, if any
            if (isset($this->listeners[$event][$callbackId]))
            {
                unset($this->listeners[$event][$callbackId]);
            }
        }

        return $this;
    }

    /**
     * Notifies all the subscribers when a certain event is triggered.
     * 
     * @param   string  $event    The fired event.
     * @param   mixed   ...$args  Arguments to pass to the function.
     * 
     * @return  A list of return values.
     */
    public function notify(string $event, &...$args)
    {
        $return = [];

        // iterate all the event subscribers
        foreach ($this->listeners[$event] ?? [] as $callback)
        {
            // invoke callback function and register returned value
            $return[] = call_user_func_array($callback, $args);
        }

        // get rid of NULL values
        return array_values(array_filter($return, fn($v) => $v !== null));
    }

    /**
     * Generates a unique and reusable identifier according to the provided callback.
     * 
     * @param   mixed   $callback  The registered callback.
     * 
     * @return  string
     */
    private function getCallbackId($callback)
    {
        if ($callback instanceof \Closure || is_object($callback))
        {
            return spl_object_hash($callback);
        }

        if (is_string($callback))
        {
            return $callback;
        }

        if (is_array($callback))
        {
            [$target, $method] = $callback;

            if (is_object($target))
            {
                return spl_object_hash($target) . '::' . $method;
            }

            return $target . '::' . $method;
        }

        throw new \InvalidArgumentException('Invalid callback provided.', 400);
    }
}
