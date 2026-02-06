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
 * RMS Pace Data Metric abstract implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
abstract class VBORmsPaceDataMetric
{
    /**
     * @var  ?array
     */
    protected ?array $options = null;

    /**
     * @var  mixed
     */
    protected $metric = null;

    /**
     * Class constructor.
     * 
     * @param   ?array  $options    Optional settings to follow.
     */
    public function __construct(?array $options = null)
    {
        // bind optional options
        $this->options = $options;
    }

    /**
     * Returns the identifier value of the current data metric.
     * 
     * @return  string
     */
    public function getID()
    {
        return preg_replace('/^VBORmsPaceDataMetric/i', '', strtolower(get_class($this)));
    }

    /**
     * Returns the requested option by key.
     * 
     * @param   string  $key    The option identifier to get.
     * 
     * @return  mixed           Current option value or default value.
     */
    public function get(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Sets the requested option key and value.
     * 
     * @param   string  $key    The option identifier to set.
     * @param   mixed   $value  The option value to set.
     * 
     * @return  self
     */
    public function set(string $key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Returns the previously calculated metric.
     * 
     * @return  mixed   The cached metric value, if any.
     */
    public function getMetric()
    {
        return $this->metric;
    }

    /**
     * Sets the calculated metric value.
     * 
     * @param   mixed   $value  The calculated metric value to cache.
     * 
     * @return  self
     */
    public function setMetric($value)
    {
        $this->metric = $value;

        return $this;
    }

    /**
     * Extracts the metric from the given pace data-period containing the bookings and current metrics.
     * 
     * @param   VBORmsPaceDataperiod    $paceDataPeriod     The pace data-period object to iterate.
     * @param   array                   $periodPaceMetrics  The currently set pace data metrics so far.
     * 
     * @return  mixed                                       The calculated data metric value.
     * 
     * @throws  Exception
     */
    abstract public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null);
}
