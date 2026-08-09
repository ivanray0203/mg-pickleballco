<?php

namespace App\Enums;

enum MatchupStatus: string
{
    case Queued = 'queued';
    case Playing = 'playing';
    case Done = 'done';

    /**
     * Get the human-readable label for the matchup status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Queued => 'In queue',
            self::Playing => 'Playing now',
            self::Done => 'Completed',
        };
    }
}
