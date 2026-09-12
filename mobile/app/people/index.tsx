import { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, Text, TextInput, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';

import { errorMessage } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { EmptyState } from '../../src/components/ui/EmptyState';
import { Screen } from '../../src/components/ui/Screen';
import { PersonRow } from '../../src/features/people/PersonRow';
import { usePeople } from '../../src/features/people/queries';
import { TYPE } from '../../src/design/typography';

export default function PeopleList() {
  const { t } = useTranslation();
  const router = useRouter();
  const { data: people, isLoading, isError, error, refetch, isRefetching } = usePeople();

  const [query, setQuery] = useState('');

  const filtered = useMemo(() => {
    if (!people) return [];
    const needle = query.trim().toLowerCase();
    if (!needle) return people;

    return people.filter((person) => person.display_name.toLowerCase().includes(needle));
  }, [people, query]);

  return (
    <Screen edges={{ top: true, bottom: false }}>
      <View className="flex-row items-center justify-between px-5 pb-3 pt-2">
        <View>
          <Text className="text-surface-900" style={TYPE.title}>{t('people.title')}</Text>
          {people && people.length > 0 ? (
            <Text className="mt-0.5 text-sm text-surface-400">
              {t('people.count', { count: people.length })}
            </Text>
          ) : null}
        </View>

        <Pressable
          onPress={() => router.push('/profile')}
          className="h-10 w-10 items-center justify-center rounded-full bg-surface-200/70 active:opacity-60"
        >
          <Text className="text-base">👤</Text>
        </Pressable>
      </View>

      {people && people.length > 0 ? (
        <View className="px-5 pb-3">
          <TextInput
            value={query}
            onChangeText={setQuery}
            placeholder={t('people.search')}
            placeholderTextColor="#a1a1aa"
            className="rounded-button bg-white px-4 py-2.5 text-base text-surface-900"
          />
        </View>
      ) : null}

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#e11d48" />
        </View>
      ) : isError ? (
        <EmptyState emoji="⚠️" title={errorMessage(error)}>
          <Button label={t('common.retry')} variant="secondary" onPress={() => refetch()} />
        </EmptyState>
      ) : filtered.length === 0 ? (
        <EmptyState
          emoji={query ? '🔍' : '🎁'}
          title={query ? t('people.search') : t('people.emptyTitle')}
          description={query ? undefined : t('people.emptyText')}
        >
          {!query && (
            <View className="gap-3">
              <Button label={t('onboarding.allow')} onPress={() => router.push('/onboarding/contacts')} />
              <Button label={t('people.add')} variant="secondary" onPress={() => router.push('/people/new')} />
            </View>
          )}
        </EmptyState>
      ) : (
        <FlashList
          data={filtered}
          keyExtractor={(person) => String(person.id)}
          contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 120 }}
          ItemSeparatorComponent={() => <View className="h-2" />}
          onRefresh={refetch}
          refreshing={isRefetching}
          renderItem={({ item }) => (
            <PersonRow person={item} onPress={() => router.push(`/people/${item.id}`)} />
          )}
        />
      )}

      {filtered.length > 0 ? (
        <View className="absolute inset-x-0 bottom-0 px-5 pb-8 pt-4">
          <Button label={t('people.add')} onPress={() => router.push('/people/new')} />
        </View>
      ) : null}
    </Screen>
  );
}
