<?php

declare(strict_types=1);

namespace Icosillion\SecretKeeper;

use Icosillion\SecretKeeper\Exceptions\InvalidPathException;
use PHPUnit\Framework\TestCase;

class SecretKeeperTest extends TestCase
{
    private const SECRETS_DIRECTORY = '/tmp/secret-keeper/';

    public function setUp()
    {
        //Check tmp is writable
        if (!is_writable('/tmp/')) {
            $this->fail('/tmp/ must be writable!');
        }

        //Create /tmp/secret-keeper/
        if (!file_exists(self::SECRETS_DIRECTORY)) {
            mkdir(self::SECRETS_DIRECTORY);
        }

        //Write secrets
        $this->writeSecret('testsecret', 'This is a secret');
        $this->writeSecret('anothersecret', 'This is another secret');
    }

    public function tearDown()
    {
        unset($_ENV['testsecret'], $_ENV['anothersecret']);
        putenv('testsecret');
        putenv('anothersecret');
        $this->deleteDirectory(self::SECRETS_DIRECTORY);
    }

    public function testInvalidSecretsDirectory()
    {
        $this->expectException(InvalidPathException::class);
        new SecretKeeper('/var/thisdirectorshouldnotexist/');
    }

    public function testLoadSingleSecret()
    {
        $secretKeeper = new SecretKeeper(self::SECRETS_DIRECTORY);
        $this->assertEquals('This is a secret', $secretKeeper->load('testsecret'));
    }

    public function testLoadSingleMissingSecret()
    {
        $secretKeeper = new SecretKeeper(self::SECRETS_DIRECTORY);
        $this->assertNull($secretKeeper->load('thissecretshouldnotexist'));
    }

    public function testLoadAll()
    {
        $secretKeeper = new SecretKeeper(self::SECRETS_DIRECTORY);
        $secrets = $secretKeeper->loadAll();
        $this->assertArraySubset([
            'testsecret' => 'This is a secret',
            'anothersecret' => 'This is another secret'
        ], $secrets);
    }

    public function testPopulateEnvironment()
    {
        $secretKeeper = new SecretKeeper(self::SECRETS_DIRECTORY);
        $secretKeeper->populateEnvironment();
        $this->assertEquals('This is a secret', $_ENV['testsecret']);
        $this->assertEquals('This is a secret', getenv('testsecret'));
        $this->assertEquals('This is another secret', $_ENV['anothersecret']);
        $this->assertEquals('This is another secret', getenv('anothersecret'));
    }

    public function testPopulateEnvironmentNoOverwrite()
    {
        putenv('testsecret=This should not be overwritten');
        $_ENV['testsecret'] = 'This should not be overwritten';
        $secretKeeper = new SecretKeeper(self::SECRETS_DIRECTORY);
        $secretKeeper->populateEnvironment();
        $this->assertEquals('This should not be overwritten', $_ENV['testsecret']);
        $this->assertEquals('This should not be overwritten', getenv('testsecret'));
    }

    private function writeSecret(string $key, string $value)
    {
        file_put_contents(self::SECRETS_DIRECTORY . $key, "$value\n");
    }

    private function deleteDirectory(string $directory)
    {
        $files = scandir($directory, \SCANDIR_SORT_ASCENDING);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            unlink($directory . $file);
        }

        rmdir($directory);
    }
}