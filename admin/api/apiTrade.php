<?php
/*
Trading Strategy

Restrictions
* Rest period - after a sell, do not buy for at least 12 hours
prevents constant buy/sell orders and losing money 

api_trade_data fields
* last_action - buy/sell 
* trade_signal - rest = hold for 12 hours
* last_updated - when the signal was last updated 
*/

include('apiPrices.php');
/*
function whichCurrency($pair) {
    switch($pair) {
        case 'btc_usd':
            $currency = 'btc';
            break;
        case 'ltc_usd':
            $currency = 'ltc';
            break;
        case 'nvc_usd':
            $currency = 'nvc';
            break;
        case 'nmc_usd':
            $currency = 'nmc';
            break;
        default:
            $currency = 'usd';
    }
    
    return $currency;
}
*/

function sendMail($sendEmailBody) {
    $headers = 'From: alerts@bestpayingsites.com' . "\r\n" .
    'Reply-To: alerts@bestpayingsites.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    $emailTo = '17182136574@tmomail.net';
    $mailSent = mail($emailTo, 'BTC-E Trade', $sendEmailBody, $headers);
    
    if($mailSent) {
        $subject = 'Text alert sent';
    }
    else {
        $subject = 'Text alert NOT sent';
    }
    
    $emailTo = 'louie.benjamin@gmail.com'; 
    mail($emailTo, $subject, $sendEmailBody, $headers);
}

function makeTrade($tradeAmt, $pair, $action, $latestPrice) {
    
    global $api, $totalBalance;
    
    if($action == 'buy') {
        
        try {
            $tradeResult = $api->makeOrder($tradeAmt, $pair, BTCeAPI::DIRECTION_BUY, $latestPrice);
        } 
        catch(BTCeAPIInvalidParameterException $e) {
            echo $e->getMessage();
        } 
        catch(BTCeAPIException $e) {
            echo $e->getMessage();
        }
    }
    else { //sell   
        try {
            $tradeResult = $api->makeOrder($tradeAmt, $pair, BTCeAPI::DIRECTION_SELL, $latestPrice);  
        } 
        catch(BTCeAPIInvalidParameterException $e) {
            echo $e->getMessage();
        } 
        catch(BTCeAPIException $e) {
            echo $e->getMessage();
        }
    }

    if ($tradeResult['success'] == 1) {
        echo $msg = $action.' '.$tradeAmt.' of '.$pair.' at price '.$latestPrice."\n".'balance: '.$totalBalance;
        sendMail($msg);
    };
}

/*
Updates the last action the program performed - buy or sell 
 * $last_action = array(
 *      last_action - buy or sell
 *      last_price - price of trade
 *      trade_signal - rest or stop loss
 *      currency - ltc or btc
 *      exchange - btc-e or bitfinex
 *      last_updated - timestamp
 * )
*/
function update_last_action($last_action_data) {

    global $db, $context;
    
    $queryD = 'UPDATE '.$context['tradeDataTable'].' SET 
        last_action="'.$last_action_data['last_action'].'",
        last_price="'.$last_action_data['last_price'].'",            
        trade_signal="'.$last_action_data['trade_signal'].'",
        last_updated="'.date('Y-m-d H:i:s', time()).'"
        WHERE currency="'.$last_action_data['currency'].'" 
            AND exchange="btc-e"';
    
    $queryD = $db->query($queryD);
}



$debug = $_GET['debug'];
global $totalBalance;

if($debug == 1) {
    echo '<< debug mode >>'; $newline = '<br>';
}
else {
    $newline = "\n";
}


//get options from api_options
$queryO = $c->query('SELECT * FROM '.$context['optionsTable'].' ORDER BY opt');

echo $newline.$newline.'api_options'.$newline;
foreach($queryO as $opt) { 
    echo '['.$opt['opt'].': '.$opt['setting'].']';

    $btc_e_option[$opt['opt']] = $opt['setting'];
}


$acctInfo = $api->apiQuery('getInfo');
$acctFunds = $acctInfo['return']['funds'];

$currency = $btc_e_option['btc_e_currency'];
$pair = $currency.'_usd';

//database field
$price_field = 'btce_'.$currency; 

$queryMA = $c->query('SELECT (AVG('.$price_field.')) AS ma_7 FROM '.$context['pricesTable'].' WHERE count <= 7');
foreach($queryMA as $row) { 
    $ma_7 = $row['ma_7']; }

$queryMA = $c->query('SELECT (AVG('.$price_field.')) AS ma_30 FROM '.$context['pricesTable'].' WHERE count <= 30');
foreach($queryMA as $row) { 
    $ma_30 = $row['ma_30']; }


//echo $pair;
$latestPrice = $allPrices[$pair]['lastPrice'];
$btcPrice =  $allPrices['btc_usd']['lastPrice'];
$ltcPrice =  $allPrices['ltc_usd']['lastPrice'];


//sum total of account balance in all currencies
$totalBalance = $acctFunds['usd'] + $acctFunds['btc'] * $btcPrice + $acctFunds['ltc'] * $ltcPrice;

//how much btc/ltc you can buy
$tradable['btc'] = number_format($acctFunds['usd']/$btcPrice, 8); 
$tradable['ltc'] = number_format($acctFunds['usd']/$ltcPrice, 8); 


echo $newline.$newline;
echo 'current prices: '.$newline.'[btc: '.$btcPrice.'] [ltc: '.$ltcPrice.']'.$newline.$newline;
echo 'account balance: '.number_format($totalBalance, 2).$newline;
echo 'btc: '.number_format($acctFunds['btc'], 4).' - ltc '.number_format($acctFunds['ltc'], 4).' - usd: '.number_format($acctFunds['usd'], 2).$newline;
echo 'tradeable btc: '.number_format($tradable['btc'], 4).' - tradeable ltc: '.number_format($tradable['ltc'], 4).$newline;
echo '7_hour_sma: '.number_format($ma_7, 4).' - 30_hour_sma: '.number_format($ma_30, 4).$newline.$newline;

if($btc_e_option['btc_e_trading'] == 1) {
    echo 'btc_e trading is on'.$newline;
}
else {
    echo 'btc_e trading is off'.$newline;
}


//determine if 12 hour rest period is over
$queryT = 'SELECT * FROM '.$context['tradeDataTable'].' WHERE currency = "'.$currency.'"
    AND exchange = "btc-e"';
$resT = $db->query($queryT);

foreach($resT as $t) { 
    $last_updated = $t['last_updated'];
    $trade_signal = $t['trade_signal'];
}

echo 'last_updated: '.$t['last_updated'].' - time now: '.date('Y-m-d H:i:s', time()).' - ';

$rest_period_over = 1; $hours = 0;
if($trade_signal == 'rest') {
    $datetime1 = date_create($t['last_updated']); //time of last action
    $datetime2 = date_create(date('Y-m-d H:i:s', time())); //time now
    $interval = date_diff($datetime1, $datetime2); //get the difference between the 2 timestamps
    $hours = $interval->format('%H'); //get the hours from the difference
    
    if($hours >= 12) { //rest period = 12
        $rest_period_over = 1; 
        
        $last_action_data = array(
            'last_action' => 'sell',
            'trade_signal' => '',
            'currency' => $currency,
        );
        
        update_last_action($last_action_data); 
    }
    else {
        $rest_period_over = 0;
    }
}
echo 'diff: '.$hours.' hours - rest period over: '.$rest_period_over.$newline;


if($btc_e_option['btc_e_trading'] == 1) //if trading is on - from options screen
if($rest_period_over == 1) //12 hour rest period is over 
if($ma_7 > $ma_30) { //uptrend signal
   
    if($debug == 1) //do not trade in debug mode
        $tradeAmt = '0.01';
    else
        $tradeAmt = $tradable[$currency]; //amount of tradatable btc/ltc
    
    echo '[buy] [tradeAmt '.$tradeAmt.'] ['.$pair.']'.$newline;
              
    $last_action_data = array(
        'last_action' => 'buy',
        'last_price' => $latestPrice,
        'trade_signal' => '',
        'currency' => $currency,
        'exchange' => 'btc-e'
    );
    
    if($debug != 1) //do not trade in debug mode
    if($acctFunds['usd'] > 0.01) { //if there is USD available for trading 
        makeTrade($tradeAmt, $pair, 'buy', $latestPrice); 
        update_last_action($last_action_data);
    }
    else {
        echo 'No balance to trade';
    }
    
    //========== stop loss ==========
/*    how to sell near the top
- set stop loss - if price current price is above buy price
- store the last buy price <==
- get the ATH after last buy action 
- after stop loss hits, rest for 12 hours */
    if($latestPrice > $ma_30) { //if current price is above MA_30
        //where time now > api_prices.time
        $queryMax = $c->query('SELECT (MAX('.$price_field.')) AS ATH FROM '.$context['pricesTable'].' WHERE count <= 10');
        foreach($queryMax as $row) { 
            $ATH = $row['ATH']; }

        //get 2.5% below ATH - if price under 2.5% then sell
        $stop = $ATH - $ATH * 0.025;
        echo 'ATH: '.number_format($ATH, 4).' - stop loss: '.number_format($stop, 4).' ';
        
        if($latestPrice <= $stop) { //price is under stop loss - sell!
            echo ' - stop loss exit'; 
            $tradeAmt = $acctFunds[$currency];
            makeTrade($tradeAmt, $pair, 'sell', $latestPrice);
            
            $last_action_data = array(
                'last_action' => 'sell',
                'last_price' => $latestPrice,
                'trade_signal' => 'rest',
                'currency' => $currency,
                'exchange' => 'btc-e'
            );
            update_last_action($last_action_data);
        }
        else echo ' - no exit yet';
    } //========== stop loss ==========
    
}//uptrend
else if ($ma_7 < $ma_30) { //downtrend signal
    
    if($debug == 1)
        $tradeAmt = 0.01;
    else
        $tradeAmt = $acctFunds[$currency]; //amount of btc/ltc in the account
            
    echo '[sell] [tradeAmt '.$tradeAmt.'] ['.$pair.'] '.$newline;
    
    $last_action_data = array(
        'last_action' => 'sell',
        'last_price' => $latestPrice,
        'trade_signal' => 'rest',
        'currency' => $currency,
        'exchange' => 'btc-e'
    );
    
    if($debug != 1) //do not trade in debug mode
    if($acctFunds[$currency] > 0.01) {
        makeTrade($tradeAmt, $pair, 'sell', $latestPrice);
        update_last_action($last_action_data);
    }
    else {
        echo 'No balance to trade';
    }
}


?>