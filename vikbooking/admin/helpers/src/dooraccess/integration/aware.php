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
 * Door Access integration abstract class of any provider.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 */
abstract class VBODooraccessIntegrationAware
{
    /**
     * @var  array
     */
    protected $record = [];

    /**
     * Proxy to construct the door access integration object.
     * 
     * @return  VBODooraccessIntegrationAware
     */
    public static function getInstance()
    {
        return new static;
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {}

    /**
     * Returns the integration alias identifier.
     * 
     * @return  string
     */
    public function getAlias()
    {
        return preg_replace('/^VBODooraccessProvider/i', '', strtolower(get_class($this)));
    }

    /**
     * Returns the integration name.
     * 
     * @return  string
     */
    abstract public function getName();

    /**
     * Returns the integration short name.
     * 
     * @return  string
     */
    public function getShortName()
    {
        // return the provider integration name by default
        return $this->getName();
    }

    /**
     * Returns the integration icon, either an image URL or an HTML icon.
     * 
     * @return  string
     */
    public function getIcon()
    {
        return '';
    }

    /**
     * Tells if the integration can unlock devices for a booking.
     * 
     * @return  bool
     */
    public function canUnlockDevices()
    {
        // providers should eventually override this method
        return false;
    }

    /**
     * Tells if the integration can watch for device passcode first access.
     * 
     * @return  bool
     */
    public function canWatchFirstAccess()
    {
        // providers should eventually override this method
        return false;
    }

    /**
     * Returns the integration parameters.
     * 
     * @return  array
     */
    public function getParams()
    {
        return [];
    }

    /**
     * Returns the current integration record (profile) settings.
     * 
     * @return  array
     */
    public function getSettings()
    {
        return (array) ($this->record['settings'] ?? []);
    }

    /**
     * Returns the current integration record (profile) devices.
     * 
     * @return  VBODooraccessIntegrationDevice[]   List of device objects.
     */
    public function getDevices()
    {
        return (array) ($this->record['devices'] ?? []);
    }

    /**
     * Tells if a specific device ID exists in the current integration record.
     * 
     * @param   string  $deviceId   The device identifier value to find.
     * 
     * @return  bool
     */
    public function deviceExists(string $deviceId)
    {
        foreach ($this->getDevices() as $device) {
            if ($device->getID() == $deviceId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a specific device ID from the current integration record.
     * 
     * @param   string  $deviceId   The device identifier value to find.
     * 
     * @return  VBODooraccessIntegrationDevice
     * 
     * @throws  Exception
     */
    public function getDeviceById(string $deviceId)
    {
        foreach ($this->getDevices() as $device) {
            if ($device->getID() == $deviceId) {
                return $device;
            }
        }

        throw new Exception(sprintf('Could not access the requested device ID: %s.', $deviceId), 404);
    }

    /**
     * Returns the current integration record (profile) processed-data.
     * 
     * @return  array
     */
    public function getData()
    {
        return (array) ($this->record['data'] ?? []);
    }

    /**
     * Tells if a booking ID was previously processed by the current integration record.
     * 
     * @param   int     $bookingId  The booking ID to evaluate.
     * 
     * @return  bool
     */
    public function getBookingAccessProcessed(int $bookingId)
    {
        return in_array($bookingId, (array) ($this->record['data']['bookings'] ?? []));
    }

    /**
     * Pushes a booking ID as processed by the current integration record.
     * 
     * @param   int     $bookingId  The booking ID to set as processed.
     * 
     * @return  self
     */
    public function setBookingAccessProcessed(int $bookingId)
    {
        if (!isset($this->record['data']['bookings'])) {
            $this->record['data']['bookings'] = [];
        }

        // push booking
        $this->record['data']['bookings'][] = $bookingId;

        // ensure the pool is not too large
        if (count($this->record['data']['bookings']) > 2000) {
            // shorten the pool by cutting off the older (first) array elements
            $this->record['data']['bookings'] = array_slice($this->record['data']['bookings'], -2000);
        }

        return $this;
    }

    /**
     * Returns the current integration record (profile) ID.
     * 
     * @return  int
     */
    public function getProfileID()
    {
        return (int) ($this->record['id'] ?? 0);
    }

    /**
     * Returns the current integration record (profile) name.
     * 
     * @return  string
     */
    public function getProfileName()
    {
        return (string) ($this->record['name'] ?? '');
    }

    /**
     * Returns the current integration record (profile) provider alias.
     * 
     * @return  string
     */
    public function getProfileProvider()
    {
        return (string) ($this->record['provider_alias'] ?? '');
    }

    /**
     * Returns the current integration record (profile) generation type enum.
     * 
     * @return  string
     */
    public function getProfileGenerationType()
    {
        return (string) ($this->record['gentype'] ?? 'booking');
    }

    /**
     * Returns the current integration record (profile) generation period.
     * 
     * @return  string
     */
    public function getProfileGenerationPeriod()
    {
        return (string) ($this->record['genperiod'] ?? '');
    }

    /**
     * Returns an array with two timestamps for the configured generation period.
     * 
     * @return  array
     */
    public function getNextGenerationPeriodTimestamps()
    {
        $genperiod = $this->getProfileGenerationPeriod() ?: '0H';

        $genNumber = null;
        $genOperator = null;
        $allowedOperators = ['H', 'D'];

        if (preg_match('/^([0-9]+)(H|D)$/i', $genperiod, $matches)) {
            $genNumber = abs((int) $matches[1]);
            $genOperator = strtoupper((string) $matches[2]);
            if (!in_array($genOperator, $allowedOperators)) {
                $genOperator = null;
            }
        }

        if (is_null($genNumber) || is_null($genOperator)) {
            // fallback to default period
            $genNumber = 0;
            $genOperator = 'H';
        }

        // calculate the target timestamp
        $targetTs = $genNumber ? strtotime(sprintf('+%d %s', $genNumber, ($genOperator === 'D' ? 'days' : 'hours'))) : time();

        // return the targeted timestamp intervals within a one-hour range
        return [
            strtotime(date('Y-m-d H:00:00', $targetTs)),
            strtotime(date('Y-m-d H:59:59', $targetTs)),
        ];
    }

    /**
     * Gets the current integration profile record.
     * 
     * @return  array
     */
    public function getProfileRecord()
    {
        return $this->record;
    }

    /**
     * Sets the current integration profile record.
     * 
     * @param   array   $record     The integration profile record.
     * 
     * @return  self
     */
    public function setProfileRecord(array $record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Sets a property to the current integration profile record (i.e. "devices").
     * 
     * @param   string  $prop   The integration profile record property to set.
     * @param   mixed   $value  The value to set for the given property.
     * 
     * @return  self
     */
    public function setProfileRecordProp(string $prop, $value)
    {
        $this->record[$prop] = $value;

        return $this;
    }

    /**
     * Tells whether the integration profile record is available.
     * 
     * @return  bool
     */
    public function hasProfileRecord()
    {
        return !empty($this->record);
    }

    /**
     * Destroys (deletes) the current integration profile record.
     * 
     * @return  true
     * 
     * @throws  Exception
     */
    public function destroyProfileRecord()
    {
        if (!$this->hasProfileRecord() || !$this->getProfileID()) {
            throw new Exception('Missing integration profile record.', 500);
        }

        // delete profile record from database
        VBOFactory::getDoorAccessControl()->deleteIntegrationRecord($this);

        // reset internal profile record data
        $this->record = [];

        return true;
    }

    /**
     * Returns the special tag string identifying the current profile record.
     * 
     * @return  ?string     Special tag string to be used for contents, or null.
     */
    public function getProfileSpecialTag()
    {
        $profileId = $this->getProfileID();
        $profileName = $this->getProfileName();
        $providerName = $this->getShortName();

        if (empty($profileId) || empty($profileName) || empty($providerName)) {
            // invalid or incomplete integration profile record
            return null;
        }

        // build short/safe provider name
        $safeProviderName = preg_replace('/[^0-9a-z]/', '', strtolower($providerName));

        // build short/safe profile name
        $safeProfileName = preg_replace('/[^0-9a-z]/', '', strtolower($profileName));

        if (strlen($safeProviderName) > 8) {
            // shorten the string
            $safeProviderName = substr($safeProviderName, 0, 4) . '-' . substr($safeProviderName, -3, 3);
        }

        if (strlen($safeProfileName) > 8) {
            // shorten the string
            $safeProfileName = substr($safeProfileName, 0, 4) . '-' . substr($safeProfileName, -3, 3);
        }

        // build integration profile identifier short string
        $shortIdentifier = sprintf('%s_%s', $safeProviderName, $safeProfileName);

        return sprintf('{door_access: p%d_%s}', $profileId, $shortIdentifier);
    }

    /**
     * Detects the passcode that was set during a booking history event from a capability result.
     * 
     * @param   array   $resultProperties   The properties binded to the device capability result.
     * 
     * @return  ?string                     Passcode string value if found, or null.
     */
    public function getPasscodeFromHistoryResult(array $resultProperties)
    {
        // providers should implement this method according to what properties they bind with cap result objects
        return null;
    }

    /**
     * Default implementation for letting a provider create a door access upon a new booking event.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The provider integration device.
     * @param   int                             $listingId  The involved listing ID.
     * @param   VBOBookingRegistry              $registry   The booking registry containing all room related details.
     * 
     * @return  ?VBODooraccessDeviceCapabilityResult        Device capability execution result, or null.
     */
    public function createBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // integration providers should implement this method
        return null;
    }

    /**
     * Default implementation for letting a provider modify a door access upon a booking modification event.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The provider integration device.
     * @param   int                             $listingId  The involved listing ID.
     * @param   VBOBookingRegistry              $registry   The booking registry containing all room related details.
     * 
     * @return  ?VBODooraccessDeviceCapabilityResult        Device capability execution result, or null.
     */
    public function modifyBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // integration providers should implement this method
        return null;
    }

    /**
     * Default implementation for letting a provider cancel a door access upon a booking cancellation event.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The provider integration device.
     * @param   int                             $listingId  The involved listing ID.
     * @param   VBOBookingRegistry              $registry   The booking registry containing all room related details.
     * 
     * @return  ?VBODooraccessDeviceCapabilityResult        Device capability execution result, or null.
     */
    public function cancelBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // integration providers should implement this method
        return null;
    }

    /**
     * Default implementation for letting a provider unlock a specific device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The provider integration device.
     * 
     * @return  ?VBODooraccessDeviceCapabilityResult        Device capability execution result, or null.
     */
    public function handleUnlockDevice(VBODooraccessIntegrationDevice $device)
    {
        // integration providers should implement this method
        return null;
    }

    /**
     * Default implementation for letting a provider detect the first door access for a given booking.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The provider integration device.
     * @param   int                             $listingId  The involved listing ID.
     * @param   VBOBookingRegistry              $registry   The booking registry containing all room related details.
     * 
     * @return  ?VBODooraccessDeviceCapabilityResult        Device capability execution result, or null.
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function detectFirstAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // integration providers should implement this method
        return null;
    }

    /**
     * Creates a new device capability object with the given properties.
     * 
     * @param   array   $properties     Associative list of capability properties.
     * 
     * @return  VBODooraccessDeviceCapability
     */
    public function createDeviceCapability(array $properties)
    {
        $capability = new VBODooraccessDeviceCapability;

        foreach ($properties as $property => $value) {
            // build setter method name
            $method = 'set' . ucfirst($property);

            // bind property value
            $capability->{$method}($value);
        }

        return $capability;
    }

    /**
     * Fetches the integration devices and parses them internally.
     * Integration providers will actually fetch the remote devices.
     * 
     * @return  VBODooraccessIntegrationDevice[]    List of integration device objects.
     * 
     * @throws  Exception
     */
    public function fetchDevices()
    {
        // access the integration settings
        $settings = $this->getSettings();

        if (!$settings && $this->getParams()) {
            throw new Exception('Missing integration provider settings.', 500);
        }

        try {
            // let the integration provider fetch the list of remote devices
            $remoteDevicesList = $this->fetchRemoteDevices($settings);
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        if (!is_array($remoteDevicesList) || !$remoteDevicesList) {
            return [];
        }

        // map every remote device payload into a decorated device object
        return array_values(array_filter(array_map(function($device) {
            // cast device payload to array
            $device = (array) $device;

            if (!$device) {
                // empty device payload
                return null;
            }

            // start device decorator
            $decorator = new VBODooraccessIntegrationDevice($device);

            // let the integration provider decorate the device properties
            $this->decorateDeviceProperties($decorator, $device);

            if (!$decorator->isComplete()) {
                // invalid device object properties decorated
                return null;
            }

            // return the decorated device object
            return $decorator;
        }, $remoteDevicesList)));
    }

    /**
     * Builds the routed Webhook endpoint URL for the
     * current integration record and profile ID to spawn.
     * 
     * @param   ?array  $data   Optional assoc list of URL data.
     * 
     * @return  string          The routed Webhook endpoint URL.
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function buildWebhookURL(?array $data = null)
    {
        // extract possibly injected options
        $options = (array) ($data['_options'] ?? []);

        // remove options from URL data
        unset($data['_options']);

        // build base URL params to spawn the current integration and profile
        $urlParams = [
            'option'   => 'com_vikbooking',
            'task'     => 'apps.webhook',
            'env'      => 'dac',
            'provider' => $this->getProfileProvider(),
            'profile'  => $this->getProfileID(),
        ];

        if ($data) {
            // merge custom URL data
            $urlParams = array_merge($urlParams, $data);
        }

        // access the platform URI
        $platformUri = VBOFactory::getPlatform()->getUri();

        if ($options['route'] ?? false) {
            // route the final URI
            $finalUri = $platformUri->route('index.php?' . http_build_query($urlParams));
        } else {
            // construct root URI with query string arguments
            $finalUri = JUri::root() . '?' . http_build_query($urlParams);
        }

        if ($options['csrf'] ?? null) {
            // add CSRF token to final URL
            $finalUri = $platformUri->addCSRF($finalUri);
        }

        // return the routed URI to spawn the integration Webhook endpoint URL
        return $finalUri;
    }

    /**
     * Builds the routed OAuth authorization URL for the
     * current integration record and profile ID to spawn.
     * 
     * @param   ?array  $data   Optional assoc list of URL data.
     * 
     * @return  string          The routed OAuth 2 auth URL.
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function buildOAuthURL(?array $data = null)
    {
        // extract possibly injected options
        $options = (array) ($data['_options'] ?? []);

        // remove options from URL data
        unset($data['_options']);

        // build base URL params to spawn the current integration and profile
        $urlParams = [
            'option'   => 'com_vikbooking',
            'task'     => 'apps.oauth',
            'env'      => 'dac',
            'provider' => $this->getProfileProvider(),
            'profile'  => $this->getProfileID(),
        ];

        if ($data) {
            // merge custom URL data
            $urlParams = array_merge($urlParams, $data);
        }

        // access the platform URI
        $platformUri = VBOFactory::getPlatform()->getUri();

        if ($options['route'] ?? false) {
            // route the final URI
            $finalUri = $platformUri->route('index.php?' . http_build_query($urlParams));
        } else {
            // construct root URI with query string arguments
            $finalUri = JUri::root() . '?' . http_build_query($urlParams);
        }

        if ($options['csrf'] ?? null) {
            // add CSRF token to final URL
            $finalUri = $platformUri->addCSRF($finalUri);
        }

        // return the routed URI to spawn the integration authorization callback URL
        return $finalUri;
    }

    /**
     * Returns an active OAuth code to verify the authenticity of the request.
     * To be used for CSRF prevention or similar purposes during OAuth authentications.
     * 
     * @param   ?string     $suffix     Optional configuration parameter suffix.
     * 
     * @return  string
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function getOAuthCode(?string $suffix = null, ?array $options = null)
    {
        // access configuration object
        $config = VBOFactory::getConfig();

        // build param name
        $paramName = 'dac_oauth_code' . ($suffix ? '_' . $suffix : '');

        // access current setting from database
        $currentSetting = (array) $config->getArray($paramName, []);

        // access current expiration timestamp, if any
        $currentExpiryTs = $currentSetting['expiryTs'] ?? null;

        // a new OAuth code will be generated if unavailable or expired
        if (!($currentSetting['code'] ?? null) || ($currentExpiryTs !== null && $currentExpiryTs < time())) {
            // generate a new OAuth code with a default validity of one hour
            $oauthCode = VikBooking::getCPinInstance()->generateSerialCode(
                (int) ($options['length'] ?? 10),
                (is_array($options['map'] ?? null) ? $options['map'] : null)
            );

            if ($options['insensitive'] ?? 1) {
                // case-insensitive by default
                $oauthCode = strtolower($oauthCode);
            }

            // build expiration timestamp
            $expiryTs = ($options['expiry_ts'] ?? 0) ?: strtotime('+1 hour');

            // store OAuth code data
            $config->set($paramName, [
                'code' => $oauthCode,
                'expiry_ts' => $expiryTs,
            ]);

            // return the currently active OAuth code
            return $oauthCode;
        }

        // return the existing OAuth code because still valid
        return $currentSetting['code'];
    }

    /**
     * OAuth authorization callback is triggered to allow the integration
     * to obtain the data upon authorising the application.
     * 
     * @param   ?array  $data   Optional spawn data to parse.
     * 
     * @return  void
     * 
     * @throws  Exception
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function spawnOAuthCallback(?array $data = null)
    {
        // do nothing by default, integrations should override this method
        return;
    }

    /**
     * Webhook endpoint callback is triggered to allow the integration
     * to obtain webhook data from the provider.
     * 
     * @param   ?array  $data   Optional spawn data to parse.
     * 
     * @return  void
     * 
     * @throws  Exception
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function spawnWebhookCallback(?array $data = null)
    {
        // do nothing by default, integrations should override this method
        return;
    }

    /**
     * Fetches the integration remote devices.
     * 
     * @return  array   List of remote device associative arrays or objects.
     */
    abstract protected function fetchRemoteDevices();

    /**
     * Decorates the properties of a remote device fetched.
     * 
     * @param   VBODooraccessIntegrationDevice  $decorator  The integration device decorator object.
     * @param   array                           $device     The remote device associative array fetched.
     * 
     * @return  void
     */
    abstract protected function decorateDeviceProperties(VBODooraccessIntegrationDevice $decorator, array $device);
}
