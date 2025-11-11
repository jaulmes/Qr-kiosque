<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = ["region", "sa_name", "cia_dsm_md_msisdn", "cia_dsm_md_name", "pos_msisdn", "pos_code", "kiosque_name", "bv"];
$sheet->fromArray($headers, NULL, 'A1');

$data = [
    ["REGION 1", "SA NAME 1", "1234567890", "DSM NAME 1", "0987654321", "POS001", "Kiosque 1", "BV001"],
    ["REGION 2", "SA NAME 2", "1234567891", "DSM NAME 2", "0987654322", "POS002", "Kiosque 2", "BV002"],
    ["REGION 3", "SA NAME 3", "1234567892", "DSM NAME 3", "0987654323", "POS003", "Kiosque 3", "BV003"],
];
$sheet->fromArray($data, NULL, 'A2');

$writer = new Xlsx($spreadsheet);
$writer->save('storage/app/imports/test.xlsx');

echo "File created successfully.";
