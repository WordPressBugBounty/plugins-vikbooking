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
 * Task driver collector implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskDrivercollector
{
    /**
     * @var  array
     */
    private $created = [];

    /**
     * @var  array
     */
    private $modified = [];

    /**
     * @var  array
     */
    private $cancelled = [];

    /**
     * Proxy to construct the object.
     * 
     * @return  VBOTaskDrivercollector
     */
    public static function getInstance()
    {
        return new static();
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Resets the task collector pools.
     * 
     * @return  VBOTaskDrivercollector
     */
    public function reset()
    {
        $this->created = [];
        $this->modified = [];
        $this->cancelled = [];

        return $this;
    }

    /**
     * Returns a list of the task pools.
     * 
     * @param   bool    $filter     If true, only the non-empty pools will be returned.
     * 
     * @return  array
     */
    public function getAll(bool $filter = false)
    {
        if ($filter) {
            return array_merge(
                array_filter($this->created),
                array_filter($this->modified),
                array_filter($this->cancelled)
            );
        }

        return [
            $this->created,
            $this->modified,
            $this->cancelled,
        ];
    }

    /**
     * Returns the newly created tasks pool.
     * 
     * @return  array
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Returns the modified tasks pool, which
     * can include cancelled and created tasks.
     * 
     * @return  array
     */
    public function getModified()
    {
        if (!$this->modified && ($this->getCreated() || $this->getCancelled())) {
            // some tasks were created or deleted, but not directly modified
            return $this->getAll(true);
        }

        return $this->modified;
    }

    /**
     * Returns the cancelled tasks pool.
     * 
     * @return  array
     */
    public function getCancelled()
    {
        return $this->cancelled;
    }

    /**
     * Registers a new task within the collector pool.
     * 
     * @param   array   $task   The task information to register.
     * @param   string  $type   The type of task to register.
     * 
     * @return  bool
     */
    public function register(array $task, string $type = 'created')
    {
        if (!strcasecmp($type, 'created')) {
            $this->created[] = $task;
            return true;
        }

        if (!strcasecmp($type, 'modified')) {
            $this->modified[] = $task;
            return true;
        }

        if (!strcasecmp($type, 'cancelled')) {
            $this->cancelled[] = $task;
            return true;
        }

        return false;
    }
}
