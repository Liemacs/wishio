import { useMutation } from '@tanstack/react-query';
import { File, Paths } from 'expo-file-system';
import * as Sharing from 'expo-sharing';

import { api } from '../../api/client';
import type { Locale, Profile } from '../../api/types';
import i18n from '../../i18n';
import { useAuthStore } from '../../stores/auth';
import { useLocaleStore } from '../../stores/locale';

const EXPORT_PREFIX = 'wishio-export-';

/**
 * Șterge copiile de export rămase în cache.
 *
 * Fișierul conține și notele despre oameni: nu-l ținem pe telefon mai mult
 * decât e nevoie, iar după ștergerea contului nu rămâne deloc.
 */
export function clearExportFiles() {
  try {
    for (const entry of Paths.cache.list()) {
      if (entry instanceof File && entry.name.startsWith(EXPORT_PREFIX)) {
        entry.delete();
      }
    }
  } catch {
    // Curățenia nu are voie să blocheze exportul sau ștergerea contului.
  }
}

/** Data locală, nu UTC: un export făcut la 01:00 la Chișinău poartă ziua de azi. */
function today(): string {
  const now = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');

  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

export type ExportResult = 'shared' | 'unavailable';

/**
 * Exportul de date — dreptul la portabilitate (docs/06 § 1).
 *
 * Fișierul ajunge în foaia de partajare a sistemului, iar utilizatorul alege
 * unde îl pune: Fișiere, AirDrop, un email către el însuși. Aplicația nu-l
 * trimite nicăieri singură (regula 10).
 */
export function useExportData() {
  return useMutation({
    mutationFn: async (): Promise<ExportResult> => {
      // O stare așteptată, nu o eroare: o întoarcem ca valoare.
      if (!(await Sharing.isAvailableAsync())) {
        return 'unavailable';
      }

      const { data } = await api.get('/account/export');

      // Rămâne o singură copie, cea de acum. Nu o ștergem imediat după
      // partajare: pe Android, aplicația care o primește o citește mai târziu.
      clearExportFiles();

      const file = new File(Paths.cache, `${EXPORT_PREFIX}${today()}.json`);
      file.create({ overwrite: true });
      file.write(JSON.stringify(data, null, 2));

      await Sharing.shareAsync(file.uri, {
        mimeType: 'application/json',
        UTI: 'public.json',
        dialogTitle: i18n.t('account.export'),
      });

      return 'shared';
    },
  });
}

/**
 * Ștergerea contului, din aplicație — cerință Apple (docs/06 § 2).
 *
 * Serverul verifică emailul scris de mână și parola. Sesiunea o închide
 * ecranul, după ce i-a confirmat utilizatorului: tokenul nu mai există pe
 * server, deci nu mai e nimic de delogat.
 */
export function useDeleteAccount() {
  return useMutation({
    mutationFn: async (input: { confirm_email: string; password: string }) => {
      await api.delete('/account', { data: input });
    },
    onSuccess: () => clearExportFiles(),
  });
}

/**
 * Schimbarea limbii: se aplică imediat și se salvează în cont.
 *
 * Fără salvare, la repornire `restore()` readuce limba din cont, iar
 * notificările și emailurile continuă să plece în limba veche.
 */
export function useChangeLocale() {
  const setLocale = useLocaleStore((s) => s.setLocale);
  const setProfile = useAuthStore((s) => s.setProfile);

  return useMutation({
    mutationFn: async (locale: Locale) =>
      (await api.patch<{ data: Profile }>('/auth/me', { locale })).data.data,
    // Interfața nu așteaptă serverul.
    onMutate: (locale) => setLocale(locale),
    onSuccess: (profile) => setProfile(profile),
  });
}
