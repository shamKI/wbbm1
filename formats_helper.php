<?php
/*
+--------------------------------------------------------------------------
|	Web-based Bookstore Management for Wisdom Bookshop 
|	File: 		formats_helper.php
|	Content:	Application-wide data formatting definitions
+--------------------------------------------------------------------------
*/

//this function coverts the floating point number into currency format
function to_currency($arg) {
    return number_format($arg,2,'.','');
}//end of function

function to_acc_currency($arg) {
    return number_format($arg,2,'.',',');
}//end of function

//end of file