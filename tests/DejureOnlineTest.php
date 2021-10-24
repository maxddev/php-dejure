<?php

/**
 * Testing DejureOnline - Linking texts with dejure.org, the Class(y) way
 *
 * @link https://github.com/S1SYPHOS/php-dejure
 * @license MIT
 */

namespace S1SYPHOS\Tests;


/**
 * Class DejureOnlineTest
 *
 * @package php-dejure
 */
class DejureOnlineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests
     */

    public function testInit(): void
    {
        # Setup
        # TODO: Install PHP extensions
        $array = [
            'file',
            # 'redis',
            # 'mongo',
            # 'mysql',
            'sqlite',
            # 'apc',
            # 'apcu',
            # 'memcache',
            # 'memcached',
            # 'wincache',
        ];

        foreach ($array as $cacheDriver) {
            # Run function
            $result = new \S1SYPHOS\DejureOnline($cacheDriver);

            # Assert result
            # TODO: Migrate to `assertInstanceOf`
            $this->assertInstanceOf('\S1SYPHOS\DejureOnline', $result);
        }
    }


    public function testInitInvalidCacheDriver(): void
    {
        $array = [
            '',
            '?!#@=',
            'mariadb',
            'sqlite3',
            'windows',
        ];

        # Assert exception
        $this->expectException(\Exception::class);

        foreach ($array as $cacheDriver) {
            # Run function
            $result = new \S1SYPHOS\DejureOnline($cacheDriver);
        }
    }


    public function testDejurify(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) Text containing legal norms
        $string  = '<div>';
        $string .= 'This is a <strong>simple</strong> HTML text.';
        $string .= 'It contains legal norms, like Art. 12 GG.';
        $string .= '.. or § 433 BGB!!!';
        $string .= '</div>';

        # (3) HTML content
        $dom = new \DOMDocument;

        # Run function
        @$dom->loadHTML($string);
        $result = $dom->getElementsByTagName('a');

        # Assert result
        $this->assertEquals(count($result), 0);

        # Run function
        @$dom->loadHTML($object->dejurify($string));
        $result = $dom->getElementsByTagName('a');

        # Assert result
        $this->assertEquals(count($result), 2);
    }
}
