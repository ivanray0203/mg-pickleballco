export type EquipmentType = 'paddle' | 'ball' | 'shoes' | 'other';

export type Equipment = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: EquipmentType;
    type_label: string;
    hourly_rate: number;
    hourly_rate_formatted: string;
    total_quantity: number;
    is_active: boolean;
    image_url: string | null;
    created_at: string | null;
};

export type EquipmentFilters = {
    search: string | null;
    type: string | null;
    status: string | null;
};

/**
 * A single equipment item as offered to a customer during booking, with the
 * number of units free for the selected time range.
 */
export type EquipmentOption = {
    id: number;
    name: string;
    type: EquipmentType;
    type_label: string;
    hourly_rate: number;
    hourly_rate_formatted: string;
    available: number;
    image_url: string | null;
};

/**
 * An equipment rental line as stored on a booking.
 */
export type BookingEquipmentLine = {
    id: number;
    name: string;
    quantity: number;
    unit_hourly_rate: number;
    unit_hourly_rate_formatted: string;
    line_total: number;
    line_total_formatted: string;
};
