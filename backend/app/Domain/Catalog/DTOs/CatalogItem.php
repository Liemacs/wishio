<?php

namespace App\Domain\Catalog\DTOs;

/** Un produs, așa cum vine de la o sursă externă, înainte de normalizare. */
readonly class CatalogItem
{
    public function __construct(
        public string $merchantCode,
        public string $externalId,
        public string $title,
        public float $price,
        public string $deeplink,
        public ?string $brand = null,
        public ?string $description = null,
        public ?string $categoryCode = null,
        public ?string $imageUrl = null,
        public ?float $oldPrice = null,
        public string $currency = 'MDL',
        public bool $inStock = true,
        /** @var list<string> coduri din taxonomia de interese */
        public array $interests = [],
    ) {}
}
