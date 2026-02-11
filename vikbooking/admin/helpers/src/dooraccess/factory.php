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
 * Door Access Factory implementation.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 */
final class VBODooraccessFactory
{
    /**
     * The singleton class instance.
     *
     * @var  VBODooraccessFactory
     */
    protected static $instance = null;

    /**
     * List of door access integration objects loaded.
     *
     * @var  VBODooraccessFactory
     */
    protected $integrations = [];

    /**
     * Class constructor is protected.
     *
     * @see     getInstance()
     */
    protected function __construct()
    {
        // load all the available door access integrations
        $this->loadIntegrations();
    }

    /**
     * Access the factory object instance.
     *
     * @return  self    A new or the existing class instance.
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Attempts to return the requested integration provider by alias.
     * 
     * @param   string  $providerAlias  The integration provider alias string.
     * 
     * @return  ?VBODooraccessIntegrationAware
     */
    public function getIntegrationProvider(string $providerAlias)
    {
        if (!$providerAlias) {
            return null;
        }

        foreach ($this->integrations as $integration) {
            if ($integration->getAlias() === $providerAlias) {
                // always return a cloned instance of the integration object
                return clone $integration;
            }
        }

        return null;
    }

    /**
     * Returns the loaded integration providers.
     * 
     * @param   bool    $assoc  True to obtain an associative alias-name list,
     *                          full objects list otherwise.
     * 
     * @return  array
     */
    public function getIntegrationProviders(bool $assoc = false)
    {
        if (!$assoc) {
            return $this->integrations;
        }

        $providers = [];

        foreach ($this->integrations as $integration) {
            $providers[$integration->getAlias()] = $integration->getName();
        }

        return $providers;
    }

    /**
     * Loads a list of integration records for the given provider.
     * 
     * @param   string  $providerAlias  The integration provider alias string.
     * @param   ?int    $profileId      Optional integration record (profile) ID.
     * 
     * @return  array                   List of associative arrays, empty array otherwise.
     */
    public function loadIntegrationRecords(string $providerAlias, ?int $profileId = null)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_door_access_integrations'))
                ->where($dbo->qn('provider_alias') . ' = ' . $dbo->q($providerAlias))
        );

        $records = array_map(function($record) {
            // return the decoded columns
            return $this->decodeIntegrationRecord($record);
        }, $dbo->loadAssocList());

        if ($profileId) {
            // sort profile records by the given profile ID
            usort($records, function($a, $b) use ($profileId) {
                if ($a['id'] == $profileId) {
                    return -1;
                }
                if ($b['id'] == $profileId) {
                    return 1;
                }

                return $a['id'] <=> $b['id'];
            });
        }

        return $records;
    }

    /**
     * Loads the requested provider integration record ID.
     * 
     * @param   int    $profileId      Provider integration record (profile) ID.
     * 
     * @return  array                  Associative record found or empty array.
     */
    public function loadIntegrationRecord(int $profileId)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_door_access_integrations'))
                ->where($dbo->qn('id') . ' = ' . $profileId)
        );

        $record = $dbo->loadAssoc();

        if (!$record) {
            return [];
        }

        // return the decoded columns
        return $this->decodeIntegrationRecord($record);
    }

    /**
     * Loads the integration records capable of generating door-access passcodes.
     * 
     * @param   string|array  $generationType   The type(s) of generating records.
     * 
     * @return  array                           List of eligible integration records.
     * 
     * @throws  InvalidArgumentException
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP) argument $generationType is now of type string|array.
     */
    public function loadGeneratingIntegrations($generationType)
    {
        $dbo = JFactory::getDbo();

        if (is_string($generationType)) {
            // always expect an array
            $generationType = [$generationType];
        }

        if (!is_array($generationType) || !$generationType) {
            throw new InvalidArgumentException('Argument $generationType must be either a string or an array of strings.', 500);
        }

        // quote all generation type values
        $generationTypes = array_map([$dbo, 'q'], $generationType);

        // fetch eligible provider integrations
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_door_access_integrations'))
                ->where($dbo->qn('gentype') . (count($generationTypes) > 1 ? ' IN (' . implode(', ', $generationTypes) . ')' : ' = ' . $generationTypes[0]))
        );

        return array_map(function($record) {
            // return the decoded columns
            return $this->decodeIntegrationRecord($record);
        }, $dbo->loadAssocList());
    }

    /**
     * Loads the active (properly configured) integration records.
     * 
     * @return  VBODooraccessIntegrationAware[]   List of active integration objects.
     */
    public function loadActiveIntegrations()
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_door_access_integrations'))
        );

        $records = array_map(function($record) {
            // return the decoded columns
            return $this->decodeIntegrationRecord($record);
        }, $dbo->loadAssocList());

        $activeIntegrations = [];

        // scan all integration records to make sure some devices were configured
        foreach ($records as $record) {
            // get the integration provider
            $integration = $this->getIntegrationProvider($record['provider_alias']);
            if (!$integration) {
                // unknown integration provider
                continue;
            }
            
            // inject profile record within the integration provider
            $integration->setProfileRecord($record);

            // ensure the integration counts some active devices
            if ($integration->getDevices()) {
                // push value integration
                $activeIntegrations[] = $integration;
            }
        }

        return $activeIntegrations;
    }

    /**
     * Saves or updates a provider integration record.
     * 
     * @param   VBODooraccessIntegrationAware   $integration    The provider integration object.
     * @param   array                           $options        Associative list of saving options.
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    public function saveIntegrationRecord(VBODooraccessIntegrationAware $integration, array $options)
    {
        $dbo = JFactory::getDbo();

        // check if we have an existing record
        $record = $integration->getProfileRecord();

        if (!empty($options['id_profile'])) {
            // make sure we are updating an existing record
            $record = $this->loadIntegrationRecord((int) $options['id_profile']);

            if (!$record) {
                throw new Exception('Invalid integration record ID.', 404);
            }

            // inject the integration record found
            $integration->setProfileRecord($record);
        }

        if (empty($options['profile_name'])) {
            // ensure we have a valid profile name
            $options['profile_name'] = $integration->getProfileName() ?: date('Y-m-d H:i:s');
        }

        // prepare database record
        $dbRecord = new stdClass;

        if ($record) {
            // existing record will be updated
            $dbRecord->id = $integration->getProfileID();
        }

        // set provider alias
        $dbRecord->provider_alias = $integration->getAlias();

        // set record name
        $dbRecord->name = $options['profile_name'];

        if (isset($options['gentype'])) {
            // set record generation type
            $dbRecord->gentype = (string) $options['gentype'];
        }

        if (isset($options['genperiod'])) {
            // set record generation period
            $dbRecord->genperiod = (string) $options['genperiod'];
        }

        if (is_array($options['settings'] ?? null)) {
            if (!($options['overwrite_settings'] ?? null)) {
                // merge existing settings with the new ones to allow
                // custom HTTP Transporter hidden settings to be kept
                $options['settings'] = array_merge($integration->getSettings(), $options['settings']);
            }
            // set record settings
            $dbRecord->settings = json_encode($options['settings']);
        }

        if (is_array($options['devices'] ?? null)) {
            // set record devices map
            $dbRecord->devices = serialize($options['devices']);
        }

        if (is_array($options['data'] ?? null)) {
            // set record data
            $dbRecord->data = json_encode($options['data']);
        }

        if (!empty($dbRecord->id)) {
            // update database record
            if (!$dbo->updateObject('#__vikbooking_door_access_integrations', $dbRecord, 'id')) {
                throw new Exception('Could not update integration record.', 500);
            }

            // process completed
            return;
        }

        // create new record
        $dbo->insertObject('#__vikbooking_door_access_integrations', $dbRecord, 'id');
        if (empty($dbRecord->id)) {
            throw new Exception('Could not save integration record.', 500);
        }

        // properly load the newly created record
        $record = $this->loadIntegrationRecord((int) $dbRecord->id);

        if (!$record) {
            throw new Exception('Did not save integration record.', 500);
        }

        // inject the integration record created to complete the process
        $integration->setProfileRecord($record);        
    }

    /**
     * Deletes a provider integration record.
     * 
     * @param   VBODooraccessIntegrationAware   $integration    The provider integration object.
     * 
     * @return  true
     * 
     * @throws  Exception
     */
    public function deleteIntegrationRecord(VBODooraccessIntegrationAware $integration)
    {
        $dbo = JFactory::getDbo();

        if (!$integration->hasProfileRecord() || !$integration->getProfileID()) {
            throw new Exception('Missing integration profile record for deletion.', 404);
        }

        // delete the current profile record from the database
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_door_access_integrations'))
                ->where($dbo->qn('id') . ' = ' . $integration->getProfileID())
                ->where($dbo->qn('provider_alias') . ' = ' . $dbo->q($integration->getProfileProvider()))
        );
        $dbo->execute();

        if (!$dbo->getAffectedRows()) {
            throw new Exception('Could not delete the requested integration profile record.', 500);
        }

        return true;
    }

    /**
     * Fetches and updates the provider remote devices within the active profile record.
     * 
     * @param   VBODooraccessIntegrationAware   $integration    The provider integration object.
     * 
     * @return  int
     * 
     * @throws  Exception
     */
    public function updateProviderDevices(VBODooraccessIntegrationAware $integration)
    {
        if (!$integration->hasProfileRecord()) {
            throw new Exception('Missing integration profile record', 500);
        }

        // get the previous record devices
        $previousDevices = $integration->getDevices();

        // fetch the provider remote devices and have them decorated
        $newDevices = $integration->fetchDevices();

        if ($previousDevices && $newDevices) {
            // restore the previous connected listings per device identifier, if any
            foreach ($previousDevices as $previousDevice) {
                if (!$previousDevice->getConnectedListings() || !$previousDevice->isComplete()) {
                    // this old device had no connected listings
                    continue;
                }

                // find the corresponding device identifier in the new list
                foreach ($newDevices as $newDevice) {
                    if ($newDevice->getID() === $previousDevice->getID()) {
                        // device found, set the previously connected listings and sub-units
                        $newDevice->setConnectedListings($previousDevice->getConnectedListings());
                        $newDevice->setConnectedSubunits($previousDevice->getConnectedSubunits());
                        break;
                    }
                }
            }
        }

        // update integration record
        $this->saveIntegrationRecord($integration, ['devices' => $newDevices]);

        return count($newDevices);
    }

    /**
     * Returns a list of special tags for contents parsing. The list will
     * include one special tag for every configured provider integration record.
     * 
     * @return  array   List of special tag strings.
     */
    public function getInstalledSpecialTags()
    {
        $specialTags = [];

        foreach ($this->loadActiveIntegrations() as $integration) {
            if ($tag = $integration->getProfileSpecialTag()) {
                // push special tag string for this integration profile
                $specialTags[] = $tag;
            }
        }

        return $specialTags;
    }

    /**
     * Parses all tokens in the given template string and replaces them with the proper
     * door access passcode(s) that was previously generated for the given booking registry.
     * 
     * @param   VBOBookingRegistry  $registry   The booking (and rooms booked) registry.
     * @param   string              &$tmpl      The template string to parse and manipulate.
     * 
     * @return  int|false                       False if no tokens were found, or number of DAC tokens.
     */
    public function parseTokens(VBOBookingRegistry $registry, string &$tmpl)
    {
        // parse all special tags related to the door-access framework
        preg_match_all('/\{door_access\:\s?p([0-9]+)\_[a-z0-9\-\_]+\}/U', $tmpl, $matches);

        // count tags found
        $tagsCount = count((array) ($matches[0] ?? []));
        if (!$tagsCount) {
            // no special tags to parse
            return false;
        }

        // build profile IDs-tags associative list
        $profileIdTags = [];
        foreach ($matches[0] as $tag) {
            if (preg_match('/^\{door_access\:\s?p([0-9]+)\_[a-z0-9\-\_]+\}$/', $tag, $matchProfile)) {
                $profileIdTags[$matchProfile[1]] = $matchProfile[0];
            }
        }

        // scan all profile-tags list
        foreach ($profileIdTags as $profileId => $profileTag) {
            // load the integration profile record by ID
            $record = $this->loadIntegrationRecord((int) $profileId);

            // get the integration provider
            $integration = $this->getIntegrationProvider($record['provider_alias'] ?? '');

            if (!$record || !$integration) {
                // profile integration record not found
                $tagsCount--;

                // replace tag within the template string
                $tmpl = str_replace($profileTag, '', $tmpl);

                // go to the next profile-tag
                continue;
            }

            // inject profile record within the integration provider
            $integration->setProfileRecord($record);

            // find the passcode data that were previously created for this booking by this integration
            $previousPasscodes = VikBooking::getBookingHistoryInstance($registry->getID())
                ->getEventsWithData(['ND', 'MD'], function($data) use ($integration) {
                    // cast history data payload to an array
                    $data = (array) $data;

                    if (empty($data['provider']) || $data['provider'] != $integration->getProfileProvider()) {
                        // integration provider alias mismatch
                        return false;
                    }

                    if (empty($data['profile']) || $data['profile'] != $integration->getProfileID()) {
                        // integration profile ID mismatch
                        return false;
                    }

                    if (empty($data['device']) || !$integration->deviceExists((string) $data['device'])) {
                        // unknown device
                        return false;
                    }

                    // ensure the history event contains the passcode or its generation properties
                    return !empty($data['passcode']) || !empty($data['props']);
                });

            if (!$previousPasscodes) {
                // nothing to set
                $tagsCount--;

                // replace tag within the template string
                $tmpl = str_replace($profileTag, '', $tmpl);

                // go to the next profile-tag
                continue;
            }

            // get list of booked listing ids and subunits
            $bookedListingSubunits = $registry->getBookedListingSubunits();

            // count the number of expected passcodes that were generated for this booking
            $expectedPasscodes = 0;

            // iterate all provider integration devices
            foreach ($integration->getDevices() as $device) {
                // count, if any, how many listings (with subunits) are compatible with the current device
                $expectedPasscodes += $device->countMatchingListingUnits($bookedListingSubunits);
            }

            if (!$expectedPasscodes) {
                // the current integration device settings do not support passcodes
                $tagsCount--;

                // replace tag within the template string
                $tmpl = str_replace($profileTag, '', $tmpl);

                // go to the next profile-tag
                continue;
            }

            // obtain the expected passcodes for this booking
            $latestBookingPasscodes = [];
            $passcodesDevicesMap = [];

            // filter out the latest booking events that should contain a valid passcode
            foreach (array_reverse($previousPasscodes) as $previousData) {
                // ensure we only have array values
                $previousData = (array) json_decode(json_encode($previousData), true);

                // get the passcode value generated
                $passcodeValue = ($previousData['passcode'] ?? '') ?: $integration->getPasscodeFromHistoryResult((array) ($previousData['props'] ?? []));

                if ($passcodeValue) {
                    // push booking passcode
                    $latestBookingPasscodes[] = $passcodeValue;

                    // set passcode-device map
                    $passcodesDevicesMap[$passcodeValue] = $integration->getDeviceById($previousData['device'])->getName();
                }

                if (count($latestBookingPasscodes) === $expectedPasscodes) {
                    // terminate the process to avoid including old passcodes that may have been deleted
                    break;
                }
            }

            if (!$latestBookingPasscodes) {
                // no passcodes were found
                $tagsCount--;

                // replace tag within the template string
                $tmpl = str_replace($profileTag, '', $tmpl);

                // go to the next profile-tag
                continue;
            }

            // ensure we've only got unique passcodes
            $latestBookingPasscodes = array_values(array_unique($latestBookingPasscodes));

            if (count($latestBookingPasscodes) > 1) {
                // map the device name along with the device passcode when multiple passcodes involved
                $latestBookingPasscodes = array_map(function($passcode) use ($passcodesDevicesMap) {
                    return sprintf('%s: %s', ($passcodesDevicesMap[$passcode] ?? ''), $passcode);
                }, $latestBookingPasscodes);
            }

            // we've got one or more passcodes to set as a special tag replacement
            $tmpl = str_replace($profileTag, implode(', ', $latestBookingPasscodes), $tmpl);
        }

        return $tagsCount;
    }

    /**
     * Attempts to return a list of associative arrays that include the active passcode string
     * value and the device information based on what was generated for a specific booking.
     * 
     * @param   VBOBookingRegistry  $registry   The booking registry.
     * 
     * @return  array                           List of passcode and device name associative
     *                                          arrays (usually one), or empty array.
     */
    public function getBookingDevicePasscodes(VBOBookingRegistry $registry)
    {
        // find the passcode data that were previously created for this booking by any integration
        $previousPasscodes = VikBooking::getBookingHistoryInstance($registry->getID())
            ->getEventsWithData(['ND', 'MD'], function($data) {
                // cast history data payload to an array
                $data = (array) $data;

                // ensure the passcode was generated by/for a valid provider, profile and device
                return !empty($data['provider']) &&
                    !empty($data['profile']) &&
                    !empty($data['device']) &&
                    (!empty($data['passcode']) || !empty($data['props']));
            });

        if (!$previousPasscodes) {
            // nothing was ever created for this booking
            return [];
        }

        // get the unique list of booked listing ids and subunits
        $bookedListingIds = $registry->getBookedListingIds();
        $bookedListingSubunits = $registry->getBookedListingSubunits();

        // build the pool of devices and passcodes data
        $devicePasscodesSignatures = [];
        $devicePasscodesPool = [];

        // iterate over the latest booking events that generated a passcode
        foreach (array_reverse($previousPasscodes) as $previousData) {
            // ensure we only have array values
            $previousData = (array) json_decode(json_encode($previousData), true);

            // build passcode signature with provider and profile identifiers
            $passcodeSignature = sprintf('%s-%d', (string) $previousData['provider'], (int) $previousData['profile']);

            // access the provider integration
            $integration = $this->getIntegrationProvider((string) $previousData['provider']);
            if (!$integration) {
                // unknown provider
                continue;
            }

            // inject profile record within the integration provider
            $integration->setProfileRecord($this->loadIntegrationRecord((int) $previousData['profile']));

            // ensure this device still exists within the integration provider
            if (!$integration->deviceExists((string) $previousData['device'])) {
                // unknown device
                continue;
            }

            // count the number of expected passcodes that were generated for this booking by the current integration provider
            $expectedPasscodes = 0;
            foreach ($integration->getDevices() as $device) {
                // count, if any, how many listings (with subunits) are compatible with the current device
                $expectedPasscodes += $device->countMatchingListingUnits($bookedListingSubunits);
            }

            // ensure we are not getting too many passcodes for this provider, which may have been cancelled
            if (($devicePasscodesSignatures[$passcodeSignature] ?? 0) >= $expectedPasscodes) {
                // fetch no more passcodes for this provider and profile
                continue;
            }

            // get the device name and listings involved with the reservation
            $deviceName = '';
            $deviceListings = [];
            try {
                $device = $integration->getDeviceById((string) $previousData['device']);
                $deviceName = $device->getName();
                $deviceListings = array_intersect($bookedListingIds, $device->getConnectedListings());
                $deviceListings = array_map(function($listingId) {
                    return VikBooking::getRoomInfo($listingId, ['name'], true)['name'] ?? $listingId;
                }, $deviceListings);
            } catch (Exception $e) {
                // fallback to device ID
                $deviceName = (string) $previousData['device'];
            }

            // get the passcode value generated
            $passcodeValue = ($previousData['passcode'] ?? '') ?: $integration->getPasscodeFromHistoryResult((array) ($previousData['props'] ?? []));
            if (!$passcodeValue) {
                // unexpected situation
                continue;
            }

            // push passcode and device information
            $devicePasscodesPool[] = [
                'deviceName' => $deviceName,
                'deviceId'   => $previousData['device'],
                'passcode'   => $passcodeValue,
                'listings'   => $deviceListings,
            ];

            // increase provider-profile counter
            $devicePasscodesSignatures[$passcodeSignature] = ($devicePasscodesSignatures[$passcodeSignature] ?? 0) + 1;
        }

        return $devicePasscodesPool;
    }

    /**
     * Attempts to handle the command for unlocking one or more devices to which the rooms booked are assigned.
     * The method will NOT validate the booking stay dates, such controls should be made prior to calling it.
     * 
     * @param   VBOBookingRegistry  $registry   The booking registry.
     * @param   ?array              $options    Associative list of request options.
     * 
     * @return  array                           List of devices unlock results, if any.
     * 
     * @throws  Exception
     */
    public function handleBookingDeviceUnlock(VBOBookingRegistry $registry, ?array $options = null)
    {
        // get list of booked listing ids and subunits
        $bookedListingSubunits = $registry->getBookedListingSubunits();

        // list of provider integrations capable of unlocking a device
        $integrations = array_filter($this->loadActiveIntegrations(), function($integration) {
            return $integration->canUnlockDevices();
        });

        if (!$integrations) {
            // raise an error
            throw new Exception('Unable to unlock remote devices.', 501);
        }

        // build the device unlock results
        $unlockResults = [];

        // flag to indicate that the requested device was not found
        $exactDeviceFound = null;

        // iterate all provider integrations
        foreach ($integrations as $integration) {
            // iterate all integration devices
            foreach ($integration->getDevices() as $device) {
                // get the listing-subunit pairs compatible with the current device
                $deviceListingUnits = $device->intersectListingUnits($bookedListingSubunits);

                if (!$deviceListingUnits) {
                    // the device is not connected to any of the booked listings
                    continue;
                }

                if (($options['device_id'] ?? null) && trim((string) $options['device_id']) != trim($device->getID())) {
                    // this is not the requested device ID to unlock
                    $exactDeviceFound = false;
                    continue;
                }

                if (($options['device_name'] ?? null)) {
                    $seek_name = trim((string) $options['device_name']);
                    if (stripos($device->getName(), $seek_name) === false && stripos($seek_name, $device->getName()) === false) {
                        // this is not the requested device name to unlock
                        $exactDeviceFound = false;
                        continue;
                    }
                }

                try {
                    // unlock the device
                    $result = $integration->handleUnlockDevice($device);

                    if (!$result) {
                        // throw an error
                        throw new Exception('The device cannot be unlocked.', 501);
                    }

                    // push the successful unlock result
                    $unlockResults[] = [
                        'deviceName' => $device->getName(),
                        'deviceId'   => $device->getID(),
                        'unlocked'   => true,
                        'message'    => (string) $result,
                    ];
                } catch (Exception $e) {
                    // push the faulty unlock result
                    $unlockResults[] = [
                        'deviceName' => $device->getName(),
                        'deviceId'   => $device->getID(),
                        'unlocked'   => false,
                        'message'    => $e->getMessage() ?: 'Unlocking the device failed.',
                    ];
                }
            }
        }

        if (!$unlockResults) {
            // raise an error
            if ($exactDeviceFound === false) {
                throw new Exception('Could not find the requested device to unlock.', 404);
            }
            throw new Exception('None of the booked listings has got a device/door to unlock/open. No compatible devices were found.', 400);
        }

        return $unlockResults;
    }

    /**
     * Takes care of cleaning the expired passcodes to free up memory on the device.
     * 
     * @param   ?array  $options    Optional associative list of processing options.
     * 
     * @return  int     Number of passcodes deleted.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function cleanExpiredPasscodes(?array $options = null)
    {
        // count total passcodes deleted
        $passcodesDeleted = 0;

        // list of configured provider integrations to delete expired passcodes
        $integrations = array_filter($this->loadActiveIntegrations(), function($integration) {
            return $integration->canCleanExpiredPasscodes();
        });

        if (!$integrations) {
            // do not proceed
            return $passcodesDeleted;
        }

        // calculate timestamp bounds, last week by default
        $tsBounds = [
            strtotime('00:00:00', strtotime(($options['date_from'] ?? date('Y-m-d', strtotime('-1 week'))))),
            strtotime('23:59:59', strtotime(($options['date_to'] ?? date('Y-m-d', strtotime('-1 day')))))
        ];

        // load the departed reservations in the last week
        $lastDepartures = $this->loadCheckedOutReservations($tsBounds);

        // map bookings with previously generated passcodes
        $lastDepartures = array_map(function($booking) {
            // set booking DAC passcodes
            $booking['_dac_passcodes_data'] = [];

            // check if any passcode was previously created
            $previousPasscodes = VikBooking::getBookingHistoryInstance($booking['id'])
                ->getEventsWithData(['ND', 'MD'], function($data) {
                    // cast history data payload to an array
                    $data = (array) $data;

                    // ensure the passcode was generated by/for a valid provider, profile and device
                    return !empty($data['provider']) &&
                        !empty($data['profile']) &&
                        !empty($data['device']) &&
                        (!empty($data['passcode']) || !empty($data['props']));
                });

            foreach (array_reverse((array) $previousPasscodes) as $previousData) {
                // ensure we only have array values
                $previousData = (array) json_decode(json_encode($previousData), true);

                // push booking passcode data
                $passcodeData = ($previousData['passcode'] ?? '') ?: (array) ($previousData['props'] ?? []);

                if ($passcodeData) {
                    // push booking passcode data
                    $booking['_dac_passcodes_data'][] = ($previousData['passcode'] ?? '') ?: (array) ($previousData['props'] ?? []);
                }
            }

            // returned the mapped booking array
            return $booking;
        }, $lastDepartures);

        // filter out bookings for which no passcodes were ever generated
        $lastDepartures = array_filter($lastDepartures, function($booking) {
            // ensure booking passcodes data is set
            return !empty($booking['_dac_passcodes_data']);
        });

        if (!$lastDepartures) {
            // nothing to delete at this time
            return $passcodesDeleted;
        }

        // build the readable date bounds
        $startInfo = getdate($tsBounds[0]);
        $endInfo = getdate($tsBounds[1]);
        $targetDtFrom = sprintf('%s %d', VikBooking::sayMonth($startInfo['mon'], true), $startInfo['mday']);
        $targetDtTo = sprintf('%s %d', VikBooking::sayMonth($endInfo['mon'], true), $endInfo['mday']);
        if ($startInfo['year'] != $endInfo['year']) {
            // append short year to both target dates
            $targetDtFrom .= ' ' . date('y', $tsBounds[0]);
            $targetDtTo .= ' ' . date('y', $tsBounds[1]);
        } else {
            // append full year to target end date
            $targetDtTo .= ' ' . $endInfo['year'];
        }

        // iterate departed reservations
        foreach ($lastDepartures as $booking) {
            // wrap the booking information into a registry
            $registry = VBOBookingRegistry::getInstance($booking);

            // get list of booked listing ids and subunits
            $bookedListingSubunits = $registry->getBookedListingSubunits();

            // scan all the eligible integration records
            foreach ($integrations as $integration) {
                // set DAC passcodes data within the booking registry
                $registry->setDACProperty($integration->getAlias(), 'passcodes_data', $booking['_dac_passcodes_data'] ?? []);

                // start integration counter
                $integrationDeletion = 0;

                // iterate all provider integration devices
                foreach ($integration->getDevices() as $device) {
                    // get the listing-subunit pairs compatible with the current device
                    $deviceListingUnits = $device->intersectListingUnits($bookedListingSubunits);

                    // iterate all listing units connected to the current device, if any
                    foreach ($deviceListingUnits as $listingIndex => $listingSubunitPair) {
                        // obtain listing ID and subunit number
                        list($listingId, $subunitId) = $listingSubunitPair;

                        // set current room index to identify a multi-room booking context
                        $registry->setCurrentRoomIndex($listingIndex);

                        // set current room number (1-based index) to identify an exact subunit for hotels inventory (if any)
                        $registry->setCurrentRoomNumber($subunitId);

                        try {
                            // attempt to delete a previously created passcode on this device for the current booking
                            if ($integration->cancelBookingDoorAccess($device, $listingId, $registry)) {
                                // increase global counter
                                $passcodesDeleted++;

                                // increase integration counter
                                $integrationDeletion++;
                            }
                        } catch (Exception $e) {
                            // do nothing
                        }
                    }
                }

                if ($integrationDeletion) {
                    // store an entry within the notifications center for the successful operation
                    VBOFactory::getNotificationCenter()
                        ->store([
                            [
                                'sender'  => 'dac',
                                'type'    => 'dac.EX.ok',
                                'title'   => sprintf('%s', (string) $integration->getProfileName()),
                                'summary' => JText::sprintf('VBO_EXP_PASSCODES_DEL_OK_RES', $integrationDeletion, $targetDtFrom, $targetDtTo),
                                'avatar'  => preg_match('/^http/', (string) $integration->getIcon()) ? $integration->getIcon() : null,
                            ],
                        ]);
                }
            }
        }

        return $passcodesDeleted;
    }

    /**
     * Watches the daily arrivals to notify on the first access through booking passcodes.
     * 
     * @param   ?array  $options    Optional associative list of watching options.
     * 
     * @return  int     Number of first access found.
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function watchFirstAccess(?array $options = null)
    {
        // count the first access found
        $firstAccessCount = 0;

        // list of configured provider integrations to watch first access
        $integrations = array_filter($this->loadActiveIntegrations(), function($integration) {
            return $integration->canWatchFirstAccess();
        });

        if (!$integrations) {
            // do not proceed
            return $firstAccessCount;
        }

        // load the arrivals for today
        $todayArrivals = $this->loadUpcomingReservations([
            strtotime('00:00:00', strtotime(($options['date_from'] ?? date('Y-m-d')))),
            strtotime('23:59:59', strtotime(($options['date_to'] ?? date('Y-m-d'))))
        ]);

        // map today bookings with previously generated passcodes
        $todayArrivals = array_map(function($booking) {
            // set booking DAC passcodes
            $booking['_dac_passcodes_data'] = [];

            // check if any passcode was previously created
            $previousPasscodes = VikBooking::getBookingHistoryInstance($booking['id'])
                ->getEventsWithData(['ND', 'MD'], function($data) {
                    // cast history data payload to an array
                    $data = (array) $data;

                    // ensure the passcode was generated by/for a valid provider, profile and device
                    return !empty($data['provider']) &&
                        !empty($data['profile']) &&
                        !empty($data['device']) &&
                        (!empty($data['passcode']) || !empty($data['props']));
                });

            foreach (array_reverse((array) $previousPasscodes) as $previousData) {
                // ensure we only have array values
                $previousData = (array) json_decode(json_encode($previousData), true);

                // push booking passcode data
                $passcodeData = ($previousData['passcode'] ?? '') ?: (array) ($previousData['props'] ?? []);

                if ($passcodeData) {
                    // push booking passcode data
                    $booking['_dac_passcodes_data'][] = ($previousData['passcode'] ?? '') ?: (array) ($previousData['props'] ?? []);
                }
            }

            // returned the mapped booking array
            return $booking;
        }, $todayArrivals);

        // filter out today bookings that should not be watched or that were watched already
        $todayArrivals = array_filter($todayArrivals, function($booking) {
            // ensure booking passcodes data is set and no history events are available for "first access"
            return !empty($booking['_dac_passcodes_data']) && !VikBooking::getBookingHistoryInstance($booking['id'])->hasEvent('FA');
        });

        if (!$todayArrivals) {
            // nothing to watch for today at this time
            return $firstAccessCount;
        }

        // iterate bookings arriving today
        foreach ($todayArrivals as $booking) {
            // wrap the booking information into a registry
            $registry = VBOBookingRegistry::getInstance($booking);

            // get list of booked listing ids and subunits
            $bookedListingSubunits = $registry->getBookedListingSubunits();

            // scan all the eligible integration records
            foreach ($integrations as $integration) {
                // set DAC passcodes data within the booking registry
                $registry->setDACProperty($integration->getAlias(), 'passcodes_data', $booking['_dac_passcodes_data'] ?? []);

                // iterate all provider integration devices
                foreach ($integration->getDevices() as $device) {
                    // get the listing-subunit pairs compatible with the current device
                    $deviceListingUnits = $device->intersectListingUnits($bookedListingSubunits);

                    // iterate all listing units connected to the current device, if any
                    foreach ($deviceListingUnits as $listingIndex => $listingSubunitPair) {
                        // obtain listing ID and subunit number
                        list($listingId, $subunitId) = $listingSubunitPair;

                        // set current room index to identify a multi-room booking context
                        $registry->setCurrentRoomIndex($listingIndex);

                        // set current room number (1-based index) to identify an exact subunit for hotels inventory (if any)
                        $registry->setCurrentRoomNumber($subunitId);

                        try {
                            // attempt to find the first access on this device for the current booking
                            $result = $integration->detectFirstAccess($device, $listingId, $registry);

                            // parse the device capability execution result
                            if ($result) {
                                // increase counter
                                $firstAccessCount++;

                                // store booking history record
                                VikBooking::getBookingHistoryInstance($registry->getID())
                                    ->setBookingData($registry->getData(), $registry->getRooms())
                                    ->setExtraData([
                                        'provider' => $integration->getProfileProvider(),
                                        'profile'  => $integration->getProfileID(),
                                        'device'   => $device->getID(),
                                    ])
                                    ->store('FA', sprintf('%s - %s: %s', (string) $integration->getProfileName(), (string) $device->getName(), (string) $result));

                                // store an entry within the notifications center for the successful operation
                                VBOFactory::getNotificationCenter()
                                    ->store([
                                        [
                                            'sender'  => 'dac',
                                            'type'    => 'dac.FA.ok',
                                            'title'   => sprintf('%s - %s', (string) $integration->getProfileName(), (string) $device->getName()),
                                            'summary' => sprintf('%s: %s', JText::translate('VBOBOOKHISTORYTFA'), strip_tags((string) $result)),
                                            'idorder' => $registry->getID(),
                                            'avatar'  => preg_match('/^http/', (string) $integration->getIcon()) ? $integration->getIcon() : null,
                                        ],
                                    ]);
                            }
                        } catch (Exception $e) {
                            // do nothing
                        }
                    }
                }
            }
        }

        return $firstAccessCount;
    }

    /**
     * Handles the upcoming check-ins by triggering the operations involving provider devices.
     * This method is constantly executed as a cron-schedule by the CMS platform itself.
     * Will invoke only the provider integrations that generate passcodes "before the check-in".
     * 
     * @return  bool    True if some door-access-control actions were performed, false otherwise.
     */
    public function handleUpcomingArrivals()
    {
        // count the door access handling actions performed
        $doorAccessActions = 0;

        // scan the eligible integration records for generating door-access passcodes at the time of booking
        foreach ($this->loadGeneratingIntegrations('checkin') as $record) {
            // get the integration provider
            $integration = $this->getIntegrationProvider($record['provider_alias']);
            if (!$integration) {
                // unknown integration provider
                continue;
            }

            // inject profile record within the integration provider
            $integration->setProfileRecord($record);

            // load the upcoming check-ins within the configured profile period, if any
            foreach ($this->loadUpcomingReservations($integration->getNextGenerationPeriodTimestamps()) as $booking) {
                // wrap the booking information into a registry
                $registry = VBOBookingRegistry::getInstance($booking);

                // ensure this booking was never processed before
                if ($integration->getBookingAccessProcessed($registry->getID())) {
                    // skip this booking as it was already processed
                    continue;
                }

                // flag the booking as processed
                $integration->setBookingAccessProcessed($registry->getID());

                // get list of booked listing ids and subunits
                $bookedListingSubunits = $registry->getBookedListingSubunits();

                // iterate all provider integration devices
                foreach ($integration->getDevices() as $device) {
                    // get the listing-subunit pairs compatible with the current device
                    $deviceListingUnits = $device->intersectListingUnits($bookedListingSubunits);

                    // iterate all listing units connected to the current device, if any
                    foreach ($deviceListingUnits as $listingIndex => $listingSubunitPair) {
                        // obtain listing ID and subunit number
                        list($listingId, $subunitId) = $listingSubunitPair;

                        // set current room index to identify a multi-room booking context
                        $registry->setCurrentRoomIndex($listingIndex);

                        // set current room number (1-based index) to identify an exact subunit for hotels inventory (if any)
                        $registry->setCurrentRoomNumber($subunitId);

                        // call door access control on provider record for the current device, listing and booking
                        try {
                            // set proper history/notification type first
                            $historyType = 'ND';

                            // new booking
                            $result = $integration->createBookingDoorAccess($device, $listingId, $registry);

                            // parse the device capability execution result
                            if ($result) {
                                // increase counter
                                $doorAccessActions++;

                                // store booking history record
                                VikBooking::getBookingHistoryInstance($registry->getID())
                                    ->setBookingData($registry->getData(), $registry->getRooms())
                                    ->setExtraData([
                                        'provider' => $integration->getProfileProvider(),
                                        'profile'  => $integration->getProfileID(),
                                        'device'   => $device->getID(),
                                        'passcode' => $result->getPasscode(),
                                        'props'    => $result->getProperties(),
                                    ])
                                    ->store($historyType, sprintf('%s - %s: %s', (string) $integration->getProfileName(), (string) $device->getName(), (string) $result->getPasscode()));

                                // store an entry within the notifications center for the successful operation
                                try {
                                    VBOFactory::getNotificationCenter()
                                        ->store([
                                            [
                                                'sender'  => 'dac',
                                                'type'    => sprintf('dac.%s.ok', $historyType),
                                                'title'   => sprintf('%s - %s', (string) $integration->getProfileName(), (string) $device->getName()),
                                                'summary' => strip_tags((string) $result),
                                                'idorder' => $registry->getID(),
                                                'avatar'  => preg_match('/^http/', (string) $integration->getIcon()) ? $integration->getIcon() : null,
                                                'label'   => $integration->getName(),
                                                'widget'  => 'door_access_control',
                                                'widget_options' => [
                                                    'provider' => $integration->getProfileProvider(),
                                                    'profile'  => $integration->getProfileID(),
                                                    'device'   => $device->getID(),
                                                ],
                                            ],
                                        ]);
                                } catch (Exception $e) {
                                    // do nothing
                                }
                            }
                        } catch (Exception $e) {
                            // check if the error exception contains retry data
                            $retryData = [];
                            if ($e instanceof VBODooraccessException) {
                                // obtain the retry information
                                $retryData = [
                                    'callback' => $e->getRetryCallback(),
                                    'options'  => $e->getRetryData(),
                                ];
                            }

                            // store an entry within the notifications center for the failed operation
                            try {
                                VBOFactory::getNotificationCenter()
                                    ->store([
                                        [
                                            'sender'  => 'dac',
                                            'type'    => sprintf('dac.%s.nok', $historyType),
                                            'title'   => sprintf('%s - %s', (string) $integration->getProfileName(), (string) $device->getName()),
                                            'summary' => $e->getMessage() ?: 'An error occurred.',
                                            'idorder' => $registry->getID(),
                                            'avatar'  => preg_match('/^http/', (string) $integration->getIcon()) ? $integration->getIcon() : null,
                                            'label'   => JText::translate('VBO_TAKE_ACTION'),
                                            'widget'  => 'door_access_control',
                                            'widget_options' => [
                                                'provider'   => $integration->getProfileProvider(),
                                                'profile'    => $integration->getProfileID(),
                                                'device'     => $device->getID(),
                                                'retry_data' => $retryData,
                                            ],
                                        ],
                                    ]);
                            } catch (Exception $e) {
                                // do nothing
                            }
                        }
                    }
                }
            }

            // update integration record
            $this->saveIntegrationRecord($integration, ['data' => $integration->getData()]);
        }

        return (bool) $doorAccessActions;
    }

    /**
     * Triggers the operations involving provider devices during a new booking confirmation event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * 
     * @return  bool
     */
    public function processBookingConfirmation(array $booking, array $booking_rooms = [])
    {
        // wrap the booking information into a registry
        $registry = VBOBookingRegistry::getInstance($booking, $booking_rooms);

        if ($registry->isClosure() || $registry->isOverbooking() || !$registry->isConfirmed()) {
            // do nothing when we're not dealing with a real confirmed and accepted reservation
            return false;
        }

        // new booking
        return $this->handleBookingEvent('confirmation', $registry);
    }

    /**
     * Triggers the operations involving provider devices during a booking modification event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * @param   array   $prev_booking   The previous booking record.
     * 
     * @return  bool
     */
    public function processBookingModification(array $booking, array $booking_rooms = [], array $prev_booking = [])
    {
        // wrap the booking information into a registry
        $registry = VBOBookingRegistry::getInstance($booking, $booking_rooms, $prev_booking);

        if ($registry->isClosure() || $registry->isOverbooking() || !$registry->isConfirmed()) {
            // do nothing when we're not dealing with a real confirmed and accepted reservation
            return false;
        }

        // detect changes from previous to current booking even at room-level (subunits)
        if (!$registry->detectAlterations($roomLevel = true)) {
            // do nothing when no significant changes were made to the booking
            return false;
        }

        // modified booking
        return $this->handleBookingEvent('modification', $registry);
    }

    /**
     * Triggers the operations involving provider devices during a booking cancellation event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * 
     * @return  bool
     */
    public function processBookingCancellation(array $booking, array $booking_rooms = [])
    {
        // wrap the booking information into a registry
        $registry = VBOBookingRegistry::getInstance($booking, $booking_rooms);

        if ($registry->isClosure() || (!$registry->isCancelled() && !$registry->getPrevious())) {
            // do nothing when we're not dealing with a real booking cancellation or modification
            return false;
        }

        // cancelled booking
        return $this->handleBookingEvent('cancellation', $registry);
    }

    /**
     * Triggers the operations involving provider devices during a pre-checkin completed event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * 
     * @return  bool
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function processPrecheckinCompleted(array $booking, array $booking_rooms = [])
    {
        // wrap the booking information into a registry
        $registry = VBOBookingRegistry::getInstance($booking, $booking_rooms);

        if ($registry->isClosure() || $registry->isOverbooking() || !$registry->isConfirmed()) {
            // do nothing when we're not dealing with a real confirmed and accepted reservation
            return false;
        }

        // ensure this is the first time the pre-checkin details are submitted, and not simply updated
        $totalPrecheckins = VikBooking::getBookingHistoryInstance($registry->getID())->getEventsWithData(['PC']);
        if ($totalPrecheckins && count($totalPrecheckins) > 1) {
            // this booking already got the pre-checkin information submitted once
            return false;
        }

        // handle the pre-checkin completed event
        return $this->handleBookingEvent('precheckin', $registry);
    }

    /**
     * Spawns the callback by reaching the OAuth2 authorization link (callback URL).
     * 
     * @param   ?array   $data  Optional spawn data to parse.
     * 
     * @return  void
     * 
     * @throws  Exception
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function spawnOAuthCallback(?array $data = null)
    {
        $app = JFactory::getApplication();

        // gather request or data variables to load the proper integration
        $provider  = ($data['provider'] ?? '') ?: $app->input->getString('provider', '');
        $profileId = ($data['profile'] ?? 0) ?: $app->input->getUInt('profile', 0);

        if (!$provider) {
            throw new Exception('Missing DAC integration provider to spawn.', 400);
        }

        if (!$profileId) {
            throw new Exception('Missing DAC integration profile to spawn.', 400);
        }

        // get the requested integration provider
        $integration = $this->getIntegrationProvider($provider);

        if (!$integration) {
            throw new Exception('Invalid door access control provider identifier.', 404);
        }

        // load the requested integration profile
        $profile = $this->loadIntegrationRecord((int) $profileId);

        if (!$profile) {
            throw new Exception('Invalid door access control provider profile.', 404);
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        // let the integration spawn the OAuth authorization callback to perform its actions
        $integration->spawnOAuthCallback($data);
    }

    /**
     * Spawns the Webhook endpoint URL callback.
     * 
     * @param   ?array   $data  Optional spawn data to parse.
     * 
     * @return  void
     * 
     * @throws  Exception
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function spawnWebhookCallback(?array $data = null)
    {
        $app = JFactory::getApplication();

        // gather request or data variables to load the proper integration
        $provider  = ($data['provider'] ?? '') ?: $app->input->getString('provider', '');
        $profileId = ($data['profile'] ?? 0) ?: $app->input->getUInt('profile', 0);

        if (!$provider) {
            throw new Exception('Missing DAC integration provider to spawn.', 400);
        }

        if (!$profileId) {
            throw new Exception('Missing DAC integration profile to spawn.', 400);
        }

        // get the requested integration provider
        $integration = $this->getIntegrationProvider($provider);

        if (!$integration) {
            throw new Exception('Invalid door access control provider identifier.', 404);
        }

        // load the requested integration profile
        $profile = $this->loadIntegrationRecord((int) $profileId);

        if (!$profile) {
            throw new Exception('Invalid door access control provider profile.', 404);
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        // let the integration spawn the Webhook endpoint URL callback to perform its actions
        $integration->spawnWebhookCallback($data);
    }

    /**
     * Handles a booking event by triggering the operations involving provider devices.
     * Will invoke only the provider integrations that can generate passcodes either
     * "at the time of booking" or "upon pre-checkin completed".
     * 
     * @param   string              $type       The booking event type (confirmation, modification, cancellation, precheckin).
     * @param   VBOBookingRegistry  $registry   The booking registry containing all room related details.
     * 
     * @return  bool                            True if some door-access-control actions were performed, false otherwise.
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP) added support to pre-checkin event.
     */
    protected function handleBookingEvent(string $type, VBOBookingRegistry $registry)
    {
        // list of supported booking event types
        $validTypes = [
            'confirmation',
            'modification',
            'cancellation',
            'precheckin',
        ];

        if (!in_array($type, $validTypes)) {
            // unsupported booking event
            return false;
        }

        // determine the allowed generation type(s) for the integrations
        if ($type === 'confirmation') {
            // only integrations that generate passcodes "at the time of booking" should run
            $generationTypes = ['booking'];
        } elseif ($type === 'precheckin') {
            // only integrations that generate passcodes "upon pre-checkin completed" should run
            $generationTypes = ['precheckin'];
        } else {
            // both "at the time of booking" and "upon pre-checkin completed" integrations should run
            $generationTypes = ['booking', 'precheckin'];
        }

        // count the door access handling actions performed
        $doorAccessActions = 0;

        // get list of booked listing ids and subunits
        $bookedListingSubunits = $registry->getBookedListingSubunits();

        // check if the booking was only modified at room-level (subunits)
        $modifiedRoomLevelOnly = false;
        if ($type === 'modification' && !$registry->detectAlterations($roomLevel = false)) {
            // turn flag on, because no booking-level changes were detected, only room-level changes (subunits)
            $modifiedRoomLevelOnly = true;
        }

        // scan the eligible integrations for generating passcodes at the time of booking or pre-checkin
        foreach ($this->loadGeneratingIntegrations($generationTypes) as $record) {
            // get the integration provider
            $integration = $this->getIntegrationProvider($record['provider_alias']);
            if (!$integration) {
                // unknown integration provider
                continue;
            }

            // inject profile record within the integration provider
            $integration->setProfileRecord($record);

            // ensure a booking modification event will not generate a new passcode too early
            if (($record['gentype'] ?? '') === 'precheckin' && in_array($type, ['modification', 'cancellation']) && !$registry->hasPreCheckedIn()) {
                // avoid premature actions because the guest has not gone through pre-checkin yet
                continue;
            }

            // iterate all provider integration devices
            foreach ($integration->getDevices() as $device) {
                if ($modifiedRoomLevelOnly && !$device->getConnectedSubunits()) {
                    // prevent useless passcode modifications in case of room-level only changes and no subunits mapped
                    continue;
                }

                // get the listing-subunit pairs compatible with the current device
                $deviceListingUnits = $device->intersectListingUnits($bookedListingSubunits);

                // iterate all listing units connected to the current device, if any
                foreach ($deviceListingUnits as $listingIndex => $listingSubunitPair) {
                    // obtain listing ID and subunit number
                    list($listingId, $subunitId) = $listingSubunitPair;

                    // set current room index to identify a multi-room booking context
                    $registry->setCurrentRoomIndex($listingIndex);

                    // set current room number (1-based index) to identify an exact subunit for hotels inventory (if any)
                    $registry->setCurrentRoomNumber($subunitId);

                    // call door access control on provider record for the current device, listing and booking
                    try {
                        if ($type === 'modification') {
                            // set proper history/notification type first
                            $historyType = 'MD';
                            // booking modified
                            $result = $integration->modifyBookingDoorAccess($device, $listingId, $registry);
                        } elseif ($type === 'cancellation') {
                            // set proper history/notification type first
                            $historyType = 'CD';
                            // booking cancelled
                            $result = $integration->cancelBookingDoorAccess($device, $listingId, $registry);
                        } else {
                            // new booking or pre-checkin completed event
                            // set proper history/notification type first
                            $historyType = 'ND';
                            // new booking
                            $result = $integration->createBookingDoorAccess($device, $listingId, $registry);
                        }

                        // parse the device capability execution result
                        if ($result) {
                            // increase counter
                            $doorAccessActions++;

                            // store booking history record
                            VikBooking::getBookingHistoryInstance($registry->getID())
                                ->setBookingData($registry->getData(), $registry->getRooms())
                                ->setExtraData([
                                    'provider' => $integration->getProfileProvider(),
                                    'profile'  => $integration->getProfileID(),
                                    'device'   => $device->getID(),
                                    'passcode' => $result->getPasscode(),
                                    'props'    => $result->getProperties(),
                                ])
                                ->store($historyType, sprintf('%s - %s: %s', (string) $integration->getProfileName(), (string) $device->getName(), (string) $result->getPasscode()));

                            // store an entry within the notifications center for the successful operation
                            try {
                                VBOFactory::getNotificationCenter()
                                    ->store([
                                        [
                                            'sender'  => 'dac',
                                            'type'    => sprintf('dac.%s.ok', $historyType),
                                            'title'   => sprintf('%s - %s', (string) $integration->getProfileName(), (string) $device->getName()),
                                            'summary' => strip_tags((string) $result),
                                            'idorder' => $registry->getID(),
                                            'avatar'  => preg_match('/^http/', (string) $integration->getIcon()) ? $integration->getIcon() : null,
                                            'label'   => $integration->getName(),
                                            'widget'  => 'door_access_control',
                                            'widget_options' => [
                                                'provider' => $integration->getProfileProvider(),
                                                'profile'  => $integration->getProfileID(),
                                                'device'   => $device->getID(),
                                            ],
                                        ],
                                    ]);
                            } catch (Exception $e) {
                                // do nothing
                            }
                        }
                    } catch (Exception $e) {
                        // check if the error exception contains retry data
                        $retryData = [];
                        if ($e instanceof VBODooraccessException) {
                            // obtain the retry information
                            $retryData = [
                                'callback' => $e->getRetryCallback(),
                                'options'  => $e->getRetryData(),
                            ];
                        }

                        // store an entry within the notifications center for the failed operation
                        try {
                            VBOFactory::getNotificationCenter()
                                ->store([
                                    [
                                        'sender'  => 'dac',
                                        'type'    => sprintf('dac.%s.nok', $historyType),
                                        'title'   => sprintf('%s - %s', (string) $integration->getProfileName(), (string) $device->getName()),
                                        'summary' => $e->getMessage() ?: 'An error occurred.',
                                        'idorder' => $registry->getID(),
                                        'avatar'  => preg_match('/^http/', (string) $integration->getIcon()) ? $integration->getIcon() : null,
                                        'label'   => JText::translate('VBO_TAKE_ACTION'),
                                        'widget'  => 'door_access_control',
                                        'widget_options' => [
                                            'provider'   => $integration->getProfileProvider(),
                                            'profile'    => $integration->getProfileID(),
                                            'device'     => $device->getID(),
                                            'retry_data' => $retryData,
                                        ],
                                    ],
                                ]);
                        } catch (Exception $e) {
                            // do nothing
                        }
                    }
                }
            }
        }

        return (bool) $doorAccessActions;
    }

    /**
     * Loads the upcoming reservations within the given check-in timestamp intervals.
     * 
     * @param   array   $intervals  List of two timestamps for the check-in bounds.
     * 
     * @return  array               List of eligible reservation records, if any.
     */
    protected function loadUpcomingReservations(array $intervals)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_orders'))
                ->where($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
                ->where($dbo->qn('closure') . ' = 0')
                ->where('(' . $dbo->qn('checkin') . ' BETWEEN ' . ((int) ($intervals[0] ?? time())) . ' AND ' . ((int) ($intervals[1] ?? strtotime('+1 hour'))) . ')')
        );

        return $dbo->loadAssocList();
    }

    /**
     * Loads the checked-out reservations within the given check-in timestamp intervals.
     * 
     * @param   array   $intervals  List of two timestamps for the check-out bounds.
     * 
     * @return  array               List of eligible reservation records, if any.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    protected function loadCheckedOutReservations(array $intervals)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_orders'))
                ->where($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
                ->where($dbo->qn('closure') . ' = 0')
                ->where('(' . $dbo->qn('checkout') . ' BETWEEN ' . ((int) ($intervals[0] ?? strtotime('-1 week', strtotime('00:00:00')))) . ' AND ' . ((int) ($intervals[1] ?? strtotime('-1 day', strtotime('23:59:59')))) . ')')
        );

        return $dbo->loadAssocList();
    }

    /**
     * Decodes and unserializes the proper record columns.
     * 
     * @param   array   $record     The raw integration database record.
     * 
     * @return  array               The decoded and unserialized record columns.
     */
    protected function decodeIntegrationRecord(array $record)
    {
        if (!empty($record['settings']) && is_scalar($record['settings'])) {
            $record['settings'] = (array) json_decode($record['settings'], true);
        }

        if (!empty($record['devices']) && is_scalar($record['devices'])) {
            $record['devices'] = (array) unserialize($record['devices']);
        }

        if (!empty($record['data']) && is_scalar($record['data'])) {
            $record['data'] = (array) json_decode($record['data'], true);
        }

        return $record;
    }

    /**
     * Loads the available door access integrations.
     * 
     * @return  void
     */
    protected function loadIntegrations()
    {
        // access the platform dispatcher
        $dispatcher = VBOFactory::getPlatform()->getDispatcher();

        // integrations path and files
        $integrations_base   = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'provider' . DIRECTORY_SEPARATOR;
        $integration_files   = glob($integrations_base . '*.php');
        $integrations_banned = [];

        /**
         * Trigger event to let other plugins register additional integrations.
         *
         * @return  array   A list of supported integrations.
         */
        $list = $dispatcher->filter('onLoadDoorAccessIntegrations');

        foreach ($list as $chunk) {
            // merge default integration files with the returned ones
            $integration_files = array_merge($integration_files, (array) $chunk);
        }

        /**
         * Trigger event to let other plugins unregister specific integrations.
         *
         * @return  array   A list of integration identifiers (aliases) to unload.
         */
        $unloaded = $dispatcher->filter('onUnloadDoorAccessIntegrations');

        foreach ($unloaded as $chunk) {
            // merge all the the returned ones
            $integrations_banned = array_merge($integrations_banned, (array) $chunk);
        }

        // scan the integration files and register the installed integrations
        foreach ($integration_files as $integration_file) {
            try {
                // require the file if it exists
                if (is_file($integration_file)) {
                    require_once($integration_file);
                }

                // integration identifier (alias)
                $integration_alias = basename($integration_file, '.php');

                // check if the integration was unloaded
                if (in_array($integration_alias, $integrations_banned)) {
                    continue;
                }

                // build integration class name
                $classname  = 'VBODooraccessProvider' . str_replace(' ', '', ucwords(str_replace('_', ' ', $integration_alias)));

                if (class_exists($classname)) {
                    // instantiate integration object
                    $integration = new $classname;

                    // push the installed integration
                    $this->integrations[] = $integration;
                }
            } catch (Throwable $e) {
                // do nothing but skip the current integration
            }
        }
    }
}
