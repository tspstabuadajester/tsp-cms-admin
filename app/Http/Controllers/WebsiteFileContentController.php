<?php

namespace App\Http\Controllers;

use App\Http\Requests\Website\UpdateWebsiteJsonRequest;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
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
     * Show the JSON file editor for a template file.
     */
    public function editJson(Website $website, string $path): Response
    {
        $this->ensureWebsiteInScope($website);

        abort_unless($website->template_path, 404);

        $filePath = $this->resolveTemplateFilePath($website->template_path, $path);

        abort_unless(
            $filePath !== null && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'json',
            404,
        );

        $parsed = $this->parseJsonSections(Storage::disk('local')->get($filePath));

        return Inertia::render('Websites/FileContent/JsonEditor', [
            'website' => $website->only(['id', 'name', 'slug']),
            'file' => [
                'path' => $filePath,
                'name' => ltrim(Str::after($filePath, $website->template_path), '/'),
            ],
            'sections' => $parsed['sections'],
            'json_error' => $parsed['error'],
            'can_preview' => $this->hasIndexPage($website->template_path),
            'status' => session('status'),
        ]);
    }

    /**
     * Rebuild and save a template JSON file from edited sections.
     */
    public function updateJson(UpdateWebsiteJsonRequest $request, Website $website, string $path): RedirectResponse
    {
        $this->ensureWebsiteInScope($website);

        abort_unless($website->template_path, 404);

        $filePath = $this->resolveTemplateFilePath($website->template_path, $path);

        abort_unless(
            $filePath !== null && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'json',
            404,
        );

        $contents = Storage::disk('local')->get($filePath);
        $decoded = json_decode($contents, true);

        abort_unless(is_array($decoded) && ! array_is_list($decoded), 422);

        $rebuilt = $this->rebuildJsonDocument($decoded, $request->validated('sections'));

        Storage::disk('local')->put(
            $filePath,
            json_encode($rebuilt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
        );

        return redirect()
            ->route('websites.files.json', ['website' => $website, 'path' => $path])
            ->with('status', 'JSON file saved successfully.');
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

    /**
     * @return array{sections: list<array{key: string, fields: list<array{path: string, value: string}>}>, error: string|null}
     */
    private function parseJsonSections(string $contents): array
    {
        $decoded = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'sections' => [],
                'error' => 'This file contains invalid JSON and cannot be edited.',
            ];
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return [
                'sections' => [],
                'error' => 'Only JSON objects with key-value pairs are supported.',
            ];
        }

        $sections = [];
        $invalidSectionKeys = [];

        foreach ($decoded as $sectionKey => $sectionValue) {
            if (! $this->isJsonObject($sectionValue)) {
                $invalidSectionKeys[] = (string) $sectionKey;

                continue;
            }

            $fields = [];
            $this->collectScalarFields('', $sectionValue, $fields);

            $sections[] = [
                'key' => (string) $sectionKey,
                'fields' => $fields,
            ];
        }

        if ($invalidSectionKeys !== [] && $sections === []) {
            return [
                'sections' => [],
                'error' => 'Each top-level key must be a JSON object with nested fields.',
            ];
        }

        return [
            'sections' => $sections,
            'error' => null,
        ];
    }

    private function isJsonObject(mixed $value): bool
    {
        return is_array($value) && ! array_is_list($value);
    }

    /**
     * @param  list<array{path: string, value: string}>  $fields
     */
    private function collectScalarFields(string $prefix, mixed $value, array &$fields): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
                $this->collectScalarFields($path, $child, $fields);
            }

            return;
        }

        if ((is_scalar($value) || $value === null) && $prefix !== '') {
            $fields[] = [
                'path' => $prefix,
                'value' => $this->jsonFieldValueToString($value),
            ];
        }
    }

    private function jsonFieldValueToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  list<array{key: string, fields: list<array{path: string, value: string}>}>  $submittedSections
     * @return array<string, mixed>
     */
    private function rebuildJsonDocument(array $original, array $submittedSections): array
    {
        foreach ($submittedSections as $section) {
            $sectionKey = $section['key'];

            if (! isset($original[$sectionKey]) || ! $this->isJsonObject($original[$sectionKey])) {
                continue;
            }

            foreach ($section['fields'] as $field) {
                $path = $field['path'];

                if ($path === '') {
                    continue;
                }

                $originalValue = data_get($original[$sectionKey], $path);

                data_set(
                    $original[$sectionKey],
                    $path,
                    $this->parseFieldValueFromString($field['value'], $originalValue),
                );
            }
        }

        return $original;
    }

    private function parseFieldValueFromString(string $value, mixed $original): mixed
    {
        if (is_bool($original)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_int($original)) {
            return (int) $value;
        }

        if (is_float($original)) {
            return (float) $value;
        }

        if ($original === null) {
            return $value === '' ? null : $value;
        }

        if (is_array($original)) {
            if ($value === '') {
                return $original;
            }

            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $original;
        }

        return $value;
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
