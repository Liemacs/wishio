<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Verifică paritatea cheilor între RO, RU și EN.
 *
 * Regula 1 din CLAUDE.md: nicio funcționalitate nu e terminată fără traduceri.
 * Fără verificarea asta, o cheie lipsă apare ca text brut pe ecran — și se
 * observă abia când o vede un utilizator.
 */
class CheckTranslationsCommand extends Command
{
    protected $signature = 'wishio:i18n-check';

    protected $description = 'Verifică paritatea cheilor de traducere între RO, RU și EN';

    /** Sufixe de plural: rusa are forme pe care româna și engleza nu le au. */
    private const PLURAL_SUFFIXES = ['_one', '_few', '_many', '_other', '_zero', '_two'];

    public function handle(): int
    {
        $base = config('wishio.locales.default');
        $locales = config('wishio.locales.supported');
        $missing = [];

        foreach ($this->keysFor($base) as $file => $keys) {
            foreach ($locales as $locale) {
                if ($locale === $base) {
                    continue;
                }

                $theirs = $this->keysFor($locale)[$file] ?? [];
                $gap = array_diff($keys, $theirs);

                foreach ($gap as $key) {
                    $missing[] = "$locale/$file.php → $key";
                }
            }
        }

        // Și fișierele din mobile: aceeași regulă, alt format.
        foreach ($this->mobileKeys($base) as $key) {
            foreach ($locales as $locale) {
                if ($locale !== $base && ! in_array($key, $this->mobileKeys($locale), true)) {
                    $missing[] = "mobile/$locale.json → $key";
                }
            }
        }

        if ($missing !== []) {
            $this->error(sprintf('%d chei de traducere lipsesc:', count($missing)));

            foreach (array_slice($missing, 0, 40) as $line) {
                $this->line("  · $line");
            }

            return self::FAILURE;
        }

        $this->info('Traduceri complete în '.implode(', ', $locales).'.');

        return self::SUCCESS;
    }

    /** @return array<string, list<string>> */
    private function keysFor(string $locale): array
    {
        $files = [];

        foreach (glob(lang_path("$locale/*.php")) ?: [] as $path) {
            $files[basename($path, '.php')] = $this->flatten(require $path);
        }

        return $files;
    }

    /** @return list<string> */
    private function mobileKeys(string $locale): array
    {
        $path = base_path("../mobile/src/i18n/locales/$locale.json");

        if (! file_exists($path)) {
            return [];
        }

        return $this->flatten(json_decode(file_get_contents($path), true) ?: []);
    }

    /** @return list<string> */
    private function flatten(array $items, string $prefix = ''): array
    {
        $keys = [];

        foreach ($items as $key => $value) {
            $full = $prefix === '' ? (string) $key : "$prefix.$key";

            if (is_array($value)) {
                $keys = [...$keys, ...$this->flatten($value, $full)];

                continue;
            }

            // Formele de plural diferă între limbi prin definiție.
            $keys[] = str_replace(self::PLURAL_SUFFIXES, '', $full);
        }

        return array_values(array_unique($keys));
    }
}
