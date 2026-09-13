<?php

return [
    'occasions' => [
        'birthday'    => 'Birthday',
        'name_day'    => 'Name day',
        'anniversary' => 'Anniversary',
        'holiday'     => 'Holiday',
        'custom'      => 'Custom occasion',
    ],
    'digest' => [
        'subject'     => '{1} One occasion coming up|[2,*] :count occasions coming up',
        'greeting'    => 'Hi :name',
        'intro'       => "Here's what's coming in the next 30 days.",
        'today'       => 'today',
        'tomorrow'    => 'tomorrow',
        'in_days'     => '{1} in one day|[2,*] in :count days',
        'cta'         => 'Open Wishio',
        'unsubscribe' => 'Unsubscribe from these emails',
        'footer'      => "You're receiving this because you enabled the weekly digest in Wishio.",
    ],

    'holiday' => [
        'today'    => 'Today is :holiday 🎉',
        'tomorrow' => 'Tomorrow is :holiday',
        'soon'     => '{1} :holiday is in one day|[2,*] :holiday is in :count days',
        'people'   => '{0} |{1} One person on your list|[2,*] :count people on your list',
    ],

    'push' => [
        'today'    => ':name has a :occasion today 🎂',
        'tomorrow' => ':name has a :occasion tomorrow',
        'soon'     => '{1} :name has a :occasion in one day|[2,*] :name has a :occasion in :count days',
        'plan'     => '{1} :name has a :occasion in one day. Shall we find a gift?|[2,*] :name has a :occasion in :count days. Shall we find a gift?',
        'cta'      => 'Find a gift',
    ],

    'reminder' => [
        'days_before' => '{1} :name has a :occasion tomorrow|[2,*] :name has a :occasion in :count days',
        'today'       => "Today is :name's :occasion",
        'cta_gift'    => 'Find a gift',
    ],

    // „Cine este?” — completări din link care așteaptă alegerea proprietarului (S9.8)
    'submissions' => [
        'one_title'  => ':name filled in your link',
        'one_body'   => 'Tell us who this is, so the birthday reaches the right person.',
        'many_title' => '{1} One submission is waiting for you|[2,*] :count submissions are waiting for you',
        'many_body'  => 'Tell us who they are, so the birthdays reach the right people.',
    ],
];
