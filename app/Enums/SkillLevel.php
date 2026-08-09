<?php

namespace App\Enums;

enum SkillLevel: string
{
    case Beginner = 'beginner';
    case Novice = 'novice';
    case Expert = 'expert';

    /**
     * Get the human-readable label for the skill level.
     */
    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Novice => 'Novice',
            self::Expert => 'Expert',
        };
    }

    /**
     * Get the skill level options formatted for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $level): array => ['value' => $level->value, 'label' => $level->label()],
            self::cases(),
        );
    }
}
