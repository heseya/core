<?php

declare(strict_types=1);

use Heseya\Insights\Sniffs\NotSpaceAfterNot;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineGlobalConstants;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenGlobals;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenPrivateMethods;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use NunoMaduro\PhpInsights\Domain\Metrics\Code\Code;
use NunoMaduro\PhpInsights\Domain\Metrics\Style\Style;
use NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UselessOverridingMethodSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\TodoSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineEndingsSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use SlevomatCodingStandard\Sniffs\Arrays\DisallowImplicitArrayCreationSniff;
use SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowShortTernaryOperatorSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\StaticClosureSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | This option controls the default preset that will be used by PHP Insights
    | to make your code reliable, simple, and clean. However, you can always
    | adjust the `Metrics` and `Insights` below in this configuration file.
    |
    | Supported: "default", "laravel", "symfony", "magento2", "drupal"
    |
    */

    'preset' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may adjust all the various `Insights` that will be used by PHP
    | Insights. You can either add, remove or configure `Insights`. Keep in
    | mind that all added `Insights` must belong to a specific `Metric`.
    |
    */

    'add' => [
        Code::class => [
            VoidReturnFixer::class,
        ],
        Style::class => [
            NotSpaceAfterNot::class,
        ],
    ],

    'remove' => [
        DeclareStrictTypesSniff::class,
        DisallowMixedTypeHintSniff::class,
        ForbiddenNormalClasses::class,
        ForbiddenTraits::class,
        ParameterTypeHintSniff::class,
        PropertyTypeHintSniff::class,
        ReturnTypeHintSniff::class,
        SuperfluousExceptionNamingSniff::class,
        UnusedParameterSniff::class,
        ForbiddenPublicPropertySniff::class,
        FunctionLengthSniff::class,
        LineEndingsSniff::class,
        TodoSniff::class,
        ForbiddenFunctionsSniff::class,
        ForbiddenSetterSniff::class,
        DisallowShortTernaryOperatorSniff::class,
        ForbiddenGlobals::class,
        DisallowImplicitArrayCreationSniff::class,

        // laravel default
        ProtectedToPrivateFixer::class,
        StaticClosureSniff::class,

        // replaced with own
        SpaceAfterNotSniff::class,
    ],

    'config' => [
        ForbiddenDefineGlobalConstants::class => [
            'ignore' => ['LARAVEL_START'],
        ],
        ForbiddenPrivateMethods::class => [
            'title' => 'The usage of private methods is not idiomatic in Laravel.',
        ],
        LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 120,
            'ignoreComments' => false,
        ],
        UselessOverridingMethodSniff::class => [
            'exclude' => [
                'app/Exceptions',
            ],
        ],
        ForbiddenFunctionsSniff::class => [
            'forbiddenFunctions' => [
                'dd' => null,
                'dump' => null,
                'ddd' => null,
                'tinker' => null,
                'collect' => 'Collection::make',
                'cache' => 'Cache::get',
                'config' => 'Config::get',
                'redirect' => 'Redirect::to',
                'response' => 'Response::json',
            ],
        ],
    ],

    'exclude' => [
        'storage',
        'bootstrap',
        'database',
        '_ide_helper.php',
        '_ide_helper_models.php',
        'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Requirements
    |--------------------------------------------------------------------------
    |
    | Here you may define a level you want to reach per `Insights` category.
    | When a score is lower than the minimum level defined, then an error
    | code will be returned. This is optional and individually defined.
    |
    */

    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 80,
        'min-architecture' => 100,
        'min-style' => 100,
        'disable-security-check' => false,
    ],

];
