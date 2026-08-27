<?php

namespace App\Livewire;

use Livewire\Component;

class LanguageSwitcher extends Component
{
    public function setLocale(string $locale): void
    {
        if (! in_array($locale, config('app.available_locales', ['fr', 'en']))) {
            return;
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        $this->redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.language-switcher', [
            'current' => app()->getLocale(),
            'locales' => config('app.available_locales', ['fr', 'en']),
        ]);
    }
}
