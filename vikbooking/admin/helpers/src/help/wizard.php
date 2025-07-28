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
 * Help wizard handler.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizard
{
    use VBOFactoryAware;

    /**
     * A list of supported instructions.
     * 
     * @var VBOHelpWizardInstruction[]
     */
    protected $instructions = null;

    /**
     * The registry where the wizard settings should be saved.
     * 
     * @var VBOConfigRegistry
     */
    protected $config;

    /**
     * The minutes of delay before to display a new instruction.
     * 
     * @var int
     */
    protected $delay;

    /**
     * Class constructor.
     * 
     * @param   array  $options  (config, delay, paths).
     */
    public function __construct(array $options = [])
    {
        if (!($options['config'] ?? null) instanceof VBOConfigRegistry) {
            $options['config'] = VBOFactory::getConfig();
        }

        $this->config = $options['config'];

        $this->delay = abs((int) ($options['delay'] ?? 0));

        if (!empty($options['paths']))
        {
            $this->setIncludePaths($options['paths']);
        }

        /**
         * @see VBOFactoryAware
         */
        $this->instanceClassPrefix = 'VBOHelpWizardDriver';
        $this->instanceNamespaceSeparator = '.';
    }

    /**
     * Returns a list of supported instructions.
     * 
     * @return  VBOHelpWizardInstruction[]
     */
    public function getInstructions()
    {
        if ($this->instructions === null)
        {
            // internally cache the instances
            $this->instructions = array_values($this->getInstances());
        }

        return $this->instructions;
    }

    /**
     * Returns the instruction matching the specified identifier.
     * 
     * @param   string  $id
     * 
     * @return  VBOHelpWizardInstruction|null
     */
    public function getInstruction(string $id)
    {
        foreach ($this->getInstructions() as $needle) {
            if ($needle->getID() == $id) {
                return $needle;
            }
        }

        return null;
    }

    /**
     * Returns the next eligible instruction (supported but not configured).
     * 
     * @return  VBOHelpWizardInstruction|null
     */
    public function getNextInstruction()
    {
        $lastCheckDateTime = $this->config->getString('help_wizard_last_check', null);

        // wait for the number of specified minutes
        if (JFactory::getDate('-' . $this->delay . ' minutes')->toISO8601() < $lastCheckDateTime) {
            return null;
        }

        foreach ($this->getInstructions() as $instruction) {
            if ($this->isDismissed($instruction)) {
                // instruction dismissed, move on
                continue;
            }

            if (!$instruction->isSupported()) {
                // instruction not supported, move on
                continue;
            }

            if ($instruction->isConfigured()) {
                // instruction already configured, move on
                continue;
            }

            // eligible instruction found
            return $instruction;
        }

        return null;
    }

    /**
     * Checks whether the specified instruction has been dismissed.
     * 
     * @param   VBOHelpWizardInstruction  $instruction
     * 
     * @return  bool
     */
    public function isDismissed(VBOHelpWizardInstruction $instruction): bool
    {
        $dismissed = $this->config->getArray('help_wizard_dismissed', []);

        if (!array_key_exists($instruction->getID(), $dismissed)) {
            return false;
        }

        $datetime = $dismissed[$instruction->getID()];

        if ($datetime == 0) {
            return true;
        }

        return $datetime > JFactory::getDate('now')->toISO8601();
    }

    /**
     * Dismisses the specified instruction.
     * 
     * @param   VBOHelpWizardInstruction  $instruction  The instruction to dismiss.
     * @param   string|null               $datetime     How long the instruction should stay silent.
     *                                                  If not specified, the instruction will be
     *                                                  permanently dismissed.
     * 
     * @return  void
     */
    public function dismiss(VBOHelpWizardInstruction $instruction, ?string $datetime = null): void
    {
        if (!$instruction->isDismissible()) {
            throw new RuntimeException('The instruction [' . $instruction->getID() . '] cannot be dismissed!', 403);
        }

        if ($datetime) {
            try {
                // make sure we have a valid date time string
                $datetime = JFactory::getDate($datetime)->toISO8601();
            } catch (Exception $error) {
                // invalid date time, permanently dismiss
                $datetime = null;
            }
        }

        // obtain dismissed instructions lookup (id->datetime)
        $dismissed = $this->config->getArray('help_wizard_dismissed', []);

        // register new threshold for the current instruction
        $dismissed[$instruction->getID()] = $datetime ?: 0;

        // update registry
        $this->config->set('help_wizard_dismissed', $dismissed);

        // update last check flag
        $this->config->set('help_wizard_last_check', JFactory::getDate('now')->toISO8601());
    }

    /**
     * @inheritDoc
     * 
     * @see VBOFactoryAware
     */
    protected function rearrangeInstances(&$list)
    {
        // rearrange instructions by descending priority
        usort($list, function($a, $b) {
            return (int) $b->getPriority() - (int) $a->getPriority();
        });
    }

    /**
     * @inheritDoc
     * 
     * @see VBOFactoryAware
     */
    protected function isInstanceValid($object)
    {
        return $object instanceof VBOHelpWizardInstruction;
    }
}
