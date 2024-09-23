<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.html
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

require_once ABSPATH . WPINC . '/sodium_compat/autoload-php7.php';
JLoader::import('adapter.cipher.key');

/**
 * JCrypt cipher for sodium algorithm encryption, decryption and key generation.
 *
 * @since 10.1.57
 */
class JSodiumCipher
{
    /**
     * The message nonce to be used with encryption/decryption.
     *
     * @var string
     */
    private $nonce;

    /**
     * Method to decrypt a data string.
     *
     * @param   string     $data  The encrypted string to decrypt.
     * @param   JCryptKey  $key   The key object to use for decryption.
     *
     * @return  string     The decrypted data string.
     *
     * @throws  RuntimeException
     */
    public function decrypt($data, JCryptKey $key)
    {
        // validate key
        if ($key->type !== 'sodium')
        {
            throw new InvalidArgumentException('Invalid key of type: ' . $key->type . '.  Expected sodium.');
        }

        if (!$this->nonce)
        {
            throw new RuntimeException('Missing nonce to decrypt data');
        }

        $decrypted = ParagonIE_Sodium_Compat::crypto_box_open(
            $data,
            $this->nonce,
            ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($key->private, $key->public)
        );

        if ($decrypted === false)
        {
            throw new RuntimeException('Malformed message or invalid MAC');
        }

        return $decrypted;
    }

    /**
     * Method to encrypt a data string.
     *
     * @param   string     $data  The data string to encrypt.
     * @param   JCryptKey  $key   The key object to use for encryption.
     *
     * @return  string     The encrypted data string.
     *
     * @throws  RuntimeException
     */
    public function encrypt($data, JCryptKey $key)
    {
        // validate key
        if ($key->type !== 'sodium')
        {
            throw new InvalidArgumentException('Invalid key of type: ' . $key->type . '.  Expected sodium.');
        }

        if (!$this->nonce)
        {
            throw new RuntimeException('Missing nonce to decrypt data');
        }

        return ParagonIE_Sodium_Compat::crypto_box(
            $data,
            $this->nonce,
            ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($key->private, $key->public)
        );
    }

    /**
     * Method to generate a new encryption key object.
     *
     * @param   array  $options  Key generation options.
     *
     * @return  JCryptKey
     *
     * @throws  RuntimeException
     */
    public function generateKey(array $options = [])
    {
        // Generate the encryption key.
        $pair = ParagonIE_Sodium_Compat::crypto_box_keypair();

        return new JCryptKey(
        	'sodium',
        	ParagonIE_Sodium_Compat::crypto_box_secretkey($pair),
        	ParagonIE_Sodium_Compat::crypto_box_publickey($pair)
        );
    }

    /**
     * Check if the cipher is supported in this environment.
     *
     * @return  bool
     */
    public static function isSupported(): bool
    {
        return class_exists(ParagonIE_Sodium_Compat::class);
    }

    /**
     * Set the nonce to use for encrypting/decrypting messages
     *
     * @param   string  $nonce  The message nonce
     *
     * @return  void
     */
    public function setNonce($nonce)
    {
        if (strlen($nonce) < ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES)
        {
            $nonce = str_repeat($nonce, ceil(ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES / strlen($nonce)));
        }

        $this->nonce = substr($nonce, 0, ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES);
    }
}
