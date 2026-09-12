/**
 * Paritatea traducerilor mobile: RO e sursa de adevăr, RU și EN o urmează.
 *
 * Verifică trei lucruri pe care o comparație simplă de chei le ratează:
 * - formele de plural diferă pe limbă (RU are `_many`, EN nu are `_few`);
 * - variabilele `{{...}}` trebuie să fie aceleași, altfel un text afișează
 *   acolade sau pierde o valoare;
 * - cheile în plus sunt la fel de suspecte ca cele lipsă.
 *
 * Echivalentul pentru backend e `php artisan wishio:i18n-check`.
 */
const fs = require('fs');
const path = require('path');

const LOCALES = ['ro', 'ru', 'en'];
const PLURAL_FORMS = { ro: ['one', 'few', 'other'], ru: ['one', 'few', 'many', 'other'], en: ['one', 'other'] };
const SUFFIX = /_(zero|one|two|few|many|other)$/;

const flatten = (object, prefix = '') =>
  Object.entries(object).flatMap(([key, value]) =>
    value && typeof value === 'object' ? flatten(value, `${prefix}${key}.`) : [[`${prefix}${key}`, value]],
  );

const variables = (text) =>
  [...String(text).matchAll(/\{\{\s*(\w+)\s*\}\}/g)].map((m) => m[1]).sort().join(',');

const tables = Object.fromEntries(
  LOCALES.map((locale) => {
    const file = path.join(__dirname, '..', 'src', 'i18n', 'locales', `${locale}.json`);
    const entries = Object.fromEntries(flatten(JSON.parse(fs.readFileSync(file, 'utf8'))));
    const plain = new Set();
    const plural = {};

    for (const key of Object.keys(entries)) {
      if (SUFFIX.test(key)) {
        (plural[key.replace(SUFFIX, '')] ??= new Set()).add(key.match(SUFFIX)[1]);
      } else {
        plain.add(key);
      }
    }

    return [locale, { entries, plain, plural }];
  }),
);

const problems = [];
const base = tables.ro;

for (const locale of LOCALES.slice(1)) {
  const table = tables[locale];

  for (const key of base.plain) {
    if (!table.plain.has(key)) problems.push(`${locale}: lipsește ${key}`);
    else if (variables(base.entries[key]) !== variables(table.entries[key])) {
      problems.push(`${locale}: alte variabile în ${key}`);
    }
  }

  for (const key of table.plain) {
    if (!base.plain.has(key)) problems.push(`${locale}: cheie în plus ${key}`);
  }
}

const pluralKeys = new Set(LOCALES.flatMap((locale) => Object.keys(tables[locale].plural)));

for (const key of pluralKeys) {
  for (const locale of LOCALES) {
    const forms = tables[locale].plural[key] ?? new Set();

    for (const form of PLURAL_FORMS[locale]) {
      if (!forms.has(form)) problems.push(`${locale}: plural incomplet ${key}_${form}`);
    }
  }
}

if (problems.length > 0) {
  console.error(problems.join('\n'));
  process.exit(1);
}

console.log(`Traduceri în paritate: ${base.plain.size} chei, ${pluralKeys.size} plurale, în ${LOCALES.join('/')}.`);
