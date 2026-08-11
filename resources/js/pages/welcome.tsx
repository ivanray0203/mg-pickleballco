import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    CalendarDays,
    Check,
    CircleCheck,
    CreditCard,
    Dumbbell,
    LayoutGrid,
    ShoppingBag,
    Sparkles,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { AuthModal } from '@/components/auth-modal';
import type { AuthMode } from '@/components/auth-modal';
import PaddleMark from '@/components/paddle-mark';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { Equipment } from '@/types/equipment';

const BRAND = 'Pickleball Booking System';

/**
 * Hero illustration: a pickleball court rendered as a tilted 3D slab with a
 * ball rallying across the net. Motion comes from the shared `.pickleball-ball`
 * animation in app.css, which pauses under `prefers-reduced-motion`.
 */
function CourtIllustration() {
    return (
        <div className="relative w-full">
            <div
                className="relative w-full perspective-[1600px]"
                role="img"
                aria-label={`${BRAND} — a pickleball rallying across a court`}
            >
                <div
                    className="relative aspect-16/10 w-full transform-[rotateX(17deg)_rotateZ(-3deg)] transform-3d"
                    aria-hidden="true"
                >
                    {/* The slab's side wall, showing the court's thickness. */}
                    <div className="absolute inset-0 translate-y-3.5 rounded-4xl bg-blue-800 shadow-2xl shadow-primary/30" />

                    {/* Playing surface. */}
                    <div className="absolute inset-0 overflow-hidden rounded-4xl bg-gradient-to-br from-primary to-blue-700">
                        {/* Court boundary and interior lines. */}
                        <div className="absolute inset-[9%] border-2 border-white/90">
                            {/* Non-volley zones — the "kitchen" — either side of the net. */}
                            <div className="absolute inset-y-0 left-1/2 w-[15%] -translate-x-full border-r-2 border-white/80" />
                            <div className="absolute inset-y-0 left-1/2 w-[15%] border-l-2 border-white/80" />
                            {/* Center service lines on each half. */}
                            <div className="absolute top-1/2 left-0 h-0.5 w-[35%] bg-white/80" />
                            <div className="absolute top-1/2 right-0 h-0.5 w-[35%] bg-white/80" />
                        </div>

                        {/* The net, laid flat across the middle of the surface. */}
                        <div className="absolute inset-y-[6%] left-1/2 w-1 -translate-x-1/2 rounded-full bg-slate-800/80" />

                        {/* The rallying ball, with its shadow tracking underneath. */}
                        <span
                            className="pickleball-ball absolute z-10 size-6 rounded-full bg-yellow-300 shadow-lg ring-2 ring-yellow-500/80"
                            style={{ top: '28%', left: '14%' }}
                        >
                            {/* Perforations that make the ball read as a pickleball. */}
                            <span className="absolute top-[22%] left-[26%] size-[14%] rounded-full bg-yellow-600/70" />
                            <span className="absolute top-[46%] left-[58%] size-[14%] rounded-full bg-yellow-600/70" />
                            <span className="absolute top-[68%] left-[32%] size-[14%] rounded-full bg-yellow-600/70" />
                            <span className="pickleball-ball-shadow absolute -bottom-2 left-1/2 h-1 w-[120%] -translate-x-1/2 rounded-full bg-black/40 blur-[2px]" />
                        </span>
                    </div>

                    {/* Net post, counter-rotated so it stands upright off the slab. */}
                    <div className="absolute top-[6%] left-1/2 h-[22%] w-1 origin-bottom -translate-x-1/2 transform-[rotateX(-17deg)] rounded-t-full bg-slate-800" />
                </div>
            </div>

            {/* Social-proof badge, overlapping the near corner of the court. */}
            <div className="absolute right-2 -bottom-4 flex size-24 flex-col items-center justify-center gap-1 rounded-full bg-card px-4 text-center shadow-xl ring-1 ring-border sm:right-6">
                <Users className="size-5 text-primary" />
                <span className="text-[10px] leading-tight font-medium text-muted-foreground">
                    Join thousands of pickleball players
                </span>
            </div>
        </div>
    );
}

type Feature = {
    icon: typeof CalendarDays;
    title: string;
    body: string;
};

const FEATURES: Feature[] = [
    {
        icon: CalendarDays,
        title: 'Book Instantly',
        body: 'Reserve courts in just a few taps. Real-time availability.',
    },
    {
        icon: ShoppingBag,
        title: 'Rent Equipment',
        body: 'Paddles, balls, and more. Add to your booking.',
    },
    {
        icon: CreditCard,
        title: 'Pay Securely',
        body: 'Safe & fast payments via GCash.',
    },
    {
        icon: Bell,
        title: 'Get Confirmed',
        body: 'Instant booking confirmation and reminders.',
    },
];

const STEPS = [
    {
        title: 'Choose a court',
        body: 'Pick from available indoor or outdoor courts near you.',
    },
    {
        title: 'Select your time',
        body: 'Grab an open slot and add any equipment you need.',
    },
    {
        title: 'Pay & play',
        body: 'Check out with GCash and get instant confirmation.',
    },
];

type WelcomeProps = {
    /** Active rentals previewed in the equipment section. */
    equipment: { data: Equipment[] };
};

export default function Welcome({ equipment }: WelcomeProps) {
    const { auth } = usePage().props;
    const isAuthed = Boolean(auth.user);

    const [authOpen, setAuthOpen] = useState(false);
    const [authMode, setAuthMode] = useState<AuthMode>('login');

    const openAuth = (mode: AuthMode) => {
        setAuthMode(mode);
        setAuthOpen(true);
    };

    const primaryLabel = isAuthed ? 'Go to dashboard' : 'Get started free';

    return (
        <>
            <Head title="Book pickleball courts online">
                <meta
                    name="description"
                    content="Reserve pickleball courts by the hour, rent equipment, and pay online in seconds."
                />
            </Head>

            <div className="min-h-screen bg-background text-foreground">
                {/* Nav */}
                <header className="sticky top-0 z-50 border-b border-border/60 bg-background/80 backdrop-blur">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-6">
                        <Link
                            href="/"
                            className="flex items-center gap-2 font-semibold"
                        >
                            <span className="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <PaddleMark className="size-5" />
                            </span>
                            <span className="font-brand text-2xl text-primary">
                                {BRAND}
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-8 text-sm text-muted-foreground md:flex">
                            <a
                                href="#features"
                                className="hover:text-foreground"
                            >
                                Features
                            </a>
                            <a
                                href="#how-it-works"
                                className="hover:text-foreground"
                            >
                                How it works
                            </a>
                            <a
                                href="#equipment"
                                className="hover:text-foreground"
                            >
                                Equipment
                            </a>
                        </nav>

                        <div className="flex items-center gap-2">
                            {isAuthed ? (
                                <Button asChild size="sm">
                                    <Link href={dashboard()}>
                                        <LayoutGrid className="size-4" />
                                        Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="hidden sm:inline-flex"
                                        onClick={() => openAuth('login')}
                                    >
                                        Log in
                                    </Button>
                                    <Button
                                        size="sm"
                                        onClick={() => openAuth('register')}
                                    >
                                        Get started
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                {/* Hero */}
                <section className="relative overflow-hidden bg-gradient-to-b from-primary/7 via-primary/3 to-background">
                    {/* Decorative background: soft blobs, dot grids and a
                        pickleball peeking in from the left edge. */}
                    <div
                        className="pointer-events-none absolute inset-0"
                        aria-hidden="true"
                    >
                        <div className="absolute -top-24 right-1/4 size-96 rounded-full bg-primary/6" />
                        <div className="absolute right-0 bottom-0 size-120 translate-x-1/4 translate-y-1/3 rounded-full bg-primary/5" />
                        <div
                            className="absolute top-16 right-16 h-16 w-24 text-primary/25"
                            style={{
                                backgroundImage:
                                    'radial-gradient(currentColor 1.5px, transparent 1.5px)',
                                backgroundSize: '14px 14px',
                            }}
                        />
                        <div
                            className="absolute right-24 bottom-16 h-6 w-32 text-primary/25"
                            style={{
                                backgroundImage:
                                    'radial-gradient(currentColor 1.5px, transparent 1.5px)',
                                backgroundSize: '16px 16px',
                            }}
                        />
                        <div className="absolute bottom-16 -left-8 size-20 rounded-full border-2 border-primary/10 bg-card/70">
                            <span className="absolute top-[30%] left-[45%] size-2 rounded-full bg-primary/15" />
                            <span className="absolute top-[55%] left-[65%] size-2 rounded-full bg-primary/15" />
                            <span className="absolute top-[70%] left-[40%] size-2 rounded-full bg-primary/15" />
                        </div>
                    </div>

                    <div className="relative mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-[1fr_1.1fr] lg:py-24">
                        <div className="flex flex-col items-start gap-6">
                            <span className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3.5 py-1.5 text-xs font-medium">
                                <Sparkles className="size-3.5 text-primary" />
                                Book, rent &amp; play in minutes
                            </span>
                            <h1 className="text-4xl leading-[1.08] font-bold tracking-tight sm:text-5xl lg:text-[3.5rem]">
                                Your next
                                <br />
                                <span className="text-primary">pickleball</span>
                                <br />
                                game starts here.
                            </h1>
                            <p className="max-w-md text-lg text-muted-foreground">
                                Reserve a court by the hour, add equipment
                                rentals, and pay online — all in one place. No
                                phone calls, no queues.
                            </p>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                {isAuthed ? (
                                    <Button asChild size="lg" className="h-11">
                                        <Link href={dashboard()}>
                                            {primaryLabel}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        size="lg"
                                        className="h-11"
                                        onClick={() => openAuth('register')}
                                    >
                                        {primaryLabel}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                )}
                                {!isAuthed && (
                                    <Button
                                        size="lg"
                                        variant="outline"
                                        className="h-11"
                                        onClick={() => openAuth('login')}
                                    >
                                        I already have an account
                                    </Button>
                                )}
                            </div>
                            <ul className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                                {[
                                    'Instant confirmation',
                                    'Secure GCash payment',
                                    'Free to sign up',
                                ].map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-center gap-1.5"
                                    >
                                        <CircleCheck className="size-4 text-primary" />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <CourtIllustration />
                    </div>
                </section>

                {/* Features */}
                <section id="features" className="py-20">
                    <div className="mx-auto w-full max-w-6xl px-6">
                        <div className="text-center">
                            <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                Features
                            </p>
                            <h2 className="mt-3 text-3xl font-bold tracking-tight">
                                Everything you need to play
                            </h2>
                            <span className="mx-auto mt-4 block h-1 w-12 rounded-full bg-primary" />
                        </div>

                        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {FEATURES.map((feature) => (
                                <div
                                    key={feature.title}
                                    className="flex items-start gap-3.5 rounded-xl border border-border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <feature.icon className="size-5" />
                                    </span>
                                    <div className="flex flex-col gap-1">
                                        <h3 className="text-[0.9375rem] font-semibold">
                                            {feature.title}
                                        </h3>
                                        <p className="text-[0.8125rem] leading-snug text-muted-foreground">
                                            {feature.body}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section
                    id="how-it-works"
                    className="border-t border-border py-20"
                >
                    <div className="mx-auto w-full max-w-6xl px-6">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Book in three simple steps
                            </h2>
                            <p className="mt-3 text-muted-foreground">
                                On the court in under a minute.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-8 md:grid-cols-3">
                            {STEPS.map((step, index) => (
                                <div
                                    key={step.title}
                                    className="relative flex flex-col gap-3"
                                >
                                    <span className="flex size-12 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground">
                                        {index + 1}
                                    </span>
                                    <h3 className="text-lg font-semibold">
                                        {step.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {step.body}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Equipment highlight */}
                <section
                    id="equipment"
                    className="border-t border-border bg-muted/30 py-20"
                >
                    <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
                        <div className="flex flex-col gap-5">
                            <span className="inline-flex w-fit items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                <Dumbbell className="size-3.5" />
                                Gear on demand
                            </span>
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Forgot your paddle? We&apos;ve got you.
                            </h2>
                            <p className="text-muted-foreground">
                                Add paddles, balls and more to your booking at
                                checkout. Stock is reserved just for your time
                                slot, so what you see is what you get — priced
                                by the hour, right alongside your court.
                            </p>
                            <ul className="grid gap-3">
                                {[
                                    'Real-time availability per time slot',
                                    'Choose exactly how many you need',
                                    'Charged together with your court',
                                ].map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Check className="size-4 text-primary" />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                            <p className="mb-4 text-sm font-medium text-muted-foreground">
                                Add to your booking
                            </p>
                            {equipment.data.length === 0 && (
                                <p className="py-3 text-sm text-muted-foreground">
                                    Rentals are being restocked — check back
                                    soon.
                                </p>
                            )}
                            <div className="flex flex-col divide-y divide-border">
                                {equipment.data.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {item.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.hourly_rate_formatted} /
                                                hr &middot; {item.type_label}
                                            </p>
                                        </div>
                                        <span className="shrink-0 rounded-lg bg-primary/10 px-2.5 py-1 text-sm font-semibold text-primary">
                                            Add
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="py-20">
                    <div className="mx-auto w-full max-w-6xl px-6">
                        <div className="relative overflow-hidden rounded-3xl bg-primary px-8 py-14 text-center text-primary-foreground shadow-lg sm:px-16">
                            <div
                                className="pointer-events-none absolute inset-0 opacity-20"
                                style={{
                                    background:
                                        'radial-gradient(40% 60% at 15% 20%, white 0%, transparent 60%)',
                                }}
                            />
                            <h2 className="relative text-3xl font-bold tracking-tight sm:text-4xl">
                                Ready to hit the court?
                            </h2>
                            <p className="relative mx-auto mt-3 max-w-md text-primary-foreground/80">
                                Create your free account and book your first
                                game today.
                            </p>
                            <div className="relative mt-8 flex justify-center">
                                {isAuthed ? (
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="secondary"
                                    >
                                        <Link href={dashboard()}>
                                            {primaryLabel}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        size="lg"
                                        variant="secondary"
                                        onClick={() => openAuth('register')}
                                    >
                                        {primaryLabel}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border py-10">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-4 px-6 sm:flex-row">
                        <Link
                            href="/"
                            className="flex items-center gap-2 font-semibold"
                        >
                            <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <PaddleMark className="size-4" />
                            </span>
                            <span className="font-brand text-xl text-primary">
                                {BRAND}
                            </span>
                        </Link>
                        <p className="text-sm text-muted-foreground">
                            © {new Date().getFullYear()} {BRAND} Pickleball. All
                            rights reserved.
                        </p>
                    </div>
                </footer>
            </div>

            <AuthModal
                open={authOpen}
                mode={authMode}
                onOpenChange={setAuthOpen}
                onModeChange={setAuthMode}
            />
        </>
    );
}
