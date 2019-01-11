<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Dimerchants_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function table_name()
    {
    	return 'dimerchants';
    }
}
