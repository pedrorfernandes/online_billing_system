<?php
require_once '../bootstrap.php';

require_once 'product.php';
require_once 'authenticationUtilities.php';

if(!comparePermissions(array('write'))) {
    $error = new Error(601, 'Permission denied');
    die( json_encode($error->getInfo()) );
}

$jsonProduct = NULL;
if ( isset($_POST['product']) && !empty($_POST['product']) ) {
    $jsonProduct = $_POST['product'];
} else {
    $error = new Error(700, 'Missing \'product\' field');
    die( json_encode($error->getInfo()) );
}

$productInfo = json_decode($jsonProduct, true);
echo json_encode(updateProduct($productInfo));
