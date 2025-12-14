<?php

namespace App\Enums;

enum QuizType: string
{
    case Practice = 'practice';
    case Timed = 'timed';
    case Mock = 'mock';

    public function label(): string
    {
        return match ($this) {
            self::Practice => 'Practice',
            self::Timed => 'Timed',
            self::Mock => 'Mock Exam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Practice => 'success',
            self::Timed => 'warning',
            self::Mock => 'info',
        };
    }

    public static function options(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $type) {
            $carry[$type->value] = $type->label();
            return $carry;
        }, []);
    }

    public static function values(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }
}
