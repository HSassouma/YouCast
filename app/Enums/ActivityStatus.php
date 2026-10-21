<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publiée',
            self::Closed => 'Clôturée',
            self::Cancelled => 'Annulée',
            self::Archived => 'Archivée',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this, [self::Published, self::Closed], true);
    }

    public function allowsRegistration(): bool
    {
        return $this === self::Published;
    }
}
