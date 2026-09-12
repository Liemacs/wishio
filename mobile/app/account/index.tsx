import { useEffect, useRef, useState } from 'react';
import { Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import * as Haptics from 'expo-haptics';
import * as WebBrowser from 'expo-web-browser';

import { errorMessage, webUrl } from '../../src/api/client';
import { BackButton } from '../../src/components/ui/BackButton';
import { Screen } from '../../src/components/ui/Screen';
import { Group, Row, Section } from '../../src/components/ui/SettingsList';
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
      <BackButton />

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
