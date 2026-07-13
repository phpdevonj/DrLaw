<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a mobile/contact number in international (E.164) format.
 *
 * The number is expected to arrive with its country calling code, e.g.
 * "+919876543210" (this is what the intl-tel-input widget submits). The rule
 * checks that:
 *   1. It is a well-formed E.164 number (leading "+" or "00", digits only,
 *      max 15 digits per ITU-T E.164).
 *   2. It begins with a real country calling code.
 *   3. The remaining national number has a length that is valid for that
 *      country (accurate ranges for the major markets, safe generic fallback
 *      for the rest).
 *
 * This is a server-side safety net so an invalid number cannot be stored even
 * if the browser-side validation is bypassed. No third-party package required.
 */
class PhoneNumber implements ValidationRule
{
    /** ITU-T E.164 hard limit on total digits (country code + national number). */
    private const MAX_E164_DIGITS = 15;

    /** Fallback national-number length range for countries without an explicit entry. */
    private const FALLBACK_MIN_NATIONAL = 4;
    private const FALLBACK_MAX_NATIONAL = 14;

    /**
     * Every valid country calling code (derived from the intl-tel-input data
     * that already ships with this project). Kept as strings so leading digits
     * are preserved during prefix matching.
     */
    private const CALLING_CODES = [
        '1', '20', '211', '212', '213', '216', '218', '220', '221', '222', '223',
        '224', '225', '226', '227', '228', '229', '230', '231', '232', '233',
        '234', '235', '236', '237', '238', '239', '240', '241', '242', '243',
        '244', '245', '246', '247', '248', '249', '250', '251', '252', '253',
        '254', '255', '256', '257', '258', '260', '261', '262', '263', '264',
        '265', '266', '267', '268', '269', '27', '290', '291', '297', '298',
        '299', '30', '31', '32', '33', '34', '350', '351', '352', '353', '354',
        '355', '356', '357', '358', '359', '36', '370', '371', '372', '373',
        '374', '375', '376', '377', '378', '380', '381', '382', '383', '385',
        '386', '387', '389', '39', '40', '41', '420', '421', '423', '43', '44',
        '45', '46', '47', '48', '49', '500', '501', '502', '503', '504', '505',
        '506', '507', '508', '509', '51', '52', '53', '54', '55', '56', '57',
        '58', '590', '591', '592', '593', '594', '595', '596', '597', '598',
        '599', '60', '61', '62', '63', '64', '65', '66', '670', '672', '673',
        '674', '675', '676', '677', '678', '679', '680', '681', '682', '683',
        '685', '686', '687', '688', '689', '690', '691', '692', '7', '81', '82',
        '84', '850', '852', '853', '855', '856', '86', '880', '886', '90', '91',
        '92', '93', '94', '95', '960', '961', '962', '963', '964', '965', '966',
        '967', '968', '970', '971', '972', '973', '974', '975', '976', '977',
        '98', '992', '993', '994', '995', '996', '998',
    ];

    /**
     * Valid national-number length range [min, max] per calling code. Anything
     * not listed uses the generic fallback range above. Ranges cover both
     * mobile and landline formats where a country uses several.
     */
    private const NATIONAL_LENGTHS = [
        '1'   => [10, 10],  // NANP (US, Canada, Caribbean)
        '7'   => [10, 10],  // Russia, Kazakhstan
        '20'  => [8, 10],   // Egypt
        '27'  => [9, 9],    // South Africa
        '30'  => [10, 10],  // Greece
        '31'  => [9, 9],    // Netherlands
        '32'  => [8, 9],    // Belgium
        '33'  => [9, 9],    // France
        '34'  => [9, 9],    // Spain
        '36'  => [8, 9],    // Hungary
        '39'  => [9, 11],   // Italy
        '40'  => [9, 9],    // Romania
        '41'  => [9, 9],    // Switzerland
        '43'  => [4, 13],   // Austria (highly variable)
        '44'  => [9, 10],   // United Kingdom
        '45'  => [8, 8],    // Denmark
        '46'  => [7, 9],    // Sweden
        '47'  => [8, 8],    // Norway
        '48'  => [9, 9],    // Poland
        '49'  => [6, 11],   // Germany (variable)
        '51'  => [9, 9],    // Peru
        '52'  => [10, 10],  // Mexico
        '54'  => [10, 11],  // Argentina
        '55'  => [10, 11],  // Brazil
        '56'  => [9, 9],    // Chile
        '57'  => [10, 10],  // Colombia
        '58'  => [10, 10],  // Venezuela
        '60'  => [7, 10],   // Malaysia
        '61'  => [9, 9],    // Australia
        '62'  => [8, 12],   // Indonesia
        '63'  => [10, 10],  // Philippines
        '64'  => [8, 10],   // New Zealand
        '65'  => [8, 8],    // Singapore
        '66'  => [9, 9],    // Thailand
        '81'  => [9, 10],   // Japan
        '82'  => [9, 10],   // South Korea
        '84'  => [9, 10],   // Vietnam
        '86'  => [7, 11],   // China
        '90'  => [10, 10],  // Turkey
        '91'  => [10, 10],  // India
        '92'  => [10, 10],  // Pakistan
        '93'  => [9, 9],    // Afghanistan
        '94'  => [9, 9],    // Sri Lanka
        '98'  => [10, 10],  // Iran
        '211' => [9, 9],    // South Sudan
        '212' => [9, 9],    // Morocco
        '213' => [9, 9],    // Algeria
        '216' => [8, 8],    // Tunisia
        '218' => [9, 9],    // Libya
        '234' => [8, 10],   // Nigeria
        '249' => [9, 9],    // Sudan
        '250' => [9, 9],    // Rwanda
        '251' => [9, 9],    // Ethiopia
        '254' => [9, 9],    // Kenya
        '255' => [9, 9],    // Tanzania
        '256' => [9, 9],    // Uganda
        '260' => [9, 9],    // Zambia
        '263' => [9, 9],    // Zimbabwe
        '351' => [9, 9],    // Portugal
        '352' => [9, 9],    // Luxembourg
        '353' => [7, 9],    // Ireland
        '358' => [5, 12],   // Finland
        '380' => [9, 9],    // Ukraine
        '420' => [9, 9],    // Czech Republic
        '421' => [9, 9],    // Slovakia
        '852' => [8, 8],    // Hong Kong
        '853' => [8, 8],    // Macau
        '855' => [8, 9],    // Cambodia
        '856' => [8, 10],   // Laos
        '880' => [10, 10],  // Bangladesh
        '886' => [9, 9],    // Taiwan
        '960' => [7, 7],    // Maldives
        '961' => [7, 8],    // Lebanon
        '962' => [9, 9],    // Jordan
        '963' => [8, 9],    // Syria
        '964' => [10, 10],  // Iraq
        '965' => [8, 8],    // Kuwait
        '966' => [9, 9],    // Saudi Arabia
        '967' => [7, 9],    // Yemen
        '968' => [8, 8],    // Oman
        '970' => [9, 9],    // Palestine
        '971' => [9, 9],    // United Arab Emirates
        '972' => [9, 9],    // Israel
        '973' => [8, 8],    // Bahrain
        '974' => [8, 8],    // Qatar
        '975' => [8, 8],    // Bhutan
        '976' => [8, 8],    // Mongolia
        '977' => [10, 10],  // Nepal
        '992' => [9, 9],    // Tajikistan
        '993' => [8, 8],    // Turkmenistan
        '994' => [9, 9],    // Azerbaijan
        '995' => [9, 9],    // Georgia
        '996' => [9, 9],    // Kyrgyzstan
        '998' => [9, 9],    // Uzbekistan
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Empty values are handled by the "required"/"nullable" rules elsewhere.
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        $raw = trim((string) $value);

        // A leading "+" or an international "00" prefix means the country code
        // is present. Without it we cannot determine the country, so reject.
        $hasInternationalPrefix = str_starts_with($raw, '+') || str_starts_with($raw, '00');

        // Keep digits only for the actual checks.
        $digits = preg_replace('/\D+/', '', $raw);

        // Drop the "00" international access prefix if that was used.
        if (! str_starts_with($raw, '+') && str_starts_with($raw, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            $fail(__('message.invalid_contact_number'));
            return;
        }

        if (! $hasInternationalPrefix) {
            $fail(__('message.contact_number_missing_country_code'));
            return;
        }

        if (strlen($digits) > self::MAX_E164_DIGITS) {
            $fail(__('message.invalid_contact_number'));
            return;
        }

        // Find the longest calling code that prefixes the number.
        $callingCode = null;
        for ($len = min(4, strlen($digits)); $len >= 1; $len--) {
            if (in_array(substr($digits, 0, $len), self::CALLING_CODES, true)) {
                $callingCode = substr($digits, 0, $len);
                break;
            }
        }

        if ($callingCode === null) {
            $fail(__('message.invalid_country_code'));
            return;
        }

        $nationalLength = strlen($digits) - strlen($callingCode);

        [$min, $max] = self::NATIONAL_LENGTHS[$callingCode]
            ?? [self::FALLBACK_MIN_NATIONAL, self::FALLBACK_MAX_NATIONAL];

        if ($nationalLength < $min || $nationalLength > $max) {
            $fail(__('message.invalid_contact_number'));
        }
    }
}
