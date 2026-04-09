import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import SocialAuthButtons from '@/Components/Auth/SocialAuthButtons';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const T = {
    title:       { en: 'Sign in',                                         pl: 'Zaloguj się',                                              pt: 'Entrar' },
    heading:     { en: 'Sign in',                                         pl: 'Zaloguj się',                                              pt: 'Entrar' },
    subtitle:    { en: 'Enter your credentials to access your account.',  pl: 'Podaj dane logowania, aby uzyskać dostęp do konta.',        pt: 'Digite suas credenciais para acessar sua conta.' },
    or_email:    { en: 'or continue with email',                          pl: 'lub kontynuuj przez e-mail',                               pt: 'ou continue com e-mail' },
    email:       { en: 'Email',                                           pl: 'E-mail',                                                   pt: 'E-mail' },
    password:    { en: 'Password',                                        pl: 'Hasło',                                                    pt: 'Senha' },
    remember:    { en: 'Remember me',                                     pl: 'Zapamiętaj mnie',                                          pt: 'Lembrar-me' },
    forgot:      { en: 'Forgot password?',                                pl: 'Nie pamiętasz hasła?',                                     pt: 'Esqueceu a senha?' },
    submit:      { en: 'Sign in',                                         pl: 'Zaloguj się',                                              pt: 'Entrar' },
    submitting:  { en: 'Signing in…',                                     pl: 'Logowanie…',                                               pt: 'Entrando…' },
};

export default function Login({ status, canResetPassword }) {
    const { locale = 'en' } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? '';

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <MarketingLayout>
            <Head title={t('title')} />

            <div className="min-h-screen bg-white dark:bg-neutral-950 text-neutral-900 dark:text-white flex flex-col">
                <main className="flex-1 flex items-center justify-center px-4 py-20">
                    <div className="w-full max-w-md">
                        <h1 className="text-3xl font-bold mb-2 text-neutral-900 dark:text-white">{t('heading')}</h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-8">{t('subtitle')}</p>

                        {status && (
                            <div className="mb-6 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-4 py-3 rounded-lg">
                                {status}
                            </div>
                        )}

                        {/* Social auth */}
                        <SocialAuthButtons mode="login" />

                        {/* Divider */}
                        <div className="relative my-6">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-neutral-200 dark:border-neutral-800" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-white dark:bg-neutral-950 px-3 text-neutral-400 dark:text-neutral-500 tracking-wider">
                                    {t('or_email')}
                                </span>
                            </div>
                        </div>

                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <InputLabel htmlFor="email" value={t('email')} />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password" value={t('password')} />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-between">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <Checkbox
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                    />
                                    <span className="text-sm text-neutral-600 dark:text-neutral-400">{t('remember')}</span>
                                </label>

                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 underline"
                                    >
                                        {t('forgot')}
                                    </Link>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
                            >
                                {processing ? t('submitting') : t('submit')}
                            </button>
                        </form>
                    </div>
                </main>
            </div>
        </MarketingLayout>
    );
}

