<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

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

        $html = Storage::disk('local')->get($indexPath);
        $baseHref = route('websites.preview', $website).'/';

        return response(
            $this->injectPreviewBaseTag($html, $baseHref),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    /**
     * Serve a template asset for website preview.
     */
    public function previewAsset(Website $website, string $path): BaseResponse
    {
        $this->ensureWebsiteInScope($website);

        abort_unless($website->template_path, 404);

        $filePath = $this->resolveTemplateFilePath($website->template_path, $path);

        abort_unless($filePath !== null, 404);

        return Storage::disk('local')->response($filePath);
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

    private function injectPreviewBaseTag(string $html, string $baseHref): string
    {
        $baseTag = '<base href="'.e($baseHref).'">';

        if (preg_match('/<head[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1] + strlen($matches[0][0]);

            return substr_replace($html, $baseTag, $position, 0);
        }

        if (preg_match('/<html[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1] + strlen($matches[0][0]);

            return substr_replace($html, '<head>'.$baseTag.'</head>', $position, 0);
        }

        return $baseTag.$html;
    }

    private function resolveTemplateFilePath(string $templatePath, string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '' || Str::contains($relativePath, ['..', "\0"])) {
            return null;
        }

        $filePath = "{$templatePath}/{$relativePath}";

        if (! Storage::disk('local')->exists($filePath) || Storage::disk('local')->directoryExists($filePath)) {
            return null;
        }

        $templateRoot = realpath(Storage::disk('local')->path($templatePath));
        $resolvedFile = realpath(Storage::disk('local')->path($filePath));

        if ($templateRoot === false || $resolvedFile === false || ! str_starts_with($resolvedFile, $templateRoot)) {
            return null;
        }

        return $filePath;
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
