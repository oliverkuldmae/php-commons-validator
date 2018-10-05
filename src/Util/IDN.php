<?php

namespace PHPCommons\Validator\Util;

use InvalidArgumentException;
use TrueBV\Punycode;
use function mb_strlen;
use function ord;

class IDN {

    /**
     * Flag to turn on the check against STD-3 ASCII rules
     */
    const USE_STD3_ASCII_RULES = 0x02;

    const MAX_LABEL_LENGTH = 63;
    const ACE_PREFIX = 'xn--';

    /**
     * @var Punycode
     */
    private static $punyCode;

    public static function toASCII(string $input, $flag = 0) : string {
        $p = $q = 0;
        $out = '';

        if (self::isRootLabel($input)) {
            return '.';
        }

        $inputLength = mb_strlen($input);

        while ($p < $inputLength) {
            $q = self::searchDots($input, $p);
            $out .= self::toASCIIInternal(mb_substr($input, $p, $q), $flag);

            if ($q !== $inputLength) {
                // has more labels, or keep the trailing dot as at present
                $out .= '.';
            }

            $p = $q + 1;
        }

        return $out;
    }

    /**
     * Check if input contains only ASCII
     * Treats null as all ASCII
     *
     * @param string|null $input
     *
     * @return bool
     */
    public static function isASCIIOnly(string $input = null) : bool {
        if (null === $input) {
            return true;
        }

        $length = mb_strlen($input);
        $chars = preg_split('//u', $input, null, PREG_SPLIT_NO_EMPTY);

        for ($i = 0; $i < $length; $i++) {
            if (ord($chars[$i]) > 0x7F) { // CHECKSTYLE IGNORE MagicNumber
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a string is a root label, ".".
     *
     * @param string $s
     *
     * @return bool
     */
    private static function isRootLabel(string $s) : bool {
        return mb_strlen($s) === 1 && self::isLabelSeparator(mb_substr($s, 0, 1));
    }

    /**
     * Checks if a character is a label separator, i.e. a dot character.
     *
     * @param string $c
     *
     * @return bool
     */
    private static function isLabelSeparator(string $c) : bool {
        return $c === '.' || $c === "\u{3002}" || $c === "\u{FF0E}" || $c === "\u{FF61}";
    }

    /**
     * Searches dots in a string and returns the index of that character,
     *  or if there are no dots, returns the length of input string
     *
     * Dots might be:
     *  \u002E (full stop),
     *  \u3002 (ideographic full stop),
     *  \uFF0E (fullwidth full stop),
     *  \uFF61 (halfwidth ideographic full stop).
     *
     * @param string $s
     * @param int    $start
     *
     * @return int
     */
    private static function searchDots(string $s, int $start) : int {
        $stringLength = mb_strlen($s);
        $chars = preg_split('//u', $s, null, PREG_SPLIT_NO_EMPTY);

        for ($i = $start; $i < $stringLength; $i++) {
            if (self::isLabelSeparator($chars[$i])) {
                break;
            }
        }

        return $i;
    }

    /**
     * @param string $label
     *
     * @param int    $flag
     *
     * @return string
     */
    private static function toASCIIInternal(string $label, int $flag) : string {
        // step 1
        // Check if the string contains code points outside the ASCII range 0..0x7c.
        $isASCII = self::isASCIIOnly($label);

        // step 2
        // perform the nameprep operation; flag ALLOW_UNASSIGNED is used here
        if (!$isASCII) {
            $dest = $label;
            // todo this is hard to implement
            // UCharacterIterator iter = UCharacterIterator.getInstance(label);
            // try {
            //      dest = namePrep.prepare(iter, flag);
            // } catch (java.text.ParseException e) {
            //      throw new IllegalArgumentException(e);
            // }
        } else {
            $dest = $label;
        }

        // step 8, move forward to check the smallest number of the code points
        // the length must be inside 1..63
        if ('' === $dest) {
            throw new InvalidArgumentException('Empty label is not a legal name');
        }

        // step 3
        // Verify the absence of non-LDH ASCII code points
        //   0..0x2c, 0x2e..0x2f, 0x3a..0x40, 0x5b..0x60, 0x7b..0x7f
        // Verify the absence of leading and trailing hyphen
        $useSTD3ASCIIRules = ($flag & self::USE_STD3_ASCII_RULES) !== 0;
        if ($useSTD3ASCIIRules) {
            $destLength = mb_strlen($dest);
            for ($i = 0; $i < $destLength; $i++) {
                $chars = preg_split('//u', $dest, null, PREG_SPLIT_NO_EMPTY);

                if (self::isNonLDHAsciiCodePoint($chars[$i])) {
                    throw new InvalidArgumentException('Contains non-LDH ASCII characters');
                }
            }

            if (mb_strpos($dest, '-') === 0 || $dest[$destLength - 1] === '-') {
                throw new InvalidArgumentException('Has leading or trailing hyphen');
            }
        }

        // step 4
        // If all code points are inside 0..0x7f, skip to step 8
        if (!$isASCII && !mb_check_encoding($dest, 'ASCII')) {
            // step 5
            // verify the sequence does not begin with ACE prefix
            if (!self::startsWithACEPrefix($dest)) {
                // step 6
                // encode the sequence with punycode
                $dest = self::getPunyCode()->encode($dest);
            } else {
                throw new InvalidArgumentException('The input starts with the ACE Prefix');
            }
        }

        // step 8
        // the length must be inside 1..63
        if (mb_strlen($dest) > self::MAX_LABEL_LENGTH) {
            throw new InvalidArgumentException('The label in the input is too long');
        }

        return $dest;
    }

    /**
     * LDH stands for "letter/digit/hyphen", with characters restricted to the
     * 26-letter Latin alphabet <A-Z a-z>, the digits <0-9>, and the hyphen
     * <->.
     * Non LDH refers to characters in the ASCII range, but which are not
     * letters, digits or the hypen.
     *
     * non-LDH = 0..0x2C, 0x2E..0x2F, 0x3A..0x40, 0x5B..0x60, 0x7B..0x7F
     *
     * @param int $ch
     *
     * @return bool
     */
    private static function isNonLDHAsciiCodePoint(int $ch) : bool {
        return (0x0000 <= $ch && $ch <= 0x002C) ||
            (0x002E <= $ch && $ch <= 0x002F) ||
            (0x003A <= $ch && $ch <= 0x0040) ||
            (0x005B <= $ch && $ch <= 0x0060) ||
            (0x007B <= $ch && $ch <= 0x007F);
    }

    /**
     * Checks if a string starts with ACE-prefix.
     *
     * @param string $input
     *
     * @return bool
     */
    private static function startsWithACEPrefix(string $input) : bool {
        $startsWithPrefix = true;

        $acePrefixLength = mb_strlen(self::ACE_PREFIX);
        if (mb_strlen($input) < $acePrefixLength) {
            return false;
        }

        $chars = preg_split('//u', $input, null, PREG_SPLIT_NO_EMPTY);

        for ($i = 0; $i < $acePrefixLength; $i++) {
            if (self::toASCIILower($chars[$i]) !== self::ACE_PREFIX[$i]) {
                $startsWithPrefix = false;
            }
        }

        return $startsWithPrefix;
    }

    /**
     * @param string $c
     *
     * @return string
     */
    private static function toASCIILower(string $c) : string {
        if ('A' <= $c && $c <= 'Z') {
            return ($c . 'a' - 'A');
        }

        return $c;
    }

    private static function getPunyCode() : Punycode {
        if (!self::$punyCode) {
            self::$punyCode = new Punycode();
        }

        return self::$punyCode;
    }

}