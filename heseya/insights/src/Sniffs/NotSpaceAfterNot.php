<?php

namespace Heseya\Insights\Sniffs;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;

class NotSpaceAfterNot extends SpaceAfterNotSniff
{
    public $spacing = 0;
}
