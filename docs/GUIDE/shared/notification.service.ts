import { inject, Injectable } from '@angular/core';
import {
  MessageService,
  ConfirmationService,
  Confirmation,
} from 'primeng/api';
import { extractApiErrorMessage } from './api-error';

export interface ConfirmDeleteOptions {
  /** Entity noun used in the dialog copy, e.g. 'client', 'student'. */
  entity: string;
  /** Record count for bulk deletes; omit for a single-record delete. */
  count?: number;
  /** Runs when the user confirms. */
  onConfirm: () => void;
}

/**
 * App-wide façade over PrimeNG's toast + confirm-dialog services.
 *
 * Single source of truth for notifications and destructive-action confirms,
 * so feature modules never re-provide `MessageService`/`ConfirmationService`
 * or re-implement the styled confirm config. The matching `<p-toast>` and
 * `<p-confirmDialog>` outlets live once in {@link NotificationOutletComponent}
 * (mounted in `app.html`).
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly messages = inject(MessageService);
  private readonly confirmation = inject(ConfirmationService);

  success(detail: string, summary = 'Success'): void {
    this.messages.add({ severity: 'success', summary, detail });
  }

  info(detail: string, summary = 'Info'): void {
    this.messages.add({ severity: 'info', summary, detail });
  }

  warn(detail: string, summary = 'Warning'): void {
    this.messages.add({ severity: 'warn', summary, detail });
  }

  /**
   * Surfaces an error as a toast. A non-empty string is shown verbatim; any
   * other value is narrowed via extractApiErrorMessage (HttpErrorResponse etc.).
   */
  error(err: unknown, summary = 'Error'): void {
    const detail =
      typeof err === 'string' && err.trim()
        ? err
        : extractApiErrorMessage(err);
    this.messages.add({ severity: 'error', summary, detail });
  }

  /** Styled "are you sure?" confirm for soft-delete flows (single or bulk). */
  confirmDelete(options: ConfirmDeleteOptions): void {
    const { entity, count, onConfirm } = options;
    const isBulk = typeof count === 'number';
    this.confirmation.confirm({
      header: isBulk ? 'Delete selected' : `Delete ${entity}`,
      message: isBulk
        ? `Are you sure you want to delete ${count} selected ${entity}(s)?`
        : `Are you sure you want to delete this ${entity}?`,
      acceptLabel: 'Delete',
      rejectLabel: 'Cancel',
      acceptButtonStyleClass: 'btn-bulk btn-bulk-delete',
      rejectButtonStyleClass: 'btn-bulk btn-bulk-clear',
      accept: onConfirm,
    });
  }

  /** Generic confirm passthrough for non-delete flows. */
  confirm(options: Confirmation): void {
    this.confirmation.confirm(options);
  }
}
