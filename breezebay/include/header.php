<?php
// The DB connection $con is expected to be included from the parent file.
if (isset($con)) {
    $header_branding_sql = "SELECT company_name, website_name, logo, favicon FROM tblbranding LIMIT 1";
    $header_branding_query = mysqli_query($con, $header_branding_sql);
    $current_branding = mysqli_fetch_assoc($header_branding_query) ?: [];
}

// Set a default page title if one wasn't provided by the calling page
$page_title = isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard';

// Use companyName for the title, with a fallback.
$company_name = isset($current_branding, $current_branding['company_name']) && !empty($current_branding['company_name']) 
    ? htmlspecialchars($current_branding['company_name']) 
    : 'RMS';
?>
<head>
    <meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="">
<title><?php echo $company_name . ' - ' . $page_title; ?></title>
<?php if (isset($current_branding, $current_branding['favicon']) && !empty($current_branding['favicon'])): ?>
    <link rel="icon" href="/breezebay/branding/uploads/<?php echo htmlspecialchars($current_branding['favicon']); ?>?v=<?php echo time(); ?>" type="image/x-icon">
<?php endif; ?>

<!-- Custom fonts for this template-->
<link href="/breezebay/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
<link
    href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
    rel="stylesheet">

<!-- Custom styles for this template-->
<link href="/breezebay/css/sb-admin-2.min.css" rel="stylesheet">
<link href="/breezebay/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>