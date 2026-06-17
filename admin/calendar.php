<?php require_once __DIR__ . '/../includes/header.php'; require_admin(); ?>

<script>
const BASE_URL_JS = "<?= BASE_URL ?>";
</script>

<h3 class="fw-bold mb-3">FullCalendar Jadwal Ruangan</h3>

<div class="card card-soft">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        locale: 'id',
        height: 720,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },

        events: '<?= BASE_URL ?>/api/calendar_events.php',

        eventClick: function(info) {
            const desc = info.event.extendedProps.description || 'Tidak ada deskripsi';

            alert(
                info.event.title + '\n\n' +
                'Deskripsi:\n' + desc
            );
        }
    }).render();

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>