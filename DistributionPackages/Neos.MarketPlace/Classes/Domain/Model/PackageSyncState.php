<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Model;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The locally cached state of a package that was synchronized from Packagist.
 *
 * The state is stored in the package sync cache and allows to detect changes without
 * touching the content repository at all.
 *
 * @api
 */
final readonly class PackageSyncState
{
    public function __construct(
        public int $syncedAt,
        public ?string $versionsHash
    ) {
    }

    public static function fromCacheValue(mixed $cacheValue): ?self
    {
        if (!is_string($cacheValue) || $cacheValue === '') {
            return null;
        }
        $decoded = json_decode($cacheValue, true);
        if (!is_array($decoded) || !isset($decoded['syncedAt'])) {
            // Legacy cache values only contained the timestamp of the last sync
            return new self((int)$cacheValue, null);
        }
        return new self(
            (int)$decoded['syncedAt'],
            isset($decoded['versionsHash']) ? (string)$decoded['versionsHash'] : null
        );
    }

    public function toCacheValue(): string
    {
        return (string)json_encode([
            'syncedAt' => $this->syncedAt,
            'versionsHash' => $this->versionsHash
        ]);
    }
}
