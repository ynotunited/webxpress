<?php

namespace App\Models;

use App\Support\Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'published_at',
        'is_published',
        'seo_title',
        'seo_description',
        'featured',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'featured' => 'boolean',
    ];

    public static function getModelLabel(): string
    {
        return 'Post';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Posts';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        if (str_starts_with($this->cover_image, 'images/')) {
            return asset($this->cover_image);
        }

        return asset('storage/'.$this->cover_image);
    }

    public function getExcerptAttribute(): string
    {
        return $this->attributes['excerpt'] ?? Str::limit(strip_tags($this->body), 160);
    }

    /**
     * The post body, sanitised for output.
     *
     * The blog template echoes `body` unescaped, which is the only way rich
     * text renders at all — and also the only place raw editor input reaches
     * a visitor's browser as markup. Sanitising on read covers rows written
     * before this existed, rows imported from elsewhere, and anything written
     * directly to the database.
     */
    protected function bodyHtml(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Html::sanitize($this->attributes['body'] ?? ''),
        );
    }

    /**
     * Sanitise the body on the way in as well, so the column only ever holds
     * one kind of HTML and no other reader can pick up something unsanitised.
     */
    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            if ($post->isDirty('body') && filled($post->body)) {
                $post->body = Html::sanitize($post->body);
            }
        });
    }
}
