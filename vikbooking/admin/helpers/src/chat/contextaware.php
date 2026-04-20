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
 * Chat context adapter class.
 * 
 * @since 1.8
 */
abstract class VBOChatContextaware implements VBOChatContext
{
    /**
     * The foreign key to link the messages to the context.
     * 
     * @var int
     */
    protected $id;

    /**
     * Class constructor.
     * 
     * @param  int  $id  The context foreign key.
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @inheritDoc
     */
    final public function getID()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(bool $public = false)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(string $key, $value)
    {
        // do nothing
    }

    /**
     * @inheritDoc
     */
    public function useAssets(VBOChatUser $user)
    {
        // do nothing
    }

    /**
     * @inheritDoc
     */
    public function getActions(VBOChatUser $user)
    {
        return [];
    }
}
