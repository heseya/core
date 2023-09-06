<?php

namespace App\Models;

use App\Enums\RedirectType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'url',
        'type',
    ];
    protected $casts = [
        'type' => RedirectType::class,
    ];
}
