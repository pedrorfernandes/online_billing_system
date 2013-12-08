﻿<?php
require_once '../bootstrap.php';
require_once 'search.php';
require_once 'utilities.php';

$sourceID = $_SESSION['username'];

Header('Content-Type: text/xml');

/****************************************************
AUDIT ELEMENT
****************************************************/

$AuditElement = new SimpleXMLElement("<AuditFile></AuditFile>");
$AuditElement->addAttribute('xmlns', 'urn:OECD:StandardAuditFile-Tax:PT_1.03_01');
//SimpleXML always removes the first namespace prefix, so we need to repeat it
$AuditElement->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$AuditElement->addAttribute('xmlns:xmlns:spi', 'http://Empresa.pt/invoice1');
$AuditElement->addAttribute('xmlns:xmlns:saf', 'urn:OECD:StandardAuditFile-Tax:PT_1.03_01');
$AuditElement->addAttribute('xsi:xsi:schemaLocation', 'urn:OECD:StandardAuditFile-Tax:PT_1.03_01 http://serprest.pt/tmp/SAFTPT-1.03_01.xsd');

/****************************************************
HEADER
****************************************************/

$Header = $AuditElement->addChild('Header');
$Header->addChild('AuditFileVersion','1.03_01');
$Header->addChild('CompanyID','Leiria 55555');
$Header->addChild('TaxRegistrationNumber','506219300');
$Header->addChild('TaxAccountingBasis', 'F');
$Header->addChild('CompanyName','Pens &amp; Pencils');
$CompanyAddress = $Header->addChild('CompanyAddress');
$CompanyAddress->addChild('AddressDetail','Avenida dos Queijos, nº 1');
$CompanyAddress->addChild('City','Porto');
$CompanyAddress->addChild('PostalCode','4400-125');
$CompanyAddress->addChild('Country','PT');
$Header->addChild('FiscalYear','2010');
$Header->addChild('StartDate','2010-01-01');//TODO
$Header->addChild('EndDate','2011-12-31');//TODO
$Header->addChild('CurrencyCode','EUR');
$Header->addChild('DateCreated','2013-11-15');//TODO
$Header->addChild('TaxEntity','Global');
$Header->addChild('ProductCompanyTaxID','506209365');
$Header->addChild('SoftwareCertificateNumber','0');
$Header->addChild('ProductID','Empresa/MagnumInvoices');
$Header->addChild('ProductVersion','1.0');


/****************************************************
MASTERFILES
****************************************************/
$MasterFile = $AuditElement->addChild('MasterFiles');

$search = new ListAllSearch('Customer', 'CustomerID', array(), array('*'), array('Customer' => 'Country'));
$customers = $search->getResults();

$search2 = new ListAllSearch('Product', 'ProductID', array(), array('*'));
$products = $search2->getResults();

$search3 = new ListAllSearch('Tax', 'TaxID', array(), array('*'));
$taxes = $search3->getResults();

foreach($customers as $customer){
	$customerElement = $MasterFile->addChild('Customer');
	$customerElement->addChild('CustomerID',$customer['CustomerID']);
	$customerElement->addChild('AccountID',$customer['CustomerTaxID']);
	$customerElement->addChild('CustomerTaxID',$customer['CustomerTaxID']);
	$customerElement->addChild('CompanyName',htmlspecialchars($customer['CompanyName']));

	$BillingAddress = $customerElement->addChild('BillingAddress');
	$BillingAddress->addChild('AddressDetail',htmlspecialchars($customer['AddressDetail']));
	$BillingAddress->addChild('City',htmlspecialchars($customer['City']));
	$BillingAddress->addChild('PostalCode',htmlspecialchars($customer['PostalCode']));
	$BillingAddress->addChild('Country',htmlspecialchars($customer['Country']));

	$customerElement->addChild('SelfBillingIndicator', '0');
}

foreach($products as $product){

	$productElement = $MasterFile->addChild('Product');
	$productElement->addChild('ProductType','P');
	$productElement->addChild('ProductCode',$product['ProductCode']);
	$productElement->addChild('ProductGroup','1');
	$productElement->addChild('ProductDescription',htmlspecialchars($product['ProductDescription']));
	$productElement->addChild('ProductNumberCode',htmlspecialchars($product['ProductCode']));
}

$productElement = $MasterFile->addChild('TaxTable');
foreach($taxes as $tax){
	$taxTableElement = $productElement->addChild('TaxTableEntry');
	$taxTableElement->addChild('TaxType',$tax['TaxType']);
	$taxTableElement->addChild('TaxCountryRegion','PT');
	$taxTableElement->addChild('TaxCode','NOR');
	$taxTableElement->addChild('Description',htmlspecialchars($tax['TaxDescription']));
	$taxTableElement->addChild('TaxPercentage',htmlspecialchars($tax['TaxPercentage']));
}

/****************************************************
Sales Invoices
****************************************************/
$SourceDocuments = $AuditElement->addChild('SourceDocuments');

$SalesInvoices = $SourceDocuments->addChild('SalesInvoices');

$search4 = new ListAllSearch('Invoice', 'InvoiceID', array(), array('*'));
$invoices = $search4->getResults();

$number = count($invoices);
$SalesInvoices->addChild('NumberOfEntries',$number);
$SalesInvoices->addChild('TotalDebit','0');//TODO

$credit = 0;

foreach($invoices as $invoice)
{
	$credit += $invoice['NetTotal'];
}

$SalesInvoices->addChild('TotalCredit',$credit);

//date_default_timezone_set("GMT");

$fulltime = date('c'); 
$date = date('Y-m-d');

foreach($invoices as $invoice)
{
	$invoiceElement = $SalesInvoices->addChild('Invoice');
	$invoiceElement->addChild('InvoiceNo',$invoice['InvoiceNo']);
	$documentStatus = $invoiceElement->addChild('DocumentStatus');

	$documentStatus->addChild('InvoiceStatus','N');
	$documentStatus->addChild('InvoiceStatusDate',$fulltime);
	$documentStatus->addChild('SourceID',$sourceID);//TODO
	$documentStatus->addChild('SourceBilling','P');

	$invoiceElement->addChild('Hash','0');
	$invoiceElement->addChild('InvoiceDate',$date);//TODO - isto pode ser o system date $date ou $invoice['invoiceDate']
	$invoiceElement->addChild('InvoiceType','FT');

	$SpecialRegimes = $invoiceElement->addChild('SpecialRegimes');
	$SpecialRegimes->addChild('SelfBillingIndicator','0');
	$SpecialRegimes->addChild('CashVATSchemeIndicator','0');
	$SpecialRegimes->addChild('ThirdPartiesBillingIndicator','0');

	$invoiceElement->addChild('SourceID',$sourceID);//TODO
	$invoiceElement->addChild('SystemEntryDate',$fulltime);
	$invoiceElement->addChild('CustomerID',$invoice['CustomerID']);

	//lines
    $table = 'InvoiceLine';
    $field = 'InvoiceID';
    $values = array($invoice['InvoiceID']);
    $rows = array('InvoiceID', 'LineNumber', 'ProductCode', 'ProductDescription', 'Quantity', 'UnitPrice', 'UnitOfMeasure', 'CreditAmount' , 'Tax.TaxID AS TaxID', 'TaxType', 'TaxPercentage');
    $joins = array('InvoiceLine' => array('Tax', 'Product'));

    $invoiceLinesSearch = new EqualSearch($table, $field, $values, $rows, $joins);
    $invoiceLines = $invoiceLinesSearch->getResults();
    foreach($invoiceLines as &$invoiceLine){
        roundLineTotals($invoiceLine);
        setValuesAsArray('Tax', array('TaxType', 'TaxPercentage'), $invoiceLine);

       if($invoiceLine['InvoiceID'] == $invoice['InvoiceID'])
        {
        	$Line = $invoiceElement->addChild('Line');
        	$Line->addChild('LineNumber',$invoiceLine['LineNumber']);
        	$Line->addChild('ProductCode',$invoiceLine['ProductCode']);
        	$Line->addChild('ProductDescription',$invoiceLine['ProductDescription']);
        	$Line->addChild('Quantity',$invoiceLine['Quantity']);
        	$Line->addChild('UnitOfMeasure',$invoiceLine['UnitOfMeasure']);
        	$Line->addChild('UnitPrice',$invoiceLine['UnitPrice']);
        	$Line->addChild('TaxPointDate',$date);//TODO - isto pode ser o system date $date ou $invoice['invoiceDate']
        	$Line->addChild('Description',$invoiceLine['ProductDescription']);//TODO - evernote says "longer description"
        	$Line->addChild('CreditAmount',$invoiceLine['CreditAmount']);
        	$TaxLine = $Line->addChild('Tax');
        	$TaxLine->addChild('TaxType',$invoiceLine['Tax']['TaxType']);
        	$TaxLine->addChild('TaxCountryRegion','PT');
        	$TaxLine->addChild('TaxCode','NOR');
        	$TaxLine->addChild('TaxPercentage',$invoiceLine['Tax']['TaxPercentage']);
        	$Line->addChild('SettlementAmount','0');

        }
    }
	$documentTotals = $invoiceElement->addChild('DocumentTotals');
	$documentTotals->addChild('TaxPayable',$invoice['TaxPayable']);
	$documentTotals->addChild('NetTotal',$invoice['NetTotal']);
	$documentTotals->addChild('GrossTotal',$invoice['GrossTotal']);
}


echo $AuditElement->asXML();
?>
