import { useMemo, useState } from 'react';
import {
  Alert, KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, Text, TextInput, View,
} from 'react-native';
import { useTranslation } from 'react-i18next';
import * as Haptics from 'expo-haptics';

import { errorMessage } from '../../api/client';
import { Button } from '../../components/ui/Button';
import { Field } from '../../components/ui/Field';
import { Group, StepperRow } from '../../components/ui/SettingsList';
import { TYPE } from '../../design/typography';
import {
  useAddHistory, useDeleteHistory, usePersonGifts, useSaveIdea,
  type GiftHistoryEntry, type GiftIdea,
} from './queries';
import { useIdeaActions } from './useIdeaActions';

/** Tipurile de ocazie de pe server, spre cheile de traducere din aplicație. */
const OCCASION_KEYS: Record<string, string> = {
  birthday: 'birthday', name_day: 'nameDay', anniversary: 'anniversary', holiday: 'holiday', custom: 'custom',
};

function formatAmount(amount: number | null, currency: string | null, locale: string): string | null {
  if (amount === null) return null;

  return `${new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(amount)} ${currency ?? 'MDL'}`;
}

/**
 * Ideile și istoricul, în fișa persoanei: ecranele G1–G3 din docs/09.
 *
 * Istoricul contează pentru recomandări: ce apare aici nu mai e propus
 * aceleiași persoane. De aceea se poate completa și cu cadourile de dinainte
 * de aplicație.
 */
export function GiftSections({ personId }: { personId: number }) {
  const { t } = useTranslation();
  const { data } = usePersonGifts(personId);
  const saveIdea = useSaveIdea(personId);
  const deleteHistory = useDeleteHistory();
  const { open, sheet } = useIdeaActions();
  const [ideaText, setIdeaText] = useState('');
  const [historyOpen, setHistoryOpen] = useState(false);

  // Serverul le trimite deja de la cel mai recent an; aici doar le grupăm.
  const years = useMemo(() => {
    const groups = new Map<number, GiftHistoryEntry[]>();

    for (const entry of data?.history ?? []) {
      groups.set(entry.year, [...(groups.get(entry.year) ?? []), entry]);
    }

    return [...groups.entries()];
  }, [data?.history]);

  const addIdea = () => {
    const title = ideaText.trim();

    if (title.length < 2) return;

    saveIdea.mutate(
      { title },
      {
        onSuccess: () => setIdeaText(''),
        onError: (error) => Alert.alert('', errorMessage(error)),
      },
    );
  };

  const confirmDeleteHistory = (entry: GiftHistoryEntry) =>
    Alert.alert(t('gifts.deleteHistoryConfirm'), t('gifts.deleteHistoryText', { title: entry.title }), [
      { text: t('common.cancel'), style: 'cancel' },
      {
        text: t('common.delete'),
        style: 'destructive',
        onPress: () => deleteHistory.mutate(entry.id, { onError: (error) => Alert.alert('', errorMessage(error)) }),
      },
    ]);

  return (
    <View className="mt-10 gap-10">
      <View>
        <SectionTitle>{t('gifts.ideasTitle')}</SectionTitle>

        {data && data.ideas.length > 0 ? (
          <Group>
            {data.ideas.map((idea) => (
              <IdeaRow key={idea.id} idea={idea} onPress={() => open(idea)} />
            ))}
          </Group>
        ) : (
          <Text className="px-1 text-surface-400" style={TYPE.footnote}>{t('gifts.ideasEmpty')}</Text>
        )}

        <View className="mt-3 flex-row gap-2">
          <TextInput
            value={ideaText}
            onChangeText={setIdeaText}
            placeholder={t('gifts.ideaPlaceholder')}
            placeholderTextColor="#a1a1aa"
            returnKeyType="done"
            onSubmitEditing={addIdea}
            className="flex-1 rounded-button border border-surface-200 bg-white px-3.5 py-2.5 text-base text-surface-900"
          />
          <Button
            label={t('gifts.addIdea')}
            disabled={ideaText.trim().length < 2}
            loading={saveIdea.isPending}
            onPress={addIdea}
          />
        </View>
      </View>

      <View>
        <SectionTitle>{t('gifts.historyTitle')}</SectionTitle>

        {years.length > 0 ? (
          <View className="gap-5">
            {years.map(([year, entries]) => (
              <View key={year}>
                <Text className="mb-2 px-1 text-surface-500" style={TYPE.callout}>{year}</Text>
                <Group>
                  {entries.map((entry) => (
                    <HistoryRow key={entry.id} entry={entry} onDelete={() => confirmDeleteHistory(entry)} />
                  ))}
                </Group>
              </View>
            ))}
          </View>
        ) : (
          <Text className="px-1 text-surface-400" style={TYPE.footnote}>{t('gifts.historyEmpty')}</Text>
        )}

        <Button
          label={t('gifts.addHistory')}
          variant="secondary"
          className="mt-3"
          onPress={() => setHistoryOpen(true)}
        />
      </View>

      {sheet}

      <AddHistorySheet personId={personId} open={historyOpen} onClose={() => setHistoryOpen(false)} />
    </View>
  );
}

function SectionTitle({ children }: { children: string }) {
  return (
    <Text className="mb-2 px-1 text-surface-400" style={{ ...TYPE.caption, textTransform: 'uppercase' }}>
      {children}
    </Text>
  );
}

export function IdeaRow({ idea, onPress }: { idea: GiftIdea; onPress: () => void }) {
  const { t, i18n } = useTranslation();
  const detail = [t(`gifts.status.${idea.status}`), formatAmount(idea.price, idea.currency, i18n.language)]
    .filter(Boolean)
    .join(' · ');

  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      className="min-h-12 flex-row items-center gap-3 px-4 py-3 active:bg-surface-100"
    >
      <View className="flex-1">
        <Text className="text-surface-900" style={TYPE.body} numberOfLines={2}>{idea.title}</Text>
        <Text className="mt-0.5 text-surface-500" style={TYPE.footnote}>{detail}</Text>
      </View>
      <Text className="text-surface-300" style={TYPE.heading}>›</Text>
    </Pressable>
  );
}

function HistoryRow({ entry, onDelete }: { entry: GiftHistoryEntry; onDelete: () => void }) {
  const { t, i18n } = useTranslation();
  const occasion = entry.occasion_type ? t(`occasions.${OCCASION_KEYS[entry.occasion_type] ?? 'custom'}`) : null;
  const detail = [occasion, formatAmount(entry.amount, null, i18n.language)].filter(Boolean).join(' · ');

  return (
    <View className="min-h-12 flex-row items-center gap-3 px-4 py-3">
      <View className="flex-1">
        <Text className="text-surface-900" style={TYPE.body} numberOfLines={2}>{entry.title}</Text>
        {detail ? <Text className="mt-0.5 text-surface-500" style={TYPE.footnote}>{detail}</Text> : null}
      </View>
      <Pressable
        onPress={onDelete}
        hitSlop={10}
        accessibilityRole="button"
        accessibilityLabel={t('common.delete')}
        className="h-8 w-8 items-center justify-center rounded-full active:bg-surface-100"
      >
        <Text className="text-surface-400" style={TYPE.callout}>✕</Text>
      </Pressable>
    </View>
  );
}

function AddHistorySheet({ personId, open, onClose }: { personId: number; open: boolean; onClose: () => void }) {
  const { t } = useTranslation();
  const add = useAddHistory(personId);
  const thisYear = new Date().getFullYear();
  const [title, setTitle] = useState('');
  const [year, setYear] = useState(thisYear);
  const [amount, setAmount] = useState('');

  const close = () => {
    setTitle('');
    setYear(thisYear);
    setAmount('');
    onClose();
  };

  const submit = () =>
    add.mutate(
      { title: title.trim(), year, amount: amount ? Number(amount) : null },
      {
        onSuccess: () => {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
          close();
        },
        onError: (error) => Alert.alert('', errorMessage(error)),
      },
    );

  return (
    <Modal visible={open} animationType="slide" presentationStyle="pageSheet" onRequestClose={close}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} className="flex-1 bg-surface-50">
        <ScrollView contentContainerStyle={{ padding: 20, paddingTop: 32 }} keyboardShouldPersistTaps="handled">
          <Text className="text-surface-900" style={TYPE.title}>{t('gifts.historyFormTitle')}</Text>

          <View className="mt-6 gap-4">
            <Field label={t('gifts.historyGift')} value={title} onChangeText={setTitle} autoFocus />

            <Group>
              <StepperRow
                label={t('gifts.historyYear')}
                value={String(year)}
                onDecrement={() => setYear((current) => Math.max(1900, current - 1))}
                onIncrement={() => setYear((current) => Math.min(thisYear, current + 1))}
              />
            </Group>

            <Field
              label={t('gifts.historyAmount')}
              value={amount}
              onChangeText={setAmount}
              keyboardType="number-pad"
            />
          </View>

          <Button
            label={t('common.save')}
            className="mt-8"
            disabled={title.trim().length < 2}
            loading={add.isPending}
            onPress={submit}
          />
          <Button label={t('common.cancel')} variant="ghost" className="mt-2" onPress={close} />
        </ScrollView>
      </KeyboardAvoidingView>
    </Modal>
  );
}
