<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('courier_name')->index();
            $table->string('tracking_id')->index();
            $table->string('status');
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_webhook_logs');
    }
};
