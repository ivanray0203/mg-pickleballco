import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Season } from '@/types';

type SeasonFormData = {
    name: string;
    starts_on: string;
    ends_on: string;
    is_active: boolean;
};

type SeasonFormProps = {
    season?: Season;
    submitUrl: string;
    method: 'post' | 'patch';
    submitLabel: string;
    onCancel: () => void;
};

export function SeasonForm({
    season,
    submitUrl,
    method,
    submitLabel,
    onCancel,
}: SeasonFormProps) {
    const form = useForm<SeasonFormData>({
        name: season?.name ?? '',
        starts_on: season?.starts_on ?? '',
        ends_on: season?.ends_on ?? '',
        is_active: season?.is_active ?? true,
    });

    const { data, setData, processing, errors } = form;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.submit(method, submitUrl, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Season name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    autoFocus
                    required
                    placeholder="e.g. Summer 2026 Season"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="starts_on">Starts on</Label>
                    <Input
                        id="starts_on"
                        type="date"
                        value={data.starts_on}
                        onChange={(e) => setData('starts_on', e.target.value)}
                        required
                    />
                    <InputError message={errors.starts_on} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ends_on">Ends on</Label>
                    <Input
                        id="ends_on"
                        type="date"
                        value={data.ends_on}
                        onChange={(e) => setData('ends_on', e.target.value)}
                        required
                    />
                    <InputError message={errors.ends_on} />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) =>
                        setData('is_active', checked === true)
                    }
                />
                <Label htmlFor="is_active" className="font-normal">
                    Active — show this season on the public leaderboard
                </Label>
            </div>
            <InputError message={errors.is_active} />

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
