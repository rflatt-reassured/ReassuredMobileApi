<?php

    function UserList(){
        return queryDB("SELECT id, firstname, lastname FROM users");
    }
