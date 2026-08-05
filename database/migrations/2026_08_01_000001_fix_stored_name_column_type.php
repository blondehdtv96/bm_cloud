<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * stored_name was originally a fixed uuid() column (CHAR(36)), but
     * FileStorageService appends the original file extension
     * (e.g. "<uuid>.pdf"), which overflows a CHAR(36) column for any
     * file that has an extension. Widen it to a plain string column.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('stored_name', 255)->change();
        });

        Schema::table('file_versions', function (Blueprint $table) {
            $table->string('stored_name', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->uuid('stored_name')->change();
        });

        Schema::table('file_versions', function (Blueprint $table) {
            $table->uuid('stored_name')->change();
        });
    }
};
