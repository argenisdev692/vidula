import * as React from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useBlogCategory } from '@/modules/blog-categories/hooks/useBlogCategory';
import { useBlogCategoryMutations } from '@/modules/blog-categories/hooks/useBlogCategoryMutations';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';
import { formatDateShort } from '@/common/helpers/formatDate';
import type { BlogCategoryDetail } from '@/types/blog-categories';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';

// ══════════════════════════════════════════════════════════════
// Info Row
// ══════════════════════════════════════════════════════════════
function InfoRow({ label, value }: { label: string; value: string | null | undefined }): React.JSX.Element {
  return (
    <div className="grid grid-cols-3 gap-4 py-3 border-b border-(--border-subtle)">
      <dt className="text-sm font-medium text-(--text-muted)">{label}</dt>
      <dd className="col-span-2 text-sm font-medium text-(--text-primary)">{value || '—'}</dd>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════
// Props
// ══════════════════════════════════════════════════════════════
interface BlogCategoryShowPageProps {
  uuid: string;
  category?: BlogCategoryDetail;
}

// ══════════════════════════════════════════════════════════════
// BlogCategoryShowPage
// ══════════════════════════════════════════════════════════════
export default function BlogCategoryShowPage({ uuid, category }: BlogCategoryShowPageProps): React.JSX.Element {
  const { deleteBlogCategory } = useBlogCategoryMutations();
  const [pendingDelete, setPendingDelete] = React.useState<boolean>(false);

  // Fetch category via TanStack Query if not passed as an Inertia prop
  const { data: fetchedCategory } = useBlogCategory(category ? undefined : uuid);
  const cat = category ?? fetchedCategory;

  async function handleConfirmDelete(): Promise<void> {
    React.startTransition(async () => {
      try {
        await deleteBlogCategory.mutateAsync(uuid);
        router.visit('/blog-categories');
      } catch {
        // Error handled by mutation's onError toast
      }
    });
  }

  if (!cat) {
    return (
      <AppLayout>
        <Head title="Blog Category" />
        <div className="flex items-center justify-center py-24">
          <span className="text-(--text-muted) text-sm animate-pulse">Loading category...</span>
        </div>
      </AppLayout>
    );
  }

  const initial = (cat.blog_category_name?.[0] ?? 'C').toUpperCase();

  return (
    <>
      <Head title={`Category — ${cat.blog_category_name}`} />
      <AppLayout>
        <div style={{ fontFamily: 'var(--font-sans)' }}>
          {/* ── Header ── */}
          <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <Link
                href="/blog-categories"
                className="flex h-9 w-9 items-center justify-center rounded-lg border border-(--border-default) bg-(--bg-card) text-(--text-muted) hover:bg-(--bg-hover) transition-all"
              >
                <ArrowLeft size={16} />
              </Link>
              <div>
                <h1 className="text-xl font-bold text-(--text-primary)">Category Details</h1>
                <p className="text-sm text-(--text-muted)">#{cat.uuid}</p>
              </div>
            </div>

            <div className="flex gap-3">
              <Link
                href={`/blog-categories/${cat.uuid}/edit`}
                className="btn-modern btn-modern-primary px-4 py-2 inline-flex items-center gap-2"
              >
                <Pencil size={16} /> Edit
              </Link>
              <button
                onClick={() => setPendingDelete(true)}
                className="btn-modern btn-modern-danger px-4 py-2 inline-flex items-center gap-2"
              >
                <Trash2 size={16} /> Delete
              </button>
            </div>
          </div>

          {/* ── Category Card ── */}
          <div className="card-modern shadow-lg p-6">
            {/* Avatar + name header */}
            <div className="mb-6 flex items-center gap-4">
              <div
                className="flex h-16 w-16 items-center justify-center rounded-xl text-lg font-bold"
                style={{
                  background: 'var(--grad-primary)',
                  color: 'var(--text-primary)',
                }}
              >
                {initial}
              </div>
              <div>
                <h2 className="text-lg font-bold text-(--text-primary)">
                  {cat.blog_category_name}
                </h2>
                {cat.blog_category_description && (
                  <p className="text-sm text-(--text-muted) mt-1">
                    {cat.blog_category_description}
                  </p>
                )}
              </div>
            </div>

            {/* ── Category Info ── */}
            <h3 className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">
              Category Information
            </h3>
            <dl>
              <InfoRow label="Name" value={cat.blog_category_name} />
              <InfoRow label="Description" value={cat.blog_category_description} />
              <InfoRow label="Image" value={cat.blog_category_image} />
            </dl>

            {/* ── Metadata ── */}
            <h3 className="mb-2 mt-6 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">
              Metadata
            </h3>
            <dl>
              <InfoRow label="Created" value={formatDateShort(cat.created_at)} />
              <InfoRow label="Updated" value={formatDateShort(cat.updated_at)} />
              {cat.deleted_at && <InfoRow label="Deleted" value={formatDateShort(cat.deleted_at)} />}
            </dl>
          </div>
        </div>

        <DeleteConfirmModal
          open={pendingDelete}
          entityLabel={cat.blog_category_name}
          onConfirm={handleConfirmDelete}
          onCancel={() => setPendingDelete(false)}
          isDeleting={deleteBlogCategory.isPending}
        />
      </AppLayout>
    </>
  );
}
