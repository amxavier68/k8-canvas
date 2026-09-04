<?php

defined('ABSPATH') || exit;

final class K8_Canvas_Access
{
    public static function is_platform_administrator(): bool
    {
        return current_user_can('manage_options');
    }

    public static function has_active_grant(): bool
    {
        if (self::is_platform_administrator()) return true;
        foreach (self::memberships() as $membership) {
            if (!empty($membership['profile_key'])) return true;
        }
        return false;
    }

    public static function memberships(int $user_id = 0): array
    {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        $t = K8_Canvas_Schema::tables();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*,o.name organisation_name,o.organisation_type,p.profile_key,p.name profile_name,p.permissions,g.boundary_type,g.boundary_id
             FROM {$t['memberships']} m
             JOIN {$t['organisations']} o ON o.id=m.organisation_id
             LEFT JOIN {$t['permission_grants']} g ON g.membership_id=m.id AND g.revoked_at IS NULL
             LEFT JOIN {$t['permission_profiles']} p ON p.id=g.permission_profile_id
             WHERE m.user_id=%d AND m.status='active' ORDER BY o.name,p.name",
            $user_id
        ), ARRAY_A);
    }

    public static function organisation_ids(string $permission): array
    {
        if (self::is_platform_administrator()) {
            global $wpdb;
            $table = K8_Canvas_Schema::tables()['organisations'];
            return array_map('intval', $wpdb->get_col("SELECT id FROM $table WHERE status='active'"));
        }
        $ids = [];
        foreach (self::memberships() as $membership) {
            if ($membership['boundary_type'] !== 'organisation' || !self::permission_matches($permission, (string) $membership['permissions'])) {
                continue;
            }
            $ids[] = (int) $membership['boundary_id'];
        }
        return self::expand_managed_organisations(array_values(array_unique($ids)));
    }

    public static function allows(string $permission, string $boundary_type, int $boundary_id): bool
    {
        if (self::is_platform_administrator()) {
            return true;
        }
        foreach (self::memberships() as $membership) {
            if ($membership['boundary_type'] !== $boundary_type || (int) $membership['boundary_id'] !== $boundary_id) {
                continue;
            }
            if (self::permission_matches($permission, (string) $membership['permissions'])) return true;
        }
        return false;
    }

    public static function allows_organisation(string $permission, int $organisation_id): bool
    {
        return $organisation_id > 0 && in_array($organisation_id, self::organisation_ids($permission), true);
    }

    public static function allows_site(string $permission, int $site_id): bool
    {
        global $wpdb;
        $site_table = K8_Canvas_Schema::tables()['sites'];
        $organisation_id = (int) $wpdb->get_var($wpdb->prepare("SELECT owning_organisation_id FROM $site_table WHERE id=%d AND status='active'", $site_id));
        return $organisation_id > 0 && self::allows_organisation($permission, $organisation_id);
    }

    private static function permission_matches(string $permission, string $encoded_permissions): bool
    {
        foreach (json_decode($encoded_permissions, true) ?: [] as $granted) {
            if ($granted === $permission || (str_ends_with($granted, '.*') && str_starts_with($permission, substr($granted, 0, -1)))) return true;
        }
        return false;
    }

    private static function expand_managed_organisations(array $organisation_ids): array
    {
        global $wpdb;
        $table = K8_Canvas_Schema::tables()['relationships'];
        $resolved = array_values(array_unique(array_map('intval', $organisation_ids)));
        $frontier = $resolved;
        for ($depth = 0; $depth < 10 && $frontier; $depth++) {
            $placeholders = implode(',', array_fill(0, count($frontier), '%d'));
            $children = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT managed_organisation_id FROM $table WHERE status='active' AND managing_organisation_id IN ($placeholders)", ...$frontier)));
            $frontier = array_values(array_diff(array_unique($children), $resolved));
            $resolved = array_values(array_unique(array_merge($resolved, $frontier)));
        }
        return $resolved;
    }

    public static function audit(string $action, string $resource_type, int $resource_id, ?int $organisation_id = null, array $metadata = []): bool
    {
        global $wpdb;
        return $wpdb->insert(K8_Canvas_Schema::tables()['audit_events'], [
            'request_id' => wp_generate_uuid4(),
            'actor_user_id' => get_current_user_id(),
            'action_key' => sanitize_text_field($action),
            'resource_type' => sanitize_key($resource_type),
            'resource_id' => $resource_id,
            'organisation_id' => $organisation_id,
            'metadata' => $metadata ? wp_json_encode($metadata) : null,
            'occurred_at' => current_time('mysql', true),
        ], ['%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s']) !== false;
    }
}
