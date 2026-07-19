<?php  
 = json_encode(['email'=,'password'=,'fullName'= User','companyName'= Company']);  
 = ['http'=,'header'= application/json\r\n','content'= 
 = stream_context_create();  
 = file_get_contents('http://127.0.0.1:8000/api/v1/auth/register', false, );  
echo implode(" "\n, ).\n\n.;  ; php tmp_api_test.php ; del tmp_api_test.php
