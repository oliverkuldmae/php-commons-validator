<?php

namespace PHPCommons\Validator\Tests\Rule;

use PHPCommons\Validator\Rule\InetAddress;
use PHPUnit\Framework\TestCase;

class InetAddressTest extends TestCase {

    /**
     * @var InetAddress
     */
    protected $validator;

    protected function setUp() {
        $this->validator = new InetAddress();
    }

    /**
     * Test IPs that point to real, well-known hosts (without actually looking them up).
     */
    public function testInetAddressesFromTheWild() {
        $this->assertTrue($this->validator->isValid("140.211.11.130"), "www.apache.org IP should be valid");
        $this->assertTrue($this->validator->isValid("72.14.253.103"), "www.l.google.com IP should be valid");
        $this->assertTrue($this->validator->isValid("199.232.41.5"), "fsf.org IP should be valid");
        $this->assertTrue($this->validator->isValid("216.35.123.87"), "appscs.ign.com IP should be valid");
    }

    public function testVALIDATOR_335() {
        $this->assertTrue($this->validator->isValid("2001:0438:FFFE:0000:0000:0000:0000:0A35"), "2001:0438:FFFE:0000:0000:0000:0000:0A35 should be valid");
    }

    public function testVALIDATOR_419() {
        $addr = "0:0:0:0:0:0:13.1.68.3";
        $this->assertTrue($this->validator->isValid($addr), $addr);
        $addr = "0:0:0:0:0:FFFF:129.144.52.38";
        $this->assertTrue($this->validator->isValid($addr), $addr);
        $addr = "::13.1.68.3";
        $this->assertTrue($this->validator->isValid($addr), $addr);
        $addr = "::FFFF:129.144.52.38";
        $this->assertTrue($this->validator->isValid($addr), $addr);

        $addr = "::ffff:192.168.1.1:192.168.1.1";
        $this->assertFalse($this->validator->isValid($addr), $addr);
        $addr = "::192.168.1.1:192.168.1.1";
        $this->assertFalse($this->validator->isValid($addr), $addr);
    }

    /**
     * Test valid and invalid IPs from each address class.
     */
    public function testInetAddressesByClass() {
        $this->assertTrue($this->validator->isValid("24.25.231.12"), "class A IP should be valid");
        $this->assertFalse($this->validator->isValid("2.41.32.324"), "illegal class A IP should be invalid");

        $this->assertTrue($this->validator->isValid("135.14.44.12"), "class B IP should be valid");
        $this->assertFalse($this->validator->isValid("154.123.441.123"), "illegal class B IP should be invalid");

        $this->assertTrue($this->validator->isValid("213.25.224.32"), "class C IP should be valid");
        $this->assertFalse($this->validator->isValid("201.543.23.11"), "illegal class C IP should be invalid");

        $this->assertTrue($this->validator->isValid("229.35.159.6"), "class D IP should be valid");
        $this->assertFalse($this->validator->isValid("231.54.11.987"), "illegal class D IP should be invalid");

        $this->assertTrue($this->validator->isValid("248.85.24.92"), "class E IP should be valid");
        $this->assertFalse($this->validator->isValid("250.21.323.48"), "illegal class E IP should be invalid");
    }

    /**
     * Test reserved IPs.
     */
    public function testReservedInetAddresses() {
        $this->assertTrue($this->validator->isValid("127.0.0.1"), "localhost IP should be valid");
        $this->assertTrue($this->validator->isValid("255.255.255.255"), "broadcast IP should be valid");
    }

    /**
     * Test obviously broken IPs.
     */
    public function testBrokenInetAddresses() {
        $this->assertFalse($this->validator->isValid("124.14.32.abc"), "IP with characters should be invalid");
        //        $this->assertFalse($this->validator->isValid("124.14.32.01"), "IP with leading zeroes should be invalid");
        $this->assertFalse($this->validator->isValid("23.64.12"), "IP with three groups should be invalid");
        $this->assertFalse($this->validator->isValid("26.34.23.77.234"), "IP with five groups should be invalid");
    }

    /**
     * Test IPv6 addresses.
     * <p>These tests were ported from a
     * <a href="http://download.dartware.com/thirdparty/test-ipv6-regex.pl">Perl script</a>.</p>
     *
     */
    public function testIPv6() {
        // The original Perl script contained a lot of duplicate tests.
        // I removed the duplicates I noticed, but there may be more.
        //        $this->assertFalse($this->validator->isValidInet6Address(""), "IPV6 empty string should be invalid")// empty string;
        $this->assertTrue($this->validator->isValidInet6Address("::1"), "IPV6 ::1 should be valid");// loopback, compressed, non-routable;
        $this->assertTrue($this->validator->isValidInet6Address("::"), "IPV6 :: should be valid");// unspecified, compressed, non-routable;
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0:0:0:1"), "IPV6 0:0:0:0:0:0:0:1 should be valid");// loopback, full;
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0:0:0:0"), "IPV6 0:0:0:0:0:0:0:0 should be valid");// unspecified, full;
        $this->assertTrue($this->validator->isValidInet6Address("2001:DB8:0:0:8:800:200C:417A"), "IPV6 2001:DB8:0:0:8:800:200C:417A should be valid");// unicast, full;
        $this->assertTrue($this->validator->isValidInet6Address("FF01:0:0:0:0:0:0:101"), "IPV6 FF01:0:0:0:0:0:0:101 should be valid");// multicast, full;
        $this->assertTrue($this->validator->isValidInet6Address("2001:DB8::8:800:200C:417A"), "IPV6 2001:DB8::8:800:200C:417A should be valid");// unicast, compressed;
        $this->assertTrue($this->validator->isValidInet6Address("FF01::101"), "IPV6 FF01::101 should be valid");// multicast, compressed;
        $this->assertFalse($this->validator->isValidInet6Address("2001:DB8:0:0:8:800:200C:417A:221"), "IPV6 2001:DB8:0:0:8:800:200C:417A:221 should be invalid");// unicast, full;
        $this->assertFalse($this->validator->isValidInet6Address("FF01::101::2"), "IPV6 FF01::101::2 should be invalid");// multicast, compressed;
        $this->assertTrue($this->validator->isValidInet6Address("fe80::217:f2ff:fe07:ed62"), "IPV6 fe80::217:f2ff:fe07:ed62 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0000:1234:0000:0000:C1C0:ABCD:0876"), "IPV6 2001:0000:1234:0000:0000:C1C0:ABCD:0876 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("3ffe:0b00:0000:0000:0001:0000:0000:000a"), "IPV6 3ffe:0b00:0000:0000:0001:0000:0000:000a should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("FF02:0000:0000:0000:0000:0000:0000:0001"), "IPV6 FF02:0000:0000:0000:0000:0000:0000:0001 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0000:0000:0000:0000:0000:0000:0000:0001"), "IPV6 0000:0000:0000:0000:0000:0000:0000:0001 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0000:0000:0000:0000:0000:0000:0000:0000"), "IPV6 0000:0000:0000:0000:0000:0000:0000:0000 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("02001:0000:1234:0000:0000:C1C0:ABCD:0876"), "IPV6 02001:0000:1234:0000:0000:C1C0:ABCD:0876 should be invalid"); // extra 0 not allowed!;
        $this->assertFalse($this->validator->isValidInet6Address("2001:0000:1234:0000:00001:C1C0:ABCD:0876"), "IPV6 2001:0000:1234:0000:00001:C1C0:ABCD:0876 should be invalid"); // extra 0 not allowed!;
        $this->assertFalse($this->validator->isValidInet6Address("2001:0000:1234:0000:0000:C1C0:ABCD:0876 0"), "IPV6 2001:0000:1234:0000:0000:C1C0:ABCD:0876 0 should be invalid"); // junk after valid address;
        $this->assertFalse($this->validator->isValidInet6Address("2001:0000:1234: 0000:0000:C1C0:ABCD:0876"), "IPV6 2001:0000:1234: 0000:0000:C1C0:ABCD:0876 should be invalid"); // internal space;
        $this->assertFalse($this->validator->isValidInet6Address("3ffe:0b00:0000:0001:0000:0000:000a"), "IPV6 3ffe:0b00:0000:0001:0000:0000:000a should be invalid"); // seven segments;
        $this->assertFalse($this->validator->isValidInet6Address("FF02:0000:0000:0000:0000:0000:0000:0000:0001"), "IPV6 FF02:0000:0000:0000:0000:0000:0000:0000:0001 should be invalid"); // nine segments;
        $this->assertFalse($this->validator->isValidInet6Address("3ffe:b00::1::a"), "IPV6 3ffe:b00::1::a should be invalid"); // double "::";
        $this->assertFalse($this->validator->isValidInet6Address("::1111:2222:3333:4444:5555:6666::"), "IPV6 ::1111:2222:3333:4444:5555:6666:: should be invalid"); // double "::"
        $this->assertTrue($this->validator->isValidInet6Address("2::10"), "IPV6 2::10 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("ff02::1"), "IPV6 ff02::1 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("fe80::"), "IPV6 fe80:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2002::"), "IPV6 2002:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:db8::"), "IPV6 2001:db8:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:1234::"), "IPV6 2001:0db8:1234:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:0:0"), "IPV6 ::ffff:0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5:6:7:8"), "IPV6 1:2:3:4:5:6:7:8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5:6::8"), "IPV6 1:2:3:4:5:6::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5::8"), "IPV6 1:2:3:4:5::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4::8"), "IPV6 1:2:3:4::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3::8"), "IPV6 1:2:3::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2::8"), "IPV6 1:2::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::8"), "IPV6 1::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::2:3:4:5:6:7"), "IPV6 1::2:3:4:5:6:7 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::2:3:4:5:6"), "IPV6 1::2:3:4:5:6 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::2:3:4:5"), "IPV6 1::2:3:4:5 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::2:3:4"), "IPV6 1::2:3:4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::2:3"), "IPV6 1::2:3 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2:3:4:5:6:7:8"), "IPV6 ::2:3:4:5:6:7:8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2:3:4:5:6:7"), "IPV6 ::2:3:4:5:6:7 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2:3:4:5:6"), "IPV6 ::2:3:4:5:6 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2:3:4:5"), "IPV6 ::2:3:4:5 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2:3:4"), "IPV6 ::2:3:4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2:3"), "IPV6 ::2:3 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::8"), "IPV6 ::8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5:6::"), "IPV6 1:2:3:4:5:6:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5::"), "IPV6 1:2:3:4:5:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4::"), "IPV6 1:2:3:4:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3::"), "IPV6 1:2:3:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2::"), "IPV6 1:2:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::"), "IPV6 1:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5::7:8"), "IPV6 1:2:3:4:5::7:8 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("1:2:3::4:5::7:8"), "IPV6 1:2:3::4:5::7:8 should be invalid"); // Double "::"
        $this->assertFalse($this->validator->isValidInet6Address("12345::6:7:8"), "IPV6 12345::6:7:8 should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4::7:8"), "IPV6 1:2:3:4::7:8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3::7:8"), "IPV6 1:2:3::7:8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2::7:8"), "IPV6 1:2::7:8 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::7:8"), "IPV6 1::7:8 should be valid");
        // IPv4 addresses as dotted-quads
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5:6:1.2.3.4"), "IPV6 1:2:3:4:5:6:1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4:5::1.2.3.4"), "IPV6 1:2:3:4:5::1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4::1.2.3.4"), "IPV6 1:2:3:4::1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3::1.2.3.4"), "IPV6 1:2:3::1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2::1.2.3.4"), "IPV6 1:2::1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::1.2.3.4"), "IPV6 1::1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3:4::5:1.2.3.4"), "IPV6 1:2:3:4::5:1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2:3::5:1.2.3.4"), "IPV6 1:2:3::5:1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1:2::5:1.2.3.4"), "IPV6 1:2::5:1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::5:1.2.3.4"), "IPV6 1::5:1.2.3.4 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1::5:11.22.33.44"), "IPV6 1::5:11.22.33.44 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:400.2.3.4"), "IPV6 1::5:400.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:260.2.3.4"), "IPV6 1::5:260.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:256.2.3.4"), "IPV6 1::5:256.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.256.3.4"), "IPV6 1::5:1.256.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.2.256.4"), "IPV6 1::5:1.2.256.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.2.3.256"), "IPV6 1::5:1.2.3.256 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:300.2.3.4"), "IPV6 1::5:300.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.300.3.4"), "IPV6 1::5:1.300.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.2.300.4"), "IPV6 1::5:1.2.300.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.2.3.300"), "IPV6 1::5:1.2.3.300 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:900.2.3.4"), "IPV6 1::5:900.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.900.3.4"), "IPV6 1::5:1.900.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.2.900.4"), "IPV6 1::5:1.2.900.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:1.2.3.900"), "IPV6 1::5:1.2.3.900 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:300.300.300.300"), "IPV6 1::5:300.300.300.300 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::5:3000.30.30.30"), "IPV6 1::5:3000.30.30.30 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::400.2.3.4"), "IPV6 1::400.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::260.2.3.4"), "IPV6 1::260.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::256.2.3.4"), "IPV6 1::256.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.256.3.4"), "IPV6 1::1.256.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.2.256.4"), "IPV6 1::1.2.256.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.2.3.256"), "IPV6 1::1.2.3.256 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::300.2.3.4"), "IPV6 1::300.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.300.3.4"), "IPV6 1::1.300.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.2.300.4"), "IPV6 1::1.2.300.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.2.3.300"), "IPV6 1::1.2.3.300 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::900.2.3.4"), "IPV6 1::900.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.900.3.4"), "IPV6 1::1.900.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.2.900.4"), "IPV6 1::1.2.900.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::1.2.3.900"), "IPV6 1::1.2.3.900 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::300.300.300.300"), "IPV6 1::300.300.300.300 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::3000.30.30.30"), "IPV6 1::3000.30.30.30 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::400.2.3.4"), "IPV6 ::400.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::260.2.3.4"), "IPV6 ::260.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::256.2.3.4"), "IPV6 ::256.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.256.3.4"), "IPV6 ::1.256.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.256.4"), "IPV6 ::1.2.256.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.3.256"), "IPV6 ::1.2.3.256 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::300.2.3.4"), "IPV6 ::300.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.300.3.4"), "IPV6 ::1.300.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.300.4"), "IPV6 ::1.2.300.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.3.300"), "IPV6 ::1.2.3.300 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::900.2.3.4"), "IPV6 ::900.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.900.3.4"), "IPV6 ::1.900.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.900.4"), "IPV6 ::1.2.900.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.3.900"), "IPV6 ::1.2.3.900 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::300.300.300.300"), "IPV6 ::300.300.300.300 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::3000.30.30.30"), "IPV6 ::3000.30.30.30 should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("fe80::217:f2ff:254.7.237.98"), "IPV6 fe80::217:f2ff:254.7.237.98 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:192.168.1.26"), "IPV6 ::ffff:192.168.1.26 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("2001:1:1:1:1:1:255Z255X255Y255"), "IPV6 2001:1:1:1:1:1:255Z255X255Y255 should be invalid"); // garbage instead of "." in IPv4
        $this->assertFalse($this->validator->isValidInet6Address("::ffff:192x168.1.26"), "IPV6 ::ffff:192x168.1.26 should be invalid"); // ditto
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:192.168.1.1"), "IPV6 ::ffff:192.168.1.1 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0:0:13.1.68.3"), "IPV6 0:0:0:0:0:0:13.1.68.3 should be valid");// IPv4-compatible IPv6 address, full, deprecated
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0:FFFF:129.144.52.38"), "IPV6 0:0:0:0:0:FFFF:129.144.52.38 should be valid");// IPv4-mapped IPv6 address, full
        $this->assertTrue($this->validator->isValidInet6Address("::13.1.68.3"), "IPV6 ::13.1.68.3 should be valid");// IPv4-compatible IPv6 address, compressed, deprecated
        $this->assertTrue($this->validator->isValidInet6Address("::FFFF:129.144.52.38"), "IPV6 ::FFFF:129.144.52.38 should be valid");// IPv4-mapped IPv6 address, compressed
        $this->assertTrue($this->validator->isValidInet6Address("fe80:0:0:0:204:61ff:254.157.241.86"), "IPV6 fe80:0:0:0:204:61ff:254.157.241.86 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("fe80::204:61ff:254.157.241.86"), "IPV6 fe80::204:61ff:254.157.241.86 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:12.34.56.78"), "IPV6 ::ffff:12.34.56.78 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("::ffff:2.3.4"), "IPV6 ::ffff:2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::ffff:257.1.2.3"), "IPV6 ::ffff:257.1.2.3 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4"), "IPV6 1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4:1111:2222:3333:4444::5555"), "IPV6 1.2.3.4:1111:2222:3333:4444::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4:1111:2222:3333::5555"), "IPV6 1.2.3.4:1111:2222:3333::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4:1111:2222::5555"), "IPV6 1.2.3.4:1111:2222::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4:1111::5555"), "IPV6 1.2.3.4:1111::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4::5555"), "IPV6 1.2.3.4::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4::"), "IPV6 1.2.3.4:: should be invalid");
        // Testing IPv4 addresses represented as dotted-quads
        // Leading zeroes in IPv4 addresses not allowed: some systems treat the leading "0" in ".086" as the start of an octal number
        // Update: The BNF in RFC-3986 explicitly defines the dec-octet (for IPv4 addresses) not to have a leading zero
        $this->assertFalse($this->validator->isValidInet6Address("fe80:0000:0000:0000:0204:61ff:254.157.241.086"), "IPV6 fe80:0000:0000:0000:0204:61ff:254.157.241.086 should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:192.0.2.128"), "IPV6 ::ffff:192.0.2.128 should be valid"); // but this is OK, since there's a single digit
        $this->assertFalse($this->validator->isValidInet6Address("XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:1.2.3.4"), "IPV6 XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:00.00.00.00"), "IPV6 1111:2222:3333:4444:5555:6666:00.00.00.00 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:000.000.000.000"), "IPV6 1111:2222:3333:4444:5555:6666:000.000.000.000 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:256.256.256.256"), "IPV6 1111:2222:3333:4444:5555:6666:256.256.256.256 should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("fe80:0000:0000:0000:0204:61ff:fe9d:f156"), "IPV6 fe80:0000:0000:0000:0204:61ff:fe9d:f156 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("fe80:0:0:0:204:61ff:fe9d:f156"), "IPV6 fe80:0:0:0:204:61ff:fe9d:f156 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("fe80::204:61ff:fe9d:f156"), "IPV6 fe80::204:61ff:fe9d:f156 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address(":"), "IPV6 : should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:c000:280"), "IPV6 ::ffff:c000:280 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444::5555:"), "IPV6 1111:2222:3333:4444::5555: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::5555:"), "IPV6 1111:2222:3333::5555: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::5555:"), "IPV6 1111:2222::5555: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::5555:"), "IPV6 1111::5555: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::5555:"), "IPV6 ::5555: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::"), "IPV6 ::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:"), "IPV6 1111: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::5555"), "IPV6 :1111:2222:3333:4444::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::5555"), "IPV6 :1111:2222:3333::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::5555"), "IPV6 :1111:2222::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::5555"), "IPV6 :1111::5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::5555"), "IPV6 :::5555 should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:85a3:0000:0000:8a2e:0370:7334"), "IPV6 2001:0db8:85a3:0000:0000:8a2e:0370:7334 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:db8:85a3:0:0:8a2e:370:7334"), "IPV6 2001:db8:85a3:0:0:8a2e:370:7334 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:db8:85a3::8a2e:370:7334"), "IPV6 2001:db8:85a3::8a2e:370:7334 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:0000:0000:0000:0000:1428:57ab"), "IPV6 2001:0db8:0000:0000:0000:0000:1428:57ab should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:0000:0000:0000::1428:57ab"), "IPV6 2001:0db8:0000:0000:0000::1428:57ab should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:0:0:0:0:1428:57ab"), "IPV6 2001:0db8:0:0:0:0:1428:57ab should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:0:0::1428:57ab"), "IPV6 2001:0db8:0:0::1428:57ab should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8::1428:57ab"), "IPV6 2001:0db8::1428:57ab should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:db8::1428:57ab"), "IPV6 2001:db8::1428:57ab should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::ffff:0c22:384e"), "IPV6 ::ffff:0c22:384e should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:1234:0000:0000:0000:0000:0000"), "IPV6 2001:0db8:1234:0000:0000:0000:0000:0000 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:0db8:1234:ffff:ffff:ffff:ffff:ffff"), "IPV6 2001:0db8:1234:ffff:ffff:ffff:ffff:ffff should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("2001:db8:a::123"), "IPV6 2001:db8:a::123 should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("123"), "IPV6 123 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("ldkfj"), "IPV6 ldkfj should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("2001::FFD3::57ab"), "IPV6 2001::FFD3::57ab should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("2001:db8:85a3::8a2e:37023:7334"), "IPV6 2001:db8:85a3::8a2e:37023:7334 should be invalid");
        // todo this is valid only in PHP because hex2dec conversion does not function correctly (370k -> 880, instead of 0)
        // $this->assertFalse($this->validator->isValidInet6Address("2001:db8:85a3::8a2e:370k:7334"), "IPV6 2001:db8:85a3::8a2e:370k:7334 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1:2:3:4:5:6:7:8:9"), "IPV6 1:2:3:4:5:6:7:8:9 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1::2::3"), "IPV6 1::2::3 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1:::3:4:5"), "IPV6 1:::3:4:5 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1:2:3::4:5:6:7:8:9"), "IPV6 1:2:3::4:5:6:7:8:9 should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:8888"), "IPV6 1111:2222:3333:4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777::"), "IPV6 1111:2222:3333:4444:5555:6666:7777:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666::"), "IPV6 1111:2222:3333:4444:5555:6666:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::"), "IPV6 1111:2222:3333:4444:5555:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444::"), "IPV6 1111:2222:3333:4444:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::"), "IPV6 1111:2222:3333:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::"), "IPV6 1111:2222:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::"), "IPV6 1111:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666::8888"), "IPV6 1111:2222:3333:4444:5555:6666::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::8888"), "IPV6 1111:2222:3333:4444:5555::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444::8888"), "IPV6 1111:2222:3333:4444::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::8888"), "IPV6 1111:2222:3333::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::8888"), "IPV6 1111:2222::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::8888"), "IPV6 1111::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::8888"), "IPV6 ::8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::7777:8888"), "IPV6 1111:2222:3333:4444:5555::7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444::7777:8888"), "IPV6 1111:2222:3333:4444::7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::7777:8888"), "IPV6 1111:2222:3333::7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::7777:8888"), "IPV6 1111:2222::7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::7777:8888"), "IPV6 1111::7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::7777:8888"), "IPV6 ::7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444::6666:7777:8888"), "IPV6 1111:2222:3333:4444::6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::6666:7777:8888"), "IPV6 1111:2222:3333::6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::6666:7777:8888"), "IPV6 1111:2222::6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::6666:7777:8888"), "IPV6 1111::6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::6666:7777:8888"), "IPV6 ::6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::5555:6666:7777:8888"), "IPV6 1111:2222:3333::5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::5555:6666:7777:8888"), "IPV6 1111:2222::5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::5555:6666:7777:8888"), "IPV6 1111::5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::5555:6666:7777:8888"), "IPV6 ::5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::4444:5555:6666:7777:8888"), "IPV6 1111:2222::4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::4444:5555:6666:7777:8888"), "IPV6 1111::4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::4444:5555:6666:7777:8888"), "IPV6 ::4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::3333:4444:5555:6666:7777:8888"), "IPV6 1111::3333:4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::3333:4444:5555:6666:7777:8888"), "IPV6 ::3333:4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2222:3333:4444:5555:6666:7777:8888"), "IPV6 ::2222:3333:4444:5555:6666:7777:8888 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:123.123.123.123"), "IPV6 1111:2222:3333:4444:5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::123.123.123.123"), "IPV6 1111:2222:3333:4444:5555::123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444::123.123.123.123"), "IPV6 1111:2222:3333:4444::123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::123.123.123.123"), "IPV6 1111:2222:3333::123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::123.123.123.123"), "IPV6 1111:2222::123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::123.123.123.123"), "IPV6 1111::123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::123.123.123.123"), "IPV6 ::123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333:4444::6666:123.123.123.123"), "IPV6 1111:2222:3333:4444::6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::6666:123.123.123.123"), "IPV6 1111:2222:3333::6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::6666:123.123.123.123"), "IPV6 1111:2222::6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::6666:123.123.123.123"), "IPV6 1111::6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::6666:123.123.123.123"), "IPV6 ::6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222:3333::5555:6666:123.123.123.123"), "IPV6 1111:2222:3333::5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::5555:6666:123.123.123.123"), "IPV6 1111:2222::5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::5555:6666:123.123.123.123"), "IPV6 1111::5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::5555:6666:123.123.123.123"), "IPV6 ::5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111:2222::4444:5555:6666:123.123.123.123"), "IPV6 1111:2222::4444:5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::4444:5555:6666:123.123.123.123"), "IPV6 1111::4444:5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::4444:5555:6666:123.123.123.123"), "IPV6 ::4444:5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("1111::3333:4444:5555:6666:123.123.123.123"), "IPV6 1111::3333:4444:5555:6666:123.123.123.123 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::2222:3333:4444:5555:6666:123.123.123.123"), "IPV6 ::2222:3333:4444:5555:6666:123.123.123.123 should be valid");
        // Trying combinations of "0" and "::"
        // These are all syntactically correct, but are bad form
        // because "0" adjacent to "::" should be combined into "::"
        $this->assertTrue($this->validator->isValidInet6Address("::0:0:0:0:0:0:0"), "IPV6 ::0:0:0:0:0:0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0:0:0:0:0:0"), "IPV6 ::0:0:0:0:0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0:0:0:0:0"), "IPV6 ::0:0:0:0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0:0:0:0"), "IPV6 ::0:0:0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0:0:0"), "IPV6 ::0:0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0:0"), "IPV6 ::0:0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0"), "IPV6 ::0 should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0:0:0::"), "IPV6 0:0:0:0:0:0:0:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0:0::"), "IPV6 0:0:0:0:0:0:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0:0::"), "IPV6 0:0:0:0:0:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0:0::"), "IPV6 0:0:0:0:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0:0::"), "IPV6 0:0:0:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0:0::"), "IPV6 0:0:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("0::"), "IPV6 0:: should be valid");
        // Invalid data
        $this->assertFalse($this->validator->isValidInet6Address("XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX"), "IPV6 XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX should be invalid");
        // Too many components
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:8888:9999"), "IPV6 1111:2222:3333:4444:5555:6666:7777:8888:9999 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:8888::"), "IPV6 1111:2222:3333:4444:5555:6666:7777:8888:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555:6666:7777:8888:9999"), "IPV6 ::2222:3333:4444:5555:6666:7777:8888:9999 should be invalid");
        // Too few components
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777"), "IPV6 1111:2222:3333:4444:5555:6666:7777 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666"), "IPV6 1111:2222:3333:4444:5555:6666 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555"), "IPV6 1111:2222:3333:4444:5555 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444"), "IPV6 1111:2222:3333:4444 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333"), "IPV6 1111:2222:3333 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222"), "IPV6 1111:2222 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111"), "IPV6 1111 should be invalid");
        // Missing :
        $this->assertFalse($this->validator->isValidInet6Address("11112222:3333:4444:5555:6666:7777:8888"), "IPV6 11112222:3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:22223333:4444:5555:6666:7777:8888"), "IPV6 1111:22223333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:33334444:5555:6666:7777:8888"), "IPV6 1111:2222:33334444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:44445555:6666:7777:8888"), "IPV6 1111:2222:3333:44445555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:55556666:7777:8888"), "IPV6 1111:2222:3333:4444:55556666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:66667777:8888"), "IPV6 1111:2222:3333:4444:5555:66667777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:77778888"), "IPV6 1111:2222:3333:4444:5555:6666:77778888 should be invalid");
        // Missing : intended for ::
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:8888:"), "IPV6 1111:2222:3333:4444:5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:"), "IPV6 1111:2222:3333:4444:5555:6666:7777: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:"), "IPV6 1111:2222:3333:4444:5555:6666: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:"), "IPV6 1111:2222:3333:4444:5555: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:"), "IPV6 1111:2222:3333:4444: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:"), "IPV6 1111:2222:3333: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:"), "IPV6 1111:2222: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":8888"), "IPV6 :8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":7777:8888"), "IPV6 :7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":6666:7777:8888"), "IPV6 :6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":5555:6666:7777:8888"), "IPV6 :5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":4444:5555:6666:7777:8888"), "IPV6 :4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":3333:4444:5555:6666:7777:8888"), "IPV6 :3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":2222:3333:4444:5555:6666:7777:8888"), "IPV6 :2222:3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555:6666:7777:8888"), "IPV6 :1111:2222:3333:4444:5555:6666:7777:8888 should be invalid");
        // :::
        $this->assertFalse($this->validator->isValidInet6Address(":::2222:3333:4444:5555:6666:7777:8888"), "IPV6 :::2222:3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:::3333:4444:5555:6666:7777:8888"), "IPV6 1111:::3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:::4444:5555:6666:7777:8888"), "IPV6 1111:2222:::4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:::5555:6666:7777:8888"), "IPV6 1111:2222:3333:::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:::6666:7777:8888"), "IPV6 1111:2222:3333:4444:::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:::7777:8888"), "IPV6 1111:2222:3333:4444:5555:::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:::8888"), "IPV6 1111:2222:3333:4444:5555:6666:::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:::"), "IPV6 1111:2222:3333:4444:5555:6666:7777::: should be invalid");
        // Double ::
        $this->assertFalse($this->validator->isValidInet6Address("::2222::4444:5555:6666:7777:8888"), "IPV6 ::2222::4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333::5555:6666:7777:8888"), "IPV6 ::2222:3333::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444::6666:7777:8888"), "IPV6 ::2222:3333:4444::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555::7777:8888"), "IPV6 ::2222:3333:4444:5555::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555:7777::8888"), "IPV6 ::2222:3333:4444:5555:7777::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555:7777:8888::"), "IPV6 ::2222:3333:4444:5555:7777:8888:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333::5555:6666:7777:8888"), "IPV6 1111::3333::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444::6666:7777:8888"), "IPV6 1111::3333:4444::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444:5555::7777:8888"), "IPV6 1111::3333:4444:5555::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444:5555:6666::8888"), "IPV6 1111::3333:4444:5555:6666::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444:5555:6666:7777::"), "IPV6 1111::3333:4444:5555:6666:7777:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444::6666:7777:8888"), "IPV6 1111:2222::4444::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444:5555::7777:8888"), "IPV6 1111:2222::4444:5555::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444:5555:6666::8888"), "IPV6 1111:2222::4444:5555:6666::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444:5555:6666:7777::"), "IPV6 1111:2222::4444:5555:6666:7777:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::5555::7777:8888"), "IPV6 1111:2222:3333::5555::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::5555:6666::8888"), "IPV6 1111:2222:3333::5555:6666::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::5555:6666:7777::"), "IPV6 1111:2222:3333::5555:6666:7777:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444::6666::8888"), "IPV6 1111:2222:3333:4444::6666::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444::6666:7777::"), "IPV6 1111:2222:3333:4444::6666:7777:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::7777::"), "IPV6 1111:2222:3333:4444:5555::7777:: should be invalid");
        // Too many components"
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:8888:1.2.3.4"), "IPV6 1111:2222:3333:4444:5555:6666:7777:8888:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:1.2.3.4"), "IPV6 1111:2222:3333:4444:5555:6666:7777:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666::1.2.3.4"), "IPV6 1111:2222:3333:4444:5555:6666::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555:6666:7777:1.2.3.4"), "IPV6 ::2222:3333:4444:5555:6666:7777:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:1.2.3.4.5"), "IPV6 1111:2222:3333:4444:5555:6666:1.2.3.4.5 should be invalid");
        // Too few components
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:1.2.3.4"), "IPV6 1111:2222:3333:4444:5555:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:1.2.3.4"), "IPV6 1111:2222:3333:4444:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:1.2.3.4"), "IPV6 1111:2222:3333:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:1.2.3.4"), "IPV6 1111:2222:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:1.2.3.4"), "IPV6 1111:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1.2.3.4"), "IPV6 1.2.3.4 should be invalid");
        // Missing :
        $this->assertFalse($this->validator->isValidInet6Address("11112222:3333:4444:5555:6666:1.2.3.4"), "IPV6 11112222:3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:22223333:4444:5555:6666:1.2.3.4"), "IPV6 1111:22223333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:33334444:5555:6666:1.2.3.4"), "IPV6 1111:2222:33334444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:44445555:6666:1.2.3.4"), "IPV6 1111:2222:3333:44445555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:55556666:1.2.3.4"), "IPV6 1111:2222:3333:4444:55556666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:66661.2.3.4"), "IPV6 1111:2222:3333:4444:5555:66661.2.3.4 should be invalid");
        // Missing .
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:255255.255.255"), "IPV6 1111:2222:3333:4444:5555:6666:255255.255.255 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:255.255255.255"), "IPV6 1111:2222:3333:4444:5555:6666:255.255255.255 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:255.255.255255"), "IPV6 1111:2222:3333:4444:5555:6666:255.255.255255 should be invalid");
        // Missing : intended for ::
        $this->assertFalse($this->validator->isValidInet6Address(":1.2.3.4"), "IPV6 :1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":6666:1.2.3.4"), "IPV6 :6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":5555:6666:1.2.3.4"), "IPV6 :5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":4444:5555:6666:1.2.3.4"), "IPV6 :4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":3333:4444:5555:6666:1.2.3.4"), "IPV6 :3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":2222:3333:4444:5555:6666:1.2.3.4"), "IPV6 :2222:3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555:6666:1.2.3.4"), "IPV6 :1111:2222:3333:4444:5555:6666:1.2.3.4 should be invalid");
        // :::
        $this->assertFalse($this->validator->isValidInet6Address(":::2222:3333:4444:5555:6666:1.2.3.4"), "IPV6 :::2222:3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:::3333:4444:5555:6666:1.2.3.4"), "IPV6 1111:::3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:::4444:5555:6666:1.2.3.4"), "IPV6 1111:2222:::4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:::5555:6666:1.2.3.4"), "IPV6 1111:2222:3333:::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:::6666:1.2.3.4"), "IPV6 1111:2222:3333:4444:::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:::1.2.3.4"), "IPV6 1111:2222:3333:4444:5555:::1.2.3.4 should be invalid");
        // Double ::
        $this->assertFalse($this->validator->isValidInet6Address("::2222::4444:5555:6666:1.2.3.4"), "IPV6 ::2222::4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333::5555:6666:1.2.3.4"), "IPV6 ::2222:3333::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444::6666:1.2.3.4"), "IPV6 ::2222:3333:4444::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555::1.2.3.4"), "IPV6 ::2222:3333:4444:5555::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333::5555:6666:1.2.3.4"), "IPV6 1111::3333::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444::6666:1.2.3.4"), "IPV6 1111::3333:4444::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444:5555::1.2.3.4"), "IPV6 1111::3333:4444:5555::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444::6666:1.2.3.4"), "IPV6 1111:2222::4444::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444:5555::1.2.3.4"), "IPV6 1111:2222::4444:5555::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::5555::1.2.3.4"), "IPV6 1111:2222:3333::5555::1.2.3.4 should be invalid");
        // Missing parts
        $this->assertFalse($this->validator->isValidInet6Address("::."), "IPV6 ::. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::.."), "IPV6 ::.. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::..."), "IPV6 ::... should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1..."), "IPV6 ::1... should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.."), "IPV6 ::1.2.. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::1.2.3."), "IPV6 ::1.2.3. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::.2.."), "IPV6 ::.2.. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::.2.3."), "IPV6 ::.2.3. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::.2.3.4"), "IPV6 ::.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::..3."), "IPV6 ::..3. should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::..3.4"), "IPV6 ::..3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::...4"), "IPV6 ::...4 should be invalid");
        // Extra : in front
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555:6666:7777::"), "IPV6 :1111:2222:3333:4444:5555:6666:7777:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555:6666::"), "IPV6 :1111:2222:3333:4444:5555:6666:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555::"), "IPV6 :1111:2222:3333:4444:5555:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::"), "IPV6 :1111:2222:3333:4444:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::"), "IPV6 :1111:2222:3333:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::"), "IPV6 :1111:2222:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::"), "IPV6 :1111:: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555:6666::8888"), "IPV6 :1111:2222:3333:4444:5555:6666::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555::8888"), "IPV6 :1111:2222:3333:4444:5555::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::8888"), "IPV6 :1111:2222:3333:4444::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::8888"), "IPV6 :1111:2222:3333::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::8888"), "IPV6 :1111:2222::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::8888"), "IPV6 :1111::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::8888"), "IPV6 :::8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555::7777:8888"), "IPV6 :1111:2222:3333:4444:5555::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::7777:8888"), "IPV6 :1111:2222:3333:4444::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::7777:8888"), "IPV6 :1111:2222:3333::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::7777:8888"), "IPV6 :1111:2222::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::7777:8888"), "IPV6 :1111::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::7777:8888"), "IPV6 :::7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::6666:7777:8888"), "IPV6 :1111:2222:3333:4444::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::6666:7777:8888"), "IPV6 :1111:2222:3333::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::6666:7777:8888"), "IPV6 :1111:2222::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::6666:7777:8888"), "IPV6 :1111::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::6666:7777:8888"), "IPV6 :::6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::5555:6666:7777:8888"), "IPV6 :1111:2222:3333::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::5555:6666:7777:8888"), "IPV6 :1111:2222::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::5555:6666:7777:8888"), "IPV6 :1111::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::5555:6666:7777:8888"), "IPV6 :::5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::4444:5555:6666:7777:8888"), "IPV6 :1111:2222::4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::4444:5555:6666:7777:8888"), "IPV6 :1111::4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::4444:5555:6666:7777:8888"), "IPV6 :::4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::3333:4444:5555:6666:7777:8888"), "IPV6 :1111::3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::3333:4444:5555:6666:7777:8888"), "IPV6 :::3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::2222:3333:4444:5555:6666:7777:8888"), "IPV6 :::2222:3333:4444:5555:6666:7777:8888 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555:6666:1.2.3.4"), "IPV6 :1111:2222:3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444:5555::1.2.3.4"), "IPV6 :1111:2222:3333:4444:5555::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::1.2.3.4"), "IPV6 :1111:2222:3333:4444::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::1.2.3.4"), "IPV6 :1111:2222:3333::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::1.2.3.4"), "IPV6 :1111:2222::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::1.2.3.4"), "IPV6 :1111::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::1.2.3.4"), "IPV6 :::1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333:4444::6666:1.2.3.4"), "IPV6 :1111:2222:3333:4444::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::6666:1.2.3.4"), "IPV6 :1111:2222:3333::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::6666:1.2.3.4"), "IPV6 :1111:2222::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::6666:1.2.3.4"), "IPV6 :1111::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::6666:1.2.3.4"), "IPV6 :::6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222:3333::5555:6666:1.2.3.4"), "IPV6 :1111:2222:3333::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::5555:6666:1.2.3.4"), "IPV6 :1111:2222::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::5555:6666:1.2.3.4"), "IPV6 :1111::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::5555:6666:1.2.3.4"), "IPV6 :::5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111:2222::4444:5555:6666:1.2.3.4"), "IPV6 :1111:2222::4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::4444:5555:6666:1.2.3.4"), "IPV6 :1111::4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::4444:5555:6666:1.2.3.4"), "IPV6 :::4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":1111::3333:4444:5555:6666:1.2.3.4"), "IPV6 :1111::3333:4444:5555:6666:1.2.3.4 should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address(":::2222:3333:4444:5555:6666:1.2.3.4"), "IPV6 :::2222:3333:4444:5555:6666:1.2.3.4 should be invalid");
        // Extra : at end
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:7777:::"), "IPV6 1111:2222:3333:4444:5555:6666:7777::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666:::"), "IPV6 1111:2222:3333:4444:5555:6666::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:::"), "IPV6 1111:2222:3333:4444:5555::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:::"), "IPV6 1111:2222:3333:4444::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:::"), "IPV6 1111:2222:3333::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:::"), "IPV6 1111:2222::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:::"), "IPV6 1111::: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555:6666::8888:"), "IPV6 1111:2222:3333:4444:5555:6666::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::8888:"), "IPV6 1111:2222:3333:4444:5555::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444::8888:"), "IPV6 1111:2222:3333:4444::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::8888:"), "IPV6 1111:2222:3333::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::8888:"), "IPV6 1111:2222::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::8888:"), "IPV6 1111::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::8888:"), "IPV6 ::8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444:5555::7777:8888:"), "IPV6 1111:2222:3333:4444:5555::7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444::7777:8888:"), "IPV6 1111:2222:3333:4444::7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::7777:8888:"), "IPV6 1111:2222:3333::7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::7777:8888:"), "IPV6 1111:2222::7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::7777:8888:"), "IPV6 1111::7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::7777:8888:"), "IPV6 ::7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333:4444::6666:7777:8888:"), "IPV6 1111:2222:3333:4444::6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::6666:7777:8888:"), "IPV6 1111:2222:3333::6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::6666:7777:8888:"), "IPV6 1111:2222::6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::6666:7777:8888:"), "IPV6 1111::6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::6666:7777:8888:"), "IPV6 ::6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222:3333::5555:6666:7777:8888:"), "IPV6 1111:2222:3333::5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::5555:6666:7777:8888:"), "IPV6 1111:2222::5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::5555:6666:7777:8888:"), "IPV6 1111::5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::5555:6666:7777:8888:"), "IPV6 ::5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111:2222::4444:5555:6666:7777:8888:"), "IPV6 1111:2222::4444:5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::4444:5555:6666:7777:8888:"), "IPV6 1111::4444:5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::4444:5555:6666:7777:8888:"), "IPV6 ::4444:5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("1111::3333:4444:5555:6666:7777:8888:"), "IPV6 1111::3333:4444:5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::3333:4444:5555:6666:7777:8888:"), "IPV6 ::3333:4444:5555:6666:7777:8888: should be invalid");
        $this->assertFalse($this->validator->isValidInet6Address("::2222:3333:4444:5555:6666:7777:8888:"), "IPV6 ::2222:3333:4444:5555:6666:7777:8888: should be invalid");
        $this->assertTrue($this->validator->isValidInet6Address("0:a:b:c:d:e:f::"), "IPV6 0:a:b:c:d:e:f:: should be valid");
        $this->assertTrue($this->validator->isValidInet6Address("::0:a:b:c:d:e:f"), "IPV6 ::0:a:b:c:d:e:f should be valid"); // syntactically correct, but bad form (::0:... could be combined)
        $this->assertTrue($this->validator->isValidInet6Address("a:b:c:d:e:f:0::"), "IPV6 a:b:c:d:e:f:0:: should be valid");
        $this->assertFalse($this->validator->isValidInet6Address("':10.0.0.1"), "IPV6 ':10.0.0.1 should be invalid");
    }
}
