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
 * Wizard help instruction configurable invoicing trait.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
trait VBOHelpWizardTraitInvoicingConfigurable
{
    /**
     * The real class name of the invoicing implementor (see einvoicing folder).
     * 
     * @var string
     */
    protected $driverId;

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::getIcon()
     */
    public function getIcon()
    {
        return VikBookingIcons::i('file-invoice-dollar');
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::getPriority()
     */
    public function getPriority()
    {
        return 99;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::isConfigured()
     */
    public function isConfigured()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select($db->qn('params'))
            ->from($db->qn('#__vikbooking_einvoicing_config'))
            ->where($db->qn('driver') . ' = ' . $db->q((string) $this->driverId));

        $db->setQuery($query, 0, 1);
        $params = $db->loadResult();

        return (bool) ($params ? json_decode($params, true) : null);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::isProcessable()
     */
    public function isProcessable(?string &$btnText = null)
    {
        $btnText = JText::translate('VBCONFIGURETASK');

        return true;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardInstruction::process()
     */
    public function process(array $args = [])
    {
        return [
            'redirect' => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=einvoicing&driver=' . $this->driverId . '#settings', false),
        ];
    }
}
