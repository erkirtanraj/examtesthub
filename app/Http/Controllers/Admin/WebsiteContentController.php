<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteContentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/WebsiteContent', [
            'items' => WebsiteContent::query()->orderBy('type')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['type'] === 'page'
            ? Str::slug($data['slug'] ?: $data['title'])
            : null;

        WebsiteContent::create($data);
        $this->audit($request, 'website_content.created', $data);

        return back()->with('success', 'Website content created.');
    }

    public function update(Request $request, WebsiteContent $websiteContent): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['type'] === 'page'
            ? Str::slug($data['slug'] ?: $data['title'])
            : null;

        $websiteContent->update($data);
        $this->audit($request, 'website_content.updated', ['id' => $websiteContent->id, 'type' => $data['type']]);

        return back()->with('success', 'Website content updated.');
    }

    public function destroy(Request $request, WebsiteContent $websiteContent): RedirectResponse
    {
        $id = $websiteContent->id;
        $websiteContent->delete();
        $this->audit($request, 'website_content.deleted', ['id' => $id]);

        return back()->with('success', 'Website content deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:home,menu,footer,contact,legal,page'],
            'key' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'content' => ['required', 'array'],
            'is_published' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        return [...$data, 'sort_order' => $data['sort_order'] ?? 0];
    }

    private function audit(Request $request, string $action, array $metadata): void
    {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500, ''),
        ]);
    }
}
