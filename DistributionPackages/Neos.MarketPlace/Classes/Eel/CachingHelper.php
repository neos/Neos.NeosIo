<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\Arrays;
use Neos\Utility\Unicode\Functions;
use Neos\Neos\Fusion\Helper\CachingHelper as TypoScriptCachingHelper;

/**
 * Caching helper to make cache tag generation easier.
 */
class CachingHelper extends TypoScriptCachingHelper
{
    /**
     * @param ActionRequest $request
     * @param string $argumentName
     * @return string|null
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
     * @throws \JsonException
     */
    public function paginationCacheKey(ActionRequest $request, string $argumentName = '--browse'): ?string
    {
        $request = $request->getParentRequest() ?: $request;
        if (!$request->hasArgument($argumentName)) {
            return null;
        }
        $arguments = $request->getArgument($argumentName);
        $arguments = array_map('trim', array_filter($arguments));
        Arrays::sortKeysRecursively($arguments);
        return md5(json_encode($arguments, JSON_THROW_ON_ERROR));
    }

    /**
     * @param string $query
     * @return string
     */
    public function queryCacheKey(string $query): string
    {
        return md5(Functions::strtolower(trim($query)));
    }
}
