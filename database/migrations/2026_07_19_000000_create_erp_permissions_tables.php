<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module');
            $table->string('action');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['module', 'action']);
        });

        Schema::create('erp_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('erp_companies')->onDelete('cascade');
            $table->string('name');
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index('is_active');
        });

        Schema::create('erp_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('erp_roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('erp_permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('erp_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained('erp_roles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_user_roles');
        Schema::dropIfExists('erp_role_permissions');
        Schema::dropIfExists('erp_roles');
        Schema::dropIfExists('erp_permissions');
    }
};