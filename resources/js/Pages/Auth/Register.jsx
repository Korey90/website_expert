import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import SocialAuthButtons from '@/Components/Auth/SocialAuthButtons';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const T = {
    title:            { en: 'Create account',                        pl: 'Utwórz konto',                              pt: 'Criar conta' },
    heading:          { en: 'Create your account',                   pl: 'Utwórz konto',                              pt: 'Crie sua conta' },
    have_account:     { en: 'Already have an account?',              pl: 'Masz już konto?',                           pt: 'Já tem uma conta?' },
    sign_in:          { en: 'Sign in',                               pl: 'Zaloguj się',                               pt: 'Entrar' },
    or_email:         { en: 'or continue with email',                pl: 'lub kontynuuj przez e-mail',                pt: 'ou continue com e-mail' },
    full_name:        { en: 'Full name',                             pl: 'Imię i nazwisko',                           pt: 'Nome completo' },
    email:            { en: 'Email address',                         pl: 'Adres e-mail',                              pt: 'Endereço de e-mail' },
    company_name:     { en: 'Company name',                          pl: 'Nazwa firmy',                               pt: 'Nome da empresa' },
    optional:         { en: 'optional',                              pl: 'opcjonalnie',                               pt: 'opcional' },
    company_ph:       { en: 'Your agency or business name',          pl: 'Nazwa Twojej agencji lub firmy',            pt: 'Nome da sua agência ou empresa' },
    password:         { en: 'Password',                              pl: 'Hasło',                                     pt: 'Senha' },
    confirm_password: { en: 'Confirm password',                      pl: 'Potwierdź hasło',                           pt: 'Confirmar senha' },
    submit:           { en: 'Create account',                        pl: 'Utwórz konto',                              pt: 'Criar conta' },
    submitting:       { en: 'Creating account…',                     pl: 'Tworzenie konta…',                          pt: 'Criando conta…' },
};

export default function Register() {
    const { locale = 'en' } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? '';

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        company_name: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <MarketingLayout navbar={{}} footer={{}}>
            <Head title={t('title')} />

            <section className="flex items-center justify-center py-24 px-4">
                <div className="w-full max-w-md">
                        <h1 className="text-3xl font-bold mb-2 text-neutral-900 dark:text-white">{t('heading')}</h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-8">
                            {t('have_account')}{' '}
                            <Link
                                href={route('login')}
                                className="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 underline"
                            >
                                {t('sign_in')}
                            </Link>
                        </p>

                        {/* Social auth */}
                        <SocialAuthButtons mode="register" />

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

                        {/* Email/password form */}
                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                    {t('full_name')}
                                </label>
                                <TextInput
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    autoComplete="name"
                                    isFocused={true}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                    {t('email')}
                                </label>
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="username"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <label htmlFor="company_name" className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                    {t('company_name')} <span className="text-neutral-400 font-normal">({t('optional')})</span>
                                </label>
                                <TextInput
                                    id="company_name"
                                    name="company_name"
                                    value={data.company_name}
                                    className="mt-1 block w-full"
                                    autoComplete="organization"
                                    onChange={(e) => setData('company_name', e.target.value)}
                                    placeholder={t('company_ph')}
                                />
                                <InputError message={errors.company_name} className="mt-2" />
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                    {t('password')}
                                </label>
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                    {t('confirm_password')}
                                </label>
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    value={data.password_confirmation}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password_confirmation} className="mt-2" />
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
                            >
                                {processing ? t('submitting') : t('submit')}
                            </button>
                        </form>

                        <p className="mt-6 text-xs text-center text-neutral-400 dark:text-neutral-500">
                            By creating an account you agree to our{' '}
                            <a href="/terms" className="underline hover:text-neutral-600 dark:hover:text-neutral-300">Terms</a>
                            {' '}and{' '}
                            <a href="/privacy" className="underline hover:text-neutral-600 dark:hover:text-neutral-300">Privacy Policy</a>.
                        </p>
                </div>
            </section>
        </MarketingLayout>
    );
}

