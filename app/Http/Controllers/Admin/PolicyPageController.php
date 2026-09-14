<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FlatformUserAccessRequest;
use App\Http\Requests\Validations\UpdatePolicyPageRequest;
use App\Models\Page;
use App\Models\Role;
use App\Support\DefaultPolicyContent;
use App\Support\PolicyPages;
use Illuminate\Support\Facades\Auth;

class PolicyPageController extends Controller
{
    private string $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = trans('app.model.policy_page');
    }

    /**
     * Dashboard of all system policy pages and where they appear.
     */
    public function index(FlatformUserAccessRequest $request)
    {
        $definitions = PolicyPages::definitions();
        $pages = Page::with('author')
            ->whereIn('slug', array_keys($definitions))
            ->get()
            ->keyBy('slug');

        $policyPages = [];
        foreach ($definitions as $slug => $meta) {
            $page = $pages->get($slug);
            $policyPages[] = [
                'slug' => $slug,
                'meta' => $meta,
                'page' => $page,
                'status' => PolicyPages::contentStatus($page),
                'web_url' => $page ? route('page.open', $page->slug) : null,
                'api_path' => 'api/page/'.$slug,
            ];
        }

        return view('admin.policy_page.index', compact('policyPages'));
    }

    /**
     * Full-page editor for a policy page (better UX than modal for long legal HTML).
     */
    public function edit(FlatformUserAccessRequest $request, string $slug)
    {
        abort_unless(PolicyPages::isPolicySlug($slug), 404);

        $meta = PolicyPages::definitions()[$slug];
        $page = Page::where('slug', $slug)->first();

        if (! $page) {
            $page = $this->ensurePage($slug, $meta);
        }

        $positions = ListHelper::page_positions();
        $status = PolicyPages::contentStatus($page);

        return view('admin.policy_page.edit', compact('page', 'meta', 'slug', 'positions', 'status'));
    }

    /**
     * Update policy page content and flush caches for web + apps.
     */
    public function update(UpdatePolicyPageRequest $request, string $slug)
    {
        abort_unless(PolicyPages::isPolicySlug($slug), 404);

        $meta = PolicyPages::definitions()[$slug];
        $page = Page::where('slug', $slug)->first() ?: $this->ensurePage($slug, $meta);

        $data = $request->only(['title', 'content', 'position', 'visibility', 'published_at']);

        if ($request->boolean('save_as_draft')) {
            $data['published_at'] = null;
        } elseif (! $request->filled('published_at') && is_null($page->published_at)) {
            $data['published_at'] = now();
        }

        $page->update($data);

        if ($request->input('delete_image')) {
            foreach ((array) $request->input('delete_image') as $type => $value) {
                if ($value) {
                    $page->deleteImageTypeOf($type);
                }
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $type => $file) {
                if ($file) {
                    $page->saveImage($file, $type);
                }
            }
        }

        PolicyPages::flushCaches($slug);

        return redirect()
            ->route('admin.utility.policyPage.index')
            ->with('success', trans('messages.updated', ['model' => $this->model]));
    }

    /**
     * Replace CMS content with built-in default policy HTML (fallback template).
     */
    public function applyDefaults(FlatformUserAccessRequest $request, string $slug)
    {
        abort_unless(PolicyPages::isPolicySlug($slug), 404);

        $meta = PolicyPages::definitions()[$slug];
        $page = Page::where('slug', $slug)->first() ?: $this->ensurePage($slug, $meta);

        PolicyPages::applyDefaultContent($page);
        $page->save();

        PolicyPages::flushCaches($slug);

        return redirect()
            ->route('admin.utility.policyPage.edit', $slug)
            ->with('success', trans('messages.policy_default_applied', ['model' => $this->model]));
    }

    /**
     * Apply default fallback content to every policy page that is missing or still placeholder.
     */
    public function applyAllDefaults(FlatformUserAccessRequest $request)
    {
        $updated = 0;

        foreach (PolicyPages::definitions() as $slug => $meta) {
            $page = Page::where('slug', $slug)->first() ?: $this->ensurePage($slug, $meta);

            if (PolicyPages::isPlaceholder($page->content)) {
                PolicyPages::applyDefaultContent($page);
                $page->save();
                $updated++;
            }
        }

        PolicyPages::flushCaches();

        return redirect()
            ->route('admin.utility.policyPage.index')
            ->with('success', trans('messages.policy_defaults_applied', ['count' => $updated]));
    }

    /**
     * Create a missing policy page row from the registry defaults.
     */
    protected function ensurePage(string $slug, array $meta): Page
    {
        $page = Page::create([
            'author_id' => Auth::id() ?: Role::SUPER_ADMIN,
            'title' => $meta['title'],
            'slug' => $slug,
            'content' => DefaultPolicyContent::forSlug($slug),
            'visibility' => $meta['default_visibility'],
            'position' => $meta['default_position'],
            'published_at' => now(),
        ]);

        return $page;
    }
}
