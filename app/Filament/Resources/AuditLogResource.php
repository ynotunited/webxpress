<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Read-only view of the audit trail (Admin -> Audit Trail).
 *
 * No create, edit or delete, deliberately. A log that can be edited is not
 * evidence: the model already refuses updates and deletes, and denying the
 * routes too means there is no path to a "corrected" entry at all. A mistaken
 * record is answered by appending the truth, not by rewriting history.
 *
 * This also means no form schema is needed. Filament's default form is empty,
 * and with every write route closed no schema is ever rendered.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'audit entry';

    protected static ?string $pluralModelLabel = 'audit entries';

    protected static ?int $navigationSort = 90;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    // The trail is dense and unremarkable most of the time, so
                    // the column name is wasted space on the widest repeated
                    // field in the list.
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (AuditLog $record): string => match ($record->event) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'logged_in' => 'success',
                        'login_failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('summary')
                    ->label('Action')
                    ->searchable()
                    ->description(fn (AuditLog $record): ?string => $record->event === 'login_failed'
                        ? null
                        : $record->subjectLabel().' #'.$record->subject_id)
                    ->wrap(),

                TextColumn::make('user_email')
                    ->label('Who')
                    ->searchable()
                    ->placeholder('—')
                    // Falls back to the snapshot when the account has since been
                    // deleted, which is exactly when knowing the name matters.
                    ->formatStateUsing(fn (AuditLog $record): string => $record->user?->email
                        ?? $record->user_email
                        ?? 'system')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('changes')
                    ->label('Changed')
                    ->formatStateUsing(fn (AuditLog $record): string => $record->changes
                        ? implode(', ', array_keys($record->changes))
                        : '—')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
            ])
            // The before/after table lives in a modal rather than in the row,
            // because a diff is several lines tall and would wreck the layout of
            // a list whose whole job is to be scannable.
            ->recordActions([
                ViewAction::make('view')
                    ->label('Details')
                    ->visible(fn (AuditLog $record): bool => filled($record->changes))
                    ->modalHeading(fn (AuditLog $record): string => $record->summary)
                    ->modalContent(fn (AuditLog $record) => view('filament.audit-log-changes', [
                        'record' => $record,
                    ]))
                    ->modalWidth(Width::FiveExtraLarge),
            ])
            ->filters([
                SelectFilter::make('event')->options([
                    'created' => 'Created',
                    'updated' => 'Updated',
                    'deleted' => 'Deleted',
                    'logged_in' => 'Signed in',
                    'login_failed' => 'Failed sign-in',
                ]),

                SelectFilter::make('subject_type')
                    ->label('Record type')
                    ->options(fn (): array => AuditLog::query()
                        ->select('subject_type')
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->all()),

                TernaryFilter::make('changes')->label('Has field changes'),
            ])
            // Newest first by default; the query stays cheap on subject_type
            // and event, both of which are indexed.
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}