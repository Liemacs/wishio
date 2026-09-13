#!/usr/bin/env node
/**
 * Grafica pentru store, generată din surse versionate (PLAN S12.1).
 *
 *   node store/render.mjs icons         iconițele aplicației, splash, favicon, iconița și bannerul din Play
 *   node store/render.mjs screenshots   capturile din telefon, încadrate cu titlu, pentru App Store și Google Play
 *   node store/render.mjs web           iconițele site-ului și imaginile Open Graph ale paginii principale
 *
 * Opțiuni: --out=<dosar> scrie în altă parte (pentru previzualizare), iar
 * --raw=<dosar> citește capturile din altă parte.
 *
 * Randează HTML și SVG cu Google Chrome fără interfață și finisează cu
 * ImageMagick (`magick`), ambele instalate local. Pe macOS, Chrome scrie
 * captura, dar nu se închide singur: îl oprim noi, după ce fișierul e complet.
 */
import { execFileSync, spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const MOBILE = path.dirname(HERE);
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const BRAND = { from: '#fb7185', to: '#e11d48', shadow: 'rgba(136, 19, 55, 0.3)' };
const FONT = "'SF Pro Display', -apple-system, 'Helvetica Neue', Arial, sans-serif";

// Limbile ca în App Store Connect; dosarele de capturi poartă aceleași nume.
const LOCALES = ['ro', 'ru', 'en-US'];

const TAGLINES = {
  ro: 'Nu uiți nicio ocazie. Știi ce să oferi.',
  ru: 'Ни одного забытого праздника. И понятно, что подарить.',
  'en-US': 'Never miss an occasion. Always know what to give.',
};

// App Store: capturile de 6,9" acoperă și ecranele mai mici, prin scalare.
// Google Play: 9:16, cel puțin 1080 px, pentru recomandări în magazin.
const PRESETS = [
  { id: 'app-store-6.9', width: 1320, height: 2868 },
  { id: 'google-play', width: 1080, height: 1920 },
];

const BOW = fs.readFileSync(path.join(HERE, 'brand', 'bow.svg'), 'utf8');

const PUBLIC = path.join(MOBILE, '..', 'backend', 'public');

// Previzualizarea linkului wishio.md în Telegram, Facebook și Viber, pe limbi.
const OG = {
  ro: { title: 'Nu uiți nicio ocazie. Știi ce să oferi.', sub: 'Zile de naștere, onomastici și idei de cadou din magazine din Moldova.' },
  ru: { title: 'Ни одного забытого праздника. И понятно, что подарить.', sub: 'Дни рождения, именины и идеи подарков из магазинов Молдовы.' },
  en: { title: 'Never miss an occasion. Always know what to give.', sub: 'Birthdays, name days and gift ideas from shops in Moldova.' },
};

function magick(...args) {
  execFileSync('magick', args, { stdio: 'inherit' });
}

/** Fără canal alfa: App Store respinge capturile și iconițele transparente. */
function opaque(file) {
  magick(file, '-background', 'white', '-alpha', 'remove', '-alpha', 'off', file);
}

async function waitForFile(file, timeout) {
  const started = Date.now();
  let previous = -1;

  while (Date.now() - started < timeout) {
    if (fs.existsSync(file)) {
      const size = fs.statSync(file).size;

      if (size > 0 && size === previous) {
        return;
      }

      previous = size;
    }

    await new Promise((resolve) => setTimeout(resolve, 250));
  }

  throw new Error(`Chrome nu a scris ${path.basename(file)} în ${timeout / 1000} s`);
}

async function render(html, { width, height, out, transparent = false, assets = {} }) {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'wishio-render-'));
  const page = path.join(dir, 'page.html');
  const shot = path.join(dir, 'shot.png');

  fs.writeFileSync(page, html);

  for (const [name, source] of Object.entries(assets)) {
    fs.copyFileSync(source, path.join(dir, name));
  }

  const chrome = spawn(CHROME, [
    '--headless=new',
    '--use-mock-keychain',
    '--disable-gpu',
    '--hide-scrollbars',
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-extensions',
    '--disable-background-networking',
    `--user-data-dir=${path.join(dir, 'profile')}`,
    '--force-device-scale-factor=1',
    ...(transparent ? ['--default-background-color=00000000'] : []),
    `--window-size=${width},${height}`,
    `--screenshot=${shot}`,
    `file://${page}`,
  ], { stdio: 'ignore' });

  try {
    await waitForFile(shot, 30_000);
  } finally {
    chrome.kill('SIGKILL');
  }

  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.copyFileSync(shot, out);
  fs.rmSync(dir, { recursive: true, force: true });
}

function escapeHtml(text) {
  return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function iconPage({ size, background, scale = 0.86, shadow = false, radius = 0, bow = true }) {
  const drop = shadow ? `filter: drop-shadow(0 ${Math.round(size * 0.016)}px ${Math.round(size * 0.02)}px ${BRAND.shadow});` : '';

  return `<!doctype html><html><head><meta charset="utf-8"><style>
    html, body { margin: 0; background: transparent; }
    .tile { position: relative; overflow: hidden; width: ${size}px; height: ${size}px; border-radius: ${radius}px; background: ${background}; color: #fff; }
    .tile svg { position: absolute; inset: 0; width: 100%; height: 100%; transform: scale(${scale}); ${drop} }
  </style></head><body><div class="tile">${bow ? BOW : ''}</div></body></html>`;
}

function featurePage(locale) {
  return `<!doctype html><html><head><meta charset="utf-8"><style>
    html, body { margin: 0; }
    .banner { box-sizing: border-box; display: flex; align-items: center; gap: 56px; width: 1024px; height: 500px; padding: 0 88px;
              background: linear-gradient(135deg, ${BRAND.from}, ${BRAND.to}); color: #fff; font-family: ${FONT}; }
    .tile { position: relative; flex: none; width: 200px; height: 200px; border-radius: 45px; background: rgba(255, 255, 255, 0.16);
            box-shadow: inset 0 0 0 1.5px rgba(255, 255, 255, 0.25); color: #fff; }
    .tile svg { position: absolute; inset: 0; width: 100%; height: 100%; transform: scale(0.84); filter: drop-shadow(0 4px 6px ${BRAND.shadow}); }
    .name { font-size: 84px; font-weight: 700; line-height: 1; letter-spacing: -0.03em; }
    .tagline { max-width: 560px; margin-top: 18px; font-size: 34px; font-weight: 500; line-height: 1.2; letter-spacing: -0.01em; text-wrap: balance; }
  </style></head><body><div class="banner"><div class="tile">${BOW}</div>
    <div><div class="name">Wishio</div><div class="tagline">${escapeHtml(TAGLINES[locale])}</div></div></div></body></html>`;
}

function screenshotPage({ width, height, caption, rawWidth, rawHeight }) {
  const captionSize = Math.round(width * 0.074);
  const top = Math.round(height * 0.055);
  const bottom = Math.round(height * 0.04);
  const bezel = Math.round(width * 0.016);

  // Titlul poate avea două rânduri (în rusă, de obicei are); ecranul ia restul.
  const available = height - top - 2 * 1.08 * captionSize - height * 0.035 - bottom - 2 * bezel;
  const scale = Math.min(available / rawHeight, (width * 0.8) / rawWidth);
  const imageWidth = Math.round(rawWidth * scale);
  const imageHeight = Math.round(rawHeight * scale);
  const radius = Math.round(imageWidth * 0.1);

  return `<!doctype html><html><head><meta charset="utf-8"><style>
    html, body { margin: 0; }
    .page { box-sizing: border-box; display: flex; flex-direction: column; align-items: center; overflow: hidden;
            width: ${width}px; height: ${height}px; padding-top: ${top}px;
            background: linear-gradient(180deg, #fff1f2 0%, #ffffff 70%); font-family: ${FONT}; }
    h1 { width: 86%; margin: 0; color: #18181b; text-align: center; font-size: ${captionSize}px; font-weight: 700;
         line-height: 1.08; letter-spacing: -0.025em; text-wrap: balance; }
    .device { margin-top: auto; margin-bottom: ${bottom}px; padding: ${bezel}px; border-radius: ${radius + bezel}px; background: #111113;
              box-shadow: 0 ${Math.round(width * 0.02)}px ${Math.round(width * 0.05)}px rgba(136, 19, 55, 0.18); }
    .device img { display: block; width: ${imageWidth}px; height: ${imageHeight}px; border-radius: ${radius}px; }
  </style></head><body><div class="page"><h1>${escapeHtml(caption)}</h1>
    <div class="device"><img src="screen.png" alt=""></div></div></body></html>`;
}

/** Fundița ca SVG de sine stătător, pentru favicon: albă, pe pătratul rotunjit. */
function faviconSvg() {
  const inner = BOW
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/^\s*<svg[^>]*>/, '')
    .replace(/<\/svg>\s*$/, '')
    .replaceAll('currentColor', '#ffffff')
    .trim();

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024">
  <!-- Generat din mobile/store/brand/bow.svg cu \`node store/render.mjs web\`. Nu se editează de mână. -->
  <defs>
    <linearGradient id="wishio-bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="${BRAND.from}"/>
      <stop offset="1" stop-color="${BRAND.to}"/>
    </linearGradient>
  </defs>
  <rect width="1024" height="1024" rx="229" fill="url(#wishio-bg)"/>
  <g transform="translate(512 512) scale(0.86) translate(-512 -512)">
    ${inner}
  </g>
</svg>
`;
}

function ogPage(locale) {
  const copy = OG[locale];

  return `<!doctype html><html><head><meta charset="utf-8"><style>
    html, body { margin: 0; }
    .og { box-sizing: border-box; display: flex; align-items: center; gap: 64px; width: 1200px; height: 630px; padding: 0 96px;
          background: linear-gradient(135deg, ${BRAND.from}, ${BRAND.to}); color: #fff; font-family: ${FONT}; }
    .tile { position: relative; flex: none; width: 240px; height: 240px; border-radius: 54px; background: rgba(255, 255, 255, 0.16);
            box-shadow: inset 0 0 0 1.5px rgba(255, 255, 255, 0.25); color: #fff; }
    .tile svg { position: absolute; inset: 0; width: 100%; height: 100%; transform: scale(0.84); filter: drop-shadow(0 5px 8px ${BRAND.shadow}); }
    .name { font-size: 40px; font-weight: 700; letter-spacing: -0.01em; opacity: 0.9; }
    .title { margin-top: 14px; font-size: 60px; font-weight: 700; line-height: 1.08; letter-spacing: -0.025em; text-wrap: balance; }
    .sub { margin-top: 20px; font-size: 28px; font-weight: 500; line-height: 1.3; opacity: 0.92; text-wrap: balance; }
  </style></head><body><div class="og"><div class="tile">${BOW}</div>
    <div><div class="name">Wishio</div><div class="title">${escapeHtml(copy.title)}</div><div class="sub">${escapeHtml(copy.sub)}</div></div></div></body></html>`;
}

async function web() {
  fs.writeFileSync(path.join(PUBLIC, 'favicon.svg'), faviconSvg());

  // PNG-urile pornesc din iconițele aplicației, deja generate: aceeași formă peste tot.
  magick(path.join(MOBILE, 'assets/icon.png'), '-resize', '180x180', path.join(PUBLIC, 'favicon-180.png'));
  magick(path.join(MOBILE, 'assets/icon.png'), '-resize', '512x512', path.join(PUBLIC, 'icon-512.png'));
  magick(path.join(MOBILE, 'assets/splash-icon.png'), '-resize', '32x32', path.join(PUBLIC, 'favicon-32.png'));
  magick(path.join(MOBILE, 'assets/splash-icon.png'), '-define', 'icon:auto-resize=48,32,16', path.join(PUBLIC, 'favicon.ico'));

  for (const locale of Object.keys(OG)) {
    const out = path.join(PUBLIC, 'og', `app-${locale}.png`);
    await render(ogPage(locale), { width: 1200, height: 630, out });
    opaque(out);
  }

  console.log(`Iconițele site-ului și imaginile Open Graph sunt în ${PUBLIC}.`);
}

async function icons(outRoot) {
  const target = (relative) => path.join(outRoot ?? MOBILE, relative);
  const gradient = `linear-gradient(135deg, ${BRAND.from}, ${BRAND.to})`;

  // iOS: pătrat plin, fără transparență. Colțurile le rotunjește sistemul.
  await render(iconPage({ size: 1024, background: gradient, shadow: true }), { width: 1024, height: 1024, out: target('assets/icon.png') });
  opaque(target('assets/icon.png'));

  // Android adaptiv: fundal și prim-plan separate; fundița stă în zona sigură.
  await render(iconPage({ size: 512, background: gradient, bow: false }), { width: 512, height: 512, out: target('assets/android-icon-background.png') });
  await render(iconPage({ size: 512, background: 'transparent', scale: 0.62, shadow: true }), {
    width: 512, height: 512, out: target('assets/android-icon-foreground.png'), transparent: true,
  });

  // Monocrom: sistemul ia doar forma, în culoarea temei. Tot ea e iconița notificărilor.
  await render(iconPage({ size: 432, background: 'transparent', scale: 0.62 }), {
    width: 432, height: 432, out: target('assets/android-icon-monochrome.png'), transparent: true,
  });

  // Splash și favicon: iconița rotunjită, pe fundal transparent.
  await render(iconPage({ size: 1024, background: gradient, shadow: true, radius: 229 }), {
    width: 1024, height: 1024, out: target('assets/splash-icon.png'), transparent: true,
  });
  magick(target('assets/splash-icon.png'), '-resize', '48x48', target('assets/favicon.png'));

  // Google Play: iconița de 512 (pătrat plin, Play o rotunjește) și bannerul, pe limbi.
  fs.mkdirSync(target('store/graphics'), { recursive: true });
  magick(target('assets/icon.png'), '-resize', '512x512', target('store/graphics/play-icon.png'));

  for (const locale of LOCALES) {
    const out = target(`store/graphics/play-feature-${locale}.png`);
    await render(featurePage(locale), { width: 1024, height: 500, out });
    opaque(out);
  }

  console.log(`Iconițele și bannerele sunt în ${outRoot ?? MOBILE}.`);
}

async function screenshots(rawRoot, outRoot) {
  const { shots } = JSON.parse(fs.readFileSync(path.join(HERE, 'screenshots', 'shots.json'), 'utf8'));
  const missing = [];
  let rendered = 0;

  for (const locale of LOCALES) {
    for (const shot of shots) {
      const raw = path.join(rawRoot, locale, `${shot.id}.png`);

      if (!fs.existsSync(raw)) {
        missing.push(`${locale}/${shot.id}.png`);
        continue;
      }

      const [rawWidth, rawHeight] = execFileSync('magick', ['identify', '-format', '%w %h', raw]).toString().trim().split(' ').map(Number);

      for (const preset of PRESETS) {
        const out = path.join(outRoot, preset.id, locale, `${shot.id}.png`);

        await render(screenshotPage({ ...preset, caption: shot.caption[locale], rawWidth, rawHeight }), {
          width: preset.width, height: preset.height, out, assets: { 'screen.png': raw },
        });
        opaque(out);
        rendered++;
      }
    }
  }

  console.log(`${rendered} capturi încadrate în ${outRoot}.`);

  if (missing.length > 0) {
    console.log(`Lipsesc din ${rawRoot}:\n  ${missing.join('\n  ')}`);
  }
}

const [command, ...rest] = process.argv.slice(2);
const option = (name, fallback) => rest.find((arg) => arg.startsWith(`--${name}=`))?.slice(name.length + 3) ?? fallback;

if (command === 'icons') {
  await icons(option('out', null));
} else if (command === 'web') {
  await web();
} else if (command === 'screenshots') {
  await screenshots(option('raw', path.join(HERE, 'screenshots', 'raw')), option('out', path.join(HERE, 'screenshots', 'out')));
} else {
  console.log('Folosire: node store/render.mjs icons|web|screenshots [--out=<dosar>] [--raw=<dosar>]');
  process.exit(1);
}
