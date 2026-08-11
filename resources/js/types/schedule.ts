export type ScheduleCellStatus =
    'available' | 'booked' | 'session' | 'blocked' | 'closed';

export type WeekBoardCell = {
    status: ScheduleCellStatus;
    /** Secondary line inside the cell, e.g. an open play session's "24/28". */
    detail: string | null;
    /** Hover text: booking reference, session title or block reason. */
    title: string | null;
};

export type WeekBoardDay = {
    date: string;
    weekday_label: string;
    day_label: string;
    is_today: boolean;
};

export type WeekBoardHour = {
    hour: string;
    label: string;
};

export type WeekBoardCourt = {
    id: number;
    name: string;
    type_label: string;
    is_active: boolean;
    summary: {
        available: number;
        booked: number;
        session: number;
    };
    /** Keyed by date ("YYYY-MM-DD"), then by hour ("HH:00"). */
    cells: Record<string, Record<string, WeekBoardCell>>;
};

export type WeekBoard = {
    start_date: string;
    end_date: string;
    range_label: string;
    prev_date: string;
    next_date: string;
    today: string;
    days: WeekBoardDay[];
    hours: WeekBoardHour[];
    courts: WeekBoardCourt[];
};
