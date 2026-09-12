import { create } from 'zustand';
import i18n, { detectLocale, type Locale } from '../i18n';

type LocaleState = {
  locale: Locale;
  setLocale: (locale: Locale) => void;
};

export const useLocaleStore = create<LocaleState>((set) => ({
  locale: detectLocale(),
  setLocale: (locale) => {
    i18n.changeLanguage(locale);
    set({ locale });
  },
}));
