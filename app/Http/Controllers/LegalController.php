<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class LegalController extends Controller
{
    public function terms(): Response
    {
        return $this->render('terms');
    }

    public function privacy(): Response
    {
        return $this->render('privacy');
    }

    public function cookies(): Response
    {
        return $this->render('cookies');
    }

    public function accessibility(): Response
    {
        return $this->render('accessibility');
    }

    /**
     * Render a legal page from the structured content of lang/{locale}/legal.php.
     *
     * @param  'terms'|'privacy'|'cookies'|'accessibility'  $page
     */
    private function render(string $page): Response
    {
        /** @var array{title: string, description: string, last_updated: string, sections: array<int, array{heading: string, paragraphs: array<int, string|array{text: string, email: string, suffix: string}>}>} $content */
        $content = __('legal.'.$page);

        return Inertia::render('legal/Show', [
            'page' => $page,
            'title' => $content['title'],
            'description' => $content['description'],
            'lastUpdated' => $content['last_updated'],
            'sections' => $content['sections'],
        ]);
    }
}
