<?php

namespace App\Domain\Metrics\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Numără o vizită pe pagina principală sau un click spre un magazin, pe zi și
 * pe sursă.
 *
 * Doar contoare: niciun IP, niciun cookie, nimic ce ar lega două vizite de
 * același om, deci nimic de cerut consimțământ. Sursa vine din `?src=` și se
 * curăță, ca un link scris diferit să nu umple tabelul cu variante.
 */
class RecordChannelEvent
{
    public const EVENTS = ['view', 'ios', 'android'];

    public function __invoke(string $event, ?string $source): void
    {
        if (! in_array($event, self::EVENTS, true)) {
            return;
        }

        $now = now();

        DB::table('channel_stats')->upsert(
            [[
                'day'        => $now->copy()->setTimezone('Europe/Chisinau')->toDateString(),
                'source'     => self::normalize($source),
                'event'      => $event,
                'total'      => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['day', 'source', 'event'],
            ['total' => DB::raw('total + 1'), 'updated_at' => $now],
        );
    }

    public static function normalize(?string $source): string
    {
        $clean = preg_replace('/[^a-z0-9_-]/', '', mb_strtolower(trim((string) $source))) ?? '';

        return substr($clean, 0, 64);
    }
}
