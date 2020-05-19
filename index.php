<?php
/*
This is the offline web app that users will see.
It makes extensive use of localstorage API
It allows them to set their contact details if they want to include them in the QR code.

We might be able to make this a single static page of HTML, calling some dependencies from CDN

*/
?>
<!doctype html>
<html lang="en">
<head>
<link rel="manifest" href="webmanifest.json">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/flatly/bootstrap.min.css">
    <title>Bump Contact Diary</title>
</head>
<body>



<nav class="navbar navbar-expand-sm  navbar-dark bg-dark">
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
  <ul class="navbar-nav nav">
      <li class="nav-item">
  <a class="navbar-brand nav-link" href="#home" data-toggle="tab" data-target="#home">Bump</a>
  </li>
      <li class="nav-item">
        <a data-toggle="tab" class="nav-link" href='#deets' data-target="#deets">I am</a>
      </li>
      <li class="nav-item">
        <a data-toggle="tab" class="nav-link" href='#contacts' data-target="#contacts">I bumped into</a>
      </li>
      <li class="nav-item">
        <a data-toggle="tab" class="nav-link" href='#sick' data-target="#sick">I am unwell</a>
      </li>
      <li class="nav-item">
        <a data-toggle="tab" class="nav-link" href='#share' data-target="#share">Share Bump</a>
      </li>
      <li class="nav-item">
        <a data-toggle="tab" class="nav-link" href='#debug' data-target="#debug">Debug info</a>
      </li>
  </ul>
</div>
</nav>

<div class="tab-content">
  <div id="home" class="container tab-pane active">
    <div id="bumpQR" style="margin:10px;"></div>
<hr/>
  <div id="loadingMessage">Looking for camera. . .</div>
  <canvas id="vidcanvas" hidden></canvas>
  <div id="output" hidden>
    <div id="outputMessage">No Bump Yet.</div>
    <div hidden><b>Data:</b> <span id="outputData"></span></div>
  </div>
  </div>
  <div id="deets" class="container tab-pane fade">
    <h3>Set your details</h3>

These are stored locally, and never sent to a server. You don't need to fill them in at all, but if you do you can share them as people scan you.
The big switch at the top controls whether they are shared or not shared, so you can turn it on or off whenever you like.
When you are sharing your contact details your QR code gets more detailed, and is blue.
<form>
  <div class="form-group">
    <div class="custom-control custom-switch">
      <input type="checkbox" class="custom-control-input" id="shareconsent" checked="">
      <label class="custom-control-label" for="shareconsent">Share Your Details</label>
    </div>
    <label for="myName"></label>
    <input type="text" class="form-control" id="myName" aria-describedby="nameHelp" placeholder="Enter name">
    <small id="nameHelp" class="form-text text-muted">This is only shared if you want</small>
    <label for="myPhone"></label>
    <input type="text" class="form-control" id="myPhone" aria-describedby="phoneHelp" placeholder="Enter a phone number">
    <small id="phoneHelp" class="form-text text-muted">This is only shared if you want</small>
    <label for="myEmail"></label>
    <input type="email" class="form-control" id="myEmail" aria-describedby="emailHelp" placeholder="Enter email">
    <small id="emailHelp" class="form-text text-muted">This is only shared if you want</small>
  </div>
</form>

  </div>
  <div id="contacts" class="container tab-pane fade">
    <h3>These people you bumped into shared their contact details with you.</h3>
You can delete any of them, you will still be alerted if necessary.
<ul class="list-group" id="contactlist">
</ul>

  </div>
  <div id="share" class="container tab-pane fade">
    <h3>Share Bump</h3>
    Any phone can open the bump application with no installation required.
<hr/>
    <div id="webaddress"></div>
    https://bumpinto.eu
<p>Bump is not an official application, more of a privacy preserving proof of concept.</p>
  </div>
  <div id="sick" class="container tab-pane fade">
    <h3>Confirm Diagnois</h3>
If you have a diagnosis code you can scan it on the main page just like scanning a person and Bump will upload your recent interactions to the server.
It won't include any details about who you are, or who you have met, just the last 14 days of interaction codes.
Ideally there would be no possibility of self-diagnosis as that allows people to be idiots and scare others needlessly. Not clear what they would gain from this, but people are idiots.
For testing purposes, you can go to https://www.bumpinto.eu/doctor.php to get a diagnosis code to scan.
  </div>

  <div id="debug" class="container tab-pane fade">
    This is all the stuff in local storage
    <ul class="list-group" id="debugstorage">
    </ul>
  </div>
</div>


<script src="qrcodejs/qrcode.min.js"></script>
<script src="https://cozmo.github.io/jsQR/jsQR.js"></script>
<script src="https://unpkg.com/uuid@latest/dist/umd/uuidv4.min.js"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.25.3/moment.min.js'></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<script src="bump.js"></script>
</body>
</html>
