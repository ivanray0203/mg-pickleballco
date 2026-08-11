export type CourtType = 'indoor' | 'outdoor';

export type Court = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: CourtType;
    type_label: string;
    hourly_rate: number;
    hourly_rate_formatted: string;
    opening_time: string;
    closing_time: string;
    opening_hours: string;
    is_active: boolean;
    /** False when the court has bookings or open play sessions tied to it. */
    can_delete: boolean;
    image_url: string | null;
    created_at: string | null;
};

export type SelectOption = {
    value: string;
    label: string;
};

export type CourtFilters = {
    search: string | null;
    type: string | null;
    status: string | null;
};

export type CourtScheduleDay = {
    id: number;
    day_of_week: number;
    day_label: string;
    is_open: boolean;
    opening_time: string | null;
    closing_time: string | null;
};

export type CourtBlock = {
    id: number;
    court_id: number;
    block_date: string;
    start_time: string;
    end_time: string;
    reason: string;
    reason_label: string;
    notes: string | null;
    created_by: number | null;
};

export type AvailabilityStatus = 'available' | 'blocked' | 'booked';

export type AvailabilitySlot = {
    start_time: string;
    end_time: string;
    label: string;
    status: AvailabilityStatus;
    reason: string | null;
};

export type DayAvailability = {
    date: string;
    day_of_week: number;
    day_label: string;
    court_active: boolean;
    is_open: boolean;
    opening_time: string | null;
    closing_time: string | null;
    slots: AvailabilitySlot[];
};
