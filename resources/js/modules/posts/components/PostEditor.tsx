import * as React from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Image from '@tiptap/extension-image';
import CharacterCount from '@tiptap/extension-character-count';
import {
  Bold,
  Italic,
  Underline as UnderlineIcon,
  Heading1,
  Heading2,
  Heading3,
  List,
  ListOrdered,
  Quote,
  Undo2,
  Redo2,
  Link as LinkIcon,
  Image as ImageIcon,
  AlignLeft,
  AlignCenter,
  AlignRight,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface PostEditorProps {
  value: string;
  onChange: (value: string) => void;
  error?: string;
  placeholder?: string;
}

interface ToolbarButtonProps {
  onClick: () => void;
  active?: boolean;
  title: string;
  children: React.ReactNode;
}

function ToolbarButton({ onClick, active = false, title, children }: ToolbarButtonProps): React.JSX.Element {
  return (
    <button
      type="button"
      onClick={onClick}
      title={title}
      className={cn(
        'inline-flex h-9 w-9 items-center justify-center rounded-lg border transition-all',
        active
          ? 'border-(--accent-primary) bg-(--accent-primary) text-white shadow-md'
          : 'border-(--border-default) bg-(--bg-card) text-(--text-secondary) hover:bg-(--bg-hover)',
      )}
    >
      {children}
    </button>
  );
}

export function PostEditor({
  value,
  onChange,
  error,
  placeholder = 'Write a rich and engaging post content...',
}: PostEditorProps): React.JSX.Element {
  const editor = useEditor({
    immediatelyRender: false,
    extensions: [
      StarterKit.configure({
        heading: {
          levels: [1, 2, 3],
        },
      }),
      Underline,
      Link.configure({
        openOnClick: false,
        autolink: true,
        protocols: ['http', 'https', 'mailto'],
      }),
      Placeholder.configure({
        placeholder,
      }),
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Image,
      CharacterCount.configure({
        limit: 20000,
      }),
    ],
    content: value,
    editorProps: {
      attributes: {
        class:
          'min-h-[320px] px-5 py-4 prose prose-sm max-w-none focus:outline-none text-(--text-primary) prose-headings:text-(--text-primary) prose-p:text-(--text-primary) prose-strong:text-(--text-primary) prose-a:text-(--accent-primary) prose-blockquote:text-(--text-secondary)',
      },
    },
    onUpdate: ({ editor: currentEditor }) => {
      onChange(currentEditor.getHTML());
    },
  });

  React.useEffect(() => {
    if (!editor) return;
    if (value === editor.getHTML()) return;
    withChain((chain) => chain.setContent(value || '<p></p>'));
  }, [editor, value]);

  function withChain(action: (chain: any) => any): void {
    if (!editor) return;
    action(editor.chain().focus()).run();
  }

  function setLink(): void {
    if (!editor) return;
    const previousUrl = editor.getAttributes('link').href as string | undefined;
    const url = window.prompt('Enter URL', previousUrl || 'https://');

    if (url === null) return;

    if (url.trim() === '') {
      withChain((chain) => chain.extendMarkRange('link').unsetLink());
      return;
    }

    withChain((chain) => chain.extendMarkRange('link').setLink({ href: url }));
  }

  function setImage(): void {
    if (!editor) return;
    const url = window.prompt('Enter image URL', 'https://');

    if (!url || url.trim() === '') return;

    withChain((chain) => chain.setImage({ src: url }));
  }

  const characterCountStorage = editor?.storage as {
    characterCount?: {
      characters: () => number;
      words: () => number;
    };
  } | undefined;
  const characters = characterCountStorage?.characterCount?.characters() ?? 0;
  const words = characterCountStorage?.characterCount?.words() ?? 0;

  return (
    <div className="flex flex-col gap-3">
      <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-(--border-default) bg-(--bg-surface) p-3 shadow-sm">
        <ToolbarButton
          title="Bold"
          active={editor?.isActive('bold')}
          onClick={() => withChain((chain) => chain.toggleBold())}
        >
          <Bold size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Italic"
          active={editor?.isActive('italic')}
          onClick={() => withChain((chain) => chain.toggleItalic())}
        >
          <Italic size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Underline"
          active={editor?.isActive('underline')}
          onClick={() => withChain((chain) => chain.toggleUnderline())}
        >
          <UnderlineIcon size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Heading 1"
          active={editor?.isActive('heading', { level: 1 })}
          onClick={() => withChain((chain) => chain.toggleHeading({ level: 1 }))}
        >
          <Heading1 size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Heading 2"
          active={editor?.isActive('heading', { level: 2 })}
          onClick={() => withChain((chain) => chain.toggleHeading({ level: 2 }))}
        >
          <Heading2 size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Heading 3"
          active={editor?.isActive('heading', { level: 3 })}
          onClick={() => withChain((chain) => chain.toggleHeading({ level: 3 }))}
        >
          <Heading3 size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Bullet list"
          active={editor?.isActive('bulletList')}
          onClick={() => withChain((chain) => chain.toggleBulletList())}
        >
          <List size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Ordered list"
          active={editor?.isActive('orderedList')}
          onClick={() => withChain((chain) => chain.toggleOrderedList())}
        >
          <ListOrdered size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Blockquote"
          active={editor?.isActive('blockquote')}
          onClick={() => withChain((chain) => chain.toggleBlockquote())}
        >
          <Quote size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Align left"
          active={editor?.isActive({ textAlign: 'left' })}
          onClick={() => withChain((chain) => chain.setTextAlign('left'))}
        >
          <AlignLeft size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Align center"
          active={editor?.isActive({ textAlign: 'center' })}
          onClick={() => withChain((chain) => chain.setTextAlign('center'))}
        >
          <AlignCenter size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Align right"
          active={editor?.isActive({ textAlign: 'right' })}
          onClick={() => withChain((chain) => chain.setTextAlign('right'))}
        >
          <AlignRight size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Insert link"
          active={editor?.isActive('link')}
          onClick={setLink}
        >
          <LinkIcon size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Insert image"
          onClick={setImage}
        >
          <ImageIcon size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Undo"
          onClick={() => withChain((chain) => chain.undo())}
        >
          <Undo2 size={16} />
        </ToolbarButton>
        <ToolbarButton
          title="Redo"
          onClick={() => withChain((chain) => chain.redo())}
        >
          <Redo2 size={16} />
        </ToolbarButton>
      </div>

      <div
        className={cn(
          'overflow-hidden rounded-2xl border bg-(--bg-card) shadow-xl transition-all',
          error ? 'border-(--accent-error)' : 'border-(--border-default)',
        )}
      >
        <EditorContent editor={editor} />
      </div>

      <div className="flex items-center justify-between text-xs text-(--text-muted)">
        <span>{words} words</span>
        <span>{characters} / 20000 characters</span>
      </div>

      {error && <p className="text-xs text-(--accent-error)">{error}</p>}
    </div>
  );
}
