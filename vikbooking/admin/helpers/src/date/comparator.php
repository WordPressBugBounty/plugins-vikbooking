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
 * Helper class for dates comparison.
 * 
 * @since 1.18.6 (J) - 1.8.6 (WP)
 */
class VBODateComparator
{
    /**
     * Aligns a given date to the same weekday in a different year, applying
     * corrections for leap-year-induced weekday shifts when necessary.
     *
     * Process overview:
     *
     * 1. Shift the original date by the difference in years to obtain the
     *    tentative equivalent date in the target year.
     *
     * 2. Compare the weekday of the original date with the weekday of the shifted
     *    date, then compute the minimal forward/backward number of steps needed
     *    to realign the weekday (using modular arithmetic).
     *
     * 3. If the date is on or after March 1st, evaluate whether a leap-year
     *    correction is required, because leap years can cause unexpected weekday
     *    shifts due to the extra day (February 29).
     *
     * 4. Apply the final computed weekday shift to the target date and return it.
     *
     * @param   int|string|DateTime  $date  The source date to convert.
     * @param   int                  $year  The target year to align the weekday to.
     *
     * @return  DateTime  A new DateTime object representing the input date
     *                    shifted to the target year while preserving the weekday.
     */
    public static function alignWeekDay($date, int $year): DateTime
    {
        if (is_numeric($date)) {
            // convert from timestamp
            $date = date('Y-m-d', $date);
        }

        if (is_string($date)) {
            // create from date
            $date = JFactory::getDate($date);
        }

        // calculate the difference in years
        $diffYears = $year - (int) $date->format('Y');

        // shift the date for the specified year
        $target = clone $date;
        $target->modify(($diffYears >= 0 ? '+' : '') . $diffYears . ' years');

        // fetch week days for both original and target dates
        $originalWeekDay = (int) $date->format('N');
        $targetWeekDay = (int) $target->format('N');

        // calculate forwards and backward steps
        $fwdSteps = ($originalWeekDay - $targetWeekDay + 7) % 7;
        $bwdSteps = ($targetWeekDay - $originalWeekDay + 7) % 7;

        // use the path that takes the lowest number of steps
        $diffWeekDays = $fwdSteps < $bwdSteps ? ('+' . $fwdSteps) : ('-' . $bwdSteps);

        // check whether we should manually fix the unexpected behavior that occurs with leap years,
        // only for dates equal or after the 1st of March
        if ($date->format('md') > '0229' && static::shouldFixLeapYear($date->format('Y'), $year)) {
            // -3 steps becomes +4, +3 steps becomes -4
            $diffWeekDays = $diffWeekDays == "-3" ? '+4' : '-4';   
        }

        // shift the target date by the specified days
        $target->modify($diffWeekDays . ' days');

        return $target;
    }

    /**
     * Calculates the minimum number of "steps" needed to align the weekday
     * of a given date with the weekday of the same date in another year.
     *
     * A "step" represents a shift of days forward (positive value) or backward
     * (negative value) within the weekly cycle (Mon–Sun).
     * The goal is to determine whether moving forward or backward requires fewer
     * steps to go from the weekday of the original date to the weekday of the
     * target date in the specified year.
     *
     * @param   DateTime  $date  The reference date.
     * @param   int       $year  The target year for calculating the weekday difference.
     *
     * @return  int       Minimum number of steps (positive for forward movement; negative for backward movement).
     */
    protected static function calcLowestSteps(DateTime $date, int $year): int
    {
        // calculate the difference in years
        $diffYears = $year - (int) $date->format('Y');

        // shift the date for the specified year
        $target = clone $date;
        $target->modify(($diffYears >= 0 ? '+' : '') . $diffYears . ' years');

        // fetch week days for both original and target dates
        $originalWeekDay = (int) $date->format('N');
        $targetWeekDay = (int) $target->format('N');

        // calculate forwards and backward steps
        $fwdSteps = ($originalWeekDay - $targetWeekDay + 7) % 7;
        $bwdSteps = ($targetWeekDay - $originalWeekDay + 7) % 7;

        // use the path that takes the lowest number of steps
        return $fwdSteps < $bwdSteps ? $fwdSteps : $bwdSteps * -1;
    }

    /**
     * Determines whether a leap-year adjustment should be applied when comparing
     * or aligning dates between two different years.
     *
     * The logic checks whether one of the two years is a leap year and the other is not.
     * If both years are either leap years or non-leap years, no correction is needed.
     *
     * If exactly one year is a leap year, the function calculates the minimal weekday
     * shift (in steps) between January 1st of the current year and January 1st of the
     * target year.
     *
     * A leap-year correction is required in two specific cases:
     * - The current year *is* a leap year and the weekday shift is exactly +3 steps.
     * - The target year *is* a leap year and the weekday shift is exactly -3 steps.
     *
     * These specific offsets occur because leap years introduce an extra day (Feb 29),
     * which causes the weekly cycle to advance or retreat by 2–3 weekday positions,
     * depending on direction.
     *
     * @param   int   $currentYear  The year from which the calculation originates.
     * @param   int   $targetYear   The destination year being evaluated.
     *
     * @return  bool  True if a leap-year adjustment is required, false otherwise.
     */
    protected static function shouldFixLeapYear(int $currentYear, int $targetYear): bool {
        // check whether the current year and the target one are leap
        $currentYearLeap = checkdate(2, 29, $currentYear);
        $targetYearLeap = checkdate(2, 29, $targetYear);

        // apply XOR to leap years, as we need to apply the correction only if one of them is leap
        if (!($currentYearLeap ^ $targetYearLeap)) {
            // correction not needed
            return false;
        }

        // calculate the minimum steps
        $steps = static::calcLowestSteps(new DateTime("$currentYear-01-01"), $targetYear);

        // check whether the correction should be applied
        if (($currentYearLeap && $steps === 3) || ($targetYearLeap && $steps === -3)) {
            return true;
        }

        return false;
    }
}
