export type Customer = {
    id: number;
    name: string;
    email: string;
    joined_at: string | null;
    total_bookings: number;
    upcoming_bookings: number;
    total_paid_amount: number;
    total_paid_formatted: string;
};

export type CustomerFilters = {
    search: string | null;
};

export type CustomerStats = {
    total_bookings: number;
    upcoming_bookings: number;
    completed_bookings: number;
    cancelled_bookings: number;
    total_paid_amount: number;
    total_paid_formatted: string;
};
