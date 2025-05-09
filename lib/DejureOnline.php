<?php

namespace S1SYPHOS;

use Illuminate\Support\Facades\Cache;

/**
 * Class DejureOnline
 *
 * Adds links to dejure.org & caches results
 *
 * @package php-dejure
 */
class DejureOnline
{
    private $version = '1.5.1';

    private $baseUrl = 'https://rechtsnetz.dejure.org';
    protected $domain = '';
    protected $email = '';
    protected $buzer = true;
    protected $class = '';
    protected $lineBreak = 'auto';
    protected $linkStyle = 'weit';
    protected $target = '';
    protected $tooltip = 'beschreibend';
    protected $streamTimeout = 10;
    protected $timeout = 3;
    protected $userAgent;
    protected $cacheDuration = 2; // in days
    public $fromCache = false;

    public function __construct()
    {
        $this->userAgent = 'php-dejure v' . $this->version;

        if (isset($_SERVER['HTTP_HOST'])) {
            $this->domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->email = 'webmaster@' . $this->domain;
            $this->userAgent .= ' @ ' . $this->domain;
        }
    }

    // Setters & getters...
    public function setCacheDuration(int $cacheDuration): void { $this->cacheDuration = $cacheDuration; }
    public function getCacheDuration(): int { return $this->cacheDuration; }
    public function setDomain(string $domain): void { $this->domain = $domain; }
    public function getDomain(): string { return $this->domain; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getEmail(): string { return $this->email; }
    public function setBuzer(bool $buzer): void { $this->buzer = $buzer; }
    public function getBuzer(): bool { return $this->buzer; }
    public function setClass(string $class): void { $this->class = $class; }
    public function getClass(): string { return $this->class; }
    public function setLineBreak(string $lineBreak): void { $this->lineBreak = $lineBreak; }
    public function getLineBreak(): string { return $this->lineBreak; }
    public function setLinkStyle(string $linkStyle): void { $this->linkStyle = $linkStyle; }
    public function getLinkStyle(): string { return $this->linkStyle; }
    public function setTarget(string $target): void { $this->target = $target; }
    public function getTarget(): string { return $this->target; }
    public function setTooltip(string $tooltip): void { $this->tooltip = $tooltip; }
    public function getTooltip(): string { return $this->tooltip; }
    public function setStreamTimeout(int $streamTimeout): void { $this->streamTimeout = $streamTimeout; }
    public function getStreamTimeout(): int { return $this->streamTimeout; }
    public function setTimeout(int $timeout): void { $this->timeout = $timeout; }
    public function getTimeout(): int { return $this->timeout; }
    public function setUserAgent(string $userAgent): void { $this->userAgent = $userAgent; }
    public function getUserAgent(): string { return $this->userAgent; }

    /**
     * Processes linkable citations & caches text (if uncached or expired)
     *
     * @param string $text Original (unprocessed) text
     * @param string $ignore Judicial file numbers to be ignored
     *
     * @return string Processed text if successful, otherwise unprocessed text
     * @throws \Exception
     */
    public function dejurify(string $text = '', string $ignore = ''): string
    {
        if (!preg_match("((?:§|&sect;|Art\.)\s*[0-9]+\s*[a-z]?\s\w+)", $text)) {
            return $text;
        }

        $text = trim($text);
        $this->fromCache = false;

        try {
            $query = $this->createQuery($text, $ignore);
        } catch (\Exception $e) {
            throw $e;
        }

        $hash = $this->query2hash($query);

        // Laravel Cache: check and fetch
        if (Cache::has($hash)) {
            $this->fromCache = true;
            return Cache::get($hash);
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $this->timeout,
        ]);

        try {
            $response = $client->request('GET', '/dienste/vernetzung/vernetzen', [
                'query'        => $query,
                'stream'       => true,
                'read_timeout' => $this->streamTimeout,
                'headers'      => [
                    'User-Agent' => $this->userAgent,
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8;'
                ],
            ]);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            return $text;
        }

        if ($response->getStatusCode() !== 200) {
            return $text;
        }

        $body = $response->getBody();
        $result = '';
        while (!$body->eof()) {
            $result .= $body->read(1024);
        }
        $result = trim($result);

        if (strlen($result) < strlen($text)) {
            return $text;
        }

        if (preg_replace("/<a href=\"https?:\/\/dejure.org\/[^>]*>([^<]*)<\/a>/i", "\\1", $text) == preg_replace("/<a href=\"https?:\/\/dejure.org\/[^>]*>([^<]*)<\/a>/i", "\\1", $result)) {
            // Laravel Cache: store
            Cache::put($hash, $result, $this->days2seconds($this->cacheDuration));
            return $result;
        }

        return $text;
    }

    /**
     * Clears cache
     *
     * @return bool Whether cache was cleared
     */
    public function clearCache(): bool
    {
        $this->fromCache = false;
        // Laravel Cache: flushes the entire cache store
        return Cache::flush();
    }

    /**
     * Helpers
     */
    protected function createQuery(string $text, string $ignore): array
    {
        if (in_array($this->linkStyle, ['weit', 'schmal']) === false) {
            throw new \Exception(sprintf('Invalid link style: "%s"', $this->linkStyle));
        }
        if (in_array($this->tooltip, ['ohne', 'neutral', 'beschreibend', 'Gesetze', 'halb']) === false) {
            throw new \Exception(sprintf('Invalid tooltip: "%s"', $this->tooltip));
        }
        if (in_array($this->lineBreak, ['ohne', 'mit', 'auto']) === false) {
            throw new \Exception(sprintf('Invalid tooltip: "%s"', $this->tooltip));
        }

        return [
            'Originaltext'           => $text,
            'AktenzeichenIgnorieren' => $ignore,
            'Anbieterkennung'        => $this->domain . '-' . $this->email,
            'format'                 => $this->linkStyle,
            'Tooltip'                => $this->tooltip,
            'Zeilenwechsel'          => $this->lineBreak,
            'target'                 => $this->target,
            'class'                  => $this->class,
            'buzer'                  => $this->buzer,
            'version'                => 'php-dejure@' . $this->version,
        ];
    }

    protected function query2hash(array $query): string
    {
        return strlen($query['Originaltext']) . md5(json_encode($query));
    }

    protected function days2seconds(int $days): int
    {
        return $days * 24 * 60 * 60;
    }

    // The createDir method is now unused and can be removed, but kept here for reference
    protected function createDir(string $dir, bool $recursive = true): bool
    {
        if (empty($dir) === true) {
            return false;
        }

        if (is_dir($dir) === true) {
            return true;
        }

        $parent = dirname($dir);

        if ($recursive === true) {
            if (is_dir($parent) === false) {
                $this->createDir($parent, true);
            }
        }

        if (is_writable($parent) === false) {
            throw new \Exception(sprintf('The directory "%s" cannot be created', $dir));
        }

        return mkdir($dir);
    }
}
