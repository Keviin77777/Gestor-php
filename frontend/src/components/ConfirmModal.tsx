import { AlertTriangle } from 'lucide-react'

interface ConfirmModalProps {
    isOpen: boolean
    title: string
    message: string
    confirmText?: string
    cancelText?: string
    onConfirm: () => void
    onCancel: () => void
    type?: 'danger' | 'warning' | 'info'
}

export default function ConfirmModal({
    isOpen,
    title,
    message,
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    onConfirm,
    onCancel,
    type = 'warning'
}: ConfirmModalProps) {
    if (!isOpen) return null

    const colors = {
        danger: {
            bg: 'bg-red-100 dark:bg-red-900/20',
            text: 'text-red-600 dark:text-red-400',
            button: 'bg-red-600 hover:bg-red-700'
        },
        warning: {
            bg: 'bg-yellow-100 dark:bg-yellow-900/20',
            text: 'text-yellow-600 dark:text-yellow-400',
            button: 'bg-yellow-600 hover:bg-yellow-700'
        },
        info: {
            bg: 'bg-blue-100 dark:bg-blue-900/20',
            text: 'text-blue-600 dark:text-blue-400',
            button: 'bg-blue-600 hover:bg-blue-700'
        }
    }

    const color = colors[type]

    return (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <div className="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl">
                <div className="p-6">
                    {/* Icon */}
                    <div className={`w-16 h-16 ${color.bg} rounded-full flex items-center justify-center mx-auto mb-4`}>
                        <AlertTriangle className={`w-8 h-8 ${color.text}`} />
                    </div>

                    {/* Title */}
                    <h3 className="text-xl font-bold text-gray-900 dark:text-white text-center mb-2">
                        {title}
                    </h3>

                    {/* Message */}
                    <div className="text-gray-600 dark:text-gray-400 text-left mb-6 whitespace-pre-line">
                        {message}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3">
                        <button
                            onClick={onCancel}
                            className="flex-1 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            {cancelText}
                        </button>
                        <button
                            onClick={onConfirm}
                            className={`flex-1 py-3 ${color.button} text-white rounded-lg font-semibold transition-colors`}
                        >
                            {confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    )
}
