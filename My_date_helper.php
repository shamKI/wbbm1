<?php
/*
+--------------------------------------------------------------------------
|	Web-based Bookstore Management for Wisdom Bookshop 
|	File: 		My_date_helper.php
|	Content:	Extending CI date manipulation definitions
+--------------------------------------------------------------------------
*/
	
//returns the current date as a string in YYYY-MM-DD format
function get_cur_date() {
    date_default_timezone_set("Asia/Colombo");
    return date('Y-m-d');
}//end of function

//returns the current date & tme as a string in YYYY-MM-DD HH:MM:SS format
function get_cur_date_time() {
    date_default_timezone_set("Asia/Colombo");
    return date('Y-m-d H:i:s');
}//end of function

//reuturns current year in YYYY format.
function get_cur_year() {
    date_default_timezone_set("Asia/Colombo");
    return date('Y');
}//end of function

//reuturns current month number.
function get_cur_month() {
    date_default_timezone_set("Asia/Colombo");
    return date('n');
}//end of function


//returns the month name of given month number
function get_month_name($month) {
    if($month == 2) { 
            return "February" ;
    } else {
            return date("F", mktime(null, null, null, $month));
    }
}//end of function

//end of file