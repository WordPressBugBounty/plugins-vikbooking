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
 * Cron scheduler and execution simulator.
 * 
 * @since 1.7
 */
final class VBOCrontabSimulator implements IteratorAggregate, Countable
{
    /** @var VBOCrontabRunner[] */
    private $schedules = [];

    /** @var VBOCrontabSemaphore */
    private $semaphore;

    /** @var VBOCrontabLogger */
    private $logger;

    /**
     * Whether crontab should prevent the execution of the runners.
     * 
     * @var bool
     */
    private $stopped = false;

    /**
     * Class contructor.
     * 
     * @param  VBOCrontabSemaphore  $semaphore  The instance that will be used to control the execution of the crons.
     * @param  VBOCrontabLogger     $logger     The instance that will be used to log details about the execution of the crons.
     */
    public function __construct(VBOCrontabSemaphore $semaphore, VBOCrontabLogger $logger)
    {
        $this->semaphore = $semaphore;
        $this->logger = $logger;
    }

    /** 
     * Schedules a cron for running at a precise interval.
     * 
     * @param   VBOCrontabRunner  $cron  The cron to schedule.
     * 
     * @return  self
     */
    public function schedule(VBOCrontabRunner $cron)
    {
        // queue execution
        $this->schedules[] = $cron;

        return $this;
    }

    /**
     * Prevent the execution of the registered runners.
     * 
     * @return  self
     */
    public function stop()
    {
        $this->stopped = true;

        return $this;
    }

    /**
     * Executes the scheduled crons if they are due to execution.
     * 
     * @return  void
     */
    public function run()
    {
        if ($this->stopped) {
            // execution prevented
            return;
        }

        // prevent duplicate runs under the same session
        $this->stop();

        // iterate all the registered schedules
        foreach ($this->schedules as $cron) {
            // check whether the cron can actually run
            if (!$cron->canRun()) {
                // nope, go ahead
                continue;
            }

            // check whether this cron should pass
            if (!$this->semaphore->shouldPass($cron)) {
                // cron still locked, go ahead
                continue;
            }

            try {
                // track the execution of the cron
                $this->logger->log(sprintf('Executing the cron [%s]...', $cron->getID()));
                // execute the cron job
                $cron->execute($this->logger);
                // execution terminated successfully
                $this->logger->log('Cron terminated successfully.');
            } catch (Throwable $error) {
                // log the error faced
                $this->logger->log($error->getMessage(), 'error');
            }
        }
    }

    /**
     * Returns the registered semaphore instance.
     * 
     * @return  VBOCrontabSemaphore
     */
    public function getSemaphore()
    {
        return $this->semaphore;
    }

    /**
     * Returns the registered logger instance.
     * 
     * @return  VBOCrontabLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Exposes the registered schedules to an iterator.
     * 
     * @see IteratorAggregate
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->schedules);
    }

    /**
     * Counts the number of registered schedules.
     * 
     * @see Countable
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return count($this->schedules);
    }
}
