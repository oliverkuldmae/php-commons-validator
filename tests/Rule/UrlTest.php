<?php

namespace PHPCommons\Validator\Tests\Rule;

use PHPCommons\Validator\Rule\Url;
use PHPCommons\Validator\Tests\ResultPair;
use PHPUnit\Framework\TestCase;
use function count;

class UrlTest extends TestCase {

    const SCHEMES = [
        "http",
        "gopher",
        "g0-To+.",
        "not_valid"// TODO this will need to be dropped if the ctor validates schemes
    ];

    /** @var ResultPair[] */
    private $testUrlScheme;
    /** @var ResultPair[] */
    private $testUrlAuthority;
    /** @var ResultPair[] */
    private $testUrlPort;
    /** @var ResultPair[] */
    private $testPath;
    /** @var ResultPair[] */
    private $testUrlPathOptions;
    /** @var ResultPair[] */
    private $testUrlQuery;
    /** @var array */
    private $testUrlParts;
    /** @var ResultPair[] */
    private $testUrlPartsOptions;
    /** @var array */
    private $testPartsIndex = [0, 0, 0, 0, 0];
    /** @var ResultPair[] */
    private $testScheme;

    protected function setUp() {
        $this->setupData();

        foreach ($this->testPartsIndex as $i => $value) {
            $this->testPartsIndex[$i] = 0;
        }
    }

    public function testIsValid() {
        $this->_testIsValid($this->testUrlParts, Url::ALLOW_ALL_SCHEMES);
        $this->setUp();
        $options = Url::ALLOW_2_SLASHES + Url::ALLOW_ALL_SCHEMES + Url::NO_FRAGMENTS;
        $this->_testIsValid($this->testUrlPartsOptions, $options);
    }

    public function testIsValidScheme() {
        $urlVal = new Url(self::SCHEMES, 0);

        foreach ($this->testScheme as $testPair) {
            $result = $urlVal->isValidScheme($testPair->item);
            $this->assertEquals($testPair->isValid, $result, $testPair->item);
        }
    }

    /**
     * Create set of tests by taking the testUrlXXX arrays and
     * running through all possible permutations of their combinations.
     *
     * @param ResultPair[] testObjects Used to create a url.
     * @param int $options
     */
    private function _testIsValid(array $testObjects, int $options) {
        $urlVal = new Url([], $options);
        $this->assertTrue($urlVal->isValid("http://www.google.com"));
        $this->assertTrue($urlVal->isValid("http://www.google.com/"));

        do {
            $url = '';
            $expected = true;

            $testPartsCount = count($this->testPartsIndex);
            for ($testPartsIndexIndex = 0; $testPartsIndexIndex < $testPartsCount; ++$testPartsIndexIndex) {
                $index = $this->testPartsIndex[$testPartsIndexIndex];
                /** @var ResultPair[] $part */
                $part = $testObjects[$testPartsIndexIndex];
                $url .= $part[$index]->item;
                $expected &= $part[$index]->isValid;
            }

            $result = $urlVal->isValid($url);
            $this->assertEquals($expected, $result, $url);
        } while (self::incrementTestPartsIndex($this->testPartsIndex, $testObjects));
    }

    public function testValidator202() {
        $urlValidator = new Url(["http", "https"], Url::NO_FRAGMENTS);
        $this->assertTrue($urlValidator->isValid("http://l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.l.org"));
    }

    public function testValidator204() {
        $urlValidator = new Url(["http", "https"]);
        $this->assertTrue($urlValidator->isValid("http://tech.yahoo.com/rc/desktops/102;_ylt=Ao8yevQHlZ4On0O3ZJGXLEQFLZA5"));
    }

    public function testValidator218() {
        $validator = new Url([], Url::ALLOW_2_SLASHES);
        $this->assertTrue($validator->isValid("http://somewhere.com/pathxyz/file(1).html"), "parentheses should be valid in URLs");
    }

    public function testValidator235() {
        $validator = new Url();
        $this->assertTrue($validator->isValid("http://xn--d1abbgf6aiiy.xn--p1ai"), "xn--d1abbgf6aiiy.xn--p1ai should validate");
        $this->assertTrue($validator->isValid("http://президент.рф"), "президент.рф should validate");
        $this->assertTrue($validator->isValid("http://www.b\u{00fc}cher.ch"), "www.b\u{00fc}cher.ch should validate");
        // todo this should pass? \uFFFD is also a valid unicode symbol
        // $this->assertFalse($validator->isValid("http://www.\u{FFFD}.ch"), "www.\u{FFFD}.ch FFFD should fail");
        $this->assertTrue($validator->isValid("ftp://www.b\u{00fc}cher.ch"), "www.b\u{00fc}cher.ch should validate");
        // todo this should pass? \uFFFD is also a valid unicode symbol
        // $this->assertFalse($validator->isValid("ftp://www.\u{FFFD}.ch"), "www.\u{FFFD}.ch FFFD should fail");
    }

    public function testValidator248() {
        $validator = new Url([], 0, '/^localhost$/');
        $this->assertTrue($validator->isValid("http://localhost/test/index.html"), "localhost URL should validate");
        $this->assertFalse($validator->isValid("http://broke.my-test/test/index.html"), "broke.my-test should not validate");
        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "www.apache.org should still validate");

        $validator = new Url([], 0, '/^.*\\.my-testing$/');
        $this->assertTrue($validator->isValid("http://first.my-testing/test/index.html"), "first.my-testing should validate");
        $this->assertTrue($validator->isValid("http://sup3r.my-testing/test/index.html"), "sup3r.my-testing should validate");
        $this->assertFalse($validator->isValid("http://broke.my-test/test/index.html"), "broke.my-test should not validate");
        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "www.apache.org should still validate");

        // Now check using options
        $validator = new Url([], Url::ALLOW_LOCAL_URLS);

        $this->assertTrue($validator->isValid("http://localhost/test/index.html"), "localhost URL should validate");

        $this->assertTrue($validator->isValid("http://machinename/test/index.html"), "machinename URL should validate");

        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "www.apache.org should still validate");
    }

    public function testValidator288() {
        $validator = new Url([], Url::ALLOW_LOCAL_URLS);

        $this->assertTrue($validator->isValid("http://hostname"), "hostname should validate");

        $this->assertTrue($validator->isValid("http://hostname/test/index.html"), "hostname with path should validate");

        $this->assertTrue($validator->isValid("http://localhost/test/index.html"), "localhost URL should validate");

        $this->assertFalse($validator->isValid("http://first.my-testing/test/index.html"), "first.my-testing should not validate");

        $this->assertFalse($validator->isValid("http://broke.hostname/test/index.html"), "broke.hostname should not validate");

        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "www.apache.org should still validate");

        // Turn it off, and check
        $validator = new Url([], 0);

        $this->assertFalse($validator->isValid("http://hostname"), "hostname should no longer validate");

        $this->assertFalse($validator->isValid("http://localhost/test/index.html"), "localhost URL should no longer validate");

        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "www.apache.org should still validate");
    }

    public function testValidator276() {
        // file:// isn't allowed by default
        $validator = new Url();

        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "http://apache.org/ should be allowed by default");

        $this->assertFalse($validator->isValid("file:///C:/some.file"), "file:///c:/ shouldn't be allowed by default");

        $this->assertFalse($validator->isValid("file:///C:\\some.file"), "file:///c:\\ shouldn't be allowed by default");

        $this->assertFalse($validator->isValid("file:///etc/hosts"), "file:///etc/ shouldn't be allowed by default");

        $this->assertFalse($validator->isValid("file://localhost/etc/hosts"), "file://localhost/etc/ shouldn't be allowed by default");

        $this->assertFalse($validator->isValid("file://localhost/c:/some.file"), "file://localhost/c:/ shouldn't be allowed by default");

        // Turn it on, and check
        // Note - we need to enable local urls when working with file:
        $validator = new Url(["http", "file"], Url::ALLOW_LOCAL_URLS);

        $this->assertTrue($validator->isValid("http://www.apache.org/test/index.html"), "http://apache.org/ should be allowed by default");

        $this->assertTrue($validator->isValid("file:///C:/some.file"), "file:///c:/ should now be allowed");

        // Currently, we don't support the c:\ form
        $this->assertFalse($validator->isValid("file:///C:\\some.file"), "file:///c:\\ shouldn't be allowed");

        $this->assertTrue($validator->isValid("file:///etc/hosts"), "file:///etc/ should now be allowed");

        $this->assertTrue($validator->isValid("file://localhost/etc/hosts"), "file://localhost/etc/ should now be allowed");

        $this->assertTrue($validator->isValid("file://localhost/c:/some.file"), "file://localhost/c:/ should now be allowed");

        // These are never valid
        $this->assertFalse($validator->isValid("file://C:/some.file"), "file://c:/ shouldn't ever be allowed, needs file:///c:/");

        $this->assertFalse($validator->isValid("file://C:\\some.file"), "file://c:\\ shouldn't ever be allowed, needs file:///c:/");
    }

    public function testValidator391OK() {
        $schemes = ["file"];
        $urlValidator = new Url($schemes);
        $this->assertTrue($urlValidator->isValid("file:///C:/path/to/dir/"));
    }

    public function testValidator391FAILS() {
        $schemes = ["file"];
        $urlValidator = new Url($schemes);
        $this->assertTrue($urlValidator->isValid("file:/C:/path/to/dir/"));
    }

    public function testValidator309() {
        $urlValidator = new Url();
        $this->assertTrue($urlValidator->isValid("http://sample.ondemand.com/"));
        $this->assertTrue($urlValidator->isValid("hTtP://sample.ondemand.CoM/"));
        $this->assertTrue($urlValidator->isValid("httpS://SAMPLE.ONEMAND.COM/"));
        $urlValidator = new Url(["HTTP", "HTTPS"]);
        $this->assertTrue($urlValidator->isValid("http://sample.ondemand.com/"));
        $this->assertTrue($urlValidator->isValid("hTtP://sample.ondemand.CoM/"));
        $this->assertTrue($urlValidator->isValid("httpS://SAMPLE.ONEMAND.COM/"));
    }

    public function testValidator339() {
        $urlValidator = new Url();
        $this->assertTrue($urlValidator->isValid("http://www.cnn.com/WORLD/?hpt=sitenav")); // without
        $this->assertTrue($urlValidator->isValid("http://www.cnn.com./WORLD/?hpt=sitenav")); // with
        $this->assertFalse($urlValidator->isValid("http://www.cnn.com../")); // doubly dotty
        $this->assertFalse($urlValidator->isValid("http://www.cnn.invalid/"));
        $this->assertFalse($urlValidator->isValid("http://www.cnn.invalid./")); // check . does not affect invalid domains
    }

    public function testValidator339IDN() {
        $urlValidator = new Url();
        $this->assertTrue($urlValidator->isValid("http://президент.рф/WORLD/?hpt=sitenav")); // without
        $this->assertTrue($urlValidator->isValid("http://президент.рф./WORLD/?hpt=sitenav")); // with
        $this->assertFalse($urlValidator->isValid("http://президент.рф..../")); // very dotty
        $this->assertFalse($urlValidator->isValid("http://президент.рф.../")); // triply dotty
        $this->assertFalse($urlValidator->isValid("http://президент.рф../")); // doubly dotty
    }

    public function testValidator342() {
        $urlValidator = new Url();
        $this->assertTrue($urlValidator->isValid("http://example.rocks/"));
        $this->assertTrue($urlValidator->isValid("http://example.rocks"));
    }

    public function testValidator411() {
        $urlValidator = new Url();
        $this->assertTrue($urlValidator->isValid("http://example.rocks:/"));
        $this->assertTrue($urlValidator->isValid("http://example.rocks:0/"));
        $this->assertTrue($urlValidator->isValid("http://example.rocks:65535/"));
        $this->assertFalse($urlValidator->isValid("http://example.rocks:65536/"));
        $this->assertFalse($urlValidator->isValid("http://example.rocks:100000/"));
    }

    public function testValidator290() {
        $validator = new Url();
        $this->assertTrue($validator->isValid("http://xn--h1acbxfam.idn.icann.org/"));
        //        $this->assertTrue($validator->isValid("http://xn--e1afmkfd.xn--80akhbyknj4f"));
        // Internationalized country code top-level domains
        $this->assertTrue($validator->isValid("http://test.xn--lgbbat1ad8j")); //Algeria
        $this->assertTrue($validator->isValid("http://test.xn--fiqs8s")); // China
        $this->assertTrue($validator->isValid("http://test.xn--fiqz9s")); // China
        $this->assertTrue($validator->isValid("http://test.xn--wgbh1c")); // Egypt
        $this->assertTrue($validator->isValid("http://test.xn--j6w193g")); // Hong Kong
        $this->assertTrue($validator->isValid("http://test.xn--h2brj9c")); // India
        $this->assertTrue($validator->isValid("http://test.xn--mgbbh1a71e")); // India
        $this->assertTrue($validator->isValid("http://test.xn--fpcrj9c3d")); // India
        $this->assertTrue($validator->isValid("http://test.xn--gecrj9c")); // India
        $this->assertTrue($validator->isValid("http://test.xn--s9brj9c")); // India
        $this->assertTrue($validator->isValid("http://test.xn--xkc2dl3a5ee0h")); // India
        $this->assertTrue($validator->isValid("http://test.xn--45brj9c")); // India
        $this->assertTrue($validator->isValid("http://test.xn--mgba3a4f16a")); // Iran
        $this->assertTrue($validator->isValid("http://test.xn--mgbayh7gpa")); // Jordan
        $this->assertTrue($validator->isValid("http://test.xn--mgbc0a9azcg")); // Morocco
        $this->assertTrue($validator->isValid("http://test.xn--ygbi2ammx")); // Palestinian Territory
        $this->assertTrue($validator->isValid("http://test.xn--wgbl6a")); // Qatar
        $this->assertTrue($validator->isValid("http://test.xn--p1ai")); // Russia
        $this->assertTrue($validator->isValid("http://test.xn--mgberp4a5d4ar")); //  Saudi Arabia
        $this->assertTrue($validator->isValid("http://test.xn--90a3ac")); // Serbia
        $this->assertTrue($validator->isValid("http://test.xn--yfro4i67o")); // Singapore
        $this->assertTrue($validator->isValid("http://test.xn--clchc0ea0b2g2a9gcd")); // Singapore
        $this->assertTrue($validator->isValid("http://test.xn--3e0b707e")); // South Korea
        $this->assertTrue($validator->isValid("http://test.xn--fzc2c9e2c")); // Sri Lanka
        $this->assertTrue($validator->isValid("http://test.xn--xkc2al3hye2a")); // Sri Lanka
        $this->assertTrue($validator->isValid("http://test.xn--ogbpf8fl")); // Syria
        $this->assertTrue($validator->isValid("http://test.xn--kprw13d")); // Taiwan
        $this->assertTrue($validator->isValid("http://test.xn--kpry57d")); // Taiwan
        $this->assertTrue($validator->isValid("http://test.xn--o3cw4h")); // Thailand
        $this->assertTrue($validator->isValid("http://test.xn--pgbs0dh")); // Tunisia
        $this->assertTrue($validator->isValid("http://test.xn--mgbaam7a8h")); // United Arab Emirates
        // Proposed internationalized ccTLDs
        //        $this->assertTrue($validator->isValid("http://test.xn--54b7fta0cc")); // Bangladesh
        //        $this->assertTrue($validator->isValid("http://test.xn--90ae")); // Bulgaria
        //        $this->assertTrue($validator->isValid("http://test.xn--node")); // Georgia
        //        $this->assertTrue($validator->isValid("http://test.xn--4dbrk0ce")); // Israel
        //        $this->assertTrue($validator->isValid("http://test.xn--mgb9awbf")); // Oman
        //        $this->assertTrue($validator->isValid("http://test.xn--j1amh")); // Ukraine
        //        $this->assertTrue($validator->isValid("http://test.xn--mgb2ddes")); // Yemen
        // Test TLDs
        //        $this->assertTrue($validator->isValid("http://test.xn--kgbechtv")); // Arabic
        //        $this->assertTrue($validator->isValid("http://test.xn--hgbk6aj7f53bba")); // Persian
        //        $this->assertTrue($validator->isValid("http://test.xn--0zwm56d")); // Chinese
        //        $this->assertTrue($validator->isValid("http://test.xn--g6w251d")); // Chinese
        //        $this->assertTrue($validator->isValid("http://test.xn--80akhbyknj4f")); // Russian
        //        $this->assertTrue($validator->isValid("http://test.xn--11b5bs3a9aj6g")); // Hindi
        //        $this->assertTrue($validator->isValid("http://test.xn--jxalpdlp")); // Greek
        //        $this->assertTrue($validator->isValid("http://test.xn--9t4b11yi5a")); // Korean
        //        $this->assertTrue($validator->isValid("http://test.xn--deba0ad")); // Yiddish
        //        $this->assertTrue($validator->isValid("http://test.xn--zckzah")); // Japanese
        //        $this->assertTrue($validator->isValid("http://test.xn--hlcj6aya9esc7a")); // Tamil
    }

    public function testValidator361() {
        $validator = new Url();
        $this->assertTrue($validator->isValid("http://hello.tokyo/"));
    }

    public function testValidator363() {
        $urlValidator = new Url();
        $this->assertTrue($urlValidator->isValid("http://www.example.org/a/b/hello..world"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/a/hello..world"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/hello.world/"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/hello..world/"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/hello.world"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/hello..world"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/..world"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/.../world"));
        $this->assertFalse($urlValidator->isValid("http://www.example.org/../world"));
        $this->assertFalse($urlValidator->isValid("http://www.example.org/.."));
        $this->assertFalse($urlValidator->isValid("http://www.example.org/../"));

        // todo normalize URI
        // $this->assertFalse($urlValidator->isValid("http://www.example.org/./.."));
        // $this->assertFalse($urlValidator->isValid("http://www.example.org/././.."));

        $this->assertTrue($urlValidator->isValid("http://www.example.org/..."));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/.../"));
        $this->assertTrue($urlValidator->isValid("http://www.example.org/.../.."));
    }

    public function testValidator375() {
        $validator = new Url();
        $url = "http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/index.html";
        $this->assertTrue($validator->isValid($url), "IPv6 address URL should validate: " . $url);
        $url = "http://[::1]:80/index.html";
        $this->assertTrue($validator->isValid($url), "IPv6 address URL should validate: " . $url);
        $url = "http://FEDC:BA98:7654:3210:FEDC:BA98:7654:3210:80/index.html";
        $this->assertFalse($validator->isValid($url), "IPv6 address without [] should not validate: " . $url);
    }

    public function testValidator353() { // userinfo
        $validator = new Url();
        $this->assertTrue($validator->isValid("http://www.apache.org:80/path"));
        $this->assertTrue($validator->isValid("http://user:pass@www.apache.org:80/path"));
        $this->assertTrue($validator->isValid("http://user:@www.apache.org:80/path"));
        $this->assertTrue($validator->isValid("http://user@www.apache.org:80/path"));
        $this->assertTrue($validator->isValid("http://us%00er:-._~!$&'()*+,;=@www.apache.org:80/path"));
        $this->assertFalse($validator->isValid("http://:pass@www.apache.org:80/path"));
        $this->assertFalse($validator->isValid("http://:@www.apache.org:80/path"));
        $this->assertFalse($validator->isValid("http://user:pa:ss@www.apache.org/path"));
        $this->assertFalse($validator->isValid("http://user:pa@ss@www.apache.org/path"));
    }

    public function testValidator382() {
        $validator = new Url();
        $this->assertTrue($validator->isValid("ftp://username:password@example.com:8042/over/there/index.dtb?type=animal&name=narwhal#nose"));
    }

    public function testValidator380() {
        $validator = new Url();
        $this->assertTrue($validator->isValid("http://www.apache.org:80/path"));
        $this->assertTrue($validator->isValid("http://www.apache.org:8/path"));
        $this->assertTrue($validator->isValid("http://www.apache.org:/path"));
    }

    public function testValidator420() {
        $validator = new Url();
        $this->assertFalse($validator->isValid("http://example.com/serach?address=Main Avenue"));
        $this->assertTrue($validator->isValid("http://example.com/serach?address=Main%20Avenue"));
        $this->assertTrue($validator->isValid("http://example.com/serach?address=Main+Avenue"));
    }

    private static function incrementTestPartsIndex(array &$testPartsIndex, array $testParts) : bool {
        $carry = true;  //add 1 to lowest order part.
        $maxIndex = true;

        for ($testPartsIndexIndex = count($testPartsIndex) - 1; $testPartsIndexIndex >= 0; --$testPartsIndexIndex) {
            $index = $testPartsIndex[$testPartsIndexIndex];
            $part = $testParts[$testPartsIndexIndex];
            $maxIndex &= ($index === (count($part) - 1));

            if ($carry) {
                if ($index < count($part) - 1) {
                    $index++;
                    $testPartsIndex[$testPartsIndexIndex] = $index;
                    $carry = false;
                } else {
                    $testPartsIndex[$testPartsIndexIndex] = 0;
                    $carry = true;
                }
            }
        }

        return !$maxIndex;
    }

    private function setupData() {
        /**
         * The data given below approximates the 4 parts of a URL
         * <scheme>://<authority><path>?<query> except that the port number
         * is broken out of authority to increase the number of permutations.
         * A complete URL is composed of a scheme+authority+port+path+query,
         * all of which must be individually valid for the entire URL to be considered
         * valid.
         */
        $this->testUrlScheme = [
            new ResultPair("http://", true),
            new ResultPair("ftp://", true),
            new ResultPair("h3t://", true),
            new ResultPair("3ht://", false),
            new ResultPair("http:/", false),
            new ResultPair("http:", false),
            new ResultPair("http/", false),
            new ResultPair("://", false)
        ];

        $this->testUrlAuthority = [
            new ResultPair("www.google.com", true),
            new ResultPair("www.google.com.", true),
            new ResultPair("go.com", true),
            new ResultPair("go.au", true),
            new ResultPair("0.0.0.0", true),
            new ResultPair("255.255.255.255", true),
            new ResultPair("256.256.256.256", false),
            new ResultPair("255.com", true),
            new ResultPair("1.2.3.4.5", false),
            new ResultPair("1.2.3.4.", false),
            new ResultPair("1.2.3", false),
            new ResultPair(".1.2.3.4", false),
            new ResultPair("go.a", false),
            new ResultPair("go.a1a", false),
            new ResultPair("go.cc", true),
            new ResultPair("go.1aa", false),
            new ResultPair("aaa.", false),
            new ResultPair(".aaa", false),
            new ResultPair("aaa", false),
            new ResultPair("", false)
        ];

        $this->testUrlPort = [
            new ResultPair(":80", true),
            new ResultPair(":65535", true), // max possible
            new ResultPair(":65536", false), // max possible +1
            new ResultPair(":0", true),
            new ResultPair("", true),
            new ResultPair(":-1", false),
            new ResultPair(":65636", false),
            new ResultPair(":999999999999999999", false),
            new ResultPair(":65a", false)
        ];

        $this->testPath = [
            new ResultPair("/test1", true),
            new ResultPair("/t123", true),
            new ResultPair("/$23", true),
            new ResultPair("/..", false),
            new ResultPair("/../", false),
            new ResultPair("/test1/", true),
            new ResultPair("", true),
            new ResultPair("/test1/file", true),
            new ResultPair("/..//file", false),
            new ResultPair("/test1//file", false)
        ];

        $this->testUrlPathOptions = [
            new ResultPair("/test1", true),
            new ResultPair("/t123", true),
            new ResultPair("/$23", true),
            new ResultPair("/..", false),
            new ResultPair("/../", false),
            new ResultPair("/test1/", true),
            new ResultPair("/#", false),
            new ResultPair("", true),
            new ResultPair("/test1/file", true),
            new ResultPair("/t123/file", true),
            new ResultPair("/$23/file", true),
            new ResultPair("/../file", false),
            new ResultPair("/..//file", false),
            new ResultPair("/test1//file", true),
            new ResultPair("/#/file", false)
        ];

        $this->testUrlQuery = [
            new ResultPair("?action=view", true),
            new ResultPair("?action=edit&mode=up", true),
            new ResultPair("", true)
        ];

        $this->testScheme = [
            new ResultPair("http", true),
            new ResultPair("ftp", false),
            new ResultPair("httpd", false),
            new ResultPair("gopher", true),
            new ResultPair("g0-to+.", true),
            new ResultPair("not_valid", false), // underscore not allowed
            new ResultPair("HtTp", true),
            new ResultPair("telnet", false)
        ];

        $this->testUrlParts = [$this->testUrlScheme, $this->testUrlAuthority, $this->testUrlPort, $this->testPath, $this->testUrlQuery];
        $this->testUrlPartsOptions = [$this->testUrlScheme, $this->testUrlAuthority, $this->testUrlPort, $this->testUrlPathOptions, $this->testUrlQuery];
    }

}
