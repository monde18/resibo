<?php
include 'config.php';
$id = intval($_GET['id'] ?? 0);

$res = $conn->query("SELECT * FROM payments WHERE id=$id");
$row = $res->fetch_assoc();

/**
 * Convert number into words (Pesos + Centavos)
 */
function numberToWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
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
    if ($number < 0) return $negative . numberToWords(abs($number));

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
            if ($remainder) $string .= $conjunction . numberToWords($remainder);
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }

    // Handle centavos
    if ($fraction !== null && is_numeric($fraction) && intval($fraction) > 0) {
        $fraction = str_pad($fraction, 2, "0"); // ensure 2 digits
        $string .= " pesos and " . numberToWords(intval($fraction)) . " centavos";
    } else {
        $string .= " pesos";
    }

    return ucfirst($string) . " only";
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
      white-space: nowrap;
    }
    /* Fixed fields */
    #date   { left: 10.5mm; top: 49.9mm; }
    #payee  { left: 9.5mm;  top: 65.7mm; }
    #total  { left: 70.5mm; top:132.2mm; font-weight:bold; }
    #words  { left: 7.9mm;  top:140.4mm; width:90mm; }
    #cashX  { left:21.5mm;  top:150.8mm; font-weight:bold; }
  </style>
</head>
<body onload="window.print()">
  <div id="receipt">
    <div id="date"  class="field"><?= htmlspecialchars($row['date']) ?></div>
    <div id="payee" class="field"><?= htmlspecialchars($row['payee']) ?></div>

    <?php
    // Y starting pos
    $yStartAcct = 81.5; // first row account top
    $yStartAmt  = 81.5; // first row amount top
    $rowGap     = 6;    // mm gap between rows

    for ($i=1; $i<=8; $i++) {
        $acct  = $row["account_name$i"];
        $amt   = $row["amount$i"];

        if ($acct || $amt) {
            $acctY = $yStartAcct + (($i-1) * $rowGap);
            $amtY  = $yStartAmt  + (($i-1) * $rowGap);

            echo '<div class="field" style="left:9.5mm;top:'.$acctY.'mm;">'.htmlspecialchars($acct).'</div>';
            echo '<div class="field" style="left:70.0mm;top:'.$amtY.'mm;">'.number_format($amt,2).'</div>';
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
