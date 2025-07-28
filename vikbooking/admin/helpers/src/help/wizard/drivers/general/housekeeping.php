<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Wizard housekeeping (task manager) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverGeneralHousekeeping extends VBOHelpWizardInstructionaware
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'general.housekeeping';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('broom');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 9;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        return VBOPlatformDetection::isJoomla() || class_exists('VikChannelManager');
    }

    /**
     * @inheritDoc
     */
    public function isConfigured()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select(1)
            ->from($db->qn('#__vikbooking_tm_areas'))
            ->where($db->qn('instanceof') . ' = ' . $db->q('cleaning'));

        $db->setQuery($query, 0, 1);
        return (bool) $db->loadResult();
    }

    /**
     * @inheritDoc
     */
    public function isProcessable(?string &$btnText = null)
    {
        $btnText = JText::translate('VBCONFIGURETASK');

        return true;
    }

    /**
     * @inheritDoc
     */
    public function process(array $args = [])
    {
        return [
            'redirect' => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&view=taskmanager', false),
        ];
    }
}
