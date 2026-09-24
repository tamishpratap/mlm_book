import { Tag } from 'primereact/tag';

export function StatusBadge({ status, label, className = '' }) {
  const normalized = (status || '').toLowerCase().trim();

  const getSeverity = () => {
    switch (normalized) {
      case 'active':
      case 'verified':
      case 'published':
      case 'available':
      case 'resolved':
      case 'success':
      case 'accepted':
      case 'delivered':
      case 'completed':
        return 'success';

      case 'pending':
      case 'warning':
      case 'unverified':
      case 'review':
      case 'upcoming':
      case 'in_progress':
      case 'paused':
        return 'warning';

      case 'blocked':
      case 'suspended':
      case 'rejected':
      case 'danger':
      case 'hidden':
      case 'error':
      case 'failed':
      case 'expired':
      case 'stopped':
      case 'cancelled':
        return 'danger';

      case 'inactive':
      case 'draft':
      case 'sold':
      case 'closed':
      case 'private':
      case 'archived':
      case 'info':
      default:
        return 'info';
    }
  };

  const displayText = label || (status ? status.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()) : 'Unknown');

  return (
    <Tag
      value={displayText}
      severity={getSeverity()}
      className={`text-[11px] font-semibold px-2 py-0.5 ${className}`}
    />
  );
}

export default StatusBadge;
