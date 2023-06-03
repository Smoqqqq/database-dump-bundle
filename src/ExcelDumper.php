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
use Smoq\DatabaseDumpBundle\Exception\MissingFileFormatException;
use Smoq\DatabaseDumpBundle\Exception\UnknowFormatException;

class ExcelDumper extends Dumper implements DumperInterface
{

    public const ACCEPTED_FILE_FORMATS = ["xlsx", "xls", "ods", "html"];

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

        $format = $this->getFileFormat($filepath);

        $this->openFile($filepath, $overwrite);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);        // Remove first sheet as it would be empty otherwise

        $writer = $this->getWriterFromFormat($format, $spreadsheet);

        foreach ($this->tables as $table) {
            if (!\in_array($table->getName(), $exclude)) {
                $data = $this->getTableData($table);
                $this->createSingleSheet($spreadsheet, $data, $table->getName());
            }
        }

        // Html writer by default only write the first sheet
        if (get_class($writer) === Html::class) {
            $writer->writeAllSheets();
        }

        $writer->save($filepath);
    }

    private function getFileFormat(string $filepath): string {
        if (strpos($filepath, ".") === false) {
            throw new MissingFileFormatException("Invalid filepath : not extension was specified.");
        }

        $format = explode(".", $filepath);
        $format = $format[count($format) - 1];

        if (!\in_array($format, self::ACCEPTED_FILE_FORMATS)) {
            throw new UnknowFormatException("File format '{$format}' isn't supported. Supported formats are : " . implode(", ", self::ACCEPTED_FILE_FORMATS));
        }

        return $format;
    }

    private function getWriterFromFormat(string $format, Spreadsheet $spreadsheet): Xlsx|Xls|Ods|Html
    {
        return match ($format) {
            "xlsx" => new Xlsx($spreadsheet),
            "xls" => new Xls($spreadsheet),
            "ods" => new Ods($spreadsheet),
            "html" => new Html($spreadsheet)
        };
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
