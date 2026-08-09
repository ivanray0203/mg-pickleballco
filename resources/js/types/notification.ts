import type { PaginationMeta } from '@/types/pagination';

export type NotificationItem = {
    id: string;
    title: string;
    message: string;
    booking_id: number | null;
    session_id: number | null;
    read_at: string | null;
    created_at: string | null;
};

export type PaginatedNotifications = {
    data: NotificationItem[];
    meta: PaginationMeta;
};
