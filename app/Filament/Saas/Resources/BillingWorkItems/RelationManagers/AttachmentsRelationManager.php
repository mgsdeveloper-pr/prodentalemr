<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                TextInput::make('title')
                    ->maxLength(255),
                FileUpload::make('file_path')
                    ->label('Attachment')
                    ->disk('local')
                    ->directory('billing-work-items')
                    ->preserveFilenames()
                    ->storeFileNamesIn('original_file_name')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('original_file_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->placeholder('-'),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => filled($state) ? number_format(((int) $state) / 1024, 1) . ' KB' : '-'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M d, Y h:i A'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record): string => route('saas.billing-work-item-attachments.download', $record)),
                DeleteAction::make(),
            ]);
    }
}
