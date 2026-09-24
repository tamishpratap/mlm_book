import React from 'react';

/**
 * Reusable Admin Action Group component for standardizing
 * spacing, alignment, and responsive wrapping for grouped buttons/controls.
 */
export function AdminActionGroup({
  children,
  className = '',
  justify = 'start',
  align = 'center',
  gap = 'gap-2.5',
}) {
  const justifyClass = {
    start: 'justify-start',
    end: 'justify-end',
    center: 'justify-center',
    between: 'justify-between',
  }[justify] || 'justify-start';

  const alignClass = {
    center: 'items-center',
    start: 'items-start',
    end: 'items-end',
    stretch: 'items-stretch',
  }[align] || 'items-center';

  return (
    <div className={`flex flex-wrap ${alignClass} ${justifyClass} ${gap} ${className}`}>
      {children}
    </div>
  );
}

export default AdminActionGroup;
