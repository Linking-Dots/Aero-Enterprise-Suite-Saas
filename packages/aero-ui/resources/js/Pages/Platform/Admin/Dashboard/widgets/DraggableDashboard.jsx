/**
 * DraggableDashboard
 * ------------------
 * Wraps children (widgets) in @dnd-kit sortable context.
 * Order is persisted to localStorage under STORAGE_KEY.
 * Falls back gracefully if @dnd-kit is unavailable.
 */
import { useState, useCallback } from 'react';

let dndAvailable = false;
let DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors;
let SortableContext, rectSortingStrategy, useSortable, arrayMove;

try {
  // Dynamic import check — @dnd-kit is in package.json
  const core   = require('@dnd-kit/core');
  const sortable = require('@dnd-kit/sortable');

  DndContext       = core.DndContext;
  closestCenter    = core.closestCenter;
  KeyboardSensor   = core.KeyboardSensor;
  PointerSensor    = core.PointerSensor;
  useSensor        = core.useSensor;
  useSensors       = core.useSensors;

  SortableContext  = sortable.SortableContext;
  rectSortingStrategy = sortable.rectSortingStrategy;
  useSortable      = sortable.useSortable;
  arrayMove        = sortable.arrayMove;

  dndAvailable = true;
} catch (_) {
  // Fail silently — widgets still render, just not draggable
}

const STORAGE_KEY = 'aeos_dashboard_widget_order';

function loadOrder(defaultIds) {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (!stored) return defaultIds;
    const parsed = JSON.parse(stored);
    if (!Array.isArray(parsed)) return defaultIds;
    // Merge: keep stored order but include any new ids not yet stored
    const known = new Set(parsed);
    const extra = defaultIds.filter((id) => !known.has(id));
    return [...parsed.filter((id) => defaultIds.includes(id)), ...extra];
  } catch {
    return defaultIds;
  }
}

function saveOrder(ids) {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(ids)); } catch (_) {}
}

// ─── Sortable item wrapper ───────────────────────────────────────────────────
function SortableItem({ id, children }) {
  if (!dndAvailable) return <>{children}</>;
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id });

  const style = {
    transform: transform
      ? `translate3d(${transform.x}px, ${transform.y}px, 0)`
      : undefined,
    transition,
    zIndex:  isDragging ? 100 : undefined,
    opacity: isDragging ? 0.6 : 1,
    cursor:  isDragging ? 'grabbing' : 'grab',
    outline: 'none',
  };

  return (
    <div ref={setNodeRef} style={style} {...attributes} {...listeners}>
      {children}
    </div>
  );
}

// ─── Main export ─────────────────────────────────────────────────────────────
/**
 * @param {{ items: { id: string; node: React.ReactNode }[] }} props
 */
export default function DraggableDashboard({ items = [], style, className }) {
  const defaultIds = items.map((i) => i.id);
  const [order, setOrder] = useState(() => loadOrder(defaultIds));

  const sensors = dndAvailable
    ? useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor)
      )
    : null;

  const handleDragEnd = useCallback(
    ({ active, over }) => {
      if (!over || active.id === over.id) return;
      setOrder((prev) => {
        const oldIdx = prev.indexOf(active.id);
        const newIdx = prev.indexOf(over.id);
        if (oldIdx === -1 || newIdx === -1) return prev;
        const next = arrayMove(prev, oldIdx, newIdx);
        saveOrder(next);
        return next;
      });
    },
    []
  );

  const sorted = order
    .map((id) => items.find((i) => i.id === id))
    .filter(Boolean);

  if (!dndAvailable) {
    return (
      <div style={style} className={className}>
        {items.map((item) => (
          <div key={item.id}>{item.node}</div>
        ))}
      </div>
    );
  }

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCenter}
      onDragEnd={handleDragEnd}
    >
      <SortableContext items={order} strategy={rectSortingStrategy}>
        <div style={style} className={className}>
          {sorted.map((item) => (
            <SortableItem key={item.id} id={item.id}>
              {item.node}
            </SortableItem>
          ))}
        </div>
      </SortableContext>
    </DndContext>
  );
}
