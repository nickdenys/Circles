<?php

namespace App\Rules;

use App\Support\AlbumListSlugger;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAlbumListTitleSlug implements ValidationRule
{
    public function __construct(private readonly AlbumListSlugger $slugger) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $base = $this->slugger->base($value);

        if ($base === null) {
            $fail('Title must contain at least one letter or number.');

            return;
        }

        if ($this->slugger->isReserved($base)) {
            $fail("\"{$base}\" is a reserved URL. Please choose a different title.");
        }
    }
}
