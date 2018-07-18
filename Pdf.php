<?php if (!defined('BASEPATH')) { exit('No direct script access allowed'); }
/*
+--------------------------------------------------------------------------
|	Web-based Bookstore Management for Wisdom Bookshop 
|	File: 		pdf.css
|	Content:	including mpdf library
+--------------------------------------------------------------------------
*/

class Pdf {
    //this connects the mPDF library with CI for CI way to use
    function __construct(){
        include_once APPPATH.'/third_party/mpdf/mpdf.php';
    }
	
}//end of class

//end of file