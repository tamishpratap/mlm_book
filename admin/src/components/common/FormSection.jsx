export function FormSection({
  title,
  description,
  icon: Icon,
  actions,
  children,
  className = '',
}) {
  return (
    <div className={`bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden ${className}`}>
      {(title || description || Icon || actions) && (
        <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
          <div className="flex items-center space-x-2.5">
            {Icon && (
              <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <Icon className="w-4 h-4" />
              </div>
            )}
            <div>
              {title && <h3 className="text-sm font-bold text-slate-900">{title}</h3>}
              {description && <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">{description}</p>}
            </div>
          </div>
          {actions && <div className="flex items-center space-x-2">{actions}</div>}
        </div>
      )}

      <div className="p-5 sm:p-6 space-y-4">
        {children}
      </div>
    </div>
  );
}

export default FormSection;
