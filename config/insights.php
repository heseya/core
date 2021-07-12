<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenPrivateMethods;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use NunoMaduro\PhpInsights\Domain\Metrics\Code\Code;
use NunoMaduro\PhpInsights\Domain\Metrics\Style\Style;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UnconditionalIfStatementSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseConstantSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowTabIndentSniff;
use PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\ControlStructures\ElseIfDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\EndFileNewlineSniff;
use PhpCsFixer\Fixer\ArrayNotation\NoTrailingCommaInSinglelineArrayFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Casing\LowercaseStaticReferenceFixer;
use PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\NoTrailingWhitespaceInCommentFixer;
use PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\StandardizeNotEqualsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarAnnotationCorrectOrderFixer;
use PhpCsFixer\Fixer\PhpTag\FullOpeningTagFixer;
use SlevomatCodingStandard\Sniffs\Arrays\TrailingArrayCommaSniff;
use SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\NamespaceSpacingSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseDoesNotStartWithBackslashSniff;
use SlevomatCodingStandard\Sniffs\Operators\RequireCombinedAssignmentOperatorSniff;
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

    'preset' => 'laravel',
    /*
    |--------------------------------------------------------------------------
    | IDE
    |--------------------------------------------------------------------------
    |
    | This options allow to add hyperlinks in your terminal to quickly open
    | files in your favorite IDE while browsing your PhpInsights report.
    |
    | Supported: "textmate", "macvim", "emacs", "sublime", "phpstorm",
    | "atom", "vscode".
    |
    | If you have another IDE that is not in this list but which provide an
    | url-handler, you could fill this config with a pattern like this:
    |
    | myide://open?url=file://%f&line=%l
    |
    */

    'ide' => 'phpstorm',
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

    'exclude' => [
        //  'path/to/directory-or-file'
    ],

    'add' => [
        Code::class => [
            ShortScalarCastFixer::class,
            UnconditionalIfStatementSniff::class,
            RequireCombinedAssignmentOperatorSniff::class,
        ],
        Style::class => [
            DisallowLongArraySyntaxSniff::class,
            DisallowTabIndentSniff::class,
            LowerCaseConstantSniff::class,
            PhpdocVarAnnotationCorrectOrderFixer::class,
            TrailingArrayCommaSniff::class,
            EndFileNewlineSniff::class,
            CamelCapsMethodNameSniff::class,
            ElseIfDeclarationSniff::class,
            UpperCaseConstantNameSniff::class,
            AlphabeticallySortedUsesSniff::class,
            NamespaceSpacingSniff::class,
            UnusedUsesSniff::class,
            UseDoesNotStartWithBackslashSniff::class,
            NoTrailingCommaInSinglelineArrayFixer::class,
            NoWhitespaceBeforeCommaInArrayFixer::class,
            BracesFixer::class,
            LowercaseStaticReferenceFixer::class,
            NoTrailingWhitespaceInCommentFixer::class,
            BinaryOperatorSpacesFixer::class,
            StandardizeNotEqualsFixer::class,
            FullOpeningTagFixer::class,
            OrderedClassElementsFixer::class,
            SingleImportPerStatementFixer::class,
        ],
    ],

    'remove' => [
        DeclareStrictTypesSniff::class,
        DisallowMixedTypeHintSniff::class,
        ForbiddenDefineFunctions::class,
        ForbiddenNormalClasses::class,
        ForbiddenTraits::class,
        ParameterTypeHintSniff::class,
        PropertyTypeHintSniff::class,
        ReturnTypeHintSniff::class,
        UselessFunctionDocCommentSniff::class,
        SpaceAfterNotSniff::class,
        SuperfluousExceptionNamingSniff::class,
        UnusedParameterSniff::class,
        ForbiddenPublicPropertySniff::class,
        FunctionLengthSniff::class,
    ],

    'config' => [
        ForbiddenPrivateMethods::class => [
            'title' => 'The usage of private methods is not idiomatic in Laravel.',
        ],
        LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 120,
            'ignoreComments' => false,
        ],
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
