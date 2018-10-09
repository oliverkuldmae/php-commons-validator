<?php

namespace PHPCommons\Validator\Rules;

use function in_array;

/**
 * URL Validation routines.
 * Behavior of validation is modified by passing in options:
 *  ALLOW_2_SLASHES - [FALSE]  Allows double '/' characters in the path component.
 *  NO_FRAGMENT- [FALSE]  By default fragments are allowed, if this option is included then fragments are flagged as illegal.
 *  ALLOW_ALL_SCHEMES - [FALSE] By default only http, https, and ftp are considered valid schemes.
 *      Enabling this option will let any scheme pass validation.
 *
 * Originally based in on PHP script by Debbie Dyer, validation.php v1.2b, Date: 03/07/02,
 * http://javascript.internet.com. However, this validation now bears little resemblance
 * to the PHP original.
 *
 * @see http://www.ietf.org/rfc/rfc2396.txt Uniform Resource Identifiers (URI): Generic Syntax
 */
class Url implements Rule {

    const MAX_UNSIGNED_16_BIT_INT = 0xFFFF; // port max

    /**
     * Allows all validly formatted schemes to pass validation instead of
     * supplying a set of valid schemes.
     */
    const ALLOW_ALL_SCHEMES = 1 << 0;

    /**
     * Allow two slashes in the path component of the URL.
     */
    const ALLOW_2_SLASHES = 1 << 1;

    /**
     * Enabling this options disallows any URL fragments.
     */
    const NO_FRAGMENTS = 1 << 2;

    /**
     * Allow local URLs, such as http://localhost/ or http://machine/ .
     * This enables a broad-brush check, for complex local machine name validation requirements you should create your own regex instead
     */
    const ALLOW_LOCAL_URLS = 1 << 3; // CHECKSTYLE IGNORE MagicNumber

    /**
     * This expression derived/taken from the BNF for URI (RFC2396).
     */
    const URL_REGEX =
        '/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?$/';
    //     12             3    4           5       6   7        8 9

    /**
     * Schema/Protocol (ie. http:, ftp:, file:, etc).
     */
    const PARSE_URL_SCHEME = 2;

    /**
     * Includes hostname/ip and port number.
     */
    const PARSE_URL_AUTHORITY = 4;

    const PARSE_URL_PATH = 5;

    const PARSE_URL_QUERY = 7;

    const PARSE_URL_FRAGMENT = 9;

    /**
     * Protocol scheme (e.g. http, ftp, https).
     */
    const SCHEME_REGEX = '/^[[:alpha:]][[:alnum:]\\+\\-\\.]*$/';

    // Drop numeric, and  "+-." for now
    // TODO does not allow for optional userinfo.
    // Validation of character set is done by isValidAuthority
    const AUTHORITY_CHARS_REGEX = "[:alnum:]\\-\\."; // allows for IPV4 but not IPV6
    const IPV6_REGEX = '[0-9a-fA-F:]+'; // do this as separate match because : could cause ambiguity with port prefix

    // userinfo    = *( unreserved / pct-encoded / sub-delims / ":" )
    // unreserved    = ALPHA / DIGIT / "-" / "." / "_" / "~"
    // sub-delims    = "!" / "$" / "&" / "'" / "(" / ")" / "*" / "+" / "," / ";" / "="
    // We assume that password has the same valid chars as user info
    const USERINFO_CHARS_REGEX = "[a-zA-Z0-9%-._~!$&'()*+,;=]";

    // since neither ':' nor '@' are allowed chars, we don't need to use non-greedy matching
    const USERINFO_FIELD_REGEX =
        self::USERINFO_CHARS_REGEX . '+' . // At least one character for the name
        '(?::' . self::USERINFO_CHARS_REGEX . '*)?@'; // colon and password may be absent

    const AUTHORITY_REGEX =
        "/^(?:\\[(" . self::IPV6_REGEX . ")\\]|(?:(?:" . self::USERINFO_FIELD_REGEX . ')?([' . self::AUTHORITY_CHARS_REGEX . ']*)))(?::(\\d*))?(.*)?$/';
    //           1                          e.g. user:pass@          2                                         3       4

    const PARSE_AUTHORITY_IPV6 = 1;

    const PARSE_AUTHORITY_HOST_IP = 2; // excludes userinfo, if present

    const PARSE_AUTHORITY_PORT = 3; // excludes leading colon

    /**
     * Should always be empty. The code currently allows spaces.
     */
    const PARSE_AUTHORITY_EXTRA = 4;

    const PATH_REGEX = '/^(\/[-\\w:@&?=+,.!\/~*\'%$_;\\(\\)]*)?$/';

    const QUERY_REGEX = '/^(\\S*)$/';

    /**
     * Holds the set of current validation options.
     */
    private $options;

    /**
     * The set of schemes that are allowed to be in a URL.
     */
    private $allowedSchemes; // Must be lower-case

    /**
     * Regular expressions used to manually validate authorities if IANA
     * domain name validation isn't desired.
     */
    private $authorityRegex;

    /**
     * If no schemes are provided, default to this set.
     */
    const DEFAULT_SCHEMES = ['http', 'https', 'ftp']; // Must be lower-case

    /**
     * Url constructor.
     *
     * @param array  $schemes
     * @param int    $options
     * @param string $authorityRegex
     */
    public function __construct(array $schemes = [], int $options = null, string $authorityRegex = null) {
        $this->options = $options;

        if ($this->isOn(self::ALLOW_ALL_SCHEMES)) {
            $this->allowedSchemes = [];
        } else {
            if (empty($schemes)) {
                $schemes = self::DEFAULT_SCHEMES;
            }

            $this->allowedSchemes = array_map('mb_strtolower', $schemes);
        }

        $this->authorityRegex = $authorityRegex;
    }

    /**
     * Checks if a field has a valid url address.
     *
     * Note that the method calls #isValidAuthority()
     * which checks that the domain is valid.
     *
     * @param string $url The value validation is being performed on.  A <code>null</code>
     * value is considered invalid.
     *
     * @return bool
     */
    public function isValid($url = null) : bool {
        if (null === $url) {
            return false;
        }

        if (!preg_match(self::URL_REGEX, $url, $matches)) {
            return false;
        }

        $scheme = @$matches[self::PARSE_URL_SCHEME];
        if (!$this->isValidScheme($scheme)) {
            return false;
        }

        $authority = @$matches[self::PARSE_URL_AUTHORITY];
        if ('file' === $scheme) {
            // Special case - file: allows an empty authority
            if (!empty($authority) && strpos($authority, ':') !== false) { // but cannot allow trailing :
                return false;
            }
            // drop through to continue validation
        } else if (!$this->isValidAuthority($authority)) {
            return false;
        }

        if (!$this->isValidPath(@$matches[self::PARSE_URL_PATH])) {
            return false;
        }

        if (!$this->isValidQuery(@$matches[self::PARSE_URL_QUERY])) {
            return false;
        }

        if (!$this->isValidFragment(@$matches[self::PARSE_URL_FRAGMENT])) {
            return false;
        }

        return true;
    }

    /**
     * Validate scheme. If schemes[] was initialized to a non null,
     * then only those schemes are allowed.
     * Otherwise the default schemes are "http", "https", "ftp".
     * Matching is case-blind.
     *
     * @param string scheme The scheme to validate. A null value is considered invalid.
     *
     * @return bool
     */
    public function isValidScheme(string $scheme = null) : bool {
        if (null === $scheme) {
            return false;
        }

        if (!preg_match(self::SCHEME_REGEX, $scheme)) {
            return false;
        }

        if ($this->isOff(self::ALLOW_ALL_SCHEMES) && !in_array(mb_strtolower($scheme), $this->allowedSchemes, true)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the authority is properly formatted.  An authority is the combination
     * of hostname and port. A null authority value is considered invalid.
     * Note: this implementation validates the domain unless a RegexValidator was provided.
     * If a RegexValidator was supplied and it matches, then the authority is regarded
     * as valid with no further checks, otherwise the method checks against the
     * AUTHORITY_PATTERN and the DomainValidator (ALLOW_LOCAL_URLS)
     *
     * @param string $authority Authority value to validate, allows IDN
     *
     * @return bool
     */
    private function isValidAuthority(string $authority = null) : bool {
        if (null === $authority) {
            return false;
        }

        // check manual authority validation if specified
        if (null !== $this->authorityRegex && preg_match($this->authorityRegex, $authority)) {
            return true;
        }

        // convert to ASCII if possible
        $authorityASCII = Domain::unicodeToASCII($authority);

        if (!preg_match(self::AUTHORITY_REGEX, $authorityASCII, $matches)) {
            return false;
        }

        // We have to process IPV6 separately because that is parsed in a different group
        $ipv6 = $matches[self::PARSE_AUTHORITY_IPV6];
        if ('' !== $ipv6) {
            $inetAddressValidator = InetAddress::getInstance();
            if (!$inetAddressValidator->isValid($ipv6)) {
                return false;
            }
        } else {
            $hostLocation = $matches[self::PARSE_AUTHORITY_HOST_IP];
            // check if authority is hostname or IP address:
            // try a hostname first since that's much more likely
            $domainValidator = Domain::getInstance($this->isOn(self::ALLOW_LOCAL_URLS));
            if (!$domainValidator->isValid($hostLocation)) {
                // try an IPv4 address
                $inetAddressValidator = InetAddress::getInstance();
                if (!$inetAddressValidator->isValidInet4Address($hostLocation)) {
                    return false;
                }
            }

            $port = $matches[self::PARSE_AUTHORITY_PORT];
            if ('' !== $port) {
                $iPort = (int) $port;
                if ($iPort < 0 || $iPort > self::MAX_UNSIGNED_16_BIT_INT) {
                    return false;
                }
            }
        }

        $extra = $matches[self::PARSE_AUTHORITY_EXTRA];

        return '' === trim($extra);
    }

    /**
     * Returns true if the path is valid. A null value is considered invalid.
     *
     * @param string $path Path value to validate.
     *
     * @return bool
     */
    private function isValidPath(string $path = null) : bool {
        if (null === $path) {
            return false;
        }

        if (!preg_match(self::PATH_REGEX, $path)) {
            return false;
        }

        if ($path === '/..' // Trying to go via the parent dir
            || strpos($path, '/../') === 0 // Trying to go via the parent dir
        ) {
            return false;
        }

        return !$this->isOff(self::ALLOW_2_SLASHES) || $this->countToken('//', $path) <= 0;
    }

    /**
     * Returns true if the query is null or it's a properly formatted query string.
     *
     * @param string $query Query value to validate.
     *
     * @return bool
     */
    private function isValidQuery(string $query = null) : bool {
        if (null === $query) {
            return true;
        }

        return preg_match(self::QUERY_REGEX, $query);
    }

    /**
     * Returns true if the given fragment is null or fragments are allowed.
     *
     * @param string $fragment Fragment value to validate.
     *
     * @return bool
     */
    private function isValidFragment(string $fragment = null) : bool {
        if (null === $fragment) {
            return true;
        }

        return $this->isOff(self::NO_FRAGMENTS);
    }


    /**
     * Returns the number of times the token appears in the target.
     *
     * @param string $token Token value to be counted.
     * @param string $target Target value to count tokens in.
     *
     * @return int
     */
    protected function countToken(string $token, string $target) : int {
        $tokenIndex = $count = 0;

        while (false !== $tokenIndex) {
            $tokenIndex = strpos($target, $token, $tokenIndex);
            if ($tokenIndex > -1) {
                $tokenIndex++;
                $count++;
            }
        }

        return $count;
    }

    /**
     * Tests whether the given flag is on.  If the flag is not a power of 2
     * (ie. 3) this tests whether the combination of flags is on.
     *
     * @param int $flag Flag value to check.
     *
     * @return bool whether the specified flag value is on.
     */
    private function isOn(int $flag) : bool {
        return ($this->options & $flag) > 0;
    }

    /**
     * Tests whether the given flag is off.  If the flag is not a power of 2
     * (ie. 3) this tests whether the combination of flags is off.
     *
     * @param int $flag Flag value to check.
     *
     * @return bool whether the specified flag value is off.
     */
    private function isOff(int $flag) : bool {
        return ($this->options & $flag) === 0;
    }
}