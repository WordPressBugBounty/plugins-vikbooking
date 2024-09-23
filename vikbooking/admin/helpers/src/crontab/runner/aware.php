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
 * Cron runner basic implementation.
 * 
 * @since 1.7
 */
class VBOCrontabRunnerAware implements VBOCrontabRunner
{
    /** @var string */
    protected $id;

    /** @var int */
    protected $interval;

    /** @var callback */
    protected $callback;

    /**
     * Class constructor.
     * 
     * @param  string    $id        The cron unique identifier.
     * @param  int       $interval  The execution interval in seconds (eg. 3600 to execute every hour).
     * @param  callable  $callback  The callback that will be invoked.
     */
    public function __construct(string $id, int $interval, $callback)
    {
        $this->id = $id;
        $this->interval = abs($interval);
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    final public function getID()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function canRun()
    {
        // always runnable
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getNextExecution(DateTime $lastExecution)
    {
        $nextExecution = clone $lastExecution;
        $nextExecution->modify('+' . $this->interval . ' seconds');
        return $nextExecution;
    }

    /**
     * @inheritDoc
     */
    public function execute(VBOCrontabLogger $logger)
    {
        // invoke the callback
        call_user_func_array($this->callback, [$logger]);
    }
}
