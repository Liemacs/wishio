import { useMemo } from 'react';
import { ActivityIndicator, Pressable, ScrollView, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { BackButton } from '../src/components/ui/BackButton';
import { Screen } from '../src/components/ui/Screen';
import { Group } from '../src/components/ui/SettingsList';
import { entrance, useReducedMotion } from '../src/design/motion';
import { TYPE } from '../src/design/typography';
import { IdeaRow } from '../src/features/gifts/GiftSections';
import { useAllIdeas, type GlobalGiftIdea } from '../src/features/gifts/queries';
import { useIdeaActions } from '../src/features/gifts/useIdeaActions';

/** Ecranul G1 din docs/09, varianta globală: toate ideile salvate, grupate pe persoane. */
export default function SavedIdeasScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const reduced = useReducedMotion();
  const { data: ideas, isLoading } = useAllIdeas();
  const { open, sheet } = useIdeaActions();

  const groups = useMemo(() => {
    const byPerson = new Map<number, { name: string; ideas: GlobalGiftIdea[] }>();

    for (const idea of ideas ?? []) {
      const group = byPerson.get(idea.person.id) ?? { name: idea.person.display_name, ideas: [] };
      group.ideas.push(idea);
      byPerson.set(idea.person.id, group);
    }

    return [...byPerson.entries()];
  }, [ideas]);

  return (
    <Screen edges={{ top: true, bottom: false }}>
      <BackButton />

      <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 60 }}>
        <MotiView {...entrance(reduced, 0)}>
          <Text className="text-surface-900" style={TYPE.title}>{t('gifts.savedTitle')}</Text>
        </MotiView>

        {isLoading ? (
          <ActivityIndicator color="#e11d48" style={{ marginTop: 40 }} />
        ) : groups.length === 0 ? (
          <Text className="mt-6 text-surface-500" style={TYPE.body}>{t('gifts.savedEmpty')}</Text>
        ) : (
          groups.map(([personId, group], index) => (
            <MotiView key={personId} {...entrance(reduced, index + 1)}>
              <View className="mt-8">
                <Pressable
                  onPress={() => router.push(`/people/${personId}`)}
                  className="mb-2 flex-row items-center justify-between px-1 active:opacity-60"
                >
                  <Text className="text-surface-900" style={TYPE.heading}>{group.name}</Text>
                  <Text className="text-surface-300" style={TYPE.heading}>›</Text>
                </Pressable>

                <Group>
                  {group.ideas.map((idea) => (
                    <IdeaRow key={idea.id} idea={idea} onPress={() => open(idea)} />
                  ))}
                </Group>
              </View>
            </MotiView>
          ))
        )}
      </ScrollView>

      {sheet}
    </Screen>
  );
}
