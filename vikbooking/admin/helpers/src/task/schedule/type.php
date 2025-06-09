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
 * Task schedule type abstract implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
abstract class VBOTaskScheduleType implements VBOTaskScheduleInterface
{
    /**
     * @var  string
     */
    protected $type = '';

    /**
     * @var  VBOTaskBooking
     */
    protected $booking;

    /**
     * Class constructor.
     * 
     * @param   string          $type       The schedule enumeration type.
     * @param   VBOTaskBooking  $booking    The current task booking registry.
     */
    public function __construct(string $type, VBOTaskBooking $booking)
    {
        // set schedule enum type
        $this->type = strtolower($type);

        // set booking registry
        $this->booking = $booking;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(array $info = [], int $counter = 0)
    {
        return sprintf('Task scheduling for %s frequency.', $this->type);
    }

    /**
     * Returns the current task booking registry.
     * 
     * @return  VBOTaskBooking
     */
    public function getBooking()
    {
        return $this->booking;
    }
}
