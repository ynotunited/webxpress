<?php

namespace App\Filament\Resources\ProjectInquiries\Schemas;

use App\Models\ProjectInquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled(),
                TextInput::make('email')
                    ->label('Email address')
                    ->disabled(),
                TextInput::make('project_type')
                    ->disabled(),
                TextInput::make('budget')
                    ->disabled(),
                TextInput::make('timeline')
                    ->disabled(),
                Textarea::make('message')
                    ->disabled()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        ProjectInquiry::STATUS_NEW => 'New',
                        ProjectInquiry::STATUS_CONTACTED => 'Contacted',
                        ProjectInquiry::STATUS_CLOSED => 'Closed',
                    ])
                    ->default(ProjectInquiry::STATUS_NEW)
                    ->required(),
            ]);
    }
}
