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
 * Helper class for hot events.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBODateHotevents
{
    /**
     * Loads the festivities for the given range of date timestamps.
     * 
     * @param   int     $tsFrom    Date range start timestamp.
     * @param   int     $tsTo      Date range end timestamp.
     * 
     * @return  array
     */
    public static function loadPeriod(int $tsFrom, int $tsTo)
    {
        $dbo = JFactory::getDbo();

        // build the hot events list
        $hotEvents = [];

        // load festivities
        $q = $dbo->getQuery(true)
            ->select('*')
            ->from($dbo->qn('#__vikbooking_fests_dates'))
            ->where($dbo->qn('dt') . ' >= ' . $dbo->q(date('Y-m-d', $tsFrom)))
            ->where($dbo->qn('dt') . ' <= ' . $dbo->q(date('Y-m-d', $tsTo)))
            ->order($dbo->qn('dt') . ' ASC');

        $dbo->setQuery($q);
        foreach ($dbo->loadAssocList() as $festRecord) {
            $festData = (array) json_decode($festRecord['festinfo'], true);
            if (!$festData) {
                continue;
            }

            // prepare events for the current date
            $hotEvents[$festRecord['dt']] = $hotEvents[$festRecord['dt']] ?? [];

            foreach ($festData as $fest) {
                $regions = (array) (($fest['regions'] ?? []) ?: ['global']);
                $festName = ($fest['trans_name'] ?? '') ?: ($fest['descr'] ?? '');
                if (empty($festName)) {
                    // malformed festivity
                    continue;
                }

                // push festivity details
                $hotEvents[$festRecord['dt']][] = [
                    'name'    => $festName,
                    'regions' => $regions,
                ];
            }
        }

        // sort list
        foreach ($hotEvents as &$dayEvents) {
            // sort global events first
            usort($dayEvents, function($a, $b) {
                if (in_array('global', $a['regions']) || in_array('global', $b['regions'])) {
                    return 1;
                }

                return 0;
            });
        }

        unset($dayEvents);

        // return the list of hot events
        return $hotEvents;
    }

    /**
     * Given a period date and interval type, matches the belonging events.
     * 
     * @param   DateTimeInterface   $period         The data period under evaluation.
     * @param   string              $intervalType   The data evaluation interval type.
     * @param   array               $hotEvents      Associative list of hot events.
     * 
     * @return  ?array
     */
    public static function matchPeriodEvents(DateTimeInterface $period, string $intervalType, array $hotEvents)
    {
        if (!$hotEvents) {
            // nothing to match
            return null;
        }

        // build the list of events matching the period
        $periodEvents = [];

        // get the timestamps at midnight for the evaluation date
        $dt = clone $period;
        $dt->modify('00:00:00');
        $tsFrom = $dt->format('U');
        $tsTo = $tsFrom;

        if ($intervalType === 'MONTH') {
            $tsTo = strtotime(date('Y-m-t', $tsFrom));
        }

        // iterate all hot events
        foreach ($hotEvents as $day => $events) {
            $dayTs = strtotime($day);
            if ($tsFrom <= $dayTs && $tsTo >= $dayTs) {
                // merge events matching the period
                $periodEvents = array_merge($periodEvents, $events);
            }
        }

        return $periodEvents ?: null;
    }
}
