var incubationperiod=14; //this would change based on the science, probably need to allow time for a reporting delay
var bumpdb=''; //the database is in global scope


$().ready(function(){

  if (!('indexedDB' in window)) {
    window.alert("This browser doesn't support IndexedDB so Bump can't work");
    return;
  }
  var dbPromise=indexedDB.open('bump',1); //the version number is an integer, incrementing it would be to change the object stores

  dbPromise.onerror=function(event){
    //this could happen in incognito mode, or if the device asks permission to create an indexeddb
    //
    window.alert("Failed to create a local contacts database on this device, the application isn't going to work.");
  }
  dbPromise.onsuccess=function(event){
    bumpdb=dbPromise.result;
    loadbump(); //this sets up the main page with the QR code and video, we first call it when the database is ready to start
  }
  dbPromise.onupgradeneeded=function(event){
    //creating a few buckets in the database to store different types of object
    //this is called on first use or schema update
    var upgradeDB=event.target.result;
    if(!upgradeDB.objectStoreNames.contains('settings')){
      var deetsdb=upgradeDB.createObjectStore('settings');
    }
    if(!upgradeDB.objectStoreNames.contains('interactions')){
      upgradeDB.createObjectStore('interactions',{keyPath:['interactionid']});
    }
    if(!upgradeDB.objectStoreNames.contains('contacts')){
      upgradeDB.createObjectStore('contacts',{keyPath:['interactionid']});
    }
  }



  //make the tab navigation work
  $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
    console.log(e.target) // newly activated tab
  });

  //but we have to call it the first time when there is no tab event
  $('a[data-target="#home"]').on('show.bs.tab', function (e) {
    console.log("create new interaction");
    loadbump();
  });

  $('a[data-target="#home"]').on('hide.bs.tab', function (e) {
    //on leaving the bump tab turn off the camera
    //why isn't this firing on the first departure from the active tab?
    video.srcObject.getTracks().forEach(track => track.stop());
  });

  $('a[data-target="#deets"]').on('hide.bs.tab', function (e) {
    console.log("save deets");
    deets={};
    deets.name=$("#myName").val();
    deets.phone=$("#myPhone").val();
    deets.email=$("#myEmail").val();
    deets.consent=$("#shareconsent").prop("checked");
    var reqest=bumpdb.transaction(["settings"],"readwrite")
       .objectStore("settings")
       .put(deets,"deets");
  });

  $('a[data-target="#deets"]').on('show.bs.tab', function (e) {
    console.log("load deets");
     var request=bumpdb.transaction(["settings"])
      .objectStore("settings")
      .get("deets");
      request.onsuccess=function(event){
       var deets=request.result;
       if (deets){
        console.log(deets);
        $("#myName").val(deets.name);
        $("#myPhone").val(deets.phone);
        $("#myEmail").val(deets.email);
        $("#shareconsent").prop("checked",deets.consent);
        }
      }
  });

  $('a[data-target="#contacts"]').on('show.bs.tab', function (e) {
loadcontacts();
  });


  $('a[data-target="#debug"]').on('show.bs.tab', function (e) {
    console.log("load debug");
    // this is just for transparency, will probably be visible only with a URL parameter
    $("#debugstorage").html('');
    Object.keys(localStorage).forEach(function(key){
      $("#debugstorage").append(  '<li class="list-group-item d-flex flex-row"><b>'+key+':</b> '+localStorage.getItem(key)+'</li>');
    });

  })


  //populate the QR code on the share page
  new QRCode(document.getElementById("webaddress"),{text:"https://bumpinto.eu",width:100, height:100});

  //when we load up, initialise the local storage retrieving any user details that may have been stored

});



function loadcontacts(){
    console.log("load contacts");
    $("#contactlist").html(''); //clearing the list so we can repopulate it
    Object.keys(localStorage).forEach(function(key){
      //if this is a contact key we want to show a pretty info card about this contact who shared their details with us
      try {
        lsitem=JSON.parse(localStorage.getItem(key));
        if(lsitem.contact){
          $("#contactlist").append(  '<li class="list-group-item d-flex flex-row"><div class="d-flex flex-column flex-lg-row flex-grow-1 justify-content-around">'
         	+'<h3>' + lsitem.contact.name + '</h3>'
         	+'<a href="tel:' + lsitem.contact.phone + '">' + lsitem.contact.phone + '</a>'
         	+'<a href="mail:' + lsitem.contact.email + '">' + lsitem.contact.email + '</a>'
		+'</div>'
        	+'<button type="button" class="btn btn-outline-secondary flex-shrink-1" onclick="localStorage.removeItem(\''+key+'\');loadcontacts()">&#x1f5d1;</button>'
		+'</li>');
        }
      }catch(e){
        //this shouldn't happen as everything we store in the localstorage parses as JSON
        console.log(e);
        console.log(key);
      }
    });
}


    //this deals with the camera on the device
    //we put the camera image onto a canvas, then look at the canvas to find a QR code
    //if we find one we store the interactionid in todays bucket of interactions
    //if there are business card details we add them to our informative collection of contacts

    var video = document.createElement("video");
    var canvasElement = document.getElementById("vidcanvas");
    var canvas = canvasElement.getContext("2d");
    var loadingMessage = document.getElementById("loadingMessage");
    var outputContainer = document.getElementById("output");
    var outputMessage = document.getElementById("outputMessage");
    var outputData = document.getElementById("outputData");

function loadbump(){
  //we start by registering a new interaction and saving it to the storage
  var interactionid=uuidv4();
  var today=moment().format('yyyyMMDD');
  //add that uuid to the list of interactions today - in a non-sequential order so we sort it.
  var interactionstoday=JSON.parse(localStorage.getItem(today));
  if(!interactionstoday){
      //this is the first time the app has been used to record an interaction today
      //initialise the array of interactions today
      //we always store the uuid even if nobody scans it, this means the device has no information about the number of people met
      interactionstoday=[];
  }
  interactionstoday.push(interactionid);
  localStorage.setItem(today,JSON.stringify(interactionstoday.sort()));
  $('#bumpQR').html('');
     var request=bumpdb.transaction(["settings"])
      .objectStore("settings")
      .get("deets");
      request.onsuccess=function(event){
       var deets=request.result;
       if (deets && deets.consent){
          //QR code with contact details is blue. Not red because it isn't a danger, and because people are red/green colourblind  
          new QRCode(document.getElementById("bumpQR"),{text:JSON.stringify({interaction:interactionid,deets:deets}),colorDark:"#000066"});
        }else{
          //they don't currently want to give out info, so we don't. QR code is dark green
          new QRCode(document.getElementById("bumpQR"),{text:JSON.stringify({interaction:interactionid}),colorDark:"#006600"});
        }
      }


    // Use facingMode: environment to attemt to get the front camera on phones
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(function(stream) {
      video.srcObject = stream;
      video.setAttribute("playsinline", true); // required to tell iOS safari we don't want fullscreen
      video.play();
      requestAnimationFrame(tick);
    });
}



    function drawLine(begin, end, color) {
      canvas.beginPath();
      canvas.moveTo(begin.x, begin.y);
      canvas.lineTo(end.x, end.y);
      canvas.lineWidth = 4;
      canvas.strokeStyle = color;
      canvas.stroke();
    }


    function tick() {
      loadingMessage.innerText = "... Loading video..."
      if (video.readyState === video.HAVE_ENOUGH_DATA) {
        loadingMessage.hidden = true;
        canvasElement.hidden = false;
        outputContainer.hidden = false;

        canvasElement.height = video.videoHeight;
        canvasElement.width = video.videoWidth;
        canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
        var imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
        var code = jsQR(imageData.data, imageData.width, imageData.height, {
          inversionAttempts: "dontInvert",
        });
        if (code) {
          drawLine(code.location.topLeftCorner, code.location.topRightCorner, "#FF3B58");
          drawLine(code.location.topRightCorner, code.location.bottomRightCorner, "#FF3B58");
          drawLine(code.location.bottomRightCorner, code.location.bottomLeftCorner, "#FF3B58");
          drawLine(code.location.bottomLeftCorner, code.location.topLeftCorner, "#FF3B58");
          outputMessage.hidden = true;
          outputData.parentElement.hidden = false;
          outputData.innerText = code.data;
          try{
            var result=JSON.parse(code.data);
            if (result.diagnosis){
              //we just scanned a diagnosis code issued to us by a doctor. That means we need to upload our contacts to the server
              window.alert ("You have been diagnosed. Your recent interactions will be sent to the server, but not their contact details.");
              window.alert ("People you bumped into will be notified that they met someone who has now been diagnosed.");
              var foundQR=true;
              //each track (audio and video) of the stream needs to be stopped to turn off the camera
              video.srcObject.getTracks().forEach(track => track.stop());
              $("#vidcanvas").hide(3000); //having read a QR code we remove the image to show we did something.
              
            }else if(result.interaction){
              console.log ("you have bumped into someone");
              console.log(result);
              var foundQR=true;
              //each track (audio and video) of the stream needs to be stopped to turn off the camera
              video.srcObject.getTracks().forEach(track => track.stop());
              $("#vidcanvas").hide(3000); //having read a QR code we remove the image to show we did something.
              //we do need to be a little bit careful of this information that has been provided by someone else
              //don't want them to be able to show us some kind of evil QR
              //first we store the interaction to todays bundle of interactions
              var today=moment().format('yyyyMMDD');
              var interactionstoday=JSON.parse(localStorage.getItem(today));
              if(!interactionstoday){
                interactionstoday=[];
              }
              interactionstoday.push(result.interaction);
              localStorage.setItem(today,JSON.stringify(interactionstoday.sort()));
              //if there are details we can save the contact
              if(result.deets){
                localStorage.setItem(result.interaction,JSON.stringify({'contact':result.deets}));
              }

            }
            //if there was no diagnosis or interaction code then we found some other QR code, which we can ignore
          }catch(e){
            console.log("These are not the QR codes you are looking for.");
          }

        
          //need to think about whether anything bad could come from scanning an evil QR code
          //it would have to parse as JSON
          //there should be an interaction code
        } else {
          outputMessage.hidden = false;
          outputData.parentElement.hidden = true;
        }
      }
      if(!foundQR){
        requestAnimationFrame(tick);
      }
    }
