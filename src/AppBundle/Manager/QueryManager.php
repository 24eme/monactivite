<?php 

namespace AppBundle\Manager;

class QueryManager
{
    public function __construct() {
        
    }

    public function query(string $query) {
        preg_replace('/".* .*"/', "%20")
    }
}