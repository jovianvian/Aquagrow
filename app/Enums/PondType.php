<?php

namespace App\Enums;

enum PondType: string
{
    case EARTHEN = 'EARTHEN';
    case CONCRETE = 'CONCRETE';
    case TARPAULIN = 'TARPAULIN';
    case FIBERGLASS = 'FIBERGLASS';
    case CAGE = 'CAGE';
    case OTHER = 'OTHER';
}
