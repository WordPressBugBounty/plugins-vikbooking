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
 * Door Access integration device decorator. Such objects are
 * serialized and stored onto the database as blobs.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 */
final class VBODooraccessIntegrationDevice
{
    /**
     * @var  array
     */
    protected array $payload = [];

    /**
     * @var  array
     */
    protected array $connectedListings = [];

    /**
     * @var  array
     */
    protected array $capabilities = [];

    /**
     * @var  ?string
     */
    protected ?string $identifier = null;

    /**
     * @var  ?string
     */
    protected ?string $name = null;

    /**
     * @var  ?string
     */
    protected ?string $description = null;

    /**
     * @var  ?string
     */
    protected ?string $icon = null;

    /**
     * @var  ?string
     */
    protected ?string $model = null;

    /**
     * @var  ?float
     */
    protected ?float $batterylevel = null;

    /**
     * Class constructor.
     * 
     * @param   array   $payload    The remote device raw payload.
     */
    public function __construct(array $payload)
    {
        // set device full payload
        $this->setPayload($payload);
    }

    /**
     * Gets the device identification value.
     * 
     * @return   ?string     The device identification value.
     */
    public function getID()
    {
        return $this->identifier;
    }

    /**
     * Sets the device identification value.
     * 
     * @param   string  $id     The identification value.
     * 
     * @return  self
     */
    public function setID(string $id)
    {
        $this->identifier = preg_replace('/[^a-z0-9\-\_\.\|]/i', '', $id);

        return $this;
    }

    /**
     * Gets the device name.
     * 
     * @return   ?string     The device name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the device name.
     * 
     * @param   string  $name   The device name.
     * 
     * @return  self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the device description.
     * 
     * @return   ?string     The device description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the device description.
     * 
     * @param   string  $description   The device description.
     * 
     * @return  self
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets the device icon (image URI or HTML icon).
     * 
     * @return   ?string     The device icon.
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the device icon (image URI or HTML icon).
     * 
     * @param   string  $icon   The device icon.
     * 
     * @return  self
     */
    public function setIcon(string $icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Gets the device model.
     * 
     * @return   ?string     The device model.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the device model.
     * 
     * @param   string  $model   The device model.
     * 
     * @return  self
     */
    public function setModel(string $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Gets the device battery level.
     * 
     * @return   ?float     The device battery level.
     */
    public function getBatteryLevel()
    {
        return $this->batterylevel;
    }

    /**
     * Sets the device battery level.
     * 
     * @param   float  $level   The device battery level.
     * 
     * @return  self
     */
    public function setBatteryLevel(float $level)
    {
        $this->batterylevel = $level;

        return $this;
    }

    /**
     * Gets the device payload.
     * 
     * @return   array     The device raw payload.
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Sets the device payload.
     * 
     * @param   array   $payload    The device raw payload.
     * 
     * @return  self
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Gets the listing IDs connected to the device.
     * 
     * @return   array     Linear array of VikBooking listing IDs.
     */
    public function getConnectedListings()
    {
        return $this->connectedListings;
    }

    /**
     * Sets the listing IDs connected to the device.
     * 
     * @param   array   $listings    List of VikBooking listing IDs.
     * 
     * @return  self
     */
    public function setConnectedListings(array $listings)
    {
        $this->connectedListings = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $listings)
                )
            )
        );

        return $this;
    }

    /**
     * Adds an entry to the list of device connected listing IDs.
     * 
     * @param   int     $listingId  The listing ID to add as connected.
     * 
     * @return  self
     */
    public function addConnectedListing(int $listingId)
    {
        if (!in_array($listingId, $this->connectedListings)) {
            // push listing ID
            $this->connectedListings[] = $listingId;
        }

        return $this;
    }

    /**
     * Removes an entry from the list of device connected listing IDs.
     * 
     * @param   int     $listingId  The listing ID to remove and disconnect.
     * 
     * @return  self
     */
    public function removeConnectedListing(int $listingId)
    {
        $this->connectedListings = array_values(array_filter($this->connectedListings, function($currentId) use ($listingId) {
            return $currentId != $listingId;
        }));

        return $this;
    }

    /**
     * Gets the device capabilities.
     * 
     * @return   VBODooraccessDeviceCapability[]
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * Returns a specific capability from the current device.
     * 
     * @param   string  $capabilityId   The device capability identifier.
     * 
     * @return  VBODooraccessDeviceCapability
     * 
     * @throws  Exception
     */
    public function getCapabilityById(string $capabilityId)
    {
        foreach ($this->getCapabilities() as $cap) {
            if ($cap->getID() == $capabilityId) {
                return $cap;
            }
        }

        throw new Exception(sprintf('Could not access the requested capability ID: %s.', $capabilityId), 404);
    }

    /**
     * Tells if the device has got capabilities.
     * 
     * @return   bool
     */
    public function hasCapabilities()
    {
        return (bool) count($this->capabilities);
    }

    /**
     * Sets a device capability.
     * 
     * @param   VBODooraccessDeviceCapability   $capability
     * 
     * @return  self
     * 
     * @throws  Exception
     */
    public function setCapability(VBODooraccessDeviceCapability $capability)
    {
        if (!$capability->isValid()) {
            throw new Exception('Capability is missing required information.', 500);
        }

        // push capability
        $this->capabilities[] = $capability;

        return $this;
    }

    /**
     * Sets multiple device capability objects.
     * 
     * @param   VBODooraccessDeviceCapability[]   $capabilities   List of device capability objects.
     * 
     * @return  self
     */
    public function setCapabilities(array $capabilities)
    {
        foreach ($capabilities as $capability) {
            $this->setCapability($capability);
        }

        return $this;
    }

    /**
     * Resets the device capabilities.
     * 
     * @return  self
     */
    public function resetCapabilities()
    {
        $this->capabilities = [];

        return $this;
    }

    /**
     * Tells if the device has decorated the mandatory properties.
     * 
     * @return  bool
     */
    public function isComplete()
    {
        if (!$this->identifier || !$this->name) {
            return false;
        }

        return true;
    }
}
