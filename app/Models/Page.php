<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema()
 */
class Page extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="terms-and-conditions",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Terms & Conditions",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="content_md",
     *   type="string",
     *   example="# Hello World!",
     * )
     */

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'public',
        'content_md',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Content in HTML.
     *
     * @OA\Property(
     *   property="content_html",
     *   type="string",
     *   example="<h1>Awesome stuff!</h1>",
     * )
     */
    public function getContentHtmlAttribute(): string
    {
        return parsedown($this->content_md);
    }
}
