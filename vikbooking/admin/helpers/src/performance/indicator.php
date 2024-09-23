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
 * Performance Indicator implementation.
 * 
 * @since  1.16.10 (J) - 1.6.10 (WP)
 */
final class VBOPerformanceIndicator
{
    /**
     * Indicators pool.
     * 
     * @var  array
     */
    private static $indicators = [];

    /**
     * Starts an indicator to measure the performances.
     * 
     * @param   string  $id  The indicator identifier.
     * 
     * @return  string       The indicator identifier.
     */
    public static function start(string $id = '')
    {
        if (!$id) {
            $id = static::uuid();
        }

        static::$indicators[$id] = [
            'id'       => $id,
            'memory'   => memory_get_usage(),
            'memory_r' => static::formatMemoryBytes(memory_get_usage()),
            'peaks'    => [],
            'ms_start' => microtime(true),
        ];

        return $id;
    }

    /**
     * Ends an indicator to measure the performances.
     * 
     * @param   string  $id  The indicator identifier.
     * 
     * @return  array        List of measurements.
     */
    public static function end(string $id = '')
    {
        if (!$id) {
            $id = end(static::$indicators)['id'] ?? '';
        }

        $start_metrics = static::$indicators[$id] ?? [];

        if (!$start_metrics) {
            return [];
        }

        // ensure metrics were not ended already
        if (static::$indicators[$id]['ended'] ?? false) {
            // indicator was ended already
            return static::$indicators[$id];
        }

        $now_ms = microtime(true);
        $now_memory = memory_get_usage();
        $memory_used = $now_memory - $start_metrics['memory'];
        $ms_duration = $now_ms - $start_metrics['ms_start'];

        $end_metrics = [
            'ended'         => true,
            'ms_end'        => $now_ms,
            'memory_end'    => $now_memory,
            'ms_duration'   => $ms_duration,
            'memory_used'   => $memory_used,
            'memory_end_r'  => static::formatMemoryBytes($now_memory),
            'memory_peak_r' => static::formatMemoryBytes(memory_get_peak_usage()),
            'memory_used_r' => static::formatMemoryBytes($memory_used),
            'ms_duration_r' => round($ms_duration, 4),
        ];

        static::$indicators[$id] = array_merge($start_metrics, $end_metrics);

        return static::$indicators[$id];
    }

    /**
     * Registers a memory peak within an indicator.
     * 
     * @param   string  $id  The indicator identifier.
     * 
     * @return  void
     */
    public static function peak(string $id = '')
    {
        if (!$id) {
            $id = end(static::$indicators)['id'] ?? '';
        }

        $start_metrics = static::$indicators[$id] ?? [];

        if (!$start_metrics) {
            return;
        }

        $now_memory = memory_get_usage();

        // push peak
        static::$indicators[$id]['peaks'] = [
            'memory'   => $now_memory,
            'memory_r' => static::formatMemoryBytes($now_memory),
        ];
    }

    /**
     * Ends all indicators and returns them.
     * 
     * @param   bool  $end  True to end all indicators before returning them.
     * 
     * @return  void
     */
    public static function getAll($end = true)
    {
        $list = [];

        foreach (static::$indicators as $id => $indicator) {
            if ($end) {
                static::end($id);
            }

            $list[$id] = static::$indicators[$id];
        }

        return $list;
    }

    /**
     * Generates a random UUID v4.
     *
     * A UUID is a 16-octet (128-bit) number. In its canonical form, a UUID is represented by 32 
     * hexadecimal digits, displayed in five groups separated by hyphens, in the form 8-4-4-4-12
     * for a total of 36 characters (32 alphanumeric characters and four hyphens).
     *
     * @return  string
     */
    public static function uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Helper method to format float values expressed in bytes (i.e. memory usage).
     * 
     * @param   float   $bytes      The number of bytes to format.
     * @param   int     $precision  The precision to use for rounding.
     * 
     * @return  string              The formatted bytes string.
     */
    public static function formatMemoryBytes($bytes, $precision = 2)
    {
        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $negative = (bool) ($bytes < 0);
        $bytes    = $negative ? abs($bytes) : $bytes;
        $bytes    = max($bytes, 0);
        $pow      = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow      = min($pow, count($units) - 1);
        $bytes    /= pow(1024, $pow);

        return ($negative ? '-' : '') . round($bytes, $precision) . ($units[$pow] ?? '?');
    }
}
