<?php
declare(strict_types=1);

if (!function_exists('admin_cloud_status_from_license')) {
    function admin_cloud_status_from_license(string $storedStatus, ?string $expiresAt): string
    {
        if ($storedStatus === 'suspendida') {
            return 'suspended';
        }

        $currentStatus = license_current_status($storedStatus, $expiresAt);
        if ($currentStatus === 'vencida') {
            return 'expired';
        }

        return 'active';
    }
}

if (!function_exists('admin_cloud_status_message')) {
    function admin_cloud_status_message(string $cloudStatus): string
    {
        return match ($cloudStatus) {
            'suspended' => 'Licencia suspendida desde el panel FLUS.',
            'expired' => 'Licencia vencida.',
            'revoked' => 'Licencia revocada.',
            default => '',
        };
    }
}

if (!function_exists('admin_cloud_next_check_at')) {
    function admin_cloud_next_check_at(): string
    {
        $licenseConfig = admin_config('license', []);
        $interval = max(30, (int) ($licenseConfig['cloud_check_interval_sec'] ?? 300));

        return gmdate(DATE_ATOM, time() + $interval);
    }
}

if (!function_exists('admin_build_cloud_license_payload')) {
    function admin_build_cloud_license_payload(array $license, array $request = []): array
    {
        $cloudStatus = admin_cloud_status_from_license(
            (string) ($license['status'] ?? ''),
            isset($license['expires_at']) ? (string) $license['expires_at'] : null
        );

        return [
            'license_key' => (string) ($license['license_key'] ?? ''),
            'installation_id' => trim((string) ($request['installation_id'] ?? '')),
            'status' => $cloudStatus,
            'plan' => (string) ($license['plan_type'] ?? ''),
            'expires_at' => (string) ($license['expires_at'] ?? ''),
            'checked_at' => gmdate(DATE_ATOM),
            'next_check_at' => admin_cloud_next_check_at(),
            'message' => admin_cloud_status_message($cloudStatus),
        ];
    }
}

if (!function_exists('admin_build_signed_cloud_license_document')) {
    function admin_build_signed_cloud_license_document(array $payload): array
    {
        $canonicalPayload = license_canonical_json($payload);

        return [
            'format' => 'FLUS-CLOUD-LICENSE-1',
            'issuer' => (string) (admin_config('license', [])['issuer'] ?? 'FLUS'),
            'alg' => 'RSA-SHA256',
            'payload_b64' => base64_encode($canonicalPayload),
            'sig_b64' => sign_license_payload($canonicalPayload),
            'pubkey_sha256' => license_public_key_sha256(),
        ];
    }
}
