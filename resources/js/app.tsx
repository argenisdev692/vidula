import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import '../css/app.css';
import './bootstrap';
import 'sileo/styles.css';
import { Toaster } from 'sileo';

// ── QueryClient — module-level (never inside a component) ──
// TanStack Query v5 best practices configuration
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5,        // 5 minutes - data stays fresh
      gcTime: 1000 * 60 * 30,          // 30 minutes - garbage collection (formerly cacheTime)
      retry: 3,                         // Retry failed requests 3 times
      refetchOnWindowFocus: true,      // Refetch when window regains focus
      refetchOnReconnect: true,        // Refetch when reconnecting
      refetchOnMount: true,            // Refetch on component mount
    },
    mutations: {
      retry: 1,                         // Retry mutations once on failure
      onError: (error) => {
        console.error('Mutation error:', error);
      },
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
                            title: 'text-[14px] font-sans font-semibold text-(--text-primary)',
                            description: 'text-[13px] font-sans text-(--text-muted)'
                        }
                    }}
                />
                <ReactQueryDevtools initialIsOpen={false} />
            </QueryClientProvider>
        );
    },
});
