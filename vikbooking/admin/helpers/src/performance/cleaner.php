<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Performance Cleaner implementation.
 * 
 * @since  1.17.2 (J) - 1.7.2 (WP)
 */
final class VBOPerformanceCleaner
{
    /**
     * @var  array
     */
    private static $options = [];

    /**
     * Sets a list of given options to filter certain cleaning operations.
     * 
     * @param   array   $options    List of options to set.
     * 
     * @return  void
     */
    public static function setOptions(array $options)
    {
        static::$options = $options;
    }

    /**
     * Performs a global check on what needs to be done to clean up performances.
     * 
     * @return  int     The number of database records that were cleaned up.
     */
    public static function runCheck()
    {
        // list of operations that should be skipped
        $skip_checks = VBOFactory::getConfig()->getArray('performance_cleaner_skip_list', []);

        // number of records affected
        $affected_records = 0;

        if (!in_array('seasons', $skip_checks)) {
            // clean up expired seasonal records
            $affected_records += self::pricingAlterations();
        }

        return $affected_records;
    }

    /**
     * Cleans up expired season pricing records.
     * 
     * @return  int     The number of rows affected.
     */
    public static function pricingAlterations()
    {
        $dbo = JFactory::getDbo();

        $affected = 0;

        $nowinfo = getdate();

        $year_base = mktime(0, 0, 0, 1, 1, $nowinfo['year']);
        $midnight_base = ($nowinfo['hours'] * 3600) + ($nowinfo['minutes'] * 60) + $nowinfo['seconds'];

        $season_secs = $nowinfo[0] - $year_base - $midnight_base;

        $isleap = $nowinfo['year'] % 4 == 0 && ($nowinfo['year'] % 100 != 0 || $nowinfo['year'] % 400 == 0);

        if ($isleap) {
            $leapts = mktime(0, 0, 0, 2, 29, $nowinfo['year']);
            if ($nowinfo[0] >= $leapts) {
                $season_secs -= 86400;
            }
        }

        // delete the records of the past year
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_seasons'))
                ->where($dbo->qn('year') . ' = ' . $dbo->q(($nowinfo['year'] - 1)))
        );

        $dbo->execute();

        $affected += (int) $dbo->getAffectedRows();

        // delete the expired records for the current year
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_seasons'))
                ->where($dbo->qn('from') . ' < ' . $season_secs)
                ->where($dbo->qn('to') . ' < ' . $season_secs)
                ->where($dbo->qn('from') . ' < ' . $dbo->qn('to'))
                ->where($dbo->qn('from') . ' > 0')
                ->where($dbo->qn('to') . ' > 0')
                ->where($dbo->qn('year') . ' = ' . $dbo->q($nowinfo['year']))
        );

        $dbo->execute();

        $affected += (int) $dbo->getAffectedRows();

        return $affected;
    }

    /**
     * Performs a pricing snapshot of a listing for a range of dates, by cleaning up
     * all previous seasonal rates and by re-creating only the needed records. Useful
     * for those listings who had hundreds of pricing alteration updates for the same
     * calendar day. Strongly recommended only for those who use a SINGLE rate plan.
     * 
     * @return  array   Seasons snapshot operation results.
     * 
     * @throws  Exception
     */
    public static function listingSeasonSnapshot()
    {
        $dbo = JFactory::getDbo();

        $listing_id = (int) (static::$options['listing_id'] ?? null);
        $id_price   = (int) (static::$options['id_price'] ?? null);
        $from_date  = static::$options['from_date'] ?? date('Y-m-d');
        $to_date    = static::$options['to_date'] ?? date('Y-m-d', strtotime('+3 months'));

        if (!$listing_id) {
            throw new Exception('Missing required listing ID.', 400);
        }

        if (!$from_date || !$to_date || strtotime($from_date) > strtotime($to_date)) {
            throw new Exception('Invalid dates provided.', 400);
        }

        // obtain a pricing snapshot for the given listing and dates
        $snapshot = VBOModelPricing::getInstance()->getRoomRates([
            'from_date'    => $from_date,
            'to_date'      => $to_date,
            'id_room'      => $listing_id,
            'id_price'     => $id_price,
            'all_rplans'   => false,
            'restrictions' => false,
        ]);

        // confirm the rate plan ID
        foreach ($snapshot as $dayrate) {
            $id_price = $dayrate['idprice'];
            break;
        }

        // gather the list of season records to remove
        $involved_seasons = [];
        foreach ($snapshot as $dayrate) {
            foreach ($dayrate['spids'] ?? [] as $sp_id) {
                if (!in_array($sp_id, $involved_seasons)) {
                    $involved_seasons[] = $sp_id;
                }
            }
        }

        if (!$involved_seasons) {
            throw new Exception('No seasonal records to clean for the given listing and dates.', 500);
        }

        // clean up database records
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_seasons'))
                ->where($dbo->qn('id') . ' IN (' . implode(',', array_map('intval', $involved_seasons)) . ')')
        );
        $dbo->execute();

        $records_removed = (int) $dbo->getAffectedRows();

        // build new season intervals from snapshot
        $all_days = array_keys($snapshot);
        $season_intervals = [];
        $firstind = 0;
        $firstdaycost = $snapshot[$all_days[0]]['cost'];
        $nextdaycost = false;
        for ($i = 1; $i < count($all_days); $i++) {
            $ind = $all_days[$i];
            $nextdaycost = $snapshot[$ind]['cost'];
            if ($firstdaycost != $nextdaycost) {
                $interval = [
                    'from' => $all_days[$firstind],
                    'to'   => $all_days[($i - 1)],
                    'cost' => $firstdaycost
                ];
                $season_intervals[] = $interval;
                $firstdaycost = $nextdaycost;
                $firstind = $i;
            }
        }
        if ($nextdaycost === false) {
            $interval = [
                'from' => $all_days[$firstind],
                'to'   => $all_days[$firstind],
                'cost' => $firstdaycost
            ];
            $season_intervals[] = $interval;
        } elseif ($firstdaycost == $nextdaycost) {
            $interval = [
                'from' => $all_days[$firstind],
                'to'   => $all_days[($i - 1)],
                'cost' => $firstdaycost
            ];
            $season_intervals[] = $interval;
        }

        // list of errors occurred
        $errors = [];

        /**
         * Attempt to preload and cache seasons for other dates, so that applying new rates
         * will be faster. We cannot preload these dates, because seasons were just removed.
         */
        VikBooking::preloadSeasonRecords([$listing_id], strtotime($to_date), strtotime('+1 month', strtotime($to_date)));

        // scan all season intervals
        foreach ($season_intervals as $season_snap) {
            try {
                // set the new rate for this calculated interval
                VBOModelPricing::getInstance([
                    'from_date'   => $season_snap['from'],
                    'to_date'     => $season_snap['to'],
                    'id_room'     => $listing_id,
                    'id_price'    => $id_price,
                    'rate'        => $season_snap['cost'],
                    'min_los'     => 0,
                    'max_los'     => 0,
                    'update_otas' => false,
                ])->modifyRateRestrictions();
            } catch (Exception $e) {
                // silently push the error
                $errors[] = sprintf('%s - %s: %s', $season_snap['from'], $season_snap['to'], $e->getMessage());
            }
        }

        // unset preloaded and cached seasons
        VikBooking::preloadSeasonRecords([$listing_id], false);

        // return values
        return [
            'listing_id'      => $listing_id,
            'new_intervals'   => count($season_intervals),
            'records_removed' => $records_removed,
            'intervals'       => $season_intervals,
            'errors'          => $errors,
        ];
    }
}