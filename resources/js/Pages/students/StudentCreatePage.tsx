import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useStudentMutations } from '@/modules/students/hooks/useStudentMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { CreateStudentDTO } from '@/types/api';
import { ArrowLeft, Save, Building2, Share2 } from 'lucide-react';

/**
 * StudentCreatePage - React 19 Modern Implementation
 * Uses standard form with FormData API and TanStack Query mutations
 */
export default function StudentCreatePage(): React.JSX.Element {
  const { createStudent } = useStudentMutations();
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    
    const payload: CreateStudentDTO = {
      user_id: 1, // Default user_id, should come from auth context in production
      company_name: formData.get('company_name') as string,
      name: formData.get('name') as string || null,
      email: formData.get('email') as string || null,
      phone: formData.get('phone') as string || null,
      address: formData.get('address') as string || null,
      website: formData.get('website') as string || null,
      linkedin_link: formData.get('linkedin_link') as string || null,
      twitter_link: formData.get('twitter_link') as string || null,
      facebook_link: formData.get('facebook_link') as string || null,
      instagram_link: formData.get('instagram_link') as string || null,
      latitude: formData.get('latitude') ? parseFloat(formData.get('latitude') as string) : null,
      longitude: formData.get('longitude') ? parseFloat(formData.get('longitude') as string) : null,
    };

    try {
      await createStudent.mutateAsync(payload);
      router.visit('/student');
    } catch (err: any) {
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      }
    }
  }
  
  const isPending = createStudent.isPending;

  return (
    <AppLayout>
      <Head title="Create Company Profile" />
      <div className="max-w-5xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12">
        
        {/* ── Header ── */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/student"
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">New Company Profile</h1>
              <p className="text-sm text-(--text-muted)">Register a new corporate entity</p>
            </div>
          </div>

          <button
            type="submit"
            form="company-create-form"
            disabled={isPending}
            className="btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
          >
            {isPending ? (
              <span className="animate-pulse">Creating...</span>
            ) : (
              <>
                <Save size={18} />
                <span className="font-bold">Save Profile</span>
              </>
            )}
          </button>
        </div>

        {/* Global Error */}
        {errors.general && (
          <div className="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <p className="text-sm text-red-600 dark:text-red-400">{errors.general}</p>
          </div>
        )}

        {/* ── Form Body ── */}
        <form id="company-create-form" onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* ── Left Column: Main Info ── */}
            <div className="lg:col-span-2 space-y-6">
              <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <Building2 className="text-(--accent-primary)" size={24} />
                  <h2 className="text-lg font-bold text-(--text-primary)">Core Information</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="md:col-span-2">
                    <PremiumField 
                      label="Official Company Name" 
                      name="company_name" 
                      required 
                      error={errors.company_name?.[0]} 
                      placeholder="Acme Corporation S.A."
                    />
                  </div>
                  <PremiumField 
                    label="Legal Representative" 
                    name="name" 
                    error={errors.name?.[0]} 
                    placeholder="John Smith"
                  />
                  <PremiumField 
                    label="Business Email" 
                    name="email" 
                    type="email"
                    error={errors.email?.[0]} 
                    placeholder="billing@acme.com"
                  />
                  <PremiumField 
                    label="Public Phone" 
                    name="phone" 
                    error={errors.phone?.[0]} 
                    placeholder="+1 800-ACME-CORP"
                  />
                  <div className="md:col-span-2">
                    <PremiumField 
                      label="Official Website" 
                      name="website" 
                      type="url"
                      error={errors.website?.[0]} 
                      placeholder="https://acme.com"
                    />
                  </div>
                  <div className="md:col-span-2">
                    <PremiumField 
                      label="Primary Address" 
                      name="address" 
                      error={errors.address?.[0]} 
                      isTextArea
                      placeholder="123 Corporate Way, Silicon Valley, CA"
                    />
                  </div>
                </div>
              </div>

              <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <Share2 className="text-(--accent-primary)" size={24} />
                  <h2 className="text-lg font-bold text-(--text-primary)">Social Media & Public Presence</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <PremiumField 
                    label="LinkedIn" 
                    name="linkedin_link" 
                    error={errors.linkedin_link?.[0]} 
                    placeholder="linkedin.com/company/acme"
                  />
                  <PremiumField 
                    label="Instagram" 
                    name="instagram_link" 
                    error={errors.instagram_link?.[0]} 
                    placeholder="instagram.com/acme"
                  />
                  <PremiumField 
                    label="Twitter / X" 
                    name="twitter_link" 
                    error={errors.twitter_link?.[0]} 
                    placeholder="x.com/acme"
                  />
                  <PremiumField 
                    label="Facebook" 
                    name="facebook_link" 
                    error={errors.facebook_link?.[0]} 
                    placeholder="facebook.com/acme"
                  />
                </div>
              </div>
            </div>

            {/* ── Right Column: Sidebar ── */}
            <div className="space-y-6">
              <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted) mb-4">Geolocation</h3>
                
                <div className="space-y-4">
                  <PremiumField 
                    label="Latitude" 
                    name="latitude" 
                    type="number"
                    step="any"
                    error={errors.latitude?.[0]} 
                    placeholder="40.7128"
                  />
                  <PremiumField 
                    label="Longitude" 
                    name="longitude" 
                    type="number"
                    step="any"
                    error={errors.longitude?.[0]} 
                    placeholder="-74.0060"
                  />
                </div>
                
                <div className="mt-4 p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
                  <p className="text-[11px] text-(--text-disabled) leading-relaxed text-center italic">
                    Used for public map listings and service discovery.
                  </p>
                </div>
              </div>

              <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted) mb-4">Quick Tips</h3>
                <ul className="space-y-2 text-xs text-(--text-muted)">
                  <li className="flex items-start gap-2">
                    <span className="text-(--accent-primary) mt-0.5">•</span>
                    <span>Company name is required for registration</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-(--accent-primary) mt-0.5">•</span>
                    <span>Social links help improve public visibility</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-(--accent-primary) mt-0.5">•</span>
                    <span>Geolocation enables map-based discovery</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
