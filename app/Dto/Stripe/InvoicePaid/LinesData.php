<?php

namespace App\Dto\Stripe\InvoicePaid;

class LinesData
{
    public string $id;

    public ?string $description;

    public string $invoice;

    public LinesParent $parent;

    public Princing $princing;

    public int $period_end;

    public function __construct(
        string $id,
        string $description,
        string $invoice,
        LinesParent $parent,
        Princing $princing,
        int $period_end,
    ) {
        $this->id = $id;
        $this->description = $description;
        $this->invoice = $invoice;
        $this->parent = $parent;
        $this->princing = $princing;
        $this->period_end = $period_end;
    }
}
