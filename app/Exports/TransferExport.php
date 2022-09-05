<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TransferExport implements FromView
{
    protected $transfers;

    function __construct($transfers)
    {
        $this->transfers = $transfers;
    }

    public function view(): View
    {
        return view('exports.transfer', [
            'transfers' => $this->transfers
        ]);
    }
}
