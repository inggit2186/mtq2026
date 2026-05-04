<?php

use App\Support\ScheduleRealtimeNotifier;

$mtqOngoingSchedules = ScheduleRealtimeNotifier::ongoingPayloads();
?>

<?php if ($mtqOngoingSchedules !== []): ?>
    <script>
        window.mtqOngoingSchedules = <?= json_encode($mtqOngoingSchedules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    </script>
<?php endif; ?>
