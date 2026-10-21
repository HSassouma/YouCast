<?php

namespace App\Enums;

enum UserRole: string
{
    case Learner = 'learner';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Learner => 'Étudiant',
            self::Admin => 'Administration BDE',
        };
    }
}
