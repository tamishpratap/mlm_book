import React from 'react';
import { Search, X } from 'lucide-react';

/**
 * Standardized Admin Search Input component.
 * Ensures consistent leading Search icon positioning (left-3) with guaranteed
 * safe 40px left padding, and an optional clear button (X) on the right with
 * guaranteed 40px right padding when a value is entered, eliminating all text overlap bugs.
 */
export function AdminSearchInput({
  value = '',
  onChange,
  onClear,
  onSubmit,
  placeholder = 'Search...',
  disabled = false,
  className = '',
  inputClassName = '',
  id,
  name,
  autoComplete = 'off',
  showClear = true,
  size = 'md', // 'sm' | 'md' | 'lg'
  ...props
}) {
  const hasValue = Boolean(value && String(value).length > 0);

  const handleClear = (e) => {
    e.stopPropagation();
    if (onClear) {
      onClear();
    } else if (onChange) {
      onChange({ target: { value: '', name } });
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Escape' && hasValue) {
      handleClear(e);
    } else if (e.key === 'Enter' && onSubmit) {
      e.preventDefault();
      onSubmit(value);
    }
  };

  const sizeClasses = {
    sm: 'py-1 text-xs',
    md: 'py-1.5 text-xs',
    lg: 'py-2 text-sm',
  }[size] || 'py-1.5 text-xs';

  return (
    <div className={`relative flex items-center w-full ${className}`}>
      {/* Leading Search Icon */}
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none select-none z-10" />

      {/* Search Input Field with guaranteed padding */}
      <input
        id={id}
        name={name}
        type="text"
        value={value}
        onChange={onChange}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
        disabled={disabled}
        autoComplete={autoComplete}
        className={`w-full pl-10 ${hasValue && showClear ? 'pr-10' : 'pr-3.5'} ${sizeClasses} bg-white border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 transition-colors focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed ${inputClassName}`}
        {...props}
      />

      {/* Trailing Clear Button */}
      {hasValue && showClear && !disabled && (
        <button
          type="button"
          onClick={handleClear}
          className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors focus:outline-hidden focus:ring-1 focus:ring-blue-500/30 cursor-pointer z-10"
          title="Clear search"
          aria-label="Clear search"
        >
          <X className="w-3.5 h-3.5" />
        </button>
      )}
    </div>
  );
}

export default AdminSearchInput;
