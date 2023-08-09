<?php

namespace Tests\Unit\Languages;

use App\Traits\GetPreferredLanguage;
use Domain\Language\Language;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetPreferredLanguageTest extends TestCase
{
    use GetPreferredLanguage;

    public function testDefaultNoHeader(): void
    {
        $default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $fr = Language::make([
            'iso' => 'fr',
            'default' => false,
        ]);

        $de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $languages = Collection::make([$default, $fr, $de]);

        $this->assertEquals(
            $default,
            $this->getPreferredLanguage(null, $languages),
        );
    }

    public function testDefaultMissingLanguages(): void
    {
        $default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $fr = Language::make([
            'iso' => 'fr',
            'default' => false,
        ]);

        $de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $languages = Collection::make([$default, $fr, $de]);

        $this->assertEquals(
            $default,
            $this->getPreferredLanguage('en,en-GB;q=0.9,en-US;q=0.8', $languages),
        );
    }

    public function testMatchingLanguage(): void
    {
        $default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $fr = Language::make([
            'iso' => 'fr',
            'default' => false,
        ]);

        $de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $languages = Collection::make([$default, $fr, $de]);

        $this->assertEquals(
            $de,
            $this->getPreferredLanguage('en,en-GB;q=0.9,en-US;q=0.8,de;q=0.7', $languages),
        );
    }

    public function testMatchingMultipleLanguages(): void
    {
        $default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $fr = Language::make([
            'iso' => 'fr',
            'default' => false,
        ]);

        $de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $languages = Collection::make([$default, $fr, $de]);

        $this->assertEquals(
            $fr,
            $this->getPreferredLanguage('en,en-GB;q=0.9,fr;q=0.8,de;q=0.7', $languages),
        );
    }

    public function testMatchingMultipleLanguagesSamePriority(): void
    {
        $default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $fr = Language::make([
            'iso' => 'fr',
            'default' => false,
        ]);

        $de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $languages = Collection::make([$default, $fr, $de]);

        $this->assertEquals(
            $fr,
            $this->getPreferredLanguage('en,en-GB;q=0.9,fr;q=0.8,de;q=0.8', $languages),
        );
    }

    public function testMatchingRegionString(): void
    {
        $default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $en = Language::make([
            'iso' => 'en-US',
            'default' => false,
        ]);

        $de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $languages = Collection::make([$default, $en, $de]);

        $this->assertEquals(
            $en,
            $this->getPreferredLanguage('en', $languages),
        );
    }
}
