<?php

namespace App;

use App\ProductSchema;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'qty',
        'photo',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'photo_id',
        'symbol',
    ];

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function schemas()
    {
        return $this->belongsToMany(ProductSchema::class, 'product_schema_item');
    }
}
