import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useSingleProduct } from '@/modules/products/hooks/useProduct';
import { useProductMutations } from '@/modules/products/hooks/useProductMutations';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { PremiumField } from '@/shadcn/PremiumField';
import type { UpdateProductDTO } from '@/types/api';
import type { PageProps } from '@inertiajs/core';
import { ArrowLeft, Save, Package, DollarSign, Tag } from 'lucide-react';

export default function ProductEditPage(): React.JSX.Element {
  const { props } = usePage<PageProps & { productId?: string }>();
  
  const urlParts = typeof window !== 'undefined' ? window.location.pathname.split('/') : [];
  const uuid = props.productId || (urlParts.length > 1 ? urlParts[urlParts.length - 2] : ''); 

  const { data: product, isPending, isError } = useSingleProduct(uuid);
  const { updateProduct } = useProductMutations();

  const [form, setForm] = React.useState<UpdateProductDTO>({
    type: '',
    title: '',
    price: 0,
    currency: '',
    description: '',
    level: '',
    language: '',
    status: '',
  });

  React.useEffect(() => {
    if (product) {
      setForm({
        type: product.type,
        title: product.title,
        price: product.price,
        currency: product.currency,
        description: product.description || '',
        level: product.level,
        language: product.language,
        status: product.status,
      });
    }
  }, [product]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setForm((prev) => ({ 
        ...prev, 
        [name]: name === 'price' ? parseFloat(value) || 0 : value 
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProduct.mutate({ userUuid: uuid, payload: form as any }, {
      onSuccess: () => {
        router.visit('/products');
      }
    });
  };

  if (isPending) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
            <div className="h-10 w-10 border-4 rounded-full animate-spin" style={{ borderColor: 'var(--accent-primary)', borderTopColor: 'transparent' }} />
            <p className="text-sm font-medium animate-pulse" style={{ color: 'var(--text-disabled)' }}>Loading Product Information...</p>
        </div>
      </AppLayout>
    );
  }

  if (isError || !product) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
            <p className="text-sm font-medium" style={{ color: 'var(--accent-error)' }}>Failed to load product details or product not found.</p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`Edit Product | ${product?.title}`} />
      <PermissionGuard permissions={['EDIT PRODUCTS']}>
        <div className="max-w-5xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-300 pb-12">
          
          {/* ── Header ── */}
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-4">
              <Link
                href="/products"
                className="flex h-10 w-10 items-center justify-center rounded-xl shadow-sm transition-all"
                style={{ 
                  background: 'var(--bg-card)', 
                  border: '1px solid var(--border-default)', 
                  color: 'var(--text-muted)' 
                }}
              >
                <ArrowLeft size={20} />
              </Link>
              <div>
                <h1 className="text-3xl font-extrabold tracking-tight" style={{ color: 'var(--text-primary)' }}>
                  Edit Course Catalog
                </h1>
                <p className="text-sm font-medium" style={{ color: 'var(--text-muted)' }}>
                  Updating details for <span style={{ color: 'var(--accent-primary)' }}>{product?.title}</span>
                </p>
              </div>
            </div>

            <button
              onClick={handleSubmit}
              disabled={updateProduct.isPending}
              className="btn-modern btn-modern-primary flex items-center gap-2 px-8 py-3 shadow-xl transition-all font-bold"
            >
              {updateProduct.isPending ? 'Syncing...' : <><Save size={18} /> Update Item</>}
            </button>
          </div>

          <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* ── Main Section ── */}
            <div className="lg:col-span-2 space-y-8">
                <section className="card-modern p-8 space-y-8 shadow-2xl glass-morphism">
                    <div className="flex items-center gap-3">
                        <Package style={{ color: 'var(--accent-primary)' }} size={24} />
                        <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Basic Info</h2>
                    </div>

                    <div className="grid grid-cols-1 gap-6">
                        <PremiumField 
                            label="Product Title" 
                            name="title" 
                            value={form.title} 
                            onChange={handleChange} 
                            required 
                            placeholder="e.g. Acme Training Course"
                        />
                        <PremiumField 
                            label="Description" 
                            name="description" 
                            value={form.description || ''} 
                            onChange={handleChange} 
                            isTextArea
                            placeholder="About this product..."
                        />
                    </div>
                </section>

                <section className="card-modern p-8 space-y-8 shadow-2xl glass-morphism">
                    <div className="flex items-center gap-3">
                        <DollarSign style={{ color: 'var(--accent-success)' }} size={24} />
                        <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Pricing Details</h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <PremiumField 
                            label="Price" 
                            name="price" 
                            type="number"
                            step="0.01"
                            value={form.price} 
                            onChange={handleChange} 
                            required 
                        />
                        <div className="flex flex-col gap-2">
                             <label className="text-[11px] font-bold uppercase tracking-widest" style={{ color: 'var(--text-secondary)' }}>Currency</label>
                             <select 
                                name="currency" 
                                value={form.currency} 
                                onChange={(e) => setForm(p => ({ ...p, currency: e.target.value }))}
                                className="w-full rounded-xl px-4 py-3 text-sm outline-none focus:ring-2"
                                style={{ 
                                  background: 'var(--bg-card)', 
                                  border: '1px solid var(--border-default)', 
                                  color: 'var(--text-primary)',
                                  focusRing: 'var(--accent-primary)'
                                }}
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
                <section className="card-modern p-6 space-y-6" style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-subtle)' }}>
                    <div className="flex items-center gap-3 mb-2">
                        <Tag style={{ color: 'var(--accent-primary)' }} size={20} />
                        <h3 className="text-sm font-bold uppercase tracking-widest" style={{ color: 'var(--text-muted)' }}>Classification</h3>
                    </div>

                    <div className="space-y-4">
                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>Product Type</label>
                             <select 
                                name="type" 
                                value={form.type} 
                                onChange={(e) => setForm(p => ({ ...p, type: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs"
                                style={{ 
                                  background: 'var(--bg-card)', 
                                  border: '1px solid var(--border-default)', 
                                  color: 'var(--text-primary)' 
                                }}
                            >
                                <option value="COURSE">Course</option>
                                <option value="WORKSHOP">Workshop</option>
                                <option value="EBOOK">E-Book</option>
                                <option value="BAGGAGE">Baggage</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>Level</label>
                             <select 
                                name="level" 
                                value={form.level} 
                                onChange={(e) => setForm(p => ({ ...p, level: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs"
                                style={{ 
                                  background: 'var(--bg-card)', 
                                  border: '1px solid var(--border-default)', 
                                  color: 'var(--text-primary)' 
                                }}
                            >
                                <option value="BEGINNER">Beginner</option>
                                <option value="INTERMEDIATE">Intermediate</option>
                                <option value="ADVANCED">Advanced</option>
                                <option value="EXPERT">Expert</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>Language</label>
                             <select 
                                name="language" 
                                value={form.language} 
                                onChange={(e) => setForm(p => ({ ...p, language: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs"
                                style={{ 
                                  background: 'var(--bg-card)', 
                                  border: '1px solid var(--border-default)', 
                                  color: 'var(--text-primary)' 
                                }}
                            >
                                <option value="SPANISH">Spanish</option>
                                <option value="ENGLISH">English</option>
                                <option value="PORTUGUESE">Portuguese</option>
                                <option value="FRENCH">French</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>
          </form>
        </div>
      </PermissionGuard>
    </AppLayout>
  );
}
