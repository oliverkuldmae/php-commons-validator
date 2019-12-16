<?php

namespace PHPCommons\Validator\Utils;

use InvalidArgumentException;

class IBANValidator {

    /*
     * The minimum length does not appear to be defined by the standard.
     * Norway is currently the shortest at 15.
     *
     * There is no standard for BBANs; they vary between countries.
     * But a BBAN must consist of a branch id and account number.
     * Each of these must be at least 2 chars (generally more) so an absolute minimum is
     * 4 characters for the BBAN and 8 for the IBAN.
     */
    private const MIN_LEN = 8;
    private const MAX_LEN = 34; // defined by [3]

    private $countryCode;
    private $format;
    private $lengthOfIBAN; // used to avoid unnecessary regex matching

    /**
     * Validator constructor.
     *
     * @param string $cc
     * @param int $len
     * @param string $format
     */
    public function __construct(string $cc, int $len, string $format) {
        if (!(strlen($cc) === 2 && ctype_upper($cc))) {
            throw new InvalidArgumentException("Invalid country Code $cc; must be exactly 2 upper-case characters");
        }

        if ($len > self::MAX_LEN || $len < self::MIN_LEN) {
            throw new InvalidArgumentException(
                'Invalid length parameter, must be in range ' . self::MIN_LEN . ' to ' . self::MAX_LEN . ' inclusive: ' . $len
            );
        }

        if (strpos($format, $cc) !== 0) {
            throw new InvalidArgumentException("countryCode '$cc' does not agree with format: $format");
        }

        $this->countryCode = $cc;
        $this->lengthOfIBAN = $len;
        $this->format = $format;
    }

    public function isValidLength(string $code) : bool {
        return strlen($code) === $this->lengthOfIBAN;
    }

    public function isValidFormat(string $code) : bool {
        return preg_match('/^' . $this->format . '$/', $code);
    }

}