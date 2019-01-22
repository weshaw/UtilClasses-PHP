<?php
/**
 * Create cache Files from array input data
 * supports prefix and file expire times.
 * ### Usage ###
 * $cache = new CacheFile('full/path/to/cache/dir');
 * $cache->setHash(["Any","Mixed","input"]);
 * $cache->checkExpireAge(60 * 60 * 24); // remove if the file is older than 24 hours
 * $hasCache = $cache->isCached();
 * if ($hasCache) {
 *  return $cache->content();
 * }
 * // no file cache, set some;
 * $content = "works";
 * $cache->save($content);
 * return $content;
 * ### End Usage ###
 */
class CacheFile
{
    protected $hash;
    protected $cacheDir;
    protected $prefix;

    /**
     * New CacheFile Object
     *
     * @param [string] location to store the file
     */
    public function __construct($cacheDir = null)
    {
        $this->prefix = 'cachefile';
        $this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/tmp/api';

        if ($cacheDir) {
            $this->cacheDir = $cacheDir;
        }
        
        if (!file_exists($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new Exception("CacheFile Class was unable to create directory: " . $this->cacheDir);
            }
        }
    }

    /**
     * Make sure a cache file is available to retrieve content
     *
     * @param mixed Unique input
     * @return boolean
     */
    public function isCached($uniqueData = null) : bool
    {
        if ($uniqueData) {
            $this->setHash($uniqueData);
        }
        $path = $this->getFilePath();
        if (!file_exists($path)) {
            return false;
        }

        return true;
    }

    /**
     * Get the contents of the active file
     *
     * @return string File Content || null if there is no file
     */
    public function content()
    {
        if (!$this->isCached()) {
            return null;
        }

        $path = $this->getFilePath();
        return file_get_contents($path) ?? null;
    }

    /**
     * Replace file content with param
     *
     * @param string new file content
     * @return boolean
     */
    public function save(string $content) : bool
    {
        $filePath = $this->getFilePath();
        if (file_put_contents($filePath, $content) === false) {
            return false;
        }
        return true;
    }

    /**
     * Check the maximum age in seconds the file can be before the cache expires.
     * setting this to 0 will auto expire the file
     * Expired files eill be removed
     * @param int Seconds file is allowed to exist
     * @return boolean true if the file has expired
     */
    public function checkExpireAge(int $aliveTime) : bool
    {
        $expire = false;
        $filePath = $this->getFilePath();
        if ($filePath === false || !file_exists($filePath)) {
            return false;
        }

        if ($aliveTime === 0) {
            $expire = true;
        } else {
            $mtime = filemtime($filePath);
            if ($mtime === false) {
                throw new Exception("Unable to get the modified time for this file.");
            }

            if (($mtime + $aliveTime) < time()) {
                $expire = true;
            }
        }

        if ($expire) {
            unlink($filePath);
        }

        return $expire;
    }

    /**
     * Set the file prefix
     *
     * @param string File Prefix
     * @return string
     */
    public function setPrefix(string $filePrefix) : string
    {
        $this->prefix = trim(preg_replace("/[^0-9a-z]+/", "_", strtolower($filePrefix)));
        return $this->prefix;
    }

    /**
     * Takes an input mixed type of any kind and creates a hash for identifying the file later
     *
     * @param mixed Unique input
     * @return string
     */
    public function setHash($uniqueData) : string
    {
        $serialize = serialize($uniqueData);
        if (!$serialize) {
            return false;
        }

        $this->hash = md5($serialize);
        return $this->hash;
    }
    
    /**
     * Get the Currently set hash
     *
     * @return string The MD5 hash currently set
     */
    public function getHash() : string
    {
        return $this->hash ?? null;
    }

    /**
     * Returns the full cache file path
     * @return string or false if the file does not exist
     */
    private function getFilePath()
    {
        if (!$this->hash || strlen($this->hash) !== 32) {
            throw new Exception("Invalid hash id for cache file: use the setHash method to generate a file hash.");
        }
        return $this->cacheDir . '/' . $this->prefix . '-' . $this->hash . '.cache';
    }
}
