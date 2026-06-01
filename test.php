<?php try { \App\Services\ActivityService::log('record_payment', 'Test', 1); echo 'Success'; } catch (\Throwable $e) { echo $e->getMessage(); }
