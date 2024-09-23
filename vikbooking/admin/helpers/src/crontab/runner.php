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
 * Cron runner interface.
 * 
 * @since 1.7
 */
interface VBOCrontabRunner
{
    /**
     * Returns the ID of the cron.
     * 
     * @return  string  A unique identifier.
     */
    public function getID();

    /**
     * Checks whether the runner should be actually executed.
     * It is possible to override this method to prevent the execution
     * of a cron in certain locations of the website.
     * 
     * @return  bool  True to run, false otherwise.
     */
    public function canRun();

    /**
     * Returns the next execution date time (in UTC).
     * 
     * @param   DateTime  $lastExecution  The last execution date time.
     * 
     * @return  DateTime  The next execution date time.
     */
    public function getNextExecution(DateTime $lastExecution);

    /**
     * Executes the cron.
     * 
     * @param   VBOCrontabLogger  logger  The instance used to log the execution.
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    public function execute(VBOCrontabLogger $logger);
}
