import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import AppLayout from '@/pages/layouts/AppLayout';
import { usePost } from '@/modules/posts/hooks/usePost';
import { usePostMutations } from '@/modules/posts/hooks/usePostMutations';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';
import { formatDateShort } from '@/common/helpers/formatDate';
import type { PostDetail } from '@/types/posts';

function InfoRow({ label, value }: { label: string; value: string | null | undefined }): React.JSX.Element {
  return (
    <div className="grid grid-cols-3 gap-4 py-3 border-b border-(--border-subtle)">
      <dt className="text-sm font-medium text-(--text-muted)">{label}</dt>
      <dd className="col-span-2 text-sm font-medium text-(--text-primary)">{value || '—'}</dd>
    </div>
  );
}

interface PostShowPageProps {
  uuid: string;
  post?: PostDetail;
}

export default function PostShowPage({ uuid, post }: PostShowPageProps): React.JSX.Element {
  const { deletePost } = usePostMutations();
  const [pendingDelete, setPendingDelete] = React.useState(false);
  const { data: fetchedPost } = usePost(post ? undefined : uuid);
  const currentPost = post ?? fetchedPost;

  async function handleConfirmDelete(): Promise<void> {
    React.startTransition(async () => {
      try {
        await deletePost.mutateAsync(uuid);
        router.visit('/posts');
      } catch {
      }
    });
  }

  if (!currentPost) {
    return (
      <AppLayout>
        <Head title="Post" />
        <div className="flex items-center justify-center py-24">
          <span className="text-(--text-muted) text-sm animate-pulse">Loading post...</span>
        </div>
      </AppLayout>
    );
  }

  return (
    <>
      <Head title={`Post — ${currentPost.post_title}`} />
      <AppLayout>
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <Link
              href="/posts"
              className="flex h-9 w-9 items-center justify-center rounded-lg border border-(--border-default) bg-(--bg-card) text-(--text-muted) hover:bg-(--bg-hover) transition-all"
            >
              <ArrowLeft size={16} />
            </Link>
            <div>
              <h1 className="text-xl font-bold text-(--text-primary)">Post Details</h1>
              <p className="text-sm text-(--text-muted)">#{currentPost.uuid}</p>
            </div>
          </div>

          <div className="flex gap-3">
            <Link
              href={`/posts/${currentPost.uuid}/edit`}
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

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div className="card-modern shadow-lg p-6 space-y-6">
              <div>
                <h2 className="text-2xl font-bold text-(--text-primary)">{currentPost.post_title}</h2>
                <div className="mt-3 flex flex-wrap items-center gap-3 text-xs text-(--text-muted)">
                  <span className="inline-flex rounded-full border border-(--border-default) px-3 py-1 bg-(--bg-surface)">
                    {currentPost.post_status}
                  </span>
                  <span>{currentPost.category_name || 'Uncategorized'}</span>
                  <span>{formatDateShort(currentPost.published_at || currentPost.created_at)}</span>
                </div>
              </div>

              {currentPost.post_cover_image && (
                <div className="overflow-hidden rounded-2xl border border-(--border-default)">
                  <img
                    src={currentPost.post_cover_image}
                    alt={currentPost.post_title}
                    className="h-[320px] w-full object-cover"
                  />
                </div>
              )}

              {currentPost.post_excerpt && (
                <div className="rounded-2xl border border-(--border-default) bg-(--bg-surface) p-5">
                  <p className="text-sm leading-7 text-(--text-secondary)">{currentPost.post_excerpt}</p>
                </div>
              )}

              <article
                className="prose prose-sm max-w-none text-(--text-primary) prose-headings:text-(--text-primary) prose-p:text-(--text-primary) prose-a:text-(--accent-primary)"
                dangerouslySetInnerHTML={{ __html: currentPost.post_content }}
              />
            </div>
          </div>

          <div className="space-y-6">
            <div className="card-modern shadow-lg p-6">
              <h3 className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">
                Metadata
              </h3>
              <dl>
                <InfoRow label="Slug" value={currentPost.post_title_slug} />
                <InfoRow label="Meta Title" value={currentPost.meta_title} />
                <InfoRow label="Meta Description" value={currentPost.meta_description} />
                <InfoRow label="Meta Keywords" value={currentPost.meta_keywords} />
                <InfoRow label="Created" value={formatDateShort(currentPost.created_at)} />
                <InfoRow label="Updated" value={formatDateShort(currentPost.updated_at)} />
                <InfoRow label="Published" value={formatDateShort(currentPost.published_at)} />
                <InfoRow label="Scheduled" value={formatDateShort(currentPost.scheduled_at)} />
              </dl>
            </div>
          </div>
        </div>

        <DeleteConfirmModal
          open={pendingDelete}
          entityLabel={currentPost.post_title}
          onConfirm={handleConfirmDelete}
          onCancel={() => setPendingDelete(false)}
          isDeleting={deletePost.isPending}
        />
      </AppLayout>
    </>
  );
}
