<?php

namespace App\Enums;

enum PlanSlug: string
{
    case Free = 'gratuito';
    case Pro = 'pro';
    case Plus = 'plus';
}
