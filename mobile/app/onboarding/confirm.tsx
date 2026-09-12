import { useMemo, useState } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { useOccasions, useSetOccasionStatus } from '../../src/features/contacts/queries';
import { TYPE } from '../../src/design/typography';

const MONTHS_KEY = 'months';

/**
 * Ecranul O7 din docs/09. Onomasticile deduse se confirmă una câte una.
 *
 * Nu le prezentăm ca fapt: un reminder greșit e mai rău decât niciunul, iar
 * până la confirmare ocazia nu generează notificări (docs/15 § 4).
 */
export default function ConfirmNameDays() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const { data: occasions, isLoading } = useOccasions({ unconfirmed: true });
  const setStatus = useSetOccasionStatus();

  const [index, setIndex] = useState(0);

  const pending = useMemo(
    () => (occasions ?? []).filter((o) => o.type === 'name_day'),
    [occasions],
  );

  const current = pending[index];

  const formatDate = (month: number, day: number) =>
    new Intl.DateTimeFormat(i18n.language, { day: 'numeric', month: 'long' })
      .format(new Date(2001, month - 1, day));

  const answer = (status: 'confirmed' | 'rejected') => {
    if (!current) return;

    setStatus.mutate({ id: current.id, status });
    advance();
  };

  const advance = () => {
    if (index + 1 < pending.length) {
      setIndex(index + 1);
    } else {
      router.replace('/onboarding/done');
    }
  };

  if (isLoading) {
    return (
      <Screen>
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#e11d48" />
        </View>
      </Screen>
    );
  }

  if (!current) {
    router.replace('/onboarding/done');

    return null;
  }

  return (
    <Screen>
      <View className="flex-1 px-6 pt-6">
        <Text className="text-surface-900" style={TYPE.heading}>{t('onboarding.confirmTitle')}</Text>
        <Text className="mt-2 text-sm leading-relaxed text-surface-500">{t('onboarding.confirmBody')}</Text>

        <View className="mt-4 h-1 flex-row gap-1">
          {pending.map((occasion, i) => (
            <View
              key={occasion.id}
              className={`h-1 flex-1 rounded-full ${i <= index ? 'bg-primary-500' : 'bg-surface-200'}`}
            />
          ))}
        </View>

        <View className="flex-1 justify-center">
          <MotiView
            key={current.id}
            from={{ opacity: 0, translateY: 10 }}
            animate={{ opacity: 1, translateY: 0 }}
            transition={{ type: 'timing', duration: 260 }}
            className="rounded-card bg-white p-6"
          >
            <Text className="text-center text-4xl">🎉</Text>

            <Text className="mt-4 text-center text-xl font-semibold leading-snug text-surface-900">
              {t('onboarding.confirmQuestion', {
                name: current.person?.display_name ?? '',
                date: formatDate(current.month, current.day),
              })}
            </Text>

            {current.saint_name ? (
              <Text className="mt-2 text-center text-sm text-surface-400">{current.saint_name}</Text>
            ) : null}
          </MotiView>
        </View>

        <View className="gap-3 pb-6">
          <Button label={t('onboarding.yes')} onPress={() => answer('confirmed')} />
          <Button label={t('onboarding.no')} variant="secondary" onPress={() => answer('rejected')} />
          <Button label={t('onboarding.later')} variant="ghost" onPress={advance} />
        </View>
      </View>
    </Screen>
  );
}
