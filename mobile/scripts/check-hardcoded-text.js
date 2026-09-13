/**
 * Auditul de traduceri din cod (S11.6). Două lucruri pe care paritatea
 * cheilor nu le vede:
 *
 *  1. Text scris direct în interfață — `<Text>Salvează</Text>`, un
 *     `placeholder="..."` sau `Alert.alert('...')` — pe care nicio limbă nu-l
 *     traduce.
 *  2. Chei folosite în cod care nu există în ro.json: i18next ar afișa cheia
 *     brută, în toate limbile. Tot aici: `defaultValue`, care ar ascunde lipsa.
 *
 * Analiza e pe arborele TypeScript, nu pe expresii regulate: un text dintr-un
 * comentariu sau dintr-un className nu e o problemă.
 */
const fs = require('fs');
const path = require('path');
const ts = require('typescript');

const ROOT = path.join(__dirname, '..');
const RO = JSON.parse(fs.readFileSync(path.join(ROOT, 'src/i18n/locales/ro.json'), 'utf8'));

/** Proprietăți JSX care ajung pe ecran sau la cititorul de ecran. */
const VISIBLE_PROPS = new Set([
  'placeholder', 'accessibilityLabel', 'accessibilityHint', 'title', 'label', 'help', 'error', 'description', 'footer',
]);

/** Ce rămâne voit netradus: simboluri, cifre, marca, moneda. */
const ALLOWED = [
  /^[\p{Extended_Pictographic}\u{FE0F}\u{200D}\s]+$/u,
  /^[›‹✕★☆↗·…•—–\-+:%()/\s\d]+$/u,
  /^Wishio$/,
  /^MDL$/,
];

const needsTranslation = (text) => /\p{L}{2,}/u.test(text) && !ALLOWED.some((pattern) => pattern.test(text.trim()));

const lookup = (parts) => parts.reduce((node, part) => (node && typeof node === 'object' ? node[part] : undefined), RO);

/** Cheie existentă, ținând cont de formele de plural (`days_one`, `days_few`...). */
function keyExists(key) {
  const parts = key.split('.');

  if (lookup(parts) !== undefined) return true;

  const last = parts.pop();
  const parent = lookup(parts);

  return Boolean(parent && typeof parent === 'object' && Object.keys(parent).some((k) => k.startsWith(`${last}_`)));
}

function files(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name);

    if (entry.isDirectory()) return entry.name === 'i18n' ? [] : files(full);

    return /\.tsx?$/.test(entry.name) ? [full] : [];
  });
}

const problems = [];

for (const file of [...files(path.join(ROOT, 'app')), ...files(path.join(ROOT, 'src'))]) {
  const source = ts.createSourceFile(file, fs.readFileSync(file, 'utf8'), ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
  const where = (node) => `${path.relative(ROOT, file)}:${source.getLineAndCharacterOfPosition(node.getStart()).line + 1}`;

  const visit = (node) => {
    if (ts.isJsxText(node)) {
      const text = node.getText().replace(/\s+/g, ' ').trim();

      if (needsTranslation(text)) problems.push(`${where(node)}  text în JSX: „${text}”`);
    }

    if (ts.isJsxAttribute(node) && VISIBLE_PROPS.has(node.name.getText())) {
      const init = node.initializer;
      const literal = init && ts.isStringLiteral(init)
        ? init
        : init && ts.isJsxExpression(init) && init.expression && ts.isStringLiteralLike(init.expression)
          ? init.expression
          : null;

      if (literal && needsTranslation(literal.text)) {
        problems.push(`${where(node)}  ${node.name.getText()}: „${literal.text}”`);
      }
    }

    if (ts.isCallExpression(node)) {
      const callee = node.expression.getText();
      const [first] = node.arguments;

      // `defaultValue` ascunde o cheie lipsă: textul de rezervă apare în toate limbile.
      if ((callee === 't' || callee.endsWith('.t')) && node.arguments[1]?.getText().includes('defaultValue')) {
        problems.push(`${where(node)}  defaultValue ascunde o cheie lipsă`);
      }

      // t('cheie'), i18n.t('cheie'): cheia trebuie să existe.
      if ((callee === 't' || callee.endsWith('.t')) && first) {
        if (ts.isStringLiteralLike(first) && !keyExists(first.text)) {
          problems.push(`${where(node)}  cheie inexistentă: ${first.text}`);
        }

        // t(`gifts.status.${status}`) sau t(`notifications.step${day}`): partea fixă
        // trebuie să ducă la o secțiune, respectiv la chei care încep așa.
        if (ts.isTemplateExpression(first) && first.head.text) {
          const parts = first.head.text.split('.');
          const start = parts.pop();
          const parent = lookup(parts);
          const matches = parent && typeof parent === 'object'
            && (start === '' || Object.keys(parent).some((k) => k.startsWith(start)));

          if (!matches) problems.push(`${where(node)}  cheie dinamică fără corespondent: ${first.head.text}…`);
        }
      }

      if (callee === 'Alert.alert') {
        for (const argument of node.arguments.slice(0, 2)) {
          if (ts.isStringLiteralLike(argument) && needsTranslation(argument.text)) {
            problems.push(`${where(node)}  Alert.alert: „${argument.text}”`);
          }
        }
      }
    }

    ts.forEachChild(node, visit);
  };

  visit(source);
}

if (problems.length > 0) {
  console.error(`${problems.length} probleme de traducere în cod:\n${problems.join('\n')}`);
  process.exit(1);
}

console.log('Cod fără text netradus și fără chei inexistente.');
