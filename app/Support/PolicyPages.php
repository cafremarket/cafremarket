<?php

namespace App\Support;

use App\Models\Page;

/**
 * Central registry of system-wide policy / legal pages.
 *
 * Content is stored in the existing `pages` table and served to Web + Apps
 * via `GET /page/{slug}` and `GET /api/page/{slug}`.
 */
class PolicyPages
{
    /**
     * Placeholder markers that mobile apps treat as "use local fallback".
     */
    public const PLACEHOLDER_MARKERS = [
        'add your own privacy policy',
        'add your own terms',
        'add your own terms and condition',
        'add your own about us',
        'modify this page from admin panel',
        'admin panel >> utilities >> pages',
        'lorem ipsum',
        'coming soon',
        'placeholder',
    ];

    /**
     * Policy page definitions keyed by slug.
     *
     * @return array<string, array{title: string, channels: list<string>, description: string, default_visibility: int, default_position: string}>
     */
    public static function definitions(): array
    {
        return [
            Page::PAGE_PRIVACY_POLICY => [
                'title' => 'Privacy Policy',
                'channels' => ['Web Storefront', 'Customer App', 'Vendor App', 'Delivery App'],
                'description' => 'Shown in account/legal screens across all apps and the website footer.',
                'default_visibility' => Page::VISIBILITY_PUBLIC,
                'default_position' => 'copyright_area',
            ],
            Page::PAGE_TNC_FOR_CUSTOMER => [
                'title' => 'Terms of Use (Customer)',
                'channels' => ['Web Storefront', 'Customer App'],
                'description' => 'Customer registration, checkout agreements, and in-app Terms screens.',
                'default_visibility' => Page::VISIBILITY_PUBLIC,
                'default_position' => 'copyright_area',
            ],
            Page::PAGE_TNC_FOR_MERCHANT => [
                'title' => 'Terms of Use (Merchant)',
                'channels' => ['Web (Merchants)', 'Vendor App', 'Delivery App'],
                'description' => 'Vendor and delivery partner agreements (merchant visibility).',
                'default_visibility' => Page::VISIBILITY_MERCHANT,
                'default_position' => 'copyright_area',
            ],
            Page::PAGE_RETURN_AND_REFUND => [
                'title' => 'Return & Refund Policy',
                'channels' => ['Web Storefront', 'Customer App'],
                'description' => 'Returns, refunds, and dispute guidance on web and customer app.',
                'default_visibility' => Page::VISIBILITY_PUBLIC,
                'default_position' => 'footer_1st_column',
            ],
            Page::PAGE_ABOUT_US => [
                'title' => 'About Us',
                'channels' => ['Web Storefront', 'Customer App'],
                'description' => 'Company / marketplace about page used on web and customer app.',
                'default_visibility' => Page::VISIBILITY_PUBLIC,
                'default_position' => 'copyright_area',
            ],
            Page::PAGE_CONTACT_US => [
                'title' => 'Contact Us',
                'channels' => ['Web Storefront'],
                'description' => 'Contact details page on the storefront (contact form attaches automatically).',
                'default_visibility' => Page::VISIBILITY_PUBLIC,
                'default_position' => 'footer_3rd_column',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::definitions());
    }

    public static function isPolicySlug(string $slug): bool
    {
        return isset(self::definitions()[$slug]);
    }

    /**
     * True when CMS content is empty or still a seed placeholder (apps will fall back).
     */
    public static function isPlaceholder(?string $content): bool
    {
        $text = trim((string) $content);
        if ($text === '') {
            return true;
        }

        $lower = mb_strtolower($text);
        foreach (self::PLACEHOLDER_MARKERS as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

        return mb_strlen($plain) < 80;
    }

    /**
     * Status label for admin UI.
     */
    public static function contentStatus(?Page $page): string
    {
        if (! $page) {
            return 'missing';
        }

        if (is_null($page->published_at)) {
            return 'draft';
        }

        if (self::isPlaceholder($page->content)) {
            return 'placeholder';
        }

        return 'published';
    }

    /**
     * CMS content if usable; otherwise default fallback HTML for the slug.
     */
    public static function resolveContent(string $slug, ?string $content = null): string
    {
        return DefaultPolicyContent::resolve($slug, $content);
    }

    /**
     * Apply default fallback HTML onto a page model (in memory; caller must save).
     */
    public static function applyDefaultContent(Page $page): Page
    {
        $html = DefaultPolicyContent::forSlug($page->slug);
        if ($html !== '') {
            $page->content = $html;
            if (is_null($page->published_at)) {
                $page->published_at = now();
            }
        }

        return $page;
    }

    /**
     * Clear web + API caches so all channels pick up new policy content.
     */
    public static function flushCaches(?string $slug = null): void
    {
        \Illuminate\Support\Facades\Cache::forget('cached_pages');

        // Page API responses use the catalog version bucket (bucket name "config" still versions via catalog).
        \App\Services\Cache\CatalogCache::bumpCatalog();
    }
}
