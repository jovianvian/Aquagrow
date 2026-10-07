<?php

namespace App\Enums;

enum CultivationCycleStatus: string
{
    case PLANNED = 'PLANNED';
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
