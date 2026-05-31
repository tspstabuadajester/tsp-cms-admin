<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteFileContentController extends Controller
{
    /**
     * Display HTML pages found in the website template directory.
     */
    public function index(Website $website): Response
    {
        $this->ensureWebsiteInScope($website);

        abort_unless($website->template_path, 404);

        return Inertia::render('Websites/FileContent/Index', [
            'website' => $website->only(['id', 'name', 'slug']),
            'pages' => $this->htmlPages($website->template_path),
        ]);
    }

    /**
     * @return list<array{path: string, name: string}>
     */
    private function htmlPages(string $templatePath): array
    {
        return collect(Storage::disk('local')->allFiles($templatePath))
            ->filter(fn (string $path) => Str::endsWith(strtolower($path), '.html'))
            ->sort()
            ->values()
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => ltrim(Str::after($path, $templatePath), '/'),
            ])
            ->all();
    }

    private function ensureWebsiteInScope(Website $website): void
    {
        $businessId = auth()->user()?->business_id;

        abort_unless(
            $businessId === null || $website->business_id === $businessId,
            403,
        );
    }
}
