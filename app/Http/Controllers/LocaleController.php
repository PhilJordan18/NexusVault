<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

final class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated()['locale'];

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back()->with('success', __('Language updated.'));
    }
}
