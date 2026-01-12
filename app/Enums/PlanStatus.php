<?php
namespace App;

enum PlanStatus: string
{
    case ACTIVE = "ativo";
    case CANCELLED = "cancelado";
    case PROCESSING = 'processando';
    case PAYMENT_FAILED = 'Pagamento falho';
    case PAYED = 'Pago';
}
