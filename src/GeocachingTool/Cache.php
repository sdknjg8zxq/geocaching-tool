<?php

namespace GeocachingTool;

class Cache
{
    private $app;
    private $userId;
    private $title;
    private $description;
    private $author;
    private $viewsCount;
    private $shortURL;
    private $clue;
    private $message;
    private $imagePath;
    private $keywords;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return mixed
     */
    public function getViewsCount()
    {
        return $this->viewsCount;
    }

    /**
     * @param mixed $viewsCount
     */
    public function setViewsCount($viewsCount)
    {
        $this->viewsCount = $viewsCount;
    }

    /**
     * @return mixed
     */
    public function getShortURL()
    {
        return $this->shortURL;
    }

    /**
     * @param mixed $shortURL
     */
    public function setShortURL($shortURL)
    {
        $this->shortURL = $shortURL;
    }

    /**
     * @return mixed
     */
    public function getClue()
    {
        return $this->clue;
    }

    /**
     * @param mixed $clue
     */
    public function setClue($clue)
    {
        $this->clue = $clue;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param mixed $imagePath
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param mixed $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    public static function getCacheByHash($app, $hash) {
        $queryBuilder = $app['db']->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('cache')
            ->where('hash = ?')
            ->setParameter(0, $hash);
        return $queryBuilder->execute()->fetch();
    }

    public static function getNewHash() {
        return md5(uniqid());
    }

    public static function incrementView($app, $hash) {
        $queryBuilder = $app['db']->createQueryBuilder();
        $queryBuilder
            ->update('cache', 'c')
            ->where('hash = ?')
            ->set('c.views_count', 'c.views_count + 1')
            ->setParameter(0, $hash);
        $queryBuilder->execute();
    }
}
