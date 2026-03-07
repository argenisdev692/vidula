import * as React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { useSingleClient } from '@/modules/clients/hooks/useClient';
import ClientStatusBadge from '@/modules/clients/components/ClientStatusBadge';
import type { PageProps } from '@inertiajs/core';
import {
  ArrowLeft, Pencil, Building2, Mail, Phone,
  Globe, MapPin, Share2, Calendar,
} from 'lucide-react';
import { formatDateShort } from '@/common/helpers/formatDate';

export default function ClientShowPage(): React.JSX.Element {
  const { props } = usePage<PageProps & { clientId: string }>();

  // Extract uuid from url if inertia prop doesn't supply it directly
  const urlParts = window.location.pathname.split('/');
  const finalUuid = props.clientId || urlParts[urlParts.length - 1];

  const { data: client, isPending, isError } = useSingleClient(finalUuid);

  if (isPending) {
    return (
      <AppLayout>
        <Head title="Client Profile" />
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <div
            className="h-10 w-10 rounded-full animate-spin"
            style={{ border: '4px solid var(--accent-primary)', borderTopColor: 'transparent' }}
          />
          <p className="text-sm font-medium animate-pulse" style={{ color: 'var(--text-muted)' }}>
            Loading client profile...
          </p>
        </div>
      </AppLayout>
    );
  }

  if (isError || !client) {
    return (
      <AppLayout>
        <Head title="Client Not Found" />
        <div className="flex h-[50vh] items-center justify-center">
          <p style={{ color: 'var(--accent-error)' }}>Failed to load client profile.</p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`${client.client_name} — Profile`} />
      <div
        className="max-w-5xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-300 pb-12"
        style={{ fontFamily: 'var(--font-sans)' }}
      >

        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/clients"
              className="flex h-10 w-10 items-center justify-center rounded-xl shadow-sm transition-all"
              aria-label="Back to clients"
              style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border-default)',
                color: 'var(--text-muted)',
              }}
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1
                className="text-2xl font-extrabold tracking-tight"
                style={{ color: 'var(--text-primary)' }}
              >
                {client.client_name}
              </h1>
              <div className="mt-1 flex items-center gap-3">
                <ClientStatusBadge status={client.deleted_at ? 'deleted' : 'active'} />
                <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                  ID: {client.uuid.substring(0, 8)}…
                </span>
                <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                  Registered: {formatDateShort(client.created_at)}
                </span>
              </div>
            </div>
          </div>
          <PermissionGuard permissions={['UPDATE_CLIENTS']}>
            <Link
              href={`/clients/${client.uuid}/edit`}
              className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 font-bold shadow-lg"
            >
              <Pencil size={16} /> Edit Profile
            </Link>
          </PermissionGuard>
        </div>

        {/* ── Grid Layout ── */}
        <div className="grid grid-cols-1 gap-8 md:grid-cols-3">

          {/* Main Info Column */}
          <div className="md:col-span-2 space-y-8">

            {/* Contact Details Card */}
            <section className="card p-8 shadow-2xl">
              <div className="flex items-center gap-3 mb-6">
                <Building2 size={22} style={{ color: 'var(--accent-primary)' }} />
                <h2 className="text-lg font-bold" style={{ color: 'var(--text-primary)' }}>Contact Information</h2>
              </div>
              <div className="space-y-5">
                {/* NIF */}
                <InfoRow icon={<Building2 size={16} />} label="NIF" value={client.nif ?? 'Not specified'} />
                {/* Email */}
                <InfoRow
                  icon={<Mail size={16} />}
                  label="Email Address"
                  value={client.email ? (
                    <a href={`mailto:${client.email}`} className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                      {client.email}
                    </a>
                  ) : 'Not specified'}
                />
                {/* Phone */}
                <InfoRow
                  icon={<Phone size={16} />}
                  label="Phone Number"
                  value={client.phone ? (
                    <a href={`tel:${client.phone}`} className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                      {client.phone}
                    </a>
                  ) : 'Not specified'}
                />
                {/* Website */}
                <InfoRow
                  icon={<Globe size={16} />}
                  label="Website"
                  value={client.social_links?.website ? (
                    <a href={client.social_links.website} target="_blank" rel="noopener noreferrer" className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                      {client.social_links.website}
                    </a>
                  ) : 'Not specified'}
                />
                {/* Address */}
                <InfoRow
                  icon={<MapPin size={16} />}
                  label="Address"
                  value={
                    <span className="whitespace-pre-wrap leading-relaxed">
                      {client.address ?? 'Not specified'}
                    </span>
                  }
                />
              </div>
            </section>

            {/* Geographic Coordinates Card */}
            <section className="card p-8 shadow-2xl">
              <div className="flex items-center gap-3 mb-6">
                <MapPin size={22} style={{ color: 'var(--accent-primary)' }} />
                <h2 className="text-lg font-bold" style={{ color: 'var(--text-primary)' }}>Geographic Data</h2>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-muted)' }}>Latitude</p>
                  <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)', fontFamily: 'var(--font-mono)' }}>
                    {client.coordinates?.latitude ?? '—'}
                  </p>
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-muted)' }}>Longitude</p>
                  <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)', fontFamily: 'var(--font-mono)' }}>
                    {client.coordinates?.longitude ?? '—'}
                  </p>
                </div>
              </div>
            </section>
          </div>

          {/* Social Links & Metadata Column */}
          <div className="space-y-8">
            <section className="card p-6">
              <div className="flex items-center gap-3 mb-6">
                <Share2 size={20} style={{ color: 'var(--accent-primary)' }} />
                <h2 className="text-base font-bold" style={{ color: 'var(--text-primary)' }}>Social Profiles</h2>
              </div>
              <div className="space-y-4">
                {[
                  { label: 'LinkedIn', url: client.social_links?.linkedin },
                  { label: 'Twitter', url: client.social_links?.twitter },
                  { label: 'Facebook', url: client.social_links?.facebook },
                  { label: 'Instagram', url: client.social_links?.instagram },
                ].map((social) => (
                  <div key={social.label}>
                    <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-muted)' }}>
                      {social.label}
                    </p>
                    <p className="mt-1 text-sm truncate" style={{ color: 'var(--text-secondary)' }}>
                      {social.url ? (
                        <a href={social.url} target="_blank" rel="noopener noreferrer" className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                          {(() => { try { return new URL(social.url).hostname.replace('www.', ''); } catch { return social.url; } })()}
                        </a>
                      ) : '—'}
                    </p>
                  </div>
                ))}
              </div>
            </section>

            <section className="card p-6">
              <div className="flex items-center gap-3 mb-6">
                <Calendar size={20} style={{ color: 'var(--accent-primary)' }} />
                <h2 className="text-base font-bold" style={{ color: 'var(--text-primary)' }}>Metadata</h2>
              </div>
              <div className="space-y-4 text-sm" style={{ color: 'var(--text-secondary)' }}>
                <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Owner User ID:</span>
                  <span style={{ fontFamily: 'var(--font-mono)' }}>{client.user_uuid}</span>
                </div>
                <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Created At:</span>
                  <span>{formatDateShort(client.created_at)}</span>
                </div>
                <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Updated At:</span>
                  <span>{client.updated_at ? formatDateShort(client.updated_at) : 'Never'}</span>
                </div>
              </div>
            </section>
          </div>

        </div>
      </div>
    </AppLayout>
  );
}

/* ── Reusable info row ── */
function InfoRow({ icon, label, value }: {
  icon: React.ReactNode;
  label: string;
  value: React.ReactNode;
}): React.JSX.Element {
  return (
    <div className="flex items-start gap-3">
      <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>{icon}</div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-muted)' }}>
          {label}
        </p>
        <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
          {value}
        </p>
      </div>
    </div>
  );
}
