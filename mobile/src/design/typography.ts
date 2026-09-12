/**
 * Fundamentele tipografice.
 *
 * Spațierea dintre litere depinde de mărime — o valoare fixă e greșită undeva.
 * Textul mare are nevoie de spațiere NEGATIVĂ: pe măsură ce crește, literele
 * par prea depărtate. Textul mic are nevoie de puțină spațiere pozitivă,
 * pentru lizibilitate.
 *
 * Interlinia merge invers: strânsă la titluri mari, lejeră la text de citit.
 */
export const TYPE = {
  display: { fontSize: 34, lineHeight: 38, letterSpacing: -0.8, fontWeight: '700' },
  title:   { fontSize: 28, lineHeight: 33, letterSpacing: -0.5, fontWeight: '700' },
  heading: { fontSize: 20, lineHeight: 25, letterSpacing: -0.2, fontWeight: '600' },
  body:    { fontSize: 16, lineHeight: 24, letterSpacing: 0,    fontWeight: '400' },
  callout: { fontSize: 15, lineHeight: 21, letterSpacing: 0,    fontWeight: '500' },
  footnote:{ fontSize: 13, lineHeight: 18, letterSpacing: 0.1,  fontWeight: '400' },
  caption: { fontSize: 11, lineHeight: 14, letterSpacing: 0.3,  fontWeight: '600' },
} as const;

export type TypeScale = keyof typeof TYPE;
