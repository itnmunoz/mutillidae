<?php
	/*  --------------------------------
	 *  We use the session on this page
	 *  --------------------------------*/
	if (!isset($_SESSION["security-level"])){
		session_start();
	}// end if

	/* ----------------------------------------
	 *	initialize security level to "insecure"
	 * ----------------------------------------*/
	if (!isset($_SESSION['security-level'])){
		$_SESSION['security-level'] = '0';
	}// end if

	/* ------------------------------------------
	 * Constants used in application
	 * ------------------------------------------ */
	require_once('../../includes/constants.php');
	require_once('../../includes/minimum-class-definitions.php');

	function populatePOSTSuperGlobal(){
		$lParameters = Array();
		parse_str(file_get_contents('php://input'), $lParameters);
		$_POST = $lParameters + $_POST;
	}// end function populatePOSTArray

	function getPOSTParameter($pParameter, $lRequired){
		if(isset($_POST[$pParameter])){
			return $_POST[$pParameter];
		}else{
			if($lRequired){
				throw new Exception("POST parameter ".$pParameter." is required");
			}else{
				return "";
			}
		}// end if isset
	}// end function validatePOSTParameter

	function jsonEncodeQueryResults($pQueryResult){
		$lDataRows = array();
		while ($lDataRow = mysqli_fetch_assoc($pQueryResult)) {
			$lDataRows[] = $lDataRow;
		}// end while

		return json_encode($lDataRows);
	}//end function jsonEncodeQueryResults

	try {

	    $lVerb = $_SERVER['REQUEST_METHOD'];

	    switch($lVerb){
	        case "GET":
	            break;
	        case "POST"://create
	            break;
	        case "PUT":	//create or update
	            break;
	        case "DELETE":
	            /* $_POST array is not auto-populated for DELETE method. Parse input into an array. */
	            populatePOSTSuperGlobal();
	            break;
	        default:
	            throw new Exception("Could not understand HTTP REQUEST_METHOD verb");
	            break;
	    }// end switch

	    header("Access-Control-Allow-Origin: {$_SERVER['REQUEST_SCHEME']}://mutillidae.local");
	    header("Access-Control-Max-Age: 600");

    	switch ($_SESSION["security-level"]){
    	    case "0": // This code is insecure. No input validation is performed.
    	        $lProtectAgainstMethodTampering = FALSE;
    	        $lProtectAgainstCommandInjection=FALSE;
    	        $lProtectAgainstXSS = FALSE;
    	        break;

    	    case "1": // This code is insecure. No input validation is performed.
    	        $lProtectAgainstMethodTampering = FALSE;
    	        $lProtectAgainstCommandInjection=FALSE;
    	        $lProtectAgainstXSS = FALSE;
    	        break;

    	    case "2":
    	    case "3":
    	    case "4":
    	    case "5": // This code is fairly secure
    	        $lProtectAgainstMethodTampering = TRUE;
    	        $lProtectAgainstCommandInjection=TRUE;
    	        $lProtectAgainstXSS = TRUE;
    	        break;
    	}// end switch

    	if(isset($_REQUEST["message"])){
    	    $lProtectAgainstMethodTampering?$lMessage = $_POST["message"]:$lMessage = $_REQUEST["message"];
    	}else{
    	    $lMessage="Hello";
    	}

    	if ($lProtectAgainstXSS) {
    	    /* Protect against XSS by output encoding */
    	    $lMessageText = $Encoder->encodeForHTML($lMessage);
    	}else{
    	    $lMessageText = $lMessage; 		//allow XSS by not encoding output
    	}//end if

    	if ($lProtectAgainstCommandInjection) {
    	    $LogHandler->writeToLog("Executed PHP command: echo " . $lMessageText);
    	}else{
    	    $lMessage = shell_exec("echo -n " . $lMessage);
    	    $LogHandler->writeToLog("Executed operating system command: echo " . $lMessageText);
    	}//end if

	}catch(Exception $e){
	    echo $CustomErrorHandler->FormatError($e, "Error setting up configuration on page html5-storage.php");
	}// end try

	try{

		echo '[';
		echo '{"Message":'.json_encode($lMessage).'},';
		echo '{"Method":'.json_encode($lVerb)."},";
		echo '{"Parameters":[';
		echo '{"GET":'.json_encode($_GET)."},";
		echo '{"POST":'.json_encode($_POST)."}";
		echo ']}';
		echo ']';

	} catch (Exception $e) {
		echo $CustomErrorHandler->FormatErrorJSON($e, "Unable to process request to web service ws-user-account");
	}// end try

?>