<?php

use App\Enums\SchemaType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema as SchemaModel;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('schemas', function (Blueprint $table) {
            $table->string('type')->change()->default(SchemaType::STRING);
            $roles = SchemaModel::all();
            $roles->each(function (SchemaModel $schema) {
                $type = match ($schema->type) {
                    '0' => SchemaType::STRING,
                    '1' => SchemaType::NUMERIC,
                    '2' => SchemaType::BOOLEAN,
                    '3' => SchemaType::DATE,
                    '4' => SchemaType::SELECT,
                    '5' => SchemaType::FILE,
                    '6' => SchemaType::MULTIPLY,
                    '7' => SchemaType::MULTIPLY_SCHEMA,
                    default => null,
                };
                if ($type !== null) {
                    $schema->type = $type;
                    $schema->save();
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schemas', function (Blueprint $table) {
            $table->unsignedTinyInteger('type');
        });
    }
};
