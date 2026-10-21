<?php

namespace App\Enums;

enum ActivityType: string
{
    case Event = 'event';
    case IndividualChallenge = 'individual_challenge';
    case TeamChallenge = 'team_challenge';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Événement',
            self::IndividualChallenge => 'Défi individuel',
            self::TeamChallenge => 'Défi en équipe',
        };
    }

    public function usesTeams(): bool
    {
        return $this === self::TeamChallenge;
    }
}
