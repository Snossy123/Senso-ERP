<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('storefronts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('draft');
            $table->string('active_template_key')->nullable();
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('storefront_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->string('page_type');
            $table->string('title')->nullable();
            $table->json('seo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['storefront_id', 'page_type']);
        });

        Schema::create('storefront_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_page_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->string('section_type');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['storefront_page_id', 'section_key']);
        });

        Schema::create('storefront_template_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->string('template_key');
            $table->string('page_type');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['storefront_id', 'template_key', 'page_type'], 'storefront_template_page_unique');
        });

        Schema::create('storefront_publish_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('published');
            $table->json('snapshot');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['storefront_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_publish_versions');
        Schema::dropIfExists('storefront_template_bindings');
        Schema::dropIfExists('storefront_sections');
        Schema::dropIfExists('storefront_pages');
        Schema::dropIfExists('storefronts');
    }
};
