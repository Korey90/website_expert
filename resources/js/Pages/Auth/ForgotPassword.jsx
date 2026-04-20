import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const T = {
    title:       { en: 'Forgot Password',                                                       pl: 'Resetuj hasło',                                                         pt: 'Esqueceu a senha' },
    heading:     { en: 'Forgot your password?',                                                 pl: 'Nie pamiętasz hasła?',                                                  pt: 'Esqueceu sua senha?' },
    subtitle:    { en: 'Enter your email and we\'ll send you a reset link.',                    pl: 'Podaj swój e-mail, a wyślemy Ci link do zresetowania hasła.',            pt: 'Digite seu e-mail e enviaremos um link para redefinição.' },
    email:       { en: 'Email address',                                                         pl: 'Adres e-mail',                                                          pt: 'Endereço de e-mail' },
    submit:      { en: 'Send reset link',                                                       pl: 'Wyślij link resetujący',                                                pt: 'Enviar link de redefinição' },
    submitting:  { en: 'Sending…',                                                              pl: 'Wysyłanie…',                                                            pt: 'Enviando…' },
    back:        { en: 'Back to sign in',                                                       pl: 'Wróć do logowania',                                                     pt: 'Voltar ao login' },
};

export default function ForgotPassword({ status }) {
    const { locale = 'en' } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? '';

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <MarketingLayout navbar={{}} footer={{}}>
            <Head title={t('title')} />

            <section className="flex items-center justify-center py-24 px-4">
                <div className="w-full max-w-md">
                    <h1 className="text-3xl font-bold mb-2 text-neutral-900 dark:text-white">{t('heading')}</h1>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-8">{t('subtitle')}</p>

                    {status && (
                        <div className="mb-6 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-4 py-3 rounded-lg">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <InputLabel htmlFor="email" value={t('email')} />
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-1 block w-full"
                                isFocused={true}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
                        >
                            {processing ? t('submitting') : t('submit')}
                        </button>
                    </form>

                    <div className="mt-6 text-center">
                        <Link
                            href={route('login')}
                            className="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 underline"
                        >
                            {t('back')}
                        </Link>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
