<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'address')) {
                $table->string('address')->nullable();
            }
            $table->decimal('total_value', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
        });

        if (Schema::hasColumn('appointments', 'is_paid')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('is_paid');
            });
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['total_value', 'amount_paid']);
        });
    }
};
