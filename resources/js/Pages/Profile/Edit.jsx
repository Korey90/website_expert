import PortalLayout from '@/Layouts/PortalLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, client }) {
    return (
        <PortalLayout client={client}>
            <Head title="Profile" />

            <div className="max-w-3xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Account Settings</h1>

                <div className="bg-white p-6 shadow-sm rounded-xl border border-gray-100">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </div>

                <div className="bg-white p-6 shadow-sm rounded-xl border border-gray-100">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="bg-white p-6 shadow-sm rounded-xl border border-gray-100">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </PortalLayout>
    );
}
