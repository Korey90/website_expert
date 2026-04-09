import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

const T = {
    title:       { en: 'Delete Account',               pl: 'Usuń konto',                       pt: 'Excluir conta' },
    warning:     { en: 'Once your account is deleted, all your personal data will be permanently removed from our systems. Financial records may be retained for the legally required period.',
                   pl: 'Po usunięciu konta wszystkie Twoje dane osobowe zostaną trwale usunięte z naszych systemów. Dokumenty finansowe mogą być przechowywane przez prawnie wymagany okres.',
                   pt: 'Depois que sua conta for excluída, todos os seus dados pessoais serão removidos permanentemente dos nossos sistemas. Registros financeiros podem ser mantidos pelo período legalmente exigido.' },
    delete_btn:  { en: 'Delete My Account',            pl: 'Usuń moje konto',                  pt: 'Excluir minha conta' },
    modal_title: { en: 'Are you sure you want to delete your account?',
                   pl: 'Czy na pewno chcesz usunąć swoje konto?',
                   pt: 'Tem certeza que deseja excluir sua conta?' },
    modal_warn:  { en: 'This action is irreversible. All your data will be permanently deleted.',
                   pl: 'Ta operacja jest nieodwracalna. Wszystkie Twoje dane zostaną trwale usunięte.',
                   pt: 'Esta ação é irreversível. Todos os seus dados serão excluídos permanentemente.' },
    pw_label:    { en: 'Enter your password to confirm',
                   pl: 'Wprowadź hasło, aby potwierdzić',
                   pt: 'Digite sua senha para confirmar' },
    confirm_chk: { en: 'I understand and want to permanently delete my account',
                   pl: 'Rozumiem i chcę trwale usunąć moje konto',
                   pt: 'Entendo e quero excluir permanentemente minha conta' },
    social_note: { en: 'Since you signed in with a social account, no password is required — please check the box below to confirm.',
                   pl: 'Ponieważ logujesz się przez konto społecznościowe, hasło nie jest wymagane — zaznacz poniższe pole, aby potwierdzić.',
                   pt: 'Como você fez login com uma conta social, nenhuma senha é necessária — marque a caixa abaixo para confirmar.' },
    cancel:      { en: 'Cancel',                       pl: 'Anuluj',                            pt: 'Cancelar' },
    confirm_btn: { en: 'Yes, delete my account',       pl: 'Tak, usuń moje konto',              pt: 'Sim, excluir minha conta' },
    gdpr_title:  { en: 'What will be deleted:',        pl: 'Co zostanie usunięte:',              pt: 'O que será excluído:' },
    gdpr_list:   {
        en: ['Your profile and login credentials', 'All social login connections', 'Your business account and settings'],
        pl: ['Twój profil i dane logowania', 'Wszystkie połączenia z kontami społecznościowymi', 'Konto firmowe i ustawienia'],
        pt: ['Seu perfil e credenciais de login', 'Todas as conexões de login social', 'Sua conta comercial e configurações'],
    },
};

export default function DeleteUserForm({ hasPassword, className = '' }) {
    const { locale } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? '';

    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm(
        hasPassword
            ? { password: '' }
            : { confirmed: false }
    );

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => hasPassword && passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingDeletion(false);
        clearErrors();
        reset();
    };

    const gdprList = T.gdpr_list?.[locale] ?? T.gdpr_list?.en ?? [];

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {t('title')}
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {t('warning')}
                </p>
            </header>

            <DangerButton onClick={() => setConfirmingDeletion(true)}>
                {t('delete_btn')}
            </DangerButton>

            <Modal show={confirmingDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6 space-y-5">
                    <h2 className="text-lg font-semibold text-gray-900">
                        {t('modal_title')}
                    </h2>

                    <p className="text-sm text-gray-600">
                        {t('modal_warn')}
                    </p>

                    {/* GDPR: lista co zostanie usunięte */}
                    <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                        <p className="text-xs font-semibold text-red-700 mb-2">{t('gdpr_title')}</p>
                        <ul className="list-disc list-inside space-y-1">
                            {gdprList.map((item) => (
                                <li key={item} className="text-xs text-red-600">{item}</li>
                            ))}
                        </ul>
                    </div>

                    {/* Potwierdzenie: hasło LUB checkbox dla social-only */}
                    {hasPassword ? (
                        <div>
                            <InputLabel htmlFor="delete-password" value={t('pw_label')} className="sr-only" />
                            <TextInput
                                id="delete-password"
                                type="password"
                                name="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1 block w-3/4"
                                isFocused
                                placeholder="••••••••"
                            />
                            <InputError message={errors.password} className="mt-2" />
                        </div>
                    ) : (
                        <div className="space-y-3">
                            <p className="text-sm text-gray-500 italic">{t('social_note')}</p>
                            <label className="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.confirmed}
                                    onChange={(e) => setData('confirmed', e.target.checked)}
                                    className="mt-0.5 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500"
                                />
                                <span className="text-sm text-gray-700">
                                    {t('confirm_chk')}
                                </span>
                            </label>
                            <InputError message={errors.confirmed} className="mt-1" />
                        </div>
                    )}

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={closeModal}>
                            {t('cancel')}
                        </SecondaryButton>
                        <DangerButton
                            disabled={processing || (!hasPassword && !data.confirmed)}
                        >
                            {t('confirm_btn')}
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
