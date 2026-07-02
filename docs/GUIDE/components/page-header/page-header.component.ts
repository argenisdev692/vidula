import {
  Component,
  input,
  output,
  signal,
  computed,
  inject,
  viewChild,
  ElementRef,
  ChangeDetectionStrategy,
} from '@angular/core';
import { Router } from '@angular/router';
import { ButtonModule } from 'primeng/button';
import { UserMenuComponent } from '../user-menu/user-menu.component';
import { AnimatedButtonComponent } from '../animated-button/animated-button.component';
import { ThemeService } from '../../features/auth/services/theme.service';
import {
  HeaderNotificationsService,
  HeaderFeed,
  HeaderNotification,
} from './header-notifications.service';

@Component({
  selector: 'app-page-header',
  imports: [ButtonModule, UserMenuComponent, AnimatedButtonComponent],
  templateUrl: './page-header.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './page-header.component.css',
  // Close any open dropdown on an outside click (auto-removed with the component).
  host: { '(document:click)': 'closeDropdowns()' },
})
export class PageHeaderComponent {
  title = input.required<string>();
  subtitle = input<string>('');
  companyName = input<string>('');
  showNewClaim = input<boolean>(false);
  buttonLabel = input<string>('New Claim');

  menuToggle = output<void>();

  private themeService = inject(ThemeService);
  private router = inject(Router);
  // White logo on the dark theme, full-color logo on the light theme.
  protected readonly isDark = computed(() => this.themeService.mode() === 'dark');

  private notifications = inject(HeaderNotificationsService);
  protected readonly feeds = this.notifications.feeds;

  // Key of the currently open dropdown, or null when all are closed.
  openFeedKey = signal<string | null>(null);
  searchOpen = signal(false);
  searchQuery = signal('');

  readonly searchInputRef = viewChild.required<ElementRef<HTMLInputElement>>('searchInput');

  toggleDropdown(key: string, event: Event): void {
    event.stopPropagation();
    this.openFeedKey.update((current) => (current === key ? null : key));
    // Pull fresh counts/items each time a dropdown is opened.
    if (this.openFeedKey() === key) {
      this.notifications.refresh();
    }
  }

  closeDropdowns(): void {
    this.openFeedKey.set(null);
  }

  openDetail(feed: HeaderFeed, item: HeaderNotification): void {
    this.closeDropdowns();
    this.router.navigate([feed.route, item.id]);
  }

  viewAll(feed: HeaderFeed): void {
    this.closeDropdowns();
    this.router.navigate([feed.route]);
  }

  openSearch(): void {
    this.closeDropdowns();
    this.searchOpen.set(true);
    // Focus input on next tick
    requestAnimationFrame(() => this.searchInputRef().nativeElement.focus());
  }

  closeSearch(): void {
    this.searchOpen.set(false);
    this.searchQuery.set('');
  }

  onSearchInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.searchQuery.set(value);
  }

  onMenuClick(): void {
    this.menuToggle.emit();
  }

  onSearchKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      this.closeSearch();
    }
  }
}
