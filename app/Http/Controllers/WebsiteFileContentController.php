<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteFileContentController extends Controller
{
    /**
     * Display template folders and files found in the website template directory.
     */
    public function index(Website $website): Response
    {
        $this->ensureWebsiteInScope($website);

        abort_unless($website->template_path, 404);

        return Inertia::render('Websites/FileContent/Index', [
            'website' => $website->only(['id', 'name', 'slug']),
            'items' => $this->templateItems($website->template_path),
            'can_preview' => $this->hasIndexPage($website->template_path),
        ]);
    }

    /**
     * Preview the website index.html in the browser.
     */
    public function preview(Website $website): HttpResponse
    {
        $this->ensureWebsiteInScope($website);

        abort_unless($website->template_path, 404);

        $indexPath = "{$website->template_path}/index.html";

        abort_unless(Storage::disk('local')->exists($indexPath), 404);

        return response(
            Storage::disk('local')->get($indexPath),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    /**
     * @return list<array{path: string, name: string, type: string}>
     */
    private function templateItems(string $templatePath): array
    {
        $disk = Storage::disk('local');

        $folders = collect($disk->allDirectories($templatePath))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => ltrim(Str::after($path, $templatePath), '/'),
                'type' => 'folder',
            ]);

        $files = collect($disk->allFiles($templatePath))
            ->map(fn (string $path) => $this->classifyTemplateFile($templatePath, $path))
            ->filter()
            ->values();

        return $folders
            ->concat($files)
            ->sortBy(fn (array $item) => [$this->templateItemSortOrder($item['type']), $item['name']])
            ->values()
            ->all();
    }

    /**
     * @return array{path: string, name: string, type: string}|null
     */
    private function classifyTemplateFile(string $templatePath, string $path): ?array
    {
        $type = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'html' => 'html',
            'css' => 'css',
            'js', 'mjs' => 'javascript',
            'json' => 'json',
            default => null,
        };

        if ($type === null) {
            return null;
        }

        return [
            'path' => $path,
            'name' => ltrim(Str::after($path, $templatePath), '/'),
            'type' => $type,
        ];
    }

    private function templateItemSortOrder(string $type): int
    {
        return match ($type) {
            'folder' => 0,
            'html' => 1,
            'css' => 2,
            'javascript' => 3,
            'json' => 4,
            default => 5,
        };
    }

    private function hasIndexPage(string $templatePath): bool
    {
        return Storage::disk('local')->exists("{$templatePath}/index.html");
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
