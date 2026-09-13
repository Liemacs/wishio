import i18n from '../../i18n';

/**
 * Eticheta relației, sau nimic pentru un cod pe care aplicația nu-l cunoaște.
 *
 * Serverul acceptă orice text scurt ca relație. Fără verificare, un cod
 * necunoscut ar apărea pe ecran ca cheie brută („relationships.naș”).
 */
export function relationshipLabel(code: string | null | undefined): string | null {
  if (!code) return null;

  const key = `relationships.${code}`;

  return i18n.exists(key) ? i18n.t(key) : null;
}
