<?php

namespace App\Services;

use App\Exceptions\CultivationCycleConflict;
use Illuminate\Database\QueryException;

final class CultivationCycleDatabaseConflictMapper
{
    public function map(QueryException $exception): ?CultivationCycleConflict
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        if ($sqlState !== '23505') {
            return null;
        }

        $details = $exception->errorInfo[2] ?? '';
        $message = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
        $diagnostic = $details.' '.$message;

        if (str_contains($diagnostic, 'cultivation_cycles_farm_id_batch_code_unique')) {
            return new CultivationCycleConflict('This batch code is already used in this farm.');
        }

        if (str_contains($diagnostic, 'cultivation_cycles_one_open_per_pond')) {
            return new CultivationCycleConflict('This pond already has a planned or active cycle.');
        }

        return null;
    }
}
