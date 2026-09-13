import { useEffect } from 'react';
import { ActivityIndicator, Alert, Linking, ScrollView, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { errorMessage } from '../src/api/client';
import { BackButton } from '../src/components/ui/BackButton';
import { Button } from '../src/components/ui/Button';
import { ErrorState } from '../src/components/ui/ErrorState';
import { Screen } from '../src/components/ui/Screen';
import { CheckRow, Group, Section, StepperRow, SwitchRow } from '../src/components/ui/SettingsList';
import { entrance, useReducedMotion } from '../src/design/motion';
import { TYPE } from '../src/design/typography';
import { formatHour, HourRuler } from '../src/features/notifications/HourRuler';
import {
  isQuietHour,
  MAX_REMINDER_STEPS,
  REMINDER_STEPS,
  useNotificationSettings,
  usePushPermission,
  useUpdateNotificationSettings,
  type NotificationSettings,
} from '../src/features/notifications/settings';
import { useAuthStore } from '../src/stores/auth';

const HOURS = Array.from({ length: 24 }, (_, hour) => hour);

/**
 * Ecranul M6 din docs/09: trepte, oră, liniște, rezumat pe email.
 *
 * Totul se salvează imediat. Ce vede utilizatorul aici e și ce face serverul:
 * la orice schimbare, notificările deja planificate se replanifică.
 */
export default function NotificationSettingsScreen() {
  const { t } = useTranslation();
  const reduced = useReducedMotion();
  const email = useAuthStore((s) => s.profile?.email ?? '');
  const { data: settings, isError, error, refetch } = useNotificationSettings();
  const update = useUpdateNotificationSettings();
  const { permission, enable } = usePushPermission();

  const save = (input: Partial<NotificationSettings>) =>
    update.mutate(input, { onError: (error) => Alert.alert('', errorMessage(error)) });

  // Notificările pornite din Setările telefonului, cu telefonul încă
  // neînregistrat: îl înregistrăm singuri, o dată la fiecare schimbare.
  useEffect(() => {
    if (permission === 'granted' && settings && !settings.has_device) {
      enable();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [permission, settings?.has_device]);

  const register = async () => {
    if ((await enable()) === 'unregistered') {
      Alert.alert(t('notifications.unregisteredTitle'), t('notifications.unregisteredBody'));
    }
  };

  const toggleStep = (current: NotificationSettings, day: number) => {
    const selected = current.reminder_days.includes(day);

    save({
      reminder_days: selected
        ? current.reminder_days.filter((d) => d !== day)
        : [...current.reminder_days, day].sort((a, b) => b - a),
    });
  };

  const shiftQuiet = (current: NotificationSettings, field: 'quiet_from' | 'quiet_to', delta: 1 | -1) => {
    const next = (current[field] + delta + 24) % 24;
    const from = field === 'quiet_from' ? next : current.quiet_from;
    const to = field === 'quiet_to' ? next : current.quiet_to;

    // Dacă ora aleasă intră în liniște, o mutăm la sfârșitul intervalului — la
    // fel ca planificatorul. Altfel ecranul ar arăta o oră, iar notificarea ar
    // veni la alta.
    save(
      isQuietHour(current.preferred_hour, from, to)
        ? { [field]: next, preferred_hour: to }
        : { [field]: next },
    );
  };

  return (
    <Screen edges={{ top: true, bottom: false }}>
      <BackButton />

      <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 60 }}>
        <MotiView {...entrance(reduced, 0)}>
          <Text className="text-surface-900" style={TYPE.title}>{t('notifications.title')}</Text>
        </MotiView>

        {isError && !settings ? (
          <ErrorState error={error} onRetry={() => refetch()} />
        ) : !settings ? (
          <ActivityIndicator color="#e11d48" style={{ marginTop: 40 }} />
        ) : (
          <>
            {settings.push_enabled && permission === 'denied' ? (
              <StatusCard
                index={1}
                reduced={reduced}
                title={t('notifications.deniedTitle')}
                body={t('notifications.deniedBody')}
                action={t('notifications.openSettings')}
                onAction={() => Linking.openSettings()}
              />
            ) : settings.push_enabled && permission === 'undetermined' ? (
              <StatusCard
                index={1}
                reduced={reduced}
                title={t('notifications.askTitle')}
                body={t('notifications.askBody')}
                action={t('notifications.allow')}
                onAction={register}
              />
            ) : settings.push_enabled && permission === 'granted' && !settings.has_device ? (
              <StatusCard
                index={1}
                reduced={reduced}
                title={t('notifications.unregisteredTitle')}
                body={t('notifications.unregisteredBody')}
                action={t('common.retry')}
                onAction={register}
              />
            ) : null}

            <Section
              title={t('notifications.pushTitle')}
              footer={settings.push_enabled ? undefined : t('notifications.pushOff')}
              index={2}
              reduced={reduced}
            >
              <Group>
                <SwitchRow
                  label={t('notifications.pushLabel')}
                  value={settings.push_enabled}
                  onValueChange={(push_enabled) => save({ push_enabled })}
                />
              </Group>
            </Section>

            {/* Fără push, restul nu are efect: rămâne vizibil, dar inactiv. */}
            <View
              pointerEvents={settings.push_enabled ? 'auto' : 'none'}
              style={{ opacity: settings.push_enabled ? 1 : 0.4 }}
            >
              <Section
                title={t('notifications.whenTitle')}
                footer={t('notifications.whenFooter', { max: MAX_REMINDER_STEPS })}
                index={3}
                reduced={reduced}
              >
                <Group>
                  {REMINDER_STEPS.map((day) => {
                    const checked = settings.reminder_days.includes(day);

                    return (
                      <CheckRow
                        key={day}
                        label={t('notifications.stepDays', { count: day })}
                        detail={t(`notifications.step${day}`)}
                        checked={checked}
                        disabled={!checked && settings.reminder_days.length >= MAX_REMINDER_STEPS}
                        onPress={() => toggleStep(settings, day)}
                      />
                    );
                  })}
                  <CheckRow label={t('notifications.sameDay')} detail={t('notifications.always')} checked locked />
                </Group>
              </Section>

              <Section title={t('notifications.hourTitle')} footer={t('notifications.hourFooter')} index={4} reduced={reduced}>
                <Group>
                  <HourRuler
                    value={settings.preferred_hour}
                    allowed={HOURS.map((hour) => !isQuietHour(hour, settings.quiet_from, settings.quiet_to))}
                    onChange={(preferred_hour) => save({ preferred_hour })}
                    accessibilityLabel={t('notifications.hourLabel')}
                  />
                </Group>
              </Section>

              <Section
                title={t('notifications.quietTitle')}
                footer={
                  settings.quiet_from === settings.quiet_to
                    ? t('notifications.quietNone')
                    : t('notifications.quietFooter')
                }
                index={5}
                reduced={reduced}
              >
                <Group>
                  <StepperRow
                    label={t('notifications.quietFrom')}
                    value={formatHour(settings.quiet_from)}
                    onDecrement={() => shiftQuiet(settings, 'quiet_from', -1)}
                    onIncrement={() => shiftQuiet(settings, 'quiet_from', 1)}
                  />
                  <StepperRow
                    label={t('notifications.quietTo')}
                    value={formatHour(settings.quiet_to)}
                    onDecrement={() => shiftQuiet(settings, 'quiet_to', -1)}
                    onIncrement={() => shiftQuiet(settings, 'quiet_to', 1)}
                  />
                </Group>
              </Section>
            </View>

            <Section
              title={t('notifications.digestTitle')}
              footer={t('notifications.digestFooter', { email })}
              index={6}
              reduced={reduced}
            >
              <Group>
                <SwitchRow
                  label={t('notifications.digestLabel')}
                  value={settings.email_digest}
                  onValueChange={(email_digest) => save({ email_digest })}
                />
              </Group>
            </Section>
          </>
        )}
      </ScrollView>
    </Screen>
  );
}

function StatusCard({
  title,
  body,
  action,
  onAction,
  index,
  reduced,
}: {
  title: string;
  body: string;
  action: string;
  onAction: () => void;
  index: number;
  reduced: boolean;
}) {
  return (
    <MotiView {...entrance(reduced, index)}>
      <View className="mt-6 rounded-card bg-white px-4 py-4">
        <Text className="text-surface-900" style={TYPE.callout}>{title}</Text>
        <Text className="mt-1 text-surface-500" style={TYPE.footnote}>{body}</Text>
        <Button label={action} variant="secondary" className="mt-3" onPress={onAction} />
      </View>
    </MotiView>
  );
}
