<?php

declare(strict_types=1);

namespace Support\Utils;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

final readonly class Migrate
{
    public static function lang(string $column, string $lang_id): Expression
    {
        return DB::raw("JSON_OBJECT('{$lang_id}', `{$column}`)");
    }
}
