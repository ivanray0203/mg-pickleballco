import {
    BarChart3,
    CalendarCheck,
    CalendarRange,
    CreditCard,
    Dumbbell,
    LandPlot,
    LayoutGrid,
    Settings,
    Trophy,
    Users,
    Volleyball,
} from 'lucide-react';
import { dashboard } from '@/routes/admin';
import { index as bookingsIndex } from '@/routes/admin/bookings';
import { index as courtsIndex } from '@/routes/admin/courts';
import { index as customersIndex } from '@/routes/admin/customers';
import { index as equipmentIndex } from '@/routes/admin/equipment';
import { index as openPlayIndex } from '@/routes/admin/open-play';
import { index as paymentsIndex } from '@/routes/admin/payments';
import { index as reportsIndex } from '@/routes/admin/reports';
import { index as scheduleIndex } from '@/routes/admin/schedule';
import { index as seasonsIndex } from '@/routes/admin/seasons';
import { edit as settingsEdit } from '@/routes/admin/settings';
import type { NavItem } from '@/types';

/**
 * Primary navigation for the admin panel.
 *
 * Items without a built feature yet point at their eventual paths and act as
 * placeholders until each is implemented.
 */
export const adminNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Courts', href: courtsIndex(), icon: LandPlot },
    { title: 'Equipment', href: equipmentIndex(), icon: Dumbbell },
    { title: 'Daily schedule', href: scheduleIndex(), icon: CalendarRange },
    { title: 'Open play', href: openPlayIndex(), icon: Volleyball },
    { title: 'Seasons', href: seasonsIndex(), icon: Trophy },
    { title: 'Bookings', href: bookingsIndex(), icon: CalendarCheck },
    { title: 'Payments', href: paymentsIndex(), icon: CreditCard },
    { title: 'Customers', href: customersIndex(), icon: Users },
    { title: 'Reports', href: reportsIndex(), icon: BarChart3 },
    { title: 'Settings', href: settingsEdit(), icon: Settings },
];
