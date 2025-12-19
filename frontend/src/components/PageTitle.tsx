interface PageTitleProps {
  children: React.ReactNode
  subtitle?: string
  className?: string
}

export default function PageTitle({ children, subtitle, className = '' }: PageTitleProps) {
  return (
    <div className={`border-b border-gray-200 dark:border-gray-700 pb-6 ${className}`}>
      <h1 className="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
        {children}
      </h1>
      {subtitle && (
        <p className="text-gray-600 dark:text-gray-400">
          {subtitle}
        </p>
      )}
    </div>
  )
}
