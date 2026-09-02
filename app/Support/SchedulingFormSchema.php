<?php

namespace App\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SchedulingFormSchema
{
    public static function hours(string $statePath = 'business_hours', string $title = 'Working Hours'): Section
    {
        $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];

        return Section::make($title)
            ->description('Set the normal availability used by appointment scheduling. Closed days do not offer slots.')
            ->schema(collect($days)->map(
                fn (string $label, string $day): Grid => Grid::make(5)->schema([
                    Toggle::make("{$statePath}.{$day}.open")
                        ->label($label)
                        ->default(! in_array($day, ['saturday', 'sunday'], true))
                        ->afterStateHydrated(function (Toggle $component, mixed $state, mixed $record) use ($day, $statePath): void {
                            if ($state === null || blank(data_get($record, $statePath))) {
                                $component->state(! in_array($day, ['saturday', 'sunday'], true));
                            }
                        })
                        ->live(),
                    TimePicker::make("{$statePath}.{$day}.opens_at")
                        ->label('Opens')
                        ->seconds(false)
                        ->default('09:00')
                        ->afterStateHydrated(function (TimePicker $component, mixed $state): void {
                            if (blank($state)) {
                                $component->state('09:00');
                            }
                        }),
                    TimePicker::make("{$statePath}.{$day}.closes_at")
                        ->label('Closes')
                        ->seconds(false)
                        ->default('17:00')
                        ->afterStateHydrated(function (TimePicker $component, mixed $state): void {
                            if (blank($state)) {
                                $component->state('17:00');
                            }
                        }),
                    TimePicker::make("{$statePath}.{$day}.break_starts_at")
                        ->label('Break starts')
                        ->seconds(false),
                    TimePicker::make("{$statePath}.{$day}.break_ends_at")
                        ->label('Break ends')
                        ->seconds(false),
                ])
            )->all())
            ->collapsible()
            ->collapsed();
    }

    public static function exceptions(string $statePath = 'schedule_exceptions', string $title = 'Closures And Leave'): Section
    {
        return Section::make($title)
            ->description('Block a full day or a specific time window for holidays, leave, meetings, or closures.')
            ->schema([
                Repeater::make($statePath)
                    ->label('Schedule exceptions')
                    ->schema([
                        Grid::make(4)->schema([
                            DatePicker::make('date')->required(),
                            Toggle::make('all_day')->label('All day')->default(true),
                            TimePicker::make('starts_at')->label('Starts')->seconds(false),
                            TimePicker::make('ends_at')->label('Ends')->seconds(false),
                            TextInput::make('reason')->label('Reason')->maxLength(160)->columnSpanFull(),
                        ]),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Add closure or leave')
                    ->reorderable(false)
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed();
    }
}
