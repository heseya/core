<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model as LaravelModel;

abstract class Model extends LaravelModel
{
    use HasUuid;
}
