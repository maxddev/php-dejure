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
     * Properties
     */

    /**
     * @var string
     */
    private static $text;


    /**
     * Setup
     */

    public static function setUpBeforeClass(): void
    {
        # Setup
        # (1) Text
        # Enforce UTF-8 encoding
        $text = '<!DOCTYPE html><meta charset="UTF-8">';

        # Insert test string
        $text .= '<div>';
        $text .= 'This is a <strong>simple</strong> HTML text.';
        $text .= 'It contains legal norms, like Art. 12 GG.';
        $text .= '.. or § 433 BGB!';
        $text .= '.. with `lineBreak` enabled, even § 2' . "\n";
        $text .= 'DSGVO!';
        $text .= '</div>';

        self::$text = $text;
    }


    /**
     * Tests
     */

    public function testInit(): void
    {
        # Setup
        # TODO: Install PHP extensions
        # (1) Cache drivers
        $cacheDrivers = [
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

        foreach ($cacheDrivers as $cacheDriver) {
            # Run function
            $result = new \S1SYPHOS\DejureOnline($cacheDriver);

            # Assert result
            # TODO: Migrate to `assertInstanceOf`
            $this->assertInstanceOf('\S1SYPHOS\DejureOnline', $result);
        }
    }


    public function testInitInvalidCacheDriver(): void
    {
        # Setup
        # (1) Cache drivers
        $cacheDrivers = [
            '',
            '?!#@=',
            'mariadb',
            'sqlite3',
            'windows',
        ];

        # Assert exception
        $this->expectException(\Exception::class);

        foreach ($cacheDrivers as $cacheDriver) {
            # Run function
            $result = new \S1SYPHOS\DejureOnline($cacheDriver);
        }
    }


    public function testDejurify(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) HTML document
        $dom = new \DOMDocument;

        # Run function
        @$dom->loadHTML(self::$text);
        $result = $dom->getElementsByTagName('a');

        # Assert result
        $this->assertEquals(0, count($result));

        # Run function
        @$dom->loadHTML($object->dejurify(self::$text));
        $result = $dom->getElementsByTagName('a');

        # Assert result
        $this->assertEquals(3, count($result));
    }


    public function testCacheDuration(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertEquals(2, $object->getCacheDuration());

        # Run function
        $object->setCacheDuration(0);

        # Assert result
        $this->assertEquals(0, $object->getCacheDuration());
    }


    public function testDomain(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertEquals('', $object->getDomain());

        # Run function
        $object->setDomain('example.com');

        # Assert result
        $this->assertEquals('example.com', $object->getDomain());
    }


    public function testEmail(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertEquals('', $object->getEmail());

        # Run function
        $object->setEmail('test@example.com');

        # Assert result
        $this->assertEquals('test@example.com', $object->getEmail());
    }


    public function testBuzer(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertEquals(true, $object->getBuzer());

        # Run function
        $object->setBuzer(false);

        # Assert result
        $this->assertEquals(false, $object->getBuzer());
    }


    public function testClass(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) HTML document
        $dom = new \DOMDocument;

        # (3) Classes
        $classes = [
            '',
            'class',
            'another-class',
            'yet/another/class'
        ];

        # Assert default
        $this->assertEquals('', $object->getClass());

        # Run function
        foreach ($classes as $class) {
            $object->setClass($class);
            @$dom->loadHTML($object->dejurify(self::$text));

            # Assert result
            foreach ($dom->getElementsByTagName('a') as $node) {
                $this->assertEquals($class, $node->getAttribute('class'));
            }

            $this->assertEquals($class, $object->getClass());
        }
    }


    # TODO: Create test case
    public function testLineBreak(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) HTML document
        $dom = new \DOMDocument;

        # (3) Line breaks
        $lineBreaks = [
            'ohne' => 3,
            'mit' => 3,
            'auto' => 3,
            # TODO: This needs further testing ..
        ];

        # Assert default
        $this->assertEquals('auto', $object->getLineBreak());

        # Run function
        foreach ($lineBreaks as $lineBreak => $count) {
            $object->setLineBreak($lineBreak);
            @$dom->loadHTML($object->dejurify(self::$text));

            # Assert result
            $this->assertEquals($count, count($dom->getElementsByTagName('a')));

            $this->assertEquals($lineBreak, $object->getLineBreak());
        }
    }


    public function testInvalidLineBreak(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) Line breaks
        $lineBreaks = [
            'not-ohne',
            'not-mit',
            'not-auto',
        ];

        # Assert exception
        $this->expectException(\Exception::class);

        foreach ($lineBreaks as $lineBreak) {
            # Run function
            $object->setLineBreak($lineBreak);
            $object->dejurify(self::$text);
        }
    }


    public function testLinkStyle(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) HTML document
        $dom = new \DOMDocument;

        # (3) Link styles
        $linkStyles = [
            'schmal' => ['12', '433', '2'],
            'weit' => ['Art. 12 GG', '§ 433 BGB', '§ 2' . "\n" . 'DSGVO'],
        ];

        # Run function
        foreach ($linkStyles as $linkStyle => $results) {
            $object->setLinkStyle($linkStyle);
            @$dom->loadHTML($object->dejurify(self::$text));

            # Assert result
            foreach ($dom->getElementsByTagName('a') as $index => $node) {
                $this->assertEquals($results[$index], $node->textContent);
            }

            $this->assertEquals($linkStyle, $object->getLinkStyle());
        }
    }


    public function testInvalidLinkStyle(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) Link styles
        $linkStyles = [
            'not-weit',
            'not-schmal',
        ];

        # Assert exception
        $this->expectException(\Exception::class);

        foreach ($linkStyles as $linkStyle) {
            # Run function
            $object->setLinkStyle($linkStyle);
            $object->dejurify(self::$text);
        }
    }


    public function testTarget(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) HTML document
        $dom = new \DOMDocument;

        # (3) Targets
        $targets = [
            '',
            '_top',
            '_self',
            '_blank',
            '_parent',
        ];

        # Assert default
        $this->assertEquals('', $object->getTarget());

        # Run function
        foreach ($targets as $target) {
            $object->setTarget($target);
            @$dom->loadHTML($object->dejurify(self::$text));

            # Assert result
            foreach ($dom->getElementsByTagName('a') as $node) {
                $this->assertEquals($target, $node->getAttribute('target'));
            }

            $this->assertEquals($target, $object->getTarget());
        }
    }


    public function testTooltip(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) HTML document
        $dom = new \DOMDocument;

        # (3) Tooltips
        $tooltips = [
            'ohne' => ['', '', ''],
            'neutral' => ['Gesetzestext über dejure.org', 'Gesetzestext über dejure.org', 'Gesetzestext über dejure.org'],
            'beschreibend' => ['Art. 12 GG', '§ 433 BGB: Vertragstypische Pflichten beim Kaufvertrag', 'Art. 2 DSGVO: Sachlicher Anwendungsbereich'],
            'Gesetze' => ['Art. 12 GG', '§ 433 BGB: Vertragstypische Pflichten beim Kaufvertrag', 'Art. 2 DSGVO: Sachlicher Anwendungsbereich'],
            'halb' => ['Art. 12 GG', '§ 433 BGB: Vertragstypische Pflichten beim Kaufvertrag', 'Art. 2 DSGVO: Sachlicher Anwendungsbereich'],
        ];

        # Run function
        foreach ($tooltips as $tooltip => $results) {
            $object->setTooltip($tooltip);
            @$dom->loadHTML($object->dejurify(self::$text));

            # Assert result
            foreach ($dom->getElementsByTagName('a') as $index => $node) {
                $this->assertEquals($results[$index], $node->getAttribute('title'));
            }

            $this->assertEquals($tooltip, $object->getTooltip());
        }
    }


    public function testInvalidTooltip(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # (2) Tooltips
        $tooltips = [
            'not-ohne',
            'not-neutral',
            'not-beschreibend',
            'not-Gesetze',
            'not-halb',
        ];

        # Assert exception
        $this->expectException(\Exception::class);

        foreach ($tooltips as $tooltip) {
            # Run function
            $object->setTooltip($tooltip);
            $object->dejurify(self::$text);
        }
    }


    public function testStreamTimeout(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertEquals(10, $object->getStreamTimeout());

        # Run function
        $object->setStreamTimeout(0);

        # Assert result
        $this->assertEquals(0, $object->getStreamTimeout());
    }


    public function testTimeout(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertEquals(3, $object->getTimeout());

        # Run function
        $object->setTimeout(0);

        # Assert result
        $this->assertEquals(0, $object->getTimeout());
    }


    public function testUserAgent(): void
    {
        # Setup
        # (1) Instance
        $object = new \S1SYPHOS\DejureOnline();

        # Assert default
        $this->assertIsString($object->getUserAgent());

        # Run function
        $object->setUserAgent('UA MyBrowser v1.2.3 test@example.com');

        # Assert result
        $this->assertEquals('UA MyBrowser v1.2.3 test@example.com', $object->getUserAgent());
    }
}
