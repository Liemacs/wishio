<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Scheletul slim din Laravel 12 nu include trait-ul. Fara el, $this->authorize()
    // nu exista si autorizarea trece tacut — exact scenariul pe care regula 3
    // din CLAUDE.md il interzice.
    use AuthorizesRequests;
}
