<?php

namespace App\Enums;

enum AlbumListMode: string
{
    case Default = 'default';
    case Listening = 'listening';

    public static function coerce(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Default;
    }
}
