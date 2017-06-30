<?php

namespace GeocachingTool;

class User
{
    private $isNew = true;
    private $key = false;

    /**
     * Sets or updates cookie that identifies the user ("user_key")
     */
    public function checkIn() {
        $cookieExpirationTime = time() + 60 * 60 * 24 * 365 * 100; // 100 years from now

        if (!isset($_COOKIE['user_key'])) {
            $this->key = $this->createKey();
            setcookie('user_key', $this->key, $cookieExpirationTime);
        } else {
            $this->isNew = false;
            $this->key = $_COOKIE['user_key'];
            setcookie('user_key', $this->key, $cookieExpirationTime);
        }
    }

    /**
     * Tells whether a user is visiting for the first time
     *
     * @return bool
     */
    public function isNew() {
        return $this->isNew;
    }

    /**
     * Generates a unique user key
     *
     * @return string
     */
    private function createKey() {
        return uniqid();
    }

    /**
     * Gets user key of a user
     *
     * @return bool
     */
    public function getKey() {
        return $this->key;
    }
}
