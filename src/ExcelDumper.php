<?php

declare(strict_types=1);

/**
 * DatabaseDumpBundle - Paul Le Flem <contact@paul-le-flem.fr>
 */

namespace Smoq\DatabaseDumpBundle;

use Doctrine\DBAL\Schema\Table;
use Smoq\DatabaseDumpBundle\Dumper;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelDumper extends Dumper implements DumperInterface
{
    /**
     * Dumps the database to a file of the given format, saving it to the specified path.
     *
     * @param string $filepath the path to save the file to. The given file extension MUST be the same as the $format param
     * @param string[] $exclude the tables to exclude from the dump
     * @param bool $overwrite weither or not to overwrite if the file exists
     * available formats:
     *  - xlsx
     *  - xls
     *  - ods
     *  - html
     */
    public function dumpToFile(string $filepath, array $exclude = [], bool $overwrite = false): void
    {
        $this->openFile($filepath, $overwrite);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $format = explode(".", $filepath);
        $format = $format[count($format) - 1];

        $writer = match ($format) {
            "xlsx" => new Xlsx($spreadsheet),
            "xls" => new Xls($spreadsheet),
            "ods" => new Ods($spreadsheet),
            "html" => new Html($spreadsheet),
            default => new Xlsx($spreadsheet)
        };

        // foreach ($this->data as $tableName => $data) {
        //     $this->createSingleSheet($spreadsheet, $data, $tableName);
        // }

        foreach ($this->tables as $table) {
            if (!\in_array($table->getName(), $exclude)) {
                $data = $this->getTableData($table);
                $this->createSingleSheet($spreadsheet, $data, $table->getName());
            }
        }

        if ($format === "html") {
            $writer->writeAllSheets();
        }

        $writer->save($filepath);
    }

    /**
     * creates a single sheet containing a table
     * 
     * @param SpreadSheet $spreadsheet
     * @param Table[] $table
     * @param string $sheetName
     */
    private function createSingleSheet(SpreadSheet $spreadsheet, array $table, string $sheetName): void
    {
        if (\count($table) == 0) {
            return;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetName);

        // https://gist.github.com/vielhuber/04dc25278b082cb0c81e
        // somehow doing ++ on a letter will go to the next alphabetical one,
        // and go to "AA", "AB"... after that, exactly what I want !
        $letter = "A";

        // Set headers
        foreach ($table[0] as $key => $value) {
            $sheet->getCell($letter . "1")->setValue($key);
            ++$letter;
        }

        // Fill table
        $sheet->fromArray($table, null, 'A2', true);
    }
}
