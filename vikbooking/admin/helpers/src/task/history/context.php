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
 * Task manager history context.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
final class VBOTaskHistoryContext implements VBOHistoryContext
{
    /** @var int */
    protected $id;

    /**
     * Class constructor.
     * 
     * @param  int  $id  The task ID.
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getAlias()
    {
        return 'task';
    }
}
