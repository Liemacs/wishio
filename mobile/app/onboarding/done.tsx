import { Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { useOccasions } from '../../src/features/contacts/queries';

/**
 * Ecranul O8 din docs/09 — momentul „aha”.
 *
 * NU presupune un rezultat. Pentru că nu am măsurat în avans câte contacte au
 * ziua completată (docs/00 § D-015), ecranul se adaptează la ce a găsit
 * efectiv — inclusiv la cazul „nimic”, care trebuie să ducă mai departe fără
 * să pară un eșec.
 */
export default function OnboardingDone() {
  const { t } = useTranslation();
  const router = useRouter();
  const { data: occasions } = useOccasions();

  const birthdays = (occasions ?? []).filter((o) => o.type === 'birthday').length;
  const nameDays = (occasions ?? []).filter((o) => o.type === 'name_day' && !o.rejected).length;
  const nothing = birthdays === 0 && nameDays === 0;

  const lines = [
    birthdays > 0 ? t('onboarding.foundBirthdays', { count: birthdays }) : null,
    nameDays > 0 ? t('onboarding.foundNameDays', { count: nameDays }) : null,
  ].filter(Boolean) as string[];

  return (
    <Screen>
      <View className="flex-1 justify-center px-6">
        <MotiView
          from={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ type: 'spring', damping: 16 }}
          className="items-center"
        >
          <Text className="text-5xl">{nothing ? '📝' : '🎂'}</Text>
          <Text className="mt-5 text-center text-3xl font-bold text-surface-900">
            {t('onboarding.doneTitle')}
          </Text>
        </MotiView>

        {nothing ? (
          <Text className="mt-4 text-center text-base leading-relaxed text-surface-500">
            {t('onboarding.foundNothing')}
          </Text>
        ) : (
          <View className="mt-6 gap-2">
            {lines.map((line, index) => (
              <MotiView
                key={line}
                from={{ opacity: 0, translateY: 8 }}
                animate={{ opacity: 1, translateY: 0 }}
                transition={{ type: 'timing', duration: 300, delay: 150 + index * 90 }}
                className="rounded-card bg-white px-5 py-4"
              >
                <Text className="text-center text-lg font-semibold text-surface-900">{line}</Text>
              </MotiView>
            ))}
          </View>
        )}

        <View className="mt-10 gap-3">
          <Button
            label={nothing ? t('people.add') : t('onboarding.start')}
            onPress={() => router.replace(nothing ? '/people/new' : '/onboarding/notifications')}
          />
          {nothing ? (
            <Button label={t('onboarding.start')} variant="ghost" onPress={() => router.replace('/onboarding/notifications')} />
          ) : null}
        </View>
      </View>
    </Screen>
  );
}
