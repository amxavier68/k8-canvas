<?php

defined('ABSPATH') || exit;

final class K8_Canvas_Access
{
    public static function is_platform_administrator(): bool
    {
        return current_user_can('manage_options');
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

    public static function allows(string $permission, string $boundary_type, int $boundary_id): bool
    {
        if (self::is_platform_administrator()) {
            return true;
        }
        foreach (self::memberships() as $membership) {
            if ($membership['boundary_type'] !== $boundary_type || (int) $membership['boundary_id'] !== $boundary_id) {
                continue;
            }
            $permissions = json_decode((string) $membership['permissions'], true) ?: [];
            foreach ($permissions as $granted) {
                if ($granted === $permission || (str_ends_with($granted, '.*') && str_starts_with($permission, substr($granted, 0, -1)))) {
                    return true;
                }
            }
        }
        return false;
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
