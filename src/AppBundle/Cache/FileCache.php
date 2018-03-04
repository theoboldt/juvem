<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Cache;


class FileCache
{
    /**
     * Root directory containing cache files
     *
     * @var string
     */
    private $cacheDir;

    /**
     * FileCache constructor.
     *
     * @param string $cacheDir
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Determine if entry exists in cache
     *
     * @param FileCachePathGenerator $key Value to check
     * @return bool
     */
    public function contains(FileCachePathGenerator $key)
    {
        return file_exists($this->path($key->getPath()));
    }

    /**
     * Fetch element from cache
     *
     * @param FileCachePathGenerator $key Value
     * @return \SplFileInfo
     */
    public function fetch(FileCachePathGenerator $key)
    {
        if (!$this->contains($key)) {
            throw new \InvalidArgumentException('Cache miss');
        }
        return new \SplFileInfo($this->path($key->getPath()));
    }

    /**
     * Save into cache
     *
     * @param FileCachePathGenerator       $key   Key
     * @param resource|string|\SplFileInfo $value Data
     * @return bool|int
     */
    public function save(FileCachePathGenerator $key, $value)
    {
        if (is_resource($value)) {
            return $this->saveResource($key, $value);
        } elseif (is_string($value)) {
            return $this->saveString($key, $value);
        } elseif ($value instanceof \SplFileInfo) {
            return $this->saveFileInfo($key, $value);
        }
        throw new \InvalidArgumentException('Value has unknown type and can not be handled');
    }

    /**
     * Save file
     *
     * @param FileCachePathGenerator $key   Key
     * @param \SplFileInfo           $value Data File
     * @return bool
     */
    public function saveFileInfo(FileCachePathGenerator $key, \SplFileInfo $value)
    {
        $this->ensureDirectoryExists($key);
        $source = $value->getPathname();
        $target = $this->path($key->getPath());
        return copy($source, $target);
    }

    /**
     * Save file resource
     *
     * @param FileCachePathGenerator $key   Key
     * @param resource               $value Data resource
     * @return bool
     */
    public function saveResource(FileCachePathGenerator $key, resource $value)
    {
        $this->ensureDirectoryExists($key);
        $target = fopen($this->path($key), 'w');
        while (!feof($value)) {
            fwrite($target, fread($value, 8192));
        }
        fclose($target);
        return true;
    }

    /**
     * Save file string
     *
     * @param FileCachePathGenerator $key   Key
     * @param string                 $value Data
     * @return bool|int
     */
    public function saveString(FileCachePathGenerator $key, string $value)
    {
        $this->ensureDirectoryExists($key);
        return file_put_contents($key->getPath(), $value);
    }

    /**
     * Ensure that root dir of cached file exists
     *
     * @param FileCachePathGenerator $key Value
     */
    private function ensureDirectoryExists(FileCachePathGenerator $key)
    {
        $dir = dirname($this->path($key));
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Get absolute path using cache dir and transmitted dir
     *
     * @param  string $pathPart Path
     * @return string Full path
     */
    private function path(string $pathPart)
    {
        return rtrim($this->cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
               ltrim($pathPart, DIRECTORY_SEPARATOR);
    }

}