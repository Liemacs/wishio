import { useMemo } from 'react';
import { ActivityIndicator, Pressable, ScrollView, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import { LinearGradient } from 'expo-linear-gradient';

import { Button } from '../src/components/ui/Button';
import { EmptyState } from '../src/components/ui/EmptyState';
import { Screen } from '../src/components/ui/Screen';
import { useOccasions, type Occasion } from '../src/features/contacts/queries';
import { useAuthStore } from '../src/stores/auth';

const ACCENT: Record<string, [string, string]> = {
  birthday:    ['#fff1f2', '#ffffff'],
  name_day:    ['#f5f3ff', '#ffffff'],
  anniversary: ['#fdf2f8', '#ffffff'],
  holiday:     ['#fffbeb', '#ffffff'],
  custom:      ['#f4f4f5', '#ffffff'],
};

const DOT: Record<string, string> = {
  birthday: 'bg-occasion-birthday',
  name_day: 'bg-occasion-nameday',
  anniversary: 'bg-occasion-anniversary',
  holiday: 'bg-occasion-holiday',
  custom: 'bg-surface-400',
};

/** Ecranul H1 din docs/09: o singură întrebare, nu douăzeci de statistici. */
export default function Home() {
  const { t } = useTranslation();
  const router = useRouter();
  const profile = useAuthStore((s) => s.profile);
  const { data: occasions, isLoading, refetch } = useOccasions();

  const upcoming = useMemo(
    () => (occasions ?? []).filter((o) => !o.rejected && !o.is_muted).slice(0, 12),
    [occasions],
  );

  const [next, ...rest] = upcoming;

  // O sărbătoare nu aparține nimănui: titlul e sărbătoarea, iar subtitlul
  // spune pentru câți oameni din listă are sens.
  const isHoliday = (occasion: Occasion) => occasion.type === 'holiday';

  const titleOf = (occasion: Occasion) =>
    isHoliday(occasion) ? occasion.label : (occasion.person?.display_name ?? '');

  const subtitleOf = (occasion: Occasion) => {
    if (!isHoliday(occasion)) return occasion.label;

    const count = occasion.audience?.length ?? 0;

    return count > 0 ? t('home.peopleOnList', { count }) : t('home.noOneMatches');
  };

  const when = (occasion: Occasion) =>
    occasion.days_until === 0
      ? t('home.today')
      : occasion.days_until === 1
        ? t('home.tomorrow')
        : t('home.inDays', { count: occasion.days_until });

  if (isLoading) {
    return (
      <Screen>
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#e11d48" />
        </View>
      </Screen>
    );
  }

  return (
    <Screen edges={{ top: true, bottom: false }}>
      <ScrollView contentContainerStyle={{ paddingBottom: 120 }}>
        <View className="px-5 pb-2 pt-2">
          <Text className="text-sm text-surface-400">
            {t('home.greeting', { name: profile?.name ?? '' })}
          </Text>
          <Text className="mt-1 text-3xl font-bold leading-tight text-surface-900">
            {t('home.todayTitle')}
          </Text>
        </View>

        {!next ? (
          <EmptyState emoji="🎁" title={t('home.emptyTitle')} description={t('home.emptyText')}>
            <Button label={t('people.add')} onPress={() => router.push('/people/new')} />
          </EmptyState>
        ) : (
          <>
            {/* Cea mai apropiată ocazie primește tot spațiul: e singurul lucru
                la care utilizatorul trebuie să se gândească acum. */}
            <MotiView
              from={{ opacity: 0, translateY: 10 }}
              animate={{ opacity: 1, translateY: 0 }}
              transition={{ type: 'timing', duration: 320 }}
              className="mx-5 mt-3 overflow-hidden rounded-card"
            >
              <LinearGradient colors={ACCENT[next.type] ?? ACCENT.custom} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
                <Pressable
                  onPress={() => next.person && router.push(`/people/${next.person.id}`)}
                  className="p-6 active:opacity-70"
                >
                  <View className="flex-row items-center gap-2">
                    <View className={`h-2 w-2 rounded-full ${DOT[next.type] ?? DOT.custom}`} />
                    <Text className="text-sm text-surface-500">{subtitleOf(next)}</Text>
                    {!next.confirmed && next.source === 'derived' ? (
                      <Text className="text-[10px] uppercase text-surface-400">
                        · {t('home.needsConfirm')}
                      </Text>
                    ) : null}
                  </View>

                  <Text className="mt-1 text-3xl font-bold text-surface-900">{titleOf(next)}</Text>
                  <Text className="mt-1 text-lg text-surface-500">{when(next)}</Text>

                  <Button label={t('reminder.findGift')} className="mt-5" onPress={() => {}} />
                </Pressable>
              </LinearGradient>
            </MotiView>

            {rest.length > 0 ? (
              <>
                <Text className="mt-8 px-5 text-xs font-semibold uppercase tracking-wide text-surface-400">
                  {t('home.upcoming')}
                </Text>

                <View className="mt-2 gap-2 px-5">
                  {rest.map((occasion, index) => (
                    <MotiView
                      key={occasion.id}
                      from={{ opacity: 0, translateX: -6 }}
                      animate={{ opacity: 1, translateX: 0 }}
                      transition={{ type: 'timing', duration: 260, delay: 60 * index }}
                    >
                      <Pressable
                        onPress={() => occasion.person && router.push(`/people/${occasion.person.id}`)}
                        className="flex-row items-center gap-3 rounded-card bg-white px-4 py-3.5 active:opacity-70"
                      >
                        <View className={`h-2 w-2 rounded-full ${DOT[occasion.type] ?? DOT.custom}`} />
                        <View className="flex-1">
                          <Text className="text-base font-semibold text-surface-900" numberOfLines={1}>
                            {titleOf(occasion)}
                          </Text>
                          <Text className="mt-0.5 text-sm text-surface-500" numberOfLines={1}>
                            {subtitleOf(occasion)} · {when(occasion)}
                          </Text>
                        </View>
                      </Pressable>
                    </MotiView>
                  ))}
                </View>
              </>
            ) : null}
          </>
        )}
      </ScrollView>

      <View className="absolute inset-x-0 bottom-0 px-5 pb-8 pt-4">
        <Button label={t('home.allPeople')} variant="secondary" onPress={() => router.push('/people')} />
      </View>
    </Screen>
  );
}
