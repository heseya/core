<?php

namespace Tests\Unit\Languages;

use App\Traits\GetPreferredLanguage;
use Domain\Language\Language;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetPreferredLanguageTest extends TestCase
{
    use GetPreferredLanguage;

    private Language $default;
    private Language $fr;
    private Language $de;
    /** @var Collection<int, Language>  */
    private Collection $languages;

    public function setUp(): void
    {
        parent::setUp();

        $this->default = Language::make([
            'iso' => 'pl',
            'default' => true,
        ]);

        $this->fr = Language::make([
            'iso' => 'fr',
            'default' => false,
        ]);

        $this->de = Language::make([
            'iso' => 'de',
            'default' => false,
        ]);

        $this->languages = Collection::make([$this->default, $this->fr, $this->de]);
    }

    public function testDefaultNoHeader(): void
    {
        $this->assertEquals(
            $this->default,
            $this->getPreferredLanguage(null, $this->languages),
        );
    }

    public function testDefaultMissingLanguages(): void
    {
        $this->assertEquals(
            $this->default,
            $this->getPreferredLanguage('en,en-GB;q=0.9,en-US;q=0.8', $this->languages),
        );
    }

    public function testMatchingLanguage(): void
    {
        $this->assertEquals(
            $this->de,
            $this->getPreferredLanguage('en,en-GB;q=0.9,en-US;q=0.8,de;q=0.7', $this->languages),
        );
    }

    public function testMatchingMultipleLanguages(): void
    {
        $this->assertEquals(
            $this->fr,
            $this->getPreferredLanguage('en,en-GB;q=0.9,fr;q=0.8,de;q=0.7', $this->languages),
        );
    }

    public function testMatchingMultipleLanguagesSamePriority(): void
    {
        $this->assertEquals(
            $this->fr,
            $this->getPreferredLanguage('en,en-GB;q=0.9,fr;q=0.8,de;q=0.8', $this->languages),
        );
    }

    public function testMatchingRegionString(): void
    {
        $en = Language::make([
            'iso' => 'en-US',
            'default' => false,
        ]);
        $this->languages = Collection::make([$this->default, $en, $this->de]);

        $this->assertEquals(
            $en,
            $this->getPreferredLanguage('en', $this->languages),
        );
    }
}
