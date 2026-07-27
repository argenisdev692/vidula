import type { StudioMode, StudioRunStatus, StudioRunStep } from '../types';

export type StudioStatusTone = 'success' | 'danger' | 'muted' | 'primary';

const STATUS_LABELS: Record<StudioRunStatus, string> = {
  pending: 'Pending',
  running: 'Running',
  completed: 'Completed',
  failed: 'Failed',
};

const STATUS_TONES: Record<StudioRunStatus, StudioStatusTone> = {
  pending: 'muted',
  running: 'primary',
  completed: 'success',
  failed: 'danger',
};

const STEP_LABELS: Record<StudioRunStep, string> = {
  queued: 'Queued',
  enriching: 'Enriching GitHub',
  refining: 'Refining CV',
  searching: 'Searching jobs',
  scoring: 'Scoring matches',
  drafting: 'Drafting outreach',
  completed: 'Completed',
  failed: 'Failed',
};

const STEP_LABELS_COMPACT: Record<StudioRunStep, string> = {
  queued: 'Queued',
  enriching: 'Enriching',
  refining: 'Refining',
  searching: 'Searching',
  scoring: 'Scoring',
  drafting: 'Drafting',
  completed: 'Completed',
  failed: 'Failed',
};

export function modeLabel(mode: StudioMode): string {
  return mode === 'career' ? 'Career' : 'Other niche';
}

export function modeTone(mode: StudioMode): 'primary' | 'muted' {
  return mode === 'career' ? 'primary' : 'muted';
}

export function statusLabel(status: StudioRunStatus): string {
  return STATUS_LABELS[status] ?? status;
}

export function statusTone(status: StudioRunStatus): StudioStatusTone {
  return STATUS_TONES[status] ?? 'muted';
}

export function stepLabel(step: StudioRunStep, compact = false): string {
  const map = compact ? STEP_LABELS_COMPACT : STEP_LABELS;

  return map[step] ?? step;
}
