<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Page extends Model
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
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
     *   @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'public',
        'content',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'public' => 'boolean',
    ];

    /**
     * HTML page content.
     *
     * @return string
     *
     * @OA\Property(
     *   property="content",
     *   type="string",
     *   example="<h1>Hello World</h1>",
     * )
     */
    public function getContentAttribute($content): string
    {
        return parsedown($content);
    }

    /**
     * Raw MD content.
     *
     * @return string
     *
     * @OA\Property(
     *   property="content_raw",
     *   type="string",
     *   example="# Hello World!",
     * )
     */
    public function getContentRawAttribute($content): string
    {
        return $content;
    }
}
