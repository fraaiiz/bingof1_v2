<?php

namespace App\Service;

use ErrorException;
use JsonException;
use RuntimeException;
use SimpleXMLElement;

class NewsFeed
{
    private const CACHE_TTL = 600;
    private const REQUEST_TIMEOUT = 5;
    private const MAX_ITEMS = 10;
    private const RSS_URL = 'https://fr.motorsport.com/rss/f1/news/';

    private string $cachePath;

    public function __construct(?string $cachePath = null)
    {
        $this->cachePath = $cachePath
            ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bingof1-actus-cache.json';
    }

    public function get(): array
    {
        $cached = $this->readCache();
        if ($cached !== null && time() - $cached['fetched_at'] < self::CACHE_TTL) {
            return [
                'items' => $cached['items'],
                'stale' => false,
                'available' => true,
            ];
        }

        try {
            $items = $this->fetchItems();
            $this->writeCache($items);

            return [
                'items' => $items,
                'stale' => false,
                'available' => true,
            ];
        } catch (ErrorException|RuntimeException $exception) {
            error_log('Unable to refresh F1 news feed: ' . $exception->getMessage());

            if ($cached !== null) {
                return [
                    'items' => $cached['items'],
                    'stale' => true,
                    'available' => true,
                ];
            }

            return [
                'items' => [],
                'stale' => false,
                'available' => false,
            ];
        }
    }

    private function fetchItems(): array
    {
        if (!function_exists('simplexml_load_string')) {
            throw new RuntimeException('The SimpleXML extension is not available.');
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => self::REQUEST_TIMEOUT,
                'header' => "User-Agent: BingoF1/1.0\r\n",
            ],
        ]);

        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        });

        try {
            $xmlContent = file_get_contents(self::RSS_URL, false, $context);
        } finally {
            restore_error_handler();
        }

        if ($xmlContent === false || $xmlContent === '') {
            throw new RuntimeException('The RSS response was empty.');
        }

        $previousLibxmlSetting = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $rss = simplexml_load_string($xmlContent, SimpleXMLElement::class, LIBXML_NONET);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        if ($rss === false) {
            $errorMessage = $xmlErrors === [] ? 'Invalid RSS XML.' : trim($xmlErrors[0]->message);
            throw new RuntimeException('Unable to parse RSS XML: ' . $errorMessage);
        }

        $items = [];
        foreach ($rss->channel->item as $item) {
            $title = trim((string) $item->title);
            $link = self::httpUrl(trim((string) $item->link));
            if ($title === '' || $link === null) {
                continue;
            }

            $publishedAt = strtotime((string) $item->pubDate);
            $items[] = [
                'title' => $title,
                'link' => $link,
                'date' => $publishedAt === false ? '' : date('d/m H:i', $publishedAt),
            ];

            if (count($items) === self::MAX_ITEMS) {
                break;
            }
        }

        return $items;
    }

    private function readCache(): ?array
    {
        if (!is_file($this->cachePath) || !is_readable($this->cachePath)) {
            return null;
        }

        $contents = file_get_contents($this->cachePath);
        if ($contents === false) {
            error_log('Unable to read F1 news cache: ' . $this->cachePath);
            return null;
        }

        try {
            $cache = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            error_log('Unable to decode F1 news cache: ' . $exception->getMessage());
            return null;
        }

        if (!is_array($cache)
            || !is_int($cache['fetched_at'] ?? null)
            || !is_array($cache['items'] ?? null)) {
            error_log('F1 news cache has an invalid structure.');
            return null;
        }

        $items = [];
        foreach ($cache['items'] as $item) {
            if (!is_array($item)
                || !is_string($item['title'] ?? null)
                || !is_string($item['link'] ?? null)
                || !is_string($item['date'] ?? null)) {
                error_log('F1 news cache contains an invalid item.');
                return null;
            }

            $link = self::httpUrl($item['link']);
            if ($link === null) {
                continue;
            }

            $item['link'] = $link;
            $items[] = $item;
        }

        return [
            'fetched_at' => $cache['fetched_at'],
            'items' => $items,
        ];
    }

    public static function httpUrl(string $link): ?string
    {
        $parts = parse_url(trim($link));
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        return trim($link);
    }

    private function writeCache(array $items): void
    {
        $directory = dirname($this->cachePath);
        $temporaryPath = tempnam($directory, 'bingof1-news-');
        if ($temporaryPath === false) {
            error_log('Unable to create a temporary F1 news cache file in ' . $directory);
            return;
        }

        try {
            $contents = json_encode([
                'fetched_at' => time(),
                'items' => $items,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
                error_log('Unable to write temporary F1 news cache file: ' . $temporaryPath);
                return;
            }

            if (!rename($temporaryPath, $this->cachePath)) {
                error_log('Unable to replace F1 news cache: ' . $this->cachePath);
            }
        } catch (JsonException $exception) {
            error_log('Unable to encode F1 news cache: ' . $exception->getMessage());
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
