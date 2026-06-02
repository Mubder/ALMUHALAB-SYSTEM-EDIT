<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PageSectionController extends Controller
{
    public function index()
    {
        $pages = Page::with('sections')->orderBy('title')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug'  => 'required|string|max:255|unique:pages,slug',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $page = Page::create($data + ['is_published' => true]);

        return redirect()->route('admin.pages.edit', $page)
            ->with('success', 'Page created. Now add sections.');
    }

    public function edit(Page $page)
    {
        $page->load('sections');
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug'  => 'required|string|max:255|unique:pages,slug,'.$page->id,
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'boolean',
        ]);

        $page->update($data);

        return redirect()->route('admin.pages.edit', $page)
            ->with('success', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted.');
    }

    // ── Section CRUD ─────────────────────────────────────────

    public function sectionStore(Request $request, Page $page)
    {
        $data = $request->validate([
            'type'  => 'required|string|in:hero,about,partners,contact,text,image_text,cards,html',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|json',
        ]);

        $maxOrder = $page->sections()->max('sort_order') ?? 0;

        $section = $page->sections()->create([
            'type'       => $data['type'],
            'title'      => $data['title'],
            'content'    => json_decode($data['content'] ?? '{}', true),
            'sort_order' => $maxOrder + 1,
            'is_visible' => true,
        ]);

        return redirect()->route('admin.pages.edit', $page)
            ->with('success', 'Section added.');
    }

    public function sectionEdit(Page $page, PageSection $section)
    {
        return view('admin.pages.section-edit', compact('page', 'section'));
    }

    public function sectionUpdate(Request $request, Page $page, PageSection $section)
    {
        $data = $request->validate([
            'type'  => 'required|string|in:hero,about,partners,contact,text,image_text,cards,html',
            'title' => 'nullable|string|max:255',
            'is_visible' => 'boolean',
        ]);

        $content = $section->content ?? [];

        // Handle partners as nested array with file uploads
        if ($request->has('partners') && is_array($request->partners)) {
            $partners = [];
            foreach ($request->partners as $i => $partner) {
                if (!is_array($partner)) continue;

                $entry = [
                    'name'        => $partner['name'] ?? '',
                    'description' => $partner['description'] ?? '',
                    'url'         => $partner['url'] ?? '',
                ];

                // Keep existing logo unless removed
                $existingLogo = $content['partners'][$i]['logo'] ?? '';
                if (!empty($partner['remove_logo']) || !empty($existingLogo) && !empty($partner['logo'])) {
                    $entry['logo'] = '';
                } elseif (!empty($existingLogo)) {
                    $entry['logo'] = $existingLogo;
                }

                // Handle logo file upload
                if ($request->hasFile("partners.$i.logo")) {
                    $entry['logo'] = $request->file("partners.$i.logo")->store('pages/partners', 'public');
                }

                $partners[] = $entry;
            }
            $content['partners'] = $partners;
        }

        // Handle flat fields (excluding partners which is already processed)
        foreach ($request->except(['_token', '_method', 'type', 'title', 'is_visible', 'partners']) as $key => $value) {
            if ($request->hasFile($key)) {
                $content[$key] = $request->file($key)->store('pages', 'public');
            } elseif (!is_array($value)) {
                if ($key === 'remove_logo') {
                    $content['logo'] = '';
                } else {
                    $content[$key] = $value;
                }
            }
        }

        $section->update([
            'type'       => $data['type'],
            'title'      => $data['title'],
            'content'    => $content,
            'is_visible' => $request->boolean('is_visible', true),
        ]);

        return redirect()->route('admin.pages.edit', $page)
            ->with('success', 'Section updated.');
    }

    public function sectionDestroy(Page $page, PageSection $section)
    {
        $section->delete();
        return redirect()->route('admin.pages.edit', $page)
            ->with('success', 'Section deleted.');
    }

    public function sectionReorder(Request $request, Page $page)
    {
        $request->validate(['sections' => 'required|array']);
        foreach ($request->sections as $i => $id) {
            PageSection::where('id', $id)->where('page_id', $page->id)->update(['sort_order' => $i]);
        }
        return response()->json(['ok' => true]);
    }
}
