<?php
header('Content-Type: application/json');
echo json_encode([
  'resolved' => gethostbyname('api.wellnessliving.com')
]);
