<?php

namespace App\Models\Swagger;

/**
 * @OA\Schema(
 *   title="Package"
 * )
 */
interface PackageSwagger
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Small package",
     * )
     *
     * @OA\Property(
     *   property="wieght",
     *   type="number",
     *   description="Weight in kg",
     *   example=5.7,
     * )
     *
     * @OA\Property(
     *   property="width",
     *   type="integer",
     *   description="Width in cm",
     *   example=10,
     * )
     * 
     * @OA\Property(
     *   property="height",
     *   type="integer",
     *   description="Height in cm",
     *   example=20,
     * )
     * 
     * @OA\Property(
     *   property="depth",
     *   type="integer",
     *   description="Depth in cm",
     *   example=30,
     * )
     */
}
