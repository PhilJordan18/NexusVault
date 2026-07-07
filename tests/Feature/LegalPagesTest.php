<?php

test('legal pages are public', function (string $routeName, string $expectedText) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee($expectedText);
})->with([
    ['legal.terms', 'Terms of Use'],
    ['legal.privacy', 'Privacy Policy'],
    ['legal.cookies', 'Cookie Policy'],
    ['legal.accessibility', 'Accessibility Statement'],
]);

test('legal pages respect the selected locale', function () {
    $this->withSession(['locale' => 'fr'])
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('Politique de confidentialité')
        ->assertSee('zéro connaissance');
});
