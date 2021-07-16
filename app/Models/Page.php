<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperPage
 */
class Page extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="order",
     *   type="boolean",
     *   example="1",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="O nas",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="o-nas",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     *   example="1",
     * )
     *
     * @OA\Property(
     *   property="content_html",
     *   type="string",
     *   example="<h2>Socer tenebras animae caute</h2>",
     * )
     */

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
    ];

    protected $casts = [
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
