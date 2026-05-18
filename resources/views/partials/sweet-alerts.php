<?php
$sweetAlertPayload = [
    'status' => session('status'),
    'toast' => session('toast'),
    'warning' => session('warning'),
    'errors' => isset($errors) && $errors->any() ? $errors->all() : [],
];
?>
<script type="application/json" id="mtq-swal-payload"><?= json_encode($sweetAlertPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
