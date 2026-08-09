import {
    CalendarCheck,
    CreditCard,
    LayoutGrid,
    LandPlot,
    Trophy,
    Volleyball,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as bookingsIndex } from '@/routes/bookings';
import { index as courtsIndex } from '@/routes/courts';
import { index as leaderboardIndex } from '@/routes/leaderboard';
import { index as openPlayIndex } from '@/routes/open-play';
import { index as paymentsIndex } from '@/routes/payments';
import type { NavItem } from '@/types';

/**
 * Primary customer navigation, shared by the desktop header and the mobile
 * drawer so the two never drift apart.
 */
export const clientNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Book a Court', href: courtsIndex(), icon: LandPlot },
    { title: 'Open Play', href: openPlayIndex(), icon: Volleyball },
    { title: 'Leaderboard', href: leaderboardIndex(), icon: Trophy },
    { title: 'My Bookings', href: bookingsIndex(), icon: CalendarCheck },
    { title: 'Payments', href: paymentsIndex(), icon: CreditCard },
];
