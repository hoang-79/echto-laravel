<?php

namespace Echto\Laravel;

/**
 * Urteil von Echto: annehmen, zurückhalten, nicht annehmen.
 */
enum Verdict: string
{
    case Pass = 'pass';
    case Review = 'review';
    case Block = 'block';
}
