<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests;


use AppBundle\Cache\FileCache;
use AppBundle\Cache\FileCachePathGenerator;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private static string $tmpTestPath;
    
    public static function setUpBeforeClass(): void
    {
        if (file_exists(__DIR__ . '/../var')) {
            $tmpTestPath = __DIR__ . '/../var/tmp/' . uniqid('test_');
        } else {
            $tmpTestPath = sys_get_temp_dir() . '/' . uniqid('test_');
        }
        $umask = umask();
        umask(0);
        if (!mkdir($tmpTestPath, 0777, true)) {
            umask($umask);
            throw new \RuntimeException('Precondition failed: Tmp dir inaccessible');
        }
        umask($umask);
        self::$tmpTestPath = $tmpTestPath;
    }
    
    public function setUp(): void
    {
        self::truncateTmpTestPath();
    }
    
    public static function truncateTmpTestPath(): void
    {
        if (file_exists(self::$tmpTestPath)) {
            $it    = new \RecursiveDirectoryIterator(self::$tmpTestPath, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator(
                $it,
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$tmpTestPath)) {
            self::truncateTmpTestPath();
            rmdir(self::$tmpTestPath);
        }
        parent::tearDownAfterClass();
    }
    
    public function testContainsNothingProvidesFalse(): void
    {
        $f      = new FileCache(self::$tmpTestPath);
        $result = $f->contains($this->mockFileCachePathPathGenerator());
        
        $this->assertFalse($result);
    }
    
    public function testContainsExistingProvidesTrue(): void
    {
        touch(self::$tmpTestPath . '/p');
        $f      = new FileCache(self::$tmpTestPath);
        $result = $f->contains($this->mockFileCachePathPathGenerator());
        
        $this->assertTrue($result);
    }
    
    public function testFetchNothingCausesException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $f = new FileCache(self::$tmpTestPath);
        $f->fetch($this->mockFileCachePathPathGenerator());
    }
    
    public function testFetchExistingProvidesFile(): void
    {
        $expectedContent = random_int(PHP_INT_MIN, PHP_INT_MAX);
        $expectedPath    = self::$tmpTestPath . '/p';
        file_put_contents($expectedPath, $expectedContent);
        $f      = new FileCache(self::$tmpTestPath);
        $result = $f->fetch($this->mockFileCachePathPathGenerator());
        $this->assertEquals($expectedPath, $result->getPathname());
        $this->assertFileExists($result->getPathname());
    }
    
    public function testSaveString(): void
    {
        $expectedContent = (string)random_int(PHP_INT_MIN, PHP_INT_MAX);
        $expectedPath    = self::$tmpTestPath . '/p';
        $f               = new FileCache(self::$tmpTestPath);
        $result          = $f->save($this->mockFileCachePathPathGenerator(), $expectedContent);
        $this->assertFileExists($expectedPath);
        $this->assertTrue($result);
        $this->assertEquals($expectedContent, file_get_contents($expectedPath));
    }
    
    public function testSaveFileInfo(): void
    {
        $expectedContent = (string)random_int(PHP_INT_MIN, PHP_INT_MAX);
        $expectedPath    = self::$tmpTestPath . '/p';
        
        $sourcePath = self::$tmpTestPath . '/s';
        file_put_contents($sourcePath, $expectedContent);
        $sourceInfo = new \SplFileInfo($sourcePath);
        
        $f      = new FileCache(self::$tmpTestPath);
        $result = $f->save($this->mockFileCachePathPathGenerator(), $sourceInfo);
        
        $this->assertFileExists($expectedPath);
        $this->assertTrue($result);
        $this->assertEquals($expectedContent, file_get_contents($expectedPath));
    }
    
    public function testSaveResource(): void
    {
        $expectedContent = (string)random_int(PHP_INT_MIN, PHP_INT_MAX);
        $expectedPath    = self::$tmpTestPath . '/p';

        $sourcePath = self::$tmpTestPath . '/s';
        file_put_contents($sourcePath, $expectedContent);
        $sourceStream = fopen($sourcePath, 'r');
        
        $f      = new FileCache(self::$tmpTestPath);
        $result = $f->save($this->mockFileCachePathPathGenerator(), $sourceStream);
        
        $this->assertFileExists($expectedPath);
        $this->assertTrue($result);
        $this->assertEquals($expectedContent, file_get_contents($expectedPath));
    }
    
    public function testSaveUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $f      = new FileCache(self::$tmpTestPath);
        $f->save($this->mockFileCachePathPathGenerator(), new \ArrayObject());
        
        $expectedPath    = self::$tmpTestPath . '/p';
        $this->assertFileDoesNotExist($expectedPath);
    }
    
    /**
     * @return FileCachePathGenerator
     */
    public function mockFileCachePathPathGenerator(): FileCachePathGenerator
    {
        $mock = $this->getMockBuilder(FileCachePathGenerator::class)->getMock();
        $mock->method('getPath')->willReturn('p');
        return $mock;
    }
    
}
