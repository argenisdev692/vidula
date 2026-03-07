import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useCurrentUser } from '@/modules/auth/hooks/useCurrentUser';
import AppLayout from '@/pages/layouts/AppLayout';
import { useProductMutations } from '@/modules/products/hooks/useProductMutations';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { PremiumField } from '@/shadcn/PremiumField';
import type { CreateProductDTO } from '@/modules/products/types';
import { ArrowLeft, Save, Package, Tag, DollarSign } from 'lucide-react';

export default function ProductCreatePage(): React.JSX.Element {
  const currentUser = useCurrentUser();
  const { createProduct: createMutation } = useProductMutations();
  const [formData, setFormData] = React.useState<CreateProductDTO>({
    user_id: currentUser?.uuid ?? '',
    type: 'classroom',
    title: '',
    slug: '',
    price: 0,
    currency: 'EUR',
    description: '',
    level: 'beginner',
    language: 'es',
    thumbnail: null,
  });

  React.useEffect(() => {
    if (currentUser?.uuid) {
      setFormData((prev) => ({ ...prev, user_id: currentUser.uuid }));
    }
  }, [currentUser?.uuid]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ 
        ...prev, 
        [name]: name === 'price' ? parseFloat(value) || 0 : value 
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(formData, {
      onSuccess: () => {
        router.visit('/products');
      }
    });
  };

  return (
    <AppLayout>
      <Head title="Create New Product" />
      <PermissionGuard permissions={['CREATE_PRODUCTS']}>
        <div className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-300 pb-12">
          
          {/* ── Header ── */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href="/products"
                className="flex h-10 w-10 items-center justify-center rounded-xl transition-all shadow-sm"
                style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)', color: 'var(--text-muted)' }}
                aria-label="Back to products"
              >
                <ArrowLeft size={20} />
              </Link>
              <div>
                <h1 className="text-2xl font-extrabold tracking-tight" style={{ color: 'var(--text-primary)' }}>
                  New Catalog Entry
                </h1>
                <p className="text-sm font-medium" style={{ color: 'var(--text-muted)' }}>
                   Register a new educational product or service.
                </p>
              </div>
            </div>
            <button
              type="submit"
              form="create-product-form"
              disabled={createMutation.isPending}
              className="btn-modern btn-modern-primary flex items-center gap-2 px-8 py-2.5 shadow-xl transition-all font-bold disabled:opacity-50"
            >
              {createMutation.isPending ? 'Saving...' : <><Save size={18} /> Save Product</>}
            </button>
          </div>

          <form id="create-product-form" onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {/* ── Main Section ── */}
            <div className="lg:col-span-2 space-y-8">
                <section className="card p-8 space-y-8 shadow-2xl">
                    <div className="flex items-center gap-3">
                        <Package size={24} style={{ color: 'var(--accent-primary)' }} />
                        <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Base Information</h2>
                    </div>

                    <div className="grid grid-cols-1 gap-6">
                        <PremiumField 
                            label="Product Title" 
                            name="title" 
                            value={formData.title} 
                            onChange={handleChange} 
                            required 
                            placeholder="e.g. Master in Web Development"
                        />
                        <PremiumField 
                            label="Slug" 
                            name="slug" 
                            value={formData.slug} 
                            onChange={handleChange} 
                            required 
                            placeholder="e.g. master-in-web-development"
                        />
                        <PremiumField 
                            label="Description" 
                            name="description" 
                            value={formData.description ?? ''} 
                            onChange={handleChange} 
                            isTextArea
                            placeholder="Detailed explanation of the product..."
                        />
                    </div>
                </section>

                <section className="card p-8 space-y-8 shadow-2xl">
                    <div className="flex items-center gap-3">
                        <DollarSign size={24} style={{ color: 'var(--accent-success)' }} />
                        <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Commercial Settings</h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <PremiumField 
                            label="Price" 
                            name="price" 
                            type="number"
                            step="0.01"
                            value={formData.price} 
                            onChange={handleChange} 
                            required 
                        />
                        <div className="flex flex-col gap-2">
                            <label className="text-[11px] font-bold uppercase tracking-widest" style={{ color: 'var(--text-secondary)' }}>Currency</label>
                            <select 
                                name="currency" 
                                value={formData.currency} 
                                onChange={(e) => setFormData(p => ({ ...p, currency: e.target.value }))}
                                className="w-full rounded-xl px-4 py-3 text-sm outline-none focus:ring-2"
                                style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)', color: 'var(--text-primary)' }}
                            >
                                <option value="EUR">Euro (EUR)</option>
                                <option value="USD">Dollar (USD)</option>
                                <option value="GBP">Pound (GBP)</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            {/* ── Sidebar ── */}
            <div className="space-y-8">
                <section className="card p-6 space-y-6" style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-subtle)' }}>
                    <div className="flex items-center gap-3 mb-2">
                        <Tag size={18} style={{ color: 'var(--accent-primary)' }} />
                        <h3 className="text-sm font-bold uppercase tracking-widest" style={{ color: 'var(--text-muted)' }}>Classification</h3>
                    </div>

                    <div className="space-y-4">
                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider" style={{ color: 'var(--text-secondary)' }}>Product Type</label>
                             <select 
                                name="type" 
                                value={formData.type} 
                                onChange={(e) => setFormData(p => ({ ...p, type: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs"
                                style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)', color: 'var(--text-primary)' }}
                            >
                                <option value="classroom">Classroom</option>
                                <option value="video">Video</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider" style={{ color: 'var(--text-secondary)' }}>Difficulty Level</label>
                             <select 
                                name="level" 
                                value={formData.level} 
                                onChange={(e) => setFormData(p => ({ ...p, level: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs"
                                style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)', color: 'var(--text-primary)' }}
                            >
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider" style={{ color: 'var(--text-secondary)' }}>Language</label>
                             <select 
                                name="language" 
                                value={formData.language} 
                                onChange={(e) => setFormData(p => ({ ...p, language: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs"
                                style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)', color: 'var(--text-primary)' }}
                            >
                                <option value="es">Spanish</option>
                                <option value="en">English</option>
                                <option value="pt">Portuguese</option>
                                <option value="fr">French</option>
                            </select>
                        </div>
                    </div>
                </section>

                <div className="p-4 rounded-xl border border-dashed text-center" style={{ borderColor: 'var(--border-subtle)' }}>
                    <p className="text-[10px] leading-relaxed" style={{ color: 'var(--text-disabled)' }}>
                        Validation will occur upon saving. Ensure all mandatory fields marked with * are filled.
                    </p>
                </div>
            </div>
          </form>
        </div>
      </PermissionGuard>
    </AppLayout>
  );
}
