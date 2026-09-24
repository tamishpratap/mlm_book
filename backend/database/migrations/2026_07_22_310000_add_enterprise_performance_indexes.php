<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('posts', 'idx_posts_group_created')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['group_id', 'created_at'], 'idx_posts_group_created');
            });
        }

        if (! Schema::hasIndex('posts', 'idx_posts_event_created')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['event_id', 'created_at'], 'idx_posts_event_created');
            });
        }

        if (! Schema::hasIndex('friendships', 'idx_friendships_m1_status')) {
            Schema::table('friendships', function (Blueprint $table) {
                $table->index(['member_one_id', 'status'], 'idx_friendships_m1_status');
            });
        }

        if (! Schema::hasIndex('friendships', 'idx_friendships_m2_status')) {
            Schema::table('friendships', function (Blueprint $table) {
                $table->index(['member_two_id', 'status'], 'idx_friendships_m2_status');
            });
        }

        if (! Schema::hasIndex('post_likes', 'idx_post_likes_p_m')) {
            Schema::table('post_likes', function (Blueprint $table) {
                $table->index(['post_id', 'member_id'], 'idx_post_likes_p_m');
            });
        }

        if (! Schema::hasIndex('post_comments', 'idx_post_comments_p_parent')) {
            Schema::table('post_comments', function (Blueprint $table) {
                $table->index(['post_id', 'parent_id'], 'idx_post_comments_p_parent');
            });
        }

        if (! Schema::hasIndex('notifications', 'idx_notifications_notifiable_read')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_notifiable_read');
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_group_created');
            $table->dropIndex('idx_posts_event_created');
        });

        Schema::table('friendships', function (Blueprint $table) {
            $table->dropIndex('idx_friendships_m1_status');
            $table->dropIndex('idx_friendships_m2_status');
        });

        Schema::table('post_likes', function (Blueprint $table) {
            $table->dropIndex('idx_post_likes_p_m');
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropIndex('idx_post_comments_p_parent');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_notifiable_read');
        });
    }
};
