<?php
include 'config.php';

// Get record by ID
$id = intval($_POST['id'] ?? ($_GET['id'] ?? 0));
$manualOR = $_POST['or_number'] ?? null;

$res = $conn->query("SELECT * FROM payments WHERE id=$id");
$row = $res->fetch_assoc();

// Use manual OR if provided
// $orNumber = $manualOR && trim($manualOR) !== "" ? $manualOR : $row['reference_no'];

/**
 * Convert number into words (ALL CAPS, with AND instead of commas)
 */
function numberToWords($number, $isRoot = true) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $negative    = 'negative ';
    $dictionary  = array(
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        1000 => 'thousand',
        1000000 => 'million',
        1000000000 => 'billion'
    );

    if (!is_numeric($number)) return false;
    if ($number < 0) return $negative . numberToWords(abs($number), false);

    $string = $fraction = null;

    if (strpos((string)$number, '.') !== false) {
        list($number, $fraction) = explode('.', (string)$number);
    }

    $number = (int)$number;

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) $string .= $hyphen . $dictionary[$units];
            break;
        case $number < 1000:
            $hundreds  = (int) ($number / 100);
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= ' ' . numberToWords($remainder, false); // ✅ no pesos here
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits, false) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $conjunction . numberToWords($remainder, false);
            }
            break;
    }

    // ✅ Add "pesos only" at the root call only
    if ($isRoot) {
        if ($fraction !== null && is_numeric($fraction) && intval($fraction) > 0) {
            $fraction = str_pad($fraction, 2, "0");
            $string .= " pesos and " . numberToWords(intval($fraction), false) . " centavos only";
        } else {
            $string .= " pesos only";
        }
        return strtoupper($string);
    }

    return $string;
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Receipt Print</title>
  <style>
    body { margin:0; }
    #receipt {
      position: relative;
      width: 100mm;   /* match template */
      height: 200mm;
      margin: 0 auto;
      background: url('resibo.png') no-repeat;
      background-size: 100% 100%;
    }
    .field {
      position: absolute;
      font-size: 11px;
      font-family: Arial, sans-serif;
    }
    /* Fixed fields */
    #date   { left: 10.5mm; top: 49.9mm; white-space: nowrap; }
    #payee  { left: 9.5mm;  top: 65.7mm; white-space: nowrap; }
    #orNo   { left: 70mm;   top: 45mm; font-weight:bold; white-space: nowrap; }
    #total  { left: 70.5mm; top:132.2mm; font-weight:bold; white-space: nowrap; }
    #words  { 
      left: 7.9mm;  
      top:140.4mm; 
      width: 90mm; 
      line-height: 1.2em; 
      white-space: normal;
      word-wrap: break-word;
    }
    #cashX  { left:21.5mm;  top:150.8mm; font-weight:bold; white-space: nowrap; }
  </style>
</head>
<body onload="window.print()">
  <div id="receipt">
    <div id="date"  class="field"><?= htmlspecialchars($row['date']) ?></div>
    <div id="payee" class="field"><?= htmlspecialchars($row['payee']) ?></div>
    <!-- <div id="orNo"  class="field">OR: <?= htmlspecialchars($orNumber) ?></div> -->

    <?php
    // Starting Y positions for dynamic rows
    $yStartAcct = 81.5; 
    $yStartAmt  = 81.5; 
    $rowGap     = 6;    

    for ($i=1; $i<=8; $i++) {
        $acct  = $row["account_name$i"];
        $amt   = $row["amount$i"];

        if ($acct || $amt) {
            $acctY = $yStartAcct + (($i-1) * $rowGap);
            $amtY  = $yStartAmt  + (($i-1) * $rowGap);

            echo '<div class="field" style="left:9.5mm;top:'.$acctY.'mm;white-space:nowrap;">'.htmlspecialchars($acct).'</div>';
            echo '<div class="field" style="left:70.0mm;top:'.$amtY.'mm;white-space:nowrap;">'.number_format($amt,2).'</div>';
        }
    }
    ?>

    <div id="total" class="field">₱ <?= number_format($row['total'],2) ?></div>
    <div id="words" class="field">
      <?= numberToWords($row['total']) ?>
    </div>
    <div id="cashX" class="field">X</div>
  </div>
</body>
</html>
