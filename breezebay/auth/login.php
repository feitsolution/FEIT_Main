<?php
session_start();
include('../include/dbconnection.php');

include('fetch_logo.php');

if(isset($_POST['login']))
{
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
    $query = mysqli_query($con, "SELECT ID, StaffName, StaffRole, Password FROM tblstaff WHERE UserName='$username'");
    $ret = mysqli_fetch_array($query);
    
    if($ret > 0){
        if(password_verify($password, $ret['Password'])){
            $_SESSION['uid'] = $ret['ID'];
            $_SESSION['name'] = $ret['StaffName'];
            $_SESSION['role'] = $ret['StaffRole'];
            if ($ret['StaffRole'] == 'Admin') {
                header('location:../dashboard/admin_dashboard.php');
            } elseif ($ret['StaffRole'] == 'Cashier') {
                header('location:../dashboard/admin_dashboard.php');
            } elseif ($ret['StaffRole'] == 'Waitor') {
                header('location:../waitor/waitor.php');
            } else {
                $msg = "Access Denied. Your role is not permitted to access the system.";
            }
        } else {
            $msg = "Invalid Details.";
        }
    } else {
        $msg = "Invalid Details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../include/header.php'); ?>
</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image" <?php if($login_bg) echo 'style="background-image: url(\''.$login_bg.'\'); background-position: center; background-size: contain; background-repeat: no-repeat;"'; ?>></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                    </div>
                                    <form class="user" method="post">
                                        <p style="font-size:16px; color:red" align="center"> <?php if(isset($msg)){echo $msg;} ?> </p>
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                id="exampleInputEmail" name="username" aria-describedby="emailHelp"
                                                placeholder="Enter Username...">
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user"
                                                id="exampleInputPassword" name="password" placeholder="Password">
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                                <label class="custom-control-label" for="customCheck">Remember
                                                    Me</label>
                                            </div>
                                        </div>
                                        <button type="submit" name="login" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>
                                    </form>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <?php include('../include/script.php'); ?>
</body>

</html>