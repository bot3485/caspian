<?php

namespace App\Enums;

enum MatchmakingStatus: string
{
    case Searching = 'searching';
    case Waiting = 'waiting';
    case Matched = 'matched';
}