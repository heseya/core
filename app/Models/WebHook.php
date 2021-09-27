<?php

namespace App\Models;

use App\Traits\Sortable;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;

class WebHook extends Model
{
    use HasFactory, SoftDeletes, Searchable, Sortable, Auditable;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'with_issuer',
        'with_hidden',
    ];

    protected $casts = [
        'with_issuer' => 'bool',
        'with_hidden' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'events' => 'array',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'url' => Like::class,
    ];

    protected array $sortable = [
        'id',
        'name',
        'url',
        'created_at',
        'updated_at',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

    public function logs(): HasMany
    {
        return $this->hasMany(WebHookEventLogEntry::class);
    }
}
