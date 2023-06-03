# Database Dump Bundle

This bundle allows to easily dump a full database to an excel file or an SQL file.

## Usage

example in a controller :
```php
#[Route("/database/dump/excel", name: "app_dump_database_excel")]
function dumpDatabase(ManagerRegistry $doctrine, ExcelDumper $dumper): BinaryFileResponse
{
    $filepath = getcwd() . "/exports/dump.xlsx";

    // dump the db to a .xlsx file
    $dumper->dumpToFile($filepath, ["doctrine_migration_versions"], true);

    $response = new BinaryFileResponse($filepath);
    $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        "dump.sql"
    );

    // return it as an attachment (download it to the user's computer)
    return $response;
}
```
Simply replace ExcelDumper by SqlDumper to create a .sql file instead (don't forget to replace the extension in the filepath !).

If you only want to dump the schema of the database, without its data, you can use the `DumperInterface::dumpSchema` method.

## Excel dumper
Please note that when using the excel dumper, the file format is specified by the file extension (string after the last dot). If the file format isn't supported, it will default throw an error

## Sql dumper
If you try to exclude an entity which another depends upon, without excluding the other one, an error will be thrown, as the generated sql file would be broken otherwise.