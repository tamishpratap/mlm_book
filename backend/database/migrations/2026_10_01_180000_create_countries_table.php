<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('iso2', 2)->nullable();
            $table->string('iso3', 3)->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->string('flag_emoji', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed comprehensive world countries data
        $countries = [
            ['name' => 'Afghanistan', 'iso2' => 'AF', 'iso3' => 'AFG', 'phone_code' => '+93', 'flag_emoji' => '🇦🇫'],
            ['name' => 'Albania', 'iso2' => 'AL', 'iso3' => 'ALB', 'phone_code' => '+355', 'flag_emoji' => '🇦🇱'],
            ['name' => 'Algeria', 'iso2' => 'DZ', 'iso3' => 'DZA', 'phone_code' => '+213', 'flag_emoji' => '🇩🇿'],
            ['name' => 'Andorra', 'iso2' => 'AD', 'iso3' => 'AND', 'phone_code' => '+376', 'flag_emoji' => '🇦🇩'],
            ['name' => 'Angola', 'iso2' => 'AO', 'iso3' => 'AGO', 'phone_code' => '+244', 'flag_emoji' => '🇦🇴'],
            ['name' => 'Argentina', 'iso2' => 'AR', 'iso3' => 'ARG', 'phone_code' => '+54', 'flag_emoji' => '🇦🇷'],
            ['name' => 'Armenia', 'iso2' => 'AM', 'iso3' => 'ARM', 'phone_code' => '+374', 'flag_emoji' => '🇦🇲'],
            ['name' => 'Australia', 'iso2' => 'AU', 'iso3' => 'AUS', 'phone_code' => '+61', 'flag_emoji' => '🇦🇺'],
            ['name' => 'Austria', 'iso2' => 'AT', 'iso3' => 'AUT', 'phone_code' => '+43', 'flag_emoji' => '🇦🇹'],
            ['name' => 'Azerbaijan', 'iso2' => 'AZ', 'iso3' => 'AZE', 'phone_code' => '+994', 'flag_emoji' => '🇦🇿'],
            ['name' => 'Bahamas', 'iso2' => 'BS', 'iso3' => 'BHS', 'phone_code' => '+1242', 'flag_emoji' => '🇧🇸'],
            ['name' => 'Bahrain', 'iso2' => 'BH', 'iso3' => 'BHR', 'phone_code' => '+973', 'flag_emoji' => '🇧🇭'],
            ['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '+880', 'flag_emoji' => '🇧🇩'],
            ['name' => 'Barbados', 'iso2' => 'BB', 'iso3' => 'BRB', 'phone_code' => '+1246', 'flag_emoji' => '🇧🇧'],
            ['name' => 'Belarus', 'iso2' => 'BY', 'iso3' => 'BLR', 'phone_code' => '+375', 'flag_emoji' => '🇧🇾'],
            ['name' => 'Belgium', 'iso2' => 'BE', 'iso3' => 'BEL', 'phone_code' => '+32', 'flag_emoji' => '🇧🇪'],
            ['name' => 'Belize', 'iso2' => 'BZ', 'iso3' => 'BLZ', 'phone_code' => '+501', 'flag_emoji' => '🇧🇿'],
            ['name' => 'Benin', 'iso2' => 'BJ', 'iso3' => 'BEN', 'phone_code' => '+229', 'flag_emoji' => '🇧🇯'],
            ['name' => 'Bhutan', 'iso2' => 'BT', 'iso3' => 'BTN', 'phone_code' => '+975', 'flag_emoji' => '🇧🇹'],
            ['name' => 'Bolivia', 'iso2' => 'BO', 'iso3' => 'BOL', 'phone_code' => '+591', 'flag_emoji' => '🇧🇴'],
            ['name' => 'Bosnia and Herzegovina', 'iso2' => 'BA', 'iso3' => 'BIH', 'phone_code' => '+387', 'flag_emoji' => '🇧🇦'],
            ['name' => 'Botswana', 'iso2' => 'BW', 'iso3' => 'BWA', 'phone_code' => '+267', 'flag_emoji' => '🇧🇼'],
            ['name' => 'Brazil', 'iso2' => 'BR', 'iso3' => 'BRA', 'phone_code' => '+55', 'flag_emoji' => '🇧🇷'],
            ['name' => 'Brunei', 'iso2' => 'BN', 'iso3' => 'BRN', 'phone_code' => '+673', 'flag_emoji' => '🇧🇳'],
            ['name' => 'Bulgaria', 'iso2' => 'BG', 'iso3' => 'BGR', 'phone_code' => '+359', 'flag_emoji' => '🇧🇬'],
            ['name' => 'Burkina Faso', 'iso2' => 'BF', 'iso3' => 'BFA', 'phone_code' => '+226', 'flag_emoji' => '🇧🇫'],
            ['name' => 'Burundi', 'iso2' => 'BI', 'iso3' => 'BDI', 'phone_code' => '+257', 'flag_emoji' => '🇧🇮'],
            ['name' => 'Cambodia', 'iso2' => 'KH', 'iso3' => 'KHM', 'phone_code' => '+855', 'flag_emoji' => '🇰🇭'],
            ['name' => 'Cameroon', 'iso2' => 'CM', 'iso3' => 'CMR', 'phone_code' => '+237', 'flag_emoji' => '🇨🇲'],
            ['name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN', 'phone_code' => '+1', 'flag_emoji' => '🇨🇦'],
            ['name' => 'Chile', 'iso2' => 'CL', 'iso3' => 'CHL', 'phone_code' => '+56', 'flag_emoji' => '🇨🇱'],
            ['name' => 'China', 'iso2' => 'CN', 'iso3' => 'CHN', 'phone_code' => '+86', 'flag_emoji' => '🇨🇳'],
            ['name' => 'Colombia', 'iso2' => 'CO', 'iso3' => 'COL', 'phone_code' => '+57', 'flag_emoji' => '🇨🇴'],
            ['name' => 'Costa Rica', 'iso2' => 'CR', 'iso3' => 'CRI', 'phone_code' => '+506', 'flag_emoji' => '🇨🇷'],
            ['name' => 'Croatia', 'iso2' => 'HR', 'iso3' => 'HRV', 'phone_code' => '+385', 'flag_emoji' => '🇭🇷'],
            ['name' => 'Cuba', 'iso2' => 'CU', 'iso3' => 'CUB', 'phone_code' => '+53', 'flag_emoji' => '🇨🇺'],
            ['name' => 'Cyprus', 'iso2' => 'CY', 'iso3' => 'CYP', 'phone_code' => '+357', 'flag_emoji' => '🇨🇾'],
            ['name' => 'Czech Republic', 'iso2' => 'CZ', 'iso3' => 'CZE', 'phone_code' => '+420', 'flag_emoji' => '🇨🇿'],
            ['name' => 'Denmark', 'iso2' => 'DK', 'iso3' => 'DNK', 'phone_code' => '+45', 'flag_emoji' => '🇩🇰'],
            ['name' => 'Dominican Republic', 'iso2' => 'DO', 'iso3' => 'DOM', 'phone_code' => '+1809', 'flag_emoji' => '🇩🇴'],
            ['name' => 'Ecuador', 'iso2' => 'EC', 'iso3' => 'ECU', 'phone_code' => '+593', 'flag_emoji' => '🇪🇨'],
            ['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '+20', 'flag_emoji' => '🇪🇬'],
            ['name' => 'Estonia', 'iso2' => 'EE', 'iso3' => 'EST', 'phone_code' => '+372', 'flag_emoji' => '🇪🇪'],
            ['name' => 'Ethiopia', 'iso2' => 'ET', 'iso3' => 'ETH', 'phone_code' => '+251', 'flag_emoji' => '🇪🇹'],
            ['name' => 'Fiji', 'iso2' => 'FJ', 'iso3' => 'FJI', 'phone_code' => '+679', 'flag_emoji' => '🇫🇯'],
            ['name' => 'Finland', 'iso2' => 'FI', 'iso3' => 'FIN', 'phone_code' => '+358', 'flag_emoji' => '🇫🇮'],
            ['name' => 'France', 'iso2' => 'FR', 'iso3' => 'FRA', 'phone_code' => '+33', 'flag_emoji' => '🇫🇷'],
            ['name' => 'Georgia', 'iso2' => 'GE', 'iso3' => 'GEO', 'phone_code' => '+995', 'flag_emoji' => '🇬🇪'],
            ['name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU', 'phone_code' => '+49', 'flag_emoji' => '🇩🇪'],
            ['name' => 'Ghana', 'iso2' => 'GH', 'iso3' => 'GHA', 'phone_code' => '+233', 'flag_emoji' => '🇬🇭'],
            ['name' => 'Greece', 'iso2' => 'GR', 'iso3' => 'GRC', 'phone_code' => '+30', 'flag_emoji' => '🇬🇷'],
            ['name' => 'Guatemala', 'iso2' => 'GT', 'iso3' => 'GTM', 'phone_code' => '+502', 'flag_emoji' => '🇬🇹'],
            ['name' => 'Honduras', 'iso2' => 'HN', 'iso3' => 'HND', 'phone_code' => '+504', 'flag_emoji' => '🇭🇳'],
            ['name' => 'Hong Kong', 'iso2' => 'HK', 'iso3' => 'HKG', 'phone_code' => '+852', 'flag_emoji' => '🇭🇰'],
            ['name' => 'Hungary', 'iso2' => 'HU', 'iso3' => 'HUN', 'phone_code' => '+36', 'flag_emoji' => '🇭🇺'],
            ['name' => 'Iceland', 'iso2' => 'IS', 'iso3' => 'ISL', 'phone_code' => '+354', 'flag_emoji' => '🇮🇸'],
            ['name' => 'India', 'iso2' => 'IN', 'iso3' => 'IND', 'phone_code' => '+91', 'flag_emoji' => '🇮🇳'],
            ['name' => 'Indonesia', 'iso2' => 'ID', 'iso3' => 'IDN', 'phone_code' => '+62', 'flag_emoji' => '🇮🇩'],
            ['name' => 'Iran', 'iso2' => 'IR', 'iso3' => 'IRN', 'phone_code' => '+98', 'flag_emoji' => '🇮🇷'],
            ['name' => 'Iraq', 'iso2' => 'IQ', 'iso3' => 'IRQ', 'phone_code' => '+964', 'flag_emoji' => '🇮🇶'],
            ['name' => 'Ireland', 'iso2' => 'IE', 'iso3' => 'IRL', 'phone_code' => '+353', 'flag_emoji' => '🇮🇪'],
            ['name' => 'Israel', 'iso2' => 'IL', 'iso3' => 'ISR', 'phone_code' => '+972', 'flag_emoji' => '🇮🇱'],
            ['name' => 'Italy', 'iso2' => 'IT', 'iso3' => 'ITA', 'phone_code' => '+39', 'flag_emoji' => '🇮🇹'],
            ['name' => 'Jamaica', 'iso2' => 'JM', 'iso3' => 'JAM', 'phone_code' => '+1876', 'flag_emoji' => '🇯🇲'],
            ['name' => 'Japan', 'iso2' => 'JP', 'iso3' => 'JPN', 'phone_code' => '+81', 'flag_emoji' => '🇯🇵'],
            ['name' => 'Jordan', 'iso2' => 'JO', 'iso3' => 'JOR', 'phone_code' => '+962', 'flag_emoji' => '🇯🇴'],
            ['name' => 'Kazakhstan', 'iso2' => 'KZ', 'iso3' => 'KAZ', 'phone_code' => '+7', 'flag_emoji' => '🇰🇿'],
            ['name' => 'Kenya', 'iso2' => 'KE', 'iso3' => 'KEN', 'phone_code' => '+254', 'flag_emoji' => '🇰🇪'],
            ['name' => 'Kuwait', 'iso2' => 'KW', 'iso3' => 'KWT', 'phone_code' => '+965', 'flag_emoji' => '🇰🇼'],
            ['name' => 'Latvia', 'iso2' => 'LV', 'iso3' => 'LVA', 'phone_code' => '+371', 'flag_emoji' => '🇱🇻'],
            ['name' => 'Lebanon', 'iso2' => 'LB', 'iso3' => 'LBN', 'phone_code' => '+961', 'flag_emoji' => '🇱🇧'],
            ['name' => 'Lithuania', 'iso2' => 'LT', 'iso3' => 'LTU', 'phone_code' => '+370', 'flag_emoji' => '🇱🇹'],
            ['name' => 'Luxembourg', 'iso2' => 'LU', 'iso3' => 'LUX', 'phone_code' => '+352', 'flag_emoji' => '🇱🇺'],
            ['name' => 'Malaysia', 'iso2' => 'MY', 'iso3' => 'MYS', 'phone_code' => '+60', 'flag_emoji' => '🇲🇾'],
            ['name' => 'Maldives', 'iso2' => 'MV', 'iso3' => 'MDV', 'phone_code' => '+960', 'flag_emoji' => '🇲🇻'],
            ['name' => 'Malta', 'iso2' => 'MT', 'iso3' => 'MLT', 'phone_code' => '+356', 'flag_emoji' => '🇲🇹'],
            ['name' => 'Mauritius', 'iso2' => 'MU', 'iso3' => 'MUS', 'phone_code' => '+230', 'flag_emoji' => '🇲🇺'],
            ['name' => 'Mexico', 'iso2' => 'MX', 'iso3' => 'MEX', 'phone_code' => '+52', 'flag_emoji' => '🇲🇽'],
            ['name' => 'Monaco', 'iso2' => 'MC', 'iso3' => 'MCO', 'phone_code' => '+377', 'flag_emoji' => '🇲🇨'],
            ['name' => 'Mongolia', 'iso2' => 'MN', 'iso3' => 'MNG', 'phone_code' => '+976', 'flag_emoji' => '🇲🇳'],
            ['name' => 'Morocco', 'iso2' => 'MA', 'iso3' => 'MAR', 'phone_code' => '+212', 'flag_emoji' => '🇲🇦'],
            ['name' => 'Myanmar', 'iso2' => 'MM', 'iso3' => 'MMR', 'phone_code' => '+95', 'flag_emoji' => '🇲🇲'],
            ['name' => 'Nepal', 'iso2' => 'NP', 'iso3' => 'NPL', 'phone_code' => '+977', 'flag_emoji' => '🇳🇵'],
            ['name' => 'Netherlands', 'iso2' => 'NL', 'iso3' => 'NLD', 'phone_code' => '+31', 'flag_emoji' => '🇳🇱'],
            ['name' => 'New Zealand', 'iso2' => 'NZ', 'iso3' => 'NZL', 'phone_code' => '+64', 'flag_emoji' => '🇳🇿'],
            ['name' => 'Nigeria', 'iso2' => 'NG', 'iso3' => 'NGA', 'phone_code' => '+234', 'flag_emoji' => '🇳🇬'],
            ['name' => 'Norway', 'iso2' => 'NO', 'iso3' => 'NOR', 'phone_code' => '+47', 'flag_emoji' => '🇳🇴'],
            ['name' => 'Oman', 'iso2' => 'OM', 'iso3' => 'OMN', 'phone_code' => '+968', 'flag_emoji' => '🇴🇲'],
            ['name' => 'Pakistan', 'iso2' => 'PK', 'iso3' => 'PAK', 'phone_code' => '+92', 'flag_emoji' => '🇵🇰'],
            ['name' => 'Panama', 'iso2' => 'PA', 'iso3' => 'PAN', 'phone_code' => '+507', 'flag_emoji' => '🇵🇦'],
            ['name' => 'Peru', 'iso2' => 'PE', 'iso3' => 'PER', 'phone_code' => '+51', 'flag_emoji' => '🇵🇪'],
            ['name' => 'Philippines', 'iso2' => 'PH', 'iso3' => 'PHL', 'phone_code' => '+63', 'flag_emoji' => '🇵🇭'],
            ['name' => 'Poland', 'iso2' => 'PL', 'iso3' => 'POL', 'phone_code' => '+48', 'flag_emoji' => '🇵🇱'],
            ['name' => 'Portugal', 'iso2' => 'PT', 'iso3' => 'PRT', 'phone_code' => '+351', 'flag_emoji' => '🇵🇹'],
            ['name' => 'Qatar', 'iso2' => 'QA', 'iso3' => 'QAT', 'phone_code' => '+974', 'flag_emoji' => '🇶🇦'],
            ['name' => 'Romania', 'iso2' => 'RO', 'iso3' => 'ROU', 'phone_code' => '+40', 'flag_emoji' => '🇷🇴'],
            ['name' => 'Russia', 'iso2' => 'RU', 'iso3' => 'RUS', 'phone_code' => '+7', 'flag_emoji' => '🇷🇺'],
            ['name' => 'Rwanda', 'iso2' => 'RW', 'iso3' => 'RWA', 'phone_code' => '+250', 'flag_emoji' => '🇷🇼'],
            ['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '+966', 'flag_emoji' => '🇸🇦'],
            ['name' => 'Senegal', 'iso2' => 'SN', 'iso3' => 'SEN', 'phone_code' => '+221', 'flag_emoji' => '🇸🇳'],
            ['name' => 'Serbia', 'iso2' => 'RS', 'iso3' => 'SRB', 'phone_code' => '+381', 'flag_emoji' => '🇷🇸'],
            ['name' => 'Singapore', 'iso2' => 'SG', 'iso3' => 'SGP', 'phone_code' => '+65', 'flag_emoji' => '🇸🇬'],
            ['name' => 'Slovakia', 'iso2' => 'SK', 'iso3' => 'SVK', 'phone_code' => '+421', 'flag_emoji' => '🇸🇰'],
            ['name' => 'Slovenia', 'iso2' => 'SI', 'iso3' => 'SVN', 'phone_code' => '+386', 'flag_emoji' => '🇸🇮'],
            ['name' => 'South Africa', 'iso2' => 'ZA', 'iso3' => 'ZAF', 'phone_code' => '+27', 'flag_emoji' => '🇿🇦'],
            ['name' => 'South Korea', 'iso2' => 'KR', 'iso3' => 'KOR', 'phone_code' => '+82', 'flag_emoji' => '🇰🇷'],
            ['name' => 'Spain', 'iso2' => 'ES', 'iso3' => 'ESP', 'phone_code' => '+34', 'flag_emoji' => '🇪🇸'],
            ['name' => 'Sri Lanka', 'iso2' => 'LK', 'iso3' => 'LKA', 'phone_code' => '+94', 'flag_emoji' => '🇱🇰'],
            ['name' => 'Sweden', 'iso2' => 'SE', 'iso3' => 'SWE', 'phone_code' => '+46', 'flag_emoji' => '🇸🇪'],
            ['name' => 'Switzerland', 'iso2' => 'CH', 'iso3' => 'CHE', 'phone_code' => '+41', 'flag_emoji' => '🇨🇭'],
            ['name' => 'Taiwan', 'iso2' => 'TW', 'iso3' => 'TWN', 'phone_code' => '+886', 'flag_emoji' => '🇹🇼'],
            ['name' => 'Tanzania', 'iso2' => 'TZ', 'iso3' => 'TZA', 'phone_code' => '+255', 'flag_emoji' => '🇹🇿'],
            ['name' => 'Thailand', 'iso2' => 'TH', 'iso3' => 'THA', 'phone_code' => '+66', 'flag_emoji' => '🇹🇭'],
            ['name' => 'Tunisia', 'iso2' => 'TN', 'iso3' => 'TUN', 'phone_code' => '+216', 'flag_emoji' => '🇹🇳'],
            ['name' => 'Turkey', 'iso2' => 'TR', 'iso3' => 'TUR', 'phone_code' => '+90', 'flag_emoji' => '🇹🇷'],
            ['name' => 'Uganda', 'iso2' => 'UG', 'iso3' => 'UGA', 'phone_code' => '+256', 'flag_emoji' => '🇺🇬'],
            ['name' => 'Ukraine', 'iso2' => 'UA', 'iso3' => 'UKR', 'phone_code' => '+380', 'flag_emoji' => '🇺🇦'],
            ['name' => 'United Arab Emirates', 'iso2' => 'AE', 'iso3' => 'ARE', 'phone_code' => '+971', 'flag_emoji' => '🇦🇪'],
            ['name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'phone_code' => '+44', 'flag_emoji' => '🇬🇧'],
            ['name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA', 'phone_code' => '+1', 'flag_emoji' => '🇺🇸'],
            ['name' => 'Uruguay', 'iso2' => 'UY', 'iso3' => 'URY', 'phone_code' => '+598', 'flag_emoji' => '🇺🇾'],
            ['name' => 'Uzbekistan', 'iso2' => 'UZ', 'iso3' => 'UZB', 'phone_code' => '+998', 'flag_emoji' => '🇺🇿'],
            ['name' => 'Venezuela', 'iso2' => 'VE', 'iso3' => 'VEN', 'phone_code' => '+58', 'flag_emoji' => '🇻🇪'],
            ['name' => 'Vietnam', 'iso2' => 'VN', 'iso3' => 'VNM', 'phone_code' => '+84', 'flag_emoji' => '🇻🇳'],
            ['name' => 'Zambia', 'iso2' => 'ZM', 'iso3' => 'ZMB', 'phone_code' => '+260', 'flag_emoji' => '🇿🇲'],
            ['name' => 'Zimbabwe', 'iso2' => 'ZW', 'iso3' => 'ZWE', 'phone_code' => '+263', 'flag_emoji' => '🇿🇼'],
        ];

        $now = now();
        $records = array_map(function ($item) use ($now) {
            return array_merge($item, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $countries);

        DB::table('countries')->insertOrIgnore($records);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
