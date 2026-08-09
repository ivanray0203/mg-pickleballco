export type OpenPlaySessionStatus =
    'scheduled' | 'open' | 'full' | 'in_progress' | 'completed' | 'cancelled';

export type OpenPlaySession = {
    id: number;
    title: string;
    court: { id: number; name: string };
    court_id: number;
    session_date: string;
    session_date_label: string;
    start_time: string;
    end_time: string;
    time_range: string;
    capacity: number;
    court_count: number;
    occupied_slots: number;
    remaining_slots: number;
    skill_level: string | null;
    skill_level_label: string | null;
    price: number;
    price_formatted: string;
    is_free: boolean;
    status: OpenPlaySessionStatus;
    status_label: string;
    notes: string | null;
    created_at: string | null;
};

export type OpenPlaySessionFilters = {
    search: string | null;
    status: string | null;
    court: string | null;
};

export type OpenPlayMyRegistration = {
    id: number;
    status: string;
    status_label: string;
    is_active: boolean;
    awaiting_payment: boolean;
};

export type ClientOpenPlaySession = {
    id: number;
    title: string;
    court: string;
    session_date: string;
    session_date_label: string;
    start_time: string;
    end_time: string;
    skill_level_label: string | null;
    price_formatted: string;
    is_free: boolean;
    capacity: number;
    remaining_slots: number;
    is_full: boolean;
    notes: string | null;
    my_registration: OpenPlayMyRegistration | null;
};

export type ClientOpenPlayFilters = {
    skill_level: string | null;
    date: string | null;
};

export type OpenPlayRegistrationRow = {
    id: number;
    user: { id: number; name: string };
    skill_level_label: string | null;
    status: string;
    status_label: string;
    checked_in: boolean;
    checked_in_at: string | null;
};

export type OpenPlayGamePlayerRow = {
    registration_id: number;
    name: string;
};

export type OpenPlayGameRow = {
    id: number;
    team_a_score: number;
    team_b_score: number;
    winning_team: 'a' | 'b';
    played_at_label: string | null;
    team_a: OpenPlayGamePlayerRow[];
    team_b: OpenPlayGamePlayerRow[];
};

export type OpenPlayStanding = {
    rank: number;
    registration_id: number;
    name: string;
    wins: number;
    losses: number;
    games_played: number;
    points_for: number;
    points_against: number;
    point_diff: number;
};

export type OpenPlayStandingsState = 'live' | 'final' | null;

export type OpenPlayParticipant = {
    registration_id: number;
    name: string;
};

export type OpenPlayPendingSubmission = {
    id: number;
    reporter_name: string;
    team_a: string[];
    team_b: string[];
    team_a_score: number;
    team_b_score: number;
};

export type OpenPlayMatchupRow = {
    id: number;
    status: 'queued' | 'playing' | 'done';
    status_label: string;
    court_label: string | null;
    team_a: string[];
    team_b: string[];
};

export type Season = {
    id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    date_range_label: string;
    is_active: boolean;
};

/**
 * A season leaderboard row. Reuses the per-session standing shape so the
 * podium/table components are shared. Skill filtering happens server-side.
 */
export type LeaderboardStanding = OpenPlayStanding;
