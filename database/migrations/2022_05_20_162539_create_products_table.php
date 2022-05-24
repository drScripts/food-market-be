<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('price')->nullable();
            $table->string('ingredients')->nullable();
            $table->double('rating')->default(0)->nullable();
            $table->text('description')->nullable();
            $table->integer('stock')->default(0)->nullable();
            $table->text('picture')->nullable();
            $table->text('picture_public_id')->nullable();
            $table->enum('type', ['new_taste', 'recommended', 'popular', 'none'])->default('none')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
