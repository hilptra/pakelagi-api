<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'event_date'], 'events_status_event_date_index');
            $table->index(['status', 'show_on_homepage', 'event_date'], 'events_status_homepage_date_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'products_status_created_at_index');
            $table->index('size_label', 'products_size_label_index');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'is_primary'], 'product_images_product_primary_index');
            $table->index(['product_id', 'sort_order'], 'product_images_product_sort_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasIndex('product_images', 'product_images_product_id_foreign')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->index('product_id', 'product_images_product_id_foreign');
            });
        }

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_product_sort_index');
            $table->dropIndex('product_images_product_primary_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_size_label_index');
            $table->dropIndex('products_status_created_at_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_homepage_date_index');
            $table->dropIndex('events_status_event_date_index');
        });
    }
};
