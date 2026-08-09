import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { OpenPlaySession, SelectOption } from '@/types';

const ALL_LEVELS = 'all';

type SessionFormData = {
    court_id: string;
    title: string;
    session_date: string;
    start_time: string;
    end_time: string;
    capacity: string;
    court_count: string;
    skill_level: string;
    price: string;
    notes: string;
};

type OpenPlaySessionFormProps = {
    session?: OpenPlaySession;
    courts: SelectOption[];
    skillLevels: SelectOption[];
    submitUrl: string;
    method: 'post' | 'patch';
    submitLabel: string;
    onCancel: () => void;
};

export function OpenPlaySessionForm({
    session,
    courts,
    skillLevels,
    submitUrl,
    method,
    submitLabel,
    onCancel,
}: OpenPlaySessionFormProps) {
    const form = useForm<SessionFormData>({
        court_id: session ? String(session.court_id) : (courts[0]?.value ?? ''),
        title: session?.title ?? '',
        session_date: session?.session_date ?? '',
        start_time: session?.start_time ?? '18:00',
        end_time: session?.end_time ?? '20:00',
        capacity: session ? String(session.capacity) : '12',
        court_count: session ? String(session.court_count) : '1',
        skill_level: session?.skill_level ?? ALL_LEVELS,
        price: session ? String(session.price) : '0',
        notes: session?.notes ?? '',
    });

    const { data, setData, processing, errors } = form;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        // "All levels" is represented as null server-side.
        form.transform((formData) => ({
            ...formData,
            skill_level:
                formData.skill_level === ALL_LEVELS
                    ? null
                    : formData.skill_level,
        }));

        form.submit(method, submitUrl, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="title">Session title</Label>
                <Input
                    id="title"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    autoFocus
                    required
                    placeholder="e.g. Evening Open Play"
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="court_id">Court</Label>
                    <Select
                        value={data.court_id}
                        onValueChange={(value) => setData('court_id', value)}
                    >
                        <SelectTrigger id="court_id" className="w-full">
                            <SelectValue placeholder="Select a court" />
                        </SelectTrigger>
                        <SelectContent>
                            {courts.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.court_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="skill_level">Skill level</Label>
                    <Select
                        value={data.skill_level}
                        onValueChange={(value) => setData('skill_level', value)}
                    >
                        <SelectTrigger id="skill_level" className="w-full">
                            <SelectValue placeholder="All levels" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_LEVELS}>
                                All levels
                            </SelectItem>
                            {skillLevels.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.skill_level} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="session_date">Date</Label>
                    <Input
                        id="session_date"
                        type="date"
                        value={data.session_date}
                        onChange={(e) =>
                            setData('session_date', e.target.value)
                        }
                        required
                    />
                    <InputError message={errors.session_date} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="capacity">Capacity (players)</Label>
                    <Input
                        id="capacity"
                        type="number"
                        min="4"
                        max="100"
                        value={data.capacity}
                        onChange={(e) => setData('capacity', e.target.value)}
                        required
                    />
                    <InputError message={errors.capacity} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="court_count">Courts (parallel)</Label>
                    <Input
                        id="court_count"
                        type="number"
                        min="1"
                        max="20"
                        value={data.court_count}
                        onChange={(e) => setData('court_count', e.target.value)}
                        required
                    />
                    <p className="text-xs text-muted-foreground">
                        Matches that can run at the same time.
                    </p>
                    <InputError message={errors.court_count} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="start_time">Start time</Label>
                    <Input
                        id="start_time"
                        type="time"
                        step="3600"
                        value={data.start_time}
                        onChange={(e) => setData('start_time', e.target.value)}
                        required
                    />
                    <InputError message={errors.start_time} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="end_time">End time</Label>
                    <Input
                        id="end_time"
                        type="time"
                        step="3600"
                        value={data.end_time}
                        onChange={(e) => setData('end_time', e.target.value)}
                        required
                    />
                    <InputError message={errors.end_time} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="price">Price per player (PHP)</Label>
                    <div className="relative">
                        <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                            ₱
                        </span>
                        <Input
                            id="price"
                            type="number"
                            step="0.01"
                            min="0"
                            className="pl-7"
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value)}
                            required
                            placeholder="0.00"
                        />
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Enter 0 for a free session.
                    </p>
                    <InputError message={errors.price} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    placeholder="Optional details shown to players"
                />
                <InputError message={errors.notes} />
            </div>

            <div className="flex items-center justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onCancel}
                    disabled={processing}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && (
                        <LoaderCircle className="size-4 animate-spin" />
                    )}
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
