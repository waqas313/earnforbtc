<?
if($_POST['reset']) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm']; 
    $username = $_POST['username']; 
    
    //password is blank
    if(empty($password))
    {
        $msg = 'Password cannot be blank';
    }
    else 
    {
        if($password != $confirm) //passwords do not match
        {
            $msg = 'Passwords do not match';
        }    
        else //update password
        {
            $upd = 'update users set password="'.$password.'" where username="'.$username.'"';
            $res = mysql_query($upd, $conn) or die(mysql_error());
            
            $msg = 'Successfully updated password';    
        }
    }    
}

if($_POST['forgot']) {
    $username = $_POST['username']; 
    $email = $_POST['email']; 
    
    $activateLink = $websiteURL.'/members/?action=forgot&username='.$username.'&code='.strrev($username); 
    $subject = 'Reset Password - '.$val['businessName'];
    $message = '<p>Hi there, </p>
    
    <p>You or somebody has requested to reset your password for the '.$val['businessName'].'
    Members Area. To reset your password please click on the link below: </p>
    
    <p><a href="'.$activateLink.'">'.$activateLink.'</a></p>
    
    <p>'.$fromName.'<br />
        '.$businessName.'</p>'; 
    
    //username is blank / email is blank 
    if(empty($username))
    {
        $msg = 'Username cannot be blank'; 
    }
    else 
    {
        if(empty($email))
        {
            $msg = 'Email cannot be blank';
        }
        else 
        {
            $selU = 'select username, email from users where username="'.$username.'"';
            $resU = mysql_query($selU, $conn) or die(mysql_error()); 
            $user = mysql_fetch_assoc($resU); 
            
            if(mysql_num_rows($resU) == 0)  //username does not exist
            {
                $msg = 'Username does not exist'; 
            }        
            else     
            {
                if($user['email'] != $email)//username doesn't match email 
                {
                    $msg = 'Username does not match email address'; 
                }
                else //all conditions are cleared 
                {
                    $headers = "From: ".$val['adminEmail']."\n";
                    $headers .= "Content-type: text/html;";     
                
                    //send activation link
                    if(@mail($email, $subject, $message, $headers))
                        $msg = 'An email has been sent to your email address - Please check your inbox now';
                    else
                        $msg = 'Something went wrong - please contact the admin.';
                }
            }
        }
    }
}

if($_GET['code']) //reset passsword
{
    $username = $_GET['username'];
    $code = $_GET['code']; 
    
    //check if code is correct 
    $selU = 'select username from users where username="'.$_GET['username'].'" and username="'.strrev($code).'"';
    $resU = mysql_query($selU, $conn) or die(mysql_error()); 
    
    if(mysql_num_rows($resU) == 0)
    {
        $msg = 'Activation link is invalid'; 
        $disField = 'disabled'; 
    }
    
    $pageContent = '<h1>Reset Password</h1>
    <center>
    <form method="POST">
    <p><font color="red">'.$msg.'</font></p>
    <table class="moduleBlue">
    <tr>
        <th colspan="2" align=center><h1>Enter New Password</h1></th>
    </tr>
    <tr>
        <td>New Password</td>
        <td><input type=text '.$disField.' class="activeField" name="password" size="30" /></td>
    </tr>
    <tr>
        <td>Confirm Password </td>
        <td><input type=text '.$disField.' class="activeField" name="confirm" size="30" /></td>
    </tr>
    <tr>
        <td colspan=2 align=center>
            <input type=submit name=reset value="Submit Form" class="btn danger" '.$disField.' />
            <input type=hidden name=username value="'.$username.'" />
        </td>
    </tr>
    <tr>
        <td align=left><a href="./">Login</a></td>
    </tr>
    </table>    
    </form>
    </center>';
    
    echo '<p>&nbsp;</p>'.$pageContent.'<p>&nbsp;</p>'; 
}
else //forgot password 
{
    include('login.html');
    
    $pageContent = '<center>
	<h1>'.$businessName.' Members Area</h1>
        <form method="POST">
        <p><font color="red">'.$msg.'</font></p>

        <table class="moduleBlue">
        <tr>
            <th colspan="2"><h1>Forgot Password</h1></th>
        </tr>
        <tr>
            <td>Username</td>
            <td><input type=text class="activeField" name="username" size="30" /></td>
        </tr>
        <tr>
            <td>Email Address</td>
            <td><input type=text class="activeField" name="email" size="30" /> </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <input type="submit" class="btn info" value="Forgot" />
                <input type="reset" class="btn info" value="Clear" />
            </td>
        </tr>
        <tr>
            <td align="left"><a href="?action=forgot">Forgot Password</a></td>
            <td align="right"><a href="'.$dir.'members">Login</a></td>
        </tr>
        </table>    
    </form>
    </center>';
}


?>