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
     * @var    array
     * 
     * @since  1.18.7 (J) - 1.8.7 (WP)
     */
    protected array $connectedSubunits = [];

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
     * @var  bool
     */
    protected bool $dataChanged = false;

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
     * @param   bool    $raw    True to accept any value.
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP) added argument $raw to support MAC addresses.
     */
    public function setID(string $id, bool $raw = false)
    {
        if (!$raw) {
            // sanitize value
            $id = preg_replace('/[^a-z0-9\-\_\.\|]/i', '', $id);
        }

        $this->identifier = $id;

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
        // set value
        $this->name = $name;

        // turn data-changed flag on
        $this->setDataChanged(true);

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
        // set value
        $this->description = $description;

        // turn data-changed flag on
        $this->setDataChanged(true);

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
        // set value
        $this->model = $model;

        // turn data-changed flag on
        $this->setDataChanged(true);

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
        // set value
        $this->batterylevel = $level;

        // turn data-changed flag on
        $this->setDataChanged(true);

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
     * Whether data has changed on the device.
     * 
     * @return  bool
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getDataChanged()
    {
        return $this->dataChanged;
    }

    /**
     * Toggles device data-changed flag.
     * 
     * @param   bool   $changed    Whether data on device has changed.
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function setDataChanged(bool $changed)
    {
        $this->dataChanged = $changed;

        return $this;
    }

    /**
     * Counts all connected listings by taking care of subunits.
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function countConnectedListings()
    {
        $totalSubunitsCount = 0;
        $excludeParents = 0;

        foreach ($this->connectedSubunits as $listingId => $subunits) {
            // count listing subunits
            $totalSubunits = count($subunits);

            // increase total subunits count
            $totalSubunitsCount += $totalSubunits;

            // increase parent listings to exclude
            $excludeParents += $totalSubunits ? 1 : 0;
        }

        // return the accurate total connected listings count (inclusive of subunits)
        return count($this->connectedListings) + $totalSubunitsCount - $excludeParents;
    }

    /**
     * Counts the connected listing-subunits from the given list.
     * 
     * @param   array   $listingSubunits    Linear array of strings as "roomid-subunit".
     * 
     * @return  int                         Total number of device connected listing units.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function countMatchingListingUnits(array $listingSubunits)
    {
        $totalCount = 0;

        foreach ($listingSubunits as $listingSubunit) {
            // extract listing ID and subunit number
            $segments = explode('-', (string) $listingSubunit);
            $listingId = (int) $segments[0];
            $subunitId = (int) ($segments[1] ?? 0);

            if (!in_array($listingId, $this->connectedListings)) {
                // main listing ID not connected
                continue;
            }

            if ($subunitId && ($this->connectedSubunits[$listingId] ?? [])) {
                // make sure this exact subunit number is connected on the device
                if (in_array($subunitId, $this->connectedSubunits[$listingId])) {
                    // match found, increase counter
                    $totalCount++;
                }
            } else {
                // safely increase the counter for the matching listing ID
                $totalCount++;
            }
        }

        return $totalCount;
    }

    /**
     * Given a list of listing-subunit pairs, intersects the available connections.
     * 
     * @param   array   $listingSubunits    Linear array of strings as "roomid-subunit".
     * 
     * @return  array                       List of intersecting listing-subunit pairs.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function intersectListingUnits(array $listingSubunits)
    {
        $intersections = [];

        foreach ($listingSubunits as $listingSubunit) {
            // extract listing ID and subunit number
            $segments = explode('-', (string) $listingSubunit);
            $listingId = (int) $segments[0];
            $subunitId = (int) ($segments[1] ?? 0);

            if (!in_array($listingId, $this->connectedListings)) {
                // main listing ID not connected
                continue;
            }

            if ($subunitId && ($this->connectedSubunits[$listingId] ?? [])) {
                // make sure this exact subunit number is connected on the device
                if (in_array($subunitId, $this->connectedSubunits[$listingId])) {
                    // intersection found for listing and sub-unit
                    $intersections[] = [$listingId, $subunitId];
                }
            } else {
                // intersection found for listing
                $intersections[] = [$listingId, 0];
            }
        }

        return $intersections;
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
     * Gets the listing subunit IDs connected to the device.
     * 
     * @return   array     Associative list of listing sub-unit IDs.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getConnectedSubunits()
    {
        return $this->connectedSubunits;
    }

    /**
     * Sets the subunit IDs connected to the device.
     * 
     * @param   array   $subunits    Associative list of listing sub-unit IDs.
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function setConnectedSubunits(array $subunits)
    {
        $this->connectedSubunits = array_filter(array_map(function($list) {
            return array_map('intval', (array) $list);
        }, $subunits));

        return $this;
    }

    /**
     * Gets the subunit IDs of the given listing ID connected to the device.
     * 
     * @param   int    $listingId  The VikBooking listing ID.
     * 
     * @return  array  Linear array of listing sub-unit IDs.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getConnectedListingSubunits(int $listingId)
    {
        return $this->connectedSubunits[$listingId] ?? [];
    }

    /**
     * Adds an entry to the list of device connected listing IDs.
     * 
     * @param   int     $listingId  The listing ID to add as connected.
     * @param   ?int    $subunitId  Optional listing sub-unit ID (1-based).
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP) added argument $subunitId.
     */
    public function addConnectedListing(int $listingId, ?int $subunitId = null)
    {
        if (!in_array($listingId, $this->connectedListings)) {
            // push listing ID
            $this->connectedListings[] = $listingId;
        }

        if ($subunitId) {
            // push listing sub-unit ID relation
            $this->connectedSubunits[$listingId] = $this->connectedSubunits[$listingId] ?? [];
            if (!in_array($subunitId, $this->connectedSubunits[$listingId])) {
                $this->connectedSubunits[$listingId][] = $subunitId;
            }
        }

        return $this;
    }

    /**
     * Removes an entry from the list of device connected listing IDs.
     * 
     * @param   int     $listingId  The listing ID to remove and disconnect.
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP) added argument $subunitId.
     */
    public function removeConnectedListing(int $listingId, ?int $subunitId = null)
    {
        // process subunit-level connection first
        if ($subunitId && ($this->connectedSubunits[$listingId] ?? [])) {
            $this->connectedSubunits[$listingId] = array_values(array_filter($this->connectedSubunits[$listingId], function($currentSubunitId) use ($subunitId) {
                return $currentSubunitId != $subunitId;
            }));

            if ($this->connectedSubunits[$listingId]) {
                // the main listing still has some sub-units connected
                // do not proceed
                return $this;
            }

            // no more sub-units under this listing
            // proceed with the listil-level connection removal for the whole listing
            unset($this->connectedSubunits[$listingId]);
        }

        // process listing-level connection
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
