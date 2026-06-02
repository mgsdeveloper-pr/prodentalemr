<?php

namespace App\Support;

class UsLocationOptions
{
    public static function stateOptions(): array
    {
        return array_combine(array_keys(self::locations()), array_keys(self::locations()));
    }

    public static function cityOptions(?string $state): array
    {
        if (! $state || ! array_key_exists($state, self::locations())) {
            return [];
        }

        $cities = self::locations()[$state];

        $options = [];

        foreach ($cities as $city => $zip) {
            $options[$city] = "{$city} ({$zip})";
        }

        return $options;
    }

    public static function zipFor(?string $state, ?string $city): ?string
    {
        if (! $state || ! $city) {
            return null;
        }

        return self::locations()[$state][$city] ?? null;
    }

    protected static function locations(): array
    {
        return [
            'Alabama' => ['Birmingham' => '35203', 'Huntsville' => '35801', 'Mobile' => '36602', 'Montgomery' => '36104', 'Tuscaloosa' => '35401'],
            'Alaska' => ['Anchorage' => '99501', 'Fairbanks' => '99701', 'Juneau' => '99801', 'Sitka' => '99835'],
            'District of Columbia' => ['Washington' => '20001'],
            'Arizona' => ['Flagstaff' => '86001', 'Mesa' => '85201', 'Phoenix' => '85001', 'Scottsdale' => '85251', 'Tempe' => '85281', 'Tucson' => '85701'],
            'Arkansas' => ['Bentonville' => '72712', 'Fayetteville' => '72701', 'Fort Smith' => '72901', 'Little Rock' => '72201'],
            'California' => ['Anaheim' => '92805', 'Fresno' => '93721', 'Los Angeles' => '90001', 'Oakland' => '94607', 'Sacramento' => '94203', 'San Diego' => '92101', 'San Francisco' => '94102', 'San Jose' => '95113'],
            'Colorado' => ['Aurora' => '80012', 'Boulder' => '80302', 'Colorado Springs' => '80903', 'Denver' => '80202', 'Fort Collins' => '80521'],
            'Connecticut' => ['Bridgeport' => '06604', 'Hartford' => '06103', 'New Haven' => '06510', 'Stamford' => '06901'],
            'Delaware' => ['Dover' => '19901', 'Middletown' => '19709', 'Newark' => '19711', 'Wilmington' => '19801'],
            'Florida' => ['Fort Lauderdale' => '33301', 'Jacksonville' => '32202', 'Miami' => '33101', 'Orlando' => '32801', 'Tallahassee' => '32301', 'Tampa' => '33602'],
            'Georgia' => ['Athens' => '30601', 'Atlanta' => '30301', 'Augusta' => '30901', 'Macon' => '31201', 'Savannah' => '31401'],
            'Hawaii' => ['Honolulu' => '96813'],
            'Idaho' => ['Boise' => '83702', 'Coeur d\'Alene' => '83814', 'Idaho Falls' => '83402', 'Meridian' => '83642'],
            'Illinois' => ['Aurora' => '60505', 'Chicago' => '60601', 'Naperville' => '60540', 'Peoria' => '61602', 'Rockford' => '61101', 'Springfield' => '62701'],
            'Indiana' => ['Bloomington' => '47404', 'Evansville' => '47708', 'Fort Wayne' => '46802', 'Indianapolis' => '46204', 'South Bend' => '46601'],
            'Iowa' => ['Cedar Rapids' => '52401', 'Davenport' => '52801', 'Des Moines' => '50309', 'Iowa City' => '52240'],
            'Kansas' => ['Kansas City' => '66101', 'Lawrence' => '66044', 'Olathe' => '66061', 'Topeka' => '66603', 'Wichita' => '67202'],
            'Kentucky' => ['Bowling Green' => '42101', 'Frankfort' => '40601', 'Lexington' => '40507', 'Louisville' => '40202'],
            'Louisiana' => ['Baton Rouge' => '70802', 'Lafayette' => '70501', 'Lake Charles' => '70601', 'New Orleans' => '70112', 'Shreveport' => '71101'],
            'Maine' => ['Augusta' => '04330', 'Bangor' => '04401', 'Portland' => '04101'],
            'Maryland' => ['Annapolis' => '21401', 'Baltimore' => '21201', 'Bethesda' => '20814', 'Frederick' => '21701', 'Rockville' => '20850'],
            'Massachusetts' => ['Boston' => '02108', 'Cambridge' => '02139', 'Lowell' => '01852', 'Springfield' => '01103', 'Worcester' => '01608'],
            'Michigan' => ['Ann Arbor' => '48104', 'Detroit' => '48201', 'Grand Rapids' => '49503', 'Lansing' => '48933', 'Traverse City' => '49684'],
            'Minnesota' => ['Duluth' => '55802', 'Minneapolis' => '55401', 'Rochester' => '55902', 'Saint Paul' => '55101'],
            'Mississippi' => ['Jackson' => '39201'],
            'Missouri' => ['Columbia' => '65201', 'Jefferson City' => '65101', 'Kansas City' => '64106', 'Springfield' => '65806', 'St. Louis' => '63101'],
            'Montana' => ['Billings' => '59101', 'Bozeman' => '59715', 'Helena' => '59601', 'Missoula' => '59802'],
            'Nebraska' => ['Grand Island' => '68801', 'Lincoln' => '68508', 'Omaha' => '68102'],
            'Nevada' => ['Carson City' => '89701', 'Henderson' => '89012', 'Las Vegas' => '88901', 'Reno' => '89501'],
            'New Hampshire' => ['Concord' => '03301', 'Manchester' => '03101', 'Nashua' => '03060', 'Portsmouth' => '03801'],
            'New Jersey' => ['Atlantic City' => '08401', 'Jersey City' => '07302', 'Newark' => '07102', 'Princeton' => '08540', 'Trenton' => '08608'],
            'New Mexico' => ['Albuquerque' => '87101', 'Las Cruces' => '88001', 'Rio Rancho' => '87124', 'Santa Fe' => '87501'],
            'New York' => ['Albany' => '12207', 'Buffalo' => '14202', 'New York City' => '10001', 'Rochester' => '14604', 'Syracuse' => '13202', 'Yonkers' => '10701'],
            'North Carolina' => ['Asheville' => '28801', 'Charlotte' => '28202', 'Durham' => '27701', 'Greensboro' => '27401', 'Raleigh' => '27601', 'Wilmington' => '28401'],
            'North Dakota' => ['Bismarck' => '58501', 'Fargo' => '58102', 'Grand Forks' => '58201'],
            'Ohio' => ['Akron' => '44308', 'Cincinnati' => '45202', 'Cleveland' => '44113', 'Columbus' => '43215', 'Dayton' => '45402', 'Toledo' => '43604'],
            'Oklahoma' => ['Norman' => '73069', 'Oklahoma City' => '73102', 'Stillwater' => '74074', 'Tulsa' => '74103'],
            'Oregon' => ['Bend' => '97701', 'Eugene' => '97401', 'Portland' => '97204', 'Salem' => '97301'],
            'Pennsylvania' => ['Allentown' => '18101', 'Erie' => '16501', 'Harrisburg' => '17101', 'Philadelphia' => '19102', 'Pittsburgh' => '15222', 'Scranton' => '18503'],
            'Rhode Island' => ['Providence' => '02903'],
            'South Carolina' => ['Charleston' => '29401', 'Columbia' => '29201', 'Greenville' => '29601', 'Myrtle Beach' => '29577'],
            'South Dakota' => ['Pierre' => '57501', 'Rapid City' => '57701', 'Sioux Falls' => '57104'],
            'Tennessee' => ['Chattanooga' => '37402', 'Knoxville' => '37902', 'Memphis' => '38103', 'Nashville' => '37219'],
            'Texas' => ['Austin' => '78701', 'Dallas' => '75201', 'El Paso' => '79901', 'Fort Worth' => '76102', 'Houston' => '77002', 'San Antonio' => '78205'],
            'Utah' => ['Ogden' => '84401', 'Provo' => '84601', 'Salt Lake City' => '84111', 'St. George' => '84770'],
            'Vermont' => ['Burlington' => '05401', 'Montpelier' => '05602', 'Rutland' => '05701'],
            'Virginia' => ['Alexandria' => '22314', 'Arlington' => '22201', 'Norfolk' => '23510', 'Richmond' => '23219', 'Virginia Beach' => '23451'],
            'Washington' => ['Olympia' => '98501', 'Seattle' => '98101', 'Spokane' => '99201', 'Tacoma' => '98402', 'Vancouver' => '98660'],
            'West Virginia' => ['Charleston' => '25301'],
            'Wisconsin' => ['Green Bay' => '54301', 'Madison' => '53703', 'Milwaukee' => '53202', 'Waukesha' => '53186'],
            'Wyoming' => ['Casper' => '82601', 'Cheyenne' => '82001', 'Laramie' => '82070'],
        ];
    }
}
