<?php

/** Translation of lang/ro/legal.php. Any change to functionality that touches
 *  personal data requires a change here too. */

return [
    'updated'  => 'Last updated: September 13, 2026',
    'operator' => 'Data controller',

    'privacy' => [
        'title' => 'Privacy Policy',
        'intro' => 'Wishio helps you remember the occasions that matter for the people close to you. To do that, it processes some personal data — yours and that of the people you add. Below is exactly what, why and for how long.',

        'sections' => [
            [
                'title' => 'What we collect about you',
                'items' => [
                    'Your name and email address, so you have an account.',
                    'Language, time zone and country, so the app works correctly.',
                    'Your birthday and interests, if you fill them in.',
                    'Notification preferences and a device identifier, so we can send reminders.',
                    'Which shop offers you open from the app and when, so we know whether suggestions are useful.',
                ],
            ],
            [
                'title' => 'What we collect about the people you add',
                'items' => [
                    'Their name and, when present, their birthday — from your phone contacts, only for the contacts you select, or entered manually by you.',
                    'Relationship, gender, interests, budget and your notes about them. Notes are encrypted and never leave the server.',
                    'Gift ideas and the history of gifts you gave, if you record them.',
                    'What other people send you through your public link: name, birthday, interests and a short message.',
                ],
            ],
            [
                'title' => 'What we do NOT collect',
                'items' => [
                    'We do not read or store phone numbers from your contacts.',
                    'We do not read photos, emails or addresses from your contacts.',
                    'We do not upload your address book — only the contacts you explicitly select.',
                    'We do not show your data to other users and we do not build shared profiles.',
                ],
            ],
            [
                'title' => 'Artificial intelligence',
                'items' => [
                    'If you agree, we send an external AI provider: approximate age, gender, relationship, occasion, budget, interests and things to avoid (picked from the list in the app), and gifts from our catalog you have already given.',
                    'We NEVER send names, email addresses, phone numbers, your notes or any other text you wrote.',
                    'You can decline. The app works fully without this — you get the same suggestions, without explanations.',
                    'You can change your choice at any time in settings.',
                ],
            ],
            [
                'title' => 'Legal basis',
                'items' => [
                    'Performance of a contract, for account data and running the service.',
                    'Legitimate interest, for data about people in your contacts, used solely for your personal benefit.',
                    'Consent, for sending data to the AI provider, for notifications, and for data other people submit through your public link.',
                ],
            ],
            [
                'title' => 'Who we share data with',
                'items' => [
                    'Our hosting provider, to run the service.',
                    'Our notification provider, through Apple or Google, to deliver reminders to your phone. A notification contains the name of the person and the occasion.',
                    'Our email provider, to send the weekly summary, until you unsubscribe.',
                    'Our AI provider, only if you agreed and only the data listed above.',
                    'We do not sell data. We do not share it for advertising.',
                ],
            ],
            [
                'title' => 'How long we keep data',
                'items' => [
                    'Account data, for as long as the account exists.',
                    'Outbound clicks to shops, 24 months, then only in aggregate.',
                    'When you delete your account, everything tied to it is permanently deleted, not deactivated.',
                    'Backups keep data for at most 3 more months after deletion, then they are overwritten.',
                ],
            ],
            [
                'title' => 'Your rights',
                'items' => [
                    'You can download all your data, from within the app.',
                    'You can delete your account and all data, from within the app, without contacting us.',
                    'You can request rectification, restriction of processing, or object to it.',
                    'If you filled in someone else\'s form through their public link, you can delete what you sent using the link you received at the end, without an account.',
                    'You can lodge a complaint with the National Centre for Personal Data Protection.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of Service',
        'intro' => 'By using Wishio, you agree to the following.',

        'sections' => [
            [
                'title' => 'The service',
                'items' => [
                    'Wishio reminds you of occasions and suggests gifts from third-party shops.',
                    'We do not sell products. Purchases happen at the shop, under its terms and responsibility.',
                    'Prices and availability come from shops and may change without notice.',
                ],
            ],
            [
                'title' => 'Your account',
                'items' => [
                    'You are responsible for keeping your access credentials safe.',
                    'Add people for personal use only. Do not use Wishio to collect data about people for any other purpose.',
                    'Do not enter sensitive data about other people: health, beliefs, orientation, political affiliation.',
                ],
            ],
            [
                'title' => 'Suggestions',
                'items' => [
                    'Suggestions are indicative. The final choice is yours.',
                    'Sponsored products are marked as such.',
                ],
            ],
            [
                'title' => 'Termination',
                'items' => [
                    'You can delete your account at any time, from within the app.',
                    'We may suspend an account used abusively or unlawfully.',
                ],
            ],
        ],
    ],

    'support' => [
        'title'    => 'Help and contact',
        'intro'    => 'Have a question, found a mistake or want to tell us what is missing? Write to us at the address below. We read every message.',
        'sections' => [
            [
                'title' => 'Frequently asked questions',
                'items' => [
                    'I am not getting reminders. Check in your phone settings that Wishio may send notifications, then in the app: My profile → Notifications.',
                    'A name day is wrong. You can reject it or correct its date, and Wishio keeps your choice.',
                    'I want to add someone without my contacts. Go to People → Add person. The app works fully without access to your contacts.',
                    'How do I delete my account? My profile → Account → Delete account. The steps and what is kept are on the account deletion page.',
                ],
            ],
            [
                'title' => 'Your data',
                'items' => [
                    'What we collect and why is written in the privacy policy.',
                    'You can download all your data at any time from My profile → Account.',
                ],
            ],
        ],
    ],

    'delete-account' => [
        'title'    => 'Deleting your Wishio account',
        'intro'    => 'You can delete your account at any time, with everything in it. Deletion is permanent: the data cannot be recovered.',
        'sections' => [
            [
                'title' => 'In the app',
                'items' => [
                    'Open Wishio and go to My profile → Account.',
                    'Tap “Delete account”.',
                    'Confirm by typing the email address of the account and your password.',
                ],
            ],
            [
                'title' => 'Without the app',
                'items' => [
                    'Email us from the address of your account, at the address below, and ask for deletion.',
                    'We delete the account within 30 days and confirm by email.',
                ],
            ],
            [
                'title' => 'What is deleted',
                'items' => [
                    'Your account: name, email, password and settings.',
                    'The people you added, with their occasions, notes, interests, ideas and gift history.',
                    'Your public link, your wishlist and what others sent you through the link.',
                    'Connected devices, reminders, recommendations and clicks to shops.',
                ],
            ],
            [
                'title' => 'What is kept',
                'items' => [
                    'Backups keep the data for at most 3 more months, then they are overwritten.',
                    'Nothing else: data is deleted, not deactivated.',
                ],
            ],
        ],
    ],

];
