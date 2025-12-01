<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();

            // dono/criador do grupo (opcional, mas recomendado)
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // dados da foto (Cloudinary)
            $table->string('photo_id')->nullable();   // publicId
            $table->string('photo_url')->nullable();  // url da imagem

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('state', 2);
            $table->string('city');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
