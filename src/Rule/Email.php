<?php

namespace PHPCommons\Validator\Rule;

use PHPCommons\Validator\Rule;
use function strlen;

/**
 * Perform email validations.
 *
 * Based on a script by <a href="mailto:stamhankar@hotmail.com">Sandeep V. Tamhankar</a>
 * http://javascript.internet.com
 *
 * This implementation is not guaranteed to catch all possible errors in an email address.
 */
class Email implements Rule {

    const SPECIAL_CHARS = '[:cntrl:]\\(\\)<>@,;:\'\\\\\\\"\\.\\[\\]';
    const VALID_CHARS = '(\\\\.)|[^\\s' . self::SPECIAL_CHARS . ']';
    const QUOTED_USER = '("(\\"|[^"])*")';
    const WORD = '((' . self::VALID_CHARS . "|')+|" . self::QUOTED_USER . ')';

    const EMAIL_REGEX = '/^\\s*?(.+)@(.+?)\\s*$/';
    const IP_DOMAIN_REGEX = '/^\\[(.*)\\]$/';
    const USER_REGEX = '/^\\s*' . self::WORD . '(\\.' . self::WORD . ')*$/';

    const MAX_USERNAME_LEN = 64;

    /**
     * @var boolean
     */
    private $allowLocal;

    /**
     * @var boolean
     */
    private $allowTld;

    /**
     * @var Email
     */
    private static $emailValidator;

    /**
     * @var Email
     */
    private static $emailValidatorWithTld;

    /**
     * @var Email
     */
    private static $emailValidatorWithLocal;

    /**
     * @var Email
     */
    private static $emailValidatorWithLocalWithTld;

    /**
     * @param bool $allowLocal
     * @param bool $allowTld
     *
     * @return Email
     */
    public static function getInstance(bool $allowLocal = false, bool $allowTld = false) : Email {
        if ($allowLocal) {
            if ($allowTld) {
                if (!self::$emailValidatorWithLocalWithTld) {
                    self::$emailValidatorWithLocalWithTld = new self(true, true);
                }

                return self::$emailValidatorWithLocalWithTld;
            }

            if (!self::$emailValidatorWithLocal) {
                self::$emailValidatorWithLocal = new self(true, false);
            }

            return self::$emailValidatorWithLocal;
        }

        if ($allowTld) {
            if (!self::$emailValidatorWithTld) {
                self::$emailValidatorWithTld = new self(false, true);
            }

            return self::$emailValidatorWithTld;
        }

        if (!self::$emailValidator) {
            self::$emailValidator = new self(false, false);
        }

        return self::$emailValidator;
    }

    /**
     * Email constructor.
     *
     * @param bool $allowLocal
     * @param bool $allowTld
     */
    protected function __construct(bool $allowLocal = false, bool $allowTld = false) {
        $this->allowLocal = $allowLocal;
        $this->allowTld = $allowTld;
    }

    /**
     * Checks if a field has a valid e-mail address.
     *
     * @param string email The value validation is being performed on.
     *                     A null value is considered invalid.
     *
     * @return true if the email address is valid.
     */
    public function isValid($email = null) : bool {
        if (null === $email) {
            return false;
        }

        // check this first - it's cheap!
        if ('.' === substr($email, -1)) {
            return false;
        }

        // Check the whole email address structure
        if (!preg_match(self::EMAIL_REGEX, $email, $matches)) {
            return false;
        }

        if (!$this->isValidUser($matches[1])) {
            return false;
        }

        if (!$this->isValidDomain($matches[2])) {
            return false;
        }

        return true;

    }

    /**
     * Returns true if the domain component of an email address is valid.
     *
     * @param string $domain being validated, may be in IDN format
     *
     * @return true if the email address's domain is valid.
     */
    protected function isValidDomain(string $domain = null) : bool {
        // see if domain is an IP address in brackets
        preg_match(self::IP_DOMAIN_REGEX, $domain, $ipDomainMatches);

        if (!empty($ipDomainMatches)) {
            $inetAddressValidator = new InetAddress();
            return $inetAddressValidator->isValid($ipDomainMatches[1]);
        }

        // Domain is symbolic name
        $domainValidator = Domain::getInstance($this->allowLocal);

        if ($this->allowTld) {
            return $domainValidator->isValid($domain) || ($domain[0] !== '.' && $domainValidator->isValidTld($domain));
        }

        return $domainValidator->isValid($domain);
    }

    /**
     * Returns true if the user component of an email address is valid.
     *
     * @param string user being validated
     *
     * @return true if the user name is valid.
     */
    protected function isValidUser(string $user = null) : bool {
        if (null === $user || strlen($user) > self::MAX_USERNAME_LEN) {
            return false;
        }

        return preg_match(self::USER_REGEX, $user);
    }

}