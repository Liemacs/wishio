<?php

namespace App\Support\Push;

/** Implicit în dezvoltare și în teste: nu trimite nimic, nu eșuează nimic. */
class NullPushSender implements PushSender
{
    /** @var list<PushMessage> */
    public array $sent = [];

    public function send(array $messages): array
    {
        $this->sent = [...$this->sent, ...$messages];

        return [];
    }
}
