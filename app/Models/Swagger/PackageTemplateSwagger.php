<?php

namespace App\Models\Swagger;

/**
 * @OA\Schema(
 *   title="PackageTemplate"
 * )
 */
interface PackageTemplateSwagger
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="5fb4a472-b5fd-4e9a-a4ee-bf42bde86a73",
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
