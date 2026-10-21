<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->index();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('post_count')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 16)->default('published');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('app_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('app_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->string('status', 16)->default('draft');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('version_name');
            $table->unsignedInteger('version_code');
            $table->string('channel', 32)->default('stable');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum')->nullable();
            $table->string('min_os')->nullable();
            $table->text('changelog')->nullable();
            $table->string('storage_key')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'version_code']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->nullable()->constrained('apps')->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('cover')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
        });

        Schema::create('ops_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('image');
            $table->string('link_type', 32)->default('url');
            $table->string('link_value')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 16)->default('published');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ops_grid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('link_type', 32)->default('url');
            $table->string('link_value')->nullable();
            $table->string('badge')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 16)->default('published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ops_grid_items');
        Schema::dropIfExists('ops_banners');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('app_versions');
        Schema::dropIfExists('apps');
        Schema::dropIfExists('app_categories');
        Schema::dropIfExists('boards');
    }
};
