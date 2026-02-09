<?php
declare(strict_types=1);

namespace Neos\NeosIo\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;

/**
 * An Eel helper to generate Gravatar URLs and img tags
 */
class GravatarHelper implements ProtectedContextAwareInterface
{
    /**
     * Generate a Gravatar URL for the given email address
     *
     * @param string $email The email address
     * @param int|null $size The size in pixels (default: null)
     * @param string|null $default The default image type or URL (default: null)
     * @return string The Gravatar URL
     */
    public function url(string $email, ?int $size = null, ?string $default = null): string
    {
        $sanitizedEmail = strtolower(trim($email));
        $gravatarUri = 'https://www.gravatar.com/avatar/' . md5($sanitizedEmail);
        $uriParts = [];

        if ($default) {
            $uriParts[] = 'd=' . urlencode($default);
        }

        if ($size) {
            $uriParts[] = 's=' . $size;
        }

        if (count($uriParts)) {
            $gravatarUri .= '?' . implode('&', $uriParts);
        }

        return $gravatarUri;
    }

    /**
     * Generate a complete img tag for a Gravatar
     *
     * @param string $email The email address
     * @param int|null $size The size in pixels (default: null)
     * @param string|null $default The default image type or URL (default: null)
     * @param string|null $alt The alt text (default: 'Gravatar')
     * @param string|null $class CSS class(es) to add
     * @param int|null $width The width attribute (defaults to size if not specified)
     * @param int|null $height The height attribute (defaults to size if not specified)
     * @return string The complete img tag
     */
    public function img(
        string $email,
        ?int $size = null,
        ?string $default = null,
        ?string $alt = 'Gravatar',
        ?string $class = null,
        ?int $width = null,
        ?int $height = null
    ): string {
        $gravatarUrl = $this->url($email, $size, $default);

        $attributes = [];
        $attributes[] = 'src="' . htmlspecialchars($gravatarUrl) . '"';
        $attributes[] = 'alt="' . htmlspecialchars($alt ?? 'Gravatar') . '"';

        if ($class) {
            $attributes[] = 'class="' . htmlspecialchars($class) . '"';
        }

        // Use explicit width/height if provided, otherwise use size for both
        $finalWidth = $width ?? $size;
        $finalHeight = $height ?? $size;

        if ($finalWidth) {
            $attributes[] = 'width="' . $finalWidth . '"';
        }

        if ($finalHeight) {
            $attributes[] = 'height="' . $finalHeight . '"';
        }

        return '<img ' . implode(' ', $attributes) . ' />';
    }

    /**
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return in_array($methodName, ['url', 'img'], true);
    }
}
