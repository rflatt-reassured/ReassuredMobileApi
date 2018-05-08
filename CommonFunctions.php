<?php

    /**
        If you have to call a function more than once, it
        should be placed here so it can be accessed easily
        if you need to use it again.
    **/

    //Used to query the database, returns all results
    function queryDB($query){
        global $db;

        $results = mysqli_query($db, $query);
        $result = mysqli_fetch_all($results, MYSQLI_ASSOC);

        return $result;
    }
