<?php

namespace App\Filament\Resources\ProjectInquiries\Tables;

use App\Filament\Resources\Deals\DealResource;
use App\Filament\Resources\ProjectInquiries\ProjectInquiryResource;
use App\Models\ProjectInquiry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->color('gray')
                    ->hiddenFrom('md'),
                TextColumn::make('project_type')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('budget')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('timeline')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ProjectInquiry::STATUS_NEW => 'danger',
                        ProjectInquiry::STATUS_CONTACTED => 'warning',
                        default => 'success',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ProjectInquiry::STATUS_NEW => 'New',
                        ProjectInquiry::STATUS_CONTACTED => 'Contacted',
                        ProjectInquiry::STATUS_CLOSED => 'Closed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('createDeal')
                    ->label('Create deal')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->color('info')
                    ->url(fn (ProjectInquiry $record): string => DealResource::getUrl('create', ['inquiry_id' => $record->id])),
                Action::make('reply')
                    ->label('Reply')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('primary')
                    ->url(fn (ProjectInquiry $record): string => ProjectInquiryResource::getUrl('respond', ['record' => $record])),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
