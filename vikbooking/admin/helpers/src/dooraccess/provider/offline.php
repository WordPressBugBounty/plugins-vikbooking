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
 * Door Access integration provider for "Offline" Fixed Code (no API connections).
 * 
 * @since   1.18.15 (J) - 1.8.15 (WP)
 */
final class VBODooraccessProviderOffline extends VBODooraccessIntegrationAware
{
    /**
     * @var    bool    Allows sorting.
     */
    public $isOffline = true;

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
        return 'Offline Fixed Code';
    }

    /**
     * @inheritDoc
     */
    public function getShortName()
    {
        return 'Offline';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return '<i class="' . VikBookingIcons::i('key') . '"></i>';
    }

    /**
     * @inheritDoc
     */
    public function getParams()
    {
        return [
            '_help' => [
                'type' => 'custom',
                'html' => '<p class="info">' . JText::translate('VBO_DAC_OFFLINE_INTGR_HELP') . '</p>',
            ],
            'device' => [
                'type'  => 'text',
                'label' => JText::translate('VBTRKDEVICE'),
                'help'  => rtrim(JText::translate('VBO_LOCK_NAME'), '.') . '.',
            ],
            'code' => [
                'type'  => 'text',
                'label' => JText::translate('VBO_PASSCODE'),
                'help'  => rtrim(JText::translate('VBO_FIXED_CODE'), '.') . '.',
            ],
            'ai' => [
                'type'    => 'checkbox',
                'label'   => JText::translate('VBO_AI_SUPPORT'),
                'help'    => JText::translate('VBO_DAC_AI_SUPPORT_HELP'),
                'default' => 1,
            ],
            'notes' => [
                'type'  => 'textarea',
                'label' => JText::translate('ORDER_NOTES'),
                'help'  => JText::translate('VBO_DAC_UNLOCK_INSTR'),
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
     * Device capability implementation to unlock a device.
     * 
     * @param   VBODooraccessIntegrationDevice  $device     The device executing the capability.
     * @param   ?array                          $options    Optional settings populated from capability parameters.
     * 
     * @return  VBODooraccessDeviceCapabilityResult
     */
    public function unlockDevice(VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        // obtain integration settings
        $settings = $this->getSettings();

        if (empty($settings['code'])) {
            throw new Exception('Error unlocking the device due to invalid settings.', 500);
        }

        return (new VBODooraccessDeviceCapabilityResult)
            ->setText(
                sprintf(
                    'The device "%s" can be unlocked with the code: %s.%s',
                    $device->getName(),
                    $settings['code'],
                    (!empty($settings['notes']) ? " \n{$settings['notes']}" : '')
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function createBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // access the integration settings
        $settings = $this->getSettings();

        if (empty($settings['code'])) {
            throw new Exception('Could not create passcode due to invalid settings.', 500);
        }

        // build result properties to bind
        $resultProps = [
            'code'      => (string) $settings['code'],
            'listingId' => $listingId,
        ];

        // get the listing name
        $listingData = VikBooking::getRoomInfo($resultProps['listingId'], ['name'], true);
        $listingName = sprintf('%s: ', $listingData['name'] ?? '');

        // wrap and return the device capability result object
        return (new VBODooraccessDeviceCapabilityResult($resultProps))
            ->setPasscode($resultProps['code'])
            ->setText($listingName . JText::sprintf('VBO_PASSCODE_GEN_OK_DEVICE', $resultProps['code'], $device->getName()));
    }

    /**
     * @inheritDoc
     */
    public function modifyBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // process the modification as a new door access creation
        return $this->createBookingDoorAccess($device, $listingId, $registry);
    }

    /**
     * @inheritDoc
     */
    public function cancelBookingDoorAccess(VBODooraccessIntegrationDevice $device, int $listingId, VBOBookingRegistry $registry)
    {
        // nothing to cancel, but prevent unwanted errors
        return null;
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
     */
    protected function fetchRemoteDevices()
    {
        $devices = [];

        // obtain settings after initializing the transporter
        $settings = $this->getSettings();

        if (empty($settings['device'])) {
            throw new Exception('Invalid settings. Please provide a default name for the lock.', 500);
        }

        if (empty($settings['code'])) {
            throw new Exception('Invalid settings. Please provide the lock fixed access code to be used offline.', 500);
        }

        return [
            // add one fixed/offline lock
            [
                'id'   => uniqid(),
                'name' => $settings['device'],
                'ts'   => time(),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function decorateDeviceProperties(VBODooraccessIntegrationDevice $decorator, array $device)
    {
        // set device ID
        $decorator->setID($device['id']);

        // set device name
        $decorator->setName($device['name']);

        // set device description
        $decorator->setDescription('Fixed access code');

        // set device icon
        $decorator->setIcon('<i class="' . VikBookingIcons::i('lock') . '"></i>');

        // set device model
        $decorator->setModel(date('Y.m.d'));

        // set dummy battery level
        $decorator->setBatteryLevel(100.00);

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
        ]);

        // set device payload
        $decorator->setPayload($device);
    }
}
