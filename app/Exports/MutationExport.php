<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MutationExport implements FromView
{
    protected $mutations;

    function __construct($mutations)
    {
        $this->mutations = $mutations;
    }

    public function view(): View
    {
        return view('exports.mutation', [
            'mutations' => $this->mutations
        ]);
    }
}
