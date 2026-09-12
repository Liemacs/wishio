<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Genereaza imaginile de previzualizare pentru retele sociale, cate una pe limba.
 *
 * Cand linkul e postat intr-un grup de Facebook sau Telegram, previzualizarea
 * este primul lucru pe care il vad oamenii. Fara ea, linkul arata rupt si
 * conversia scade — conteaza direct pentru P0.7 (docs/14 § 4).
 *
 *   php artisan wishio:og-images
 */
class GenerateOgImagesCommand extends Command
{
    protected $signature = 'wishio:og-images';

    protected $description = 'Generează imaginile Open Graph pentru RO, RU și EN';

    private const WIDTH  = 1200;
    private const HEIGHT = 630;

    /** Trebuie sa acopere si chirilicul, si diacriticele romanesti. */
    private const FONT = '/System/Library/Fonts/HelveticaNeue.ttc';

    /** @var array<string, array{headline: string, sub: string, footer: string}> */
    private const COPY = [
        'ro' => [
            'headline' => "Nu știi ce cadou să iei?",
            'sub'      => '5 idei reale, cu preț și magazin din Moldova',
            'footer'   => 'Gratis · în 24 de ore',
        ],
        'ru' => [
            'headline' => 'Не знаете, что подарить?',
            'sub'      => '5 реальных идей с ценой и магазином из Молдовы',
            'footer'   => 'Бесплатно · за 24 часа',
        ],
        'en' => [
            'headline' => "Don't know what to give?",
            'sub'      => '5 real ideas, with prices and shops in Moldova',
            'footer'   => 'Free · within 24 hours',
        ],
    ];

    public function handle(): int
    {
        if (! extension_loaded('imagick')) {
            $this->error('Extensia imagick nu este disponibilă.');

            return self::FAILURE;
        }

        $directory = public_path('og');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach (self::COPY as $locale => $copy) {
            $path = "$directory/$locale.png";
            $this->render($copy)->writeImage($path);
            $this->line(sprintf('  <fg=green>✓</> %s.png  (%s KB)', $locale, number_format(filesize($path) / 1024, 1)));
        }

        $this->newLine();
        $this->info('Imaginile Open Graph au fost generate în public/og/.');

        return self::SUCCESS;
    }

    /** @param array{headline: string, sub: string, footer: string} $copy */
    private function render(array $copy): Imagick
    {
        $image = new Imagick();
        $image->newImage(self::WIDTH, self::HEIGHT, new ImagickPixel('#fffbfb'));
        $image->setImageFormat('png');

        $this->drawBackdrop($image);

        // Bara de accent din stanga — elementul de brand.
        $bar = new ImagickDraw();
        $bar->setFillColor(new ImagickPixel('#e11d48'));
        $bar->rectangle(0, 0, 14, self::HEIGHT);
        $image->drawImage($bar);

        $this->drawText($image, 'WISHIO.MD', 96, 108, 28, '#e11d48', 700);

        // Subtitlul porneste sub titlu, nu de la o pozitie fixa: titlurile au
        // lungimi foarte diferite intre limbi si se suprapuneau.
        $afterHeadline = $this->drawWrapped($image, $copy['headline'], 96, 235, 68, '#18181b', 700, 20);

        $this->drawWrapped($image, $copy['sub'], 96, max($afterHeadline + 62, 450), 32, '#52525b', 400, 46);
        $this->drawText($image, $copy['footer'], 96, self::HEIGHT - 58, 24, '#a1a1aa', 400);

        return $image;
    }

    /** Aura calda in coltul din dreapta-jos, in loc de fundal plat. */
    private function drawBackdrop(Imagick $image): void
    {
        $glow = new ImagickDraw();
        $glow->setFillColor(new ImagickPixel('#ffe4e6'));
        $glow->circle(1150, 640, 1150, 260);
        $image->drawImage($glow);

        $blur = new ImagickDraw();
        $blur->setFillColor(new ImagickPixel('#fff1f2'));
        $blur->circle(1050, 120, 1050, -120);
        $image->drawImage($blur);
    }

    private function drawText(Imagick $image, string $text, int $x, int $y, int $size, string $color, int $weight): void
    {
        $draw = new ImagickDraw();
        $draw->setFont(self::FONT);
        $draw->setFontSize($size);
        $draw->setFontWeight($weight);
        $draw->setFillColor(new ImagickPixel($color));
        $image->annotateImage($draw, $x, $y, 0, $text);
    }

    /**
     * Impachetare pe cuvinte. Returneaza pozitia y a ultimului rand.
     *
     * NU foloseste wordwrap() din PHP: acela numara OCTETI, nu caractere.
     * Chirilicul are 2 octeti pe litera, deci titlul rusesc se rupea in patru
     * randuri si intra peste subtitlu.
     */
    private function drawWrapped(Imagick $image, string $text, int $x, int $y, int $size, string $color, int $weight, int $charsPerLine): int
    {
        $lineHeight = (int) round($size * 1.25);
        $lines      = [];
        $current    = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = $current === '' ? $word : "$current $word";

            if (mb_strlen($candidate) <= $charsPerLine) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        foreach ($lines as $index => $line) {
            $this->drawText($image, $line, $x, $y + $index * $lineHeight, $size, $color, $weight);
        }

        return $y + (count($lines) - 1) * $lineHeight;
    }
}
