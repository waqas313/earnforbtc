<?php
include('adminCode.php');

if($_POST[update])
{
    $dbOpt = array(
        'memHeader' => $_POST['memHeader'],
        'memFooter' => $_POST['memFooter'],
        'memAreaUpsell' => $_POST['memAreaUpsell'],
        'memAreaContent' => $_POST['memAreaContent'],
        'memUpsellProductID' => $_POST['memUpsellProductID'],
        'memUpsellFile' => $_POST['memUpsellFile'],
        'memUpsellBackup' => $_POST['memUpsellBackup'],
        'memUpsellBackupID' => $_POST['memUpsellBackupID'],
        'memUpsellBackupFile' => $_POST['memUpsellBackupFile']
    );
    
    foreach($dbOpt as $opt => $setting)
    {
        $setting = addslashes(trim($setting)); 
        
        $updS = 'update settings set setting="'.$setting.'" where opt="'.$opt.'"';
        mysql_query($updS) or print(mysql_error()); 
    }
}

$selM = 'select * from settings order by opt';
$resM = mysql_query($selM, $conn); 

while($m = mysql_fetch_assoc($resM))
{
    $m['setting'] = stripslashes($m['setting']);
    $memOptions[$m['opt']] = $m['setting'];  
}

//get product info 
$selP = 'select * from products order by id'; 
$resP = mysql_query($selP, $conn) or die(mysql_error()); 

while($p = mysql_fetch_assoc($resP))
{
    $p = formatFields($p); 
    
    $pick = '';
    if($p[id] == $memOptions[memUpsellProductID]) {
        $pick = 'selected'; 
        $itemName = $p[itemName]; 
    }
    $upsellProductOptions .= '<option value="'.$p[id].'" '.$pick.'>'.$p[itemName].'</option>';

    $backupPick = '';
    if($p[id] == $memOptions[memUpsellBackupID]) {
        $backupPick = 'selected';
    }
    
    $backupProductOptions .= '<option value="'.$p[id].'" '.$backupPick.'>'.$p[itemName].'</option>';
    
}    
    
//upsell is enabled 
if($memOptions['memAreaUpsell'] == 'on')
{
    $upsellCheck = 'checked';  //upsell product 
    
    if($memOptions['memUpsellBackup'] == 'on') //2nd upsell product
        $memUpsellBackupCheck = 'checked'; 
    else {   
        $skipUpsellCheck = 'checked'; 
        $upsellBackupDis = 'disabled'; 
    }
    
    $upsellOptions = '<tr>
    <td colspan=2><p>&nbsp;</p> If member is already a customer of '.$itemName.' </td>
    </tr>
    <tr>
        <td>Skip upsell </td>
        <td>
            <div title="header=[Option to skip upsell] body=[If the user is already a customer of the upsell product, check this box to skip upsell. User will log in and not see upsell page] "><img src="'.$helpImg.'" />
            <input type=checkbox name=skipUpsell '.$skipUpsellCheck.' />
            </div>
            </td>
    </tr>
    <tr>
        <td>Use another product </td>
        <td>
            <div title="header=[User Another Upsell Product] body=[If the user is already a customer of the upsell product, check this box to use another upsell in place of the above product] "><img src="'.$helpImg.'" /> 
         <input type=checkbox name=memUpsellBackup '.$memUpsellBackupCheck.' /></td>
    </tr>
    <tr>
        <td>Use this product </td>
        <td>
              <div title="header=[Upsell Product] body=[This is the 2nd upsell product] "><img src="'.$helpImg.'" />
            <select '.$upsellBackupDis.' name=memUpsellBackupID>
            '.$backupProductOptions.'</select>
            </div>
        </td>
    </tr>
    <tr>
        <td>Use this file </td>
        <td>
              <div title="header=[Option to skip upsell] body=[Location of the upsell sales page to use. This file is relative to the members area - for example, if the file is in members/upsell2.html, 
                    put in upsell2.html] "><img src="'.$helpImg.'" />
            <input '.$upsellBackupDis.' type=text name=memUpsellBackupFile value="'.$memOptions['memUpsellBackupFile'].'" /></td>
    </tr>';
}
else {      //upsell is disabled 
	$productDis = 'disabled'; 
}

?>
<form method=post>
<table>
<tr valign=top>
    <td>
        <div class="moduleBlue"><h1>Members Area Template</h1>
        <div class="moduleBody">
            <p><?=$msg?></p>
            <table>
            <tr>
                <td>Header File</td>
                <td>
                    <div title="header=[Header File] body=[File to be included as the header, leave blank if you don't want to use a header] "><img src="<?=$helpImg?>" />
                        <input type=text class=activeField name=memHeader size="30" value="<?=$memOptions['memHeader']?>"/></td>
            </tr>
            <tr>
                <td>Footer File</td>
                <td>
                    <div title="header=[Footer File] body=[File to be included as the footer, leave blank if you don't want to use a footer] "><img src="<?=$helpImg?>" />
                        <input type=text class=activeField name=memFooter size="30" value="<?=$memOptions['memFooter']?>" /></td>
            </tr>
            <tr>
                <td colspan=2 align=center>
                    <input type=submit name=update value=" Update Members Area " />
                </td>
            </tr>
            </table>
        </div>
        </div>
        
        <p>&nbsp;</p>
        
        <div class="moduleBlue"><h1>Members Area Upsell Page</h1>
        <div class="moduleBody">
            <table>
            <tr>
                <td>Use Upsell?</td>
                <td>
                    <div title="header=[Option to Upsell the User] body=[Check this box if you want to use an upsell product, the upsell is shown after the user logs into the members area] "><img src="<?=$helpImg?>" />
                    <input type=checkbox name=memAreaUpsell <?=$upsellCheck?> />
                    </div>
                </td>
            </tr>
            <tr>
                <td>Use this product</td>
                <td>
                    <div title="header=[Which product to upsell] body=[If you checked the upsell option, which product do you want to use for the upsell?] "><img src="<?=$helpImg?>" />
                        <select <?=$productDis?> name=memUpsellProductID>
                        <?=$upsellProductOptions ?>
                        </select>
                    </div>
                </td>
            </tr>
                <td>Use this file </td>
                <td>
                    <div title="header=[Upsell sales page] body=[Location of the upsell sales page to use. This file is relative to the members area - for example, if the file is in members/upsell.html, 
                    put in upsell.html] "><img src="<?=$helpImg?>" />
                    <input <?=$productDis?> type=text name=memUpsellFile value="<?=$memOptions['memUpsellFile']?>" /></div>
                </td>
            <tr>
            </tr>
                <?=$upsellOptions?>
            <tr>
                <td colspan=2 align=center>
                    <input type=submit name=update value=" Update Members Area " />
                </td>
            </tr>
            </table>
        </div>
        </div>
        
    </td>
    <td width="20px"></td>
    <td>
        <div class="moduleBlue"><h1>Members Area Custom Content</h1>
        <div class="moduleBody">
        <center>
            <textarea name=memAreaContent rows=20 cols=45><?=$memOptions['memAreaContent']?></textarea>
            <br /><br />
            <input type=submit name=update value=" Update Members Area " />
        
        </center>
        </div>
        </div>
        
    </td>
</tr>
</table>
</form>

<p>&nbsp;</p>

<?
include('adminFooter.php');  ?>