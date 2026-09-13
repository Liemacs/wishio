<?php

namespace App\Domain\Reminders\Actions;

use App\Domain\Occasions\Models\Occasion;

/**
 * Textul unei notificări. Vezi scara din docs/09 § 3.
 *
 * Regula: fiecare treaptă spune ALTCEVA. O serie care repetă același mesaj de
 * patru ori se transformă în zgomot, iar utilizatorul închide notificările.
 * Cu 7 zile înainte invităm la acțiune; în ziua respectivă doar anunțăm.
 */
class BuildReminderContent
{
    /** @return array{title: string, body: string, cta: string} */
    public function __invoke(Occasion $occasion, int $daysBefore, string $locale): array
    {
        if ($occasion->isHoliday()) {
            return $this->holiday($occasion, $daysBefore, $locale);
        }

        $replace = [
            'name' => $occasion->person->display_name,
            // Forma din propoziție, nu eticheta: „are ziua”, nu „are zi de naștere”.
            'occasion' => __("wishio.push.occasion.{$occasion->type}", [], $locale),
            'count'    => $daysBefore,
        ];

        // Titlul: numele și momentul, scurt, ca iOS să nu-l taie (~40 de caractere,
        // docs/09 § 3). Invitația la acțiune stă în corp, de la 5 zile în sus: mai e
        // timp să cumperi.
        $title = match (true) {
            $daysBefore === 0 => __('wishio.push.today', $replace, $locale),
            $daysBefore === 1 => __('wishio.push.tomorrow', $replace, $locale),
            default           => trans_choice('wishio.push.soon', $daysBefore, $replace, $locale),
        };

        $body = array_filter([
            $occasion->type === 'name_day' ? $occasion->nameDay?->saintName($locale) : null,
            $daysBefore >= 5 ? __('wishio.push.ask', [], $locale) : null,
        ]);

        return [
            'title' => $title,
            'body'  => implode(' · ', $body),
            'cta'   => __('wishio.push.cta', [], $locale),
        ];
    }

    /**
     * O sărbătoare produce O SINGURĂ notificare, nu una per persoană.
     * „8 Martie e peste 3 zile, ai 12 persoane pe listă” — nu douăsprezece
     * notificări separate, care ar fi zgomot garantat.
     */
    private function holiday(Occasion $occasion, int $daysBefore, string $locale): array
    {
        $replace = ['holiday' => $occasion->holiday->label($locale), 'count' => $daysBefore];

        $title = match (true) {
            $daysBefore === 0 => __('wishio.holiday.today', $replace, $locale),
            $daysBefore === 1 => __('wishio.holiday.tomorrow', $replace, $locale),
            default           => trans_choice('wishio.holiday.soon', $daysBefore, $replace, $locale),
        };

        $people = $occasion->audience()->count();

        return [
            'title' => $title,
            'body'  => trim(trans_choice('wishio.holiday.people', $people, ['count' => $people], $locale)),
            'cta'   => __('wishio.push.cta', [], $locale),
        ];
    }
}
