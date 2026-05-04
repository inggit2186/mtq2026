<?php

use App\Support\ScheduleRealtimeNotifier;

$mtqOngoingSchedules = ScheduleRealtimeNotifier::ongoingPayloads();
?>

<script>
    window.mtqOngoingSchedulesUrl = '<?= e(route('schedules.ongoing')) ?>';
    window.mtqOngoingSchedules = <?= json_encode($mtqOngoingSchedules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
