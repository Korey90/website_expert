export default function EmptyState({ icon, title, description, action }) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-12 text-center">
            {icon && (
                <div className="text-4xl mb-4">{icon}</div>
            )}
            <h3 className="text-sm font-semibold text-gray-700">{title}</h3>
            {description && (
                <p className="mt-1 text-sm text-gray-500">{description}</p>
            )}
            {action && (
                <div className="mt-5">{action}</div>
            )}
        </div>
    );
}
