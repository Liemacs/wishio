import { Alert, Pressable, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';

import { errorMessage } from '../../src/api/client';
import { Screen } from '../../src/components/ui/Screen';
import { PersonForm } from '../../src/features/people/PersonForm';
import { useCreatePerson } from '../../src/features/people/queries';

export default function NewPerson() {
  const { t } = useTranslation();
  const router = useRouter();
  const create = useCreatePerson();

  return (
    <Screen>
      <View className="flex-row items-center gap-3 px-5 py-3">
        <Pressable onPress={() => router.back()} className="py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>
        <Text className="text-lg font-semibold text-surface-900">{t('person.new')}</Text>
      </View>

      <PersonForm
        saving={create.isPending}
        onSubmit={(input) =>
          create.mutate(input, {
            onSuccess: (person) => router.replace(`/people/${person.id}`),
            onError: (error) => Alert.alert('', errorMessage(error)),
          })
        }
      />
    </Screen>
  );
}
