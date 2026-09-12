<?php

namespace App\Support\Push;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushSender implements PushSender
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /** Expo acceptă maximum 100 de mesaje per cerere. */
    private const CHUNK = 100;

    public function send(array $messages): array
    {
        $failures = [];

        foreach (array_chunk($messages, self::CHUNK) as $chunk) {
            $payload = array_map(fn (PushMessage $message) => [
                'to'    => $message->token,
                'title' => $message->title,
                'body'  => $message->body,
                'data'  => $message->data,
                'sound' => 'default',
            ], $chunk);

            try {
                $response = Http::timeout(20)->acceptJson()->post(self::ENDPOINT, $payload);

                foreach ($response->json('data', []) as $index => $ticket) {
                    if (($ticket['status'] ?? null) !== 'ok') {
                        $failures[$chunk[$index]->token] = $ticket['details']['error']
                            ?? ($ticket['message'] ?? 'unknown');
                    }
                }
            } catch (\Throwable $exception) {
                // Nu logăm conținutul notificării: conține nume de persoane.
                Log::warning('Push batch failed', ['count' => count($chunk), 'error' => $exception->getMessage()]);

                foreach ($chunk as $message) {
                    $failures[$message->token] = 'transport';
                }
            }
        }

        return $failures;
    }
}
