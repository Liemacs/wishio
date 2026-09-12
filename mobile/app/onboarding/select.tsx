import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, Text, TextInput, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import * as Haptics from 'expo-haptics';

import { errorMessage } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { readContacts, type ContactsResult, type DeviceContact } from '../../src/features/contacts/deviceContacts';
import { useImportContacts } from '../../src/features/contacts/queries';
import { TYPE } from '../../src/design/typography';

export default function SelectContacts() {
  const { t } = useTranslation();
  const router = useRouter();
  const importContacts = useImportContacts();

  const [result, setResult] = useState<ContactsResult | null>(null);
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [query, setQuery] = useState('');

  useEffect(() => {
    readContacts().then((loaded) => {
      setResult(loaded);
      // Pre-bifăm cine are deja ziua de naștere: sunt cei mai valoroși și
      // utilizatorul nu trebuie să-i caute prin listă.
      setSelected(new Set(loaded.contacts.filter((c) => c.birthDate).map((c) => c.id)));
    });
  }, []);

  const visible = useMemo(() => {
    if (!result) return [];
    const needle = query.trim().toLowerCase();

    return needle
      ? result.contacts.filter((c) => c.name.toLowerCase().includes(needle))
      : result.contacts;
  }, [result, query]);

  const toggle = (id: string) => {
    Haptics.selectionAsync();
    setSelected((current) => {
      const next = new Set(current);
      next.has(id) ? next.delete(id) : next.add(id);

      return next;
    });
  };

  const submit = () => {
    if (!result) return;

    importContacts.mutate(
      {
        contacts: result.contacts.filter((c) => selected.has(c.id)),
        total: result.total,
        withBirthday: result.withBirthday,
      },
      {
        onSuccess: (summary) =>
          router.replace(
            summary.name_days_to_confirm > 0
              ? '/onboarding/confirm'
              : { pathname: '/onboarding/done', params: { ...summary, person_ids: '' } },
          ),
        onError: (error) => Alert.alert('', errorMessage(error)),
      },
    );
  };

  if (!result) {
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
      <View className="px-5 pb-3 pt-2">
        <Text className="text-surface-900" style={TYPE.heading}>{t('onboarding.selectTitle')}</Text>
        <Text className="mt-1 text-sm text-surface-400">
          {t('onboarding.selectedCount', { count: selected.size })}
        </Text>
      </View>

      <View className="flex-row items-center gap-3 px-5 pb-3">
        <TextInput
          value={query}
          onChangeText={setQuery}
          placeholder={t('people.search')}
          placeholderTextColor="#a1a1aa"
          className="flex-1 rounded-button bg-white px-4 py-2.5 text-base text-surface-900"
        />
        <Pressable
          onPress={() =>
            setSelected(selected.size === result.contacts.length
              ? new Set()
              : new Set(result.contacts.map((c) => c.id)))
          }
          className="active:opacity-60"
        >
          <Text className="text-sm font-medium text-primary-600">
            {selected.size === result.contacts.length ? t('onboarding.selectNone') : t('onboarding.selectAll')}
          </Text>
        </Pressable>
      </View>

      <FlashList
        data={visible}
        keyExtractor={(contact) => contact.id}
        contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 130 }}
        ItemSeparatorComponent={() => <View className="h-1.5" />}
        renderItem={({ item }: { item: DeviceContact }) => {
          const active = selected.has(item.id);

          return (
            <Pressable
              onPress={() => toggle(item.id)}
              className="flex-row items-center gap-3 rounded-card bg-white px-4 py-3 active:opacity-70"
            >
              <View className={`h-5 w-5 items-center justify-center rounded-md border-2
                                ${active ? 'border-primary-600 bg-primary-600' : 'border-surface-300'}`}>
                {active ? <Text className="text-xs font-bold text-white">✓</Text> : null}
              </View>

              <View className="flex-1">
                <Text className="text-base text-surface-900" numberOfLines={1}>{item.name}</Text>
                {item.birthDate ? (
                  <Text className="mt-0.5 text-xs text-primary-600">🎂 {t('onboarding.hasBirthday')}</Text>
                ) : null}
              </View>
            </Pressable>
          );
        }}
      />

      <View className="absolute inset-x-0 bottom-0 px-5 pb-8 pt-4">
        <Button
          label={importContacts.isPending ? t('onboarding.importing') : t('common.continue', { defaultValue: '→' })}
          onPress={submit}
          loading={importContacts.isPending}
          disabled={selected.size === 0}
        />
      </View>
    </Screen>
  );
}
