import * as React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useSingleProduct } from '@/modules/products/hooks/useProduct';
import ProductStatusBadge from '@/modules/products/components/ProductStatusBadge';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import type { PageProps } from '@inertiajs/core';
import { ArrowLeft, Edit, Globe, Tag, Info, Layers, Calendar, DollarSign } from 'lucide-react';

import { formatDateShort } from '@/common/helpers/formatDate';

export default function ProductShowPage(): React.JSX.Element {
  const { props } = usePage<PageProps & { productId: string }>();
  
  const urlParts = typeof window !== 'undefined' ? window.location.pathname.split('/') : [];
  const finalUuid = props.productId || (urlParts.length > 0 ? urlParts[urlParts.length - 1] : ''); 

  const { data: product, isPending, isError } = useSingleProduct(finalUuid);

  if (isPending) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4 animate-pulse">
           <div className="h-10 w-10 border-4 rounded-full animate-spin" style={{ borderColor: 'var(--accent-primary)', borderTopColor: 'transparent' }} />
           <p style={{ color: 'var(--text-muted)' }}>Loading product details...</p>
        </div>
      </AppLayout>
    );
  }

  if (isError || !product) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] items-center justify-center">
          <p style={{ color: 'var(--accent-error)' }}>Failed to load product details.</p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`${product.title} - Product Details`} />
      <PermissionGuard permissions={['VIEW ANY PRODUCTS']}>
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
                  {product.title}
                </h1>
                <div className="mt-1 flex items-center gap-3">
                  <ProductStatusBadge status={product.deleted_at ? 'deleted' : 'active'} />
                  <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                    {product.type.toUpperCase()} • ID: {product.id.substring(0, 8)}...
                  </span>
                </div>
              </div>
            </div>
            <Link
              href={`/products/${product.id}/edit`}
              className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-xl transition-all font-bold"
            >
              <Edit size={18} /> Edit Product
            </Link>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              
            {/* ── Main content (Left) ── */}
            <div className="lg:col-span-2 space-y-8">
              
              {/* Product Info Card */}
              <div className="card-modern p-8 shadow-2xl glass-morphism">
                <div className="flex items-center gap-3 mb-6">
                   <Info style={{ color: 'var(--accent-primary)' }} size={24} />
                   <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Product Overview</h2>
                </div>
                
                <div className="space-y-6">
                  <div>
                    <label className="text-[11px] font-bold uppercase tracking-widest mb-2 block" style={{ color: 'var(--text-disabled)' }}>
                      Description
                    </label>
                    <p className="text-sm leading-relaxed whitespace-pre-wrap" style={{ color: 'var(--text-secondary)' }}>
                      {product.description || 'No description provided.'}
                    </p>
                  </div>

                  <div className="grid grid-cols-2 gap-6 pt-4" style={{ borderTop: '1px solid var(--border-subtle)' }}>
                    <div>
                        <label className="text-[11px] font-bold uppercase tracking-widest mb-1 block" style={{ color: 'var(--text-disabled)' }}>
                          Level
                        </label>
                        <div className="flex items-center gap-2 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
                           <Layers size={14} style={{ color: 'var(--accent-info)' }} />
                           {product.level}
                        </div>
                    </div>
                    <div>
                        <label className="text-[11px] font-bold uppercase tracking-widest mb-1 block" style={{ color: 'var(--text-disabled)' }}>
                          Language
                        </label>
                        <div className="flex items-center gap-2 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
                           <Globe size={14} style={{ color: 'var(--accent-info)' }} />
                           {product.language}
                        </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Pricing & Commercial Card */}
              <div className="card-modern p-8 shadow-2xl glass-morphism">
                <div className="flex items-center gap-3 mb-6">
                   <DollarSign style={{ color: 'var(--accent-success)' }} size={24} />
                   <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Pricing & Commercial</h2>
                </div>
                
                <div className="grid grid-cols-2 gap-8">
                  <div className="p-4 rounded-xl" style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)' }}>
                      <label className="text-[10px] font-bold uppercase tracking-widest mb-1 block" style={{ color: 'var(--text-disabled)' }}>
                        Base Price
                      </label>
                      <div className="text-2xl font-black" style={{ color: 'var(--accent-success)' }}>
                        {product.price} <span className="text-sm font-normal">{product.currency}</span>
                      </div>
                  </div>
                  <div className="p-4 rounded-xl" style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)' }}>
                      <label className="text-[10px] font-bold uppercase tracking-widest mb-1 block" style={{ color: 'var(--text-disabled)' }}>
                        Module Identifier
                      </label>
                      <div className="text-sm font-mono mt-1" style={{ color: 'var(--text-secondary)' }}>
                        {product.slug}
                      </div>
                  </div>
                </div>
              </div>
            </div>

            {/* ── Sidebar (Right) ── */}
            <div className="space-y-8">
              <div className="card-modern p-6" style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-subtle)' }}>
                <div className="flex items-center gap-3 mb-6">
                   <Tag style={{ color: 'var(--accent-primary)' }} size={20} />
                   <h3 className="text-sm font-bold uppercase tracking-widest" style={{ color: 'var(--text-muted)' }}>Attributes</h3>
                </div>
                <div className="space-y-4">
                  <div className="flex items-center justify-between py-2" style={{ borderBottom: '1px solid var(--border-subtle)' }}>
                     <span className="text-xs" style={{ color: 'var(--text-disabled)' }}>Type</span>
                     <span className="text-xs font-bold uppercase" style={{ color: 'var(--text-secondary)' }}>{product.type}</span>
                  </div>
                  <div className="flex items-center justify-between py-2" style={{ borderBottom: '1px solid var(--border-subtle)' }}>
                     <span className="text-xs" style={{ color: 'var(--text-disabled)' }}>Status</span>
                     <ProductStatusBadge status={product.deleted_at ? 'deleted' : 'active'} />
                  </div>
                </div>
              </div>

              <div className="card-modern p-6" style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-subtle)' }}>
                <div className="flex items-center gap-3 mb-6">
                   <Calendar style={{ color: 'var(--accent-primary)' }} size={20} />
                   <h3 className="text-sm font-bold uppercase tracking-widest" style={{ color: 'var(--text-muted)' }}>Project Dates</h3>
                </div>
                <div className="space-y-4">
                  <div>
                    <span className="text-[10px] font-bold uppercase tracking-tighter block" style={{ color: 'var(--text-disabled)' }}>Created</span>
                    <span className="text-xs" style={{ color: 'var(--text-secondary)' }}>{formatDateShort(product.created_at)}</span>
                  </div>
                  {product.updated_at && (
                    <div>
                      <span className="text-[10px] font-bold uppercase tracking-tighter block" style={{ color: 'var(--text-disabled)' }}>Latest Update</span>
                      <span className="text-xs" style={{ color: 'var(--text-secondary)' }}>{formatDateShort(product.updated_at)}</span>
                    </div>
                  )}
                </div>
              </div>
            </div>

          </div>
        </div>
      </PermissionGuard>
    </AppLayout>
  );
}
