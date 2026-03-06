import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PostForm } from '@/modules/posts/components/PostForm';
import { usePost } from '@/modules/posts/hooks/usePost';
import { usePostMutations } from '@/modules/posts/hooks/usePostMutations';
import type { UpdatePostPayload } from '@/types/posts';

interface PostEditPageProps {
  uuid: string;
}

export default function PostEditPage({ uuid }: PostEditPageProps): React.JSX.Element {
  const { data: post, isPending: isLoading } = usePost(uuid);
  const { updatePost } = usePostMutations();
  const [initialized, setInitialized] = React.useState(false);
  const [form, setForm] = React.useState<UpdatePostPayload>({
    post_title: '',
    post_title_slug: '',
    post_content: '<p></p>',
    post_excerpt: '',
    post_cover_image: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    category_uuid: '',
    post_status: 'draft',
    published_at: null,
    scheduled_at: null,
  });
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    if (!post || initialized) return;
    setForm({
      post_title: post.post_title ?? '',
      post_title_slug: post.post_title_slug ?? '',
      post_content: post.post_content ?? '<p></p>',
      post_excerpt: post.post_excerpt ?? '',
      post_cover_image: post.post_cover_image ?? '',
      meta_title: post.meta_title ?? '',
      meta_description: post.meta_description ?? '',
      meta_keywords: post.meta_keywords ?? '',
      category_uuid: post.category_uuid ?? '',
      post_status: post.post_status ?? 'draft',
      published_at: post.published_at ?? null,
      scheduled_at: post.scheduled_at ?? null,
    });
    setInitialized(true);
  }, [post, initialized]);

  function handleChange(field: string, value: string | null): void {
    setForm((prev) => ({ ...prev, [field]: value ?? '' }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
  }

  function normalizePayload(payload: UpdatePostPayload): UpdatePostPayload {
    return {
      ...payload,
      category_uuid: payload.category_uuid || undefined,
      published_at: payload.published_at || null,
      scheduled_at: payload.scheduled_at || null,
    };
  }

  async function handleSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();

    updatePost.mutate(
      { uuid, payload: normalizePayload(form) },
      {
        onSuccess: () => {
          router.visit(`/posts/${uuid}`);
        },
        onError: (err: Error) => {
          const axiosErr = err as { response?: { data?: { errors?: Record<string, string[]> } } };
          if (axiosErr.response?.data?.errors) {
            const serverErrors: Record<string, string> = {};
            for (const [key, msgs] of Object.entries(axiosErr.response.data.errors)) {
              serverErrors[key] = msgs[0] ?? '';
            }
            setErrors(serverErrors);
          }
        },
      },
    );
  }

  if (isLoading || !post) {
    return (
      <AppLayout>
        <Head title="Edit Post" />
        <div className="flex items-center justify-center py-24">
          <span className="text-(--text-muted) text-sm animate-pulse">Loading post...</span>
        </div>
      </AppLayout>
    );
  }

  return (
    <>
      <Head title={`Edit — ${post.post_title}`} />
      <AppLayout>
        <PostForm
          title="Edit Post"
          subtitle={post.post_title}
          backHref={`/posts/${uuid}`}
          submitLabel="Save Changes"
          isSubmitting={updatePost.isPending}
          values={form}
          errors={errors}
          onChange={handleChange}
          onSubmit={handleSubmit}
        />
      </AppLayout>
    </>
  );
}
