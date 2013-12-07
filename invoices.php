<?php
require_once 'bootstrap.php';
require_once "searches.php";
require_once './api/authenticationUtilities.php';

$neededPermissions = array('read');
evaluateSessionPermissions($neededPermissions);

$fields = array(
    'invoiceNo' => 'Invoice Number',
    'invoiceDate' => 'Invoice Date',
    'CompanyName' => 'Company Name',
    'taxPayable' => 'Payable tax',
    'netTotal' => 'Net Total',
    'grossTotal' => 'Gross total');

echo getSearchPage("Invoices", $fields);
?>