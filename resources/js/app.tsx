import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import '../css/app.css';
import './bootstrap';
import 'sileo/styles.css';
import { Toaster } from 'sileo';

// ── QueryClient — module-level (never inside a component) ──
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
      throwOnError: false,
    },
  },
});

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
        return pages[`./pages/${name}.tsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <QueryClientProvider client={queryClient}>
                <App {...props} />
                <Toaster 
                    theme="system"
                    options={{
                        fill: 'var(--bg-card)',
                        roundness: 8,
                        styles: {
                            title: 'text-[14px] font-sans font-semibold text-[var(--text-primary)]',
                            description: 'text-[13px] font-sans text-[var(--text-muted)]'
                        }
                    }}
                />
                <ReactQueryDevtools initialIsOpen={false} />
            </QueryClientProvider>
        );
    },
});
