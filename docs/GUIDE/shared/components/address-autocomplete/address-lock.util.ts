/**
 * Builds the per-field lock map used by address forms: a field is **locked**
 * (readonly) when it currently holds a truthy value (auto-filled by the address
 * autocomplete) and **unlocked** when empty, so the user may fill the gap
 * manually. Recompute this only on address selection / entity load — never on
 * keystrokes — so a field never re-locks while being typed.
 */
export function computeLockMap<K extends string>(
  values: Record<string, unknown>,
  fields: readonly K[],
): Record<K, boolean> {
  return Object.fromEntries(fields.map((f) => [f, !!values[f]])) as Record<K, boolean>;
}
