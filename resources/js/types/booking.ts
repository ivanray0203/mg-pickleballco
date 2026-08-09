import type { BookingEquipmentLine } from './equipment';

export type BookingStatus =
    'pending_payment' | 'confirmed' | 'cancelled' | 'completed' | 'expired';

export type BookingCustomer = {
    id: number;
    name: string;
    email: string;
};

export type BookingCourt = {
    id: number;
    name: string;
    slug: string;
    type_label: string;
    image_url: string | null;
};

export type Booking = {
    id: number;
    booking_reference: string;
    booking_date: string;
    start_time: string;
    end_time: string;
    duration_hours: number;
    hourly_rate: number;
    hourly_rate_formatted: string;
    total_amount: number;
    total_amount_formatted: string;
    status: BookingStatus;
    status_label: string;
    is_cancellable: boolean;
    payment_status: string | null;
    payment_status_label: string | null;
    confirmed_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    expires_at: string | null;
    created_at: string | null;
    customer: BookingCustomer;
    court: BookingCourt;
    equipment: BookingEquipmentLine[];
};

export type BookingFilters = {
    search: string | null;
    date: string | null;
    court: string | null;
    status: string | null;
};

export type DailyScheduleStatus = 'available' | 'blocked' | 'booked' | 'closed';

export type DailyScheduleCell = {
    court_id: number;
    status: DailyScheduleStatus;
    reason: string | null;
};

export type DailyScheduleRow = {
    hour: string;
    label: string;
    cells: DailyScheduleCell[];
};

export type DailySchedule = {
    date: string;
    day_label: string;
    courts: Array<{ id: number; name: string }>;
    rows: DailyScheduleRow[];
};
