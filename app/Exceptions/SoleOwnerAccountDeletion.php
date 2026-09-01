<?php

namespace App\Exceptions;

use DomainException;

final class SoleOwnerAccountDeletion extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot delete your account while you are the only owner of one or more farms. Assign another owner before deleting your account.');
    }
}
