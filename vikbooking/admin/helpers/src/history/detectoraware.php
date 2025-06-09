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
 * Changes detector aware class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
abstract class VBOHistoryDetectoraware implements VBOHistoryDetector
{
    /**
     * The value set before the changes.
     * 
     * @var mixed
     */
    protected $previousValue;

    /**
     * The value set after the changes.
     * 
     * @var mixed
     */
    protected $currentValue;

    /**
     * The name of the property to compare.
     * 
     * @var string
     */
    protected $propertyName;

    /**
     * Class constructor.
     * 
     * @param  string  $propertyName  The name of the property to compare.
     */
    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @inheritDoc
     */
    public function getEvent()
    {
        return $this->propertyName . '.changed';
    }

    /**
     * @inheritDoc
     */
    public function hasChanged(object $prev, object $curr)
    {
        if (!property_exists($prev, $this->propertyName)) {
            return false;
        }

        if (!property_exists($curr, $this->propertyName)) {
            return false;
        }

        // internally save for later use
        $this->previousValue = $prev->{$this->propertyName};
        $this->currentValue = $curr->{$this->propertyName};

        return $this->checkChanges($this->previousValue, $this->currentValue);
    }

    /**
     * Internal changes detection.
     * 
     * @param   mixed   $previousValue
     * @param   mixed   $currentValue
     * 
     * @return  bool
     */
    protected function checkChanges($previousValue, $currentValue)
    {
        // check whether the property of the 2 elements are different
        return (bool) strcmp((string) $previousValue, (string) $currentValue);
    }

    /**
     * @inheritDoc
     */
    final public function describe()
    {
        return $this->doDescribe($this->previousValue, $this->currentValue);
    }

    /**
     * Internal changes description.
     * 
     * @param   mixed   $previousValue
     * @param   mixed   $currentValue
     * 
     * @return  string
     */
    abstract protected function doDescribe($previousValue, $currentValue);

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('pencil-alt');
    }
}
