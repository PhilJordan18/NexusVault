<?php

use Inertia\Testing\AssertableInertia as Assert;

test('legal pages are public and rendered by the Vue legal page', function (string $routeName, string $page, string $expectedTitle) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('legal/Show')
            ->where('page', $page)
            ->where('title', $expectedTitle)
            ->has('description')
            ->has('lastUpdated')
            ->has('sections.0.heading')
            ->has('sections.0.paragraphs.0')
        );
})->with([
    ['legal.terms', 'terms', 'Terms of Use'],
    ['legal.privacy', 'privacy', 'Privacy Policy'],
    ['legal.cookies', 'cookies', 'Cookie Policy'],
    ['legal.accessibility', 'accessibility', 'Accessibility Statement'],
]);

test('legal pages respect the selected locale', function () {
    $this->withSession(['locale' => 'fr'])
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('legal/Show')
            ->where('title', 'Politique de confidentialité')
            ->where('locale', 'fr')
            ->where('translations.Legal Center', 'Centre légal')
            ->where('sections.0.paragraphs.0', fn (string $paragraph) => str_contains($paragraph, 'minimiser les données visibles par le serveur'))
        );
});

test('legal pages share the locale catalog with the Vue page', function () {
    $this->get(route('legal.terms'))
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->where('locale', 'en')
            ->where('locales', ['en', 'fr'])
            ->where('translations.Legal Center', 'Legal Center')
        );
});

test('legal content has the same structure in both languages', function (string $page) {
    $english = __('legal.'.$page, [], 'en');
    $french = __('legal.'.$page, [], 'fr');

    expect($english)->toHaveKeys(['title', 'description', 'last_updated', 'sections'])
        ->and(count($french['sections']))->toBe(count($english['sections']));

    foreach ($english['sections'] as $index => $section) {
        expect(count($french['sections'][$index]['paragraphs']))->toBe(count($section['paragraphs']));
    }
})->with(['terms', 'privacy', 'cookies', 'accessibility']);
