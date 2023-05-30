<?php

namespace Smoq\DatabaseDumpBundle;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelDumper extends Dumper
{

    private Csv|Xlsx|Xls|Ods|Html $writer;
    private Spreadsheet $spreadsheet;

    private const CELLS = [
        'A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'I1', 'J1', 'K1', 'L1', 'M1', 'N1', 'O1', 'P1', 'Q1', 'R1', 'S1', 'T1', 'U1', 'V1', 'W1', 'X1', 'Y1', 'Z1', 'AA1', 'AB1', 'AC1', 'AD1', 'AE1', 'AF1', 'AG1', 'AH1', 'AI1', 'AJ1', 'AK1', 'AL1', 'AM1', 'AN1', 'AO1', 'AP1', 'AQ1', 'AR1', 'AS1', 'AT1', 'AU1', 'AV1', 'AW1', 'AX1', 'AY1', 'AZ1', 'BA1', 'BB1', 'BC1', 'BD1', 'BE1', 'BF1', 'BG1', 'BH1', 'BI1', 'BJ1', 'BK1', 'BL1', 'BM1', 'BN1', 'BO1', 'BP1', 'BQ1', 'BR1', 'BS1', 'BT1', 'BU1', 'BV1', 'BW1', 'BX1', 'BY1', 'BZ1',
    ];

    /**
     * Dumps the database to an excel file
     * 
     * @param string $destinationFile the path to save the file to
     * @param array $exclude the tables to exclude from the dump
     * @param string $format the file format, **excluding the dot**
     * 
     * @return string the filename of the new file
     */
    public function dumpToFile(string $destinationFile, array $exclude = [], string $format = "xlsx"): string
    {
        // Get tables data
        $this->dump($exclude);

        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);

        $this->writer = match ($format) {
            "csv" => new Csv($this->spreadsheet),
            "xlsx" => new Xlsx($this->spreadsheet),
            "xls" => new Xls($this->spreadsheet),
            "ods" => new Ods($this->spreadsheet),
            "html" => new Html($this->spreadsheet),
            default => new Xlsx($this->spreadsheet)
        };

        foreach ($this->data as $tableName => $data) {
            $this->createSingleSheet($data, $tableName);
        }

        $time = date("d-m-y--h-i");
        $filename = "{$destinationFile}/{$time}_db_dump.{$format}";

        $this->writer->save($filename);

        return $filename;
    }

    private function createSingleSheet(array $table, string $sheetName)
    {
        if (count($table) > 0) {
            $sheet = $this->spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            $i = 0;

            // Set headers
            foreach ($table[0] as $key => $value) {
                $sheet->getCell(self::CELLS[$i])->setValue($key);
                ++$i;
            }

            $rowIndex = 2;

            // Fill table
            $sheet->fromArray($table, null, 'A2', true);
        }
    }
}
