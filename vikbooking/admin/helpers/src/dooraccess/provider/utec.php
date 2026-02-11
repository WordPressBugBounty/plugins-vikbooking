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
 * Door Access integration provider for U-Tec.
 * 
 * @since   1.18.7 (J) - 1.8.7 (WP)
 * 
 * @link    https://doc.api.u-tec.com/
 */
final class VBODooraccessProviderUtec extends VBODooraccessIntegrationAware
{
    /**
     * @var     array
     */
    private array $httpHeaders = [];

    /**
     * @var     string
     */
    private string $authScopes = 'openapi';

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
        return 'U-tec Smart Home';
    }

    /**
     * @inheritDoc
     */
    public function getShortName()
    {
        return 'U-tec';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VBO_ADMIN_URI . 'resources/u-tec-vikbooking-integration-logo.png';
    }

    /**
     * @inheritDoc
     */
    public function getParams()
    {
        // load current settings
        $settings = $this->getSettings();

        // build the OAuth redirect URL
        $dac_oauth_spawn_url = $this->buildOAuthURL();

        // check if the application was authorised through OAuth
        $oauth_authorised = !empty($settings['_oauth']['access_token']);

        // build OAuth2 authorization link
        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            // settings must be saved first
            $oauth2_auth_link = '<span class="label label-error">Save settings first. OAuth2 API Key and API Secret cannot be empty.</span>';
            // convert the redirect URI to a text
            $dac_oauth_spawn_url = 'Save settings first.';
        } else {
            // build button-link for the authorization code URL
            $utec_auth_data = [
                'response_type' => 'code',
                'client_id'     => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
                'scope'         => $this->authScopes,
                'redirect_uri'  => $dac_oauth_spawn_url,
                'state'         => $this->getOAuthCode(),
            ];

            // build final authorization URL to be clicked
            $utec_auth_link = 'https://oauth.u-tec.com/authorize?' . http_build_query($utec_auth_data);

            // set button content
            $oauth2_auth_link = '<a class="btn btn-warning" href="' . $utec_auth_link . '">' . ($oauth_authorised ? '(Re-)' : '') . 'Authorize Application</a>';
        }

        // build HTML instructions for obtaining the OAuth2 information
        $oauth_instructions_html = <<<HTML
        <div>By using the official U-tec App, visit the section <strong>OpenAPI</strong> to obtain your <strong>Client ID</strong> and <strong>Client Secret</strong>. Then enter the following <strong>Redirect URI</strong>:</div>
        <ul>
            <li><em>$dac_oauth_spawn_url</em></li>
        </ul>
        <div>Save your settings on the U-tec App.</div>
        HTML;

        if (!$oauth_authorised) {
            // add to the instructions that the application must be authorised
            $oauth_instructions_html .= <<<HTML
            <div>Proceed by clicking the authorization link below after saving your account settings from this interface.</div>
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
            '_oauth2_auth_help' => [
                'type'  => 'custom',
                'html'  => $oauth_instructions_html,
            ],
            '_oauth2_auth_status' => [
                'type'  => 'custom',
                'label' => 'Authorization Status',
                'html'  => $oauth2_auth_status,
            ],
            '_oauth2_auth_link' => [
                'type'  => 'custom',
                'label' => 'Authorization Link',
                'html'  => $oauth2_auth_link,
            ],
            'client_id' => [
                'type'  => 'text',
                'label' => 'Client ID',
                'help'  => (empty($settings['client_id']) ? 'Save settings to see the authorization link.' : ''),
            ],
            'client_secret' => [
                'type'  => 'password',
                'label' => 'Client Secret',
                'help'  => (empty($settings['client_id']) ? 'Save settings to see the authorization link. ' : ''),
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
            // missing CSRF proof token
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
                'grant_type'    => 'authorization_code',
                'client_id'     => ($settings['client_id'] ?? ''),
                'client_secret' => ($settings['client_secret'] ?? ''),
                'code'          => $auth_code,
            ];

            // make the API request
            $response = $transporter->get('https://oauth.u-tec.com/token?' . http_build_query($requestData), $this->httpHeaders, 60);

            // obtain response data
            $responseData = (array) json_decode((string) $response->body, true);

            if (empty($response->code) || $response->code > 299) {
                // an error occurred
                throw new Exception($response->body ?: 'Error exchanging the auth code for the OAuth access token.', ($response->code ?: 500));
            }

            if (empty($responseData['access_token']) || empty($responseData['refresh_token'])) {
                // unexpected response format
                throw new Exception(sprintf('Unexpected response format: missing access token or refresh token. %s', (string) ($responseData['error_description'] ?? '')), 500);
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
     * 
     * @todo  Identify the payload received for the various Webhook events.
     *        Validate the payload signature through the registered token.
     *        Identify the type(s) of Webhook events to process, if any.
     */
    public function spawnWebhookCallback(?array $data = null)
    {
        $app = JFactory::getApplication();

        /**
         * @todo  remove webhook payload debugging
         */
        $fp = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'webhook_debug.txt', 'a+');
        fwrite($fp, date('c') . "\n" . print_r(file_get_contents('php://input'), true) . "\n" . print_r($app->input->request->getArray(), true) . "\n" . print_r($app->input->server->getArray(), true) . "\n\n\n");
        fclose($fp);

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
     * @link    https://doc.api.u-tec.com/ Lock User Management - Command: st.lock - unlock
     */
    public function unlockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Command',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                        'command' => [
                            'capability' => 'st.lock',
                            'name' => 'unlock',
                        ],
                    ],
                ],
            ],
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
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
     * @link    https://doc.api.u-tec.com/ Lock User Management - Command: st.lock - lock
     */
    public function lockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Command',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                        'command' => [
                            'capability' => 'st.lock',
                            'name' => 'lock',
                        ],
                    ],
                ],
            ],
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
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
     * @link    https://doc.api.u-tec.com/ Lock User Management - Command: st.lockUser - list/get(id)
     */
    public function listPasscodes(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Command',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                        'command' => [
                            'capability' => 'st.lockUser',
                            'name' => 'list',
                        ],
                    ],
                ],
            ],
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error fetching device passcodes (lock users).', ($response->code ?: 500));
        }

        if (empty($responseData['payload']['devices'][0]['users'])) {
            throw new Exception('No passcodes (lock users) found for the device.', 500);
        }

        // list of lock users returned
        $lockUsersList = (array) $responseData['payload']['devices'][0]['users'];

        // check if a specific passcode name should be matched from the API response (filter not supported)
        $searchPasscode = $options['search'] ?? null;

        // check if the list of lock users needs to be filtered by an exact search term (passcode name)
        if ($searchPasscode && $lockUsersList) {
            // filter lock users (passcodes) found by the given name
            $lockUsersList = array_filter($lockUsersList, function($lockUser) use ($searchPasscode) {
                // lock user (passcode) name must match the given one as we are doing an exact search
                return ($lockUser['name'] ?? '') === $searchPasscode;
            });
        }

        if (!$lockUsersList) {
            throw new Exception('No matching passcodes (lock users) found for the device.', 500);
        }

        // reset array keys
        $lockUsersList = array_values($lockUsersList);

        // parse the lock users list and fetch their details (password)
        foreach ($lockUsersList as &$lockUser) {
            // build request data to obtain all lock-user (password) details
            $data = [
                'header' => [
                    'namespace' => 'Uhome.Device',
                    'name' => 'Command',
                    'messageId' => VBOPerformanceIndicator::uuid(),
                    'payloadVersion' => '1',
                ],
                'payload' => [
                    'devices' => [
                        [
                            'id' => $device->getID(),
                            'command' => [
                                'capability' => 'st.lockUser',
                                'name' => 'get',
                                'arguments' => [
                                    'id' => $lockUser['id'] ?? null,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // make the API request
            $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

            // obtain the response data
            $responseData = (array) json_decode((string) $response->body, true);

            // check if we got a password for the lock-user
            if ($responseData['payload']['devices'][0]['user']['password'] ?? null) {
                // lock user details were read successfully, merge user-lock information
                $lockUser = array_merge($lockUser, $responseData['payload']['devices'][0]['user']);
            }
        }

        // unset last reference
        unset($lockUser);

        // associative list of lock user IDs and related details
        $lockUsersAssoc = [];

        // build HTML output
        $output = '';

        // lang defs
        $lang_passcode  = JText::translate('VBO_PASSCODE');
        $lang_startdate = JText::translate('VBNEWPKGDFROM');
        $lang_enddate   = JText::translate('VBNEWPKGDTO');

        // table head
        $output .= <<<HTML
<div class="vbo-dac-table-wrap">
    <table class="vbo-dac-table">
        <thead>
            <tr>
                <td>Password ID</td>
                <td>Name</td>
                <td>{$lang_passcode}</td>
                <td>{$lang_startdate}</td>
                <td>{$lang_enddate}</td>
                <td>Type</td>
                <td>Status</td>
                <td>Sync Status</td>
            </tr>
        </thead>
        <tbody>
HTML;

        // scan all passcodes obtained
        foreach ($lockUsersList as $lockUser) {
            // set passcode properties
            $lockUserId = $lockUser['id'] ?? '';
            $lockUserName = $lockUser['name'] ?? '';
            $lockUserPwd = $lockUser['password'] ?? '';
            $lockUserPwdFrom = $lockUser['daterange'][0] ?? '';
            $lockUserPwdTo = $lockUser['daterange'][1] ?? '';
            $lockUserType = $this->getUserTypes((int) ($lockUser['type'] ?? 0), true);
            $lockUserStatus = $lockUser['status'] ?? '';
            $lockUserSyncStatus = $lockUser['sync_status'] ?? '';

            // bind passcode id values
            $lockUsersAssoc[$lockUserId] = [
                'name'  => $lockUserName,
                'value' => $lockUserPwd,
            ];

            // build passcode HTML code
            $output .= <<<HTML
            <tr>
                <td><span class="vbo-dac-table-passcode-id">{$lockUserId}</span></td>
                <td><span class="vbo-dac-table-passcode-name">{$lockUserName}</span></td>
                <td><span class="vbo-dac-table-passcode-code">{$lockUserPwd}</span></td>
                <td>{$lockUserPwdFrom}</td>
                <td>{$lockUserPwdTo}</td>
                <td>{$lockUserType}</td>
                <td>{$lockUserStatus}</td>
                <td>{$lockUserSyncStatus}</td>
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
        return (new VBODooraccessDeviceCapabilityResult($lockUsersAssoc))
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
     * @link    https://doc.api.u-tec.com/ Lock User Management - Command: st.lockUser - add(user)
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
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Command',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                        'command' => [
                            'capability' => 'st.lockUser',
                            'name' => 'add',
                            'arguments' => [
                                'name' => $options['pwdname'] ?? null,
                                // type = 2 means "temporary user"
                                'type' => 2,
                                'password' => (int) $options['pwdvalue'],
                                'daterange' => [
                                    date('Y-m-d H:i', strtotime($options['startdate'] ?? date('Y-m-d H:i:s'))),
                                    date('Y-m-d H:i', strtotime($options['enddate'] ?? date('Y-m-d H:i:s'))),
                                ],
                                'weeks' => [0, 1, 2, 4, 5, 6],
                                'timerange' => ['00:00', '23:59'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if (empty($options['startdate']) || empty($options['enddate'])) {
            // unset the validity date-range and related properties when no dates provided
            unset(
                $data['payload']['devices'][0]['command']['arguments']['daterange'],
                $data['payload']['devices'][0]['command']['arguments']['weeks'],
                $data['payload']['devices'][0]['command']['arguments']['timerange']
            );
        }

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
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

        if ($responseData['payload']['devices'][0]['error']['message'] ?? null) {
            // erroneous response
            // an error occurred, build DAC Exception with retry-data
            $dacError = (new VBODooraccessException(sprintf('(%s) %s', ($responseData['payload']['devices'][0]['error']['code'] ?? '0'), $responseData['payload']['devices'][0]['error']['message']), 500))
                ->setDevice($device)
                ->setRetryCallback('createCustomPasscode')
                ->setRetryData($options);

            // throw error
            throw $dacError;
        }

        // build result properties to bind
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
     * @link    https://doc.api.u-tec.com/ Lock User Management - Command: st.lockUser - delete(id)
     */
    public function deletePasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        if (empty($options['pwdid'])) {
            throw new Exception($response->body ?: 'Missing lock user (passcode) ID to delete.', 400);
        }

        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Command',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                        'command' => [
                            'capability' => 'st.lockUser',
                            'name' => 'delete',
                            'arguments' => [
                                'id' => $options['pwdid'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error deleting lock user (passcode) from device.', ($response->code ?: 500));
        }

        if ($responseData['payload']['devices'][0]['error']['message'] ?? null) {
            // erroneous response
            throw new Exception(sprintf('(%s) %s', ($responseData['payload']['devices'][0]['error']['code'] ?? '0'), $responseData['payload']['devices'][0]['error']['message']), 500);
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
     * @link    https://doc.api.u-tec.com/ Lock User Management - Command: st.lockUser - update(user)
     */
    public function updatePasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        if (empty($options['pwdid'])) {
            throw new Exception($response->body ?: 'Missing lock user (passcode) ID to update.', 400);
        }

        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Command',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                        'command' => [
                            'capability' => 'st.lockUser',
                            'name' => 'update',
                            'arguments' => [
                                'id' => $options['pwdid'],
                                'password' => $options['pwdvalue'] ?? null,
                                'daterange' => [
                                    date('Y-m-d H:i', strtotime($options['startdate'] ?? date('Y-m-d H:i:s'))),
                                    date('Y-m-d H:i', strtotime($options['enddate'] ?? date('Y-m-d H:i:s'))),
                                ],
                                'weeks' => [0, 1, 2, 4, 5, 6],
                                'timerange' => ['00:00', '23:59'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if (empty($options['pwdvalue'])) {
            // unset the password property if the same passcode should be kept
            unset($data['payload']['devices'][0]['command']['arguments']['password']);
        }

        if (empty($options['startdate']) || empty($options['enddate'])) {
            // unset the validity date-range and related properties to keep the existing value
            unset(
                $data['payload']['devices'][0]['command']['arguments']['daterange'],
                $data['payload']['devices'][0]['command']['arguments']['weeks'],
                $data['payload']['devices'][0]['command']['arguments']['timerange']
            );
        }

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error updating lock user (passcode) on device.', ($response->code ?: 500));
        }

        if ($responseData['payload']['devices'][0]['error']['message'] ?? null) {
            // erroneous response
            throw new Exception(sprintf('(%s) %s', ($responseData['payload']['devices'][0]['error']['code'] ?? '0'), $responseData['payload']['devices'][0]['error']['message']), 500);
        }

        return (new VBODooraccessDeviceCapabilityResult)
            ->setText(JText::sprintf('VBO_PASSCODE_UPD_OK_DEVICE', $device->getName()));
    }

    /**
     * Device capability implementation to check the current device status, and eventually update the battery level.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @throws  Exception
     * 
     * @link    https://doc.api.u-tec.com/ Query Device Status
     */
    public function checkStatus(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Query',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'devices' => [
                    [
                        'id' => $device->getID(),
                    ],
                ],
            ],
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error checking device status.', ($response->code ?: 500));
        }

        if (empty($responseData['payload']['devices'][0]['states'])) {
            throw new Exception('No device status information obtained. The device may not be reachable, or may not be connected to the Internet.', 500);
        }

        // list of lock states
        $lockStatesList = (array) $responseData['payload']['devices'][0]['states'];

        // access the current device battery-level information, if any
        $batteryInfo = null;
        $devicePayload = $device->getPayload();
        if (($devicePayload['attributes']['batteryLevelRange']['max'] ?? null)) {
            // assign the device battery-level information
            $batteryInfo = $devicePayload['attributes']['batteryLevelRange'];
        }

        // iterate all lock state properties (statuses) to check if we have the battery level
        foreach ($lockStatesList as $lockStatus) {
            if ($batteryInfo && ($lockStatus['capability'] ?? '') === 'st.batteryLevel' && isset($lockStatus['value'])) {
                // calculate the device batter level
                $currentLevelPcent = round(100 * (int) $lockStatus['value'] / (((int) $batteryInfo['max']) ?: 1), 0);

                // update device battery level
                $device->setBatteryLevel((float) $currentLevelPcent);

                // update current device
                try {
                    VBODooraccessFactory::getInstance()->saveIntegrationRecord($this, ['devices' => $this->getDevices()]);
                } catch (Exception $e) {
                    // silently catch the error and do nothing
                }

                // do not proceed any further
                break;
            }
        }

        // build HTML output
        $output = '';

        // lang defs
        $lang_passcode  = JText::translate('VBO_PASSCODE');
        $lang_startdate = JText::translate('VBNEWPKGDFROM');
        $lang_enddate   = JText::translate('VBNEWPKGDTO');

        // table head
        $output .= <<<HTML
<div class="vbo-dac-table-wrap">
    <table class="vbo-dac-table">
        <thead>
            <tr>
                <td>Name</td>
                <td>Value</td>
                <td>Capability</td>
            </tr>
        </thead>
        <tbody>
HTML;

        // scan all statuses obtained
        foreach ($lockStatesList as $lockStatus) {
            // set passcode properties
            $lockStatusName = $lockStatus['name'] ?? '';
            $lockStatusValue = $lockStatus['value'] ?? '';
            $lockStatusCap = $lockStatus['capability'] ?? '';

            // build passcode HTML code
            $output .= <<<HTML
            <tr>
                <td><span class="vbo-dac-table-passcode-name">{$lockStatusName}</span></td>
                <td><span class="vbo-dac-table-passcode-code">{$lockStatusValue}</span></td>
                <td><span class="vbo-dac-table-passcode-id">{$lockStatusCap}</span></td>
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
        return (new VBODooraccessDeviceCapabilityResult($lockStatesList))
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
             * lock user ID to be used for deleting it immediately. We need to "search" the lock user by name.
             */

            // get the previous passcode name
            $previousPasscodeName = ($previousData['props']['name'] ?? '');

            if (empty($previousPasscodeName) || in_array($previousPasscodeName, $previousPasscodeNamings)) {
                // no lock-user name to search and delete, or already deleted
                continue;
            }

            // push processed passcode name
            $previousPasscodeNamings[] = $previousPasscodeName;

            try {
                // attempt to find the passcode on this device by name, which includes booking and listing IDs
                $findResult = $this->listPasscodes($device, [
                    // inject search property to match this exact lock-user (passcode) name
                    'search' => sprintf('bid:%d-%d', $registry->getID(), $listingId),
                ]);

                if (!$findResult->getProperties()) {
                    // lock-user not found by name
                    throw new Exception('Previous lock-user not found by name.', 404);
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
                // inject search property to match this exact lock-user (passcode) name
                'search' => sprintf('bid:%d-%d', $registry->getID(), $listingId),
            ]);

            if (!$findResult->getProperties()) {
                // lock-user (passcode) not found
                throw new Exception('Previous lock-user (passcode) not found.', 404);
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
     * @link    https://doc.api.u-tec.com/ Retrieve Device List
     */
    protected function fetchRemoteDevices()
    {
        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Device',
                'name' => 'Discovery',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => new stdClass,
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error fetching the remove devices.', ($response->code ?: 500));
        }

        if (empty($responseData['payload']['devices'])) {
            throw new Exception('No devices found under the current account.', 500);
        }

        /**
         * We should register the Notification URL at this point to allow
         * Webhook Notifications to be delivered by U-tec for any device.
         */
        $this->registerNotificationURL();

        // return the list of devices fetched
        return (array) $responseData['payload']['devices'];
    }

    /**
     * @inheritDoc
     */
    protected function decorateDeviceProperties(VBODooraccessIntegrationDevice $decorator, array $device)
    {
        // set device ID (device MAC address)
        $decorator->setID(($device['id'] ?? ''), $raw = true);

        // set device name
        $decorator->setName($device['name'] ?? '');

        // set device description
        $decorator->setDescription(implode(' - ', array_filter([
            $device['category'] ?? '',
            $device['handleType'] ?? '',
        ])));

        // set device icon
        if (!strcasecmp(($device['category'] ?? ''), 'LIGHT')) {
            // not a smartlock, but a light
            $decorator->setIcon('<i class="' . VikBookingIcons::i('lightbulb') . '"></i>');
        } elseif (stripos(($device['category'] ?? ''), 'plug') !== false) {
            // not a smartlock, but a smart-plug
            $decorator->setIcon('<i class="' . VikBookingIcons::i('plug') . '"></i>');
        } else {
            // default to smartlock
            $decorator->setIcon('<i class="' . VikBookingIcons::i('fingerprint') . '"></i>');
        }

        // set device model
        $decorator->setModel($device['deviceInfo']['model'] ?? '');
        $decorator->setModel(implode(' - ', array_filter([
            $device['deviceInfo']['manufacturer'] ?? '',
            $device['deviceInfo']['model'] ?? '',
        ])));

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
                        'help'    => JText::translate('VBO_PASSCODE_EMPTY_HELP') . ' 4-8 digits (1-9, 6 digits by default), should not start with 12 and should not contain 0.',
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
                        'help'  => 'The password ID to delete. List all passcodes to find it.',
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
                        'help'  => 'The password ID to update. List all passcodes to find it.',
                    ],
                    'pwdvalue' => [
                        'type'    => 'text',
                        'label'   => JText::translate('VBO_PASSCODE'),
                        'help'    => 'Leave empty to keep the existing password.',
                        'attributes' => [
                            'pattern' => '^(?!12)[1-9]{6}$',
                        ],
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
            // check (query) device status
            $this->createDeviceCapability([
                'id'          => 'device_status',
                'title'       => JText::translate('VBSTATUS'),
                'description' => 'Check the current device status.',
                'icon'        => '<i class="' . VikBookingIcons::i('signal') . '"></i>',
                'callback'    => 'checkStatus',
            ]),
        ]);

        // set device payload
        $decorator->setPayload($device);
    }

    /**
     * Registers the Notification URL to receive Webhook notifications from U-tec.
     * 
     * @param   bool    $updateToken    True to generate a new verification token.
     * 
     * @return  void
     * 
     * @throws  Exception
     * 
     * @todo    they need an "access_token": at the moment we are generating a random token
     *          internally to pass it to U-tec for the registration. They suggest to change
     *          it periodically, so we are guessing they don't want the OAuth2 access token
     *          that would be obtained through $this->getOauthToken(). However, their response
     *          payload property was empty in both cases.
     */
    protected function registerNotificationURL(bool $updateToken = false)
    {
        // access the integration settings
        $settings = $this->getSettings();

        // obtain current webhook token
        $webhookToken = $settings['_webhook']['token'] ?? null;

        if ($webhookToken && !$updateToken) {
            // the notification URL was already registered with success
            return;
        }

        if (!$webhookToken || $updateToken) {
            // generate a new token
            $webhookToken = VikBooking::getCPinInstance()->generateSerialCode(32, [
                'abcdefghijklmnopqrstuvwxyz',
                '0123456789',
            ]);
        }

        // start transporter (by setting the Content-Type header)
        $transporter = $this->createHTTPTransporter([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // build request data
        $data = [
            'header' => [
                'namespace' => 'Uhome.Configure',
                'name' => 'Set',
                'messageId' => VBOPerformanceIndicator::uuid(),
                'payloadVersion' => '1',
            ],
            'payload' => [
                'configure' => [
                    'notification' => [
                        // this is a value that will be used to sign the Webhook requests
                        'access_token' => $webhookToken,
                        // webhook endpoint URL for the current integration profile
                        'url' => $this->buildWebhookURL(),
                    ],
                ],
            ],
        ];

        // make the API request
        $response = $transporter->post('https://api.u-tec.com/action', json_encode($data), $this->httpHeaders, 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if (empty($response->code) || $response->code > 299) {
            // an error occurred
            throw new Exception($response->body ?: 'Error registering the notification URL.', ($response->code ?: 500));
        }

        // update settings to identify the webhook activation
        $webhookDetails = [
            'token'       => $webhookToken,
            'creation_ts' => time(),
        ];

        // merge webhook details with any possible value obtained within the response
        $webhookDetails = array_merge((array) ($responseData['payload'] ?? []), $webhookDetails);

        // inject webhook details within the current integration settings
        $settings['_webhook'] = $webhookDetails;

        // update integration record settings
        $this->setProfileRecordProp('settings', $settings);

        // store integration record settings
        VBODooraccessFactory::getInstance()->saveIntegrationRecord($this, ['settings' => $this->getSettings()]);
    }

    /**
     * Maps the supported user type identifiers with name.
     * 
     * @param   ?int    $type   Optional user type identifier to fetch.
     * @param   bool    $name   True to get only the user type name.
     * 
     * @return  array|string    Full list, user type array or type string name.
     */
    private function getUserTypes(?int $type = null, bool $name = false)
    {
        $list = [
            0 => [
                'name' => 'Normal User',
            ],
            1 => [
                'name' => 'User',
            ],
            2 => [
                'name' => 'Temporary User',
            ],
            3 => [
                'name' => 'Admin',
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
     * The sequence obtained will never contain zeros for integer requirements
     * and it will not start with "12" for better randomness.
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
     * Creates the HTTP Transporter to establish API connections with U-tec.
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

        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            // settings must be configured
            throw new Exception('Missing OAuth2 API Key (Client ID) and OAuth2 API Secret (Client Secret). Please go through settings.', 500);
        }

        // access bearer token
        $bearerToken = null;

        // ensure we are not actually using the transporter for authorising the application
        if (empty($options['doing_oauth'])) {
            if (empty($settings['_oauth']['access_token'])) {
                // application must be authorised
                throw new Exception('Missing OAuth2 authorisation data for the application. Please go through settings.', 500);
            }

            // get or refresh the access (bearer) token
            $bearerToken = $this->getOauthToken();
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
     * Obtains an active OAuth (Bearer) token to establish API connections with U-tec.
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
     * Makes an API request with U-tec to refresh and save the OAuth token for any HTTP request.
     * 
     * @return  string  An active OAuth (Bearer) token ready to be used.
     * 
     * @throws  Exception
     */
    private function renewOauthToken()
    {
        // access current profile settings
        $settings = $this->getSettings();

        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
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
            'client_id'     => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'refresh_token' => $settings['_oauth']['refresh_token'],
        ];

        // exchange the settings to obtain the OAuth token details
        $response = (new JHttp)->post('https://oauth.u-tec.com/token', http_build_query($data), ['Content-Type' => 'application/x-www-form-urlencoded'], 10);

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
