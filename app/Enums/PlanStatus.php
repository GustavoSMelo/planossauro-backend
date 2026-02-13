<?php
namespace App\Enums;

enum PlanStatus: string
{
    case UNPAID = 'Unpaid';
    case PAID = 'Paid';
    case CANCELED = 'canceled';
    case ACTIVE = 'active';
}
