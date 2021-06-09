<?php
namespace Neos\NeosIo\ViewHelpers\Link;

use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;

class ExternalViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerArgument('uri', 'string', 'the URI that will be put in the href attribute of the rendered link tag', true);
        $this->registerArgument('defaultScheme', 'string', 'Open the linked document in new Tab', false, 'http');
        $this->registerArgument('openInNewTab', 'boolean', 'Open the linked document in new Tab', false, false);
        $this->registerArgument('noLinkWhen', 'boolean', 'Only renders the link if condition evaluates to FALSE', false, false);
    }

    /**
     * @return string Rendered link
     * @api
     */
    public function render()
    {
        $uri = $this->arguments['uri'];
        $defaultScheme = $this->arguments['defaultScheme'];
        if (!$this->arguments['noLinkWhen'] && !empty(trim($uri))) {
            $scheme = parse_url($uri, PHP_URL_SCHEME);
            $host = parse_url($uri, PHP_URL_HOST);
            if ($scheme === null && $defaultScheme !== '' && !empty($host)) {
                $uri = $defaultScheme . '://' . $uri;
            }
            $this->tag->addAttribute('href', $uri);
            if ($this->arguments['openInNewTab']) {
                $this->tag->addAttribute('target', '_blank');
                $this->tag->addAttribute('rel', 'noopener');
            }
        }

        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
