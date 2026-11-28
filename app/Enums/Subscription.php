<?php

namespace App\Enums;

enum Subscription: string
{
    case Free = 'Free';

    /** Invited during the beta: free as promised, whatever the plans become */
    case Founder = 'Founder';
}
