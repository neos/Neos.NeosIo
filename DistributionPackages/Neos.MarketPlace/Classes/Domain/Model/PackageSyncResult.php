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
 * The outcome of a single package synchronization.
 *
 * @api
 */
enum PackageSyncResult: string
{
    /**
     * The package was not checked at all, because it did not require an update.
     */
    case NotRequired = 'notRequired';

    /**
     * The package was checked, but the upstream data did not change.
     */
    case Unchanged = 'unchanged';

    /**
     * The package was updated.
     */
    case Changed = 'changed';

    /**
     * Whether the package was not processed at all.
     */
    public function isSkipped(): bool
    {
        return $this === self::NotRequired;
    }
}
