<?php
/*
this allows people to upload codes accompanied with a valid diagnosis code
this provides recent codes uploaded by diagnosed people

we are dealing with the interactions table that has these columns:
interactionid
interactiondate
reporteddate
*/
if(file_exists('dbcredentials.local.php'))
    include 'dbcredentials.local.php';
else{
    include "dbcredentials.php";
}
$mysqli = new mysqli($server, $user, $password, $database);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	//if we are being called as a POST then we check the diagnosis code is valid and unused
	//then we update the date on the diagnosis code
	//and record all the interactions
	//what do we reply with? Just a return code?
	$diagnoisid=$_POST['diagnosis'];
	$interactions=json_decode($_POST['interactions']);
	//is the diagnosis code valid and unused?
	$stmt = $mysqli->prepare("select * from diagnosis where diagnosisid=?");
	$stmt->bind_param("s",$diagnosisid);
	$stmt->execute();
	$result=$stmt->get_result();
	$row = $result->fetch_array(MYSQLI_ASSOC);
	$reporteddate=date("Y-m-d"); //everything stored as reported today by the server clock.
	if($row){
		//we found a valid diagnosis code, is the date blank?
		if ($row['dateused']){
			//this diagnosis has been used
			//we presumably don't accept additional contacts after diagnoisis as it could be another person using the code
			echo "Diagnosis code already used";
			//does it give anything away that we confirm it is already used?
		}else{
			//we have a diagnosis code that is valid
			//we accept their interactions
			foreach ($interactions as $interaction=>$interactiondate){
				//here we are accepting user supplied data, and we will be putting it in our database
				//we will also be serving it back up to other people
				//so we need to check it is what we are expecting
				//only for the arbitary string though, the date will be sorted by the prepared statement
				//using regex to validate it is a uuid
				if(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',$interaction)){
					$stmt = $mysqli->prepare("insert into interactions (interactionid,interactiondate,reporteddate) values (?,?,?)");
					$stmt->bind_param("s",$interaction);
					//do we need to check the interaction date isn't in the far past or future?
					$stmt->bind_param("d",$interactiondate);
					$stmt->bind_param("d",$reporteddate);
					$stmt->execute();
				}
			}
		}
	}else{
		//no diagnosis code found?
		echo "Diagnoisis code not accepted";
	}

}else{
	//if we are receiving a get then we serve up the last 14 days worth of codes
	//we return some interactions - perhaps starting from a cuttoff date supplied by the client to minimise what we send
	//must remember that interactions can and will be reported out of order
	//as a start we just return the whole lot
	$stmt = $mysqli->prepare("select * from interactions");
	//will need to bind any parameters sent to limit the interactions they want back
	//probably starting from a particular reporteddate
	//
	$stmt->execute();
	$result=$stmt->get_result();
	while($row = $result->fetch_array(MYSQLI_ASSOC)){
		//the client probably doesn't need the reported date, however it isn't a secret
		//as it can be deduced by spotting the first date an interaction appears in the data feed.
		$interactions[]=$row;
	}
	header("Content-type:application/json");
	echo json_encode($interactions);
}
