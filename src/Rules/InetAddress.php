<?php

namespace PHPCommons\Validator\Rules;

use function array_slice;
use function count;
use function intval;
use function strlen;

/**
 * InetAddress validation and conversion routines.
 *
 * This class provides methods to validate a candidate IP address.
 */
class InetAddress implements Rule {

    const IPV4_MAX_OCTET_VALUE = 255;

    const MAX_UNSIGNED_SHORT = 0xffff;

    const BASE_16 = 16;

    const IPV4_REGEX = "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/";

    // Max number of hex groups (separated by :) in an IPV6 address
    const IPV6_MAX_HEX_GROUPS = 8;

    // Max hex digits in each IPv6 group
    const IPV6_MAX_HEX_DIGITS_PER_GROUP = 4;

    /**
     * @var InetAddress
     */
    private static $validator;

    public static function getInstance() : InetAddress {
        if (!self::$validator) {
            self::$validator = new self();
        }

        return self::$validator;
    }

    /**
     * @param string $inetAddress
     *
     * @return bool
     */
    public function isValid($inetAddress = null) : bool {
        return $this->isValidInet4Address($inetAddress) || $this->isValidInet6Address($inetAddress);
    }

    /**
     * Validates an IPv4 address. Returns true if valid.
     *
     * @param string|null $inet4Address
     *
     * @return true if the argument contains a valid IPv4 address
     */
    public function isValidInet4Address(string $inet4Address = null) : bool {
        preg_match(self::IPV4_REGEX, $inet4Address, $matches);

        if (empty($matches)) {
            return false;
        }

        foreach ($matches as $ipSegment) {
            // preg_match stores the full match as the first $matches array element
            if ($ipSegment === $inet4Address) {
                continue;
            }

            if ('' === $ipSegment) {
                return false;
            }

            if (((int) $ipSegment) > self::IPV4_MAX_OCTET_VALUE) {
                return false;
            }

            if (strlen($ipSegment) > 1 && '0' === $ipSegment[0]) {
                return false;
            }
        }

        return true;
    }


    /**
     * Validates an IPv6 address. Returns true if valid.
     *
     * @param string $inet6Address the IPv6 address to validate
     *
     * @return true if the argument contains a valid IPv6 address
     */
    public function isValidInet6Address(string $inet6Address = null) : bool {
        $containsCompressedZeroes = strpos($inet6Address, '::') !== FALSE;
        if ($containsCompressedZeroes && (strpos($inet6Address, '::') !== strrpos($inet6Address, '::'))) {
            return false;
        }

        $addressLength = strlen($inet6Address);
        $endsWithDoubleColon = substr($inet6Address, $addressLength - 2, 2) === '::';
        if ((strpos($inet6Address, ':') === 0 && strpos($inet6Address, '::') !== 0)
            || ($inet6Address[$addressLength - 1] === ':' && !$endsWithDoubleColon)
        ) {
            return false;
        }

        $octets = self::splitOctets($inet6Address);
        if ($containsCompressedZeroes) {
            if ($endsWithDoubleColon) {
                $octets[] = '';
            } else if (!empty($octets) && strpos($inet6Address, '::') === 0) {
                unset($octets[0]);
            }

            // Get values to reset keys
            $octets = array_values($octets);
        }

        $octetCount = count($octets);
        if ($octetCount > self::IPV6_MAX_HEX_GROUPS) {
            return false;
        }

        $validOctets = 0;
        $emptyOctets = 0; // consecutive empty chunks

        for ($i = 0; $i < $octetCount; $i++) {
            $octet = $octets[$i];

            if ('' === $octet) {
                if (++$emptyOctets > 1) {
                    return false;
                }
            } else {
                $emptyOctets = 0;
                if ($i === $octetCount - 1 && strpos($octet, '.') !== FALSE) {
                    if (!$this->isValidInet4Address($octet)) {
                        return false;
                    }

                    $validOctets += 2;
                    continue;
                }

                if (strlen($octet) > self::IPV6_MAX_HEX_DIGITS_PER_GROUP) {
                    return false;
                }

                $octetInt = intval($octet, 16);

                // Check if valid base16 number
                if ($octetInt === 0 && !is_numeric($octet)) {
                    return false;
                }

                if ($octetInt < 0 || $octetInt > self::MAX_UNSIGNED_SHORT) {
                    return false;
                }
            }

            $validOctets++;
        }

        /** @noinspection IfReturnReturnSimplificationInspection */
        if ($validOctets > self::IPV6_MAX_HEX_GROUPS || ($validOctets < self::IPV6_MAX_HEX_GROUPS && !$containsCompressedZeroes)) {
            return false;
        }

        return true;
    }

    /**
     * Function to emulate Java's String.split() method
     *
     * @param string $s
     *
     * @return array|string
     */
    private static function splitOctets(string $s) {
        $off = $next = 0;
        $list = [];

        while (($next = strpos($s, ':', $off)) !== false) {
            $list[] = substr($s, $off, $next - $off);
            $off = $next + 1;
        }

        if ($off === 0) {
            return $s;
        }

        $list[] = substr($s, $off, strlen($s));

        $resultSize = count($list);
        while ($resultSize > 0 && '' === $list[$resultSize - 1]) {
            $resultSize--;
        }

        return array_slice($list, 0, $resultSize);
    }

}