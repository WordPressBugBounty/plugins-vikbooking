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
 * Wizard help instruction interface.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
abstract class VBOHelpWizardInstructionaware implements VBOHelpWizardInstruction
{
    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        $id = preg_replace("/[^a-zA-Z0-9]+/", '_', $this->getID());
        return JText::translate('VBO_HELP_WIZARD_' . strtoupper($id));
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('exclamation');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        // medium priority
        return 99;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        // always supported by default
        return true;
    }

    /**
     * @inheritDoc
     */
    public function show()
    {
        $data = array_merge($this->getLayoutData(), [
            'instruction' => $this,
        ]);

        return JLayoutHelper::render('helpwizard.' . $this->getID(), $data);
    }

    /**
     * @inheritDoc
     */
    public function isDismissible()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isProcessable(?string &$btnText = null)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function process(array $args = [])
    {
        // do nothing by default
    }

    /**
     * The display data to be passed to the rendering layout.
     * 
     * @return  array
     */
    protected function getLayoutData()
    {
        return [];
    }
}
