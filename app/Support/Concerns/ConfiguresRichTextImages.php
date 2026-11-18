<?php

namespace App\Support\Concerns;

use App\Support\Html;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Shared configuration for every rich text field in the admin.
 *
 * Rich text is the one place an editor can introduce an image, and there are
 * three separate editors that can do it. Configuring them individually is how
 * they drift apart — one gets an upload button, two do not, and the only
 * images the other two can produce are pasted hotlinks. Every editor is
 * therefore set up through here, so "images are uploaded, not linked" is one
 * decision made once.
 */
trait ConfiguresRichTextImages
{
    /**
     * Point a rich text field at the public disk and give it an image button.
     *
     * The `public` disk is named explicitly even though Filament would infer
     * it from the default visibility: inferring it makes the destination
     * depend on a global setting, so changing `filament.default_filesystem_
     * disk` elsewhere would silently move every body image into a directory
     * the web server never serves.
     *
     * Static so the same helper serves resource classes and the static
     * field-builder helpers, which cannot instantiate a trait.
     */
    protected static function withLocalImageUploads(RichEditor $editor, string $directory): RichEditor
    {
        return $editor
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory($directory)
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(self::imageFileTypes())
            ->fileAttachmentsMaxSize(5 * 1024)
            ->resizableImages()
            ->getFileAttachmentUrlUsing(self::publicImageUrl(...));
    }

    /**
     * Warn when a paste would silently lose an image.
     *
     * Without this, the sanitizer's removal of a remote `<img>` looks like the
     * editor's own mistake — the image was visible a moment ago. Saying so on
     * the way past turns a confusing data loss into an explanation with a next
     * step.
     */
    protected static function warnOnRemoteImages(RichEditor $editor): RichEditor
    {
        return $editor->live(onBlur: true)->afterStateUpdated(function ($state): void {
            if (! Html::hasRemoteImage(is_string($state) ? $state : null)) {
                return;
            }

            Notification::make()
                ->title('Linked image removed')
                ->body('Images linked from another website are not kept. Use the image button in the toolbar to upload it to this site instead.')
                ->warning()
                ->persistent()
                ->send();
        });
    }

    /**
     * Public URL for a path on the public disk.
     *
     * Root-relative rather than the absolute URL Filament produces by default.
     * Both pass the sanitizer's own-host allowlist, but a relative one survives
     * a domain change: a site that moves to another host would otherwise keep
     * publishing stored body images under the old domain, which the new
     * host's allowlist correctly rejects and silently deletes.
     */
    protected static function publicImageUrl(mixed $file): ?string
    {
        $file = is_string($file) ? ltrim($file, '/') : '';

        if ($file === '' || Str::contains($file, ['..', "\0"])) {
            return null;
        }

        return '/storage/'.$file;
    }

    /**
     * The image formats a body may contain.
     *
     * SVG is excluded on purpose: it is a document format that can carry
     * script, so accepting it through an upload would be the same hole the
     * sanitizer exists to close, reached by a different door.
     *
     * @return list<string>
     */
    protected static function imageFileTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
        ];
    }
}
