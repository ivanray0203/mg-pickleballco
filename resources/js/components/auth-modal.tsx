import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { AuthDivider, SocialButtons } from '@/components/social-buttons';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store as loginStore } from '@/routes/login';
import { request } from '@/routes/password';
import { store as registerStore } from '@/routes/register';

export type AuthMode = 'login' | 'register';

type AuthModalProps = {
    open: boolean;
    mode: AuthMode;
    onOpenChange: (open: boolean) => void;
    onModeChange: (mode: AuthMode) => void;
};

/** Inline link-styled button used to toggle between the two forms. */
function SwitchButton({
    children,
    onClick,
}: {
    children: React.ReactNode;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="font-medium text-primary underline-offset-4 hover:underline"
        >
            {children}
        </button>
    );
}

function LoginForm({ onSwitch }: { onSwitch: () => void }) {
    return (
        <Form
            {...loginStore.form()}
            resetOnSuccess={['password']}
            className="flex flex-col gap-5"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="login-email">Email address</Label>
                        <Input
                            id="login-email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            autoComplete="email"
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="login-password">Password</Label>
                            <a
                                href={request().url}
                                className="ml-auto text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                            >
                                Forgot password?
                            </a>
                        </div>
                        <PasswordInput
                            id="login-password"
                            name="password"
                            required
                            autoComplete="current-password"
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox id="login-remember" name="remember" />
                        <Label htmlFor="login-remember">Remember me</Label>
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Log in
                    </Button>

                    <p className="text-center text-sm text-muted-foreground">
                        Don&apos;t have an account?{' '}
                        <SwitchButton onClick={onSwitch}>Sign up</SwitchButton>
                    </p>
                </>
            )}
        </Form>
    );
}

function RegisterForm({ onSwitch }: { onSwitch: () => void }) {
    return (
        <Form
            {...registerStore.form()}
            resetOnSuccess={['password', 'password_confirmation']}
            disableWhileProcessing
            className="flex flex-col gap-5"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="register-name">Name</Label>
                        <Input
                            id="register-name"
                            type="text"
                            name="name"
                            required
                            autoFocus
                            autoComplete="name"
                            placeholder="Full name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="register-email">Email address</Label>
                        <Input
                            id="register-email"
                            type="email"
                            name="email"
                            required
                            autoComplete="email"
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="register-password">Password</Label>
                        <PasswordInput
                            id="register-password"
                            name="password"
                            required
                            autoComplete="new-password"
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="register-password-confirmation">
                            Confirm password
                        </Label>
                        <PasswordInput
                            id="register-password-confirmation"
                            name="password_confirmation"
                            required
                            autoComplete="new-password"
                            placeholder="Confirm password"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Create account
                    </Button>

                    <p className="text-center text-sm text-muted-foreground">
                        Already have an account?{' '}
                        <SwitchButton onClick={onSwitch}>Log in</SwitchButton>
                    </p>
                </>
            )}
        </Form>
    );
}

/**
 * Login / register in a single dialog, toggled between the two modes. Both
 * forms submit straight to the existing Fortify endpoints, so all auth
 * behaviour (validation, throttling, redirects, 2FA) is preserved — a
 * successful submit follows the server redirect and closes the modal.
 */
export function AuthModal({
    open,
    mode,
    onOpenChange,
    onModeChange,
}: AuthModalProps) {
    const isLogin = mode === 'login';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isLogin ? 'Welcome back' : 'Create your account'}
                    </DialogTitle>
                    <DialogDescription>
                        {isLogin
                            ? 'Log in to book courts and manage your games.'
                            : 'Sign up to start booking courts in seconds.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-4">
                    <SocialButtons />
                    <AuthDivider />

                    {isLogin ? (
                        <LoginForm onSwitch={() => onModeChange('register')} />
                    ) : (
                        <RegisterForm onSwitch={() => onModeChange('login')} />
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
