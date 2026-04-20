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
 * Chat session model.
 * 
 * @since 1.8.8
 */
class VBOChatSessionModel extends VBOMvcModel
{
    /**
     * Used to cache the current session based on cookie token.
     * 
     * @var object|null
     */
    protected static $currentSession = null;

    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_chat_sessions';

    /**
     * The cookie name of the token.
     * 
     * @var string
     */
    protected $cookieTokenName = 'vbo_chat_session';

    /**
     * Returns the cookie token currently set for this user.
     * 
     * @return  string|null
     */
    public function getCookieToken()
    {
        // fetch session secret ID from cookie
        $token = JFactory::getApplication()->input->cookie->getString($this->cookieTokenName);

        // fetch token from PHP session as well
        $sessionToken = JFactory::getSession()->get($this->cookieTokenName);

        // in case we have a token within the PHP session and the cookie token does not match,
        // prefer the one saved in the session
        if ($sessionToken && strcmp($token, $sessionToken)) {
            $token = $sessionToken;

            $this->setCookieToken($token);
        }

        return $token;
    }

    /**
     * Updates the cookie token for this user.
     * 
     * @param   string  $token  The session token.
     * 
     * @return  void
     */
    public function setCookieToken(string $token)
    {
        // in case of provided token, preserve cookie for 60 days, otherwise immediately expire it
        $exp = $token ? 86400 * 60 : -3600;

        // save the session token within the cookie
        JFactory::getApplication()->input->cookie->set($this->cookieTokenName, $token, [
            'expires'  => time() + $exp,
            'path'     => '/',
            'httponly' => true,
        ]);

        // save the token within the PHP session as well
        JFactory::getSession()->set($this->cookieTokenName, $token);
    }

    /**
     * Returns the session record according to the cookie token currently set, if any.
     * 
     * @return  object|null
     */
    public function getFromCookie()
    {
        if (static::$currentSession === null) {
            // fetch session secret ID from cookie
            $sessionSecretId = $this->getCookieToken();

            if (!$sessionSecretId) {
                return null;
            }

            // load session cookie (cache for later use)
            static::$currentSession = $this->getItem(['token' => $sessionSecretId]);
        }

        return static::$currentSession;
    }

    /**
     * Starts a new session by removing the current cookie token.
     * 
     * @return  void
     */
    public function endSession()
    {
        // clear the configured token
        $this->setCookieToken('');

        // reset the token from the PHP session as well
        JFactory::getSession()->set($this->cookieTokenName, '');
    }

    /**
     * Updates a metadata for the specified session.
     * 
     * @param   int|object  $session  Either a session ID or the session object.
     * @param   string      $key      The metadata name.
     * @param   mixed       $value    The metadata value.
     * 
     * @return  void
     */
    public function setMetadata($session, string $key, $value)
    {
        if (is_numeric($session)) {
            $session = $this->getItem((int) $session);
        }

        if (!$session) {
            throw new InvalidArgumentException('Invalid session.', 400);
        }

        if (!$key) {
            throw new InvalidArgumentException('Invalid metadata name.', 400);
        }

        // update metadata
        $session->metadata[$key] = $value;

        // update session
        $this->save([
            'id' => $session->id,
            'metadata' => $session->metadata,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getItem($pk)
    {
        $item = parent::getItem($pk);

        if ($item) {
            // decode metadata from JSON
            $item->metadata = (array) ($item->metadata ? json_decode($item->metadata, true) : []);
        }

        return $item;
    }

    /**
     * @inheritDoc
     */
    protected function preflight(array &$data)
    {
        if (empty($data['id'])) {
            do {
                // always generate a unique secret token for new sessions
                $data['token'] = VikBooking::getCPinInstance()->generateSerialCode(32);
                // repeat in case a session with the generated token already exists
            } while ($this->getItem(['token' => $data['token']]));

            $user = JFactory::getUser();

            if (empty($data['name'])) {
                // always use force a name
                $data['name'] = $user->name ?: 'Guest';
            }

            if (!isset($data['id_user'])) {
                $data['id_user'] = $user->id;
            }

            $data['created'] = JFactory::getDate('now')->toSql();
        }

        if (!empty($data['phone'])) {
            // normalize phone number (accept letters and dots to support WhatsApp BSUID as well)
            $data['phone'] = preg_replace("/[^0-9A-Z.]+/", '', $data['phone']);
        }

        if (isset($data['metadata']) && is_string($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }

        // always postpone the logout by one minute every time we save the record
        $data['logout'] = JFactory::getDate('+1 minute')->toSql();

        return parent::preflight($data);
    }

    /**
     * @inheritDoc
     */
    protected function postflight(array $data, $isNew)
    {
        // do not save session token in case we are starting a WhatsApp session
        if ($isNew && empty($data['phone'])) {
            // save the session token within the cookie
            $this->setCookieToken($data['token']);
        }
    }
}
