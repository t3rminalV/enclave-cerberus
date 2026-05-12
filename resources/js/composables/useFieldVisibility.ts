type VisibleWhen = { field_id?: number; equals?: unknown } | null | undefined;

export function isFieldVisible(
  visibleWhen: VisibleWhen,
  responsesByFieldId: Record<number | string, unknown>,
): boolean {
  if (!visibleWhen || !visibleWhen.field_id) return true;
  const actual = responsesByFieldId[visibleWhen.field_id];
  const expected = visibleWhen.equals;
  if (Array.isArray(expected)) return expected.some((e) => e === actual || (Array.isArray(actual) && actual.includes(e)));
  if (Array.isArray(actual)) return actual.includes(expected);
  return String(actual ?? '') === String(expected ?? '');
}
