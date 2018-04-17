<?php
namespace Neos\NeosIo\Fusion;

use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * An inline icon replacer object.
 */
class IconReplacerImplementation extends AbstractFusionObject
{
    /**
     * The string where replacing is done.
     *
     * @return mixed
     */
    public function getHaystack()
    {
        return $this->fusionValue('haystack');
    }

    /**
     * The tag name with which the searched string is replaced
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->fusionValue('tagName');
    }

    /**
     * The class stub which is set on the replaced tags, appended with the preg match
     *
     * @return string
     */
    public function getClassStub()
    {
        return $this->fusionValue('classStub');
    }

    /**
     * Replaces all occurences of [icon-xyz] with a tag generated from the input values.
     *
     * @return string The original text with icon placeholders replaced by tags.
     */
    public function evaluate()
    {
        $tagName = $this->getTagName();
        $classStub = $this->getClassStub();
        return preg_replace_callback(
            "/\[icon-(?<replace>[\w-]*)\]/i",
            function($match) use ($tagName, $classStub) {
                return '<'.$tagName.' class="'.$classStub.strtolower($match['replace']).'"></'.$tagName.'>';
            },
            $this->getHaystack()
        );
    }
}
