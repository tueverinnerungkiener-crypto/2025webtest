<?php
// 302 Redirect to Google Maps for the address
header('Location: https://www.google.com/maps/dir/?api=1&destination=Wallstadter%20Stra%C3%9Fe%2049%2F1%2C%2068526%20Ladenburg');
http_response_code(302);
exit;

