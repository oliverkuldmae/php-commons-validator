<?php

namespace PHPCommons\Validator\Rule;

use PHPCommons\Validator\Rule;
use function count;
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
     * @param string $inetAddress
     *
     * @return bool
     */
    public function isValid($inetAddress) : bool {
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
            if (empty($ipSegment)) {
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

        if ((strpos($inet6Address, ':') === 0 && '::' !== $inet6Address[0])
            || ($inet6Address[strlen($inet6Address) - 1] === ':' && $inet6Address[strlen($inet6Address) - 1] !== '::')
        ) {
            return false;
        }

        $octets = explode(':', $inet6Address);
        if ($containsCompressedZeroes) {
            $octetList = [];
            if ($octets[count($octets) - 1] === '::') {
                $octets[] = '';
            } else if (!empty($octetList) && strpos($inet6Address, '::') === 0) {
                unset($octetList[0]);
            }

            $octets = $octetList;
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

                if ($octetCount > self::IPV6_MAX_HEX_DIGITS_PER_GROUP) {
                    return false;
                }

                // todo check for failure?
                $octetInt = base_convert($octet, 10, 16);
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

}