<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Tests\Unit\Support;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Symfony\Component\Serializer\Serializer;

/**
 * Serializer that is able to denormalize the property types used by the marketplace.
 */
final class TestSerializer extends Serializer
{
    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if ($type === 'DateTime' && is_string($data)) {
            return new \DateTimeImmutable($data);
        }
        return $data;
    }
}
