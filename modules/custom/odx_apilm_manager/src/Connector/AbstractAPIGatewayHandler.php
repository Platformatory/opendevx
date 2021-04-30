<?php

// Convert this to a class

function apilm_connect(&$context) {
  sleep(2);
  $context['message'] = 'Now connecting to the API Gateway...';
}

function apilm_scan(&$context) {
  sleep(2);
  $context['message'] = 'Scanning and computing delta...';
}

function apilm_pull(&$context){
  sleep(2);
  $context['message'] = 'Pull Completed. 2 APIs syncrhonized to the local state store...';
}

function apilm_merge(&$context) {
  sleep(2);
  $context['message'] = 'Updating local entities...';
}

function apilm_sync_completed_callback($success, $results, $operations) {
  if ($success) {
    $message = t('Sync successful. 2 new entities synchronized.');
  }
  else {
    $message = t('Sync process failed. Check your logs for errors');
  }
  \Drupal::messenger()->addMessage($message);
}
