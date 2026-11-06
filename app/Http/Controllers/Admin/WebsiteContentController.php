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
        $this->ensureDefaultHomepageContent();
        $this->ensureDefaultHomepageSections();

        return Inertia::render('Admin/Settings/WebsiteContent', [
            'items' => WebsiteContent::query()->orderBy('type')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * Import the current public homepage copy once, without overwriting admin edits.
     */
    private function ensureDefaultHomepageContent(): void
    {
        $defaults = [
            'navHome' => ['menu', 'Home', 'होम'],
            'navCourses' => ['menu', 'Courses', 'कोर्स'],
            'navTests' => ['menu', 'Test Series', 'टेस्ट सीरीज़'],
            'navMaterials' => ['menu', 'Study Materials', 'अध्ययन सामग्री'],
            'studentLogin' => ['menu', 'Login', 'लॉगिन'],
            'adminLogin' => ['menu', 'Admin', 'एडमिन'],
            'register' => ['menu', 'Register', 'रजिस्टर करें'],
            'eyebrow' => ['home', 'YOUR EXAM PREPARATION PARTNER', 'आपकी सफलता की तैयारी यहीं से शुरू होती है'],
            'title' => ['home', 'Prepare smarter. Learn faster. Achieve more.', 'सीखें बेहतर। अभ्यास करें। सफलता पाएं।'],
            'description' => ['home', 'Courses, online test series and study materials for competitive exam preparation—all in one place.', 'प्रतियोगी परीक्षाओं की तैयारी के लिए कोर्स, ऑनलाइन टेस्ट सीरीज़ और अध्ययन सामग्री—सब कुछ एक ही जगह।'],
            'explore' => ['home', 'Start preparing', 'तैयारी शुरू करें'],
            'browse' => ['home', 'Explore test series', 'टेस्ट सीरीज़ देखें'],
            'trust' => ['home', 'One focused platform for learning, practice and progress', 'सीखने और अभ्यास के लिए आपका एक भरोसेमंद प्लेटफॉर्म'],
            'sectionEyebrow' => ['home', 'ONE PLATFORM', 'एक ही प्लेटफॉर्म'],
            'sectionTitle' => ['home', 'Everything you need to prepare', 'परीक्षा की तैयारी के लिए जरूरी सब कुछ'],
            'sectionText' => ['home', 'Build a consistent study routine with learning resources and practice tools in one place.', 'अपनी तैयारी को व्यवस्थित रखें और हर कदम पर अपनी प्रगति पर ध्यान दें।'],
            'courses' => ['home', 'Online Courses', 'ऑनलाइन कोर्स'],
            'coursesText' => ['home', 'Learn at your pace with structured sections, chapters and lessons.', 'व्यवस्थित सेक्शन, अध्याय और पाठ के साथ अपनी गति से सीखें।'],
            'tests' => ['home', 'Online Test Series', 'ऑनलाइन टेस्ट सीरीज़'],
            'testsText' => ['home', 'Practice with timed assessments and review your performance.', 'समयबद्ध टेस्ट का अभ्यास करें और अपने प्रदर्शन को समझें।'],
            'materials' => ['home', 'Study Materials', 'अध्ययन सामग्री'],
            'materialsText' => ['home', 'Access notes and PDF resources in one convenient library.', 'नोट्स और PDF संसाधनों को एक ही जगह एक्सेस करें।'],
            'performance' => ['home', 'Performance Insights', 'प्रदर्शन विश्लेषण'],
            'performanceText' => ['home', 'Review your results and keep track of your preparation.', 'अपने परिणाम और तैयारी की प्रगति को ट्रैक करें।'],
            'howEyebrow' => ['home', 'GETTING STARTED', 'कैसे शुरू करें'],
            'howTitle' => ['home', 'Your preparation in three steps', 'तीन आसान कदम'],
            'step1' => ['home', 'Create your account', 'अपना अकाउंट बनाएं'],
            'step1Text' => ['home', 'Register for your student account.', 'स्टूडेंट अकाउंट के लिए रजिस्टर करें।'],
            'step2' => ['home', 'Choose what to study', 'अपना कोर्स या टेस्ट चुनें'],
            'step2Text' => ['home', 'Explore courses or find a test series for your goals.', 'अपनी जरूरत के अनुसार सीखना या अभ्यास करना शुरू करें।'],
            'step3' => ['home', 'Track your progress', 'प्रगति पर नजर रखें'],
            'step3Text' => ['home', 'Review your attempts and learning progress.', 'अपने टेस्ट और सीखने के परिणाम देखें।'],
            'ctaTitle' => ['home', 'Take the next step in your preparation', 'आज ही अपनी तैयारी शुरू करें'],
            'ctaText' => ['home', 'Sign in or create an account to get started with ExamTestHub.', 'लॉगिन करें या नया अकाउंट बनाकर अपनी सीखने की यात्रा शुरू करें।'],
            'createAccount' => ['home', 'Create an account', 'नया अकाउंट बनाएं'],
            'signIn' => ['home', 'Student Login', 'स्टूडेंट लॉगिन'],
            'adminAccess' => ['menu', 'Admin access', 'एडमिन एक्सेस'],
            'footerLine' => ['footer', 'Learn · Practice · Achieve', 'सीखें · अभ्यास करें · सफलता पाएं'],
            'privacy' => ['footer', 'Privacy', 'प्राइवेसी'],
            'terms' => ['footer', 'Terms', 'नियम और शर्तें'],
            'contact' => ['footer', 'Contact', 'संपर्क'],
        ];

        foreach ($defaults as $key => [$type, $english, $hindi]) {
            WebsiteContent::query()->firstOrCreate(
                ['type' => $type, 'key' => $key],
                [
                    'title' => Str::headline($key),
                    'content' => ['en' => $english, 'hi' => $hindi],
                    'is_published' => true,
                    'sort_order' => 0,
                ],
            );
        }
    }


    /**
     * Create editable homepage section records once; never overwrite admin changes.
     */
    private function ensureDefaultHomepageSections(): void
    {
        $sections = [
            ['section.hero', 'Hero / Banner', 'मुख्य बैनर', 'Prepare smarter. Learn faster. Achieve more.', 'सीखें बेहतर। अभ्यास करें। सफलता पाएं।', 'Courses, online test series and study materials for competitive exam preparation—all in one place.', 'प्रतियोगी परीक्षाओं की तैयारी के लिए कोर्स, ऑनलाइन टेस्ट सीरीज़ और अध्ययन सामग्री—सब कुछ एक ही जगह।', 10],
            ['section.features', 'Features', 'मुख्य सुविधाएँ', 'Everything you need to prepare', 'परीक्षा की तैयारी के लिए जरूरी सब कुछ', 'Explore the learning, test and study material features available on ExamTestHub.', 'ExamTestHub पर उपलब्ध कोर्स, टेस्ट और अध्ययन सामग्री की सुविधाएँ देखें।', 20],
            ['section.steps', 'How it works', 'कैसे काम करता है', 'Your preparation in three steps', 'तीन आसान कदम', 'Create an account, choose what to study and track your progress.', 'अकाउंट बनाएं, पढ़ाई चुनें और अपनी प्रगति देखें।', 30],
            ['section.cta', 'Call to action', 'कॉल टू एक्शन', 'Take the next step in your preparation', 'आज ही अपनी तैयारी शुरू करें', 'Sign in or create an account to get started with ExamTestHub.', 'लॉगिन करें या नया अकाउंट बनाकर अपनी सीखने की यात्रा शुरू करें।', 40],
        ];

        foreach ($sections as [$key, $title, $titleHi, $en, $hi, $descEn, $descHi, $order]) {
            WebsiteContent::query()->firstOrCreate(
                ['type' => 'home', 'key' => $key],
                [
                    'title' => $title,
                    'content' => [
                        'en' => ['title' => $en, 'description' => $descEn],
                        'hi' => ['title' => $hi, 'description' => $descHi],
                        'section_type' => str_replace('section.', '', $key),
                    ],
                    'is_published' => true,
                    'sort_order' => $order,
                ],
            );
        }
    }


    public function saveLayout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'blocks' => ['required', 'array'],
            'blocks.*.id' => ['required', 'integer', 'exists:website_contents,id'],
            'blocks.*.sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'blocks.*.is_published' => ['required', 'boolean'],
        ]);

        foreach ($data['blocks'] as $block) {
            WebsiteContent::query()
                ->whereKey($block['id'])
                ->where('type', 'home')
                ->where('key', 'like', 'section.%')
                ->update([
                    'sort_order' => $block['sort_order'],
                    'is_published' => $block['is_published'],
                ]);
        }

        $this->audit($request, 'website_content.layout_saved', ['count' => count($data['blocks'])]);

        return back()->with('success', 'Homepage layout saved.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = in_array($data['type'], ['page', 'legal'], true)
            ? Str::slug($data['slug'] ?: $data['title'])
            : null;

        WebsiteContent::create($data);
        $this->audit($request, 'website_content.created', $data);

        return back()->with('success', 'Website content created.');
    }

    public function update(Request $request, WebsiteContent $websiteContent): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = in_array($data['type'], ['page', 'legal'], true)
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

        if (($data['content']['section_type'] ?? null) === 'html') {
            foreach (['en', 'hi'] as $locale) {
                if (isset($data['content'][$locale]['html'])) {
                    $data['content'][$locale]['html'] = $this->sanitizeHomepageHtml($data['content'][$locale]['html']);
                }
            }
        }

        return [...$data, 'sort_order' => $data['sort_order'] ?? 0];
    }


    /**
     * Keep custom homepage markup to a small, attribute-free HTML allowlist.
     */
    private function sanitizeHomepageHtml(string $html): string
    {
        $html = preg_replace('/<(script|style|iframe|object|embed)[^>]*>.*?<\\/\\1>/is', '', $html) ?? '';
        $html = strip_tags($html, '<h1><h2><h3><h4><p><div><span><strong><b><em><i><ul><ol><li><br><hr><blockquote>');
        return preg_replace_callback('/<\\/?([a-z0-9]+)\\b[^>]*>/i', function ($match) {
            $tag = strtolower($match[1]);
            $allowed = ['h1','h2','h3','h4','p','div','span','strong','b','em','i','ul','ol','li','br','hr','blockquote'];
            if (!in_array($tag, $allowed, true)) return '';
            return str_starts_with($match[0], '</') ? '</' . $tag . '>' : '<' . $tag . '>';
        }, $html) ?? '';
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
