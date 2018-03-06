<?php

declare(strict_types=1);

namespace Icosillion\SecretKeeper;

use Icosillion\SecretKeeper\Exceptions\InvalidPathException;

class SecretKeeper
{
    /**
     * @var string
     */
    private $secretsDirectory;

    /**
     * @var bool
     */
    private $stripWhitespace;

    /**
     * @param string $secretsDirectory
     * @param bool $stripWhitespace
     * @throws InvalidPathException
     */
    public function __construct($secretsDirectory = '/run/secrets/', $stripWhitespace = true)
    {
        if (!is_dir($secretsDirectory)) {
            throw new InvalidPathException("Path '$secretsDirectory' is not a directory.");
        }

        $this->secretsDirectory = $secretsDirectory;
        $this->stripWhitespace = $stripWhitespace;
    }

    /**
     * Fetches a single Docker secret
     *
     * @param string $key
     * @return string|null
     */
    public function load(string $key): ?string
    {
        $secretPath = $this->secretsDirectory . "/$key";
        if (!is_file($secretPath)) {
            return null;
        }

        return $this->processValue(file_get_contents($secretPath));
    }

    /**
     * Fetches all available Docker secrets
     *
     * @return array
     */
    public function loadAll(): array
    {
        $filenames = scandir($this->secretsDirectory, \SCANDIR_SORT_ASCENDING);
        $filenames = array_filter($filenames, function ($filename) {
            return $filename !== '.' || $filename !== '..';
        });

        $secrets = [];
        foreach ($filenames as $filename) {
            $secrets[$filename] = $this->load($filename);
        }

        return $secrets;
    }

    /**
     * Populates the environment with all available Docker secrets.
     * These can be accessed though $_ENV and \getenv()
     *
     * @param bool $overwrite
     */
    public function populateEnvironment(bool $overwrite = false): void
    {
        $secrets = $this->loadAll();
        foreach ($secrets as $key => $value) {
            $this->setENVSuperglobal($key, $value, $overwrite);
            $this->setPutenv($key, $value, $overwrite);
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $overwrite
     */
    private function setPutenv(string $key, ?string $value, bool $overwrite = false): void
    {
        if (getenv($key) !== false && !$overwrite) {
            return;
        }

        if ($value === null) {
            putenv("$key");
        } else {
            putenv("$key=$value");
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $overwrite
     */
    private function setENVSuperglobal(string $key, ?string $value, bool $overwrite = false): void
    {
        if (array_key_exists($key, $_ENV) && !$overwrite) {
            return;
        }

        $_ENV[$key] = $value;
    }

    /**
     * @param string $value
     * @return string
     */
    private function processValue(string $value): string
    {
        return $this->stripWhitespace ? trim($value) : $value;
    }
}