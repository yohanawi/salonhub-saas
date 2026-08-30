<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center p-5 mb-5">
            <span class="fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-flex flex-column p-5 mb-5">
            <span class="fw-bold mb-2">Please fix the following:</span>
            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!--begin::Basic info-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse"
            data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
            <!--begin::Card title-->
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">Profile Details</h3>
            </div>
            <!--end::Card title-->
        </div>
        <!--begin::Card header-->
        <!--begin::Content-->
        <div id="kt_account_settings_profile_details" class="collapse show">
            <!--begin::Form-->
            <form id="kt_account_profile_details_form" class="form" method="POST"
                action="{{ route('profile.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <!--begin::Card body-->
                <div class="card-body border-top p-9">
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Avatar</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                            <!--begin::Image input-->
                            <div class="image-input image-input-outline" data-kt-image-input="true"
                                style="background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}')">
                                <!--begin::Preview existing avatar-->
                                <div class="image-input-wrapper w-125px h-125px"
                                    style="background-image: url('{{ $user->profile_photo_url ?: asset('assets/media/avatars/blank.png') }}')">
                                </div>
                                <!--end::Preview existing avatar-->
                                <!--begin::Label-->
                                <label
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <!--begin::Inputs-->
                                    <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="avatar_remove" />
                                    <!--end::Inputs-->
                                </label>
                                <!--end::Label-->
                                <!--begin::Cancel-->
                                <span
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <!--end::Cancel-->
                                <!--begin::Remove-->
                                <span
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <!--end::Remove-->
                            </div>
                            <!--end::Image input-->
                            <!--begin::Hint-->
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                            <!--end::Hint-->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Full Name</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                            <!--begin::Row-->
                            <div class="row">
                                <!--begin::Col-->
                                <div class="col-lg-6 fv-row">
                                    <input type="text" name="fname"
                                        class="form-control form-control-lg form-control-solid mb-3 mb-lg-0"
                                        placeholder="First name" value="{{ old('fname', $user->first_name) }}" />
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col-lg-6 fv-row">
                                    <input type="text" name="lname"
                                        class="form-control form-control-lg form-control-solid" placeholder="Last name"
                                        value="{{ old('lname', $user->last_name) }}" />
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Company</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="company" class="form-control form-control-lg form-control-solid"
                                placeholder="Company name" value="{{ old('company', $tenant?->name) }}" />
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">
                            <span class="required">Contact Phone</span>
                            <span class="ms-1" data-bs-toggle="tooltip" title="Phone number must be active">
                                <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                        </label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <input type="tel" name="phone" class="form-control form-control-lg form-control-solid"
                                placeholder="Phone number" value="{{ old('phone', $user->phone) }}" />
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Company Site</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="website" class="form-control form-control-lg form-control-solid"
                                placeholder="Company website" value="{{ old('website', $tenant?->website) }}" />
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">
                            <span class="required">Country</span>
                            <span class="ms-1" data-bs-toggle="tooltip" title="Country of origination">
                                <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                        </label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <select name="country" aria-label="Select a Country" data-control="select2"
                                data-placeholder="Select a country..."
                                class="form-select form-select-solid form-select-lg fw-semibold">
                                <option value="">Select a Country...</option>
                                @foreach ([
                                    'AF' => 'Afghanistan', 'AX' => 'Aland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria',
                                    'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla',
                                    'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba',
                                    'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas',
                                    'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus',
                                    'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda',
                                    'BT' => 'Bhutan', 'BO' => 'Bolivia, Plurinational State of',
                                    'BQ' => 'Bonaire, Sint Eustatius and Saba', 'BA' => 'Bosnia and Herzegovina',
                                    'BW' => 'Botswana', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory',
                                    'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso',
                                    'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada',
                                    'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic',
                                    'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island',
                                    'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros',
                                    'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => "Côte d'Ivoire",
                                    'HR' => 'Croatia', 'CU' => 'Cuba', 'CW' => 'Curaçao', 'CZ' => 'Czech Republic',
                                    'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica',
                                    'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt',
                                    'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea',
                                    'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)',
                                    'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'PF' => 'French Polynesia',
                                    'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany',
                                    'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland',
                                    'GD' => 'Grenada', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey',
                                    'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'HT' => 'Haiti',
                                    'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong',
                                    'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia',
                                    'IR' => 'Iran, Islamic Republic of', 'IQ' => 'Iraq', 'IE' => 'Ireland',
                                    'IM' => 'Isle of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica',
                                    'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan',
                                    'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => "Korea, Democratic People's Republic of",
                                    'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => "Lao People's Democratic Republic",
                                    'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia',
                                    'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
                                    'MO' => 'Macao', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia',
                                    'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands',
                                    'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'MX' => 'Mexico',
                                    'FM' => 'Micronesia, Federated States of', 'MD' => 'Moldova, Republic of',
                                    'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat',
                                    'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia',
                                    'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NZ' => 'New Zealand',
                                    'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue',
                                    'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway',
                                    'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau',
                                    'PS' => 'Palestinian Territory, Occupied', 'PA' => 'Panama',
                                    'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru',
                                    'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico',
                                    'QA' => 'Qatar', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda',
                                    'BL' => 'Saint Barthélemy', 'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia',
                                    'MF' => 'Saint Martin (French part)', 'VC' => 'Saint Vincent and the Grenadines',
                                    'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe',
                                    'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles',
                                    'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SX' => 'Sint Maarten (Dutch part)',
                                    'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands',
                                    'SO' => 'Somalia', 'ZA' => 'South Africa', 'KR' => 'South Korea',
                                    'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan',
                                    'SR' => 'Suriname', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland',
                                    'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan, Province of China',
                                    'TJ' => 'Tajikistan', 'TZ' => 'Tanzania, United Republic of', 'TH' => 'Thailand',
                                    'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga',
                                    'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey',
                                    'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu',
                                    'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates',
                                    'GB' => 'United Kingdom', 'US' => 'United States', 'UY' => 'Uruguay',
                                    'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela, Bolivarian Republic of',
                                    'VN' => 'Vietnam', 'VI' => 'Virgin Islands', 'YE' => 'Yemen', 'ZM' => 'Zambia',
                                    'ZW' => 'Zimbabwe',
                                ] as $countryCode => $countryName)
                                    <option value="{{ $countryCode }}"
                                        @selected(trim((string) ($tenant->country ?? '')) === $countryName)>
                                        {{ $countryName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Language</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <!--begin::Input-->
                            <select name="language" aria-label="Select a Language" data-control="select2"
                                data-placeholder="Select a language..."
                                class="form-select form-select-solid form-select-lg">
                                <option value="">Select a Language...</option>
                                @foreach ([
                                    'id' => 'Bahasa Indonesia - Indonesian',
                                    'msa' => 'Bahasa Melayu - Malay',
                                    'ca' => 'Català - Catalan',
                                    'cs' => 'Čeština - Czech',
                                    'da' => 'Dansk - Danish',
                                    'de' => 'Deutsch - German',
                                    'en' => 'English',
                                    'en-gb' => 'English UK - British English',
                                    'es' => 'Español - Spanish',
                                    'fil' => 'Filipino',
                                    'fr' => 'Français - French',
                                    'ga' => 'Gaeilge - Irish (beta)',
                                    'gl' => 'Galego - Galician (beta)',
                                    'hr' => 'Hrvatski - Croatian',
                                    'it' => 'Italiano - Italian',
                                    'hu' => 'Magyar - Hungarian',
                                    'nl' => 'Nederlands - Dutch',
                                    'no' => 'Norsk - Norwegian',
                                    'pl' => 'Polski - Polish',
                                    'pt' => 'Português - Portuguese',
                                    'ro' => 'Română - Romanian',
                                    'sk' => 'Slovenčina - Slovak',
                                    'fi' => 'Suomi - Finnish',
                                    'sv' => 'Svenska - Swedish',
                                    'vi' => 'Tiếng Việt - Vietnamese',
                                    'tr' => 'Türkçe - Turkish',
                                    'el' => 'Ελληνικά - Greek',
                                    'bg' => 'Български език - Bulgarian',
                                    'ru' => 'Русский - Russian',
                                    'sr' => 'Српски - Serbian',
                                    'uk' => 'Українська мова - Ukrainian',
                                    'he' => 'עִבְרִית - Hebrew',
                                    'ur' => 'اردو - Urdu (beta)',
                                    'ar' => 'العربية - Arabic',
                                    'fa' => 'فارسی - Persian',
                                    'mr' => 'मराठी - Marathi',
                                    'hi' => 'हिन्दी - Hindi',
                                    'bn' => 'বাংলা - Bangla',
                                    'gu' => 'ગુજરાતી - Gujarati',
                                    'ta' => 'தமிழ் - Tamil',
                                    'kn' => 'ಕನ್ನಡ - Kannada',
                                    'th' => 'ภาษาไทย - Thai',
                                    'ko' => '한국어 - Korean',
                                    'ja' => '日本語 - Japanese',
                                    'zh-cn' => '简体中文 - Simplified Chinese',
                                    'zh-tw' => '繁體中文 - Traditional Chinese',
                                ] as $langCode => $langLabel)
                                    <option value="{{ $langCode }}" @selected(($language ?? null) === $langCode)>
                                        {{ $langLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <!--end::Input-->
                            <!--begin::Hint-->
                            <div class="form-text">Please select a preferred language, including date, time, and number
                                formatting.</div>
                            <!--end::Hint-->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Time Zone</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <select name="timezone" aria-label="Select a Timezone" data-control="select2"
                                data-placeholder="Select a timezone.."
                                class="form-select form-select-solid form-select-lg">
                                <option value="">Select a Timezone..</option>
                                @foreach ([
                                    'Etc/GMT+12' => ['-39600', 'International Date Line West'],
                                    'Pacific/Midway' => ['-39600', 'Midway Island'],
                                    'Pacific/Pago_Pago' => ['-39600', 'Samoa'],
                                    'Pacific/Honolulu' => ['-36000', 'Hawaii'],
                                    'America/Juneau' => ['-28800', 'Alaska'],
                                    'America/Los_Angeles' => ['-25200', 'Pacific Time (US & Canada)'],
                                    'America/Tijuana' => ['-25200', 'Tijuana'],
                                    'America/Phoenix' => ['-25200', 'Arizona'],
                                    'America/Denver' => ['-21600', 'Mountain Time (US & Canada)'],
                                    'America/Chihuahua' => ['-21600', 'Chihuahua'],
                                    'America/Mazatlan' => ['-21600', 'Mazatlan'],
                                    'America/Regina' => ['-21600', 'Saskatchewan'],
                                    'America/Guatemala' => ['-21600', 'Central America'],
                                    'America/Chicago' => ['-18000', 'Central Time (US & Canada)'],
                                    'America/Mexico_City' => ['-18000', 'Guadalajara'],
                                    'America/Monterrey' => ['-18000', 'Monterrey'],
                                    'America/Bogota' => ['-18000', 'Bogota'],
                                    'America/Lima' => ['-18000', 'Lima'],
                                    'America/New_York' => ['-14400', 'Eastern Time (US & Canada)'],
                                    'America/Indiana/Indianapolis' => ['-14400', 'Indiana (East)'],
                                    'America/Caracas' => ['-14400', 'Caracas'],
                                    'America/La_Paz' => ['-14400', 'La Paz'],
                                    'America/Guyana' => ['-14400', 'Georgetown'],
                                    'America/Halifax' => ['-10800', 'Atlantic Time (Canada)'],
                                    'America/Santiago' => ['-10800', 'Santiago'],
                                    'America/Sao_Paulo' => ['-10800', 'Brasilia'],
                                    'America/Argentina/Buenos_Aires' => ['-10800', 'Buenos Aires'],
                                    'America/St_Johns' => ['-9000', 'Newfoundland'],
                                    'America/Nuuk' => ['-7200', 'Greenland'],
                                    'Atlantic/South_Georgia' => ['-7200', 'Mid-Atlantic'],
                                    'Atlantic/Cape_Verde' => ['-3600', 'Cape Verde Is.'],
                                    'Atlantic/Azores' => ['0', 'Azores'],
                                    'Africa/Monrovia' => ['0', 'Monrovia'],
                                    'UTC' => ['0', 'UTC'],
                                    'Europe/Dublin' => ['3600', 'Dublin'],
                                    'Europe/London' => ['3600', 'Edinburgh'],
                                    'Europe/Lisbon' => ['3600', 'Lisbon'],
                                    'Africa/Casablanca' => ['3600', 'Casablanca'],
                                    'Africa/Algiers' => ['3600', 'West Central Africa'],
                                    'Europe/Belgrade' => ['7200', 'Belgrade'],
                                    'Europe/Bratislava' => ['7200', 'Bratislava'],
                                    'Europe/Budapest' => ['7200', 'Budapest'],
                                    'Europe/Ljubljana' => ['7200', 'Ljubljana'],
                                    'Europe/Prague' => ['7200', 'Prague'],
                                    'Europe/Sarajevo' => ['7200', 'Sarajevo'],
                                    'Europe/Skopje' => ['7200', 'Skopje'],
                                    'Europe/Warsaw' => ['7200', 'Warsaw'],
                                    'Europe/Zagreb' => ['7200', 'Zagreb'],
                                    'Europe/Brussels' => ['7200', 'Brussels'],
                                    'Europe/Copenhagen' => ['7200', 'Copenhagen'],
                                    'Europe/Madrid' => ['7200', 'Madrid'],
                                    'Europe/Paris' => ['7200', 'Paris'],
                                    'Europe/Amsterdam' => ['7200', 'Amsterdam'],
                                    'Europe/Berlin' => ['7200', 'Berlin'],
                                    'Europe/Zurich' => ['7200', 'Bern'],
                                    'Europe/Rome' => ['7200', 'Rome'],
                                    'Europe/Stockholm' => ['7200', 'Stockholm'],
                                    'Europe/Vienna' => ['7200', 'Vienna'],
                                    'Africa/Cairo' => ['7200', 'Cairo'],
                                    'Africa/Harare' => ['7200', 'Harare'],
                                    'Africa/Johannesburg' => ['7200', 'Pretoria'],
                                    'Europe/Bucharest' => ['10800', 'Bucharest'],
                                    'Europe/Helsinki' => ['10800', 'Helsinki'],
                                    'Europe/Kyiv' => ['10800', 'Kiev'],
                                    'Europe/Riga' => ['10800', 'Riga'],
                                    'Europe/Sofia' => ['10800', 'Sofia'],
                                    'Europe/Tallinn' => ['10800', 'Tallinn'],
                                    'Europe/Vilnius' => ['10800', 'Vilnius'],
                                    'Europe/Athens' => ['10800', 'Athens'],
                                    'Europe/Istanbul' => ['10800', 'Istanbul'],
                                    'Europe/Minsk' => ['10800', 'Minsk'],
                                    'Asia/Jerusalem' => ['10800', 'Jerusalem'],
                                    'Europe/Moscow' => ['10800', 'Moscow'],
                                    'Europe/Volgograd' => ['10800', 'Volgograd'],
                                    'Asia/Kuwait' => ['10800', 'Kuwait'],
                                    'Asia/Riyadh' => ['10800', 'Riyadh'],
                                    'Africa/Nairobi' => ['10800', 'Nairobi'],
                                    'Asia/Baghdad' => ['10800', 'Baghdad'],
                                    'Asia/Muscat' => ['14400', 'Abu Dhabi'],
                                    'Asia/Baku' => ['14400', 'Baku'],
                                    'Asia/Tbilisi' => ['14400', 'Tbilisi'],
                                    'Asia/Yerevan' => ['14400', 'Yerevan'],
                                    'Asia/Tehran' => ['16200', 'Tehran'],
                                    'Asia/Kabul' => ['16200', 'Kabul'],
                                    'Asia/Yekaterinburg' => ['18000', 'Ekaterinburg'],
                                    'Asia/Karachi' => ['18000', 'Islamabad'],
                                    'Asia/Tashkent' => ['18000', 'Tashkent'],
                                    'Asia/Kolkata' => ['19800', 'Chennai'],
                                    'Asia/Colombo' => ['19800', 'Sri Jayawardenepura'],
                                    'Asia/Kathmandu' => ['20700', 'Kathmandu'],
                                    'Asia/Almaty' => ['21600', 'Astana'],
                                    'Asia/Dhaka' => ['21600', 'Dhaka'],
                                    'Asia/Urumqi' => ['21600', 'Urumqi'],
                                    'Asia/Yangon' => ['23400', 'Rangoon'],
                                    'Asia/Novosibirsk' => ['25200', 'Novosibirsk'],
                                    'Asia/Bangkok' => ['25200', 'Bangkok'],
                                    'Asia/Jakarta' => ['25200', 'Jakarta'],
                                    'Asia/Krasnoyarsk' => ['25200', 'Krasnoyarsk'],
                                    'Asia/Shanghai' => ['28800', 'Beijing'],
                                    'Asia/Chongqing' => ['28800', 'Chongqing'],
                                    'Asia/Hong_Kong' => ['28800', 'Hong Kong'],
                                    'Asia/Kuala_Lumpur' => ['28800', 'Kuala Lumpur'],
                                    'Asia/Singapore' => ['28800', 'Singapore'],
                                    'Asia/Taipei' => ['28800', 'Taipei'],
                                    'Australia/Perth' => ['28800', 'Perth'],
                                    'Asia/Irkutsk' => ['28800', 'Irkutsk'],
                                    'Asia/Ulaanbaatar' => ['28800', 'Ulaan Bataar'],
                                    'Asia/Seoul' => ['32400', 'Seoul'],
                                    'Asia/Tokyo' => ['32400', 'Tokyo'],
                                    'Asia/Yakutsk' => ['32400', 'Yakutsk'],
                                    'Australia/Darwin' => ['34200', 'Darwin'],
                                    'Australia/Adelaide' => ['34200', 'Adelaide'],
                                    'Australia/Canberra' => ['36000', 'Canberra'],
                                    'Australia/Melbourne' => ['36000', 'Melbourne'],
                                    'Australia/Sydney' => ['36000', 'Sydney'],
                                    'Australia/Brisbane' => ['36000', 'Brisbane'],
                                    'Australia/Hobart' => ['36000', 'Hobart'],
                                    'Asia/Vladivostok' => ['36000', 'Vladivostok'],
                                    'Pacific/Guam' => ['36000', 'Guam'],
                                    'Pacific/Port_Moresby' => ['36000', 'Port Moresby'],
                                    'Pacific/Guadalcanal' => ['36000', 'Solomon Is.'],
                                    'Asia/Magadan' => ['39600', 'Magadan'],
                                    'Pacific/Noumea' => ['39600', 'New Caledonia'],
                                    'Pacific/Fiji' => ['43200', 'Fiji'],
                                    'Asia/Kamchatka' => ['43200', 'Kamchatka'],
                                    'Pacific/Majuro' => ['43200', 'Marshall Is.'],
                                    'Pacific/Auckland' => ['43200', 'Auckland'],
                                    'Pacific/Tongatapu' => ['46800', "Nuku'alofa"],
                                ] as $tzId => $tzMeta)
                                    <option data-bs-offset="{{ $tzMeta[0] }}" value="{{ $tzId }}"
                                        @selected(($tenant->timezone ?? null) === $tzId)>
                                        @php $tzOffset = (int) $tzMeta[0]; @endphp
                                        (GMT{{ $tzOffset === 0 ? '' : (($tzOffset > 0 ? '+' : '-') . gmdate('H:i', abs($tzOffset))) }})
                                        {{ $tzMeta[1] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Currency</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <select name="currency" aria-label="Select a Currency" data-control="select2"
                                data-placeholder="Select a currency.."
                                class="form-select form-select-solid form-select-lg">
                                <option value="">Select a currency..</option>
                                <option data-kt-flag="flags/united-states.svg" value="USD"
                                    @selected(($tenant->currency ?? null) === 'USD')>
                                    <b>USD</b>&nbsp;-&nbsp;USA dollar
                                </option>
                                <option data-kt-flag="flags/united-kingdom.svg" value="GBP"
                                    @selected(($tenant->currency ?? null) === 'GBP')>
                                    <b>GBP</b>&nbsp;-&nbsp;British pound
                                </option>
                                <option data-kt-flag="flags/australia.svg" value="AUD"
                                    @selected(($tenant->currency ?? null) === 'AUD')>
                                    <b>AUD</b>&nbsp;-&nbsp;Australian dollar
                                </option>
                                <option data-kt-flag="flags/japan.svg" value="JPY"
                                    @selected(($tenant->currency ?? null) === 'JPY')>
                                    <b>JPY</b>&nbsp;-&nbsp;Japanese yen
                                </option>
                                <option data-kt-flag="flags/sweden.svg" value="SEK"
                                    @selected(($tenant->currency ?? null) === 'SEK')>
                                    <b>SEK</b>&nbsp;-&nbsp;Swedish krona
                                </option>
                                <option data-kt-flag="flags/canada.svg" value="CAD"
                                    @selected(($tenant->currency ?? null) === 'CAD')>
                                    <b>CAD</b>&nbsp;-&nbsp;Canadian dollar
                                </option>
                                <option data-kt-flag="flags/switzerland.svg" value="CHF"
                                    @selected(($tenant->currency ?? null) === 'CHF')>
                                    <b>CHF</b>&nbsp;-&nbsp;Swiss franc
                                </option>
                            </select>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Communication</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <!--begin::Options-->
                            <div class="d-flex align-items-center mt-3">
                                <!--begin::Option-->
                                <label class="form-check form-check-custom form-check-inline form-check-solid me-5">
                                    <input class="form-check-input" name="communication[]" type="checkbox"
                                        value="1" @checked(in_array('1', $communicationChannels ?? [])) />
                                    <span class="fw-semibold ps-2 fs-6">Email</span>
                                </label>
                                <!--end::Option-->
                                <!--begin::Option-->
                                <label class="form-check form-check-custom form-check-inline form-check-solid">
                                    <input class="form-check-input" name="communication[]" type="checkbox"
                                        value="2" @checked(in_array('2', $communicationChannels ?? [])) />
                                    <span class="fw-semibold ps-2 fs-6">Phone</span>
                                </label>
                                <!--end::Option-->
                            </div>
                            <!--end::Options-->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-0">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Allow Marketing</label>
                        <!--begin::Label-->
                        <!--begin::Label-->
                        <div class="col-lg-8 d-flex align-items-center">
                            <div class="form-check form-check-solid form-switch form-check-custom fv-row">
                                <input class="form-check-input w-45px h-30px" type="checkbox" id="allowmarketing"
                                    name="allow_marketing" value="1" @checked($marketingOptIn) />
                                <label class="form-check-label" for="allowmarketing"></label>
                            </div>
                        </div>
                        <!--begin::Label-->
                    </div>
                    <!--end::Input group-->
                </div>
                <!--end::Card body-->
                <!--begin::Actions-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-light btn-active-light-primary me-2">Discard</button>
                    <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">Save
                        Changes</button>
                </div>
                <!--end::Actions-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Basic info-->
    <!--begin::Sign-in Method-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse"
            data-bs-target="#kt_account_signin_method">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">Sign-in Method</h3>
            </div>
        </div>
        <!--end::Card header-->
        <!--begin::Content-->
        <div id="kt_account_settings_signin_method" class="collapse show">
            <!--begin::Card body-->
            <div class="card-body border-top p-9">
                <!--begin::Email Address-->
                <div class="d-flex flex-wrap align-items-center">
                    <!--begin::Label-->
                    <div id="kt_signin_email">
                        <div class="fs-6 fw-bold mb-1">Email Address</div>
                        <div class="fw-semibold text-gray-600">{{ $user->email }}</div>
                    </div>
                    <!--end::Label-->
                    <!--begin::Edit-->
                    <div id="kt_signin_email_edit" class="flex-row-fluid d-none">
                        <!--begin::Form-->
                        <form id="kt_signin_change_email" class="form" method="POST"
                            action="{{ route('profile.settings.email') }}" novalidate="novalidate">
                            @csrf
                            @method('PATCH')
                            <div class="row mb-6">
                                <div class="col-lg-6 mb-4 mb-lg-0">
                                    <div class="fv-row mb-0">
                                        <label for="emailaddress" class="form-label fs-6 fw-bold mb-3">Enter New
                                            Email Address</label>
                                        <input type="email"
                                            class="form-control form-control-lg form-control-solid"
                                            id="emailaddress" placeholder="Email Address" name="emailaddress"
                                            value="{{ old('emailaddress', $user->email) }}" />
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="fv-row mb-0">
                                        <label for="confirmemailpassword"
                                            class="form-label fs-6 fw-bold mb-3">Confirm Password</label>
                                        <input type="password"
                                            class="form-control form-control-lg form-control-solid"
                                            name="confirmemailpassword" id="confirmemailpassword" />
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex">
                                <button id="kt_signin_submit" type="submit"
                                    class="btn btn-primary me-2 px-6">Update Email</button>
                                <button id="kt_signin_cancel" type="button"
                                    class="btn btn-color-gray-500 btn-active-light-primary px-6">Cancel</button>
                            </div>
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Edit-->
                    <!--begin::Action-->
                    <div id="kt_signin_email_button" class="ms-auto">
                        <button class="btn btn-light btn-active-light-primary">Change Email</button>
                    </div>
                    <!--end::Action-->
                </div>
                <!--end::Email Address-->
                <!--begin::Separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::Separator-->
                <!--begin::Password-->
                <div class="d-flex flex-wrap align-items-center mb-10">
                    <!--begin::Label-->
                    <div id="kt_signin_password">
                        <div class="fs-6 fw-bold mb-1">Password</div>
                        <div class="fw-semibold text-gray-600">************</div>
                    </div>
                    <!--end::Label-->
                    <!--begin::Edit-->
                    <div id="kt_signin_password_edit" class="flex-row-fluid d-none">
                        <!--begin::Form-->
                        <form id="kt_signin_change_password" class="form" method="POST"
                            action="{{ route('profile.settings.password') }}" novalidate="novalidate">
                            @csrf
                            @method('PATCH')
                            <div class="row mb-1">
                                <div class="col-lg-4">
                                    <div class="fv-row mb-0">
                                        <label for="currentpassword" class="form-label fs-6 fw-bold mb-3">Current
                                            Password</label>
                                        <input type="password"
                                            class="form-control form-control-lg form-control-solid"
                                            name="currentpassword" id="currentpassword" />
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="fv-row mb-0">
                                        <label for="newpassword" class="form-label fs-6 fw-bold mb-3">New
                                            Password</label>
                                        <input type="password"
                                            class="form-control form-control-lg form-control-solid"
                                            name="newpassword" id="newpassword" />
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="fv-row mb-0">
                                        <label for="confirmpassword" class="form-label fs-6 fw-bold mb-3">Confirm
                                            New Password</label>
                                        <input type="password"
                                            class="form-control form-control-lg form-control-solid"
                                            name="newpassword_confirmation" id="confirmpassword" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mb-5">Password must be at least 8 character and contain symbols
                            </div>
                            <div class="d-flex">
                                <button id="kt_password_submit" type="submit"
                                    class="btn btn-primary me-2 px-6">Update Password</button>
                                <button id="kt_password_cancel" type="button"
                                    class="btn btn-color-gray-500 btn-active-light-primary px-6">Cancel</button>
                            </div>
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Edit-->
                    <!--begin::Action-->
                    <div id="kt_signin_password_button" class="ms-auto">
                        <button class="btn btn-light btn-active-light-primary">Reset Password</button>
                    </div>
                    <!--end::Action-->
                </div>
                <!--end::Password-->
                <!--begin::Notice-->
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                    <!--begin::Icon-->
                    <i class="ki-duotone ki-shield-tick fs-2tx text-primary me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--end::Icon-->
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                        <!--begin::Content-->
                        <div class="mb-3 mb-md-0 fw-semibold">
                            <h4 class="text-gray-900 fw-bold">Secure Your Account</h4>
                            <div class="fs-6 text-gray-700 pe-7">Two-factor authentication adds an extra layer of
                                security to your account. To log in, in addition you'll need to provide a 6 digit code
                            </div>
                        </div>
                        <!--end::Content-->
                        <!--begin::Action-->
                        <a href="#" class="btn btn-primary px-6 align-self-center text-nowrap"
                            data-bs-toggle="modal" data-bs-target="#kt_modal_two_factor_authentication">Enable</a>
                        <!--end::Action-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Notice-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Sign-in Method-->
    <!--begin::Deactivate Account-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse"
            data-bs-target="#kt_account_deactivate" aria-expanded="true" aria-controls="kt_account_deactivate">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">Deactivate Account</h3>
            </div>
        </div>
        <!--end::Card header-->
        <!--begin::Content-->
        <div id="kt_account_settings_deactivate" class="collapse show">
            <!--begin::Form-->
            <form id="kt_account_deactivate_form" class="form" method="POST"
                action="{{ route('profile.settings.deactivate') }}">
                @csrf
                <!--begin::Card body-->
                <div class="card-body border-top p-9">
                    <!--begin::Notice-->
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
                        <!--begin::Icon-->
                        <i class="ki-duotone ki-information fs-2tx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <!--end::Icon-->
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack flex-grow-1">
                            <!--begin::Content-->
                            <div class="fw-semibold">
                                <h4 class="text-gray-900 fw-bold">You Are Deactivating Your Account</h4>
                                <div class="fs-6 text-gray-700">For extra security, this requires you to confirm your
                                    email or phone number when you reset yousignr password.
                                    <br />
                                    <a class="fw-bold" href="#">Learn more</a>
                                </div>
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Notice-->
                    <!--begin::Form input row-->
                    <div class="form-check form-check-solid fv-row">
                        <input name="deactivate" class="form-check-input" type="checkbox" value="1"
                            id="deactivate" />
                        <label class="form-check-label fw-semibold ps-2 fs-6" for="deactivate">I confirm my account
                            deactivation</label>
                    </div>
                    <!--end::Form input row-->
                </div>
                <!--end::Card body-->
                <!--begin::Card footer-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button id="kt_account_deactivate_account_submit" type="submit"
                        class="btn btn-danger fw-semibold">Deactivate Account</button>
                </div>
                <!--end::Card footer-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Deactivate Account-->

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = function (mainId, buttonWrapperId, editId, cancelId) {
                    const mainEl = document.getElementById(mainId);
                    const buttonWrapperEl = document.getElementById(buttonWrapperId);
                    const editEl = document.getElementById(editId);

                    if (!mainEl || !buttonWrapperEl || !editEl) {
                        return;
                    }

                    const show = function () {
                        mainEl.classList.add('d-none');
                        buttonWrapperEl.classList.add('d-none');
                        editEl.classList.remove('d-none');
                    };

                    const hide = function () {
                        mainEl.classList.remove('d-none');
                        buttonWrapperEl.classList.remove('d-none');
                        editEl.classList.add('d-none');
                    };

                    buttonWrapperEl.querySelector('button')?.addEventListener('click', show);
                    document.getElementById(cancelId)?.addEventListener('click', function (e) {
                        e.preventDefault();
                        hide();
                    });
                };

                toggle('kt_signin_email', 'kt_signin_email_button', 'kt_signin_email_edit', 'kt_signin_cancel');
                toggle('kt_signin_password', 'kt_signin_password_button', 'kt_signin_password_edit', 'kt_password_cancel');
            });
        </script>
    @endpush
</x-default-layout>
