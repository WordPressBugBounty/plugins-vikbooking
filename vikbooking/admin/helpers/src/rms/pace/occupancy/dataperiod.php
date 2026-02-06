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
 * RMS Pace Occupancy Data-period implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceOccupancyDataperiod extends VBORmsPaceDataperiod
{
    /**
     * @var  ?VBORmsRatesRegistry
     */
    private ?VBORmsRatesRegistry $ratesRegistry = null;

    /**
     * @var  array
     */
    private array $cancellations = [];

    /**
     * @var  array
     */
    private array $hotEvents = [];

    /**
     * @inheritDoc
     */
    public function __construct(array $bookings, DateTimeInterface $period, ?DateInterval $interval = null)
    {
        // call parent constructor
        parent::__construct($bookings, $period, $interval);
    }

    /**
     * Returns the current booking cancellations.
     * 
     * @return  array
     */
    public function getCancellations(): array
    {
        return $this->cancellations;
    }

    /**
     * Sets the current booking cancellations.
     * 
     * @param   array   $cancellations   List of cancelled booking records.
     * 
     * @return  self
     */
    public function setCancellations(array $cancellations): VBORmsPaceDataperiod
    {
        $this->cancellations = $cancellations;

        return $this;
    }

    /**
     * Returns the current hot events.
     * 
     * @return  array
     */
    public function getHotEvents(): array
    {
        return $this->hotEvents;
    }

    /**
     * Sets the current hot events.
     * 
     * @param   array   $events   List of hot event details.
     * 
     * @return  self
     */
    public function setHotEvents(array $events): VBORmsPaceDataperiod
    {
        $this->hotEvents = $events;

        return $this;
    }

    /**
     * Returns the current rates registry, if any.
     * 
     * @return  ?VBORmsRatesRegistry
     */
    public function getRatesRegistry()
    {
        return $this->ratesRegistry;
    }

    /**
     * Sets the current rates registry.
     * 
     * @param   ?VBORmsRatesRegistry    $ratesRegistry  The registry to set.
     * 
     * @return  self
     */
    public function setRatesRegistry(?VBORmsRatesRegistry $ratesRegistry = null): VBORmsPaceDataperiod
    {
        $this->ratesRegistry = $ratesRegistry;

        return $this;
    }
}
