<?php

namespace PHPCommons\Validator\Rules;

use PHPCommons\Validator\Utils\{IBANCheckDigit, IBANValidator as Validator};

class IBAN implements Rule {

    /**
     * @var IBAN
     */
    private static $instance;

    /**
     * @var Validator[]
     */
    private $validators;

    /**
     * @return IBAN
     */
    public static function getInstance() : IBAN {
        if (!self::$instance) {
            /*
             * Wikipedia [1] says that only uppercase is allowed.
             * The SWIFT PDF file [2] implies that lower case is allowed.
             * However there are no examples using lower-case.
             * Unfortunately the relevant ISO documents (ISO 13616-1) are not available for free.
             * The IBANCheckDigit code treats upper and lower case the same,
             * so any case validation has to be done in this class.
             *
             * Note: the European Payments council has a document [3] which includes a description
             * of the IBAN. Section 5 clearly states that only upper case is allowed.
             * Also the maximum length is 34 characters (including the country code),
             * and the length is fixed for each country.
             *
             * It looks like lower-case is permitted in BBANs, but they must be converted to
             * upper case for IBANs.
             *
             * [1] https://en.wikipedia.org/wiki/International_Bank_Account_Number
             * [2] http://www.swift.com/dsp/resources/documents/IBAN_Registry.pdf (404)
             * => https://www.swift.com/sites/default/files/resources/iban_registry.pdf
             * [3] http://www.europeanpaymentscouncil.eu/documents/ECBS%20IBAN%20standard%20EBS204_V3.2.pdf
             */
            self::$instance = new self(
                [
                    'AD' => new Validator('AD', 24, "AD\\d{10}[A-Z0-9]{12}"), // Andorra
                    'AE' => new Validator('AE', 23, "AE\\d{21}"), // United Arab Emirates
                    'AL' => new Validator('AL', 28, "AL\\d{10}[A-Z0-9]{16}"), // Albania
                    'AT' => new Validator('AT', 20, "AT\\d{18}"), // Austria
                    'AZ' => new Validator('AZ', 28, "AZ\\d{2}[A-Z]{4}[A-Z0-9]{20}"), // Republic of Azerbaijan
                    'BA' => new Validator('BA', 20, "BA\\d{18}"), // Bosnia and Herzegovina
                    'BE' => new Validator('BE', 16, "BE\\d{14}"), // Belgium
                    'BG' => new Validator('BG', 22, "BG\\d{2}[A-Z]{4}\\d{6}[A-Z0-9]{8}"), // Bulgaria
                    'BH' => new Validator('BH', 22, "BH\\d{2}[A-Z]{4}[A-Z0-9]{14}"), // Bahrain (Kingdom of)
                    'BR' => new Validator('BR', 29, "BR\\d{25}[A-Z]{1}[A-Z0-9]{1}"), // Brazil
                    'BY' => new Validator('BY', 28, "BY\\d{2}[A-Z0-9]{4}\\d{4}[A-Z0-9]{16}"), // Republic of Belarus
                    'CH' => new Validator('CH', 21, "CH\\d{7}[A-Z0-9]{12}"), // Switzerland
                    'CR' => new Validator('CR', 22, "CR\\d{20}"), // Costa Rica
                    'CY' => new Validator('CY', 28, "CY\\d{10}[A-Z0-9]{16}"), // Cyprus
                    'CZ' => new Validator('CZ', 24, "CZ\\d{22}"), // Czech Republic
                    'DE' => new Validator('DE', 22, "DE\\d{20}"), // Germany
                    'DK' => new Validator('DK', 18, "DK\\d{16}"), // Denmark
                    'DO' => new Validator('DO', 28, "DO\\d{2}[A-Z0-9]{4}\\d{20}"), // Dominican Republic
                    'EE' => new Validator('EE', 20, "EE\\d{18}"), // Estonia
                    'ES' => new Validator('ES', 24, "ES\\d{22}"), // Spain
                    'FI' => new Validator('FI', 18, "FI\\d{16}"), // Finland
                    'FO' => new Validator('FO', 18, "FO\\d{16}"), // Denmark (Faroes)
                    'FR' => new Validator('FR', 27, "FR\\d{12}[A-Z0-9]{11}\\d{2}"), // France
                    'GB' => new Validator('GB', 22, "GB\\d{2}[A-Z]{4}\\d{14}"), // United Kingdom
                    'GE' => new Validator('GE', 22, "GE\\d{2}[A-Z]{2}\\d{16}"), // Georgia
                    'GI' => new Validator('GI', 23, "GI\\d{2}[A-Z]{4}[A-Z0-9]{15}"), // Gibraltar
                    'GL' => new Validator('GL', 18, "GL\\d{16}"), // Denmark (Greenland)
                    'GR' => new Validator('GR', 27, "GR\\d{9}[A-Z0-9]{16}"), // Greece
                    'GT' => new Validator('GT', 28, "GT\\d{2}[A-Z0-9]{24}"), // Guatemala
                    'HR' => new Validator('HR', 21, "HR\\d{19}"), // Croatia
                    'HU' => new Validator('HU', 28, "HU\\d{26}"), // Hungary
                    'IE' => new Validator('IE', 22, "IE\\d{2}[A-Z]{4}\\d{14}"), // Ireland
                    'IL' => new Validator('IL', 23, "IL\\d{21}"), // Israel
                    'IQ' => new Validator('IQ', 23, "IQ\\d{2}[A-Z]{4}\\d{15}"), // Iraq
                    'IS' => new Validator('IS', 26, "IS\\d{24}"), // Iceland
                    'IT' => new Validator('IT', 27, "IT\\d{2}[A-Z]{1}\\d{10}[A-Z0-9]{12}"), // Italy
                    'JO' => new Validator('JO', 30, "JO\\d{2}[A-Z]{4}\\d{4}[A-Z0-9]{18}"), // Jordan
                    'KW' => new Validator('KW', 30, "KW\\d{2}[A-Z]{4}[A-Z0-9]{22}"), // Kuwait
                    'KZ' => new Validator('KZ', 20, "KZ\\d{5}[A-Z0-9]{13}"), // Kazakhstan
                    'LB' => new Validator('LB', 28, "LB\\d{6}[A-Z0-9]{20}"), // Lebanon
                    'LC' => new Validator('LC', 32, "LC\\d{2}[A-Z]{4}[A-Z0-9]{24}"), // Saint Lucia
                    'LI' => new Validator('LI', 21, "LI\\d{7}[A-Z0-9]{12}"), // Liechtenstein (Principality of)
                    'LT' => new Validator('LT', 20, "LT\\d{18}"), // Lithuania
                    'LU' => new Validator('LU', 20, "LU\\d{5}[A-Z0-9]{13}"), // Luxembourg
                    'LV' => new Validator('LV', 21, "LV\\d{2}[A-Z]{4}[A-Z0-9]{13}"), // Latvia
                    'MC' => new Validator('MC', 27, "MC\\d{12}[A-Z0-9]{11}\\d{2}"), // Monaco
                    'MD' => new Validator('MD', 24, "MD\\d{2}[A-Z0-9]{20}"), // Moldova
                    'ME' => new Validator('ME', 22, "ME\\d{20}"), // Montenegro
                    'MK' => new Validator('MK', 19, "MK\\d{5}[A-Z0-9]{10}\\d{2}"), // Macedonia, Former Yugoslav Republic of
                    'MR' => new Validator('MR', 27, "MR\\d{25}"), // Mauritania
                    'MT' => new Validator('MT', 31, "MT\\d{2}[A-Z]{4}\\d{5}[A-Z0-9]{18}"), // Malta
                    'MU' => new Validator('MU', 30, "MU\\d{2}[A-Z]{4}\\d{19}[A-Z]{3}"), // Mauritius
                    'NL' => new Validator('NL', 18, "NL\\d{2}[A-Z]{4}\\d{10}"), // The Netherlands
                    'NO' => new Validator('NO', 15, "NO\\d{13}"), // Norway
                    'PK' => new Validator('PK', 24, "PK\\d{2}[A-Z]{4}[A-Z0-9]{16}"), // Pakistan
                    'PL' => new Validator('PL', 28, "PL\\d{26}"), // Poland
                    'PS' => new Validator('PS', 29, "PS\\d{2}[A-Z]{4}[A-Z0-9]{21}"), // Palestine, State of
                    'PT' => new Validator('PT', 25, "PT\\d{23}"), // Portugal
                    'QA' => new Validator('QA', 29, "QA\\d{2}[A-Z]{4}[A-Z0-9]{21}"), // Qatar
                    'RO' => new Validator('RO', 24, "RO\\d{2}[A-Z]{4}[A-Z0-9]{16}"), // Romania
                    'RS' => new Validator('RS', 22, "RS\\d{20}"), // Serbia
                    'SA' => new Validator('SA', 24, "SA\\d{4}[A-Z0-9]{18}"), // Saudi Arabia
                    'SC' => new Validator('SC', 31, "SC\\d{2}[A-Z]{4}\\d{20}[A-Z]{3}"), // Seychelles
                    'SE' => new Validator('SE', 24, "SE\\d{22}"), // Sweden
                    'SI' => new Validator('SI', 19, "SI\\d{17}"), // Slovenia
                    'SK' => new Validator('SK', 24, "SK\\d{22}"), // Slovak Republic
                    'SM' => new Validator('SM', 27, "SM\\d{2}[A-Z]{1}\\d{10}[A-Z0-9]{12}"), // San Marino
                    'ST' => new Validator('ST', 25, "ST\\d{23}"), // Sao Tome and Principe
                    'SV' => new Validator('SV', 28, "SV\\d{2}[A-Z]{4}\\d{20}"), // El Salvador
                    'TL' => new Validator('TL', 23, "TL\\d{21}"), // Timor-Leste
                    'TN' => new Validator('TN', 24, "TN\\d{22}"), // Tunisia
                    'TR' => new Validator('TR', 26, "TR\\d{8}[A-Z0-9]{16}"), // Turkey
                    'UA' => new Validator('UA', 29, "UA\\d{8}[A-Z0-9]{19}"), // Ukraine
                    'VG' => new Validator('VG', 24, "VG\\d{2}[A-Z]{4}\\d{16}"), // Virgin Islands, British
                    'XK' => new Validator('XK', 20, "XK\\d{18}"), // Republic of Kosovo
                ]
            );
        }

        return self::$instance;
    }

    /**
     * IBAN constructor.
     *
     * @param Validator[] $validators
     */
    private function __construct(array $validators) {
        $this->validators = $validators;
    }

    /**
     * Validate an IBAN Code
     *
     * @param string $code The value validation is being performed on
     *
     * @return bool
     */
    public function isValid($code = null) : bool {
        $validator = $this->getValidator($code);

        if ($validator === null || !$validator->isValidLength($code) || !$validator->isValidFormat($code)) {
            return false;
        }

        return IBANCheckDigit::getInstance()->isValid($code);
    }

    /**
     * Does the class have the required validator?
     *
     * @param string|null $code
     *
     * @return true if there is a validator
     */
    public function hasValidator(?string $code) : bool {
        return $this->getValidator($code) !== null;
    }

    /**
     * Get the Validator for a given IBAN
     *
     * @param string|null $code
     *
     * @return Validator|null validator or  if there is not one registered.
     */
    public function getValidator(?string $code) : ?Validator {
        if ($code === null || strlen($code) < 2) { // ensure we can extract the code
            return null;
        }

        $key = substr($code, 0, 2);

        return $this->validators[$key] ?? null;
    }

}
