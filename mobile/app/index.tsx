import { View, Text, Pressable, ScrollView } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';

import { SUPPORTED_LOCALES, type Locale } from '../src/i18n';
import { useLocaleStore } from '../src/stores/locale';

const LOCALE_NAMES: Record<Locale, string> = {
  ro: 'Română',
  ru: 'Русский',
  en: 'English',
};

/**
 * Ecran de verificare a fundatiei (PLAN.md S1.5 / S1.6).
 * Confirma ca i18n, pluralul si NativeWind functioneaza in toate trei limbile.
 * Se inlocuieste cu Home real la S5.6.
 */
export default function Index() {
  const { t } = useTranslation();
  const insets = useSafeAreaInsets();
  const { locale, setLocale } = useLocaleStore();

  return (
    <ScrollView
      className="flex-1 bg-surface-50"
      contentContainerStyle={{ paddingTop: insets.top + 24, paddingBottom: 48 }}
    >
      <View className="px-6">
        <Text className="text-4xl font-bold text-surface-900">
          {t('common.appName')}
        </Text>
        <Text className="mt-2 text-base text-surface-500">
          {t('common.tagline')}
        </Text>

        <Text className="mt-10 text-xs font-semibold uppercase tracking-wide text-surface-400">
          {t('common.language')}
        </Text>
        <View className="mt-3 flex-row gap-2">
          {SUPPORTED_LOCALES.map((code) => {
            const active = code === locale;
            return (
              <Pressable
                key={code}
                onPress={() => setLocale(code)}
                className={`rounded-button px-4 py-3 ${
                  active ? 'bg-primary-600' : 'bg-surface-200'
                }`}
              >
                <Text
                  className={`text-sm font-semibold ${
                    active ? 'text-white' : 'text-surface-700'
                  }`}
                >
                  {LOCALE_NAMES[code]}
                </Text>
              </Pressable>
            );
          })}
        </View>

        {/* Verificarea formelor de plural. Rusa are 3 forme (1 / 2-4 / 5+),
            romana are 3 (1 / 2-19 / 20+), engleza are 2. */}
        <Text className="mt-10 text-xs font-semibold uppercase tracking-wide text-surface-400">
          Plural
        </Text>
        <View className="mt-3 rounded-card bg-white p-4">
          {[1, 2, 5, 21, 101].map((count) => (
            <Text key={count} className="py-1 text-base text-surface-800">
              {t('reminder.daysBefore', { count })}
            </Text>
          ))}
        </View>

        <Text className="mt-10 text-xs font-semibold uppercase tracking-wide text-surface-400">
          {t('home.upcoming')}
        </Text>
        <View className="mt-3 rounded-card bg-white p-5">
          <View className="flex-row items-center gap-2">
            <View className="h-2 w-2 rounded-full bg-occasion-birthday" />
            <Text className="text-sm text-surface-500">
              {t('occasions.birthday')}
            </Text>
          </View>
          <Text className="mt-1 text-2xl font-semibold text-surface-900">Alex</Text>
          <Text className="mt-1 text-base text-surface-500">
            {t('reminder.daysBefore', { count: 5 })}
          </Text>
          <Pressable className="mt-4 rounded-button bg-primary-600 px-5 py-3">
            <Text className="text-center text-base font-semibold text-white">
              {t('reminder.findGift')}
            </Text>
          </Pressable>
        </View>
      </View>
    </ScrollView>
  );
}
