<?php
namespace App\Enums;

enum PlanStatus: string
{
    case ACTIVE = "Ativo";
    case CANCELLED = "Cancelado";
    case PROCESSING = 'Processando';
    case PAYMENT_FAILED = 'Pagamento falho';
    case PAYED = 'Pago';
}
