export type DashboardStats = {
    today_bookings: number;
    today_revenue: number;
    today_revenue_formatted: string;
    active_courts: number;
    pending_payments: number;
};

export type ReportSummary = {
    total_revenue: number;
    total_revenue_formatted: string;
    total_bookings: number;
    confirmed_bookings: number;
    cancelled_bookings: number;
    average_booking_value: number;
    average_booking_value_formatted: string;
};

export type ReportData = {
    summary: ReportSummary;
    revenue_by_court: Array<{
        court: string;
        amount: number;
        amount_formatted: string;
    }>;
    bookings_by_court: Array<{ court: string; count: number }>;
    bookings_by_day: Array<{ date: string; count: number }>;
    peak_hours: Array<{ hour: string; label: string; count: number }>;
};

export type ReportRange = 'today' | 'week' | 'month' | 'custom';

export type ReportFilters = {
    range: ReportRange;
    from: string;
    to: string;
};
