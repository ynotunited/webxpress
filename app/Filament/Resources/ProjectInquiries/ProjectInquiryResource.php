<?php

namespace App\Filament\Resources\ProjectInquiries;

use App\Filament\Resources\ProjectInquiries\Pages\EditProjectInquiry;
use App\Filament\Resources\ProjectInquiries\Pages\ListProjectInquiries;
use App\Filament\Resources\ProjectInquiries\Pages\RespondInquiry;
use App\Filament\Resources\ProjectInquiries\Pages\ViewProjectInquiry;
use App\Filament\Resources\ProjectInquiries\Schemas\ProjectInquiryForm;
use App\Filament\Resources\ProjectInquiries\Schemas\ProjectInquiryInfolist;
use App\Filament\Resources\ProjectInquiries\Tables\ProjectInquiriesTable;
use App\Models\ProjectInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectInquiryResource extends Resource
{
    protected static ?string $model = ProjectInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static ?string $navigationLabel = 'Inquiries';

    protected static ?string $modelLabel = 'Inquiry';

    protected static ?string $pluralModelLabel = 'Project Inquiries';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = ProjectInquiry::new()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectInquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectInquiriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInquiryInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectInquiries::route('/'),
            'view' => ViewProjectInquiry::route('/{record}'),
            'edit' => EditProjectInquiry::route('/{record}/edit'),
            'respond' => RespondInquiry::route('/{record}/respond'),
        ];
    }
}
