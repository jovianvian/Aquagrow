<?php

namespace App\Enums;

enum PondOperationalStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case MAINTENANCE = 'MAINTENANCE';
    case INACTIVE = 'INACTIVE';
}
