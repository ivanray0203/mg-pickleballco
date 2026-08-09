export type BookingSlot = {
    time: string;
    label: string;
    available: boolean;
    max_duration: number;
};

export type BookingAvailability = {
    date: string;
    day_label: string;
    court_active: boolean;
    is_open: boolean;
    bookable_date: boolean;
    max_duration: number;
    slots: BookingSlot[];
};

export type BookingConstraints = {
    min_date: string;
    max_date: string;
    max_duration: number;
};
