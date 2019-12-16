<?php

namespace PHPCommons\Validator\Utils;

use Exception;
use RuntimeException;

/**
 * IBAN (International Bank Account Number) Check Digit calculation/validation.
 *
 * This routine is based on the ISO 7064 Mod 97,10 check digit calculation routine.
 *
 * The two check digit characters in a IBAN number are the third and fourth characters
 * in the code. For check digit calculation/validation the first four characters are moved
 * to the end of the code.
 *
 * So CCDDnnnnnnn becomes nnnnnnnCCDD (where CC is the country code and DD is the check digit).
 * For check digit calculation the check digit value should be set to zero (i.e. CC00nnnnnnn in this example).
 *
 * Note: the class does not check the format of the IBAN number, only the check digits.
 *
 * @see http://en.wikipedia.org/wiki/International_Bank_Account_Number
 */
class IBANCheckDigit {

    private const MIN_CODE_LEN = 5;
    private const MAX_ALPHANUMERIC_VALUE = 35; // Character.getNumericValue('Z')
    private const MAX = 999999999;
    private const MODULUS = 97;

    /**
     * @var IBANCheckDigit
     */
    private static $instance;

    public static function getInstance() : IBANCheckDigit {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Validate the check digit of an IBAN code.
     *
     * @param string $code
     *
     * @return bool
     */
    public function isValid(string $code) : bool {
        if ($code === null || strlen($code) < self::MIN_CODE_LEN) {
            return false;
        }

        $check = substr($code, 2, 2);
        if (in_array($check, ['00', '01', '99'], true)) {
            return false;
        }

        try {
            $modulusResult = $this->calculateModulus($code);
            return ($modulusResult === 1);
        } catch (Exception  $ex) {
            return false;
        }
    }

    /**
     * Calculate the Check Digit for an IBAN code.
     * Note: The check digit is the third and fourth
     * characters and is set to the value "00".
     *
     * @param string $code
     *
     * @return string
     */
    public function calculate(string $code) : string {
        if ($code === null || strlen($code) < self::MIN_CODE_LEN) {
            throw new RuntimeException('Invalid Code length=' . ($code === null ? 0 : strlen($code)));
        }

        $code = substr($code, 0, 2) . '00' . substr($code, 4);
        $modulusResult = $this->calculateModulus($code);
        $charValue = (98 - $modulusResult);
        $checkDigit = (string) $charValue;

        return ($charValue > 9 ? $checkDigit : '0' . $checkDigit);
    }

    /**
     * Calculate the modulus for an IBAN code.
     *
     * @param string $code
     *
     * @return int
     */
    private function calculateModulus(string $code) : int {
        $reformattedCode = substr($code, 4) . substr($code, 0, 4);
        $total = 0;

        $length = strlen($reformattedCode);

        for ($i = 0; $i < $length; $i++) {
            $charValue = $this->getNumericValue($reformattedCode[$i]);
            if ($charValue < 0 || $charValue > self::MAX_ALPHANUMERIC_VALUE) {
                throw new RuntimeException("Invalid Character[{$i}] = '{$charValue}'");
            }

            $total = ($charValue > 9 ? $total * 100 : $total * 10) + $charValue;
            if ($total > self::MAX) {
                $total %= self::MODULUS;
            }
        }

        return $total % self::MODULUS;
    }

    private function getNumericValue(string $char) : int {
        if (ctype_digit($char)) {
            return ord($char) - ord('0');
        }

        if (ctype_upper($char)) {
            return ord($char) - ord('A') + 10;
        }

        if (ctype_lower($char)) {
            return ord($char) - ord('a') + 10;
        }

        return -1;
    }

}