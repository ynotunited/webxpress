<?php

namespace App\Filament\Resources\ProjectInquiries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->copyable()
                            ->copyableState(false)
                            ->icon('heroicon-m-envelope'),
                        TextEntry::make('project_type')
                            ->badge()
                            ->color('primary')
                            ->placeholder('—'),
                        TextEntry::make('budget')
                            ->placeholder('—'),
                        TextEntry::make('timeline')
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'new' => 'danger',
                                'contacted' => 'warning',
                                default => 'success',
                            }),
                        TextEntry::make('created_at')
                            ->label('Received')
                            ->dateTime('M j, Y g:i A')
                            ->columnSpanFull(),
                    ]),
                Section::make('Project brief')
                    ->schema([
                        TextEntry::make('message')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
