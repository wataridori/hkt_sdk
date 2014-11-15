<?php
/**
 * @author Tran Duc Thang <thangtd90@gmail.com>
 * Date: 10/25/14
 */

namespace wataridori\HktSdk;


class PersistentStorage {

    /**
     * @var array $supported_keys
     * All keys that can be saved to session
     */
    public static $supported_keys =
        ['state', 'code', 'access_token', 'user'];

    /**
     * @var string $client_id
     */
    private $client_id;

    /**
     * Constructor
     * @param $client_id
     */
    public function __construct($client_id)
    {
        if (!session_id()) {
            session_start();
        }
        $this->client_id = $client_id;
    }

    /**
     * @return string Client ID
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param string $key Key name
     * @return string
     */
    public function createSessionVariableName($key) {
        $parts = array('hkt', $this->client_id, $key);
        return implode('_', $parts);
    }

    /**
     * @param string $key
     * @param string $value
     * @return boolean set Persistent Data successfully or not
     */
    public function setPersistentData($key, $value) {
        if (!in_array($key, self::$supported_keys)) {
            error_log('Unsupported key passed to setPersistentData.');
            return false;
        }

        $session_var_name = $this->createSessionVariableName($key);
        $_SESSION[$session_var_name] = $value;
        return true;
    }

    /**
     * @param string $key
     * @param bool $default
     * @return string|bool
     */
    public function getPersistentData($key, $default = false) {
        if (!in_array($key, self::$supported_keys)) {
            error_log('Unsupported key passed to getPersistentData.');
            return $default;
        }

        $session_var_name = $this->createSessionVariableName($key);
        return isset($_SESSION[$session_var_name]) ?
            $_SESSION[$session_var_name] : $default;
    }

    /**
     * @param string $key
     * @return boolean clear Persistent Data successfully or not
     */
    public function clearPersistentData($key) {
        if (!in_array($key, self::$supported_keys)) {
            error_log('Unsupported key passed to clearPersistentData.');
            return false;
        }

        $session_var_name = $this->createSessionVariableName($key);
        if (isset($_SESSION[$session_var_name])) {
            unset($_SESSION[$session_var_name]);
        }

        return true;
    }

    /**
     * Clear all Persistent Data
     */
    public function clearAllPersistentData() {
        foreach (self::$supported_keys as $key) {
            $this->clearPersistentData($key);
        }
    }

}