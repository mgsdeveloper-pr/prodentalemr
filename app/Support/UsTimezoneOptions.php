<?php

namespace App\Support;

class UsTimezoneOptions
{
    public static function options(): array
    {
        return [
            'America/New_York' => 'Eastern Time (America/New_York)',
            'America/Chicago' => 'Central Time (America/Chicago)',
            'America/Denver' => 'Mountain Time (America/Denver)',
            'America/Phoenix' => 'Arizona Time (America/Phoenix)',
            'America/Los_Angeles' => 'Pacific Time (America/Los_Angeles)',
            'America/Anchorage' => 'Alaska Time (America/Anchorage)',
            'Pacific/Honolulu' => 'Hawaii Time (Pacific/Honolulu)',
        ];
    }

    public static function timezoneForState(?string $state): ?string
    {
        if (! $state) {
            return null;
        }

        return match ($state) {
            'Connecticut',
            'Delaware',
            'District of Columbia',
            'Florida',
            'Georgia',
            'Indiana',
            'Kentucky',
            'Maine',
            'Maryland',
            'Massachusetts',
            'Michigan',
            'New Hampshire',
            'New Jersey',
            'New York',
            'North Carolina',
            'Ohio',
            'Pennsylvania',
            'Rhode Island',
            'South Carolina',
            'Tennessee',
            'Vermont',
            'Virginia',
            'West Virginia' => 'America/New_York',

            'Alabama',
            'Arkansas',
            'Illinois',
            'Iowa',
            'Kansas',
            'Louisiana',
            'Minnesota',
            'Mississippi',
            'Missouri',
            'Nebraska',
            'North Dakota',
            'Oklahoma',
            'South Dakota',
            'Texas',
            'Wisconsin' => 'America/Chicago',

            'Arizona' => 'America/Phoenix',

            'Colorado',
            'Idaho',
            'Montana',
            'New Mexico',
            'Utah',
            'Wyoming' => 'America/Denver',

            'California',
            'Nevada',
            'Oregon',
            'Washington' => 'America/Los_Angeles',

            'Alaska' => 'America/Anchorage',
            'Hawaii' => 'Pacific/Honolulu',
            default => 'America/New_York',
        };
    }
}
