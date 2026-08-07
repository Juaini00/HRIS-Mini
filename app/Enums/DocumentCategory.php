<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentCategory: string
{
    use HasOptions;

    case Identity = 'identity';
    case Contract = 'contract';
    case Education = 'education';
    case Certification = 'certification';
    case Tax = 'tax';
    case Bank = 'bank';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Identity => 'Identity',
            self::Contract => 'Contract',
            self::Education => 'Education',
            self::Certification => 'Certification',
            self::Tax => 'Tax',
            self::Bank => 'Bank',
            self::Other => 'Other',
        };
    }
}
