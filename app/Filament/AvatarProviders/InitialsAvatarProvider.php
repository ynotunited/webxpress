<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Local, CSP-safe replacement for Filament's ui-avatars.com default.
 *
 * The admin CSP allows data: images but no third-party origins, and the panel
 * should not leak the admin's name to an external service just to draw two
 * letters. Generating the SVG here keeps the panel fully self-hosted.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = '';

        foreach (explode(' ', (string) Filament::getNameForDefaultAvatar($record)) as $segment) {
            $letter = preg_replace('/^[^\p{L}\p{N}]+/u', '', trim($segment));

            if (filled($letter)) {
                $initials .= mb_substr($letter, 0, 1);
            }

            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        $initials = mb_strtoupper($initials !== '' ? $initials : 'WX');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" rx="32" fill="#1B2D5F"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="system-ui, -apple-system, sans-serif" font-size="24" font-weight="600" fill="#FFFFFF">'
            .e($initials)
            .'</text></svg>';

        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode($svg);
    }
}
