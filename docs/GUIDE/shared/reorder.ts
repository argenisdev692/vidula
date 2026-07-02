import { signal, type Signal } from '@angular/core';

/** Anything reorderable carries a stable `id`. */
export interface Reorderable {
  id: string;
}

/**
 * Controller returned by {@link createReorder}. Wire the handlers straight to
 * the template (drag handle + up/down buttons) and expose `dragId` for the
 * `.row-dragging` class.
 */
export interface ReorderController {
  /** Id of the row currently being dragged (null when idle). */
  readonly dragId: Signal<string | null>;
  onDragStart(id: string): void;
  onDragOver(event: DragEvent, id: string): void;
  onDragEnd(): void;
  onDrop(event: DragEvent): void;
  /** Keyboard / single-pointer move (WCAG 2.2 SC 2.5.7). `direction` is -1 (up/left) or 1 (down/right). */
  move(id: string, direction: -1 | 1): void;
}

/**
 * Signal-based drag-and-keyboard reorder, shared by every list/grid that needs it.
 *
 * @param read    returns the current ordered list (e.g. `() => this.items()`)
 * @param write   commits the new order to the local signal (e.g. `next => this.items.set(next)`)
 * @param persist sends the new order to the backend (owns its own optimistic-revert + toast)
 */
export function createReorder<T extends Reorderable>(
  read: () => readonly T[],
  write: (next: T[]) => void,
  persist: (ordered: T[]) => void,
): ReorderController {
  const dragId = signal<string | null>(null);
  let dragOverId: string | null = null;

  const apply = (fromId: string, toId: string): void => {
    const list = [...read()];
    const from = list.findIndex((x) => x.id === fromId);
    const to = list.findIndex((x) => x.id === toId);
    if (from < 0 || to < 0 || from === to) return;
    const [moved] = list.splice(from, 1);
    list.splice(to, 0, moved);
    write(list);
    persist(list);
  };

  return {
    dragId,
    onDragStart: (id) => dragId.set(id),
    onDragOver: (event, id) => {
      event.preventDefault();
      dragOverId = id;
    },
    onDragEnd: () => {
      dragId.set(null);
      dragOverId = null;
    },
    onDrop: (event) => {
      event.preventDefault();
      const fromId = dragId();
      const toId = dragOverId;
      dragId.set(null);
      dragOverId = null;
      if (fromId && toId) apply(fromId, toId);
    },
    move: (id, direction) => {
      const list = read();
      const from = list.findIndex((x) => x.id === id);
      const to = from + direction;
      if (from < 0 || to < 0 || to >= list.length) return;
      apply(id, list[to].id);
    },
  };
}
