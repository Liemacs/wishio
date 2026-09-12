import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';

import { errorMessage } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { InterestPicker } from '../../src/features/people/InterestPicker';
import { PersonForm } from '../../src/features/people/PersonForm';
import { useDeletePerson, usePerson, useUpdatePerson } from '../../src/features/people/queries';

export default function PersonDetail() {
  const { t } = useTranslation();
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const personId = Number(id);

  const { data: person, isLoading, isError, error } = usePerson(personId);
  const update = useUpdatePerson(personId);
  const remove = useDeletePerson();

  const [pickerOpen, setPickerOpen] = useState(false);

  const confirmDelete = () => {
    Alert.alert(t('person.deleteConfirm'), t('person.deleteText'), [
      { text: t('common.cancel'), style: 'cancel' },
      {
        text: t('common.delete'),
        style: 'destructive',
        onPress: () => remove.mutate(personId, { onSuccess: () => router.replace('/people') }),
      },
    ]);
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

  if (isError || !person) {
    return (
      <Screen>
        <View className="flex-1 items-center justify-center px-8">
          <Text className="text-center text-surface-500">{errorMessage(error)}</Text>
          <Button label={t('common.back')} variant="secondary" className="mt-5" onPress={() => router.back()} />
        </View>
      </Screen>
    );
  }

  return (
    <Screen>
      <View className="flex-row items-center justify-between px-5 py-3">
        <Pressable onPress={() => router.back()} className="py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>

        <View className="flex-row items-center gap-4">
          <Pressable onPress={() => router.push(`/gift/${personId}`)} className="py-1 active:opacity-60">
            <Text className="text-sm font-semibold text-primary-600">{t('reminder.findGift')}</Text>
          </Pressable>

          <Pressable onPress={confirmDelete} className="py-1 active:opacity-60">
            <Text className="text-sm font-medium text-danger">{t('common.delete')}</Text>
          </Pressable>
        </View>
      </View>

      <PersonForm
        person={person}
        saving={update.isPending}
        onSubmit={(input) => update.mutate(input, { onError: (e) => Alert.alert('', errorMessage(e)) })}
      >
        <View>
          <Text className="text-sm font-medium text-surface-700">{t('person.interests')}</Text>

          {person.interests && person.interests.length > 0 ? (
            <View className="mt-2 flex-row flex-wrap gap-2">
              {person.interests.map((interest) => (
                <View key={interest.id} className="flex-row items-center gap-1.5 rounded-full bg-surface-200 px-3 py-1.5">
                  <Text className="text-sm text-surface-700">{interest.label}</Text>
                  {/* Interesele deduse se marcheaza vizual: utilizatorul trebuie
                      sa vada ce a spus el si ce am presupus noi (docs/04 § 3). */}
                  {interest.source && interest.source !== 'owner_manual' ? (
                    <Text className="text-[10px] uppercase text-surface-400">{t('person.inferred')}</Text>
                  ) : null}
                </View>
              ))}
            </View>
          ) : (
            <Text className="mt-2 text-sm text-surface-400">{t('person.interestsEmpty')}</Text>
          )}

          <View className="mt-3 gap-2">
            <Button
              label={t('person.chooseInterests')}
              variant="secondary"
              onPress={() => setPickerOpen(true)}
            />
            <Button
              label={t('analyze.analyze')}
              variant="ghost"
              onPress={() => router.push(`/analyze/${personId}`)}
            />
          </View>
        </View>
      </PersonForm>

      <InterestPicker
        personId={personId}
        selected={person.interests ?? []}
        open={pickerOpen}
        onClose={() => setPickerOpen(false)}
      />
    </Screen>
  );
}
