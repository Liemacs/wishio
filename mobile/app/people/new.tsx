import { Alert, Pressable, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';

import { errorMessage } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { TYPE } from '../../src/design/typography';
import { PersonForm } from '../../src/features/people/PersonForm';
import { useCreatePerson } from '../../src/features/people/queries';

export default function NewPerson() {
  const { t } = useTranslation();
  const router = useRouter();
  const create = useCreatePerson();

  // Ramura fără contacte din onboarding (docs/09, F1): după prima persoană urmează
  // tot „aha” și întrebarea despre notificări, nu fișa persoanei.
  const { from } = useLocalSearchParams<{ from?: string }>();
  const onboarding = from === 'onboarding';

  const back = () => (router.canGoBack() ? router.back() : router.replace(onboarding ? '/onboarding/contacts' : '/people'));

  return (
    <Screen>
      <View className="flex-row items-center gap-3 px-5 py-3">
        <Pressable onPress={back} className="py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>
        <Text className="text-lg font-semibold text-surface-900">{t('person.new')}</Text>
      </View>

      {onboarding ? (
        <View className="mx-5 rounded-card bg-white px-4 py-3.5">
          <Text className="text-surface-900" style={TYPE.callout}>{t('onboarding.deniedTitle')}</Text>
          <Text className="mt-1 text-surface-500" style={TYPE.footnote}>{t('onboarding.deniedBody')}</Text>
        </View>
      ) : null}

      <PersonForm
        saving={create.isPending}
        onSubmit={(input) =>
          create.mutate(input, {
            onSuccess: (person) => router.replace(onboarding ? '/onboarding/done' : `/people/${person.id}`),
            onError: (error) => Alert.alert('', errorMessage(error)),
          })
        }
        footer={
          onboarding ? (
            <Button label={t('onboarding.later')} variant="ghost" onPress={() => router.replace('/onboarding/done')} />
          ) : undefined
        }
      />
    </Screen>
  );
}
