import { describe, it, expect, vi } from 'vitest';
import { createReorder } from './reorder';

interface Item {
  id: string;
}

function setup(initial: string[]) {
  let list: Item[] = initial.map((id) => ({ id }));
  const persist = vi.fn<(ordered: Item[]) => void>();
  const ctrl = createReorder<Item>(
    () => list,
    (next) => {
      list = next;
    },
    persist,
  );
  const fakeEvent = () =>
    ({ preventDefault: vi.fn() }) as unknown as DragEvent;
  return { ctrl, persist, fakeEvent, ids: () => list.map((i) => i.id) };
}

describe('createReorder.move', () => {
  it('moves an item down and persists the new order', () => {
    const { ctrl, persist, ids } = setup(['a', 'b', 'c']);
    ctrl.move('a', 1);
    expect(ids()).toEqual(['b', 'a', 'c']);
    expect(persist).toHaveBeenCalledWith([{ id: 'b' }, { id: 'a' }, { id: 'c' }]);
  });

  it('moves an item up', () => {
    const { ctrl, ids } = setup(['a', 'b', 'c']);
    ctrl.move('c', -1);
    expect(ids()).toEqual(['a', 'c', 'b']);
  });

  it('is a no-op at the top boundary', () => {
    const { ctrl, persist, ids } = setup(['a', 'b', 'c']);
    ctrl.move('a', -1);
    expect(ids()).toEqual(['a', 'b', 'c']);
    expect(persist).not.toHaveBeenCalled();
  });

  it('is a no-op at the bottom boundary', () => {
    const { ctrl, persist, ids } = setup(['a', 'b', 'c']);
    ctrl.move('c', 1);
    expect(ids()).toEqual(['a', 'b', 'c']);
    expect(persist).not.toHaveBeenCalled();
  });
});

describe('createReorder drag/drop', () => {
  it('drops the dragged row onto the hovered row', () => {
    const { ctrl, persist, fakeEvent, ids } = setup(['a', 'b', 'c']);
    ctrl.onDragStart('a');
    expect(ctrl.dragId()).toBe('a');
    ctrl.onDragOver(fakeEvent(), 'c');
    ctrl.onDrop(fakeEvent());
    expect(ids()).toEqual(['b', 'c', 'a']);
    expect(ctrl.dragId()).toBeNull();
    expect(persist).toHaveBeenCalledTimes(1);
  });

  it('does nothing when dropped on itself', () => {
    const { ctrl, persist, fakeEvent, ids } = setup(['a', 'b', 'c']);
    ctrl.onDragStart('b');
    ctrl.onDragOver(fakeEvent(), 'b');
    ctrl.onDrop(fakeEvent());
    expect(ids()).toEqual(['a', 'b', 'c']);
    expect(persist).not.toHaveBeenCalled();
  });

  it('clears drag state on drag end', () => {
    const { ctrl } = setup(['a', 'b']);
    ctrl.onDragStart('a');
    ctrl.onDragEnd();
    expect(ctrl.dragId()).toBeNull();
  });
});
