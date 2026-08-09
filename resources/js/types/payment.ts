export type PaymentStatus =
    'pending' | 'paid' | 'failed' | 'expired' | 'refunded';

export type PaymentBooking = {
    id: number;
    reference: string;
    date: string;
    start_time: string;
    end_time: string;
    court: string;
    customer: {
        name: string;
        email: string;
    };
};

export type Payment = {
    id: number;
    payment_reference: string;
    provider: string;
    provider_label: string;
    provider_reference: string | null;
    payment_method: string;
    payment_method_label: string;
    amount: number;
    amount_formatted: string;
    status: PaymentStatus;
    status_label: string;
    paid_at: string | null;
    failed_at: string | null;
    expires_at: string | null;
    created_at: string | null;
    booking?: PaymentBooking;
};

export type PaymentFilters = {
    search: string | null;
    status: string | null;
    method: string | null;
    date: string | null;
};
