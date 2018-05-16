<?php

    /**
        This is the landing page for all of the API functions.
        Everything must go through this file.
    **/

    //The API returns JSON, always
    header('Content-Type: application/json');

    //Break up the request
    $endpoint = array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));

    //The endpoint must be at least 3 in length
    if(sizeof($endpoint) < 3){
        echo json_encode( array("status" => 400, "info" => "You must specify an endpoint and a function") );
        die();
    }

    //Find the root function
    $rootFunction = ($endpoint[1] . '/' . $endpoint[2]);

    //This is a list of authentication exempt endpoints
    $authExempt = array(
            "users/create",
            "users/activate",
            "teams/list",
            "location/list"
        );

    //The only bit of the API which won't require API auth is the teams list, user creation and activation.
    if( !in_array($rootFunction, $authExempt)  ){
        //Put all the parameters into one location so they can be accessed wherever
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            //Parameters in a post request come from standard input
            $parameters = json_decode(trim(file_get_contents("php://input"), "data="), true);

            //The credentials needed come in the request body
            if( !isset($parameters['email']) || !isset($parameters['password']) ){
                echo json_encode(array("status" => 403, "info" => "You must provide authentication."));
                die();
            } else {
                $credentials['email'] = $parameters['email'];
                $credentials['password'] = $parameters['password'];
            }
        } else if($_SERVER['REQUEST_METHOD'] === 'GET'){
            //Parameters in the get request come from the get URL
            $parameters = $_GET;

            //Have credentials been provided?
            if( !isset( $_GET['email'] ) || !isset( $_GET['password'] ) ){
                echo json_encode( array( "status" => 403, "info" => "You must provide authentication." ) );
                die();
            } else {
                $credentials['email'] = $_GET['email'];
                $credentials['password'] = $_GET['password'];
            }
        } else {
            echo json_encode( array( "status" => 400, "info" => "That request type is not supported.") );
            die();
        }

        //Include the database file now
        include_once('Database.php');

        //Prepare the login query
        $query = $db->prepare("SELECT id, email, password, firstname, lastname, team_id, location_id, display_location FROM users WHERE email = :email AND password = :password");

        //Add the parameters and execute
        $query->execute(array(
                ':email' => $credentials['email'],
                ':password' => $credentials['password']
            ));

        //Fetch all the results
        $user_results = $query->fetchAll(PDO::FETCH_ASSOC);

        if(sizeof($user_results) != 1){
            echo json_encode( array("status" => 403, "info" => "Authentication error. Incorrect credentials.") );
            die();
        }

        //Store the details of the single matching row in user
        $user = $user_results[0];
    }

    //Build the file name
    $fileName = (__DIR__ . '/' . $endpoint[1] . '.php');

    //Look for the file that is being requested
    if(!file_exists($fileName)){
        echo json_encode( array( "status" => 400, "info" => "Requested endpoint does not exist. Consult API docs." ) );
        die();
    } else {
        include_once($fileName);
    }

    //Does the function exist?
    if(!function_exists($endpoint[2])){
        echo json_encode( array( "status" => 400, "info" => "Requested function does not exist. Consult API docs." ) );
        die();
    }

    //Import the API settings to get all the keys necessary
    include_once('ApiSettings.php');

    //Include the common functions. Done here so the user can't bypass function_exists checks
    include_once('CommonFunctions.php');

    //Finally, execute the requested function
    echo json_encode($endpoint[2]());
?>
