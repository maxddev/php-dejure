<?php

/**
 * php-dejure - Linking texts with dejure.org, the Class(y) way.
 *
 * @link https:#github.com/S1SYPHOS/php-dejure
 * @license https:#opensource.org/licenses/MIT MIT
 */

namespace S1SYPHOS;


/**
 * Class DejureOnline
 *
 * Adds links to dejure.org & caches results
 *
 * @package php-dejure
 */
class DejureOnline
{
    /**
     * Constants
     */

    /**
     * Current version of php-dejure
     */
    const VERSION = '1.1.0';

    /**
     * Current API version
     */

    const DJO_VERSION = '2.22';


    /**
     * Properties
     */

    /**
     * Defines path to caching directory
     *
     * @var string
     */
    protected $cacheDir = './tmp/';

    /**
     * Defines provider designation
     *
     * @var string
     */
    protected $provider = '';

    /**
     * Defines contact email address
     *
     * @var string
     */
    protected $email = '';

    /**
     * Determines whether citation should be linked completely or rather partially
     * Possible values: 'weit' | 'schmal'
     *
     * @var string
     */
    protected $linkStyle = 'weit';

    /**
     * Controls `target` attribute
     *
     * @var string
     */
    protected $target = '';

    /**
     * Controls `class` attribute
     *
     * @var string
     */
    protected $class = '';

    /**
     * Enables linking to 'buzer.de' if legal norm not available on dejure.org
     *
     * @var bool
     */
    protected $buzer = true;

    /**
     * Defines cache duration (in days)
     *
     * @var int
     */
    protected $cacheDuration = 2;

    /**
     * Timeout period for API requests (in seconds)
     *
     * @var int
     */
    protected $timeout = 3;

    /**
     * Expired (= removed) cache entries
     *
     * @var array
     */
    public $expired = [];


    /*
     * Constructor
     */

    public function __construct(string $cacheDir = null)
    {
        # Determine path to caching path
        if (isset($cacheDir)) {
            $this->cacheDir = $cacheDir;
        }

        # Create cache directory (if not existent)
        $this->createDir($this->cacheDir);

        # Provide sensible defaults, like ..
        if (isset($_SERVER['HTTP_HOST'])) {
            # (1) .. current domain for provider designation
            $this->domain = $_SERVER['HTTP_HOST'];

            # (2) .. 'webmaster' @ current domain for contact email
            if (empty($this->email)) {
                $this->email = 'webmaster@' . $this->domain;
            }
        }
    }


    /**
     * Setters & getters
     */

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setMail(string $mail): void
    {
        $this->email = $mail;
    }

    public function getMail(): string
    {
        return $this->email;
    }

    public function setLinkStyle(string $linkStyle): void
    {
        $this->linkStyle = $linkStyle;
    }

    public function getLinkStyle(): string
    {
        return $this->linkStyle;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setBuzer(bool $buzer): void
    {
        $this->buzer = $buzer;
    }

    public function getBuzer(): string
    {
        return $this->buzer;
    }

    public function setCacheDuration(int $cacheDuration): void
    {
        $this->cacheDuration = $cacheDuration;
    }

    public function getCacheDuration(): string
    {
        return $this->cacheDuration;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function getTimeout(): string
    {
        return $this->timeout;
    }


    /**
     * Functionality
     */

    /**
     * Main function:
     * (1) Extracts linkable citations
     * (2) Processes them (if uncached) via API
     * (3) Removes expired cache entries
     *
     * @param string $text Original (unprocessed) text
     * @return string Processed text if successful, otherwise unprocessed text
     */
    public function dejurify(string $text = ''): string
    {
        # Return text as-is if no linkable citations are found
        if (!preg_match("/§|&sect;|Art\.|\/[0-9][0-9](?![0-9\/])| [0-9][0-9]?[\/\.][0-9][0-9](?![0-9\.])|[0-9][0-9], /", $text)) {
            return $text;
        }

        # Check if text was processed & cached before ..
        $result = $this->fetchCache($text);

        if (empty($result)) {
            # .. otherwise, process & cache it
            # TODO: Move caching logic to main function, so it's clear what's happening and when etc
            $result = $this->connect($text);
        }

        # Remove expired cache entries every day between 0am - 6am
        # TODO: Remove data dynamically
        if (date('G') < 6) {
            $this->resetCache();
        }

        return $result;
    }


    /**
     * Fetches processed text from cache (if present)
     *
     * @param string $text Original (unprocessed) text
     * @return string|bool Processed text from cache, otherwise false
     */
    protected function fetchCache(string $text)
    {
        # Normalize input
        $text = trim($text);

        # Convert cache duration from days to seconds
        $cacheDuration = $this->days2seconds($this->cacheDuration);

        # Create hash, using it as filename
        $hash = $this->text2hash($text);

        # If cache file exists, check if ..
        if (file_exists($this->cacheDir . $hash)) {
            # .. cache file is expired & has to be renewed, otherwise ..
            if (filemtime($this->cacheDir . $hash) < time() - $cacheDuration) {
                return false;
            }

            # .. fetch processed text from cache file
            return file_get_contents($this->cacheDir . $hash);
        }

        # Report back that cache is empty & has to be renewed
        return false;
    }


    /**
     * Stores processed text in cache
     *
     * @param string $text Original (unprocessed) text
     * @param string $result Modified (processed) text
     * @return void
     */
    protected function storeCache(string $text, string $result): void
    {
        # Check if text was processed before by using its hash as filename
        $hash = $this->text2hash($text);
        $file = fopen($this->cacheDir . $hash, 'w');

        if ($file !== false) {
            fwrite($file, $result);
            fclose($file);
        }
    }


    /**
     * Removes expired cache entries
     *
     * @return void
     */
    public function resetCache()
    {
        # Convert cache duration from days to seconds
        $cacheDuration = $this->days2seconds($this->cacheDuration);

        # Fetch time of last cache reset
        # (1) Check if file containing this information exists ..
        if (!file_exists($this->cacheDir . 'cache_status')) {
            # .. if not, create it (since apparently no cache exists)
            $this->cacheExpiry();

            return;
        }

        # (2) Load file containing last cache reset
        $lastReset = file_get_contents($this->cacheDir . 'cache_status');

        # Proceed if last reset dates back farther than one day
        if (time() - $lastReset > $this->days2seconds(1)) {
            $cacheFiles = scandir($this->cacheDir);

            if (!empty($cacheFiles[0])) {
                foreach ($cacheFiles as $cacheFile) {
                    if (in_array($cacheFile, ['.', '..']) === false) {
                        $fileTime = filemtime($this->cacheDir . $cacheFile);

                        # Delete cached files if past their expiry time
                        if ($fileTime < (time() - $cacheDuration)) {
                            unlink($this->cacheDir . $cacheFile);
                            $this->expired[time()] = $this->cacheDir . $cacheFile;
                        }
                    }
                }
            }

            # Mark cache as cleared, saving current time to file
            $this->cacheExpiry();
        }
    }


    /**
     * Creates file that contains time of last cache reset
     *
     * @return void
     */
    protected function cacheExpiry(): void
    {
        # Prepare file
        $file = fopen($this->cacheDir . 'cache_status', 'w');

        # Add timestamp
        fputs($file, mktime(0, 0, 0, date('d'), date('m'), date('Y')));
        fclose($file);
    }


    /**
     * Processes text by connecting to API:
     * (1) Sends unprocessed text
     * (2) Receives processed text
     * (3) Checks data integrity
     * (4) Stores result in cache
     *
     * @param string $text Original (unprocessed) text
     * @return string Processed text if successful, otherwise unprocessed text
     */
    protected function connect(string $text): string
    {
        # Normalize input
        # (1) Remove whitespaces from both ends of the string
        $text = trim($text);

        # (2) Link style only supports two possible options
        $linkStyle = in_array($this->linkStyle, ['weit', 'schmal']) === true
            ? $this->linkStyle
            : 'weit'
        ;

        # (3) Whether linking unknown legal norms to `buzer.de` or not needs to be an integer
        $buzer = (int)$this->buzer;

        # Note: Changing parameters requires manual cache reset!
        $parameters = [
            'Anbieterkennung' => $this->provider . '__' . $this->email,
            'format'          => $linkStyle,
            'target'          => $this->target,
            'class'           => $this->class,
            'buzer'           => $buzer,
            'version'         => 'php-' . self::DJO_VERSION,
            'Schema'          => 'https',
        ];

        # Build URL-encoded request string ..
        # (1) .. from unprocessed text
        $request = 'Originaltext=' . urlencode($text);

        # (2) .. required parameters
        foreach ($parameters as $key => $value) {
            $request .= '&' . urlencode($key) . '=' . urlencode($value);
        }

        # (3) .. and prepare request header
        $header = 'POST /dienste/vernetzung/vernetzen HTTP/1.0' . "\r\n";
        $header .= 'User-Agent: ' . $this->provider . ' (PHP-Vernetzung ' . self::DJO_VERSION. ')' . "\r\n";
        $header .= 'Content-type: application/x-www-form-urlencoded' . "\r\n";
        $header .= 'Content-length: ' . strlen($request) . "\r\n";
        $header .= 'Host: rechtsnetz.dejure.org' . "\r\n";
        $header .= 'Connection: close' . "\r\n";
        $header .= "\r\n";

        # Connect to API ..
        # (1) .. over encrypted connection
        if (extension_loaded('openssl')) {
            $handle = fsockopen('tls://rechtsnetz.dejure.org', 443, $errorCode, $errorMessage, $this->timeout);
        }

        # (2) .. alternatively, over unencrypted connection
        if ($handle === false) {
            $handle = fsockopen('rechtsnetz.dejure.org', 80, $errorCode, $errorMessage, $this->timeout);
        }

        # Return unprocessed text if connection ultimately fails ..
        if ($handle === false) {
            return $text;
        }

        # .. otherwise, send text for processing (until reaching timeout)
        stream_set_timeout($handle, $this->timeout, 0);
        stream_set_blocking($handle, true);
        fputs($handle, $header . $request);

        $socketTimeout = false;
        $socketEOF = false;
        $response = '';

        while (!$socketEOF && !$socketTimeout) {
            $response .= fgets($handle, 1024);
            $socketStatus = stream_get_meta_data($handle);
            $socketEOF = $socketStatus['eof'];
            $socketTimeout = $socketStatus['timed_out'];
        }

        fclose($handle);

        # Handle problems with data transmission, returning unprocessed text if ..
        # (1) .. timeout is reached or connection broke down
        if (!preg_match("/^(.*?)\r?\n\r?\n\r?\n?(.*)/s", $response, $matches)) {
            return $text;
        }

        # (2) .. status code indicates something other than successful transfer
        if (strpos($matches[1], '200 OK') === false) {
            return $text;
        }

        # (3) .. otherwise, transmission *may* have worked
        $response = $matches[2];

        # Check if processed text is shorter than unprocessed one, which indicates corrupted data
        if (strlen($response) < strlen($text)) {
            return $text;
        }

        # Verify data integrity by comparing original & modified text
        # (1) Normalize input
        $text = trim($text);
        $result = trim($response);

        # (2) Check if processed text (minus `dejure.org` links) matches original (unprocessed) text ..
        if (preg_replace("/<a href=\"https?:\/\/dejure.org\/[^>]*>([^<]*)<\/a>/i", "\\1", $text) == preg_replace("/<a href=\"https?:\/\/dejure.org\/[^>]*>([^<]*)<\/a>/i", "\\1", $result)) {
            # .. if so, store result in cache & return it
            $this->storeCache($text, $result);

            return $result;

        }

        # .. otherwise, return original (unprocessed) text
        return $text;
    }


    /**
     * Helpers
     */

    /**
     * Converts days to seconds
     *
     * @param int $days
     * @return int
     */
    protected function days2seconds(int $days): int
    {
        return $days * 24 * 60 * 60;
    }


    /**
     * Builds hash from text length & content
     *
     * @param string $text
     * @return string
     */
    protected function text2hash(string $text): string
    {
        return strlen($text) . md5($text);
    }


    /**
     * Creates a new directory
     *
     * Source: Kirby v3 - Bastian Allgeier
     * See https://getkirby.com/docs/reference/objects/toolkit/dir/make
     *
     * @param string $dir The path for the new directory
     * @param bool $recursive Create all parent directories, which don't exist
     * @return bool True: the dir has been created, false: creating failed
     */
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
            throw new Exception(sprintf('The directory "%s" cannot be created', $dir));
        }

        return mkdir($dir);
    }
}
