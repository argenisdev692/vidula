import * as React from 'react';

interface AuthLayoutProps {
  children: React.ReactNode;
}

/**
 * AuthLayout — Unauthenticated layout for Login, Register, Forgot Password.
 * Full-screen centered card with Vidula branding.
 */
export default function AuthLayout({ children }: AuthLayoutProps): React.JSX.Element {
  return (
    <div
      className="flex min-h-screen items-center justify-center px-4 py-8"
      style={{
        background: 'radial-gradient(circle at top right, var(--purple-900), transparent), radial-gradient(circle at bottom left, var(--blue-950), var(--bg-void))',
      }}
    >
      {/* Decorative background circles */}
      <div
        className="pointer-events-none fixed inset-0 overflow-hidden"
        aria-hidden="true"
      >
        <div
          className="absolute -top-40 -right-40 h-96 w-96 rounded-full opacity-10"
          style={{ background: 'var(--purple-500)' }}
        />
        <div
          className="absolute -bottom-32 -left-32 h-80 w-80 rounded-full opacity-8"
          style={{ background: 'var(--blue-500)' }}
        />
      </div>

      <div className="relative z-10 w-full max-w-md">
        {/* Logo / Brand */}
        <div className="mb-8 text-center">
          <div
            className="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl"
            style={{
              background: 'var(--bg-surface)',
              boxShadow: '0 8px 32px rgba(0,0,0,0.5)',
              border: '1px solid var(--border-strong)',
            }}
          >
            <img 
              src="/img/Logo PNG.png" 
              alt="Vidula Logo" 
              className="h-14 w-auto object-contain drop-shadow-md"
            />
          </div>
          <h1
            className="mt-4 text-2xl font-bold tracking-tight"
            style={{ color: 'var(--color-white)' }}
          >
            Vidula
          </h1>
          <p
            className="mt-1 text-sm"
            style={{ color: 'var(--color-silver)' }}
          >
            Customer Relationship Management
          </p>
        </div>

        {/* Auth Card */}
        <div
          className="rounded-2xl p-8"
          style={{
            background: 'color-mix(in srgb, var(--color-white) 4%, transparent)',
            backdropFilter: 'blur(24px)',
            border: '1px solid color-mix(in srgb, var(--color-white) 8%, transparent)',
            boxShadow: '0 24px 64px color-mix(in srgb, #000 40%, transparent)',
          }}
        >
          {children}
        </div>

        {/* Footer */}
        <p
          className="mt-6 text-center text-xs"
          style={{ color: 'var(--text-secondary)' }}
        >
          © {new Date().getFullYear()} Vidula. All rights reserved.
        </p>
      </div>
    </div>
  );
}
