<?php

namespace App\Filament\Resources\Deals\Schemas;

use App\Models\Deal;
use App\Models\DealDocument;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DealForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('project_inquiry_id')
                    ->label('Linked inquiry')
                    ->relationship('inquiry', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Optional — links this deal to a website inquiry.'),
                TextInput::make('name')
                    ->label('Client name')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(150),
                TextInput::make('phone')
                    ->label('Phone / WhatsApp')
                    ->maxLength(30),
                TextInput::make('company')
                    ->maxLength(100),
                TextInput::make('project_title')
                    ->label('Project title')
                    ->required()
                    ->maxLength(150),
                TextInput::make('project_type')
                    ->label('Project type')
                    ->maxLength(100),
                Textarea::make('scope')
                    ->label('Scope of work')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Total project fee (₦)')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                TextInput::make('upfront_percent')
                    ->label('Upfront %')
                    ->numeric()
                    ->required()
                    ->default(70)
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('delivery_percent')
                    ->label('Delivery %')
                    ->numeric()
                    ->required()
                    ->default(30)
                    ->minValue(0)
                    ->maxValue(100),
                DatePicker::make('start_date')
                    ->label('Project start date')
                    ->native(false),
                TextInput::make('duration_label')
                    ->label('Duration (e.g. "6 weeks")')
                    ->maxLength(60),
                Textarea::make('milestones')
                    ->label('Milestones (one per line)')
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('terms')
                    ->label('Custom terms (overrides defaults)')
                    ->rows(4)
                    ->helperText('If blank, the standard terms template is used.')
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Internal notes')
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Deal status')
                    ->options(Deal::statusOptions())
                    ->required()
                    ->default(Deal::STATUS_DRAFT)
                    ->helperText('Advanced: usually managed automatically from the Deal page.'),
                Repeater::make('documents')
                    ->relationship()
                    ->label('Documents')
                    ->defaultItems(0)
                    ->reorderableWithButtons()
                    ->schema([
                        Select::make('type')
                            ->options([
                                DealDocument::TYPE_TEMPLATE => 'Templated (auto-filled)',
                                DealDocument::TYPE_UPLOAD => 'Uploaded file',
                            ])
                            ->default(DealDocument::TYPE_TEMPLATE)
                            ->live()
                            ->required(),
                        Select::make('template_key')
                            ->options([
                                DealDocument::KEY_PROPOSAL => 'Proposal',
                                DealDocument::KEY_TIMELINE => 'Timeline',
                                DealDocument::KEY_TERMS => 'Terms',
                            ])
                            ->hidden(fn (Get $get): bool => $get('type') !== DealDocument::TYPE_TEMPLATE),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(150),
                        Textarea::make('content')
                            ->label('Template content (blank = default)')
                            ->rows(5)
                            ->hidden(fn (Get $get): bool => $get('type') !== DealDocument::TYPE_TEMPLATE)
                            ->helperText('Use placeholders like {{ client_name }}, {{ amount }}, {{ start_date }}, {{ duration }}.'),
                        FileUpload::make('file_path')
                            ->label('File (PDF, DOCX)')
                            ->disk('public')
                            ->directory('deal-documents')
                            ->visibility('public')
                            ->hidden(fn (Get $get): bool => $get('type') !== DealDocument::TYPE_UPLOAD),
                        TextInput::make('file_name')
                            ->label('Display name')
                            ->maxLength(150)
                            ->hidden(fn (Get $get): bool => $get('type') !== DealDocument::TYPE_UPLOAD),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Visible to client')
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
