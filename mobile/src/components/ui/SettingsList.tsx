import { Children } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { MotiView } from 'moti';
import * as Haptics from 'expo-haptics';

import { entrance } from '../../design/motion';
import { TYPE } from '../../design/typography';

/**
 * Listele de setări: secțiuni, rânduri grupate și controalele din ele.
 *
 * Toate rândurile au înălțimea minimă de 48 pt și evidențierea la apăsare —
 * o schimbare de culoare, nu de poziție, deci rămâne și cu mișcare redusă.
 */
export function Section({
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

/** Rânduri grupate, cu separator decalat între ele. */
export function Group({ children }: { children: React.ReactNode }) {
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

export function Row({
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

export function SwitchRow({
  label,
  value,
  onValueChange,
  disabled = false,
}: {
  label: string;
  value: boolean;
  onValueChange: (value: boolean) => void;
  disabled?: boolean;
}) {
  return (
    <View className="min-h-12 flex-row items-center gap-3 px-4 py-2">
      <Text className={`flex-1 ${disabled ? 'text-surface-400' : 'text-surface-900'}`} style={TYPE.body}>
        {label}
      </Text>
      <Switch
        accessibilityLabel={label}
        value={value}
        disabled={disabled}
        onValueChange={(next) => {
          Haptics.selectionAsync();
          onValueChange(next);
        }}
        trackColor={{ false: '#e4e4e7', true: '#e11d48' }}
        ios_backgroundColor="#e4e4e7"
      />
    </View>
  );
}

/**
 * Rând cu bifă, pentru alegeri multiple. `locked` e o alegere care nu se
 * poate schimba, dar nu e inactivă: se vede normal, doar nu reacționează.
 */
export function CheckRow({
  label,
  detail,
  checked,
  onPress,
  disabled = false,
  locked = false,
  single = false,
}: {
  label: string;
  detail?: string;
  checked: boolean;
  onPress?: () => void;
  disabled?: boolean;
  locked?: boolean;
  /** O singură alegere din grup: pentru cititorul de ecran e buton radio, nu bifă. */
  single?: boolean;
}) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled || locked}
      accessibilityRole={single ? 'radio' : 'checkbox'}
      accessibilityState={{ checked, disabled: disabled || locked }}
      className={`min-h-12 flex-row items-center gap-3 px-4 py-3 ${locked ? '' : 'active:bg-surface-100'} ${disabled ? 'opacity-40' : ''}`}
    >
      <View className="flex-1">
        <Text className="text-surface-900" style={TYPE.body}>{label}</Text>
        {detail ? <Text className="mt-0.5 text-surface-500" style={TYPE.footnote}>{detail}</Text> : null}
      </View>

      <Text className={locked ? 'text-surface-300' : 'text-primary-600'} style={{ ...TYPE.heading, opacity: checked ? 1 : 0 }}>
        ✓
      </Text>
    </Pressable>
  );
}

/** Rând cu o valoare mică, ajustată pas cu pas. Pentru VoiceOver e un singur element reglabil. */
export function StepperRow({
  label,
  value,
  onDecrement,
  onIncrement,
  disabled = false,
}: {
  label: string;
  value: string;
  onDecrement: () => void;
  onIncrement: () => void;
  disabled?: boolean;
}) {
  return (
    <View
      accessible
      accessibilityRole="adjustable"
      accessibilityLabel={label}
      accessibilityValue={{ text: value }}
      accessibilityState={{ disabled }}
      accessibilityActions={[{ name: 'increment' }, { name: 'decrement' }]}
      onAccessibilityAction={(event) => {
        if (disabled) return;
        if (event.nativeEvent.actionName === 'increment') onIncrement();
        else onDecrement();
      }}
      className={`min-h-12 flex-row items-center gap-2 px-4 py-2 ${disabled ? 'opacity-40' : ''}`}
    >
      <Text className="flex-1 text-surface-900" style={TYPE.body}>{label}</Text>
      <StepButton symbol="−" onPress={onDecrement} disabled={disabled} />
      <Text className="text-surface-900" style={[TYPE.body, styles.stepValue]}>{value}</Text>
      <StepButton symbol="+" onPress={onIncrement} disabled={disabled} />
    </View>
  );
}

function StepButton({ symbol, onPress, disabled }: { symbol: string; onPress: () => void; disabled: boolean }) {
  return (
    <Pressable
      onPress={() => {
        Haptics.selectionAsync();
        onPress();
      }}
      disabled={disabled}
      hitSlop={6}
      className="h-8 w-8 items-center justify-center rounded-full bg-surface-100 active:bg-surface-200"
    >
      <Text className="text-surface-700" style={TYPE.heading}>{symbol}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  separator: {
    height: StyleSheet.hairlineWidth,
    marginLeft: 16,
    backgroundColor: '#e4e4e7',
  },
  // Cifre de lățime egală: valoarea nu „tremură” când trece de la 09 la 10.
  stepValue: {
    minWidth: 56,
    textAlign: 'center',
    fontVariant: ['tabular-nums'],
  },
});
