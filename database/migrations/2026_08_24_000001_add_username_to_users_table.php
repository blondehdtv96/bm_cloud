<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        $usernames = [];
        DB::table('users')->orderBy('id')->select('id', 'email')->get()->each(function ($user) use (&$usernames) {
            $base = strtolower(preg_replace('/[^a-z0-9]/i', '', explode('@', $user->email)[0]));
            $base = $base ?: 'user' . $user->id;
            $username = $base;
            $suffix = 1;
            while (in_array($username, $usernames) || DB::table('users')->where('username', $username)->exists()) {
                $username = $base . $suffix++;
            }
            $usernames[] = $username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
