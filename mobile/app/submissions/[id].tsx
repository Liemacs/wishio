import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import * as Haptics from 'expo-haptics';

import { errorMessage } from '../../src/api/client';
import { BackButton } from '../../src/components/ui/BackButton';
import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { CheckRow, Group, Section } from '../../src/components/ui/SettingsList';
import { entrance, useReducedMotion } from '../../src/design/motion';
import { TYPE } from '../../src/design/typography';
import {
  usePendingSubmissions,
  useResolveSubmission,
  type IdentityCandidate,
} from '../../src/features/submissions/queries';
import { relationshipLabel } from '../../src/features/people/relationship';
import { formatBirthday } from '../../src/lib/dates';

/**
 * „Cine este?” — S9.8.
 *
 * O completare al cărei nume seamănă cu contacte existente nu atinge niciunul
 * până aici. Proprietarul alege: unul dintre ei, sau altcineva. Nimic nu e
 * preselectat, fiindcă alegerea trebuie să fie a lui.
 */
export default function ResolveSubmissionScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const reduced = useReducedMotion();
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: pending, isLoading, isFetching, refetch } = usePendingSubmissions();
  const resolve = useResolveSubmission();
  const [choice, setChoice] = useState<number | 'new' | null>(null);

  const submission = pending?.find((item) => item.id === Number(id));

  // Deschis dintr-o notificare cu lista încă în cache: o completare lipsă poate
  // fi doar una nouă. Reîncărcăm o dată înainte să spunem că nu mai așteaptă.
  useEffect(() => {
    if (pending && !submission) {
      refetch();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
  const selected = submission?.candidates.find((candidate) => candidate.id === choice);

  const birthday = (date: string, withYear: boolean) => formatBirthday(date, withYear, i18n.language);

  const candidateDetail = (candidate: IdentityCandidate) =>
    [
      relationshipLabel(candidate.relationship),
      candidate.birth_date
        ? t('submissions.candidateBirthday', { date: birthday(candidate.birth_date, candidate.birth_year_known) })
        : t('submissions.candidateNoBirthday'),
    ]
      .filter(Boolean)
      .join(' · ');

  const pick = (value: number | 'new') => {
    Haptics.selectionAsync();
    setChoice(value);
  };

  const confirm = () => {
    if (!submission || choice === null) return;

    resolve.mutate(
      { id: submission.id, personId: choice === 'new' ? null : choice },
      {
        onSuccess: (personId) => {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
          router.replace(`/people/${personId}`);
        },
        onError: (error) => {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
          Alert.alert('', errorMessage(error));
        },
      },
    );
  };

  // După confirmare, completarea dispare din listă înainte ca navigarea să se
  // termine: nu arătăm „nu mai așteaptă” pentru o clipă.
  if (!submission && resolve.isSuccess) {
    return null;
  }

  if (isLoading || (!submission && isFetching)) {
    return (
      <Screen>
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#e11d48" />
        </View>
      </Screen>
    );
  }

  if (!submission) {
    return (
      <Screen>
        <BackButton />
        <Text className="px-5 text-surface-500" style={TYPE.body}>{t('submissions.gone')}</Text>
      </Screen>
    );
  }

  return (
    <Screen>
      <BackButton />

      <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 40 }}>
        <MotiView {...entrance(reduced, 0)}>
          <Text className="text-surface-900" style={TYPE.title}>
            {t('submissions.title', { name: submission.display_name })}
          </Text>
          <Text className="mt-3 text-surface-600" style={TYPE.body}>{t('submissions.body')}</Text>
        </MotiView>

        <MotiView {...entrance(reduced, 1)}>
          <View className="mt-6 rounded-card bg-white px-4 py-4">
            <Text className="text-surface-400" style={{ ...TYPE.caption, textTransform: 'uppercase' }}>
              {t('submissions.sentTitle')}
            </Text>

            <Text className="mt-2 text-surface-900" style={TYPE.body}>
              {submission.birth_date
                ? t('submissions.birthday', { date: birthday(submission.birth_date, submission.birth_year_known) })
                : t('submissions.noBirthday')}
            </Text>

            {submission.interests.length > 0 ? (
              <Text className="mt-1 text-surface-600" style={TYPE.callout}>
                {t('submissions.interests', { list: submission.interests.map((interest) => interest.label).join(', ') })}
              </Text>
            ) : null}

            {submission.message ? (
              <Text className="mt-2 text-surface-500" style={[TYPE.footnote, { fontStyle: 'italic' }]}>
                {submission.message}
              </Text>
            ) : null}
          </View>
        </MotiView>

        <Section
          title={t('submissions.whoTitle')}
          footer={selected ? t('submissions.keepsName', { name: selected.display_name }) : undefined}
          index={2}
          reduced={reduced}
        >
          <Group>
            {submission.candidates.map((candidate) => (
              <CheckRow
                key={candidate.id}
                single
                label={candidate.display_name}
                detail={candidateDetail(candidate)}
                checked={choice === candidate.id}
                onPress={() => pick(candidate.id)}
              />
            ))}
            <CheckRow
              single
              label={t('submissions.newPerson')}
              detail={t('submissions.newDetail', { name: submission.display_name })}
              checked={choice === 'new'}
              onPress={() => pick('new')}
            />
          </Group>
        </Section>

        <Button
          label={t('submissions.confirm')}
          className="mt-8"
          disabled={choice === null}
          loading={resolve.isPending}
          onPress={confirm}
        />
      </ScrollView>
    </Screen>
  );
}
