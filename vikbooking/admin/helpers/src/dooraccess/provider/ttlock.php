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
 * Door Access integration provider for TTLock.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 * 
 * @link    https://euopen.ttlock.com/document/doc?urlName=userGuide%2FekeyEn.html
 */
final class VBODooraccessProviderTtlock extends VBODooraccessIntegrationAware
{
    /**
     * @var     ?string
     */
    private ?string $oauthToken = null;

    /**
     * @var     array
     */
    private array $httpHeaders = [];

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
        return 'TTLock - Smart Locks';
    }

    /**
     * @inheritDoc
     */
    public function getShortName()
    {
        return 'TTLock';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VBO_ADMIN_URI . 'resources/ttlock-vikbooking-integration-logo.png';
    }

    /**
     * @inheritDoc
     */
    public function getParams()
    {
        return [
            'ai' => [
                'type'    => 'checkbox',
                'label'   => JText::translate('VBO_AI_SUPPORT'),
                'help'    => JText::translate('VBO_DAC_AI_SUPPORT_HELP'),
                'default' => 1,
            ],
            'firstaccess_notif' => [
                'type'    => 'checkbox',
                'label'   => JText::translate('VBO_NOTIFY_FIRST_ACCESS'),
                'help'    => JText::translate('VBO_NOTIFY_FIRST_ACCESS_HELP'),
                'default' => 0,
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
            'client_id' => [
                'type'  => 'text',
                'label' => 'Client ID',
            ],
            'client_secret' => [
                'type'  => 'password',
                'label' => 'Client Secret',
            ],
            'username' => [
                'type'  => 'text',
                'label' => 'Username',
            ],
            'password' => [
                'type'  => 'password',
                'label' => 'Password',
            ],
        ];
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
     * @inheritDoc
     */
    public function canWatchFirstAccess()
    {
        // this method is called when the integration has loaded its profile record
        // we return true only if the apposite setting is enabled

        $settings = $this->getSettings();

        return !empty($settings['firstaccess_notif']);
    }

    /**
     * Device capability implementation to unlock a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Fgateway%2FunlockEn.html
     */
    public function unlockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        // build request data
        $data = [
            'clientId'    => $settings['client_id'],
            'accessToken' => $settings['_oauth']['access_token'],
            'lockId'      => $device->getID(),
            'date'        => time() . '000',
        ];

        // make the API request
        $response = $transporter->post('https://euapi.ttlock.com/v3/lock/unlock', $data, [], 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if ($response->code != 200 || !empty($responseData['errcode'])) {
            // an error occurred
            throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error unlocking the device.', ($response->code != 200 ? $response->code : 500));
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
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Fgateway%2FunlockEn.html
     */
    public function lockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        // build request data
        $data = [
            'clientId'    => $settings['client_id'],
            'accessToken' => $settings['_oauth']['access_token'],
            'lockId'      => $device->getID(),
            'date'        => time() . '000',
        ];

        // make the API request
        $response = $transporter->post('https://euapi.ttlock.com/v3/lock/lock', $data, [], 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if ($response->code != 200 || !empty($responseData['errcode'])) {
            // an error occurred
            throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error locking the device.', ($response->code != 200 ? $response->code : 500));
        }

        return (new VBODooraccessDeviceCapabilityResult)->setText(sprintf('The device "%s" was locked!', $device->getName()));
    }

    /**
     * Device capability implementation to list a device passcodes.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @throws  Exception
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Fpasscode%2FlistEn.html
     */
    public function listPasscodes(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        $passcodes = [];

        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        // request page settings
        $pageNo = 1;
        $pageSize = 100;
        $reqCount = 0;
        $reqMax = 5;

        // start a loop to support pagination
        while (true) {
            if ($reqCount >= $reqMax) {
                // too many requests
                break;
            }

            // build query string data
            $data = [
                'clientId'    => $settings['client_id'],
                'accessToken' => $settings['_oauth']['access_token'],
                'lockId'      => $device->getID(),
                'searchStr'   => $options['search'] ?? null,
                'pageNo'      => $pageNo,
                'pageSize'    => $pageSize,
                'orderBy'     => 1,
                'date'        => time() . '000',
            ];

            // make a request to obtain all created passcodes of a lock
            $response = $transporter->get('https://euapi.ttlock.com/v3/lock/listKeyboardPwd?' . http_build_query($data), [], 20);

            // obtain the response data
            $responseData = (array) json_decode((string) $response->body, true);

            if ($response->code != 200 || !empty($responseData['errcode'])) {
                // an error occurred
                throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error fetching device passcodes.', ($response->code != 200 ? $response->code : 500));
            }

            // increase request counter
            $reqCount++;

            if (!empty($responseData['list'])) {
                $passcodes = array_merge($passcodes, $responseData['list']);
            }

            if (($responseData['pages'] ?? 0) > $pageNo) {
                // go to the next loop
                $pageNo++;

                continue;
            }

            // all passcodes were read
            break;
        }

        if (!$passcodes) {
            throw new Exception('No passcodes found for the device.', 404);
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
        $lang_custom    = JText::translate('VBO_CUSTOM');

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
                <td>{$lang_custom}</td>
            </tr>
        </thead>
        <tbody>
HTML;

        // scan all passcodes obtained
        foreach ($passcodes as $passcode) {
            // set passcode properties
            $passcodeId = $passcode['keyboardPwdId'] ?? '';
            $passcodeValue = $passcode['keyboardPwd'] ?? '';
            $passcodeName = $passcode['keyboardPwdName'] ?? '';
            $passcodeType = $this->getPasscodeTypes((int) ($passcode['keyboardPwdType'] ?? 0), true);
            $startDate = !empty($passcode['startDate']) ? date('Y-m-d H:i:s', ($passcode['startDate'] ?: 1000) / 1000) : '---';
            $endDate = !empty($passcode['endDate']) ? date('Y-m-d H:i:s', ($passcode['endDate'] ?: 1000) / 1000) : '---';
            $sendDate = !empty($passcode['sendDate']) ? date('Y-m-d H:i:s', ($passcode['sendDate'] ?: 1000) / 1000) : '---';
            $senderUsername = $passcode['senderUsername'] ?? '';
            $isCustom = ($passcode['isCustom'] ?? 0) == 1 ? JText::translate('VBYES') : JText::translate('VBNO');

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
                <td>{$isCustom}</td>
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
     * Device capability implementation to create a random passcode for a device (generated by TTLock).
     * Notice that the random passcode generation of TTLock is based on the password type and the validity dates.
     * This means that deleting a passcode for a device that was valid on certain dates, will trigger an error
     * if another random passcode is generated for the same device and validity dates. It is, therefore, unsafe
     * to rely on this passcode generation method when passcodes are generated at the time of booking, because
     * modifications or cancellations may occur and a new re-generation of passcode may be requested for new bookings.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Fpasscode%2FgetEn.html
     */
    public function createRandomPasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        // build request data
        $data = [
            'clientId'        => $settings['client_id'],
            'accessToken'     => $settings['_oauth']['access_token'],
            'lockId'          => $device->getID(),
            'keyboardPwdType' => (int) ($options['pwdtype'] ?? 3),
            'keyboardPwdName' => $options['pwdname'] ?? null,
            'startDate'       => strtotime($options['startdate'] ?? date('Y-m-d H:i:s')) . '000',
            'endDate'         => strtotime($options['enddate'] ?? date('Y-m-d H:i:s')) . '000',
            'date'            => time() . '000',
        ];

        // make the API request
        $response = $transporter->post('https://euapi.ttlock.com/v3/keyboardPwd/get', $data, [], 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if ($response->code != 200 || !empty($responseData['errcode'])) {
            // an error occurred
            throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error getting a random passcode for the device.', ($response->code != 200 ? $response->code : 500));
        }

        // build result properties to bind
        $resultProps = [
            'keyboardPwd'   => (string) ($responseData['keyboardPwd'] ?? ''),
            'keyboardPwdId' => (string) ($responseData['keyboardPwdId'] ?? ''),
            'listingId'     => (int) ($options['listing_id'] ?? 0),
        ];

        // get the listing name, if available
        $listingName = '';
        if (!empty($resultProps['listingId'])) {
            $listingData = VikBooking::getRoomInfo($resultProps['listingId'], ['name'], true);
            $listingName = sprintf('%s: ', $listingData['name'] ?? '');
        }

        // wrap and return the device capability result object
        return (new VBODooraccessDeviceCapabilityResult($resultProps))
            ->setPasscode($resultProps['keyboardPwd'])
            ->setText($listingName . JText::sprintf('VBO_PASSCODE_GEN_OK_DEVICE', $resultProps['keyboardPwd'], $device->getName()));
    }

    /**
     * Device capability implementation to create a custom passcode for a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Fpasscode%2FaddEn.html
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP) API errors will throw a Door Access Exception with retry-data.
     */
    public function createCustomPasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        if (empty($options['pwdvalue'])) {
            // passcode value cannot be empty
            $options['pwdvalue'] = $this->generateRandomPasscode();
        }

        // build request data
        $data = [
            'clientId'        => $settings['client_id'],
            'accessToken'     => $settings['_oauth']['access_token'],
            'lockId'          => $device->getID(),
            'keyboardPwd'     => (int) $options['pwdvalue'],
            'keyboardPwdName' => $options['pwdname'] ?? null,
            'keyboardPwdType' => (int) ($options['pwdtype'] ?? 3),
            'startDate'       => strtotime($options['startdate'] ?? date('Y-m-d H:i:s')) . '000',
            'endDate'         => strtotime($options['enddate'] ?? date('Y-m-d H:i:s')) . '000',
            'addType'         => 2,
            'date'            => time() . '000',
        ];

        // make the API request
        $response = $transporter->post('https://euapi.ttlock.com/v3/keyboardPwd/add', $data, [], 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if ($response->code != 200 || !empty($responseData['errcode'])) {
            // an error occurred, build DAC Exception with retry-data
            $dacError = (new VBODooraccessException($responseData['errmsg'] ?? $response->body ?: 'Error adding a custom passcode to the device.', ($response->code != 200 ? $response->code : 500)))
                ->setDevice($device)
                ->setRetryCallback('createCustomPasscode')
                ->setRetryData($options);

            // throw error
            throw $dacError;
        }

        // build result properties to bind
        $resultProps = [
            'keyboardPwd'   => (string) $options['pwdvalue'],
            'keyboardPwdId' => (string) ($responseData['keyboardPwdId'] ?? ''),
            'listingId'     => (int) ($options['listing_id'] ?? 0),
        ];

        // get the listing name, if available
        $listingName = '';
        if (!empty($resultProps['listingId'])) {
            $listingData = VikBooking::getRoomInfo($resultProps['listingId'], ['name'], true);
            $listingName = sprintf('%s: ', $listingData['name'] ?? '');
        }

        // wrap and return the device capability result object
        return (new VBODooraccessDeviceCapabilityResult($resultProps))
            ->setPasscode($resultProps['keyboardPwd'])
            ->setText($listingName . JText::sprintf('VBO_PASSCODE_GEN_OK_DEVICE', $resultProps['keyboardPwd'], $device->getName()));
    }

    /**
     * Device capability implementation to delete a passcode from a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Fpasscode%2FdeleteEn.html
     */
    public function deletePasscode(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        // build request data
        $data = [
            'clientId'      => $settings['client_id'],
            'accessToken'   => $settings['_oauth']['access_token'],
            'lockId'        => $device->getID(),
            'keyboardPwdId' => $options['pwdid'] ?? null,
            'deleteType'    => 2,
            'date'          => time() . '000',
        ];

        // make the API request
        $response = $transporter->post('https://euapi.ttlock.com/v3/keyboardPwd/delete', $data, [], 60);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if ($response->code != 200 || !empty($responseData['errcode'])) {
            // an error occurred
            throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error deleting passcode from device.', ($response->code != 200 ? $response->code : 500));
        }

        return (new VBODooraccessDeviceCapabilityResult)
            ->setText(JText::sprintf('VBO_PASSCODE_DEL_OK_DEVICE', $device->getName()));
    }

    /**
     * Device capability implementation to show the list of activity logs (unlock records) of a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     * 
     * @throws  Exception
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2FlockRecord%2FlistEn.html
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function showActivityLogs(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        $activities = [];

        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain integration settings after initializing the transporter
        $settings = $this->getSettings();

        // request page settings
        $pageNo = 1;
        $pageSize = 150;
        $reqCount = 0;
        $reqMax = 5;

        // start a loop to support pagination
        while (true) {
            if ($reqCount >= $reqMax) {
                // too many requests
                break;
            }

            // build query string data
            $data = [
                'clientId'    => $settings['client_id'],
                'accessToken' => $settings['_oauth']['access_token'],
                'lockId'      => $device->getID(),
                'startDate'   => (!empty($options['startdate']) ? strtotime($options['startdate']) . '000' : null),
                'endDate'     => (!empty($options['enddate']) ? strtotime($options['enddate']) . '000' : null),
                'pageNo'      => $pageNo,
                'pageSize'    => $pageSize,
                'recordType'  => (!empty($options['recordtype']) ? (int) $options['recordtype'] : null),
                'searchStr'   => $options['search'] ?? null,
                'date'        => time() . '000',
            ];

            // make a request to obtain the unlock records of a lock
            $response = $transporter->get('https://euapi.ttlock.com/v3/lockRecord/list?' . http_build_query($data), [], 20);

            // obtain the response data
            $responseData = (array) json_decode((string) $response->body, true);

            if ($response->code != 200 || !empty($responseData['errcode'])) {
                // an error occurred
                throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error fetching device unlock records.', ($response->code != 200 ? $response->code : 500));
            }

            // increase request counter
            $reqCount++;

            if (!empty($responseData['list'])) {
                $activities = array_merge($activities, $responseData['list']);
            }

            if (($responseData['pages'] ?? 0) > $pageNo) {
                // go to the next loop
                $pageNo++;

                continue;
            }

            // all records were read
            break;
        }

        if (!$activities) {
            throw new Exception('No unlock records found for the device.', 404);
        }

        // build HTML output
        $output = '';

        // lang defs
        $lang_passcode  = JText::translate('VBO_PASSCODE');
        $lang_type      = JText::translate('VBPSHOWSEASONSTHREE');
        $lang_status    = JText::translate('VBSTATUS');
        $lang_createdon = JText::translate('VBOINVCREATIONDATE');
        $lang_createdby = JText::translate('VBCSVCREATEDBY');

        // table head
        $output .= <<<HTML
<div class="vbo-dac-table-wrap">
    <table class="vbo-dac-table">
        <thead>
            <tr>
                <td>ID</td>
                <td>{$lang_passcode}</td>
                <td>{$lang_type}</td>
                <td>{$lang_status}</td>
                <td>{$lang_createdon}</td>
                <td>{$lang_createdby}</td>
            </tr>
        </thead>
        <tbody>
HTML;

        // scan all activites obtained
        foreach ($activities as $activity) {
            // set activity properties
            $activityId = $activity['recordId'] ?? '';
            $passcodeValue = $activity['keyboardPwd'] ?? '---';
            $activityType = $this->getActivityRecordTypes((int) ($activity['recordType'] ?? $activity['recordTypeFromLock'] ?? 0));
            $isSuccess = ($activity['success'] ?? 0) == 1 ? JText::translate('VBYES') : JText::translate('VBNO');
            $createdOn = !empty($activity['lockDate']) ? date('Y-m-d H:i:s', ($activity['lockDate'] / 1000)) : '';
            $createdOn = empty($createdOn) && !empty($activity['serverDate']) ? date('Y-m-d H:i:s', ($activity['serverDate'] / 1000)) : $createdOn;
            $createdOn = $createdOn ?: '---';
            $createdBy = $activity['username'] ?? '---';

            // build passcode HTML code
            $output .= <<<HTML
            <tr>
                <td><span class="vbo-dac-table-passcode-id">{$activityId}</span></td>
                <td><span class="vbo-dac-table-passcode-code">{$passcodeValue}</span></td>
                <td>{$activityType}</td>
                <td>{$isSuccess}</td>
                <td>{$createdOn}</td>
                <td>{$createdBy}</td>
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
            // generate custom, yet random, passcode value of 8 digits for this device
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
            // process the modification as a new door access creation (with TTLock random passcode)
            return $this->createBookingDoorAccess($device, $listingId, $registry);
        }

        // scan all previously created passcodes in DESC order on this device and delete them
        $previousPasscodeIds = [];
        foreach (array_reverse($previousDevicePasscodes) as $previousData) {
            // ensure we only have array values
            $previousData = (array) json_decode(json_encode($previousData), true);

            // get the previous passcode
            $previousPasscode = ($previousData['passcode'] ?? '') ?: ($previousData['props']['keyboardPwdId'] ?? '');

            if (empty($previousPasscode) || in_array($previousPasscode, $previousPasscodeIds)) {
                // no passcode ID to delete, or already deleted
                continue;
            }

            // push processed passcode ID
            $previousPasscodeIds[] = $previousPasscode;

            try {
                // delete previous passcode for this booking
                $this->deletePasscode($device, [
                    'pwdid' => $previousPasscode,
                ]);
            } catch (Exception $e) {
                // do nothing on error
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
        return $resultProperties['keyboardPwd'] ?? null;
    }

    /**
     * @inheritDoc
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function detectFirstAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // the expected passcode name to match in the activities "username"
        $matchPwdName = sprintf('bid:%d-%d', $registry->getID(), $listingId);
        try {
            // access the activity logs for the booking stay dates in the current device
            $findResult = $this->showActivityLogs($device, [
                'startdate' => date('Y-m-d 00:00:00', $registry->getProperty('checkin', 0)),
                'enddate'   => date('Y-m-d 23:59:59', $registry->getProperty('checkout', 0)),
            ]);

            if (!$findResult->getProperties()) {
                // no activities found
                throw new Exception('No activities found.', 404);
            }
        } catch (Exception $e) {
            // nothing useful was detected, but prevent unwanted errors to be thrown
            return null;
        }

        // iterate all activities found
        foreach ($findResult->getProperties() as $activity) {
            if (($activity['username'] ?? '') == $matchPwdName) {
                // booking access found within the device activity logs for matching passcode name
                $createdOn = !empty($activity['lockDate']) ? date('Y-m-d H:i:s', ($activity['lockDate'] / 1000)) : '';
                $createdOn = empty($createdOn) && !empty($activity['serverDate']) ? date('Y-m-d H:i:s', ($activity['serverDate'] / 1000)) : $createdOn;

                // return the capability result with the matching activity
                return (new VBODooraccessDeviceCapabilityResult($activity))
                    ->setPasscode($activity['keyboardPwd'] ?? '')
                    ->setText(sprintf('%s (%s)', ($activity['keyboardPwd'] ?? ''), $createdOn));
            }
        }

        // if nothing is found, scan booking registry DAC passcodes data as fallback
        foreach ($registry->getDACProperty($this->getAlias(), 'passcodes_data', []) as $passcodeData) {
            // get the passcode value
            $passcodeValue = is_array($passcodeData) ? $this->getPasscodeFromHistoryResult($passcodeData) : $passcodeData;

            if (!is_string($passcodeValue) || !$passcodeValue) {
                // no passcode to look for
                continue;
            }

            foreach ($findResult->getProperties() as $activity) {
                if (($activity['keyboardPwd'] ?? '') == $passcodeValue) {
                    // booking access found within the device activity logs for matching passcode value
                    $createdOn = !empty($activity['lockDate']) ? date('Y-m-d H:i:s', ($activity['lockDate'] / 1000)) : '';
                    $createdOn = empty($createdOn) && !empty($activity['serverDate']) ? date('Y-m-d H:i:s', ($activity['serverDate'] / 1000)) : $createdOn;

                    // return the capability result with the matched property
                    return (new VBODooraccessDeviceCapabilityResult($activity))
                        ->setPasscode($activity['keyboardPwd'] ?? '')
                        ->setText(sprintf('%s (%s)', ($activity['keyboardPwd'] ?? ''), $createdOn));
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Flock%2FlistEn.html
     */
    protected function fetchRemoteDevices()
    {
        $devices = [];

        // start transporter
        $transporter = $this->createHTTPTransporter();

        // obtain settings after initializing the transporter
        $settings = $this->getSettings();

        // request page settings
        $pageNo = 1;
        $pageSize = 20;
        $reqCount = 0;
        $reqMax = 20;

        // start a loop to support pagination
        while (true) {
            if ($reqCount >= $reqMax) {
                // too many requests
                break;
            }

            // build query string data
            $data = [
                'clientId'    => $settings['client_id'],
                'accessToken' => $settings['_oauth']['access_token'],
                'pageNo'      => $pageNo,
                'pageSize'    => $pageSize,
                'date'        => time() . '000',
            ];

            // make a request to obtain the lock list of an account
            $response = $transporter->get('https://euapi.ttlock.com/v3/lock/list?' . http_build_query($data), [], 20);

            // obtain the response data
            $responseData = (array) json_decode((string) $response->body, true);

            if ($response->code != 200 || !empty($responseData['errcode'])) {
                // an error occurred
                throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Error fetching the remove devices.', ($response->code != 200 ? $response->code : 500));
            }

            // increase request counter
            $reqCount++;

            if (!empty($responseData['list'])) {
                $devices = array_merge($devices, $responseData['list']);
            }

            if (($responseData['pages'] ?? 0) > $pageNo) {
                // go to the next loop
                $pageNo++;

                continue;
            }

            // all devices were read
            break;
        }

        if (!$devices) {
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
        $decorator->setID($device['lockId'] ?? '');

        // set device name
        $decorator->setName($device['lockAlias'] ?? $device['lockName'] ?? '');

        // set device description
        $decorator->setDescription($device['groupName'] ?? '');

        // set device icon
        $decorator->setIcon('<i class="' . VikBookingIcons::i('fingerprint') . '"></i>');

        // set device model
        $decorator->setModel($device['lockName'] ?? '');

        if ($device['electricQuantity'] ?? null) {
            // set device battery level
            $decorator->setBatteryLevel((float) $device['electricQuantity']);
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
                    'search' => [
                        'type'  => 'text',
                        'label' => JText::translate('VBO_SEARCH_PASSCODE'),
                        'help'  => JText::translate('VBO_OPT_SEARCH_KEYWORD'),
                    ],
                ],
            ]),
            // create (random) passcode
            $this->createDeviceCapability([
                'id'          => 'create_passcode',
                'title'       => JText::translate('VBO_CREATE_PASSCODE'),
                'description' => JText::translate('VBO_CREATE_PASSCODE_HELP'),
                'icon'        => '<i class="' . VikBookingIcons::i('plus') . '"></i>',
                'callback'    => 'createRandomPasscode',
                'params'      => [
                    'pwdname' => [
                        'type'  => 'text',
                        'label' => JText::translate('VBO_PASSCODE_NAME'),
                        'help'  => JText::translate('VBO_OPT_PASSCODE_NAME'),
                    ],
                    'pwdtype' => [
                        'type' => 'select',
                        'label' => JText::translate('VBPSHOWSEASONSTHREE'),
                        'options' => array_combine(array_keys($this->getPasscodeTypes()), array_column($this->getPasscodeTypes(), 'name')),
                        'default' => 3,
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
                        'help'    => JText::translate('VBO_PASSCODE_EMPTY_HELP') . ' 4-9 digits, first digit should not be 0.',
                        'attributes' => [
                            'pattern' => '[1-9][0-9]{3,8}',
                        ],
                    ],
                    'pwdname' => [
                        'type'  => 'text',
                        'label' => JText::translate('VBO_PASSCODE_NAME'),
                        'help'  => JText::translate('VBO_OPT_PASSCODE_NAME'),
                    ],
                    'pwdtype' => [
                        'type' => 'select',
                        'label' => JText::translate('VBPSHOWSEASONSTHREE'),
                        'options' => array_combine(array_keys($this->getPasscodeTypes()), array_column($this->getPasscodeTypes(), 'name')),
                        'default' => 3,
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
                    'recordtype' => [
                        'type'    => 'select',
                        'label'   => JText::translate('VBPSHOWSEASONSTHREE'),
                        'options' => ([JText::translate('VBANYTHING')] + $this->getActivityRecordTypes()),
                    ],
                    'search' => [
                        'type'  => 'text',
                        'label' => JText::translate('VBO_SEARCH_PASSCODE'),
                        'help'  => JText::translate('VBO_OPT_SEARCH_KEYWORD'),
                    ],
                ],
            ]),
        ]);

        // set device payload by unsetting the unwanted properties
        unset($device['lockData']);
        $decorator->setPayload($device);
    }

    /**
     * Generates a random serial code made of only digits with a given length.
     * The sequence obtained will never start with 0 to allow integer casting.
     * 
     * @param   int     $length     The passcode length.
     * 
     * @return  string
     */
    private function generateRandomPasscode(int $length = 8)
    {
        return rand(1, 9) . VikBooking::getCPinInstance()->generateSerialCode($length - 1, ['0123456789']);
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
            1 => [
                'name' => 'One-time',
                'descr' => 'Valid only once within 6 hours from the Start Time.',
            ],
            2 => [
                'name' => 'Permanent',
                'descr' => 'Code must be used at least once within 24 Hours after the Start Time, or it will be invalidated.',
            ],
            3 => [
                'name' => 'Period',
                'descr' => 'Code must be used at least once within 24 Hours after the Start Time, or it will be invalidated.',
            ],
            4 => [
                'name' => 'Delete',
                'descr' => 'The code will delete all other codes.',
            ],
            5 => [
                'name' => 'Weekend Cyclic',
                'descr' => 'The code is valid during the time period at the weekend.',
            ],
            6 => [
                'name' => 'Daily Cyclic',
                'descr' => 'The code is valid during the time period everyday.',
            ],
            7 => [
                'name' => 'Workday Cyclic',
                'descr' => 'The code is valid during the time period on workdays.',
            ],
            8 => [
                'name' => 'Monday Cyclic',
                'descr' => 'The code is valid during the time period on Mondays.',
            ],
            9 => [
                'name' => 'Tuesday Cyclic',
                'descr' => 'The code is valid during the time period on Tuesdays.',
            ],
            10 => [
                'name' => 'Wednesday Cyclic',
                'descr' => 'The code is valid during the time period on Wednesdays.',
            ],
            11 => [
                'name' => 'Thursday Cyclic',
                'descr' => 'The code is valid during the time period on Thursdays.',
            ],
            12 => [
                'name' => 'Friday Cyclic',
                'descr' => 'The code is valid during the time period on Fridays.',
            ],
            13 => [
                'name' => 'Saturday Cyclic',
                'descr' => 'The code is valid during the time period on Saturdays.',
            ],
            14 => [
                'name' => 'Sunday Cyclic',
                'descr' => 'The code is valid during the time period on Sundays.',
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
     * Maps the known record types for the unlock records (activity logs).
     * 
     * @param   ?int    $type   Optional passcode type identifier to fetch.
     * 
     * @return  array|string    Full list, or record type (event) name.
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    private function getActivityRecordTypes(?int $type = null)
    {
        $list = [
            4  => 'unlock by passcode',
            1  => 'unlock by app',
            7  => 'unlock by IC card',
            8  => 'unlock by fingerprint',
            9  => 'unlock by wrist strap',
            10 => 'unlock by Mechanical key',
            12 => 'unlock by gateway',
            46 => 'unlock by unlock key',
            49 => 'unlock by hotel card',
            50 => 'Unlocked due to the high temperature',
            57 => 'Unlock with QR code success',
            58 => 'Unlock with QR code failed, it\'s expired',
            51 => 'Try to unlock with a deleted card',
            5  => 'Rise the lock (for parking lock)',
            6  => 'Lower the lock (for parking lock)',
            11 => 'lock by app',
            29 => 'apply some force on the Lock',
            30 => 'Door sensor closed',
            31 => 'Door sensor open',
            32 => 'open from inside',
            33 => 'lock by fingerprint',
            34 => 'lock by passcode',
            35 => 'lock by IC card',
            36 => 'lock by Mechanical key',
            37 => 'Use APP button to control the lock (rise, fall, stop, lock), mostly used for roller shutter door',
            42 => 'received new local mail',
            43 => 'received new other cities mail',
            44 => 'Tamper alert',
            45 => 'Auto Lock',
            47 => 'lock by lock key',
            48 => 'System locked ( Caused by, for example: Using INVALID Passcode/Fingerprint/Card several times)',
            52 => 'Dead lock with APP',
            53 => 'Dead lock with passcode',
            54 => 'The car left (for parking lock)',
            55 => 'Use remote control lock or unlock lock',
            59 => 'Double locked',
            60 => 'Cancel double lock',
            61 => 'Lock with QR code success',
            62 => 'Lock with QR code failed, the lock is double locked',
            63 => 'Auto unlock at passage mode',
            64 => 'Door unclosed alarm',
            65 => 'Failed to unlock',
            66 => 'Failed to lock',
            67 => 'Face unlock success',
            68 => 'Face unlock failed (door locked from inside)',
            69 => 'Lock with face',
            71 => 'Face unlock failed (expired or ineffective)',
            75 => 'Unlocked by App granting',
            76 => 'Unlocked by remote granting',
            77 => 'Dual authentication Bluetooth unlock verification success, waiting for second user',
            78 => 'Dual authentication password unlock verification success, waiting for second user',
            79 => 'Dual authentication fingerprint unlock verification success, waiting for second user',
            80 => 'Dual authentication IC card unlock verification success, waiting for second user',
            81 => 'Dual authentication face card unlock verification success, waiting for second user',
            82 => 'Dual authentication wireless key unlock verification success, waiting for second user',
            83 => 'Dual authentication palm vein unlock verification success, waiting for second user',
            84 => 'Palm vein unlock success',
            85 => 'Palm vein unlock success',
            86 => 'Lock with palm vein',
            88 => 'Palm vein unlock failed (expired or ineffective)',
            92 => 'Administrator password to unlock ',
        ];

        if (is_null($type)) {
            return $list;
        }

        return $list[$type] ?? '';
    }

    /**
     * Creates the HTTP Transporter to establish API connections with TTLock.
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

        if (empty($settings['client_id']) || empty($settings['client_secret']) || empty($settings['username']) || empty($settings['password'])) {
            throw new Exception('Missing integration profile credentials (settings).', 500);
        }

        // obtain a valid OAuth token
        if ($options['renew_token'] ?? null) {
            // force the token renewal
            $this->oauthToken = $this->renewOauthToken();
        } else {
            // get the possibly valid token
            $this->oauthToken = $this->getOauthToken();
        }

        // set HTTP headers
        $this->httpHeaders = [
            'Authorization' => "Bearer {$this->oauthToken}",
            'ContentType' => 'application/x-www-form-urlencoded',
        ];

        return new JHttp;
    }

    /**
     * Obtains an active OAuth (Bearer) token to establish API connections with TTLock.
     * 
     * @return  string      An active OAuth (Bearer) token.
     * 
     * @throws  Exception
     */
    private function getOauthToken()
    {
        // access current profile settings
        $settings = $this->getSettings();

        if (empty($settings['_oauth']['access_token']) || empty($settings['_oauth']['expiry_ts'])) {
            // the token should be obtained from scratch
            return $this->renewOauthToken();
        }

        if ($settings['_oauth']['expiry_ts'] < time()) {
            // the token should be renewed
            return $this->renewOauthToken($refresh = true);
        }

        // return the supposingly active token
        return (string) $settings['_oauth']['access_token'];
    }

    /**
     * Makes an API request with TTLock to get and save the OAuth token for any HTTP request.
     * It can either get a new access token, or it can refresh an existing access token.
     * 
     * @param   bool    $refresh    True to refresh an existing token, false to obtain a new one.
     * 
     * @return  string              An active OAuth (Bearer) token.
     * 
     * @throws  Exception
     * 
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Foauth2%2FgetAccessTokenEn.html
     * @link    https://euopen.ttlock.com/document/doc?urlName=cloud%2Foauth2%2FrefreshAccessTokenEn.html
     */
    private function renewOauthToken(bool $refresh = false)
    {
        // access current profile settings
        $settings = $this->getSettings();

        if (empty($settings['client_id']) || empty($settings['client_secret']) || empty($settings['username']) || empty($settings['password'])) {
            throw new Exception('Missing integration profile credentials (settings).', 500);
        }

        // build request data
        if ($refresh === true && !empty($settings['_oauth']['refresh_token'])) {
            // refresh an existing token
            $data = [
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $settings['_oauth']['refresh_token'],
            ];
        } else {
            // get a new token
            $data = [
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
                'username' => $settings['username'],
                'password' => md5($settings['password']),
            ];
        }

        // exchange the settings to obtain the OAuth token details
        $response = (new JHttp)->post('https://euapi.ttlock.com/oauth2/token', http_build_query($data), ['ContentType' => 'application/x-www-form-urlencoded'], 10);

        // obtain the response data
        $responseData = (array) json_decode((string) $response->body, true);

        if ($response->code != 200) {
            // an error occurred
            throw new Exception($response->body ?: 'OAuth token error.', $response->code);
        }

        if (empty($responseData['access_token'])) {
            // invalid response
            throw new Exception($responseData['errmsg'] ?? $response->body ?: 'Generic response error.', 500);
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
        return $responseData['access_token'];
    }
}
