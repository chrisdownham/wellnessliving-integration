<?php
header('Content-Type: application/json', true, 200);
echo json_encode(['status' => 'ok', 'time' => microtime(true)]);
