<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'group' => 'dashboard', 'description' => 'Access executive overview and platform metrics'],

            // Members Management
            ['name' => 'View Members', 'slug' => 'view-members', 'group' => 'members', 'description' => 'View registered members list and profiles'],
            ['name' => 'Edit Members', 'slug' => 'edit-members', 'group' => 'members', 'description' => 'Modify member profile details and status'],
            ['name' => 'Block / Unblock Members', 'slug' => 'block-members', 'group' => 'members', 'description' => 'Block and unblock member access'],
            ['name' => 'Delete Members', 'slug' => 'delete-members', 'group' => 'members', 'description' => 'Permanently remove member accounts'],
            ['name' => 'Export Members', 'slug' => 'export-members', 'group' => 'members', 'description' => 'Export member datasets (CSV/Excel)'],

            // Posts & Moderation
            ['name' => 'View Posts', 'slug' => 'view-posts', 'group' => 'posts', 'description' => 'View public and shared posts across the platform'],
            ['name' => 'Edit Posts', 'slug' => 'edit-posts', 'group' => 'posts', 'description' => 'Modify post contents and attachments'],
            ['name' => 'Hide / Unhide Posts', 'slug' => 'hide-posts', 'group' => 'posts', 'description' => 'Toggle post visibility in global feed'],
            ['name' => 'Delete Posts', 'slug' => 'delete-posts', 'group' => 'posts', 'description' => 'Remove infringing or flagged posts'],
            ['name' => 'Manage Post Reports', 'slug' => 'manage-post-reports', 'group' => 'posts', 'description' => 'Review and resolve reported post incidents'],

            // Stories Management
            ['name' => 'View Stories', 'slug' => 'view-stories', 'group' => 'stories', 'description' => 'Monitor user published stories'],
            ['name' => 'Delete Stories', 'slug' => 'delete-stories', 'group' => 'stories', 'description' => 'Remove inappropriate or expired stories'],

            // Communities Management
            ['name' => 'View Communities', 'slug' => 'view-communities', 'group' => 'communities', 'description' => 'Explore communities directory and member lists'],
            ['name' => 'Edit Communities', 'slug' => 'edit-communities', 'group' => 'communities', 'description' => 'Update community configurations and rules'],
            ['name' => 'Manage Community Reports', 'slug' => 'manage-community-reports', 'group' => 'communities', 'description' => 'Resolve community member disputes and moderation logs'],
            ['name' => 'Delete Communities', 'slug' => 'delete-communities', 'group' => 'communities', 'description' => 'Remove abandoned or violating communities'],

            // Business Pages Management
            ['name' => 'View Business Pages', 'slug' => 'view-business-pages', 'group' => 'business_pages', 'description' => 'Browse registered business pages and directories'],
            ['name' => 'Edit Business Pages', 'slug' => 'edit-business-pages', 'group' => 'business_pages', 'description' => 'Update business profile information and categories'],
            ['name' => 'Verify Business Pages', 'slug' => 'verify-business-pages', 'group' => 'business_pages', 'description' => 'Approve or reject business verification applications'],
            ['name' => 'Manage Business Categories', 'slug' => 'manage-business-categories', 'group' => 'business_pages', 'description' => 'Create and reassign business industry categories'],
            ['name' => 'Delete Business Pages', 'slug' => 'delete-business-pages', 'group' => 'business_pages', 'description' => 'Remove business brand accounts'],

            // Ad Campaigns Management
            ['name' => 'View Ad Campaigns', 'slug' => 'view-ad-campaigns', 'group' => 'ad_campaigns', 'description' => 'Review advertising campaigns and budget reports'],
            ['name' => 'Approve Ad Campaigns', 'slug' => 'approve-ad-campaigns', 'group' => 'ad_campaigns', 'description' => 'Approve submitted business advertising campaigns'],
            ['name' => 'Reject Ad Campaigns', 'slug' => 'reject-ad-campaigns', 'group' => 'ad_campaigns', 'description' => 'Reject submitted business advertising campaigns'],
            ['name' => 'Control Ad Campaigns', 'slug' => 'control-ad-campaigns', 'group' => 'ad_campaigns', 'description' => 'Pause, resume, or stop advertising campaigns'],

            // Marketplace Management
            ['name' => 'View Marketplace', 'slug' => 'view-marketplace', 'group' => 'marketplace', 'description' => 'View listed products and seller listings'],
            ['name' => 'Edit Products', 'slug' => 'edit-products', 'group' => 'marketplace', 'description' => 'Update product catalog descriptions and pricing'],
            ['name' => 'Feature Products', 'slug' => 'feature-products', 'group' => 'marketplace', 'description' => 'Toggle featured product badges'],
            ['name' => 'Delete Products', 'slug' => 'delete-products', 'group' => 'marketplace', 'description' => 'Remove prohibited product items'],

            // Events Management
            ['name' => 'View Events', 'slug' => 'view-events', 'group' => 'events', 'description' => 'Monitor public and community events'],
            ['name' => 'Edit Events', 'slug' => 'edit-events', 'group' => 'events', 'description' => 'Modify event dates, locations, and descriptions'],
            ['name' => 'Delete Events', 'slug' => 'delete-events', 'group' => 'events', 'description' => 'Cancel and remove scheduled events'],

            // Reports & Moderation Center
            ['name' => 'View Reports', 'slug' => 'view-reports', 'group' => 'reports', 'description' => 'Access central report queue for all entities'],
            ['name' => 'Resolve Reports', 'slug' => 'resolve-reports', 'group' => 'reports', 'description' => 'Take disciplinary action on submitted reports'],

            // Roles & Permissions (RBAC)
            ['name' => 'View Roles', 'slug' => 'view-roles', 'group' => 'roles', 'description' => 'View admin roles and assignment matrix'],
            ['name' => 'Create Roles', 'slug' => 'create-roles', 'group' => 'roles', 'description' => 'Define custom administrator roles'],
            ['name' => 'Edit Roles', 'slug' => 'edit-roles', 'group' => 'roles', 'description' => 'Modify role permissions and details'],
            ['name' => 'Delete Roles', 'slug' => 'delete-roles', 'group' => 'roles', 'description' => 'Delete non-system custom roles'],
            ['name' => 'Manage Permission Matrix', 'slug' => 'manage-permission-matrix', 'group' => 'roles', 'description' => 'Bulk modify permission matrix across all roles'],
            ['name' => 'Assign Admin Roles', 'slug' => 'assign-admin-roles', 'group' => 'roles', 'description' => 'Assign and change admin user roles'],

            // Notifications & Communications
            ['name' => 'View Notifications', 'slug' => 'view-notifications', 'group' => 'notifications', 'description' => 'View system communications and alert logs'],
            ['name' => 'Send Broadcasts', 'slug' => 'send-broadcasts', 'group' => 'notifications', 'description' => 'Send system-wide broadcast messages to members'],

            // Analytics & Business Intelligence
            ['name' => 'View Analytics', 'slug' => 'view-analytics', 'group' => 'analytics', 'description' => 'Inspect platform growth charts, revenue, and engagement'],
            ['name' => 'Export Analytics', 'slug' => 'export-analytics', 'group' => 'analytics', 'description' => 'Download BI summary reports'],

            // Platform Settings
            ['name' => 'View Settings', 'slug' => 'view-settings', 'group' => 'settings', 'description' => 'Review platform global configuration'],
            ['name' => 'Edit Settings', 'slug' => 'edit-settings', 'group' => 'settings', 'description' => 'Update application branding and limits'],
            ['name' => 'Clear Cache', 'slug' => 'clear-cache', 'group' => 'settings', 'description' => 'Flush system cache and view compiler'],
            ['name' => 'Toggle Maintenance', 'slug' => 'toggle-maintenance', 'group' => 'settings', 'description' => 'Put platform in maintenance mode'],

            // System Tools & Logs
            ['name' => 'View System Logs', 'slug' => 'view-system-logs', 'group' => 'system', 'description' => 'Read Laravel system log files and error traces'],
            ['name' => 'Clear Logs', 'slug' => 'clear-logs', 'group' => 'system', 'description' => 'Truncate or download application log files'],
            ['name' => 'Manage Failed Jobs', 'slug' => 'manage-failed-jobs', 'group' => 'system', 'description' => 'Retry or purge failed background queue jobs'],
            ['name' => 'Run Artisan Commands', 'slug' => 'run-artisan-commands', 'group' => 'system', 'description' => 'Execute authorized maintenance artisan commands'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }

        $allPermissionIds = Permission::pluck('id')->toArray();

        // 1. Super Admin Role (System Protected)
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Unrestricted root administrator with absolute access across all platform modules and system tools.',
                'is_system' => true,
            ]
        );
        $superAdminRole->permissions()->sync($allPermissionIds);

        // 2. Administrator Role
        $adminRole = Role::firstOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'description' => 'Full operational access to manage members, content, business pages, communities, and marketplace.',
                'is_system' => false,
            ]
        );
        $adminPerms = Permission::whereNotIn('group', ['system', 'roles'])->pluck('id')->toArray();
        $adminRole->permissions()->sync($adminPerms);

        // 3. Moderator Role
        $moderatorRole = Role::firstOrCreate(
            ['slug' => 'moderator'],
            [
                'name' => 'Moderator',
                'description' => 'Focused on user safety, review resolution, post moderation, and content safety.',
                'is_system' => false,
            ]
        );
        $moderatorPerms = Permission::whereIn('slug', [
            'view-dashboard',
            'view-members',
            'block-members',
            'view-posts',
            'hide-posts',
            'delete-posts',
            'manage-post-reports',
            'view-stories',
            'delete-stories',
            'view-communities',
            'manage-community-reports',
            'view-reports',
            'resolve-reports',
        ])->pluck('id')->toArray();
        $moderatorRole->permissions()->sync($moderatorPerms);

        // 4. Support Staff Role
        $supportRole = Role::firstOrCreate(
            ['slug' => 'support-staff'],
            [
                'name' => 'Support Staff',
                'description' => 'Customer support role for answering inquiries, verifying accounts, and monitoring user activity.',
                'is_system' => false,
            ]
        );
        $supportPerms = Permission::whereIn('slug', [
            'view-dashboard',
            'view-members',
            'view-posts',
            'view-communities',
            'view-business-pages',
            'verify-business-pages',
            'view-marketplace',
            'view-events',
            'view-reports',
            'resolve-reports',
        ])->pluck('id')->toArray();
        $supportRole->permissions()->sync($supportPerms);

        // Assign Super Admin role to existing admins in `admins` table
        $admins = Admin::all();
        foreach ($admins as $admin) {
            if (!$admin->roles()->where('slug', 'super-admin')->exists()) {
                $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
            }
        }
    }
}
