<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('employment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->json('emergency_contact')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employment_type_id');
            $table->dropColumn('emergency_contact');
        });
        Schema::dropIfExists('employment_types');
    }
};
