import { confirmDialog } from 'primereact/confirmdialog';

/**
 * Standardized confirmation dialog triggers for administrative actions.
 */
export const confirmHelper = {
  /**
   * Generic confirmation dialog
   */
  confirm: ({
    header = 'Confirm Action',
    message = 'Are you sure you want to proceed with this administrative action?',
    icon = 'pi pi-exclamation-triangle text-amber-500',
    acceptLabel = 'Confirm',
    rejectLabel = 'Cancel',
    acceptClassName = 'p-button-primary text-xs',
    rejectClassName = 'p-button-outlined p-button-secondary text-xs',
    onAccept,
    onReject,
  }) => {
    confirmDialog({
      header,
      message,
      icon,
      acceptLabel,
      rejectLabel,
      acceptClassName,
      rejectClassName,
      accept: onAccept,
      reject: onReject,
    });
  },

  /**
   * Destructive delete confirmation
   */
  confirmDelete: ({
    header = 'Confirm Permanent Deletion',
    message = 'Are you sure you want to delete this record? This action cannot be undone.',
    itemName,
    onAccept,
    onReject,
  }) => {
    const formattedMessage = itemName
      ? `Are you sure you want to delete "${itemName}"? This action cannot be undone.`
      : message;

    confirmDialog({
      header,
      message: formattedMessage,
      icon: 'pi pi-trash text-red-500',
      acceptLabel: 'Delete Permanently',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-danger text-xs',
      rejectClassName: 'p-button-outlined p-button-secondary text-xs',
      accept: onAccept,
      reject: onReject,
    });
  },

  /**
   * Member/Account block confirmation
   */
  confirmBlock: ({
    header = 'Block Member?',
    message = 'Are you sure you want to block this member? Blocking may restrict access according to platform rules.',
    userName,
    onAccept,
    onReject,
  }) => {
    const formattedMessage = userName
      ? `Are you sure you want to block "${userName}"? Blocking may restrict access according to platform rules.`
      : message;

    confirmDialog({
      header,
      message: formattedMessage,
      icon: 'pi pi-ban text-red-500',
      acceptLabel: 'Block Member',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-danger text-xs',
      rejectClassName: 'p-button-outlined p-button-secondary text-xs',
      accept: onAccept,
      reject: onReject,
    });
  },

  /**
   * Member/Account unblock confirmation
   */
  confirmUnblock: ({
    header = 'Unblock Member?',
    message = 'Are you sure you want to restore this member?',
    userName,
    onAccept,
    onReject,
  }) => {
    const formattedMessage = userName
      ? `Are you sure you want to restore and unblock "${userName}"?`
      : message;

    confirmDialog({
      header,
      message: formattedMessage,
      icon: 'pi pi-check-circle text-emerald-500',
      acceptLabel: 'Unblock Member',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-success text-xs',
      rejectClassName: 'p-button-outlined p-button-secondary text-xs',
      accept: onAccept,
      reject: onReject,
    });
  },

  /**
   * Verification Approval / Rejection
   */
  confirmVerify: ({
    header = 'Confirm Verification Approval',
    message = 'Are you sure you want to approve this identity verification request and grant the verified badge?',
    targetName,
    onAccept,
    onReject,
  }) => {
    const formattedMessage = targetName
      ? `Are you sure you want to approve verification for "${targetName}"?`
      : message;

    confirmDialog({
      header,
      message: formattedMessage,
      icon: 'pi pi-check-circle text-emerald-500',
      acceptLabel: 'Approve & Verify',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-success text-xs',
      rejectClassName: 'p-button-outlined p-button-secondary text-xs',
      accept: onAccept,
      reject: onReject,
    });
  },
};

export default confirmHelper;
