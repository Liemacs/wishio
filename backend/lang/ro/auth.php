<?php

return [
    // Același mesaj pentru email inexistent și parolă greșită: altfel
    // formularul de login ar spune cine are cont la noi.
    'failed'   => 'Emailul sau parola nu sunt corecte.',
    'password' => 'Parola nu este corectă.',

    // Formele de plural le alege regula limbii, nu intervale explicite:
    // „de” apare de la 20 în sus, dar nu când ultimele două cifre sunt
    // 01–19 („105 secunde”) — un interval [20,*] ar greși acolo.
    'throttle' => 'Prea multe încercări. Încearcă din nou peste o secundă.|Prea multe încercări. Încearcă din nou peste :seconds secunde.|Prea multe încercări. Încearcă din nou peste :seconds de secunde.',
];
