import { useEffect, useState } from 'react';
import { AccessibilityInfo } from 'react-native';
import { ReduceMotion, type WithSpringConfig, type WithTimingConfig } from 'react-native-reanimated';

/**
 * Fundamentele de mișcare.
 *
 * Două parametri, nu trei: raportul de amortizare (cât sare) și răspunsul
 * (cât de repede ajunge). Sunt parametrii cu care gândește un designer;
 * masa, rigiditatea și amortizarea fizică sunt un detaliu de implementare.
 *
 * Regula: amortizare 1.0 — fără depășire — pentru aproape tot. Salt doar
 * acolo unde gestul însuși a purtat impuls (o aruncare, o tragere eliberată).
 * O depășire pe un meniu care doar a apărut se simte greșit; aceeași depășire
 * pe un card pe care l-ai aruncat se simte corect.
 */
export const SPRING = {
  /** Implicit: se așază fără să sară. Repoziționări, apariții, comutări. */
  default: { dampingRatio: 1.0, duration: 400 },

  /** Apăsare: răspuns imediat, fără depășire. */
  press: { dampingRatio: 1.0, duration: 250 },

  /** Impuls: doar când gestul a purtat viteză. */
  momentum: { dampingRatio: 0.8, duration: 400 },

  /** Panouri și sheet-uri, conform valorilor Apple. */
  sheet: { dampingRatio: 0.8, duration: 300 },
} as const satisfies Record<string, WithSpringConfig>;

/** Apariții care nu sunt conduse de un gest: timing, nu arc. */
export const TIMING = {
  enter: { duration: 320 },
  quick: { duration: 180 },
} as const satisfies Record<string, WithTimingConfig>;

/** Decalajul dintre elementele unei liste. Sub 50 ms nu se percepe; peste 80 ms se simte lent. */
export const STAGGER_MS = 70;

/**
 * Preferința de mișcare redusă a sistemului.
 *
 * „Mișcare redusă” NU înseamnă „fără feedback”: înseamnă un echivalent mai
 * blând, fără componenta vestibulară. Păstrăm schimbările de opacitate și
 * culoare, care ajută înțelegerea; renunțăm la deplasări, scalări și depășiri.
 */
export function useReducedMotion(): boolean {
  const [reduced, setReduced] = useState(false);

  useEffect(() => {
    let active = true;

    AccessibilityInfo.isReduceMotionEnabled().then((value) => {
      if (active) setReduced(value);
    });

    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduced);

    return () => {
      active = false;
      subscription.remove();
    };
  }, []);

  return reduced;
}

/**
 * Configurație de arc care respectă preferința sistemului.
 *
 * Reanimated are un mod propriu de mișcare redusă; îl folosim ca sursă de
 * adevăr, ca animațiile să se scurteze consecvent peste tot.
 */
export function spring(preset: keyof typeof SPRING = 'default'): WithSpringConfig {
  return { ...SPRING[preset], reduceMotion: ReduceMotion.System };
}

export function timing(preset: keyof typeof TIMING = 'enter'): WithTimingConfig {
  return { ...TIMING[preset], reduceMotion: ReduceMotion.System };
}

/**
 * Animația de intrare a unui element.
 *
 * Cu mișcare redusă, deplasarea dispare și rămâne doar tranziția de opacitate:
 * utilizatorul înțelege în continuare că a apărut ceva nou, fără mișcare
 * vestibulară.
 */
export function entrance(reduced: boolean, delayIndex = 0) {
  return {
    from: reduced ? { opacity: 0 } : { opacity: 0, translateY: 10 },
    animate: reduced ? { opacity: 1 } : { opacity: 1, translateY: 0 },
    transition: {
      type: 'timing' as const,
      duration: reduced ? 160 : TIMING.enter.duration,
      delay: reduced ? 0 : delayIndex * STAGGER_MS,
    },
  };
}
