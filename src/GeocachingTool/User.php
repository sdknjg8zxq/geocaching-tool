<?php

namespace GeocachingTool;

class User
{
    private $key;
    private $app;
    private $cookieExpirationTime;

    public function __construct($app)
    {
        $this->app = $app;
        $this->cookieExpirationTime = $app['user.cookie_expiration_time'];

        if ($this->isFirstVisit()) {
            $this->setKey($this->generateKey());
            $this->saveInDatabase();
            $this->setCookie();
        } else {
            $this->setKey($this->readCookie());
            $this->updateLastUseMoment();
            $this->setCookie();
        }
    }

    /**
     * Tells whether a user is visiting for the first time
     *
     * @return bool
     */
    public function isFirstVisit()
    {
        return !isset($_COOKIE['user_key']);
    }

    /**
     * Generates a unique user key
     *
     * @return string
     */
    private function generateKey()
    {
        return uniqid();
    }

    /**
     * Setter for the key property
     *
     * @param $key string
     */
    private function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Gets user key of a user
     *
     * @return bool
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Saves this user into the database
     */
    private function saveInDatabase()
    {
        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->insert('user')
            ->values(
                array(
                    "`key`" => "'$this->key'",
                    "first_use_moment" => 'CURRENT_TIMESTAMP',
                    "last_use_moment" => 'CURRENT_TIMESTAMP'
                )
            );
        $query = $queryBuilder->getSql();
        $this->app['db']->query($query);
    }

    /**
     * Update last use moment of this user in the database
     */
    private function updateLastUseMoment()
    {
        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->update('user', 'u')
            ->set('u.last_use_moment', 'CURRENT_TIMESTAMP')
            ->where('`key` = "' . $this->key . '"');
        $query = $queryBuilder->getSql();
        $this->app['db']->query($query);
    }

    /**
     * Retrieve the user id from the database
     */
    public function getId()
    {
        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->select('`id`')
            ->from ('`user`')
            ->where("`key` = " . '"' . $this->key . '"');
        $query = $queryBuilder->getSql();
        $result = $this->app['db']->fetchAssoc($query);
        return (int) $result['id'];
    }

    /**
     * Set cookie that identifies the user
     */
    private function setCookie()
    {
        setcookie('user_key', $this->key, $this->cookieExpirationTime);
    }

    /**
     * Get value of user_key cookie
     *
     * @return string
     */
    private function readCookie()
    {
        return $_COOKIE['user_key'];
    }
}
