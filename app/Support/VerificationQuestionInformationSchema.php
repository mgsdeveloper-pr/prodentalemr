<?php

namespace App\Support;

use App\Models\VerificationFormQuestion;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class VerificationQuestionInformationSchema
{
    public static function make(): Section
    {
        return Section::make('Information & Reuse')
            ->columnSpan(12)->collapsible()->collapsed()
            ->schema([
                Select::make('information_scope')
                    ->label('Information type')
                    ->options(VerificationFormQuestion::INFORMATION_SCOPE_OPTIONS)
                    ->default('unclassified')->required()->live()
                    ->afterStateUpdated(fn (Set $set) => $set('reuse_policy', 'fresh_verification')),
                Select::make('reuse_policy')
                    ->label('Reuse rule')
                    ->options(fn (Get $get): array => $get('information_scope') === 'plan'
                        ? VerificationFormQuestion::REUSE_POLICY_OPTIONS
                        : ['fresh_verification' => 'Fresh verification required'])
                    ->default('fresh_verification')->required(),
            ]);
    }
}
