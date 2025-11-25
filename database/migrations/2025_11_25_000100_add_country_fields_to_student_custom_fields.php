<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_custom_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('student_custom_fields', 'show_country')) {
                $table->boolean('show_country')->default(0);
            }

            if (!Schema::hasColumn('student_custom_fields', 'required_country')) {
                $table->boolean('required_country')->default(0);
            }

            if (!Schema::hasColumn('student_custom_fields', 'editable_country')) {
                $table->boolean('editable_country')->default(1);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_custom_fields', function (Blueprint $table) {
            if (Schema::hasColumn('student_custom_fields', 'show_country')) {
                $table->dropColumn('show_country');
            }
            if (Schema::hasColumn('student_custom_fields', 'required_country')) {
                $table->dropColumn('required_country');
            }
            if (Schema::hasColumn('student_custom_fields', 'editable_country')) {
                $table->dropColumn('editable_country');
            }
        });
    }
};

