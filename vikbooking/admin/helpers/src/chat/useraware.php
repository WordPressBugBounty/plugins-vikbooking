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
 * Chat user adapter.
 * 
 * @since 1.8
 */
abstract class VBOChatUseraware implements VBOChatUser, JsonSerializable
{
    /**
     * @inheritDoc
     */
    public function getAvatar()
    {
        return '';
    }

    /**
     * @inheritDoc
     *
     * @see JsonSerializable
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->getID(),
            'name' => $this->getName(),
            'avatar' => $this->getAvatar(),
        ];
    }
}
