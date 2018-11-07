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
        $this->registerArgument('openInNewTab', 'boolean', 'Open the linked document in new Tab', false, false);
        $this->registerArgument('noLinkWhen', 'boolean', 'Only renders the link if condition evaluates to FALSE', false, false);
    }

    /**
     * @param string $uri the URI that will be put in the href attribute of the rendered link tag
     * @param string $defaultScheme scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already
     *
     * @return string Rendered link
     * @api
     */
    public function render($uri, $defaultScheme = 'http')
    {
        if (!$this->arguments['noLinkWhen'] && !empty(trim($uri))) {
            $scheme = parse_url($uri, PHP_URL_SCHEME);
            if ($scheme === null && $defaultScheme !== '') {
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
