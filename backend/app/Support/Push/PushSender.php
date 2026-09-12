<?php

namespace App\Support\Push;

interface PushSender
{
    /**
     * @param  list<PushMessage>  $messages
     * @return array<string, string>  token => motivul eșecului (gol dacă toate au reușit)
     */
    public function send(array $messages): array;
}
