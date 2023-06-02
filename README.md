# Database Dump Bundle

This bundle allows to easily dump a full database to an excel file or an SQL file.

## Usage

```php
$dumper = new ExcelDumper(); // Or SqlDumper as they both implement DumperInterface
$dumper->dumpToFile($filepath, $exlude, $format)
```