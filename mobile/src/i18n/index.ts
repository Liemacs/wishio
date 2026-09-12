import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import { getLocales } from 'expo-localization';

import ro from './locales/ro.json';
import ru from './locales/ru.json';
import en from './locales/en.json';

/**
 * RO este limba de baza si sursa de adevar pentru traduceri.
 * RU si EN au paritate functionala, cu fallback la RO.
 * Vezi docs/05-arhitectura.md § 6 si CLAUDE.md regula 1.
 */
export const SUPPORTED_LOCALES = ['ro', 'ru', 'en'] as const;
export type Locale = (typeof SUPPORTED_LOCALES)[number];

export const DEFAULT_LOCALE: Locale = 'ro';

export function detectLocale(): Locale {
  for (const locale of getLocales()) {
    const code = locale.languageCode?.toLowerCase();
    if (code && (SUPPORTED_LOCALES as readonly string[]).includes(code)) {
      return code as Locale;
    }
  }
  return DEFAULT_LOCALE;
}

i18n.use(initReactI18next).init({
  resources: {
    ro: { translation: ro },
    ru: { translation: ru },
    en: { translation: en },
  },
  lng: detectLocale(),
  fallbackLng: DEFAULT_LOCALE,
  interpolation: { escapeValue: false },
  returnNull: false,
});

export default i18n;
