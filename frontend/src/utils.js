export const inr = n => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(n || 0);
export const dmy = d => {
  if (!d) return '—';
  const s = String(d);
  const x = /^\d{4}-\d{2}-\d{2}$/.test(s) ? new Date(s + 'T00:00:00') : new Date(s); // date-only values must not shift by timezone
  return x.toLocaleDateString('en-GB').replaceAll('/', '-');
};
