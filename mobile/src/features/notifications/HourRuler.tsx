import { useEffect, useMemo, useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Gesture, GestureDetector } from 'react-native-gesture-handler';
import Animated, {
  cancelAnimation,
  Extrapolation,
  interpolate,
  useAnimatedStyle,
  useSharedValue,
  withSpring,
  type SharedValue,
} from 'react-native-reanimated';
import { scheduleOnRN } from 'react-native-worklets';
import { LinearGradient } from 'expo-linear-gradient';
import * as Haptics from 'expo-haptics';

import { spring, useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';

const HOURS = Array.from({ length: 24 }, (_, hour) => hour);

/** Lățimea unei ore: o țintă de atingere de 44 pt. */
const STEP = 44;

/** Rata de decelerare a derulării iOS. Din ea se proiectează unde s-ar opri rigla. */
const DECELERATION = 0.998;

/** Configurațiile se calculează o dată: în worklet nu se pot apela funcții JS. */
const MOMENTUM = spring('momentum');
const SETTLE = spring('default');

export const formatHour = (hour: number) => `${String(hour).padStart(2, '0')}:00`;

function clampIndex(index: number): number {
  'worklet';
  return Math.min(23, Math.max(0, index));
}

/** Cea mai apropiată oră permisă. Orele de liniște se văd, dar sunt sărite. */
function nearestAllowed(index: number, allowed: boolean[]): number {
  'worklet';
  for (let distance = 0; distance < 24; distance++) {
    if (index - distance >= 0 && allowed[index - distance]) return index - distance;
    if (index + distance <= 23 && allowed[index + distance]) return index + distance;
  }
  return index;
}

/** Rezistența elastică de la capete, cu formula folosită de derularea iOS. */
function rubberband(value: number, min: number, max: number, dimension: number): number {
  'worklet';
  const band = (overshoot: number) => (overshoot * dimension * 0.55) / (dimension + 0.55 * overshoot);

  if (value > max) return max + band(value - max);
  if (value < min) return min - band(min - value);
  return value;
}

/**
 * Selectorul orei, tras cu degetul.
 *
 * Rigla stă sub deget cadru cu cadru. La eliberare, viteza gestului proiectează
 * unde s-ar fi oprit singură; ne fixăm pe cea mai apropiată oră permisă de acel
 * punct, cu un arc care pornește din viteza degetului — deci fără salt de
 * viteză la trecerea de la gest la animație. Atingerea opreşte o riglă în
 * mișcare exact unde e.
 *
 * Valoarea se salvează la eliberare, nu în timpul gestului.
 */
export function HourRuler({
  value,
  onChange,
  allowed,
  accessibilityLabel,
}: {
  value: number;
  onChange: (hour: number) => void;
  allowed: boolean[];
  accessibilityLabel: string;
}) {
  const reduced = useReducedMotion();
  const [width, setWidth] = useState(0);
  const [preview, setPreview] = useState(value);

  const x = useSharedValue(-value * STEP);
  const start = useSharedValue(0);
  const lastIndex = useSharedValue(value);
  const dragging = useSharedValue(false);

  // Gestul e memorat; valoarea și callback-ul curente le citește de aici.
  const latest = useRef({ value, onChange, allowed });
  latest.current = { value, onChange, allowed };

  // O valoare venită din afară (serverul, schimbarea liniștii) mută rigla,
  // dar nu în timpul unui gest: degetul are prioritate.
  useEffect(() => {
    setPreview(value);

    if (dragging.value) return;

    lastIndex.value = value;
    x.value = reduced ? -value * STEP : withSpring(-value * STEP, SETTLE);
  }, [value, reduced, x, lastIndex, dragging]);

  const tick = (index: number) => {
    setPreview(index);
    Haptics.selectionAsync();
  };

  const commit = (index: number) => {
    setPreview(index);

    if (index !== latest.current.value) {
      latest.current.onChange(index);
    }
  };

  const allowedKey = allowed.join(',');

  const gesture = useMemo(() => {
    const min = -23 * STEP;
    const max = 0;

    const settleAt = (index: number, velocity: number) => {
      'worklet';
      const target = -index * STEP;

      x.value = reduced ? target : withSpring(target, { ...(velocity === 0 ? SETTLE : MOMENTUM), velocity });
      lastIndex.value = index;
      scheduleOnRN(commit, index);
    };

    return (
      Gesture.Pan()
        // Doar pe orizontală: derularea verticală a paginii rămâne a paginii.
        .activeOffsetX([-8, 8])
        .failOffsetY([-12, 12])
        .onBegin(() => {
          cancelAnimation(x);
          dragging.value = true;
          start.value = x.value;
        })
        .onUpdate((event) => {
          x.value = rubberband(start.value + event.translationX, min, max, width);

          const index = clampIndex(Math.round(-x.value / STEP));

          if (index !== lastIndex.value) {
            lastIndex.value = index;
            scheduleOnRN(tick, index);
          }
        })
        .onEnd((event) => {
          const projected = x.value + ((event.velocityX / 1000) * DECELERATION) / (1 - DECELERATION);
          const index = nearestAllowed(clampIndex(Math.round(-projected / STEP)), allowed);

          settleAt(index, event.velocityX);
        })
        .onFinalize((_event, success) => {
          dragging.value = false;

          // Atins fără tragere: rigla s-a oprit din mers, rămâne unde a prins-o degetul.
          if (!success) {
            settleAt(nearestAllowed(clampIndex(Math.round(-x.value / STEP)), allowed), 0);
          }
        })
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [width, reduced, allowedKey]);

  const strip = useAnimatedStyle(() => ({ transform: [{ translateX: x.value }] }));

  const step = (direction: 1 | -1) => {
    const { value: current, allowed: permitted, onChange: change } = latest.current;
    let index = current + direction;

    while (index >= 0 && index <= 23 && !permitted[index]) {
      index += direction;
    }

    if (index < 0 || index > 23) return;

    Haptics.selectionAsync();
    change(index);
  };

  const offset = width / 2 - STEP / 2;

  return (
    <View>
      <View className="flex-row items-center justify-center gap-4 pt-4">
        <Chevron symbol="‹" onPress={() => step(-1)} />
        <Text className="text-surface-900" style={[TYPE.display, styles.readout]}>
          {formatHour(preview)}
        </Text>
        <Chevron symbol="›" onPress={() => step(1)} />
      </View>

      <GestureDetector gesture={gesture}>
        <View
          onLayout={(event) => setWidth(event.nativeEvent.layout.width)}
          accessible
          accessibilityRole="adjustable"
          accessibilityLabel={accessibilityLabel}
          accessibilityValue={{ text: formatHour(value) }}
          accessibilityActions={[{ name: 'increment' }, { name: 'decrement' }]}
          onAccessibilityAction={(event) => step(event.nativeEvent.actionName === 'increment' ? 1 : -1)}
          style={styles.track}
        >
          {width > 0 ? (
            <>
              <View pointerEvents="none" style={[styles.lens, { left: offset + 3 }]} />

              <Animated.View style={[styles.strip, { left: offset }, strip]}>
                {HOURS.map((hour) => (
                  <Tick key={hour} hour={hour} x={x} allowed={allowed[hour]} reduced={reduced} />
                ))}
              </Animated.View>

              {/* Marginile se estompează: rigla continuă dincolo de ce se vede. */}
              <LinearGradient
                pointerEvents="none"
                colors={['#ffffff', 'rgba(255,255,255,0)']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.fade, { left: 0 }]}
              />
              <LinearGradient
                pointerEvents="none"
                colors={['rgba(255,255,255,0)', '#ffffff']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.fade, { right: 0 }]}
              />
            </>
          ) : null}
        </View>
      </GestureDetector>
    </View>
  );
}

function Tick({ hour, x, allowed, reduced }: { hour: number; x: SharedValue<number>; allowed: boolean; reduced: boolean }) {
  const style = useAnimatedStyle(() => {
    // Distanța față de centru, în ore. Se recalculează la fiecare cadru al gestului.
    const distance = Math.abs(hour + x.value / STEP);
    const opacity = interpolate(distance, [0, 4], [1, 0.3], Extrapolation.CLAMP);

    return {
      opacity: allowed ? opacity : Math.min(opacity, 0.3),
      // Cu mișcare redusă rămâne doar opacitatea, fără scalare.
      transform: [{ scale: reduced ? 1 : interpolate(distance, [0, 1], [1.2, 1], Extrapolation.CLAMP) }],
    };
  });

  return (
    <Animated.View style={[styles.tick, style]}>
      <Text className={allowed ? 'text-surface-900' : 'text-surface-400'} style={[TYPE.callout, styles.tabular]}>
        {String(hour).padStart(2, '0')}
      </Text>
      <View style={allowed ? styles.mark : styles.quietBand} />
    </Animated.View>
  );
}

function Chevron({ symbol, onPress }: { symbol: string; onPress: () => void }) {
  return (
    <Pressable
      onPress={onPress}
      hitSlop={10}
      // Rigla e deja un element reglabil pentru VoiceOver; butoanele ar fi dubluri.
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
      className="h-10 w-10 items-center justify-center rounded-full active:bg-surface-100"
    >
      <Text className="text-surface-400" style={TYPE.title}>{symbol}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  // Cifre de lățime egală: ora nu „tremură” când trece de la 09 la 10.
  readout: { minWidth: 120, textAlign: 'center', fontVariant: ['tabular-nums'] },
  tabular: { fontVariant: ['tabular-nums'] },
  track: { height: 64, marginTop: 8, marginBottom: 8, overflow: 'hidden' },
  strip: { position: 'absolute', top: 0, bottom: 0, flexDirection: 'row' },
  tick: { width: STEP, alignItems: 'center', justifyContent: 'center', gap: 6 },
  mark: { width: 2, height: 10, borderRadius: 1, backgroundColor: '#a1a1aa' },
  // Orele de liniște alăturate formează o bandă continuă.
  quietBand: { width: STEP, height: 2, backgroundColor: '#d4d4d8' },
  lens: { position: 'absolute', top: 6, bottom: 6, width: STEP - 6, borderRadius: 10, backgroundColor: '#fff1f2' },
  fade: { position: 'absolute', top: 0, bottom: 0, width: 56 },
});
