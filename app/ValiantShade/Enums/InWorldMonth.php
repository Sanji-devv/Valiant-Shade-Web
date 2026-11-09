<?php

namespace App\ValiantShade\Enums;

enum InWorldMonth: string
{
    case Silvar = 'Silvar';
    case Ren = 'Ren';
    case Hoolin = 'Hoolin';
    case Kean = 'Kean';
    case Vakhan = 'Vakhan';
    case Balan = 'Balan';
    case Belaran = 'Belaran';
    case Shean = 'Shean';
    case Roran = 'Roran';
    case Siwan = 'Siwan';
    case Leos = 'Leos';
    case Dosan = 'Dosan';

    public function terrestrial(): string
    {
        return match ($this) {
            self::Silvar => 'Ocak',
            self::Ren => 'Şubat',
            self::Hoolin => 'Mart',
            self::Kean => 'Nisan',
            self::Vakhan => 'Mayıs',
            self::Balan => 'Haziran',
            self::Belaran => 'Temmuz',
            self::Shean => 'Ağustos',
            self::Roran => 'Eylül',
            self::Siwan => 'Ekim',
            self::Leos => 'Kasım',
            self::Dosan => 'Aralık',
        };
    }

    /** @param  1|2|3|4|5|6|7|8|9|10|11|12  $gregorianMonth */
    public static function fromGregorian(int $gregorianMonth): self
    {
        return match ($gregorianMonth) {
            1 => self::Silvar,
            2 => self::Ren,
            3 => self::Hoolin,
            4 => self::Kean,
            5 => self::Vakhan,
            6 => self::Balan,
            7 => self::Belaran,
            8 => self::Shean,
            9 => self::Roran,
            10 => self::Siwan,
            11 => self::Leos,
            12 => self::Dosan,
            default => throw new \InvalidArgumentException('Geçersiz ay: '.$gregorianMonth),
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
