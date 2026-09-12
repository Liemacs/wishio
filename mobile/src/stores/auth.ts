import { create } from 'zustand';

import { api, getToken, setToken } from '../api/client';
import type { Locale, Profile } from '../api/types';
import i18n from '../i18n';
import { useLocaleStore } from './locale';

type AuthState = {
  profile: Profile | null;
  status: 'loading' | 'authenticated' | 'guest';
  restore: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
};

function adoptProfile(profile: Profile) {
  // Limba contului are prioritate față de cea detectată din sistem:
  // utilizatorul a ales-o explicit, pe orice dispozitiv.
  if (profile.locale && profile.locale !== i18n.language) {
    useLocaleStore.getState().setLocale(profile.locale as Locale);
  }
}

export const useAuthStore = create<AuthState>((set) => ({
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
  },

  logout: async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Chiar dacă serverul nu răspunde, ieșim local.
    }
    await setToken(null);
    set({ profile: null, status: 'guest' });
  },
}));
