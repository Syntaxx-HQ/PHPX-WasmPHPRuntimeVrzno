<?php

namespace Syntaxx\PhpWasm;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;

class Installer
{
    private const RELEASE_URL_PATTERN = 'https://github.com/Syntaxx-HQ/PHPX-phpwasmbuilder/releases/download/v%s/php-vrzno-web.%s';
    private const FILES = [
        'mjs' => 'php-vrzno-web.mjs',
        'wasm' => 'php-vrzno-web.wasm'
    ];

    public static function install(Event $event)
    {
        $composer = $event->getComposer();
        $io = $event->getIO();
        $package = $composer->getPackage();
        $version = self::getVersion($package);
        
        $io->write(sprintf('Installing PHP WASM version: %s', $version));
        
        $config = $package->getExtra()['php-wasm'] ?? [];
        $targetDir = $config['target-dir'] ?? __DIR__ . '/../../../public/wasm';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach (self::FILES as $extension => $filename) {
            $url = sprintf(self::RELEASE_URL_PATTERN, $version, $extension);
            $targetPath = $targetDir . '/' . $filename;

            try {
                if (self::downloadFile($url, $targetPath)) {
                    $io->write(sprintf('Downloaded %s to %s', $filename, $targetPath));
                }
            } catch (\Exception $e) {
                $io->writeError(sprintf('Error downloading %s: %s', $filename, $e->getMessage()));
                throw $e; // Re-throw to ensure Composer knows about the failure
            }
        }
    }

    private static function getVersion(PackageInterface $package): string
    {
        $version = $package->getVersion();
        // Remove 'v' prefix if present and any dev/alpha/beta suffixes
        return preg_replace('/^v|(-dev|-alpha|-beta).*$/', '', $version);
    }

    private static function downloadFile(string $url, string $targetPath): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Composer/1.0',
                ],
                'ignore_errors' => true, // This allows us to get the response even for error status codes
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            $error = error_get_last();
            throw new \RuntimeException(sprintf(
                "Failed to download from URL: %s\nError: %s",
                $url,
                $error['message'] ?? 'Unknown error'
            ));
        }

        // Check HTTP status code
        if (isset($http_response_header[0])) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? null;
            
            if ($statusCode === '404') {
                throw new \RuntimeException(sprintf(
                    "File not found (404): %s\nPlease check if the version exists in the releases.",
                    $url
                ));
            }
            
            if ($statusCode >= 400) {
                throw new \RuntimeException(sprintf(
                    "HTTP error %s while downloading: %s\nResponse: %s",
                    $statusCode,
                    $url,
                    $content
                ));
            }
        }

        $result = file_put_contents($targetPath, $content);
        if ($result === false) {
            throw new \RuntimeException(sprintf(
                "Failed to write file to: %s\nError: %s",
                $targetPath,
                error_get_last()['message'] ?? 'Unknown error'
            ));
        }

        return true;
    }
}
