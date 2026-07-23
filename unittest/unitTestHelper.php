<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class unitTestHelper extends TestCase
{
    protected $instance;
    /* support for private / protected properties and methods */

    protected function getPrivatePublic($attribute, $instance = null)
    {
        $instance = ($instance) ?? $this->instance;

        $getter = function () use ($attribute) {
            return $this->$attribute;
        };

        $closure = \Closure::bind($getter, $instance, get_class($instance));

        return $closure();
    }

    protected function setPrivatePublic($attribute, $value, $instance = null)
    {
        $instance = ($instance) ?? $this->instance;

        $setter = function ($value) use ($attribute) {
            $this->$attribute = $value;
        };

        $closure = \Closure::bind($setter, $instance, get_class($instance));

        $closure($value);
    }

    protected function callMethod(string $method, ?array $args = null, $instance = null)
    {
        $instance = ($instance) ?? $this->instance;

        $reflectionMethod = new ReflectionMethod($instance, $method);

        return (is_array($args)) ? $reflectionMethod->invokeArgs($instance, $args) : $reflectionMethod->invoke($instance);
    }

    protected function stripInvisible(string $string): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    }

    /* create a fresh temporary directory and return its absolute path */
    protected function makeTempDir(string $prefix = 'hb-'): string
    {
        $dir = sys_get_temp_dir() . '/' . $prefix . bin2hex(random_bytes(6));

        mkdir($dir, 0777, true);

        return $dir;
    }

    /* recursively remove a directory created by makeTempDir() */
    protected function removeTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;

            is_dir($path) ? $this->removeTempDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
