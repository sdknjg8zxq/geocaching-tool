<?php

namespace GeocachingTool;

use GeocachingTool\Cache;

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

    /**
     * Get array of cache objects that belong to this user
     * and set $this->caches to this array
     */
    public function getCaches()
    {
        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('cache')
            ->where('user_id = ' . '"' . $this->getId() . '"')
            ->orderby('id', 'DESC');
        $query = $queryBuilder->getSql();
        $caches = $this->app['db']->fetchall($query);

        // Add creation_time_ago item to each cache
        $timeAgo = new \TimeAgo();
        foreach ($caches as $key => $cache) {
            $caches[$key]['creation_time_ago'] = $timeAgo->inWords($cache['creation_moment']);
        }

        return $caches;
    }

    public function saveNewCache($author, $title, $message, $clue, $hash, $shortURL)
    {
        $date = new \DateTime();
        $dateFormated = $date->format('Y-m-d H:i:s');

        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->insert('cache')
            ->values(
                array(
                    'author' => '?',
                    'title' => '?',
                    'description' => '?',
                    'message' => '?',
                    'clue' => '?',
                    'keywords' => '?',
                    'user_id' => '?',
                    'hash' => '?',
                    'views_count' => '?',
                    'creation_moment' => '?',
                    'short_url' => '?'
                )
            )
            ->setParameter(0, $author)
            ->setParameter(1, $title)
            ->setParameter(2, 'There\'s a treasure nearby!')
            ->setParameter(3, $message)
            ->setParameter(4, $clue)
            ->setParameter(5, '')
            ->setParameter(6, $this->getId())
            ->setParameter(7, $hash)
            ->setParameter(8, 0)
            ->setParameter(9, $dateFormated)
            ->setParameter(10, $shortURL);
        $queryBuilder->execute();
    }

    /**
     * Returns array representing the cache or false if it's not found
     *
     * @return mixed
     */
    public function getCacheByHash($hash) {
        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('cache')
            ->where('user_id = ?')
            ->andWhere('hash = ?')
            ->setParameter(0, $this->getId())
            ->setParameter(1, $hash);
        $result = $queryBuilder->execute()->fetch();

        // Add creation_time_ago to cache
        $timeAgo = new \TimeAgo();
        if ($result) {
            $result['creation_time_ago'] = $timeAgo->inWords($result['creation_moment']);
        }

        return $result;
    }

    /**
     * Deletes cache by hash if it belongs to this user
     *
     * @param $hash
     */
    public function deleteCache($hash) {
        $queryBuilder = $this->app['db']->createQueryBuilder();
        $queryBuilder
            ->delete('cache')
            ->where('user_id = ?')
            ->andWhere('hash = ?')
            ->setParameter(0, $this->getId())
            ->setParameter(1, $hash);
        $queryBuilder->execute();
    }
}
