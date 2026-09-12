import { create } from 'zustand';
import { getCalendars } from 'expo-localization';

import { api, getToken, setToken } from '../api/client';
import type { Locale, Profile } from '../api/types';
import i18n from '../i18n';
import { queryClient } from '../lib/queryClient';
import { useLocaleStore } from './locale';

type AuthState = {
  profile: Profile | null;
  status: 'loading' | 'authenticated' | 'guest';
  restore: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  /** Închide sesiunea local, fără server. După ștergerea contului, tokenul nu mai există acolo. */
  endSession: () => Promise<void>;
  setProfile: (profile: Profile) => void;
};

function adoptProfile(profile: Profile) {
  // Limba contului are prioritate față de cea detectată din sistem:
  // utilizatorul a ales-o explicit, pe orice dispozitiv.
  if (profile.locale && profile.locale !== i18n.language) {
    useLocaleStore.getState().setLocale(profile.locale as Locale);
  }
}

/**
 * Ora aleasă pentru notificări e ora de pe ceasul utilizatorului, deci contul
 * trebuie să cunoască fusul telefonului. Fără asta, cine nu e în fusul
 * Chișinăului ar primi reminderele la altă oră decât cea aleasă.
 *
 * Întoarce profilul actualizat, sau null dacă nu era nimic de schimbat.
 */
async function syncTimezone(profile: Profile): Promise<Profile | null> {
  let timezone: string | null = null;

  try {
    timezone = getCalendars()[0]?.timeZone ?? null;
  } catch {
    return null;
  }

  if (!timezone || timezone === profile.timezone) {
    return null;
  }

  try {
    const { data } = await api.patch<{ data: Profile }>('/auth/me', { timezone });

    return data.data;
  } catch {
    // Nu blocăm sesiunea pentru asta: reîncercăm la următoarea pornire.
    return null;
  }
}

export const useAuthStore = create<AuthState>((set, get) => ({
  profile: null,
  status: 'loading',

  restore: async () => {
    if (!(await getToken())) {
      set({ status: 'guest', profile: null });
      return;
    }

    try {
      const { data } = await api.get<{ data: Profile }>('/auth/me');
      adoptProfile(data.data);
      set({ profile: data.data, status: 'authenticated' });
    syncTimezone(data.data).then((profile) => profile && set({ profile }));
    } catch {
      // Token expirat sau invalidat pe server.
      await setToken(null);
      set({ status: 'guest', profile: null });
    }
  },

  login: async (email, password) => {
    const { data } = await api.post<{ token: string; data: Profile }>('/auth/login', { email, password });
    await setToken(data.token);
    adoptProfile(data.data);
    set({ profile: data.data, status: 'authenticated' });
    syncTimezone(data.data).then((profile) => profile && set({ profile }));
  },

  register: async (name, email, password) => {
    const { data } = await api.post<{ token: string; data: Profile }>('/auth/register', {
      name,
      email,
      password,
      locale: i18n.language,
    });
    await setToken(data.token);
    set({ profile: data.data, status: 'authenticated' });
    syncTimezone(data.data).then((profile) => profile && set({ profile }));
  },

  logout: async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Chiar dacă serverul nu răspunde, ieșim local.
    }
    await get().endSession();
  },

  endSession: async () => {
    await setToken(null);
    set({ profile: null, status: 'guest' });

    // Datele din cache aparțin contului care a ieșit. Fără golire, următorul
    // cont de pe același telefon le-ar vedea până la prima reîncărcare —
    // regula 3 din CLAUDE.md.
    queryClient.clear();
  },

  setProfile: (profile) => set({ profile }),
}));
