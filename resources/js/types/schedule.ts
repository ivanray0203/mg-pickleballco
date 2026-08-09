export type DailyBoardStatus = 'available' | 'booked' | 'session' | 'blocked';

export type DailyBoardSlot = {
    hour: string;
    label: string;
    status: DailyBoardStatus;
    detail: string | null;
};

export type DailyBoardCourt = {
    id: number;
    name: string;
    is_active: boolean;
    is_open: boolean;
    hours_label: string | null;
    summary: {
        available: number;
        booked: number;
        session: number;
        blocked: number;
    };
    slots: DailyBoardSlot[];
};

export type DailyBoard = {
    date: string;
    day_label: string;
    courts: DailyBoardCourt[];
};
