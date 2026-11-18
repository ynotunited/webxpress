<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\BlogPostResource\Pages\CreateBlogPost;
use App\Filament\Resources\Blog\BlogPostResource\Pages\EditBlogPost;
use App\Filament\Resources\Blog\BlogPostResource\Pages\ListBlogPosts;
use App\Models\Post;
use App\Support\Concerns\ConfiguresRichTextImages;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    use ConfiguresRichTextImages;

    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Blog Posts';

    protected static ?string $modelLabel = 'Blog Post';

    protected static ?string $pluralModelLabel = 'Blog Posts';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        $body = static::warnOnRemoteImages(
            static::withLocalImageUploads(
                RichEditor::make('body')->required(),
                'posts/body',
            ),
        );

        $body = $body
            ->toolbarButtons([
                'bold', 'italic', 'link',
                'h2', 'h3', 'blockquote', 'bulletList', 'orderedList',
                'attachFiles',
                'undo', 'redo',
            ])
            ->helperText('Formatting is kept. Scripts and unsafe links are removed when the post is saved. Images must be uploaded with the image button, not linked from another site.')
            ->columnSpanFull();

        return $schema->components([
            Section::make('Content')->schema([
                TextInput::make('title')->required()->live(onBlur: true)->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')->unique(ignoreRecord: true)->required(),
                Select::make('category_id')->relationship('category', 'name')->searchable()->preload()->nullable(),
                Textarea::make('excerpt')->rows(3),
                $body,                FileUpload::make('cover_image')
                    ->image()
                    ->disk('public')
                    ->directory('posts/covers')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1200')
                    ->visibility('public'),
                Toggle::make('is_published'),
                Toggle::make('featured'),
            ])->columns(2),
            Section::make('SEO')->schema([
                TextInput::make('seo_title')->maxLength(70),
                Textarea::make('seo_description')->rows(2),
            ])->collapsed(),
            DateTimePicker::make('published_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold'),
                TextColumn::make('category.name')->badge()->color('primary'),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('published_at')->dateTime('M j, Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
