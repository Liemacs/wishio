import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import * as WebBrowser from 'expo-web-browser';

import { errorMessage } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { EmptyState } from '../../src/components/ui/EmptyState';
import { Field } from '../../src/components/ui/Field';
import { Screen } from '../../src/components/ui/Screen';
import { usePerson } from '../../src/features/people/queries';
import { AiConsentSheet } from '../../src/features/recommendations/AiConsentSheet';
import { ProductCard } from '../../src/features/recommendations/ProductCard';
import {
  useRecommendation,
  useStartRecommendation,
  useTrackClick,
} from '../../src/features/recommendations/queries';
import { useAuthStore } from '../../src/stores/auth';

/** Benzile din docs/09 § R1. Aceleași cu cele de pe landing, pentru consecvență. */
const BANDS: { min: number | null; max: number | null; label: string }[] = [
  { min: null, max: 500, label: '< 500' },
  { min: 500, max: 1000, label: '500 – 1 000' },
  { min: 1000, max: 2000, label: '1 000 – 2 000' },
  { min: 2000, max: 5000, label: '2 000 – 5 000' },
  { min: 5000, max: null, label: '5 000+' },
];

export default function GiftFlow() {
  const { t } = useTranslation();
  const router = useRouter();
  const { personId } = useLocalSearchParams<{ personId: string }>();
  const id = Number(personId);

  const { data: person } = usePerson(id);
  const profile = useAuthStore((s) => s.profile);

  const start = useStartRecommendation(id);
  const [runId, setRunId] = useState<number | null>(null);
  const { data: run } = useRecommendation(runId);
  const track = useTrackClick();

  const [band, setBand] = useState<number | null>(null);
  const [customMin, setCustomMin] = useState('');
  const [customMax, setCustomMax] = useState('');
  const [consentOpen, setConsentOpen] = useState(false);

  const budget = () => {
    if (band !== null) {
      return { budget_min: BANDS[band].min, budget_max: BANDS[band].max };
    }

    return {
      budget_min: customMin ? Number(customMin) : null,
      budget_max: customMax ? Number(customMax) : null,
    };
  };

  const search = () =>
    start.mutate(budget(), {
      onSuccess: (created) => setRunId(created.id),
      onError: (error) => Alert.alert('', errorMessage(error)),
    });

  const begin = () => {
    // Consimțământul AI se cere o singură dată, la prima căutare — nu la
    // instalare, când utilizatorul n-are context ca să decidă (docs/06 § 4).
    if (profile && !profile.ai_consent_asked) {
      setConsentOpen(true);

      return;
    }

    search();
  };

  const openShop = (offerId: number) =>
    track.mutate(
      { offerId, personId: id, context: 'recommendation' },
      {
        onSuccess: (url) => WebBrowser.openBrowserAsync(url),
        onError: (error) => Alert.alert('', errorMessage(error)),
      },
    );

  const header = (
    <View className="flex-row items-center gap-3 px-5 py-3">
      <Pressable onPress={() => router.back()} className="py-1 pr-2 active:opacity-60">
        <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
      </Pressable>
    </View>
  );

  // ── R3: se caută ─────────────────────────────────────────────────────────
  if (run?.status === 'pending' || start.isPending) {
    return (
      <Screen>
        {header}
        <View className="flex-1 items-center justify-center px-8">
          <MotiView
            from={{ scale: 0.9, opacity: 0.6 }}
            animate={{ scale: 1.05, opacity: 1 }}
            transition={{ type: 'timing', duration: 900, loop: true }}
          >
            <Text className="text-5xl">🎁</Text>
          </MotiView>
          <Text className="mt-6 text-lg font-semibold text-surface-800">{t('gift.searching')}</Text>
          <Text className="mt-1 text-sm text-surface-400">{t('gift.searchingHint')}</Text>
        </View>
      </Screen>
    );
  }

  // ── R4: rezultate ────────────────────────────────────────────────────────
  if (run?.status === 'ready') {
    const items = run.items ?? [];

    return (
      <Screen edges={{ top: true, bottom: false }}>
        {header}

        {items.length === 0 ? (
          <EmptyState emoji="🤔" title={t('gift.emptyTitle')} description={t('gift.emptyText')}>
            <View className="gap-3">
              <Button label={t('gift.retry')} onPress={() => setRunId(null)} />
              <Button
                label={t('gift.emptyAction')}
                variant="secondary"
                onPress={() => router.replace(`/people/${id}`)}
              />
            </View>
          </EmptyState>
        ) : (
          <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 40 }}>
            <Text className="mb-4 text-2xl font-bold leading-tight text-surface-900">
              {t('gift.resultsTitle', { count: items.length, name: person?.display_name ?? '' })}
            </Text>

            <View className="gap-2">
              {items.map((item, index) => (
                <ProductCard
                  key={item.product.id}
                  item={item}
                  index={index}
                  onPress={() => item.product.offer && openShop(item.product.offer.id)}
                />
              ))}
            </View>

            <Button label={t('gift.retry')} variant="secondary" className="mt-6"
                    onPress={() => setRunId(null)} />
          </ScrollView>
        )}
      </Screen>
    );
  }

  // ── R1: alegerea bugetului ───────────────────────────────────────────────
  return (
    <Screen>
      {header}

      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 60 }}>
        <Text className="text-3xl font-bold leading-tight text-surface-900">{t('gift.title')}</Text>
        <Text className="mt-2 text-base text-surface-500">
          {t('gift.subtitle', { name: person?.display_name ?? '' })}
        </Text>

        <View className="mt-6 gap-2">
          {BANDS.map((option, index) => {
            const active = band === index;

            return (
              <Pressable
                key={option.label}
                onPress={() => setBand(active ? null : index)}
                className={`rounded-card px-5 py-4 ${active ? 'bg-primary-600' : 'bg-white'}`}
              >
                <Text className={`text-base font-semibold ${active ? 'text-white' : 'text-surface-800'}`}>
                  {option.label} MDL
                </Text>
              </Pressable>
            );
          })}

          <Pressable
            onPress={() => setBand(null)}
            className={`rounded-card px-5 py-4 ${band === null ? 'bg-surface-200' : 'bg-white'}`}
          >
            <Text className="text-base font-semibold text-surface-800">{t('gift.custom')}</Text>
          </Pressable>
        </View>

        {band === null ? (
          <View className="mt-4 flex-row gap-3">
            <View className="flex-1">
              <Field label={t('gift.min')} value={customMin} onChangeText={setCustomMin}
                     keyboardType="number-pad" placeholder="0" />
            </View>
            <View className="flex-1">
              <Field label={t('gift.max')} value={customMax} onChangeText={setCustomMax}
                     keyboardType="number-pad" placeholder={t('gift.anyBudget')} />
            </View>
          </View>
        ) : null}

        <Button label={t('gift.search')} className="mt-8" onPress={begin} loading={start.isPending} />
      </ScrollView>

      <AiConsentSheet
        open={consentOpen}
        onDecided={() => {
          setConsentOpen(false);
          search();
        }}
      />
    </Screen>
  );
}
