import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarCheck,
    Check,
    Clock,
    CreditCard,
    Dumbbell,
    LayoutGrid,
    MapPin,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import { AuthModal } from '@/components/auth-modal';
import type { AuthMode } from '@/components/auth-modal';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

const BRAND = 'Skye Paddle';

/** Inline pickleball paddle mark used as the brand logo. */
function PaddleMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            className={className}
            aria-hidden="true"
        >
            {/* Paddle face */}
            <path
                d="M12 1.8c4.3 0 7.4 3.2 7.4 7.3 0 3.9-2.7 6.7-6 7.2v3.1a1.4 1.4 0 0 1-2.8 0v-3.1c-3.3-.5-6-3.3-6-7.2C4.6 5 7.7 1.8 12 1.8Z"
                fill="currentColor"
                opacity="0.15"
            />
            <path
                d="M12 1.8c4.3 0 7.4 3.2 7.4 7.3 0 3.9-2.7 6.7-6 7.2v3.1a1.4 1.4 0 0 1-2.8 0v-3.1c-3.3-.5-6-3.3-6-7.2C4.6 5 7.7 1.8 12 1.8Z"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinejoin="round"
            />
            {/* Holes */}
            <circle cx="9.6" cy="7.6" r="1" fill="currentColor" />
            <circle cx="14.4" cy="7.6" r="1" fill="currentColor" />
            <circle cx="12" cy="10.4" r="1" fill="currentColor" />
        </svg>
    );
}

/** Hero illustration featuring the brand logo. */
function CourtIllustration() {
    return (
        <div className="relative flex aspect-4/3 w-full items-center justify-center">
            <div className="absolute inset-0 rounded-3xl bg-gradient-to-br from-primary/25 via-primary/5 to-transparent blur-2xl" />
            {/* Pure logo with a soft border + shadow */}
            <img
                src="/storage/images/logo.png"
                alt={`${BRAND} pickleball courts logo`}
                className="relative h-full max-h-[26rem] w-auto rounded-3xl border border-border object-contain shadow-2xl"
            />
        </div>
    );
}

type Feature = {
    icon: typeof CalendarCheck;
    title: string;
    body: string;
};

const FEATURES: Feature[] = [
    {
        icon: MapPin,
        title: 'Find your court',
        body: 'Browse indoor and outdoor courts with live availability and clear hourly rates.',
    },
    {
        icon: Clock,
        title: 'Book by the hour',
        body: 'Pick a date and time in seconds. Your slot is held while you check out.',
    },
    {
        icon: Dumbbell,
        title: 'Rent equipment',
        body: 'No paddle or balls? Add rentals to your booking — stock is reserved for your slot.',
    },
    {
        icon: CreditCard,
        title: 'Pay online',
        body: 'Secure GCash checkout via PayMongo. Instant confirmation, no counter queue.',
    },
    {
        icon: ShieldCheck,
        title: 'Guaranteed slot',
        body: 'Every booking is locked to a single court and time — no double-booking, ever.',
    },
    {
        icon: CalendarCheck,
        title: 'Manage bookings',
        body: 'View upcoming games, past sessions and payment history from one dashboard.',
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

export default function Welcome() {
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
                            <span className="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground">
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
                <section className="relative overflow-hidden">
                    <div
                        className="pointer-events-none absolute inset-0 -z-10 opacity-60"
                        style={{
                            background:
                                'radial-gradient(60% 50% at 80% 0%, var(--accent) 0%, transparent 60%)',
                        }}
                    />
                    <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:py-24">
                        <div className="flex flex-col items-start gap-6">
                            <span className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                                <Sparkles className="size-3.5 text-primary" />
                                Book, rent &amp; play in minutes
                            </span>
                            <h1 className="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                Your next{' '}
                                <span className="text-primary">pickleball</span>{' '}
                                game starts here.
                            </h1>
                            <p className="max-w-md text-lg text-muted-foreground">
                                Reserve a court by the hour, add equipment
                                rentals, and pay online — all in one place. No
                                phone calls, no queues.
                            </p>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                {isAuthed ? (
                                    <Button asChild size="lg">
                                        <Link href={dashboard()}>
                                            {primaryLabel}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        size="lg"
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
                                        <Check className="size-4 text-primary" />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <CourtIllustration />
                    </div>
                </section>

                {/* Features */}
                <section
                    id="features"
                    className="border-t border-border bg-muted/30 py-20"
                >
                    <div className="mx-auto w-full max-w-6xl px-6">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Everything you need to play
                            </h2>
                            <p className="mt-3 text-muted-foreground">
                                From finding an open court to gearing up,{' '}
                                {BRAND} handles the whole booking in one smooth
                                flow.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {FEATURES.map((feature) => (
                                <div
                                    key={feature.title}
                                    className="flex flex-col gap-3 rounded-2xl border border-border bg-card p-6 transition-shadow hover:shadow-md"
                                >
                                    <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <feature.icon className="size-5" />
                                    </span>
                                    <h3 className="font-semibold">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {feature.body}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section id="how-it-works" className="py-20">
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
                            <div className="flex flex-col divide-y divide-border">
                                {[
                                    {
                                        name: 'Pro Graphite Paddle',
                                        price: '₱50 / hr',
                                        qty: 2,
                                    },
                                    {
                                        name: 'Ball Set (3)',
                                        price: '₱30 / hr',
                                        qty: 1,
                                    },
                                    {
                                        name: 'Court Shoes',
                                        price: '₱40 / hr',
                                        qty: 0,
                                    },
                                ].map((item) => (
                                    <div
                                        key={item.name}
                                        className="flex items-center justify-between py-3"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {item.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.price}
                                            </p>
                                        </div>
                                        <span
                                            className={
                                                item.qty > 0
                                                    ? 'rounded-lg bg-primary/10 px-2.5 py-1 text-sm font-semibold text-primary'
                                                    : 'text-sm text-muted-foreground'
                                            }
                                        >
                                            {item.qty > 0
                                                ? `× ${item.qty}`
                                                : 'Add'}
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
