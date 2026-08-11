export type AdminSettings = {
    business_name: string;
    contact_email: string | null;
    contact_number: string | null;
    address: string | null;
    booking_hold_minutes: number;
    max_booking_hours: number;
    advance_booking_days: number;
    gcash_enabled: boolean;
};

/** The PayMongo credentials an admin can override from the settings screen. */
export type CredentialKey =
    'paymongo_public_key' | 'paymongo_secret_key' | 'paymongo_webhook_secret';

/**
 * Where a credential currently comes from. The full value is never sent to the
 * browser — only whether one exists and a masked preview of it.
 */
export type CredentialStatus = {
    configured: boolean;
    source: 'database' | 'environment' | null;
    masked: string | null;
};

export type CredentialStatuses = Record<CredentialKey, CredentialStatus>;

export type AuditLogEntry = {
    id: number;
    action: string;
    actor: string;
    entity: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    description: string;
};
