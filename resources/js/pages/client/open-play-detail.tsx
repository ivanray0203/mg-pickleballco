import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    CircleCheck,
    Clock,
    LoaderCircle,
    MapPin,
    Users,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { GameReportForm } from '@/components/open-play/game-report-form';
import { PodiumCard } from '@/components/open-play/podium-card';
import { StandingsTable } from '@/components/open-play/standings-table';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index as openPlayIndex, pay, register } from '@/routes/open-play';
import { cancel } from '@/routes/open-play/registrations';
import { store as submitResult } from '@/routes/open-play/submissions';
import type {
    ClientOpenPlaySession,
    OpenPlayParticipant,
    OpenPlayStanding,
    OpenPlayStandingsState,
} from '@/types';

type DetailProps = {
    session: ClientOpenPlaySession;
    standings: OpenPlayStanding[];
    standingsState: OpenPlayStandingsState;
    canReport: boolean;
    participants: OpenPlayParticipant[];
    myRegistrationId: number | null;
};

function InfoRow({
    icon: Icon,
    children,
}: {
    icon: typeof MapPin;
    children: React.ReactNode;
}) {
    return (
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Icon className="size-4 shrink-0" />
            <span>{children}</span>
        </div>
    );
}

export default function OpenPlayDetail({
    session,
    standings,
    standingsState,
    canReport,
    participants,
    myRegistrationId,
}: DetailProps) {
    const [working, setWorking] = useState(false);
    const reg = session.my_registration;
    const awaiting = reg?.awaiting_payment ?? false;

    // While a payment is pending, poll for the webhook-confirmed status.
    useEffect(() => {
        if (!awaiting) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({ only: ['session'] });
        }, 4000);

        return () => clearInterval(interval);
    }, [awaiting]);

    const doRegister = () => {
        setWorking(true);
        router.post(
            register.url({ openPlaySession: session.id }),
            {},
            { onFinish: () => setWorking(false) },
        );
    };

    const doPay = () => {
        setWorking(true);
        router.post(
            pay.url({ openPlaySession: session.id }),
            {},
            { onFinish: () => setWorking(false) },
        );
    };

    const doCancel = () => {
        if (!reg) {
            return;
        }

        setWorking(true);
        router.delete(cancel.url({ openPlayRegistration: reg.id }), {
            preserveScroll: true,
            onFinish: () => setWorking(false),
        });
    };

    return (
        <>
            <Head title={session.title} />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6">
                <Link
                    href={openPlayIndex()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Back to open play
                </Link>

                <Card>
                    <CardContent className="flex flex-col gap-4">
                        <div>
                            <h1 className="text-xl font-semibold">
                                {session.title}
                            </h1>
                            {session.skill_level_label && (
                                <p className="text-sm text-muted-foreground">
                                    {session.skill_level_label} level
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <InfoRow icon={CalendarClock}>
                                {session.session_date_label}
                            </InfoRow>
                            <InfoRow icon={Clock}>
                                {session.start_time} – {session.end_time}
                            </InfoRow>
                            <InfoRow icon={MapPin}>{session.court}</InfoRow>
                            <InfoRow icon={Users}>
                                {session.is_full
                                    ? 'Full'
                                    : `${session.remaining_slots} of ${session.capacity} spots left`}
                            </InfoRow>
                        </div>

                        {session.notes && (
                            <p className="rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                                {session.notes}
                            </p>
                        )}

                        <div className="flex items-center justify-between border-t border-border pt-3">
                            <span className="text-sm text-muted-foreground">
                                Price per player
                            </span>
                            <span className="text-lg font-semibold">
                                {session.price_formatted}
                            </span>
                        </div>

                        <ActionPanel
                            session={session}
                            working={working}
                            onRegister={doRegister}
                            onPay={doPay}
                            onCancel={doCancel}
                        />
                    </CardContent>
                </Card>

                {canReport && myRegistrationId !== null && (
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <h2 className="font-semibold">Report a result</h2>
                            <GameReportForm
                                participants={participants}
                                myRegistrationId={myRegistrationId}
                                submitUrl={submitResult.url({
                                    openPlaySession: session.id,
                                })}
                            />
                        </CardContent>
                    </Card>
                )}

                {standingsState !== null && (
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <div className="flex items-center justify-between">
                                <h2 className="font-semibold">Standings</h2>
                                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    {standingsState === 'final'
                                        ? 'Final'
                                        : 'Live'}
                                </span>
                            </div>
                            {standings.length === 0 ? (
                                <p className="rounded-lg bg-muted/50 p-3 text-center text-sm text-muted-foreground">
                                    Standings appear once games are played.
                                </p>
                            ) : (
                                <>
                                    <PodiumCard standings={standings} />
                                    <StandingsTable standings={standings} />
                                </>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

function ActionPanel({
    session,
    working,
    onRegister,
    onPay,
    onCancel,
}: {
    session: ClientOpenPlaySession;
    working: boolean;
    onRegister: () => void;
    onPay: () => void;
    onCancel: () => void;
}) {
    const reg = session.my_registration;

    // Awaiting payment — poll + let them complete or cancel.
    if (reg?.awaiting_payment) {
        return (
            <div className="flex flex-col gap-3 rounded-lg border border-amber-500/30 bg-amber-500/5 p-4">
                <div className="flex items-center gap-2 text-amber-600">
                    <LoaderCircle className="size-4 animate-spin" />
                    <p className="text-sm font-medium">
                        Payment pending — complete your GCash payment to confirm
                        your spot.
                    </p>
                </div>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <Button
                        onClick={onPay}
                        disabled={working}
                        className="flex-1"
                    >
                        Complete payment
                    </Button>
                    <Button
                        variant="outline"
                        onClick={onCancel}
                        disabled={working}
                        className="flex-1"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    // Confirmed / checked in.
    if (reg?.is_active) {
        return (
            <div className="flex flex-col gap-3 rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-4">
                <div className="flex items-center gap-2 text-emerald-600">
                    <CircleCheck className="size-5" />
                    <p className="text-sm font-medium">
                        You're registered for this session.
                    </p>
                </div>
                <Button variant="outline" onClick={onCancel} disabled={working}>
                    Cancel registration
                </Button>
            </div>
        );
    }

    // Full and not registered.
    if (session.is_full) {
        return (
            <div className="rounded-lg border border-border bg-muted/40 p-4 text-center text-sm text-muted-foreground">
                This session is full. Check back in case a spot opens up.
            </div>
        );
    }

    // Open to register.
    return (
        <Button onClick={onRegister} disabled={working} className="w-full">
            {working && <LoaderCircle className="size-4 animate-spin" />}
            {session.is_free
                ? 'Register'
                : `Register — ${session.price_formatted}`}
        </Button>
    );
}
