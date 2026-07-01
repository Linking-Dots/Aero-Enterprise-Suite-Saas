/** Format a monetary amount with its currency. Shared across billing panels. */
export function money(amount, currency = 'USD') {
  if (amount == null || amount === '') return '—';
  try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(Number(amount) || 0);
  } catch {
    return `${currency || 'USD'} ${Number(amount) || 0}`;
  }
}
