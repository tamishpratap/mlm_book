import React from 'react';

/**
 * Standardized Admin FormField wrapper.
 * Provides consistent label, required indicators, error states, help text,
 * and prefix/suffix icon and addon slots with guaranteed non-overlapping padding.
 */
export function FormField({
  label,
  id,
  htmlFor,
  required = false,
  error,
  helpText,
  className = '',
  startIcon: StartIcon,
  startAddon,
  endIcon: EndIcon,
  endAddon,
  children,
}) {
  const targetId = id || htmlFor;
  const hasStart = Boolean(StartIcon || startAddon);
  const hasEnd = Boolean(EndIcon || endAddon);

  let renderedInput = children;

  if (hasStart || hasEnd) {
    let paddingClass = '';
    if (hasStart && hasEnd) {
      paddingClass = 'has-both-icons pl-10 pr-10';
    } else if (hasStart) {
      paddingClass = StartIcon ? 'has-start-icon pl-10' : 'has-start-addon pl-10';
    } else if (hasEnd) {
      paddingClass = EndIcon ? 'has-end-icon pr-10' : 'has-end-addon pr-10';
    }

    if (React.isValidElement(children)) {
      const childProps = children.props || {};
      const combinedClassName = `${childProps.className || ''} ${paddingClass}`.trim();
      renderedInput = React.cloneElement(children, { className: combinedClassName });
    }

    renderedInput = (
      <div className="relative flex items-center w-full">
        {StartIcon && (
          <div className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none z-10 flex items-center justify-center">
            {typeof StartIcon === 'function' || typeof StartIcon === 'object' ? (
              <StartIcon className="w-4 h-4" />
            ) : (
              StartIcon
            )}
          </div>
        )}
        {!StartIcon && startAddon && (
          <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold font-mono text-xs pointer-events-none z-10 select-none">
            {startAddon}
          </span>
        )}

        {renderedInput}

        {EndIcon && (
          <div className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none z-10 flex items-center justify-center">
            {typeof EndIcon === 'function' || typeof EndIcon === 'object' ? (
              <EndIcon className="w-4 h-4" />
            ) : (
              EndIcon
            )}
          </div>
        )}
        {!EndIcon && endAddon && (
          <span className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold font-mono text-xs pointer-events-none z-10 select-none">
            {endAddon}
          </span>
        )}
      </div>
    );
  }

  return (
    <div className={`space-y-1.5 ${className}`}>
      {label && (
        <label
          htmlFor={targetId}
          className="block text-xs font-bold text-slate-800 select-none"
        >
          {label}
          {required && <span className="text-red-500 font-bold ml-0.5">*</span>}
        </label>
      )}

      {renderedInput}

      {error && (
        <p className="text-xs text-red-600 font-medium mt-1 leading-tight flex items-center">
          {error}
        </p>
      )}

      {helpText && !error && (
        <p className="text-xs text-slate-500 mt-1 leading-normal">
          {helpText}
        </p>
      )}
    </div>
  );
}

export default FormField;
