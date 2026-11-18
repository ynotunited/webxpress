<?php

namespace App\Rules;

use App\Support\PageSectionSchema;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Route;

/**
 * A block's link field holds a named route ("contact", "work.index").
 *
 * This is the one copy mistake that fails silently: the renderers skip a link
 * whose route does not exist, so a typo does not render a broken button, it
 * renders no button at all. Validating here turns that into an obvious error
 * on save, with the list of names that do exist.
 */
class ExistingRouteName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (Route::has((string) $value)) {
            return;
        }

        $fail("There is no route named '{$value}'. Use one of: ".implode(', ', PageSectionSchema::pageRoutes()).'.');
    }
}
