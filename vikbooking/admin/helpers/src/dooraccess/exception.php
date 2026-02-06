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
 * Exception used to transport retry-data for Door Access Control.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
class VBODooraccessException extends Exception
{
    /** @var ?VBODooraccessIntegrationDevice */
    protected $device = null;

    /** @var array */
    protected $retryData = [];

    /** @var ?string */
    protected $retryCallback = null;

    /**
     * Returns the involved device, if any.
     * 
     * @return  ?VBODooraccessIntegrationDevice
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Sets the involved device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device  The involved device.
     * 
     * @return  self
     */
    public function setDevice(VBODooraccessIntegrationDevice $device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Returns the current retry data, if any.
     * 
     * @return  array
     */
    public function getRetryData()
    {
        return $this->retryData;
    }

    /**
     * Sets the current retry data.
     * 
     * @param   array   $data   The data to set.
     * 
     * @return  self
     */
    public function setRetryData(array $data)
    {
        $this->retryData = $data;

        return $this;
    }

    /**
     * Returns the current retry callback, if any.
     * 
     * @return  ?string
     */
    public function getRetryCallback()
    {
        return $this->retryCallback;
    }

    /**
     * Sets the current retry callback.
     * 
     * @param   string   $callback   The callback to set.
     * 
     * @return  self
     */
    public function setRetryCallback(string $callback)
    {
        $this->retryCallback = $callback;

        return $this;
    }
}
