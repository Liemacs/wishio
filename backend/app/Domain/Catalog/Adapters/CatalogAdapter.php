<?php

namespace App\Domain\Catalog\Adapters;

use App\Domain\Catalog\DTOs\CatalogItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sursa de produse.
 *
 * Abstracția rămâne chiar dacă azi avem o singură implementare: ea e ce ne
 * permite să schimbăm furnizorul fără să atingem motorul de recomandări.
 * Vezi docs/02 § R4 — dependența de un singur partener e un risc asumat.
 */
interface CatalogAdapter
{
    /** @return Collection<int, CatalogItem> */
    public function fetch(?CarbonImmutable $since = null): Collection;

    public function supports(string $countryCode): bool;
}
