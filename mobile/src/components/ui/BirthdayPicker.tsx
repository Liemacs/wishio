import { useMemo, useRef, useState } from 'react';
import { Keyboard, Pressable, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';
import {
  BottomSheetBackdrop,
  BottomSheetModal,
  BottomSheetView,
  type BottomSheetBackdropProps,
} from '@gorhom/bottom-sheet';
import WheelPicker from '@quidone/react-native-wheel-picker';
import * as Haptics from 'expo-haptics';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { TYPE } from '../../design/typography';
import { formatBirthday } from '../../lib/dates';
import { Button } from './Button';

/** Ziua de naștere, cu luna de la 1 la 12; anul lipsește când nu se știe. */
export type BirthdayValue = { day: number; month: number; year: number | null };

// Pe roata anului, prima poziție înseamnă „fără an”.
const NO_YEAR = 0;
const YEARS_BACK = 110;
const ITEM_HEIGHT = 40;

const WHEEL_TEXT = { fontSize: 20, color: '#18181b' };
const WHEEL_OVERLAY = { backgroundColor: '#e4e4e7', opacity: 0.45, borderRadius: 10 };

/** Fără an, luna are câte zile ar avea într-un an bisect: 29 februarie rămâne posibil. */
function daysIn(month: number, year: number | null): number {
  return new Date(Date.UTC(year ?? 2000, month, 0)).getUTCDate();
}

/** Formatul API-ului. Fără an, un an bisect fix, ca la importul din agendă. */
export function toIsoBirthday(value: BirthdayValue): string {
  const pad = (n: number) => String(n).padStart(2, '0');

  return `${value.year ?? 2000}-${pad(value.month)}-${pad(value.day)}`;
}

function today(): BirthdayValue {
  const now = new Date();

  return { day: now.getDate(), month: now.getMonth() + 1, year: null };
}

type Props = {
  label: string;
  help?: string;
  value: BirthdayValue | null;
  onChange: (value: BirthdayValue | null) => void;
};

/**
 * Ziua de naștere se alege pe roți — zi, lună, an —, într-o foaie de jos.
 *
 * Anul e opțional, pentru că deseori știm doar ziua și luna, ca în Contactele
 * din iPhone. Roțile vin din @quidone/react-native-wheel-picker, foaia din
 * @gorhom/bottom-sheet (docs/05 § 1.1). Alegerea se aplică doar la „Gata”:
 * închisă altfel, foaia nu schimbă nimic.
 */
export function BirthdayPicker({ label, help, value, onChange }: Props) {
  const { t, i18n } = useTranslation();
  const insets = useSafeAreaInsets();
  const sheet = useRef<BottomSheetModal>(null);
  const [draft, setDraft] = useState<BirthdayValue>(() => value ?? today());

  const months = useMemo(() => {
    const format = new Intl.DateTimeFormat(i18n.language, { month: 'long', timeZone: 'UTC' });

    return Array.from({ length: 12 }, (_, i) => {
      const name = format.format(new Date(Date.UTC(2000, i, 1)));

      return { value: i + 1, label: name.charAt(0).toLocaleUpperCase(i18n.language) + name.slice(1) };
    });
  }, [i18n.language]);

  const years = useMemo(() => {
    const current = new Date().getFullYear();

    return [
      { value: NO_YEAR, label: t('person.birthDateNoYear') },
      ...Array.from({ length: YEARS_BACK + 1 }, (_, i) => ({ value: current - i, label: String(current - i) })),
    ];
  }, [t]);

  const days = useMemo(
    () => Array.from({ length: daysIn(draft.month, draft.year) }, (_, i) => ({ value: i + 1, label: String(i + 1) })),
    [draft.month, draft.year],
  );

  const update = (patch: Partial<BirthdayValue>) => {
    Haptics.selectionAsync();

    setDraft((current) => {
      const next = { ...current, ...patch };

      // 31 martie trecut pe aprilie devine 30 aprilie; 29 februarie, într-un an nebisect, 28.
      return { ...next, day: Math.min(next.day, daysIn(next.month, next.year)) };
    });
  };

  const open = () => {
    Keyboard.dismiss();
    setDraft(value ?? today());
    sheet.current?.present();
  };

  const close = (result: BirthdayValue | null) => {
    onChange(result);
    sheet.current?.dismiss();
  };

  const text = value ? formatBirthday(toIsoBirthday(value), value.year !== null, i18n.language) : null;

  const renderBackdrop = (props: BottomSheetBackdropProps) => (
    <BottomSheetBackdrop {...props} appearsOnIndex={0} disappearsOnIndex={-1} pressBehavior="close" />
  );

  const wheel = {
    itemHeight: ITEM_HEIGHT,
    visibleItemCount: 5,
    width: '100%' as const,
    enableScrollByTapOnItem: true,
    itemTextStyle: WHEEL_TEXT,
    overlayItemStyle: WHEEL_OVERLAY,
  };

  return (
    <View>
      <Text className="text-sm font-medium text-surface-700">{label}</Text>

      <Pressable
        onPress={open}
        accessibilityRole="button"
        accessibilityLabel={label}
        accessibilityValue={{ text: text ?? t('person.birthDatePick') }}
        className="mt-1.5 flex-row items-center rounded-button border border-surface-200 bg-white px-3.5 py-3 active:bg-surface-50"
      >
        <Text className={`flex-1 text-base ${text ? 'text-surface-900' : 'text-surface-300'}`}>
          {text ?? t('person.birthDatePick')}
        </Text>
        <Text className="text-surface-300" style={TYPE.heading}>›</Text>
      </Pressable>

      {help ? <Text className="mt-1.5 text-xs leading-relaxed text-surface-400">{help}</Text> : null}

      <BottomSheetModal
        ref={sheet}
        // Roțile se derulează vertical: foaia se trage doar de mâner, altfel
        // fiecare derulare ar închide-o.
        enableContentPanningGesture={false}
        backdropComponent={renderBackdrop}
        backgroundStyle={{ backgroundColor: '#fafafa', borderRadius: 24 }}
        handleIndicatorStyle={{ backgroundColor: '#d4d4d8' }}
      >
        <BottomSheetView style={{ paddingHorizontal: 20, paddingBottom: insets.bottom + 16 }}>
          <Text className="text-surface-900" style={TYPE.heading}>{label}</Text>

          <View className="mt-4 flex-row gap-2">
            <View style={{ flex: 0.8 }}>
              <WheelPicker {...wheel} data={days} value={draft.day} onValueChanged={({ item }) => update({ day: item.value })} />
            </View>
            <View style={{ flex: 1.6 }}>
              <WheelPicker {...wheel} data={months} value={draft.month} onValueChanged={({ item }) => update({ month: item.value })} />
            </View>
            <View style={{ flex: 1.2 }}>
              <WheelPicker
                {...wheel}
                data={years}
                value={draft.year ?? NO_YEAR}
                onValueChanged={({ item }) => update({ year: item.value === NO_YEAR ? null : item.value })}
              />
            </View>
          </View>

          <View className="mt-5 gap-2">
            <Button label={t('common.done')} onPress={() => close(draft)} />
            {value ? <Button label={t('person.birthDateClear')} variant="ghost" onPress={() => close(null)} /> : null}
          </View>
        </BottomSheetView>
      </BottomSheetModal>
    </View>
  );
}
