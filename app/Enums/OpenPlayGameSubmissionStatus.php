<?php

namespace App\Enums;

enum OpenPlayGameSubmissionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    /**
     * Get the human-readable label for the submission status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
        };
    }
}
