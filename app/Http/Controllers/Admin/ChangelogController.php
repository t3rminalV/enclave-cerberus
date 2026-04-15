<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class ChangelogController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Changelog', [
            'version' => config('app.version'),
            'releases' => $this->releases(),
        ]);
    }

    private function releases(): array
    {
        $path = base_path('CHANGELOG.md');

        if (! File::exists($path)) {
            return [];
        }

        $releases = [];
        $currentRelease = null;
        $currentSection = null;

        foreach (preg_split('/\R/', File::get($path)) as $line) {
            if (preg_match('/^#{2,3}\s+(?:\[?v?([0-9][^\]\s)]*)\]?(?:\([^)]+\))?)(?:\s+-\s+|\s+\()([^)]+)\)?/', $line, $matches)) {
                if ($currentRelease) {
                    $releases[] = $currentRelease;
                }

                $currentRelease = [
                    'version' => $matches[1],
                    'date' => trim($matches[2]),
                    'sections' => [],
                ];
                $currentSection = null;
                continue;
            }

            if (! $currentRelease) {
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                $currentSection = trim($matches[1]);
                $currentRelease['sections'][$currentSection] = [];
                continue;
            }

            if ($currentSection && preg_match('/^\s*[-*]\s+(.+)$/', $line, $matches)) {
                $currentRelease['sections'][$currentSection][] = $this->plainText(trim($matches[1]));
            }
        }

        if ($currentRelease) {
            $releases[] = $currentRelease;
        }

        return $releases;
    }

    private function plainText(string $line): string
    {
        return preg_replace('/\[(.+?)\]\([^)]+\)/', '$1', $line);
    }
}
