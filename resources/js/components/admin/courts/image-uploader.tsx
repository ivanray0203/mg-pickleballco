import { ImageIcon, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type ImageUploaderProps = {
    value: File | null;
    onChange: (file: File | null) => void;
    existingImageUrl?: string | null;
    error?: string;
    disabled?: boolean;
    /** Field label; defaults to the court use-case for backwards-compat. */
    label?: string;
    /** DOM id for the hidden file input (must be unique per form). */
    inputId?: string;
};

/**
 * Reusable image field. Shows a preview of the newly selected file or, failing
 * that, the existing image. Used by both court and equipment forms.
 */
export function ImageUploader({
    value,
    onChange,
    existingImageUrl,
    error,
    disabled,
    label = 'Court image',
    inputId = 'court-image',
}: ImageUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);

    const previewUrl = useMemo(
        () => (value ? URL.createObjectURL(value) : null),
        [value],
    );

    // Revoke the object URL once it's no longer needed to avoid leaks.
    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const displayUrl = previewUrl ?? existingImageUrl ?? null;

    const handleSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
        onChange(event.target.files?.[0] ?? null);
    };

    const handleClear = () => {
        onChange(null);

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    return (
        <div className="grid gap-2">
            <Label htmlFor={inputId}>{label}</Label>

            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div
                    className={cn(
                        'flex aspect-video w-full max-w-56 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed bg-muted/40',
                        error && 'border-destructive',
                    )}
                >
                    {displayUrl ? (
                        <img
                            src={displayUrl}
                            alt="Court preview"
                            className="size-full object-cover"
                        />
                    ) : (
                        <div className="flex flex-col items-center gap-1 text-muted-foreground">
                            <ImageIcon className="size-6" />
                            <span className="text-xs">No image</span>
                        </div>
                    )}
                </div>

                <div className="flex flex-col gap-2">
                    <input
                        ref={inputRef}
                        id={inputId}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="hidden"
                        onChange={handleSelect}
                        disabled={disabled}
                    />

                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={disabled}
                            onClick={() => inputRef.current?.click()}
                        >
                            <Upload className="size-4" />
                            {displayUrl ? 'Replace image' : 'Upload image'}
                        </Button>

                        {value && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                disabled={disabled}
                                onClick={handleClear}
                            >
                                <X className="size-4" />
                                Remove
                            </Button>
                        )}
                    </div>

                    <p className="text-xs text-muted-foreground">
                        JPG, PNG or WEBP up to 5MB.
                        {existingImageUrl && !value
                            ? ' Leave empty to keep the current image.'
                            : ''}
                    </p>

                    <InputError message={error} />
                </div>
            </div>
        </div>
    );
}
