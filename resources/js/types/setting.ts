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

export type AuditLogEntry = {
    id: number;
    action: string;
    actor: string;
    entity: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    description: string;
};
