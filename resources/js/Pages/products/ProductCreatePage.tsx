import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useProductMutations } from '@/modules/products/hooks/useProductMutations';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { PremiumField } from '@/shadcn/PremiumField';
import type { CreateProductDTO } from '@/types/api';
import { ArrowLeft, Save, Package, Tag, DollarSign } from 'lucide-react';

export default function ProductCreatePage(): React.JSX.Element {
  const { createProduct: createMutation } = useProductMutations();
  const [formData, setFormData] = React.useState<CreateProductDTO>({
    type: 'COURSE', // Default type
    title: '',
    price: 0,
    currency: 'EUR',
    description: '',
    level: 'BEGINNER',
    language: 'SPANISH',
    status: 'ACTIVE',
  });

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
      <PermissionGuard permissions={['CREATE PRODUCTS']}>
        <div className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-300 pb-12">
          
          {/* ── Header ── */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href="/products"
                className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) transition-all shadow-sm"
              >
                <ArrowLeft size={20} />
              </Link>
              <div>
                <h1 className="text-2xl font-extrabold tracking-tight text-(--text-primary)">
                  New Catalog Entry
                </h1>
                <p className="text-sm font-medium text-(--text-muted)">
                   Register a new educational product or service.
                </p>
              </div>
            </div>
            <button
              onClick={handleSubmit}
              disabled={createMutation.isPending}
              className="btn-modern-primary flex items-center gap-2 px-8 py-2.5 shadow-xl hover:shadow-(--accent-primary)/20 transition-all font-bold disabled:opacity-50"
            >
              {createMutation.isPending ? 'Saving...' : <><Save size={18} /> Save Product</>}
            </button>
          </div>

          <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {/* ── Main Section ── */}
            <div className="lg:col-span-2 space-y-8">
                <section className="card-modern p-8 space-y-8 shadow-2xl glass-morphism">
                    <div className="flex items-center gap-3">
                        <Package className="text-(--accent-primary)" size={24} />
                        <h2 className="text-xl font-bold text-(--text-primary)">Base Information</h2>
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
                            label="Description" 
                            name="description" 
                            value={formData.description} 
                            onChange={handleChange} 
                            isTextArea
                            placeholder="Detailed explanation of the product..."
                        />
                    </div>
                </section>

                <section className="card-modern p-8 space-y-8 shadow-2xl glass-morphism">
                    <div className="flex items-center gap-3">
                        <DollarSign className="text-(--accent-success)" size={24} />
                        <h2 className="text-xl font-bold text-(--text-primary)">Commercial Settings</h2>
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
                            <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary)">Currency</label>
                            <select 
                                name="currency" 
                                value={formData.currency} 
                                onChange={(e) => setFormData(p => ({ ...p, currency: e.target.value }))}
                                className="w-full rounded-xl px-4 py-3 text-sm bg-(--bg-card) border border-(--border-default) text-(--text-primary) outline-none focus:ring-2 focus:ring-(--accent-primary)"
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
                <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-6">
                    <div className="flex items-center gap-3 mb-2">
                        <Tag className="text-(--accent-primary)" size={18} />
                        <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted)">Classification</h3>
                    </div>

                    <div className="space-y-4">
                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider text-(--text-disabled)">Product Type</label>
                             <select 
                                name="type" 
                                value={formData.type} 
                                onChange={(e) => setFormData(p => ({ ...p, type: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs bg-(--bg-card) border border-(--border-default) text-(--text-primary)"
                            >
                                <option value="COURSE">Course</option>
                                <option value="WORKSHOP">Workshop</option>
                                <option value="EBOOK">E-Book</option>
                                <option value="BAGGAGE">Baggage</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider text-(--text-disabled)">Difficulty Level</label>
                             <select 
                                name="level" 
                                value={formData.level} 
                                onChange={(e) => setFormData(p => ({ ...p, level: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs bg-(--bg-card) border border-(--border-default) text-(--text-primary)"
                            >
                                <option value="BEGINNER">Beginner</option>
                                <option value="INTERMEDIATE">Intermediate</option>
                                <option value="ADVANCED">Advanced</option>
                                <option value="EXPERT">Expert</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[10px] font-bold uppercase tracking-wider text-(--text-disabled)">Language</label>
                             <select 
                                name="language" 
                                value={formData.language} 
                                onChange={(e) => setFormData(p => ({ ...p, language: e.target.value }))}
                                className="w-full rounded-xl px-3 py-2 text-xs bg-(--bg-card) border border-(--border-default) text-(--text-primary)"
                            >
                                <option value="SPANISH">Spanish</option>
                                <option value="ENGLISH">English</option>
                                <option value="PORTUGUESE">Portuguese</option>
                                <option value="FRENCH">French</option>
                            </select>
                        </div>
                    </div>
                </section>

                <div className="p-4 rounded-xl border border-dashed border-(--border-subtle) text-center">
                    <p className="text-[10px] text-(--text-disabled) leading-relaxed">
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
