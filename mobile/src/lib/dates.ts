/**
 * O zi de naștere în limba interfeței: „10 ianuarie” sau „10 ianuarie 1990”.
 * Data vine de la server ca YYYY-MM-DD, fără oră.
 */
export function formatBirthday(isoDate: string, withYear: boolean, locale: string): string {
  const [year, month, day] = isoDate.split('-').map(Number);

  // Mijlocul zilei, formatat în UTC: nicio zonă orară nu mută data în ziua vecină.
  const date = new Date(Date.UTC(year, month - 1, day, 12));

  return new Intl.DateTimeFormat(locale, {
    day: 'numeric',
    month: 'long',
    ...(withYear ? { year: 'numeric' as const } : {}),
    timeZone: 'UTC',
  }).format(date);
}
