import { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, Text, TextInput, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import * as Haptics from 'expo-haptics';

import { errorMessage } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { usePerson, useSaveInterests } from '../../src/features/people/queries';
import { useAnalyzePerson, type InterestSuggestion } from '../../src/features/recommendations/queries';
import { TYPE } from '../../src/design/typography';

/**
 * Ecranul P5 din docs/09: „Spune-mi despre Alex”.
 *
 * Rezultatul se PROPUNE, nu se aplică. O deducere acceptată automat ar umple
 * profilul cu presupuneri pe care nimeni nu le-a verificat, iar recomandările
 * construite peste ele ar părea aleatorii fără motiv vizibil.
 */
export default function AnalyzePerson() {
  const { t } = useTranslation();
  const router = useRouter();
  const { personId } = useLocalSearchParams<{ personId: string }>();
  const id = Number(personId);

  const { data: person } = usePerson(id);
  const analyze = useAnalyzePerson(id);
  const save = useSaveInterests(id);

  const [text, setText] = useState('');
  const [suggestions, setSuggestions] = useState<InterestSuggestion[] | null>(null);
  const [accepted, setAccepted] = useState<string[]>([]);

  const run = () =>
    analyze.mutate(text.trim(), {
      onSuccess: (found) => {
        setSuggestions(found);
        // Pre-bifăm doar ce am dedus cu încredere mare; restul rămâne la
        // alegerea utilizatorului.
        setAccepted(found.filter((s) => s.confidence >= 0.75).map((s) => s.interest.code));
      },
      onError: (error) => Alert.alert('', errorMessage(error)),
    });

  const toggle = (code: string) => {
    Haptics.selectionAsync();
    setAccepted((current) =>
      current.includes(code) ? current.filter((c) => c !== code) : [...current, code],
    );
  };

  const persist = () => {
    const existing = (person?.interests ?? [])
      .filter((i) => i.source === 'owner_manual')
      .map((i) => i.code);

    save.mutate([...new Set([...existing, ...accepted])], {
      onSuccess: () => router.back(),
      onError: (error) => Alert.alert('', errorMessage(error)),
    });
  };

  return (
    <Screen>
      <View className="flex-row items-center gap-3 px-5 py-3">
        <Pressable onPress={() => router.back()} className="py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>
      </View>

      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} className="flex-1">
        <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 60 }} keyboardShouldPersistTaps="handled">
          <Text className="text-surface-900" style={TYPE.heading}>
            {t('analyze.title', { name: person?.display_name ?? '' })}
          </Text>
          <Text className="mt-2 text-sm leading-relaxed text-surface-500">{t('analyze.hint')}</Text>

          <TextInput
            value={text}
            onChangeText={setText}
            placeholder={t('analyze.placeholder')}
            placeholderTextColor="#d4d4d8"
            multiline
            className="mt-4 rounded-card bg-white px-4 py-3.5 text-base leading-relaxed text-surface-900"
            style={{ minHeight: 140, textAlignVertical: 'top' }}
          />

          <Button
            label={t('analyze.analyze')}
            className="mt-4"
            loading={analyze.isPending}
            disabled={text.trim().length < 10}
            onPress={run}
          />

          {suggestions !== null ? (
            <View className="mt-8">
              <Text className="text-sm font-medium text-surface-700">
                {suggestions.length > 0 ? t('analyze.found') : t('analyze.nothing')}
              </Text>

              <View className="mt-3 flex-row flex-wrap gap-2">
                {suggestions.map((suggestion, index) => {
                  const active = accepted.includes(suggestion.interest.code);

                  return (
                    <MotiView
                      key={suggestion.interest.code}
                      from={{ opacity: 0, scale: 0.95 }}
                      animate={{ opacity: 1, scale: 1 }}
                      transition={{ type: 'timing', duration: 220, delay: index * 50 }}
                    >
                      <Pressable
                        onPress={() => toggle(suggestion.interest.code)}
                        className={`rounded-full px-3.5 py-2 ${active ? 'bg-primary-600' : 'bg-white'}`}
                      >
                        <Text className={`text-sm ${active ? 'font-medium text-white' : 'text-surface-700'}`}>
                          {suggestion.interest.label}
                        </Text>
                      </Pressable>
                    </MotiView>
                  );
                })}
              </View>

              {suggestions.length > 0 ? (
                <Button label={t('analyze.save')} className="mt-6" loading={save.isPending}
                        disabled={accepted.length === 0} onPress={persist} />
              ) : null}
            </View>
          ) : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </Screen>
  );
}
