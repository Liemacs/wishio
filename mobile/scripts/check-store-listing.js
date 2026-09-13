/**
 * Textele din App Store și Google Play, verificate înainte de trimitere.
 *
 * Limitele vin din App Store Connect și Play Console: un text prea lung e
 * respins abia la încărcare, iar cuvintele-cheie se numără în octeți, nu în
 * caractere — în rusă, fiecare literă ocupă doi. Descrierea din Play trebuie
 * să rămână aceeași cu cea din App Store: altfel cele două se despart în timp.
 *
 * Rulare: npm run store:check
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const info = require(path.join(root, 'store.config.json')).apple.info;

// Limba din App Store → dosarul din Play Console.
const PLAY = { ro: 'ro', ru: 'ru-RU', 'en-US': 'en-US' };

const chars = (text) => [...(text ?? '')].length;
const errors = [];

function limit(where, text, max, min = 0) {
  const length = chars(text);

  if (length < min || length > max) {
    errors.push(`${where}: ${length} caractere, limita e ${min}–${max}`);
  }
}

for (const locale of Object.keys(PLAY)) {
  const apple = info[locale];

  if (!apple) {
    errors.push(`${locale}: lipsește din store.config.json`);
    continue;
  }

  limit(`${locale} title`, apple.title, 30, 2);
  limit(`${locale} subtitle`, apple.subtitle, 30);
  limit(`${locale} promoText`, apple.promoText, 170);
  limit(`${locale} description`, apple.description, 4000, 10);

  const keywords = apple.keywords ?? [];
  const bytes = Buffer.byteLength(keywords.join(','), 'utf8');

  if (bytes > 100) {
    errors.push(`${locale} keywords: ${bytes} octeți, limita e 100`);
  }

  for (const keyword of keywords) {
    if (chars(keyword) <= 2) {
      errors.push(`${locale} keywords: „${keyword}” are sub trei caractere`);
    }
  }

  const dir = path.join(root, 'store', 'google-play', PLAY[locale]);
  const read = (file) => fs.readFileSync(path.join(dir, file), 'utf8').trim();

  limit(`${PLAY[locale]} title (Play)`, read('title.txt'), 30, 1);
  limit(`${PLAY[locale]} short_description (Play)`, read('short_description.txt'), 80, 1);
  limit(`${PLAY[locale]} full_description (Play)`, read('full_description.txt'), 4000, 1);

  if (read('full_description.txt') !== apple.description.trim()) {
    errors.push(`${PLAY[locale]}: descrierea din Google Play diferă de cea din App Store`);
  }
}

if (errors.length > 0) {
  console.error(errors.join('\n'));
  process.exit(1);
}

const report = Object.entries(info).map(([locale, apple]) =>
  `${locale}: titlu ${chars(apple.title)}/30, subtitlu ${chars(apple.subtitle)}/30, promo ${chars(apple.promoText)}/170, descriere ${chars(apple.description)}/4000, cuvinte-cheie ${Buffer.byteLength(apple.keywords.join(','), 'utf8')}/100 octeți`,
);

console.log(report.join('\n'));
console.log('Textele din store încap în limite, în toate cele trei limbi.');
