<?php
/*
This page is purely for testing purposes
It provides a diagnosis code
We have to create the diagnosis code on the server side
*/
ini_set("display_errors","off");
function guidv4(){
    //generates a reasonably good unique ID on windows or linux platforms
    //it doesn't really have to be perfect, and it shouldn't be high volume so we don't need to be too concerned about entropy, or waits for entropy.
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
$diagnosiscode=guidv4();
//save the diagnoisis code in the database
if(file_exists('dbcredentials.local.php'))
    include 'dbcredentials.local.php';
else{
    include "dbcredentials.php";
}
$mysqli = new mysqli($server, $user, $password, $database);
$stmt = $mysqli->prepare("insert into diagnosis (diagnosisid,authority) values (?,'self')");
//creating a diagnosis code in the database, with self as the authority - there could be other diagnosis authorites where people get the diagnosis QR from a doctor
//or from an official test result
$stmt->bind_param("s",$diagnosiscode);
$stmt->execute();


?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <title>Bump Self Diagnosis</title>
</head>
<body>
<nav class="navbar navbar-expand-sm  navbar-dark bg-dark">
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
  <ul class="navbar-nav nav">
      <li class="nav-item">
  <a class="navbar-brand nav-link" href="#home" data-toggle="tab" data-target="#home">Bump Doctor Page</a>
</div>
</nav>


<p>This is a diagnois code that you can scan with your phone</p>
<p>Your phone will then inform the server of your recent contact ids but it will pass no contact information whatsoever</p>
<p>People you have recently bumped into will be informed that someone they met has self-diagnosed.</p>
<p>If they scanned your contact information (blue code) then they will see that you have self-diagnosed.</p>
<p>You might want to contact people in your app directly and anyone else you have recently been in meaningful contact with.</p>
<p>Get well soon, and self isolate to avoid making any new contacts.</p>

    <div id="qrcode" style="margin:10px;"></div>

<script src="qrcodejs/qrcode.min.js"></script>
<script src="https://cozmo.github.io/jsQR/jsQR.js"></script>
<script src="https://unpkg.com/uuid@latest/dist/umd/uuidv4.min.js"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.25.3/moment.min.js'></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<script type="text/javascript">
$().ready(function(){
    var diagnosisid="<?php
echo $diagnosiscode;
?>";
    new QRCode(document.getElementById("qrcode"),JSON.stringify({diagnosis:diagnosisid}));

});

</script>

</body>
</html>
