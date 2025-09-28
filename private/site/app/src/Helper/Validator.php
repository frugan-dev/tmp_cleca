<?php

declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

namespace App\Helper;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use IsoCodes\Bsn;
use IsoCodes\Cif;
use IsoCodes\Insee;
use IsoCodes\Ssn;
use IsoCodes\Vat;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class Validator extends Helper
{
    public function isValidEmail($email, $strictMode = true)
    {
        if (empty($email)) {
            return false;
        }

        $validator = new EmailValidator();

        $validations = [
            // Standard RFC-like email validation.
            new RFCValidation(),

            // RFC-like validation that will fail when warnings* are found.
            new NoRFCWarningsValidation(),
        ];

        // Add heavy validations only in strict mode
        if ($strictMode) {
            // Will check if there are DNS records that signal that the server accepts emails.
            // This does not entails that the email exists.
            $validations[] = new DNSCheckValidation();

            // Follows RFC2822 for message-id to validate that field, that has some differences in the domain part.
            $validations[] = new MessageIDValidation();

            // Will check for multi-utf-8 chars that can signal an erroneous email name.
            $validations[] = new SpoofCheckValidation();
        }

        $multipleValidations = new MultipleValidationWithAnd($validations);
        $isValid = $validator->isValid($email, $multipleValidations);

        // Log if email is invalid or has warnings
        if (!$isValid || $validator->hasWarnings()) {
            $warnings = [];
            if ($validator->hasWarnings()) {
                foreach ($validator->getWarnings() as $warning) {
                    $warnings[] = $warning->__toString();
                }
            }

            $this->logger->warning('Email validation issue for address: {email}', [
                'error' => var_export($warnings, true),
                'email' => $email,
                'valid' => $isValid,
                'warnings' => $warnings,
                'strict_mode' => $strictMode,
            ]);
        }

        return $isValid;
    }

    // https://dunglas.fr/2014/11/php-7-introducing-a-domain-name-validator-and-making-the-url-validator-stricter/
    // https://stackoverflow.com/a/48801316/3929620
    public function isValidUrl($value)
    {
        if (empty($value)) {
            return false;
        }

        $noSchemeUrl = $this->Url()->removeScheme($value);
        $noSchemeHost = strstr((string) $noSchemeUrl, '/') ? substr((string) $noSchemeUrl, 0, strpos((string) $noSchemeUrl, '/')) : $noSchemeUrl;

        $schemeUrl = $this->Url()->addScheme($value);

        if (!filter_var($noSchemeHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return false;
        }

        // https://php.watch/versions/8.0/filter_var-flags
        if (!filter_var($schemeUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (!filter_var(gethostbyname($noSchemeHost), FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!checkdnsrr($noSchemeHost, 'A')) {
            return false;
        }

        return true;
    }

    public function isValidPhone(
        $value,
        $defaultRegion = null,
        $PhoneNumberTypes = [
            // PhoneNumberType::FIXED_LINE,
            // PhoneNumberType::MOBILE,
            // PhoneNumberType::FIXED_LINE_OR_MOBILE,
            // PhoneNumberType::TOLL_FREE,
            // PhoneNumberType::PREMIUM_RATE,
            // PhoneNumberType::SHARED_COST,
            // PhoneNumberType::VOIP,
            // PhoneNumberType::PERSONAL_NUMBER,
            // PhoneNumberType::PAGER,
            // PhoneNumberType::UAN,
            // PhoneNumberType::VOICEMAIL,
        ],
        $format = PhoneNumberFormat::INTERNATIONAL
    ) {
        if (!$value) {
            return false;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            $swissNumberProto = $phoneUtil->parse($value, $defaultRegion);

            if ($phoneUtil->isPossibleNumber($swissNumberProto)) { // It provides a more lenient check than isValidNumber()
                if (!$phoneUtil->isValidNumber($swissNumberProto)) {
                    return false;
                }
            } else {
                return false;
            }

            if ((is_countable($PhoneNumberTypes) ? \count($PhoneNumberTypes) : 0) > 0) {
                $PhoneNumberTypesValues = array_intersect_key(PhoneNumberType::values(), array_flip($PhoneNumberTypes));

                if (!\in_array($phoneUtil->getNumberType($swissNumberProto), $PhoneNumberTypes, true)) {
                    return $PhoneNumberTypesValues;
                }
            }

            return $phoneUtil->format($swissNumberProto, $format);
        } catch (NumberParseException $e) {
            $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__.' -> '.$value, [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function isValidVat($value, $country = null)
    {
        if (!$value) {
            return false;
        }

        if ($country && !\array_key_exists($country, Vat::$patterns)) {
            return false;
        }

        if (!Vat::validate($value)) {
            return false;
        }

        return true;
    }

    // https://en.wikipedia.org/wiki/National_identification_number
    // http://www.myfakeinfo.com/check/validate-id.php
    public function isValidNIN($value, $country)
    {
        if (!$value) {
            return false;
        }

        switch ($country) {
            case 'IT':
                if (!$this->has('CodiceFiscale\Checker')) {
                    $this->set('CodiceFiscale\Checker', new Checker());
                }

                return @$this->get('CodiceFiscale\Checker')->isFormallyCorrect($value);

                break;

            case 'ES': // C38934196
                return Cif::validate($value);

                break;
                /*case 'FI': //311280-888Y 131052-308T
                    return Hetu::validate( $value );
                    break;*/

            case 'FR': // 180126955222380 283209921625930
                return Insee::validate($value);

                break;

            case 'NL': // 027036078
                return Bsn::validate($value);

                break;

            case 'US': // 374520302 428268014
                return Ssn::validate($value);

                break;
        }

        return true;
    }
}
