<?php

namespace App\ValiantShade\Enums;

enum InWorldWeekday: string
{
    case Vonyas = 'Vonyas';
    case Vartre = 'Vartre';
    case Biniru = 'Biniru';
    case Dustar = 'Dustar';
    case Sherry = 'Sherry';
    case Honza = 'Honza';
    case Saemon = 'Saemon';

    public function terrestrial(): string
    {
        return match ($this) {
            self::Vonyas => 'Pazar',
            self::Vartre => 'Pazartesi',
            self::Biniru => 'Salı',
            self::Dustar => 'Çarşamba',
            self::Sherry => 'Perşembe',
            self::Honza => 'Cuma',
            self::Saemon => 'Cumartesi',
        };
    }

    /** @param  0|1|2|3|4|5|6  $gregorianDayOfWeek */
    public static function fromGregorian(int $gregorianDayOfWeek): self
    {
        return match ($gregorianDayOfWeek) {
            0 => self::Vonyas,
            1 => self::Vartre,
            2 => self::Biniru,
            3 => self::Dustar,
            4 => self::Sherry,
            5 => self::Honza,
            6 => self::Saemon,
            default => throw new \InvalidArgumentException('Geçersiz haftanın günü: '.$gregorianDayOfWeek),
        };
    }

    public static function tryFromTerrestrial(string $terrestrial): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->terrestrial() === $terrestrial) {
                return $case;
            }
        }

        return null;
    }
}
