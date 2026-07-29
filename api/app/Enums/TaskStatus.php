<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case Done       = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo       => 'A fazer',
            self::InProgress => 'Em desenvolvimento',
            self::Done       => 'Completo'
        };
    }
}
