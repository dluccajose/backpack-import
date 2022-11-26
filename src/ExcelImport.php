<?php

namespace Dlucca\BackpackImport;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** 
 * Clase helpers para transformar un archivo excel/csv a array
 */
class ExcelImport implements WithHeadingRow
{
    private int $headingRow;

    public function __construct($headingRow = 1)
    {
        $this->headingRow = $headingRow;    
    }
    
    public function headingRow(): int
    {
        return $this->headingRow;
    }
}
