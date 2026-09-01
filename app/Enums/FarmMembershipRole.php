<?php

namespace App\Enums;

enum FarmMembershipRole: string
{
    case FARM_OWNER = 'FARM_OWNER';
    case POND_OPERATOR = 'POND_OPERATOR';
}
