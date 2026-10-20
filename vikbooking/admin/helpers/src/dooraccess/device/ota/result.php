<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Door Access device OTA result registry.
 * 
 * @since   1.18.17 (J) - 1.8.17 (WP)
 */
final class VBODooraccessDeviceOtaResult
{
    /** DAC Device passcode generation result */
    protected VBODooraccessDeviceCapabilityResult $result;

    /** DAC integration device involved */
    protected VBODooraccessIntegrationDevice $device;

    /** PMS listing ID involved */
    protected int $listingId;

    /** Involved OTA booking registry */
    protected VBOBookingRegistry $registry;

    /** Holds the list of OTA IDs that got synced */
    protected array $syncedOtaIds = [];

    /** Holds the list of OTA names that got synced */
    protected array $syncedOtaNames = [];

    /** Associative list of metadata values */
    protected array $metaData = [];

    /** List of errors, strings or Exceptions */
    protected array $errorsList = [];

    /**
     * Binds object properties.
     */
    public function __construct(
        VBODooraccessDeviceCapabilityResult $result,
        VBODooraccessIntegrationDevice $device,
        int $listingId,
        VBOBookingRegistry $registry
    ) {
        $this->result = $result;
        $this->device = $device;
        $this->listingId = $listingId;
        $this->registry = $registry;
    }

    /**
     * Returns the DAC device passcode generation result.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns the DAC provider device implementation.
     * 
     * @return  VBODooraccessIntegrationDevice
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Returns the involved PMS listing ID.
     * 
     * @return  int
     */
    public function getListingId()
    {
        return $this->listingId;
    }

    /**
     * Returns the involved OTA booking registry.
     * 
     * @return  VBOBookingRegistry
     */
    public function getBooking()
    {
        return $this->registry;
    }

    /**
     * Tells whether OTAs were synced.
     * 
     * @return  bool
     */
    public function hasSyncedOtas()
    {
        return ($this->syncedOtaIds || $this->syncedOtaNames);
    }

    /**
     * Returns the list of synced OTA IDs.
     * 
     * @param   bool    $unique     Whether to get distinct values.
     * 
     * @return  array
     */
    public function getSyncedOtaIds(bool $unique = true)
    {
        if ($unique) {
            return array_values(array_unique($this->syncedOtaIds));
        }

        return $this->syncedOtaIds;
    }

    /**
     * Returns the list of synced OTA names.
     * 
     * @param   bool    $unique     Whether to get distinct values.
     * 
     * @return  array
     */
    public function getSyncedOtaNames(bool $unique = true)
    {
        if ($unique) {
            return array_values(array_unique($this->syncedOtaNames));
        }

        return $this->syncedOtaNames;
    }

    /**
     * Sets the information about a synced OTAs.
     * 
     * @param   int     $otaId      The OTA channel identifier.
     * @param   string  $otaName    The OTA channel readable name.
     * 
     * @return  self
     */
    public function setSyncedOta(int $otaId, string $otaName)
    {
        $this->syncedOtaIds[] = $otaId;
        $this->syncedOtaNames[] = $otaName;

        return $this;
    }

    /**
     * Resets the information about the synced OTAs.
     * 
     * @return  self
     */
    public function resetSyncedOta()
    {
        $this->syncedOtaIds = [];
        $this->syncedOtaNames = [];

        return $this;
    }

    /**
     * Returns the current metadata values.
     * 
     * @return  array
     */
    public function getMetadata()
    {
        return $this->metaData;
    }

    /**
     * Sets metadata values.
     * 
     * @param   array   $data   Associative list of values to set.
     * 
     * @return  self
     */
    public function setMetadata(array $data)
    {
        $this->metaData = $data;

        return $this;
    }

    /**
     * Tells whether the registry got errors.
     * 
     * @return  bool
     */
    public function hasError()
    {
        return $this->errorsList ? true : false;
    }

    /**
     * Returns the full error string, or null.
     * 
     * @return  ?string
     */
    public function getError()
    {
        if (!$this->hasError()) {
            return null;
        }

        $messages = [];

        foreach ($this->errorsList as $error) {
            if (is_string($error) && $error) {
                $messages[] = $error;
            } elseif ($error instanceof Exception) {
                $messages[] = $error->getMessage();
            }
        }

        return $messages ? implode("\n", $messages) : null;
    }

    /**
     * Appends one error to the pool.
     * 
     * @param   string|Exception    $error  Error message or Exception caught.
     * 
     * @return  self
     */
    public function setError($error)
    {
        if ((is_string($error) && $error) || ($error instanceof Exception)) {
            $this->errorsList[] = $error;
        }

        return $this;
    }

    /**
     * Resets the pool of errors.
     * 
     * @return  self
     */
    public function resetErrors()
    {
        $this->errorsList = [];

        return $this;
    }

    /**
     * Returns the proper result output value.
     */
    public function __toString()
    {
        if (!$this->hasSyncedOtas() && !$this->hasError()) {
            // nothing to express
            return '';
        }

        if ($this->hasError()) {
            // format errors occurred
            $otasInvolved = implode("\n", array_map(function($otaName) {
                return "❌ {$otaName}";
            }, $this->getSyncedOtaNames()));

            return substr((string) $this->getError(), 0, 200) . ($otasInvolved ? " \n{$otasInvolved}" : '');
        }

        // format OTAs involved, if any
        return implode("\n", array_map(function($otaName) {
            return "✅ {$otaName}";
        }, $this->getSyncedOtaNames()));
    }
}
