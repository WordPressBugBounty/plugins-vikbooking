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
 * Door Access integration provider for Nuki.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 * 
 * @link    https://api.nuki.io/
 * @link    https://developer.nuki.io/
 */
final class VBODooraccessProviderNuki extends VBODooraccessIntegrationAware
{
    /**
     * @var     array
     */
    private array $httpHeaders = [];

    /**
     * @var     string
     */
    private string $authScopes = 'smartlock smartlock.action smartlock.auth smartlock.log webhook.central';

    /**
     * @inheritDoc
     */
    public function getAlias()
    {
        return basename(__FILE__, '.php');
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'Nuki - Smart Locks';
    }

    /**
     * @inheritDoc
     */
    public function getShortName()
    {
        return 'Nuki';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VBO_ADMIN_URI . 'resources/nuki-vikbooking-integration-logo.png';
    }

    /**
     * @inheritDoc
     */
    public function getParams()
    {
        // load current settings
        $settings = $this->getSettings();

        // always build the OAuth redirect URL
        $dac_oauth_spawn_url = $this->buildOAuthURL();

        // check if the application was authorised through OAuth
        $oauth_authorised = !empty($settings['_oauth']['access_token']);

        // build OAuth2 authorization link
        if (empty($settings['oauth2_api_key']) || empty($settings['oauth2_api_secret'])) {
            // settings must be saved first
            $oauth2_auth_link = '<span class="label label-error">Save settings first. OAuth2 API Key and API Secret cannot be empty.</span>';
        } else {
            // build button-link for the authorization code URL
            $nuki_auth_data = [
                'response_type' => 'code',
                'client_id'     => $settings['oauth2_api_key'],
                'scope'         => $this->authScopes,
                'state'         => $this->getOAuthCode(),
                'redirect_uri'  => $dac_oauth_spawn_url,
            ];

            // build final Nuki authorization URL to be clicked
            $nuki_auth_link = 'https://api.nuki.io/oauth/authorize?' . http_build_query($nuki_auth_data);

            // set button content
            $oauth2_auth_link = '<a class="btn btn-warning" href="' . $nuki_auth_link . '">' . ($oauth_authorised ? '(Re-)' : '') . 'Authorize Application</a>';
        }

        // build HTML instructions for obtaining the OAuth2 information
        $help_url_webhook = $this->buildWebhookURL();
        $oauth_instructions_html = <<<HTML
        <div>Visit your <a href="https://web.nuki.io/" target="_blank">Nuki Web</a> account (section <em>API</em> - <em>OAuth2</em>) to enable and obtain the <strong>OAuth2 API Key</strong> and <strong>OAuth2 API Secret</strong>. Then enter the following <strong>OAuth2 redirect URL</strong>:</div>
        <ul>
            <li><em>$dac_oauth_spawn_url</em></li>
        </ul>
        <div>From the <em>Nuki Advanced API Integration section</em>, enther the following <strong>Webhook URL</strong>:</div>
        <ul>
            <li><em>$help_url_webhook</em></li>
        </ul>
        HTML;

        if (!$oauth_authorised) {
            // add to the instructions that the application must be authorised
            $oauth_instructions_html .= <<<HTML
            <div>Proceed by clicking the authorization link below after saving your OAuth settings.</div>
            HTML;
        }

        if (!$oauth_authorised) {
            // never obtained an access token through OAuth before
            $oauth2_auth_status = '<span class="badge badge-error">Not Authorized</span>';
        } else {
            // access token was once obtained through OAuth
            $oauth2_auth_status = '<span class="badge badge-success">Authorized</span>';
        }

        // return the list of parameters
        return [
            'ai' => [
                'type'    => 'checkbox',
                'label'   => JText::translate('VBO_AI_SUPPORT'),
                'help'    => JText::translate('VBO_DAC_AI_SUPPORT_HELP'),
                'default' => 1,
            ],
            'passquant' => [
                'type'    => 'select',
                'label'   => JText::translate('VBO_PASSCODES'),
                'help'    => JText::translate('VBO_PASSCODES_QUANT_HELP'),
                'options' => [
                    1 => JText::translate('VBO_ONE_PER_DEVICE'),
                    2 => JText::translate('VBO_ONE_PER_BOOKING'),
                ],
                'default' => 1,
            ],
            'authmeth' => [
                'type'    => 'select',
                'label'   => 'Authentication Method',
                'help'    => 'Choose the authentication type configured in your Nuki Web account.',
                'options' => [
                    'oauth'     => 'OAuth2',
                    'api_token' => 'API Token',
                ],
                'default' => 'oauth',
            ],
            'api_token' => [
                'type'  => 'password',
                'label' => 'API Token',
                'conditional' => 'authmeth:api_token',
            ],
            '_oauth2_auth_help' => [
                'type'  => 'custom',
                // 'label' => 'Instructions',
                'html'  => $oauth_instructions_html,
                'conditional' => 'authmeth:oauth',
            ],
            '_oauth2_auth_status' => [
                'type'  => 'custom',
                'label' => 'Authorization Status',
                'html'  => $oauth2_auth_status,
                'conditional' => 'authmeth:oauth',
            ],
            '_oauth2_auth_link' => [
                'type'  => 'custom',
                'label' => 'Authorization Link',
                'html'  => $oauth2_auth_link,
                'conditional' => 'authmeth:oauth',
            ],
            'oauth2_api_key' => [
                'type'  => 'text',
                'label' => 'OAuth2 API Key (Client ID)',
                'help'  => (empty($settings['oauth2_api_key']) ? 'Save settings to see the authorization link. ' : '') . 'OAuth2 API Key from Nuki Web > Menu > API.',
                'conditional' => 'authmeth:oauth',
            ],
            'oauth2_api_secret' => [
                'type'  => 'password',
                'label' => 'OAuth2 API Secret (Client Secret)',
                'help'  => (empty($settings['oauth2_api_key']) ? 'Save settings to see the authorization link. ' : '') . 'OAuth2 API Secret from Nuki Web > Menu > API.',
                'conditional' => 'authmeth:oauth',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function spawnOAuthCallback(?array $data = null)
    {
        $app = JFactory::getApplication();

        // gather request or data variables to obtain the authorization code for the application
        $auth_code  = ($data['code'] ?? '') ?: $app->input->getString('code', '');
        $auth_state = ($data['state'] ?? '') ?: $app->input->getString('state', '');

        if (!empty($auth_code) && empty($auth_state)) {
            // missing CSRF proof token from Nuki
            throw new Exception(JText::translate('JINVALID_TOKEN'), 403);
        }

        // perform the OAuth token internal validation that relies on the database for CSRF prevention
        if ($auth_state != $this->getOAuthCode()) {
            throw new Exception(JText::translate('JINVALID_TOKEN'), 403);
        }

        // load current settings
        $settings = $this->getSettings();

        if (!empty($auth_code)) {
            /**
             * Application was authorized and an authorization code was received.
             * Perform a request to exchange the auth code and obtain the access token.
             */

            // start transporter (by setting the necessary headers)
            $transporter = $this->createHTTPTransporter([
                'doing_oauth' => 1,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            // build request data, inclusive of redirect URI (must match the one for the initial authorization)
            $requestData = [
                'client_id'     => ($settings['oauth2_api_key'] ?? ''),
                'client_secret' => ($settings['oauth2_api_secret'] ?? ''),
                'grant_type'    => 'authorization_code',
                'code'          => $auth_code,
                'redirect_uri'  => $this->buildOAuthURL(),
            ];

            // make the API request (POST with query string values in endpoint URL)
            $response = $transporter->post('https://api.nuki.io/oauth/token', http_build_query($requestData), $this->httpHeaders, 60);

            // obtain the response data (response should be empty in case of success)
            $responseData = (array) json_decode((string) $response->body, true);

            if (empty($response->code) || $response->code > 299) {
                // an error occurred
                throw new Exception($response->body ?: 'Error exchanging the auth code for the OAuth access token.', ($response->code ?: 500));
            }

            if (empty($responseData['access_token']) || empty($responseData['refresh_token'])) {
                // unexpected response format
                throw new Exception('Unexpected response format: missing access token or refresh token.', 500);
            }

            // calculate and set the token expiration timestamp (3600 seconds)
            $responseData['expiry_ts'] = strtotime(sprintf('+%d seconds', (int) ($responseData['expires_in'] ?? 0)));

            // inject OAuth details within the current integration settings
            $settings['_oauth'] = $responseData;

            // update integration record settings
            $this->setProfileRecordProp('settings', $settings);

            // store integration record settings
            VBODooraccessFactory::getInstance()->saveIntegrationRecord($this, ['settings' => $this->getSettings()]);

            // application was successfully authorised, close the response

            // redirect to VikBooking admin-widget
            $redirectData = [
                'option'         => 'com_vikbooking',
                'load_widget'    => 'door_access_control',
                'multitask_data' => [
                    'provider' => $this->getProfileProvider(),
                    'profile'  => $this->getProfileID(),
                    'tab'      => 'settings',
                ],
            ];
            $redirectUri = VBOFactory::getPlatform()->getUri()->admin('index.php?' . http_build_query($redirectData));

            $app->redirect($redirectUri);
            $app->close();
        }

        // close the response with a status code 204 (No Content)
        VBOHttpDocument::getInstance($app)->close(204, 'No data to authorise the application.');
    }

    /**
     * @inheritDoc
     */
    public function spawnWebhookCallback(?array $data = null)
    {
        $app = JFactory::getApplication();

        // load current settings
        $settings = $this->getSettings();

        // access signing key (client secret)
        $signingKey = $settings['oauth2_api_secret'] ?? '';

        // access raw body payload
        $rawPayload = file_get_contents('php://input');

        if (empty($rawPayload)) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close(400, 'Missing webhook payload.');
        }

        // access the signature value from headers
        $signatureHeader = $app->input->server->get('HTTP_X_NUKI_SIGNATURE_SHA256', '');

        if (empty($signatureHeader)) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close(400, 'Missing webhook signature header.');
        }

        // generate the HMAC SHA256 signature
        $computedSignature = hash_hmac('sha256', $rawPayload, $signingKey);

        // validate signature
        if ($computedSignature != $signatureHeader && !hash_equals($computedSignature, $signatureHeader)) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close(401, 'Invalid webhook signature value.');
        }

        // access webhook data
        $webhookData = $app->input->json->getArray();

        // determine the webhook notification type
        $webhookType = strtoupper((string) ($webhookData['feature'] ?? ''));

        // process the request, if known and needed
        try {
            switch ($webhookType) {
                case 'DEVICE_STATUS':
                    // handle device status update webhook notification
                    $this->webhookHandleDeviceStatus($webhookData);
                    break;

                case 'DEVICE_LOGS':
                    // handle device log webhook notification
                    $this->webhookHandleDeviceLogs($webhookData);
                    break;

                default:
                    // do nothing
                    break;
            }
        } catch (Exception $e) {
            // propagate the "error" (could also be 200) by closing the response
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // close the response with a 200 status code
        VBOHttpDocument::getInstance($app)->close(200, 'Webhook data successfully received.');
    }

    /**
     * @inheritDoc
     */
    public function canUnlockDevices()
    {
        // this method is called when the integration has loaded its profile record
        // we return true only if the apposite AI setting is enabled

        $settings = $this->getSettings();

        return !empty($settings['ai']);
    }

    /**
     * Device capability implementation to unlock a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://api.nuki.io/#/Smartlock/SmartlockUnlockActionResource_postUnlock_post
     */
    public function unlockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // make the API request
        $response = $transporter->post('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/action/unlock', [], $this->httpHeaders, 60);

        // obtain the response data (response should be empty in case of success)
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error unlocking the device.', ($response->code ?: 500));
        }

        return (new VBODooraccessDeviceCapabilityResult)->setText(sprintf('The device "%s" was unlocked!', $device->getName()));
    }

    /**
     * Device capability implementation to lock a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://api.nuki.io/#/Smartlock/SmartlockLockActionResource_postLock_post
     */
    public function lockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // make the API request
        $response = $transporter->post('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/action/lock', [], $this->httpHeaders, 60);

        // obtain the response data (response should be empty in case of success)
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error locking the device.', ($response->code ?: 500));
        }

        return (new VBODooraccessDeviceCapabilityResult)->setText(sprintf('The device "%s" was locked!', $device->getName()));
    }

    /**
     * Device capability implementation to list a device authorization codes.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @throws  Exception
     * 
     * @link    https://api.nuki.io/#/SmartlockAuth/SmartlocksAuthsResource_get_get
     */
    public function listPasscodes(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // check if a specific passcode name should be matched from the API response (filter not supported)
        $searchPasscode = $options['search'] ?? null;

        // build request data
        $data = [
            // type option could be "any", but we only accept integers, to be casted to string
            // as the query-string filter accept a list of comma-separated values (i.e. "0,2,13")
            'types' => is_numeric($options['type'] ?? null) ? (string) $options['type'] : null,
        ];

        // make a request to obtain all created passcodes of a lock
        $response = $transporter->get('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/auth?' . http_build_query(array_filter($data, function($value) {
            // allow to filter by type "0" which equals to "App"
            return !empty($value) || $value === '0';
        })), $this->httpHeaders, 20);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error fetching device passcodes.', ($response->code ?: 500));
        }

        // returned passcodes list expected
        $passcodes = $responseData;

        if ($searchPasscode && is_array($passcodes)) {
            // filter passcodes found by the given name
            $passcodes = array_filter($passcodes, function($authPasscode) use ($searchPasscode) {
                // passcode name must match the given one as we are doing an exact search
                return ($authPasscode['name'] ?? '') === $searchPasscode;
            });
        }

        if (!$passcodes) {
            throw new Exception('No authorization codes found for the device.', 404);
        }

        // list of passcode IDs and values obtained
        $passcodesAssoc = [];

        // build HTML output
        $output = '';

        // lang defs
        $lang_passcode  = JText::translate('VBO_PASSCODE');
        $lang_startdate = JText::translate('VBNEWPKGDFROM');
        $lang_enddate   = JText::translate('VBNEWPKGDTO');
        $lang_createdon = JText::translate('VBOINVCREATIONDATE');
        $lang_createdby = JText::translate('VBCSVCREATEDBY');

        // table head
        $output .= <<<HTML
<div class="vbo-dac-table-wrap">
    <table class="vbo-dac-table">
        <thead>
            <tr>
                <td>Passcode ID</td>
                <td>Passcode Name</td>
                <td>{$lang_passcode}</td>
                <td>Passcode Type</td>
                <td>{$lang_startdate}</td>
                <td>{$lang_enddate}</td>
                <td>{$lang_createdon}</td>
                <td>{$lang_createdby}</td>
            </tr>
        </thead>
        <tbody>
HTML;

        // scan all passcodes obtained
        foreach ($passcodes as $passcode) {
            // set passcode properties
            $passcodeId = $passcode['id'] ?? '';
            $passcodeValue = $passcode['code'] ?? '';
            $passcodeName = $passcode['name'] ?? '';
            $passcodeType = $this->getPasscodeTypes((int) ($passcode['type'] ?? 0), true);
            $startDate = ($passcode['allowedFromDate'] ?? '') ? JHtml::fetch('date', $passcode['allowedFromDate'], 'Y-m-d H:i:s') : '---';
            $endDate = ($passcode['allowedUntilDate'] ?? '') ? JHtml::fetch('date', $passcode['allowedUntilDate'], 'Y-m-d H:i:s') : '---';
            $sendDate = ($passcode['creationDate'] ?? '') ? JHtml::fetch('date', $passcode['creationDate'], 'Y-m-d H:i:s') : '---';
            $senderUsername = $passcode['accountUserId'] ?? '';

            // bind passcode id values
            $passcodesAssoc[$passcodeId] = [
                'name'  => $passcodeName,
                'value' => $passcodeValue,
            ];

            // build passcode HTML code
            $output .= <<<HTML
            <tr>
                <td><span class="vbo-dac-table-passcode-id">{$passcodeId}</span></td>
                <td><span class="vbo-dac-table-passcode-name">{$passcodeName}</span></td>
                <td><span class="vbo-dac-table-passcode-code">{$passcodeValue}</span></td>
                <td>{$passcodeType}</td>
                <td>{$startDate}</td>
                <td>{$endDate}</td>
                <td>{$sendDate}</td>
                <td>{$senderUsername}</td>
            </tr>
HTML;
        }

        // close table
        $output .= <<<HTML
        </tbody>
    </table>
</div>
HTML;

        // return the capability result object by setting the output value
        return (new VBODooraccessDeviceCapabilityResult($passcodesAssoc))
            ->setOutput($output);
    }

    /**
     * Device capability implementation to create a custom passcode for a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://api.nuki.io/#/SmartlockAuth/SmartlockAuthsResource_put_put
     */
    public function createCustomPasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        if (empty($options['pwdvalue'])) {
            // passcode value cannot be empty
            $options['pwdvalue'] = $this->generateRandomPasscode();
        }

        // build request data
        $data = [
            'name'             => $options['pwdname'] ?? null,
            'allowedFromDate'  => (($options['startdate'] ?? '') ? $this->getDateRFC3339($options['startdate']) : null),
            'allowedUntilDate' => (($options['enddate'] ?? '') ? $this->getDateRFC3339($options['enddate']) : null),
            'allowedWeekDays'  => 127,
            'allowedFromTime'  => 0,
            'allowedUntilTime' => 0,
            'remoteAllowed'    => true,
            'type'             => 13,
            'code'             => (int) $options['pwdvalue'],
        ];

        // make the API request
        $response = $transporter->put('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/auth', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data (should be empty in case of success)
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred, build DAC Exception with retry-data
            $dacError = (new VBODooraccessException($response->body ?: 'Error adding a custom passcode to the device.', ($response->code ?: 500)))
                ->setDevice($device)
                ->setRetryCallback('createCustomPasscode')
                ->setRetryData($options);

            // throw error
            throw $dacError;
        }

        // build result properties to bind (operation is asynchronous, so we don't immediately get an authorization ID for the new access code)
        $resultProps = [
            'code'      => (string) $options['pwdvalue'],
            'name'      => (string) ($options['pwdname'] ?? ''),
            'listingId' => (int) ($options['listing_id'] ?? 0),
        ];

        // get the listing name, if available
        $listingName = '';
        if (!empty($resultProps['listingId'])) {
            $listingData = VikBooking::getRoomInfo($resultProps['listingId'], ['name'], true);
            $listingName = sprintf('%s: ', $listingData['name'] ?? '');
        }

        // wrap and return the device capability result object
        return (new VBODooraccessDeviceCapabilityResult($resultProps))
            ->setPasscode($resultProps['code'])
            ->setText($listingName . JText::sprintf('VBO_PASSCODE_GEN_OK_DEVICE', $resultProps['code'], $device->getName()));
    }

    /**
     * Device capability implementation to delete a passcode from a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://api.nuki.io/#/SmartlockAuth/SmartlockAuthResource_delete_delete
     */
    public function deletePasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        if (empty($options['pwdid'])) {
            throw new Exception($response->body ?: 'Missing authorization (access code) ID to delete.', 400);
        }

        // start transporter
        $transporter = $this->createHTTPTransporter();

        // make the API request
        $response = $transporter->delete('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/auth/' . $options['pwdid'], [], $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error deleting passcode from device.', ($response->code ?: 500));
        }

        return (new VBODooraccessDeviceCapabilityResult)
            ->setText(JText::sprintf('VBO_PASSCODE_DEL_OK_DEVICE', $device->getName()));
    }

    /**
     * Device capability implementation to update a passcode from a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://api.nuki.io/#/SmartlockAuth/SmartlockAuthResource_post_post
     */
    public function updatePasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        if (empty($options['pwdid'])) {
            throw new Exception($response->body ?: 'Missing authorization (access code) ID to update.', 400);
        }

        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'allowedFromDate'  => (($options['startdate'] ?? '') ? $this->getDateRFC3339($options['startdate']) : null),
            'allowedUntilDate' => (($options['enddate'] ?? '') ? $this->getDateRFC3339($options['enddate']) : null),
            'allowedWeekDays'  => 127,
            'allowedFromTime'  => 0,
            'allowedUntilTime' => 0,
            'remoteAllowed'    => true,
        ];

        // make the API request
        $response = $transporter->post('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/auth/' . $options['pwdid'], json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error updating passcode on device.', ($response->code ?: 500));
        }

        return (new VBODooraccessDeviceCapabilityResult)
            ->setText(JText::sprintf('VBO_PASSCODE_UPD_OK_DEVICE', $device->getName()));
    }

    /**
     * Device capability implementation to show the list of activity logs of a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @throws  Exception
     * 
     * @link    https://api.nuki.io/#/SmartlockLog/SmartlockLogsResource_get_get
     */
    public function showActivityLogs(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // build query string data
        $data = [
            'fromDate' => (!empty($options['startdate']) ? $this->getDateRFC3339($options['startdate']) : null),
            'toDate'   => (!empty($options['enddate']) ? $this->getDateRFC3339($options['enddate']) : null),
            'limit'    => 50,
        ];

        // make a request to obtain the logs of a lock
        $response = $transporter->get('https://api.nuki.io/smartlock/' . $this->getDecimalDeviceID($device) . '/log?' . http_build_query(array_filter($data)), $this->httpHeaders, 20);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error fetching device logs.', ($response->code ?: 500));
        }

        $activities = $responseData;

        if (!is_array($activities) || !$activities) {
            throw new Exception(sprintf("No log records found for the device.\n%s", print_r($activities, true)), 404);
        }

        // build HTML output
        $output = '';

        // lang defs
        $lang_createdon = JText::translate('VBOINVCREATIONDATE');

        // table head
        $output .= <<<HTML
<div class="vbo-dac-table-wrap">
    <table class="vbo-dac-table">
        <thead>
            <tr>
                <td>Log ID</td>
                <td>Name</td>
                <td>Passcode ID</td>
                <td>Action</td>
                <td>Trigger</td>
                <td>State</td>
                <td>Source</td>
                <td>{$lang_createdon}</td>
            </tr>
        </thead>
        <tbody>
HTML;

        // scan all activities obtained
        foreach ($activities as $activity) {
            // set activity properties
            $activityId = $activity['id'] ?? '';
            $activityName = $activity['name'] ?? '';
            $activityAuthId = $activity['authId'] ?? '';
            $activityAction = $this->getActivityAction((int) ($activity['action'] ?? 0));
            $activityTrigger = $this->getActivityTrigger((int) ($activity['trigger'] ?? 0));
            $activityState = $this->getActivityState((int) ($activity['state'] ?? 0));
            $activitySource = $this->getActivitySource((int) ($activity['source'] ?? 0));
            $activityDate = ($activity['date'] ?? '') ? JHtml::fetch('date', $activity['date'], 'Y-m-d H:i:s') : '---';

            // build passcode HTML code
            $output .= <<<HTML
            <tr>
                <td><span class="vbo-dac-table-passcode-id">{$activityId}</span></td>
                <td><span class="vbo-dac-table-passcode-name">{$activityName}</span></td>
                <td><span class="vbo-dac-table-passcode-id">{$activityAuthId}</span></td>
                <td>{$activityAction}</td>
                <td>{$activityTrigger}</td>
                <td>{$activityState}</td>
                <td>{$activitySource}</td>
                <td>{$activityDate}</td>
            </tr>
HTML;
        }

        // close table
        $output .= <<<HTML
        </tbody>
    </table>
</div>
HTML;

        // return the capability result object by setting the output value
        return (new VBODooraccessDeviceCapabilityResult($activities))
            ->setOutput($output);
    }

    /**
     * @inheritDoc
     */
    public function createBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // access the integration settings
        $settings = $this->getSettings();

        // build booking-listing signature
        $signature = sprintf('%d-%d', $registry->getID(), $listingId);

        // access booking registry DAC data for passcodes generated
        $passcodesBuffer = $registry->getDACProperty($this->getAlias(), 'passcodes', []);

        // determine the passcode value to use, either a new one or a previous one for the same booking
        if (($settings['passquant'] ?? 0) == 2 && ($passcodesBuffer[$signature] ?? null)) {
            // use the previously generated passcode for this booking and listing also on this device
            $passcodeValue = $passcodesBuffer[$signature];
        } else {
            // generate custom, yet random, passcode value of 6 digits for this device
            $passcodeValue = $this->generateRandomPasscode();
        }

        // prepare the options for creating a custom passcode (randomly generated by us)
        $options = [
            // use a password name that can be used later to find it under this booking and listing ID
            'pwdname'   => sprintf('bid:%d-%d', $registry->getID(), $listingId),
            // set the passcode validity start date and time
            'startdate' => date('Y-m-d H:i:00', $registry->getProperty('checkin', 0)),
            // set the passcode validity end date and time
            'enddate'   => date('Y-m-d H:i:00', $registry->getProperty('checkout', 0)),
            // custom passcode value to create on the device
            'pwdvalue' => $passcodeValue,
            // inject the listing ID for completion of data
            'listing_id' => $listingId,
        ];

        // create custom passcode on the current device
        $result = $this->createCustomPasscode($device, $options);

        // update booking registry DAC data for passcodes generated
        $passcodesBuffer[$signature] = $result->getPasscode();
        $registry->setDACProperty($this->getAlias(), 'passcodes', $passcodesBuffer);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function modifyBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // searching, deleting and re-creating passcodes is always safer in case of
        // booking modification for possibly different listing IDs involved

        // find the passcode data that were previously created for this booking
        $previousDevicePasscodes = VikBooking::getBookingHistoryInstance($registry->getID())
            ->getEventsWithData(['ND', 'MD'], function($data) use ($device) {
                $data = (array) $data;
                // ensure the passcode was generated for this provider, profile and device
                return ($data['provider'] ?? '') == $this->getProfileProvider() &&
                    ($data['profile'] ?? '') == $this->getProfileID() &&
                    ($data['device'] ?? '') == $device->getID() &&
                    (!empty($data['passcode']) || !empty($data['props']));
            });

        if (!$previousDevicePasscodes) {
            // no passcodes were previously created for this booking
            // process the modification as a new door access creation
            return $this->createBookingDoorAccess($device, $listingId, $registry);
        }

        // scan all previously created passcodes in DESC order on this device and delete them
        $previousPasscodeNamings = [];
        foreach (array_reverse($previousDevicePasscodes) as $previousData) {
            // ensure we only have array values
            $previousData = (array) json_decode(json_encode($previousData), true);

            /**
             * The generation of booking passcodes is asynchronous, hence we don't immediately get and store the
             * passcode ID to be used for deleting it immediately. We need to "search" the passcode by name.
             */

            // get the previous passcode name
            $previousPasscodeName = ($previousData['props']['name'] ?? '');

            if (empty($previousPasscodeName) || in_array($previousPasscodeName, $previousPasscodeNamings)) {
                // no passcode name to search and delete, or already deleted
                continue;
            }

            // push processed passcode name
            $previousPasscodeNamings[] = $previousPasscodeName;

            try {
                // attempt to find the passcode on this device by name, which includes booking and listing IDs
                $findResult = $this->listPasscodes($device, [
                    // type "Keypad"
                    'type'   => 13,
                    // inject search property to match this exact passcode name
                    'search' => sprintf('bid:%d-%d', $registry->getID(), $listingId),
                ]);

                if (!$findResult->getProperties()) {
                    // passcode not found by name
                    throw new Exception('Previous passcode not found by name.', 404);
                }

                // iterate the list of passcodes found, even if only one is expected
                foreach ($findResult->getProperties() as $pwdId => $pwdData) {
                    // delete the first passcode found, previously created for this booking
                    $this->deletePasscode($device, [
                        'pwdid' => $pwdId,
                    ]);

                    // abort loop at first run
                    break;
                }
            } catch (Exception $e) {
                // do nothing on error with cancellations or previous passcodes not found
            }
        }

        // process the modification as a new door access creation, always with custom passcodes
        return $this->createBookingDoorAccess($device, $listingId, $registry);
    }

    /**
     * @inheritDoc
     */
    public function cancelBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        try {
            // find the previously created passcode for this booking and listing
            $findResult = $this->listPasscodes($device, [
                // type "Keypad"
                'type'   => 13,
                // inject search property to match this exact passcode name
                'search' => sprintf('bid:%d-%d', $registry->getID(), $listingId),
            ]);

            if (!$findResult->getProperties()) {
                // passcode not found
                throw new Exception('Previous passcode not found.', 404);
            }
        } catch (Exception $e) {
            // nothing to cancel, but prevent unwanted errors not related to the real cancellation
            return null;
        }

        // iterate the list of passcodes found, even if only one is expected
        foreach ($findResult->getProperties() as $pwdId => $pwdData) {
            // delete the first passcode found
            return $this->deletePasscode($device, [
                'pwdid' => $pwdId,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function handleUnlockDevice(VBODooraccessIntegrationDevice $device)
    {
        // unlock the requested device
        return $this->unlockDevice($device);
    }

    /**
     * @inheritDoc
     */
    public function getPasscodeFromHistoryResult(array $resultProperties)
    {
        // creating a passcode should bind its value within the device capability result object
        return $resultProperties['code'] ?? null;
    }

    /**
     * @inheritDoc
     * 
     * @link    https://api.nuki.io/#/Smartlock/SmartlocksResource_get_get
     */
    protected function fetchRemoteDevices()
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // make a request to obtain the lock list of an account
        $response = $transporter->get('https://api.nuki.io/smartlock', $this->httpHeaders, 20);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error fetching the remove devices.', ($response->code ?: 500));
        }

        // get the list of devices returned
        $devices = $responseData;

        if (empty($devices[0]['smartlockId'])) {
            throw new Exception('No devices found under the current account.', 500);
        }

        return $devices;
    }

    /**
     * @inheritDoc
     */
    protected function decorateDeviceProperties(VBODooraccessIntegrationDevice $decorator, array $device)
    {
        // set device ID
        $decorator->setID($device['smartlockId'] ?? '');

        // set device name
        $decorator->setName($device['name'] ?? '');

        // set device description
        $decorator->setDescription($device['accountId'] ?? '');

        // set device icon
        $decorator->setIcon('<i class="' . VikBookingIcons::i('fingerprint') . '"></i>');

        // map device model (type)
        $modelName = ($device['type'] ?? '') ? $this->getDeviceTypes((int) $device['type'], true) : '';

        // set device model
        $decorator->setModel($modelName);

        if ($device['state']['batteryCharge'] ?? null) {
            // set device battery level
            $decorator->setBatteryLevel((float) $device['state']['batteryCharge']);
        }

        // set device capabilities
        $decorator->setCapabilities([
            // unlock device
            $this->createDeviceCapability([
                'id'          => 'unlock_device',
                'title'       => JText::translate('VBDASHUNLOCK'),
                'description' => JText::translate('VBO_UNLOCK_DEVICE_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('unlock') . '"></i>',
                'callback'    => 'unlockDevice',
            ]),
            // lock device
            $this->createDeviceCapability([
                'id'          => 'lock_device',
                'title'       => JText::translate('VBO_LOCK'),
                'description' => JText::translate('VBO_LOCK_DEVICE_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('lock') . '"></i>',
                'callback'    => 'lockDevice',
            ]),
            // read passcodes
            $this->createDeviceCapability([
                'id'          => 'list_passcodes',
                'title'       => JText::translate('VBO_LIST_PASSCODES'),
                'description' => JText::translate('VBO_LIST_PASSCODES_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('key') . '"></i>',
                'callback'    => 'listPasscodes',
                'params'      => [
                    'type' => [
                        'type'    => 'select',
                        'label'   => JText::translate('VBPSHOWSEASONSTHREE'),
                        'options' => [
                            'any' => sprintf('- %s', JText::translate('VBANYTHING')),
                            13    => 'Keypad',
                            0     => 'App',
                            2     => 'Fob',
                        ],
                    ],
                ],
            ]),
            // create (custom) passcode
            $this->createDeviceCapability([
                'id'          => 'create_custom_passcode',
                'title'       => JText::translate('VBO_CREATE_PASSCODECUST'),
                'description' => JText::translate('VBO_CREATE_PASSCODECUST_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('user-plus') . '"></i>',
                'callback'    => 'createCustomPasscode',
                'params'      => [
                    'pwdvalue' => [
                        'type'    => 'text',
                        'label'   => JText::translate('VBO_PASSCODE'),
                        'help'    => JText::translate('VBO_PASSCODE_EMPTY_HELP') . ' 6 digits (1-9), should not start with 12 and should not contain 0.',
                        'attributes' => [
                            'pattern' => '^(?!12)[1-9]{6}$',
                        ],
                    ],
                    'pwdname' => [
                        'type'  => 'text',
                        'label' => JText::translate('VBO_PASSCODE_NAME'),
                        'help'  => JText::translate('VBO_OPT_PASSCODE_NAME'),
                    ],
                    'startdate' => [
                        'type'  => 'datetime',
                        'label' => JText::translate('VBNEWPKGDFROM'),
                        'help'  => JText::translate('VBO_PASSCODE_VALID_START'),
                    ],
                    'enddate' => [
                        'type'  => 'datetime',
                        'label' => JText::translate('VBNEWPKGDTO'),
                        'help'  => JText::translate('VBO_PASSCODE_VALID_END'),
                    ],
                ],
            ]),
            // delete passcode
            $this->createDeviceCapability([
                'id'          => 'delete_passcode',
                'title'       => JText::translate('VBO_DELETE_PASSCODE'),
                'description' => JText::translate('VBO_DELETE_PASSCODE_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('trash') . '"></i>',
                'callback'    => 'deletePasscode',
                'params'      => [
                    'pwdid' => [
                        'type'  => 'text',
                        'label' => 'Passcode ID',
                    ],
                ],
            ]),
            // update passcode
            $this->createDeviceCapability([
                'id'          => 'update_passcode',
                'title'       => JText::translate('VBO_UPDATE_PASSCODE'),
                'description' => JText::translate('VBO_UPDATE_PASSCODE_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('user-plus') . '"></i>',
                'callback'    => 'updatePasscode',
                'params'      => [
                    'pwdid' => [
                        'type'  => 'text',
                        'label' => 'Passcode ID',
                    ],
                    'startdate' => [
                        'type'  => 'datetime',
                        'label' => JText::translate('VBNEWPKGDFROM'),
                        'help'  => JText::translate('VBO_PASSCODE_VALID_START'),
                    ],
                    'enddate' => [
                        'type'  => 'datetime',
                        'label' => JText::translate('VBNEWPKGDTO'),
                        'help'  => JText::translate('VBO_PASSCODE_VALID_END'),
                    ],
                ],
            ]),
            // show activity logs
            $this->createDeviceCapability([
                'id'          => 'activity_logs',
                'title'       => JText::translate('VBO_ACTIVITY_LOGS'),
                'description' => JText::translate('VBO_ACTIVITY_LOGS_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('search') . '"></i>',
                'callback'    => 'showActivityLogs',
                'params'      => [
                    'startdate' => [
                        'type'  => 'datetime',
                        'label' => JText::translate('VBOREPORTSDATEFROM'),
                    ],
                    'enddate' => [
                        'type'  => 'datetime',
                        'label' => JText::translate('VBOREPORTSDATETO'),
                    ],
                ],
            ]),
        ]);

        // set device payload by unsetting the unwanted properties
        unset(
            $device['advancedConfig'],
            $device['openerAdvancedConfig'],
            $device['smartdoorAdvancedConfig'],
            $device['webConfig'],
            $device['previousSubscriptions'],
            $device['currentSubscription']
        );
        $decorator->setPayload($device);
    }

    /**
     * Handles a Nuki Webhook notification of type "DEVICE_STATUS".
     * 
     * @param   ?array  $data   The notification payload array.
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    protected function webhookHandleDeviceStatus(?array $data = null)
    {
        if (empty($data['smartlockId'])) {
            // useless to proceed without knowing the device ID
            // abort with no error status codes
            throw new Exception('Missing smartlock ID.', 200);
        }

        // access the current device by ID
        try {
            $device = $this->getDeviceById((string) $data['smartlockId']);
        } catch (Exception $e) {
            // abort with no error status codes
            throw new Exception($e->getMessage(), 200);
        }

        if (is_numeric($data['state']['batteryCharge'] ?? null)) {
            // update the device battery level
            $device->setBatteryLevel((float) $data['state']['batteryCharge']);
        }

        // update current device
        try {
            VBODooraccessFactory::getInstance()->saveIntegrationRecord($this, ['devices' => $this->getDevices()]);
        } catch (Exception $e) {
            // abort with no error status codes
            throw new Exception($e->getMessage(), 200);
        }
    }

    /**
     * Handles a Nuki Webhook notification of type "DEVICE_LOGS".
     * 
     * @param   ?array  $data   The notification payload array.
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    protected function webhookHandleDeviceLogs(?array $data = null)
    {
        if (empty($data['smartlockLog']['smartlockId'])) {
            // useless to proceed without knowing the device ID
            // abort with no error status codes
            throw new Exception('Missing smartlock ID.', 200);
        }

        // access the current device by ID
        try {
            $device = $this->getDeviceById((string) $data['smartlockLog']['smartlockId']);
        } catch (Exception $e) {
            // abort with no error status codes
            throw new Exception($e->getMessage(), 200);
        }

        // identify log action, trigger, state and source
        $logAction  = $data['smartlockLog']['action'] ?? null;
        $logTrigger = $data['smartlockLog']['trigger'] ?? null;
        $logState   = $data['smartlockLog']['state'] ?? null;
        $logSource  = $data['smartlockLog']['source'] ?? null;

        /**
         * Check if the smartlock log refers to a successful authentication code through keypad.
         * 
         * Action:  should be 3, 1 or 5 (unlatch, unlock, lock-n-go with unlatch).
         * Trigger: should be 255 (keypad).
         * State:   should be 0 (successful).
         * Source:  should be 1 or 2 (keypad or fingerprint).
         */
        if (($logAction == 1 || $logAction == 3) && $logTrigger == 255 && $logState == 0 && ($logSource == 1 || $logSource == 2)) {
            // the device webhook log refers to a keypad/fingerprint successfuly authentication

            // attempt to match a previously generated passcode name and authentication ID
            if (empty($data['smartlockLog']['authId']) || !preg_match('/^bid\:([0-9]+)\-([0-9]+)$/', (string) ($data['smartlockLog']['name'] ?? ''), $matches)) {
                // silently abort with a success code in order to not break the webhook response
                throw new Exception('Smartlock log verified with no needed actions.', 200);
            }

            // identify booking ID and booking room ID from authentication code name
            $bookingId = (int) $matches[1];
            $bookingRoomId = (int) $matches[2];

            // obtain the booking details
            $booking = VikBooking::getBookingInfoFromID($bookingId);

            if (!$booking) {
                // silently abort with a success code in order to not break the webhook response
                throw new Exception('Smartlock log verified with no booking details.', 200);
            }

            // wrap the booking information into a registry
            $registry = VBOBookingRegistry::getInstance($booking);

            // obtain the authentication ID
            $authenticationId  = (string) $data['smartlockLog']['authId'];

            // make sure a first access notification for this booking was not already processed
            $history = VikBooking::getBookingHistoryInstance($booking['id']);

            if ($history->hasEvent('FA')) {
                // silently abort with a success code in order to not break the webhook response
                throw new Exception('Smartlock log already processed.', 200);
            }

            // we trust the webhook notification authentication ID to be the one previously generated
            // without performing any API request to match the authentication code saved in the history

            // store booking history record
            VikBooking::getBookingHistoryInstance($registry->getID())
                ->setBookingData($registry->getData(), $registry->getRooms())
                ->setExtraData([
                    'provider' => $this->getProfileProvider(),
                    'profile'  => $this->getProfileID(),
                    'device'   => $device->getID(),
                ])
                ->store('FA', sprintf('%s - %s: %s', (string) $this->getProfileName(), (string) $device->getName(), $authenticationId));

            // store an entry within the notifications center for the successful operation
            VBOFactory::getNotificationCenter()
                ->store([
                    [
                        'sender'  => 'dac',
                        'type'    => 'dac.FA.ok',
                        'title'   => sprintf('%s - %s', (string) $this->getProfileName(), (string) $device->getName()),
                        'summary' => sprintf('%s: %s', JText::translate('VBOBOOKHISTORYTFA'), $authenticationId),
                        'idorder' => $registry->getID(),
                        'avatar'  => preg_match('/^http/', (string) $this->getIcon()) ? $this->getIcon() : null,
                    ],
                ]);

            // terminate and go no further
            return;
        }
    }

    /**
     * Given a date-time string in military format, returns the ISO 8601 / RFC 3339 date.
     * 
     * @param   string  $dateTime   The datetime in military format, eventually with time.
     * 
     * @return  string              The formatted date in UTC timezone.
     */
    private function getDateRFC3339(string $dateTime)
    {
        // construct datetime object
        $dt = new DateTime($dateTime, new DateTimeZone(date_default_timezone_get()));

        // force the timezone to be UTC
        $dt->setTimezone(new DateTimeZone('UTC'));

        // return the formatted date
        return $dt->format('Y-m-d\TH:i:s.v\Z');
    }

    /**
     * HTTP requests towards Nuki require the smartlock ID to be an integer, but
     * for some locks, the value fetched is in hexadecimal format. In order to
     * convert it into decimal format, hence integer, we also need to prefix a
     * number matching the device type to obtain a valid decimal ID for the lock.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device to parse.
     * 
     * @return  int     The decimal smartlock ID.
     */
    private function getDecimalDeviceID(VBODooraccessIntegrationDevice $device)
    {
        // obtain the current device ID
        $deviceId = (string) $device->getID();

        if (!preg_match('/(?=.*[a-fA-F])[0-9a-fA-F]+/', $deviceId)) {
            // no hexadecimal values found within the device ID, hence it's supposingly a decimal value
            return (int) $deviceId;
        }

        // attempt to access the device type
        $devicePayload  = $device->getPayload();
        $deviceTypeInfo = $this->getDeviceTypes($devicePayload['type'] ?? -1);
        $deviceTypeId   = $deviceTypeInfo['type'] ?? 0;

        // prefix the hexadecimal string with exact device type prefix
        $prefixedHex = $deviceTypeId . $deviceId;

        // return the converted decimal ID for the device
        return (int) hexdec($prefixedHex);
    }

    /**
     * Fetches the activity action name from the given code.
     * 
     * @param   int     $code   The activity action code.
     * 
     * @return  string
     */
    private function getActivityAction(int $code)
    {
        $list = [
            1   => 'unlock',
            2   => 'lock',
            3   => 'unlatch',
            4   => 'lock\'n\'go',
            5   => 'lock\'n\'go with unlatch',
            6   => 'activate cm',
            7   => 'deactivate cm',
            208 => 'door warning ajar',
            209 => 'door warning status mismatch',
            224 => 'doorbell recognition (only Opener)',
            240 => 'door opened',
            241 => 'door closed',
            242 => 'door sensor jammed',
            243 => 'firmware update',
            250 => 'door log enabled',
            251 => 'door log disabled',
            252 => 'initialization',
            253 => 'calibration',
            254 => '(activity) log enabled',
            255 => '(activity) log disabled',
        ];

        return $list[$code] ?? '';
    }

    /**
     * Fetches the activity trigger name from the given code.
     * 
     * @param   int     $code   The activity trigger code.
     * 
     * @return  string
     */
    private function getActivityTrigger(int $code)
    {
        $list = [
            0   => 'system (bluetooth)',
            1   => 'manual',
            2   => 'button',
            3   => 'automatic',
            4   => 'web',
            5   => 'app',
            6   => 'auto lock',
            7   => 'external accessory',
            255 => 'keypad',
        ];

        return $list[$code] ?? '';
    }

    /**
     * Fetches the activity state name from the given code.
     * 
     * @param   int     $code   The activity state code.
     * 
     * @return  string
     */
    private function getActivityState(int $code)
    {
        $list = [
            0   => 'Success',
            1   => 'Motor blocked',
            2   => 'Cancelled',
            3   => 'Too recent',
            4   => 'Busy',
            5   => 'Low motor voltage',
            6   => 'Clutch failure',
            7   => 'Motor power failure',
            8   => 'Incomplete',
            9   => 'Rejected',
            10  => 'Rejected night mode',
            254 => 'Other errors',
            255 => 'Unknown error',
        ];

        return $list[$code] ?? '';
    }

    /**
     * Fetches the activity source name from the given code.
     * 
     * @param   int     $code   The activity source code.
     * 
     * @return  string
     */
    private function getActivitySource(int $code)
    {
        $list = [
            0 => 'Default',
            1 => 'Keypad code',
            2 => 'Fingerprint',
        ];

        return $list[$code] ?? '';
    }

    /**
     * Maps the supported device type identifiers with name and prefix.
     * 
     * @param   ?int    $type   Optional device type identifier to fetch.
     * @param   bool    $name   True to get only the device type name.
     * 
     * @return  array|string    Full list, device type array or type string name.
     */
    private function getDeviceTypes(?int $type = null, bool $name = false)
    {
        // list of device types, whose key "type" is equal to the hexadecimal prefix
        $list = [
            0 => [
                'name' => 'Nuki Smartlock 1 or 2',
                'type' => '0',
            ],
            1 => [
                'name' => 'Nuki Box',
                'type' => '1',
            ],
            2 => [
                'name' => 'Nuki Opener',
                'type' => '2',
            ],
            3 => [
                'name' => 'Nuki Smartdoor',
                'type' => '3',
            ],
            4 => [
                'name' => 'Nuki Smartlock 3rd/4th Gen (Basic & Pro)',
                'type' => '4',
            ],
            5 => [
                'name' => 'Nuki Smartlock Ultra',
                'type' => '5',
            ],
        ];

        if (is_null($type)) {
            return $list;
        }

        if (!$name) {
            return $list[$type] ?? [];
        }

        return $list[$type]['name'] ?? '';
    }

    /**
     * Maps the supported passcode type identifiers with name and description.
     * 
     * @param   ?int    $type   Optional passcode type identifier to fetch.
     * @param   bool    $name   True to get only the passcode name.
     * 
     * @return  array|string    Full list, passcode type array or passcode string name.
     */
    private function getPasscodeTypes(?int $type = null, bool $name = false)
    {
        $list = [
            0 => [
                'name' => 'App',
                'descr' => 'Authentication code for the App.',
            ],
            2 => [
                'name' => 'Fob',
                'descr' => 'Authentication code for Fob.',
            ],
            13 => [
                'name' => 'Keypad',
                'descr' => 'Authentication code for the Keypad.',
            ],
        ];

        if (is_null($type)) {
            return $list;
        }

        if (!$name) {
            return $list[$type] ?? [];
        }

        return $list[$type]['name'] ?? '';
    }

    /**
     * Generates a random serial code made of only digits with a given length.
     * The sequence obtained will never contain zeros to support the Nuki Keypad
     * and it will not start with "12".
     * 
     * @param   int     $length     The passcode length.
     * 
     * @return  string
     */
    private function generateRandomPasscode(int $length = 6)
    {
        do {
            $passcode = VikBooking::getCPinInstance()->generateSerialCode($length, ['123456789']);
        } while (substr($passcode, 0, 2) == '12');

        return $passcode;
    }

    /**
     * Creates the HTTP Transporter to establish API connections with Nuki.
     * An integration profile record is supposed to be set before making an HTTP request.
     * 
     * @param   ?array  $options    Optional transporter options.
     * 
     * @return  object              The prepared HTTP transporter object with bearer token.
     * 
     * @throws  Exception
     */
    private function createHTTPTransporter(?array $options = null)
    {
        // access current profile settings
        $settings = $this->getSettings();

        // determine the authentication method: OAuth2 or API Token
        $authMethod = ($settings['authmeth'] ?? '') == 'oauth' ? 'oauth' : 'api_token';

        // access the bearer token depending on the authentication method configured
        $bearerToken = null;

        if ($authMethod === 'api_token') {
            if (empty($settings['api_token'])) {
                throw new Exception('Missing API Token for Web API (settings).', 500);
            }

            // use static token from configuration settings
            $bearerToken = $settings['api_token'];
        } else {
            // authentication method is OAuth2
            if (empty($settings['oauth2_api_key']) || empty($settings['oauth2_api_secret'])) {
                // settings must be configured
                throw new Exception('Missing OAuth2 API Key (Client ID) and OAuth2 API Secret (Client Secret). Please go through settings.', 500);
            }

            // ensure we are not actually using the transporter for authorising the application
            if (empty($options['doing_oauth'])) {
                if (empty($settings['_oauth']['access_token'])) {
                    // application must be authorised
                    throw new Exception('Missing OAuth2 authorisation data for the application. Please go through settings.', 500);
                }

                // get or refresh the access (bearer) token
                $bearerToken = $this->getOauthToken();
            }
        }

        // set HTTP headers
        $this->httpHeaders = [
            'Authorization' => "Bearer {$bearerToken}",
            'Accept' => 'application/json',
        ];

        if (!$bearerToken) {
            // the request should not define a default Authorization header
            unset($this->httpHeaders['Authorization']);
        }

        if (is_array($options['headers'] ?? null)) {
            // merge default headers with the given ones (associative list expected)
            $this->httpHeaders = $this->httpHeaders + $options['headers'];
        }

        return new JHttp;
    }

    /**
     * Obtains an active OAuth (Bearer) token to establish API connections with Nuki.
     * In this case, the authentication method configured should be "OAuth".
     * 
     * @return  string      An active OAuth (Bearer) token.
     * 
     * @throws  Exception
     */
    private function getOauthToken()
    {
        // access current profile settings
        $settings = $this->getSettings();

        if (empty($settings['_oauth']['access_token'])) {
            // application must be authorised
            throw new Exception('Missing OAuth2 authorisation data for the application. Please go through settings.', 500);
        }

        if (($settings['_oauth']['expiry_ts'] ?? 0) < time()) {
            // the token should be renewed because it's expired
            return $this->renewOauthToken();
        }

        // return the supposingly active token
        return (string) $settings['_oauth']['access_token'];
    }

    /**
     * Makes an API request with Nuki to refresh and save the OAuth token for any HTTP request.
     * 
     * @return  string  An active OAuth (Bearer) token ready to be used.
     * 
     * @throws  Exception
     * 
     * @link    https://developer.nuki.io/
     */
    private function renewOauthToken()
    {
        // access current profile settings
        $settings = $this->getSettings();

        if (empty($settings['oauth2_api_key']) || empty($settings['oauth2_api_secret'])) {
            // settings must be configured
            throw new Exception('Missing OAuth2 API Key (Client ID) and OAuth2 API Secret (Client Secret). Please go through settings.', 500);
        }

        if (empty($settings['_oauth']['refresh_token'])) {
            // application must be authorised
            throw new Exception('Missing OAuth2 authorisation data (refresh token) for the application. Please go through authorisation.', 500);
        }

        // build request data
        $data = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $settings['oauth2_api_key'],
            'client_secret' => $settings['oauth2_api_secret'],
            'refresh_token' => $settings['_oauth']['refresh_token'],
        ];

        // exchange the settings to obtain the OAuth token details
        $response = (new JHttp)->post('https://api.nuki.io/oauth/token', http_build_query($data), ['Content-Type' => 'application/x-www-form-urlencoded'], 10);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'OAuth token error.', $response->code);
        }

        if (empty($responseData['access_token'])) {
            // invalid response
            throw new Exception($response->body ?: 'OAuth token refresh response missing access token.', 500);
        }

        // calculate and set the token expiration timestamp
        $responseData['expiry_ts'] = strtotime(sprintf('+%d seconds', (int) ($responseData['expires_in'] ?? 0)));

        // inject OAuth details within the current integration settings
        $settings['_oauth'] = $responseData;

        // update integration record settings
        $this->setProfileRecordProp('settings', $settings);

        // store integration record settings
        VBODooraccessFactory::getInstance()->saveIntegrationRecord($this, ['settings' => $this->getSettings()]);

        // return the current access token
        return (string) $responseData['access_token'];
    }
}
