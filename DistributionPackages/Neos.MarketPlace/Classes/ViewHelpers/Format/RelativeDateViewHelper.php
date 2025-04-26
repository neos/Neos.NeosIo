<?php
declare(strict_types=1);

namespace Neos\MarketPlace\ViewHelpers\Format;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Utility\Now;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a DateTime formatted relative to the current date
 */
class RelativeDateViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('date', \DateTimeInterface::class, 'the URI that will be put in the href attribute of the rendered link tag');
        $this->registerArgument('compact', 'string', 'Open the linked document in new Tab', false, false);
    }

    /**
     * Renders a DateTime formatted relative to the current date.
     * Shows the time if the date is the current date.
     * Shows the month and date if the date is the current year.
     * Shows the year/month/date if the date is not the current year.
     *
     * @return string an <img...> html tag
     * @throws \InvalidArgumentException
     */
    public function render(): string
    {
        $date = $this->arguments['date'];
        $compact = $this->arguments['compact'];
        if ($date === null) {
            $date = $this->renderChildren();
        }
        if (!$date instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('No valid date given,', 1459411176);
        }
        $now = new Now();
        // Same day of same year
        if ($date->format('Y z') === $now->format('Y z')) {
            $hours = $date->diff($now)->h;
            if ($hours > 1) {
                return $compact ? $hours . ' hours ago' : 'Last activity ' .  $hours . ' hours ago';
            }

            return $compact ? 'One hour ago' : 'Last activity one hour ago';
        }
        $days = $now->diff($date)->format('%a');
        if ($days < 30) {
            if ($days > 1) {
                return $compact ? $days . ' days ago' : 'Last activity ' .  $days . ' days ago';
            }

            return $compact ? 'Yesterday' : 'Last activity yesterday';
        }

        return $compact ? $date->format('d M Y') : 'Last activity on ' . $date->format('d F Y');
    }
}
