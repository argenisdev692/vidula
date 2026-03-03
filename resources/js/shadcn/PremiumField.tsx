import * as React from 'react';
import { cn } from '@/lib/utils';

interface PremiumFieldProps extends React.InputHTMLAttributes<HTMLInputElement | HTMLTextAreaElement> {
  label: string;
  error?: string;
  isTextArea?: boolean;
}

export const PremiumField = ({
  label,
  error,
  isTextArea = false,
  className,
  id,
  required,
  ...props
}: PremiumFieldProps) => {
  const fieldId = id || label.toLowerCase().replace(/\s+/g, '-');
  
  const baseClasses = cn(
    "w-full rounded-xl px-4 py-3 text-sm outline-none transition-all duration-300",
    "bg-(--bg-card) border-(--border-default)",
    "focus:ring-2 focus:ring-(--accent-primary) focus:ring-offset-2 focus:ring-offset-(--bg-surface)",
    "placeholder:text-(--text-disabled) text-(--text-primary)",
    "hover:border-(--accent-primary) shadow-sm",
    error ? "border-(--accent-error) ring-(--accent-error)" : "border-(--border-default)",
    className
  );

  return (
    <div className="flex flex-col gap-2 group animate-in slide-in-from-top-2 duration-300">
      <label
        htmlFor={fieldId}
        className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary) group-focus-within:text-(--accent-primary) transition-colors"
      >
        {label} {required && <span className="text-(--accent-error) ml-1">*</span>}
      </label>
      
      {isTextArea ? (
        <textarea
          id={fieldId}
          className={cn(baseClasses, "min-h-[100px] resize-y")}
          {...(props as any)}
        />
      ) : (
        <input
          id={fieldId}
          className={baseClasses}
          {...props}
        />
      )}

      {error && (
        <span className="text-[11px] font-medium text-(--accent-error) animate-in fade-in slide-in-from-left-1">
          {error}
        </span>
      )}
    </div>
  );
};
