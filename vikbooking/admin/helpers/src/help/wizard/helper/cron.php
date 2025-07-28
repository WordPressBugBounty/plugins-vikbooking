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
 * Cron job wizard helper class.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardHelperCron
{
    /**
     * A list of cached cron jobs.
     * 
     * @var array
     */
    protected static $crons = null;

    /**
     * Returns the report_auto_exporter cron job configured for the specified report ID, if any.
     * 
     * @param   string  $reportId
     * 
     * @return  array|null
     */
    public static function getAutoExport(string $reportId)
    {
        foreach (static::getList() as $cron) {
            if ($cron['class_file'] !== 'report_auto_exporter') {
                continue;
            }

            if (strcasecmp($cron['params']['report'] ?? '', $reportId)) {
                continue;
            }

            return $cron;
        }

        return null;
    }

    /**
     * Returns the list of supported cron jobs.
     * 
     * @return  array
     */
    public static function getList()
    {
        if (is_null(static::$crons)) {
            static::$crons = [];

            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                ->select($db->qn(['id', 'class_file', 'params', 'published']))
                ->from($db->qn('#__vikbooking_cronjobs'));

            $db->setQuery($query);
            
            foreach ($db->loadAssocList() as $cron)
            {
                $cron['params'] = $cron['params'] ? json_decode($cron['params'], true) : [];

                static::$crons[] = $cron;
            }
        }

        return static::$crons;
    }
}
