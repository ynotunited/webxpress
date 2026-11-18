<?php

namespace App\Filament\Resources\Deals\Tables;

use App\Filament\Resources\Deals\DealResource;
use App\Models\Deal;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('project_title')
                    ->label('Project')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Fee')
                    ->formatStateUsing(fn ($state) => '₦'.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Deal::STATUS_DRAFT => 'gray',
                        Deal::STATUS_DOCUMENTS_SENT => 'info',
                        Deal::STATUS_UPFRONT_PENDING => 'warning',
                        Deal::STATUS_IN_PROGRESS => 'primary',
                        Deal::STATUS_DELIVERY_PENDING => 'warning',
                        Deal::STATUS_COMPLETED => 'success',
                        Deal::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_via')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'web' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'web' ? 'Website' : 'External'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Deal::statusOptions()),
                SelectFilter::make('created_via')
                    ->options([
                        'web' => 'Website',
                        'manual' => 'External / manual',
                    ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('manage')
                    ->label('Manage')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('primary')
                    ->url(fn (Deal $record): string => DealResource::getUrl('view', ['record' => $record])),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
