<?php

namespace App\Dto\Stripe\InvoicePaid;

class LinesData
{
    public string $id;
    public ?string $description;
    public string $invoice;
    public LinesParent $parent;
    public Princing $princing;

    public function __construct(
        string $id,
        string $description,
        string $invoice,
        LinesParent $parent,
        Princing $princing
    )
    {
        $this->id = $id;
        $this->description = $description;
        $this->invoice = $invoice;
        $this->parent = $parent;
        $this->princing = $princing;
    }
}
