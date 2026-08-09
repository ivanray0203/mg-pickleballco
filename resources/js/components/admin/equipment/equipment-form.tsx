import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { ImageUploader } from '@/components/admin/courts/image-uploader';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { Equipment, SelectOption } from '@/types';

type EquipmentFormData = {
    name: string;
    description: string;
    type: string;
    hourly_rate: string;
    total_quantity: string;
    is_active: boolean;
    image: File | null;
};

type EquipmentFormProps = {
    equipment?: Equipment;
    equipmentTypes: SelectOption[];
    submitUrl: string;
    method: 'post' | 'patch';
    submitLabel: string;
    onCancel: () => void;
};

export function EquipmentForm({
    equipment,
    equipmentTypes,
    submitUrl,
    method,
    submitLabel,
    onCancel,
}: EquipmentFormProps) {
    const { data, setData, post, transform, processing, errors, progress } =
        useForm<EquipmentFormData>({
            name: equipment?.name ?? '',
            description: equipment?.description ?? '',
            type: equipment?.type ?? equipmentTypes[0]?.value ?? '',
            hourly_rate: equipment ? String(equipment.hourly_rate) : '',
            total_quantity: equipment ? String(equipment.total_quantity) : '1',
            is_active: equipment?.is_active ?? true,
            image: null,
        });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        // Files require multipart, which can't PATCH directly, so updates are
        // sent as POST with Laravel's `_method` spoofing.
        transform((formData) =>
            method === 'patch' ? { ...formData, _method: 'patch' } : formData,
        );

        post(submitUrl, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Equipment name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    autoFocus
                    required
                    placeholder="e.g. Pro Graphite Paddle"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Optional notes about this item"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="type">Type</Label>
                    <Select
                        value={data.type}
                        onValueChange={(value) => setData('type', value)}
                    >
                        <SelectTrigger id="type" className="w-full">
                            <SelectValue placeholder="Select a type" />
                        </SelectTrigger>
                        <SelectContent>
                            {equipmentTypes.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="hourly_rate">Hourly rate (PHP)</Label>
                    <div className="relative">
                        <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                            ₱
                        </span>
                        <Input
                            id="hourly_rate"
                            type="number"
                            step="0.01"
                            min="0.01"
                            className="pl-7"
                            value={data.hourly_rate}
                            onChange={(e) =>
                                setData('hourly_rate', e.target.value)
                            }
                            required
                            placeholder="20.00"
                        />
                    </div>
                    <InputError message={errors.hourly_rate} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="total_quantity">Total stock</Label>
                    <Input
                        id="total_quantity"
                        type="number"
                        step="1"
                        min="0"
                        value={data.total_quantity}
                        onChange={(e) =>
                            setData('total_quantity', e.target.value)
                        }
                        required
                        placeholder="10"
                    />
                    <p className="text-xs text-muted-foreground">
                        Units you own and can rent out at the same time.
                    </p>
                    <InputError message={errors.total_quantity} />
                </div>
            </div>

            <ImageUploader
                value={data.image}
                onChange={(file) => setData('image', file)}
                existingImageUrl={equipment?.image_url}
                error={errors.image}
                disabled={processing}
                label="Equipment image"
                inputId="equipment-image"
            />

            {progress && (
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full bg-primary transition-all"
                        style={{ width: `${progress.percentage ?? 0}%` }}
                    />
                </div>
            )}

            <div className="flex items-center gap-2 rounded-lg border border-border bg-muted/30 p-4">
                <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) =>
                        setData('is_active', checked === true)
                    }
                />
                <div className="grid gap-0.5">
                    <Label htmlFor="is_active" className="cursor-pointer">
                        Active
                    </Label>
                    <p className="text-xs text-muted-foreground">
                        Inactive items are hidden from customer booking.
                    </p>
                </div>
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
