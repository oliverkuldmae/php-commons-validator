<?php

namespace PHPCommons\Validator\Tests\Rule;

use PHPCommons\Validator\Rule\Domain;
use PHPCommons\Validator\Util\TLD;
use PHPUnit\Framework\TestCase;
use function count;
use function strlen;

class DomainTest extends TestCase {

    /**
     * @var Domain
     */
    protected $validator;

    protected function setUp() {
        $this->validator = Domain::getInstance();
    }

    public function testValidDomains() {
        $this->assertTrue($this->validator->isValid("apache.org"), "apache.org should validate");
        $this->assertTrue($this->validator->isValid("www.google.com"), "www.google.com should validate");

        $this->assertTrue($this->validator->isValid("test-domain.com"), "test-domain.com should validate");
        $this->assertTrue($this->validator->isValid("test---domain.com"), "test---domain.com should validate");
        $this->assertTrue($this->validator->isValid("test-d-o-m-ain.com"), "test-d-o-m-ain.com should validate");
        $this->assertTrue($this->validator->isValid("as.uk"), "two-letter domain label should validate");

        $this->assertTrue($this->validator->isValid("ApAchE.Org"), "case-insensitive ApAchE.Org should validate");

        $this->assertTrue($this->validator->isValid("z.com"), "single-character domain label should validate");

        $this->assertTrue($this->validator->isValid("i.have.an-example.domain.name"), "i.have.an-example.domain.name should validate");
    }


    public function testInvalidDomains() {
        $this->assertFalse($this->validator->isValid(".org"), "bare TLD .org shouldn't validate");
        $this->assertFalse($this->validator->isValid(" apache.org "), "domain name with spaces shouldn't validate");
        $this->assertFalse($this->validator->isValid("apa che.org"), "domain name containing spaces shouldn't validate");
        $this->assertFalse($this->validator->isValid("-testdomain.name"), "domain name starting with dash shouldn't validate");
        $this->assertFalse($this->validator->isValid("testdomain-.name"), "domain name ending with dash shouldn't validate");
        $this->assertFalse($this->validator->isValid("---c.com"), "domain name starting with multiple dashes shouldn't validate");
        $this->assertFalse($this->validator->isValid("c--.com"), "domain name ending with multiple dashes shouldn't validate");
        $this->assertFalse($this->validator->isValid("apache.rog"), "domain name with invalid TLD shouldn't validate");

        $this->assertFalse($this->validator->isValid("http://www.apache.org"), "URL shouldn't validate");
        $this->assertFalse($this->validator->isValid(" "), "Empty string shouldn't validate as domain name");
        $this->assertFalse($this->validator->isValid(null), "Null shouldn't validate as domain name");
    }

    public function testTopLevelDomains() {
        // infrastructure TLDs
        $this->assertTrue($this->validator->isValidInfrastructureTld(".arpa"), ".arpa should validate as iTLD");
        $this->assertFalse($this->validator->isValidInfrastructureTld(".com"), ".com shouldn't validate as iTLD");

        // generic TLDs
        $this->assertTrue($this->validator->isValidGenericTld(".name"), ".name should validate as gTLD");
        $this->assertFalse($this->validator->isValidGenericTld(".us"), ".us shouldn't validate as gTLD");

        // country code TLDs
        $this->assertTrue($this->validator->isValidCountryCodeTld(".uk"), ".uk should validate as ccTLD");
        $this->assertFalse($this->validator->isValidCountryCodeTld(".org"), ".org shouldn't validate as ccTLD");

        // case-insensitive
        $this->assertTrue($this->validator->isValidTld(".COM"), ".COM should validate as TLD");
        $this->assertTrue($this->validator->isValidTld(".BiZ"), ".BiZ should validate as TLD");

        // corner cases
        $this->assertFalse($this->validator->isValid(".nope"), "invalid TLD shouldn't validate"); // TODO this is not guaranteed invalid forever
        $this->assertFalse($this->validator->isValid(""), "empty string shouldn't validate as TLD");
        $this->assertFalse($this->validator->isValid(null), "null shouldn't validate as TLD");
    }

    public function testAllowLocal() {
        $noLocal = Domain::getInstance(false);
        $allowLocal = Domain::getInstance(true);

        // Default is false, and should use singletons
        $this->assertEquals($noLocal, $this->validator);

        // Default won't allow local
        $this->assertFalse($noLocal->isValid("localhost.localdomain"), "localhost.localdomain should validate");
        $this->assertFalse($noLocal->isValid("localhost"), "localhost should validate");

        // But it may be requested
        $this->assertTrue($allowLocal->isValid("localhost.localdomain"), "localhost.localdomain should validate");
        $this->assertTrue($allowLocal->isValid("localhost"), "localhost should validate");
        $this->assertTrue($allowLocal->isValid("hostname"), "hostname should validate");
        $this->assertTrue($allowLocal->isValid("machinename"), "machinename should validate");

        // Check the localhost one with a few others
        $this->assertTrue($allowLocal->isValid("apache.org"), "apache.org should validate");
        $this->assertFalse($allowLocal->isValid(" apache.org "), "domain name with spaces shouldn't validate");
    }

    public function testIDN() {
        $this->assertTrue($this->validator->isValid("www.xn--bcher-kva.ch"), "b\u{00fc}cher.ch in IDN should validate");
    }

    public function testIDNJava6OrLater() {
        // xn--d1abbgf6aiiy.xn--p1ai http://президент.рф
        $this->assertTrue($this->validator->isValid("www.b\u{00fc}cher.ch"), "b\u{00fc}cher.ch should validate");
        $this->assertTrue($this->validator->isValid("xn--d1abbgf6aiiy.xn--p1ai"), "xn--d1abbgf6aiiy.xn--p1ai should validate");
        $this->assertTrue($this->validator->isValid("президент.рф"), "президент.рф should validate");

        // todo this should pass? \uFFFD is also a valid unicode symbol
        // $this->assertFalse($this->validator->isValid("www.\u{FFFD}.ch"), "www.\u{FFFD}.ch FFFD should fail");
    }

    // RFC2396: domainlabel   = alphanum | alphanum *( alphanum | "-" ) alphanum
    public function testRFC2396domainlabel() { // use fixed valid TLD
        $this->assertTrue($this->validator->isValid("a.ch"), "a.ch should validate");
        $this->assertTrue($this->validator->isValid("9.ch"), "9.ch should validate");
        $this->assertTrue($this->validator->isValid("az.ch"), "az.ch should validate");
        $this->assertTrue($this->validator->isValid("09.ch"), "09.ch should validate");
        $this->assertTrue($this->validator->isValid("9-1.ch"), "9-1.ch should validate");
        $this->assertFalse($this->validator->isValid("91-.ch"), "91-.ch should not validate");
        $this->assertFalse($this->validator->isValid("-.ch"), "-.ch should not validate");
    }

    // RFC2396 toplabel = alpha | alpha *( alphanum | "-" ) alphanum
    public function testRFC2396toplabel() {
        // These tests use non-existent TLDs so currently need to use a package protected method
        $this->assertTrue($this->validator->isValidDomainSyntax("a.c"), "a.c (alpha) should validate");
        $this->assertTrue($this->validator->isValidDomainSyntax("a.cc"), "a.cc (alpha alpha) should validate");
        $this->assertTrue($this->validator->isValidDomainSyntax("a.c9"), "a.c9 (alpha alphanum) should validate");
        $this->assertTrue($this->validator->isValidDomainSyntax("a.c-9"), "a.c-9 (alpha - alphanum) should validate");
        $this->assertTrue($this->validator->isValidDomainSyntax("a.c-z"), "a.c-z (alpha - alpha) should validate");

        $this->assertFalse($this->validator->isValidDomainSyntax("a.9c"), "a.9c (alphanum alpha) should fail");
        $this->assertFalse($this->validator->isValidDomainSyntax("a.c-"), "a.c- (alpha -) should fail");
        $this->assertFalse($this->validator->isValidDomainSyntax("a.-"), "a.- (-) should fail");
        $this->assertFalse($this->validator->isValidDomainSyntax("a.-9"), "a.-9 (- alphanum) should fail");
    }

    public function testDomainNoDots() {// rfc1123
        $this->assertTrue($this->validator->isValidDomainSyntax("a"), "a (alpha) should validate");
        $this->assertTrue($this->validator->isValidDomainSyntax("9"), "9 (alphanum) should validate");
        $this->assertTrue($this->validator->isValidDomainSyntax("c-z"), "c-z (alpha - alpha) should validate");

        $this->assertFalse($this->validator->isValidDomainSyntax("c-"), "c- (alpha -) should fail");
        $this->assertFalse($this->validator->isValidDomainSyntax("-c"), "-c (- alpha) should fail");
        $this->assertFalse($this->validator->isValidDomainSyntax("-"), "- (-) should fail");
    }

    public function testValidator297() {
        $this->assertTrue(
            $this->validator->isValid("xn--d1abbgf6aiiy.xn--p1ai"),
            "xn--d1abbgf6aiiy.xn--p1ai should validate"
        ); // This uses a valid TLD
    }

    // labels are a max of 63 chars and domains 253
    public function testValidator306() {
        $longString = "abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789A";
        $this->assertEquals(63, strlen($longString)); // 26 * 2 + 11

        $this->assertTrue($this->validator->isValidDomainSyntax($longString . ".com"), "63 chars label should validate");
        $this->assertFalse($this->validator->isValidDomainSyntax($longString . "x.com"), "64 chars label should fail");

        $this->assertTrue($this->validator->isValidDomainSyntax("test." . $longString), "63 chars TLD should validate");
        $this->assertFalse($this->validator->isValidDomainSyntax("test.x" . $longString), "64 chars TLD should fail");

        $longDomain = $longString
            . '.' . $longString
            . '.' . $longString
            . '.' . substr($longString, 0, 61);

        $this->assertEquals(253, strlen($longDomain));
        $this->assertTrue($this->validator->isValidDomainSyntax($longDomain), "253 chars domain should validate");
        $this->assertFalse($this->validator->isValidDomainSyntax($longDomain . "x"), "254 chars domain should fail");
    }

    // Check that IDN.toASCII behaves as it should (when wrapped by DomainValidator.unicodeToASCII)
    // Tests show that method incorrectly trims a trailing "." character
    public function testUnicodeToASCII() {
        $asciidots = [
            "",
            ",",
            ".", // fails IDN.toASCII, but should pass wrapped version
            "a.", // ditto
            "a.b",
            "a..b",
            "a...b",
            ".a",
            "..a",
        ];

        foreach ($asciidots as $s) {
            $this->assertEquals($s, Domain::unicodeToASCII($s));
        }

        // RFC3490 3.1. 1)
        //      Whenever dots are used as label separators, the following
        //      characters MUST be recognized as dots: U+002E (full stop), U+3002
        //      (ideographic full stop), U+FF0E (fullwidth full stop), U+FF61
        //      (halfwidth ideographic full stop).

        $otherDots = [
            ["b\u{3002}", "b.",],
            ["b\u{FF0E}", "b.",],
            ["b\u{FF61}", "b.",],
            ["\u{3002}", ".",],
            ["\u{FF0E}", ".",],
            ["\u{FF61}", ".",],
        ];

        foreach ($otherDots as $s) {
            $this->assertEquals($s[1], Domain::unicodeToASCII($s[0]));
        }
    }

    // Check array is sorted and is lower-case
    public function test_INFRASTRUCTURE_TLDS_sortedAndLowerCase() {
        $this->assertTrue(self::isSortedLowerCase(TLD::INFRASTRUCTURE_TLDS));
    }

    // Check array is sorted and is lower-case
    public function test_COUNTRY_CODE_TLDS_sortedAndLowerCase() {
        $this->assertTrue(self::isSortedLowerCase(TLD::COUNTRY_CODE_TLDS));
    }

    // Check array is sorted and is lower-case
    public function test_GENERIC_TLDS_sortedAndLowerCase() {
        $this->assertTrue(self::isSortedLowerCase(TLD::GENERIC_TLDS));
    }

    // Check array is sorted and is lower-case
    public function test_LOCAL_TLDS_sortedAndLowerCase() {
        $this->assertTrue(self::isSortedLowerCase(TLD::LOCAL_TLDS));
    }

    private static function isLowerCase(string $string) : bool {
        return $string === mb_strtolower($string);
    }

    /**
     * Checks if an array is strictly sorted - and lowerCase
     *
     * @param array $values
     *
     * @return bool
     */
    private static function isSortedLowerCase(array $values) : bool {
        $sorted = true;
        $strictlySorted = true;
        $length = count($values);

        $lowerCase = self::isLowerCase($values[$length - 1]); // Check the last entry

        for ($i = 0; $i < $length - 1; $i++) { // compare all but last entry with next
            $entry = $values[$i];
            $nextEntry = $values[$i + 1];
            $cmp = strcmp($entry, $nextEntry);

            if ($cmp > 0) { // out of order
                fwrite(STDOUT, __METHOD__ . "() - Out of order entry: $entry < $nextEntry");
                $sorted = false;
            } else if ($cmp === 0) {
                fwrite(STDOUT, __METHOD__ . "() - Duplicate entry: $entry");
                $strictlySorted = false;
            }
            if (!self::isLowerCase($entry)) {
                fwrite(STDOUT, __METHOD__ . "() - Non lowerCase entry: $entry");
                $lowerCase = false;
            }
        }

        return $sorted && $strictlySorted && $lowerCase;
    }

}
