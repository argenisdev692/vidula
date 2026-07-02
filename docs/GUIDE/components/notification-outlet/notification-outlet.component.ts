import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ToastModule } from 'primeng/toast';
import { ConfirmDialogModule } from 'primeng/confirmdialog';

/**
 * Single app-wide outlet for toasts and confirm dialogs. Mounted once in
 * `app.html`; driven by {@link NotificationService}. Feature components must
 * NOT render their own `<p-toast>`/`<p-confirmDialog>`.
 */
@Component({
  selector: 'app-notification-outlet',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ToastModule, ConfirmDialogModule],
  template: `
    <p-toast position="top-right" />
    <p-confirmDialog />
  `,
})
export class NotificationOutletComponent {}
