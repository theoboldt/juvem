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
    private string $cacheDir;

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
    public function contains(FileCachePathGenerator $key): bool
    {
        return file_exists($this->path($key->getPath()));
    }

    /**
     * Fetch element from cache
     *
     * @param FileCachePathGenerator $key Value
     * @return \SplFileInfo
     */
    public function fetch(FileCachePathGenerator $key): \SplFileInfo
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
     * @return bool
     */
    public function save(FileCachePathGenerator $key, $value): bool
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
    public function saveFileInfo(FileCachePathGenerator $key, \SplFileInfo $value): bool
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
    public function saveResource(FileCachePathGenerator $key, $value): bool
    {
        if (!is_resource($value)) {
            throw new \InvalidArgumentException('A resource must be transmitted');
        }
        $this->ensureDirectoryExists($key);
        $target = fopen($this->path($key->getPath()), 'w');
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
     * @return bool
     */
    public function saveString(FileCachePathGenerator $key, string $value): bool
    {
        $this->ensureDirectoryExists($key);
        if (file_put_contents($this->path($key->getPath()), $value) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Ensure that root dir of cached file exists
     *
     * @param FileCachePathGenerator $key Value
     */
    private function ensureDirectoryExists(FileCachePathGenerator $key): void
    {
        $dir = dirname($this->path($key->getPath()));
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException('Failed to create directory ' . $dir);
            }
        }
    }

    /**
     * Get absolute path using cache dir and transmitted dir
     *
     * @param  string $pathPart Path
     * @return string Full path
     */
    private function path(string $pathPart): string
    {
        return rtrim($this->cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
               ltrim($pathPart, DIRECTORY_SEPARATOR);
    }

}