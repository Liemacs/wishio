import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Modal, Pressable, ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import * as Haptics from 'expo-haptics';

import type { Interest } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { useInterestGroups, useSaveInterests } from './queries';

type Props = {
  personId: number;
  selected: Interest[];
  open: boolean;
  onClose: () => void;
};

export function InterestPicker({ personId, selected, open, onClose }: Props) {
  const { t } = useTranslation();
  const insets = useSafeAreaInsets();
  const { data: groups, isLoading } = useInterestGroups();
  const save = useSaveInterests(personId);

  // Bifăm doar ce a ales utilizatorul explicit. Interesele deduse rămân
  // în profil, dar nu apar ca alegeri ale lui — vezi docs/16 § 4.
  const manualCodes = useMemo(
    () => selected.filter((i) => i.source === 'owner_manual').map((i) => i.code),
    [selected],
  );

  const [codes, setCodes] = useState<string[]>(manualCodes);

  useEffect(() => { if (open) setCodes(manualCodes); }, [open, manualCodes]);

  const toggle = (code: string) => {
    Haptics.selectionAsync();
    setCodes((current) =>
      current.includes(code) ? current.filter((c) => c !== code) : [...current, code],
    );
  };

  return (
    <Modal visible={open} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
      <View className="flex-1 bg-surface-50">
        <View className="flex-row items-center justify-between border-b border-surface-200 px-5 py-4">
          <Pressable onPress={onClose} className="py-1 active:opacity-60">
            <Text className="text-base text-surface-500">{t('common.cancel')}</Text>
          </Pressable>

          <Text className="text-base font-semibold text-surface-900">{t('person.interests')}</Text>

          <Pressable
            onPress={() => save.mutate(codes, { onSuccess: onClose })}
            disabled={save.isPending}
            className="py-1 active:opacity-60"
          >
            <Text className="text-base font-semibold text-primary-600">{t('common.done')}</Text>
          </Pressable>
        </View>

        {isLoading ? (
          <View className="flex-1 items-center justify-center">
            <ActivityIndicator color="#e11d48" />
          </View>
        ) : (
          <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: insets.bottom + 40 }}>
            {groups?.map((group) => (
              <View key={group.code} className="mb-6">
                <Text className="mb-2.5 text-sm font-semibold text-surface-500">
                  {group.icon} {group.label}
                </Text>

                <View className="flex-row flex-wrap gap-2">
                  {group.interests.map((interest) => {
                    const active = codes.includes(interest.code);

                    return (
                      <Pressable
                        key={interest.code}
                        onPress={() => toggle(interest.code)}
                        className={`rounded-full px-3.5 py-2 ${active ? 'bg-primary-600' : 'bg-white'}`}
                      >
                        <Text className={`text-sm ${active ? 'font-medium text-white' : 'text-surface-700'}`}>
                          {interest.label}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            ))}

            <Button label={t('common.done')} loading={save.isPending}
                    onPress={() => save.mutate(codes, { onSuccess: onClose })} />
          </ScrollView>
        )}
      </View>
    </Modal>
  );
}
