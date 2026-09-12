import { Children, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import * as Haptics from 'expo-haptics';
import * as WebBrowser from 'expo-web-browser';

import { errorMessage, webUrl } from '../../src/api/client';
import { Screen } from '../../src/components/ui/Screen';
import { entrance, spring, useReducedMotion } from '../../src/design/motion';
import { TYPE } from '../../src/design/typography';
import { useChangeLocale, useExportData } from '../../src/features/account/queries';
import { LOCALE_NAMES, SUPPORTED_LOCALES, type Locale } from '../../src/i18n';
import { useAuthStore } from '../../src/stores/auth';
import { useLocaleStore } from '../../src/stores/locale';

/**
 * Ecranul M7 din docs/09: limbă, export de date, documente legale, ștergere.
 *
 * Ștergerea stă ultima și separat, cu text roșu, dar nu ascunsă: Apple cere
 * să poată fi găsită în aplicație, iar legea cere să fie reală (docs/06).
 */
export default function AccountScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const reduced = useReducedMotion();
  const locale = useLocaleStore((s) => s.locale);
  const email = useAuthStore((s) => s.profile?.email);
  const logout = useAuthStore((s) => s.logout);
  const changeLocale = useChangeLocale();
  const exportData = useExportData();

  const onLocale = (code: Locale) =>
    changeLocale.mutate(code, { onError: (error) => Alert.alert('', errorMessage(error)) });

  const onExport = () =>
    exportData.mutate(undefined, {
      onSuccess: (result) => {
        if (result === 'unavailable') {
          Alert.alert('', t('account.exportUnavailable'));
        }
      },
      onError: (error) => {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
        Alert.alert('', errorMessage(error));
      },
    });

  // În limba aplicației, nu a telefonului: browserul din aplicație trimite
  // limba sistemului, iar cele două pot fi diferite.
  const openLegal = (key: 'privacy' | 'terms') =>
    WebBrowser.openBrowserAsync(webUrl(`/legal/${key}?lang=${locale}`));

  return (
    <Screen edges={{ top: true, bottom: false }}>
      <View className="px-5 py-3">
        <Pressable onPress={() => router.back()} hitSlop={8} className="self-start py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 60 }}>
        <MotiView {...entrance(reduced, 0)}>
          <Text className="text-surface-900" style={TYPE.title}>{t('account.title')}</Text>
        </MotiView>

        <Section title={t('account.language')} footer={t('account.languageHint')} index={1} reduced={reduced}>
          <LanguagePicker value={locale} onChange={onLocale} reduced={reduced} />
        </Section>

        <Section title={t('account.dataTitle')} footer={t('account.exportHint')} index={2} reduced={reduced}>
          <Group>
            <Row label={t('account.export')} onPress={onExport} busy={exportData.isPending} />
          </Group>
        </Section>

        <Section title={t('account.legalTitle')} index={3} reduced={reduced}>
          <Group>
            <Row label={t('account.privacy')} accessory="external" onPress={() => openLegal('privacy')} />
            <Row label={t('account.terms')} accessory="external" onPress={() => openLegal('terms')} />
          </Group>
        </Section>

        <Section footer={email ? t('account.signedInAs', { email }) : undefined} index={4} reduced={reduced}>
          <Group>
            <Row label={t('auth.logout')} tone="accent" onPress={logout} />
          </Group>
        </Section>

        <Section footer={t('account.deleteHint')} index={5} reduced={reduced}>
          <Group>
            <Row
              label={t('account.delete')}
              tone="danger"
              accessory="chevron"
              onPress={() => router.push('/account/delete')}
            />
          </Group>
        </Section>
      </ScrollView>
    </Screen>
  );
}

function Section({
  title,
  footer,
  index,
  reduced,
  children,
}: {
  title?: string;
  footer?: string;
  index: number;
  reduced: boolean;
  children: React.ReactNode;
}) {
  return (
    <MotiView {...entrance(reduced, index)}>
      <View className="mt-8">
        {title ? (
          <Text className="mb-2 px-4 text-surface-400" style={{ ...TYPE.caption, textTransform: 'uppercase' }}>
            {title}
          </Text>
        ) : null}

        {children}

        {footer ? (
          <Text className="mt-2 px-4 text-surface-500" style={TYPE.footnote}>
            {footer}
          </Text>
        ) : null}
      </View>
    </MotiView>
  );
}

/** Rânduri grupate, cu separator decalat între ele — idiomul listelor de setări. */
function Group({ children }: { children: React.ReactNode }) {
  const rows = Children.toArray(children);

  return (
    <View className="overflow-hidden rounded-card bg-white">
      {rows.map((row, i) => (
        <View key={i}>
          {i > 0 ? <View style={styles.separator} /> : null}
          {row}
        </View>
      ))}
    </View>
  );
}

const TONE = {
  default: 'text-surface-900',
  accent: 'text-primary-600',
  danger: 'text-danger',
} as const;

function Row({
  label,
  onPress,
  tone = 'default',
  accessory,
  busy = false,
}: {
  label: string;
  onPress: () => void;
  tone?: keyof typeof TONE;
  accessory?: 'chevron' | 'external';
  busy?: boolean;
}) {
  return (
    <Pressable
      onPress={onPress}
      disabled={busy}
      accessibilityRole={accessory === 'external' ? 'link' : 'button'}
      accessibilityState={{ busy }}
      /*
       * Evidențierea apare la apăsare, nu la eliberare. E o schimbare de
       * culoare, nu de poziție, deci rămâne și cu mișcare redusă.
       */
      className="min-h-12 flex-row items-center gap-3 px-4 py-3 active:bg-surface-100"
    >
      <Text className={`flex-1 ${TONE[tone]}`} style={TYPE.body}>
        {label}
      </Text>

      {busy ? (
        <ActivityIndicator size="small" color="#a1a1aa" />
      ) : accessory === 'chevron' ? (
        <Text className="text-surface-300" style={TYPE.heading}>›</Text>
      ) : accessory === 'external' ? (
        <Text className="text-surface-300" style={TYPE.callout}>↗</Text>
      ) : null}
    </Pressable>
  );
}

const PAD = 3;
const RADIUS = 12;

/**
 * Selector segmentat de limbă.
 *
 * Indicatorul alunecă pe un arc, deci o răzgândire rapidă îl redirecționează
 * din mers, din poziția și viteza curentă, fără să aștepte finalul animației.
 */
function LanguagePicker({
  value,
  onChange,
  reduced,
}: {
  value: Locale;
  onChange: (locale: Locale) => void;
  reduced: boolean;
}) {
  const [width, setWidth] = useState(0);
  const x = useSharedValue(0);
  const placed = useRef(false);

  const segment = width > 0 ? (width - PAD * 2) / SUPPORTED_LOCALES.length : 0;
  const index = SUPPORTED_LOCALES.indexOf(value);

  useEffect(() => {
    if (segment === 0) return;

    const target = index * segment;

    // Prima poziționare nu se animă: indicatorul e deja la locul lui când
    // apare ecranul. Cu mișcare redusă nu se animă niciodată — rămâne
    // schimbarea de culoare a textului.
    if (!placed.current || reduced) {
      x.value = target;
      placed.current = true;
    } else {
      x.value = withSpring(target, spring('default'));
    }
  }, [index, segment, reduced, x]);

  const indicator = useAnimatedStyle(() => ({ transform: [{ translateX: x.value }] }));

  return (
    <View
      accessibilityRole="radiogroup"
      onLayout={(event) => setWidth(event.nativeEvent.layout.width)}
      className="flex-row bg-surface-200"
      style={{ padding: PAD, borderRadius: RADIUS }}
    >
      {segment > 0 ? (
        <Animated.View pointerEvents="none" style={[styles.indicator, { width: segment }, indicator]} />
      ) : null}

      {SUPPORTED_LOCALES.map((code) => {
        const selected = code === value;

        return (
          <Pressable
            key={code}
            accessibilityRole="radio"
            accessibilityState={{ checked: selected }}
            onPress={() => {
              if (selected) return;

              Haptics.selectionAsync();
              onChange(code);
            }}
            className="flex-1 items-center py-2 active:opacity-60"
          >
            <Text className={selected ? 'text-surface-900' : 'text-surface-500'} style={TYPE.callout}>
              {LOCALE_NAMES[code]}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  separator: {
    height: StyleSheet.hairlineWidth,
    marginLeft: 16,
    backgroundColor: '#e4e4e7',
  },
  indicator: {
    position: 'absolute',
    top: PAD,
    bottom: PAD,
    left: PAD,
    // Colțuri concentrice: raza interioară e cea exterioară minus spațierea.
    borderRadius: RADIUS - PAD,
    backgroundColor: '#ffffff',
    shadowColor: '#000000',
    shadowOpacity: 0.08,
    shadowRadius: 3,
    shadowOffset: { width: 0, height: 1 },
    elevation: 1,
  },
});
