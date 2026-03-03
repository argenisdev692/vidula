import * as React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useSingleClient } from '@/modules/clients/hooks/useClient';
import ClientStatusBadge from '@/modules/clients/components/ClientStatusBadge';
import type { PageProps } from '@inertiajs/core';

// ══════════════════════════════════════════════════════════════
// Icons
// ══════════════════════════════════════════════════════════════
const ic = {
  w: 16, h: 16, viewBox: '0 0 24 24', fill: 'none',
  stroke: 'currentColor', strokeWidth: 2,
  strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const,
};
const IconArrowLeft = () => <svg {...ic}><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>;
const IconEdit = () => <svg {...ic}><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>;
const IconMail = () => <svg {...ic}><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>;
const IconPhone = () => <svg {...ic}><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>;
const IconGlobe = () => <svg {...ic}><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>;
const IconMapPin = () => <svg {...ic}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>;
const IconBuilding = () => <svg {...ic}><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>;

// ══════════════════════════════════════════════════════════════
// ClientShowPage
// ══════════════════════════════════════════════════════════════
export default function ClientShowPage(): React.JSX.Element {
  const { props } = usePage<PageProps & { companyId: string }>();
  
  // Extract uuid from url if inertia prop doesn't supply it directly (it's the last segment)
  const urlParts = window.location.pathname.split('/');
  const finalUuid = props.companyId || urlParts[urlParts.length - 1]; 

  const { data: company, isPending, isError } = useSingleClient(finalUuid);

  if (isPending) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] items-center justify-center">
          <p style={{ color: 'var(--text-muted)' }}>Loading company profile...</p>
        </div>
      </AppLayout>
    );
  }

  if (isError || !company) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] items-center justify-center">
          <p style={{ color: 'var(--accent-error)' }}>Failed to load company profile.</p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`${company.companyName} Profile`} />
      <div style={{ fontFamily: 'var(--font-sans)', maxWidth: '900px', margin: '0 auto' }}>
        
        {/* ── Header ── */}
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/client"
              className="flex h-9 w-9 items-center justify-center rounded-lg transition-all hover:bg-(--bg-hover)"
              style={{ color: 'var(--text-muted)' }}
            >
              <IconArrowLeft />
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {company.companyName}
              </h1>
              <div className="mt-1 flex items-center gap-3">
                <ClientStatusBadge status={company.deletedAt ? 'deleted' : 'active'} />
                <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                  ID: {company.uuid.substring(0, 8)}...
                </span>
                <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                  Registered: {new Date(company.createdAt).toLocaleDateString()}
                </span>
              </div>
            </div>
          </div>
          <Link
            href={`/client/${company.uuid}/edit`}
            className="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all hover:bg-(--bg-hover)"
            style={{
              background: 'var(--accent-primary)',
              color: 'var(--color-white)',
            }}
          >
            <IconEdit /> Edit Profile
          </Link>
        </div>

        {/* ── Grid Layout ── */}
        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
            
          {/* Main Info Column */}
          <div className="md:col-span-2 space-y-6">
            
            {/* Contact Details Card */}
            <div className="card-modern shadow-md">
              <h2 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
                Contact Information
              </h2>
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>
                    <IconBuilding />
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
                      NIF
                    </p>
                    <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
                      {company.nif ?? 'Not specified'}
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>
                    <IconMail />
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
                      Email Address
                    </p>
                    <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
                      {company.email ? (
                        <a href={`mailto:${company.email}`} className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                           {company.email}
                        </a>
                      ) : 'Not specified'}
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>
                    <IconPhone />
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
                      Phone Number
                    </p>
                    <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
                      {company.phone ? (
                        <a href={`tel:${company.phone}`} className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                           {company.phone}
                        </a>
                      ) : 'Not specified'}
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>
                    <IconGlobe />
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
                      Website
                    </p>
                    <p className=" mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
                      {company.socialLinks?.website ? (
                        <a href={company.socialLinks.website} target="_blank" rel="noopener noreferrer" className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                           {company.socialLinks.website}
                        </a>
                      ) : 'Not specified'}
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>
                    <IconMapPin />
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
                      Address
                    </p>
                    <p className="mt-1 text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--text-secondary)' }}>
                      {company.address ?? 'Not specified'}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Geographic Coordinates Card (if map is needed later) */}
            <div className="card-modern shadow-md">
               <h2 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
                Geographic Data
              </h2>
              <div className="grid grid-cols-2 gap-4">
                <div>
                   <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>Latitude</p>
                   <p className="mt-1 text-sm font-mono" style={{ color: 'var(--text-secondary)' }}>{company.coordinates?.latitude ?? '—'}</p>
                </div>
                <div>
                   <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>Longitude</p>
                   <p className="mt-1 text-sm font-mono" style={{ color: 'var(--text-secondary)' }}>{company.coordinates?.longitude ?? '—'}</p>
                </div>
              </div>
            </div>

          </div>

          {/* Social Links & Metadata Column */}
          <div className="space-y-6">
            <div className="card-modern">
              <h2 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
                Social Profiles
              </h2>
              <div className="space-y-4">
                {[
                  { label: 'LinkedIn', url: company.socialLinks?.linkedin },
                  { label: 'Twitter', url: company.socialLinks?.twitter },
                  { label: 'Facebook', url: company.socialLinks?.facebook },
                  { label: 'Instagram', url: company.socialLinks?.instagram },
                ].map((social) => (
                  <div key={social.label}>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
                      {social.label}
                    </p>
                    <p className="mt-1 text-sm truncate" style={{ color: 'var(--text-secondary)' }}>
                      {social.url ? (
                        <a href={social.url} target="_blank" rel="noopener noreferrer" className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                           {new URL(social.url).hostname.replace('www.', '')}
                        </a>
                      ) : '—'}
                    </p>
                  </div>
                ))}
              </div>
            </div>

            <div className="card-modern">
               <h2 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
                Metadata
              </h2>
              <div className="space-y-4 text-sm" style={{ color: 'var(--text-secondary)' }}>
                 <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
                     <span style={{ color: 'var(--text-disabled)' }}>Owner User ID:</span>
                     <span className="font-mono">{company.userUuid}</span>
                 </div>
                 <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
                     <span style={{ color: 'var(--text-disabled)' }}>Created At:</span>
                     <span>{new Date(company.createdAt).toLocaleString()}</span>
                 </div>
                 <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
                     <span style={{ color: 'var(--text-disabled)' }}>Updated At:</span>
                     <span>{company.updatedAt ? new Date(company.updatedAt).toLocaleString() : 'Never'}</span>
                 </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </AppLayout>
  );
}
