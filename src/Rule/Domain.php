<?php

namespace PHPCommons\Validator\Rule;

use InvalidArgumentException;
use PHPCommons\Validator\Rule;
use PHPCommons\Validator\Util\{IDN, TLD};
use function count;
use function in_array;
use function strlen;

class Domain implements Rule {

    const MAX_DOMAIN_LENGTH = 253;

    // Regular expression strings for hostnames (derived from RFC2396 and RFC 1123)

    // RFC2396: domainlabel   = alphanum | alphanum *( alphanum | "-" ) alphanum
    // Max 63 characters
    const DOMAIN_LABEL_REGEX = '[[:alnum:]](?>[[:alnum:]-]{0,61}[[:alnum:]])?';

    // RFC2396 toplabel = alpha | alpha *( alphanum | "-" ) alphanum
    // Max 63 characters
    const TOP_LABEL_REGEX = '[[:alpha:]](?>[[:alnum:]-]{0,61}[[:alnum:]])?';

    // RFC2396 hostname = *( domainlabel "." ) toplabel [ "." ]
    // Note that the regex currently requires both a domain label and a top level label, whereas
    // the RFC does not. This is because the regex is used to detect if a TLD is present.
    // If the match fails, input is checked against DOMAIN_LABEL_REGEX (hostnameRegex)
    // RFC1123 sec 2.1 allows hostnames to start with a digit
    const DOMAIN_NAME_REGEX = '/^(?:' . self::DOMAIN_LABEL_REGEX . "\.)+" . '(' . self::TOP_LABEL_REGEX . ")\.?$/u";

    /**
     * @var boolean
     */
    private $allowLocal;

    /**
     * @var Domain
     */
    private static $domainValidator;

    /**
     * @var Domain
     */
    private static $domainValidatorWithLocal;

    /**
     * @param bool $allowLocal
     *
     * @return Domain
     */
    public static function getInstance(bool $allowLocal = false) : Domain {
        if ($allowLocal) {
            if (!self::$domainValidatorWithLocal) {
                self::$domainValidatorWithLocal = new self(true);
            }

            return self::$domainValidatorWithLocal;
        }

        if (!self::$domainValidator) {
            self::$domainValidator = new self(false);
        }

        return self::$domainValidator;
    }

    /**
     * Domain constructor.
     *
     * @param bool $allowLocal
     */
    protected function __construct(bool $allowLocal = false) {
        $this->allowLocal = $allowLocal;
    }

    /**
     * Returns true if the specified string parses
     * as a valid domain name with a recognized top-level domain.
     * The parsing is case-insensitive.
     *
     * @param string domain the parameter to check for domain name syntax
     *
     * @return true if the parameter is a valid domain name
     */
    public function isValid($domain = null) : bool {
        if ($domain === null) {
            return false;
        }

        $domain = self::unicodeToASCII($domain);

        // hosts must be equally reachable via punycode and Unicode;
        // Unicode is never shorter than punycode, so check punycode
        // if $domain did not convert, then it will be caught by ASCII
        // checks in the regexes below
        if (mb_strlen($domain) > self::MAX_DOMAIN_LENGTH) {
            return false;
        }

        preg_match(self::DOMAIN_NAME_REGEX, $domain, $matches);

        if (!empty($matches)) {
            return $this->isValidTld($matches[count($matches) - 1]);
        }

        return $this->allowLocal && preg_match('/^' . self::DOMAIN_LABEL_REGEX . '$/', $domain);
    }

    /**
     * Returns true if the specified string matches any
     * IANA-defined top-level domain. Leading dots are ignored if present.
     * The search is case-insensitive.
     *
     * @param string $tld the parameter to check for TLD status, not null
     *
     * @return true if the parameter is a TLD
     */
    public function isValidTld(string $tld) : bool {
        $tld = self::unicodeToASCII($tld);

        if ($this->allowLocal && $this->isValidLocalTld($tld)) {
            return true;
        }

        return $this->isValidInfrastructureTld($tld)
            || $this->isValidGenericTld($tld)
            || $this->isValidCountryCodeTld($tld);
    }

    /**
     * Returns true if the specified string matches any
     * IANA-defined infrastructure top-level domain. Leading dots are
     * ignored if present. The search is case-insensitive.
     *
     * @param string $iTld the parameter to check for infrastructure TLD status, not null
     *
     * @return true if the parameter is an infrastructure TLD
     */
    public function isValidInfrastructureTld(string $iTld) : bool {
        $key = $this->chompLeadingDot(self::unicodeToASCII($iTld));
        return in_array(mb_strtolower($key), TLD::INFRASTRUCTURE_TLDS, TRUE);
    }

    /**
     * Returns true if the specified string matches any
     * IANA-defined generic top-level domain. Leading dots are ignored
     * if present. The search is case-insensitive.
     *
     * @param string $gTld the parameter to check for generic TLD status, not null
     *
     * @return true if the parameter is a generic TLD
     */
    public function isValidGenericTld(string $gTld) : bool {
        $key = $this->chompLeadingDot(self::unicodeToASCII($gTld));
        return in_array(mb_strtolower($key), TLD::GENERIC_TLDS, TRUE);
    }

    /**
     * Returns true if the specified string matches any
     * IANA-defined country code top-level domain. Leading dots are
     * ignored if present. The search is case-insensitive.
     *
     * @param string $ccTld the parameter to check for country code TLD status, not null
     *
     * @return true if the parameter is a country code TLD
     */
    public function isValidCountryCodeTld(string $ccTld) : bool {
        $key = $this->chompLeadingDot(self::unicodeToASCII($ccTld));
        return in_array(mb_strtolower($key), TLD::COUNTRY_CODE_TLDS, TRUE);
    }

    /**
     * Returns true if the specified string matches any
     * widely used 'local' domains (localhost or localdomain). Leading dots are
     * ignored if present. The search is case-insensitive.
     *
     * @param string $lTld the parameter to check for local TLD status, not null
     *
     * @return true if the parameter is an local TLD
     */
    public function isValidLocalTld(string $lTld) : bool {
        $key = $this->chompLeadingDot(self::unicodeToASCII($lTld));
        return in_array(mb_strtolower($key), TLD::LOCAL_TLDS, TRUE);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    private function chompLeadingDot(string $str) : string {
        if (0 === mb_strpos($str, '.')) {
            return mb_substr($str, 1);
        }

        return $str;
    }

    /**
     * Converts potentially Unicode input to punycode.
     * If conversion fails, returns the original input.
     *
     * @param string $input the string to convert, not null
     *
     * @return string converted input, or original input if conversion fails
     */
    // Needed by UrlValidator
    public static function unicodeToASCII(string $input) : string {
        if (IDN::isASCIIOnly($input)) { // skip possibly expensive processing
            return $input;
        }

        try {
            $ascii = IDN::toASCII($input);
            if (self::keepsTrailingDot()) {
                return $ascii;
            }

            $length = strlen($input);
            if ($length === 0) {// check there is a last character
                return $input;
            }

            // RFC3490 3.1. 1)
            //            Whenever dots are used as label separators, the following
            //            characters MUST be recognized as dots: U+002E (full stop), U+3002
            //            (ideographic full stop), U+FF0E (fullwidth full stop), U+FF61
            //            (halfwidth ideographic full stop).
            $lastChar = substr($input, $length - 1);// fetch original last char
            switch ($lastChar) {
                case '\u002E': // "." full stop
                case '\u3002': // ideographic full stop
                case '\uFF0E': // fullwidth full stop
                case '\uFF61': // halfwidth ideographic full stop
                    return $ascii . '.'; // restore the missing stop
                default:
                    return $ascii;
            }
        } catch (InvalidArgumentException $e) { // input is not valid
            return $input;
        }
    }

    /**
     * Must conform to isValid above
     *
     * @param string|null $domain
     *
     * @return bool
     */
    public function isValidDomainSyntax(string $domain = null) : bool {
        if (null === $domain) {
            return false;
        }

        $domain = self::unicodeToASCII($domain);

        // hosts must be equally reachable via punycode and Unicode;
        // Unicode is never shorter than punycode, so check punycode
        // if $domain did not convert, then it will be caught by ASCII
        // checks in the regexes below
        if (mb_strlen($domain) > self::MAX_DOMAIN_LENGTH) {
            return false;
        }

        preg_match(self::DOMAIN_NAME_REGEX, $domain, $matches);

        return !empty($matches) || preg_match('/^' . self::DOMAIN_LABEL_REGEX . '$/', $domain);
    }

    /**
     * @return bool
     */
    private static function keepsTrailingDot() : bool {
        $input = 'a.'; // must be a valid name
        return IDN::toASCII($input) === $input;
    }

}
