import * as React from 'react';
import { cn } from '@/lib/utils';

type PremiumFieldBaseProps = {
  label: string;
  error?: string;
};

type PremiumInputProps = PremiumFieldBaseProps & {
  isTextArea?: false;
} & React.InputHTMLAttributes<HTMLInputElement>;

type PremiumTextAreaProps = PremiumFieldBaseProps & {
  isTextArea: true;
} & React.TextareaHTMLAttributes<HTMLTextAreaElement>;

type PremiumFieldProps = PremiumInputProps | PremiumTextAreaProps;

export function PremiumField(props: PremiumFieldProps): React.JSX.Element {
  const {
    label,
    error,
    className,
    id,
    required,
  } = props;

  const fieldId = id ?? label.toLowerCase().replace(/\s+/g, '-');

  const baseClasses = cn(
    'w-full rounded-xl px-4 py-3 text-sm outline-none transition-all duration-300',
    'bg-(--bg-card) border-(--border-default)',
    'focus-visible:ring-2 focus-visible:ring-(--accent-primary) focus-visible:ring-offset-2 focus-visible:ring-offset-(--bg-surface)',
    'placeholder:text-(--text-muted) text-(--text-primary)',
    'hover:border-(--accent-primary) shadow-sm',
    error ? 'border-(--accent-error) ring-(--accent-error)' : 'border-(--border-default)',
    className,
  );

  return (
    <div className="flex flex-col gap-2 group animate-in slide-in-from-top-2 duration-300">
      <label
        htmlFor={fieldId}
        className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary) group-focus-within:text-(--accent-primary) transition-colors"
      >
        {label} {required && <span className="ml-1 text-(--accent-error)">*</span>}
      </label>

      {props.isTextArea ? (() => {
        const {
          label: _label,
          error: _error,
          isTextArea: _isTextArea,
          className: _className,
          id: _id,
          required: _required,
          ...textAreaProps
        } = props;

        return (
          <textarea
            id={fieldId}
            required={required}
            className={cn(baseClasses, 'min-h-[100px] resize-y')}
            {...textAreaProps}
          />
        );
      })() : (() => {
        const {
          label: _label,
          error: _error,
          isTextArea: _isTextArea,
          className: _className,
          id: _id,
          required: _required,
          ...inputProps
        } = props;

        return (
          <input
            id={fieldId}
            required={required}
            className={baseClasses}
            {...inputProps}
          />
        );
      })()}

      {error && (
        <span className="text-[11px] font-medium text-(--accent-error) animate-in fade-in slide-in-from-left-1">
          {error}
        </span>
      )}
    </div>
  );
}
