/**
 * Fără această configurație, Tailwind nu e invocat deloc.
 *
 * NativeWind v5 (react-native-css) nu procesează el CSS-ul: îl dă pipeline-ului
 * web al Expo, care rulează PostCSS doar dacă găsește un postcss.config.
 * Fără el, `@import "tailwindcss"` și `@theme` ajung neatinse la lightningcss,
 * care nu le înțelege — bundle-ul iOS cade, iar exportul web produce un fișier
 * CSS gol, fără nicio eroare.
 */
module.exports = {
  plugins: {
    '@tailwindcss/postcss': {},
  },
};
