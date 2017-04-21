<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/../../../cc-config.php';

$format = "Y-m-d H:i:s";
$now = new \DateTime('now');
$interval = new DateInterval("P1M"); 
$date = $now->sub($interval);
$client = new \ChambeCarnet\WeezEvent\Api\Client();
$events = $client->getEvents();

?>
<style type="text/css">
    .events {
        width: 80%;
        text-align: left;
        margin-top: 50px;
    }
    .events th,
    .events td {
        padding: 10px 10px;
    }
    .events thead tr,
    .events tbody tr:nth-child(even) {
        background-color: rgba(200, 200, 200, 0.5);
    }
    .events tbody tr:nth-child(odd) {
        background-color: rgba(150, 150, 150, 0.5);
    }
    .events tr a {
        background-color: #00A2ff;
        color: white;
        padding: 2px 10px;
        text-decoration: none;
        font-weight: 600;
    }
</style>


<h1>Evénements WeezEvent de ChambeCarnet</h1>

<?php if (!empty($events)) { ?>
    <table class="events">
        <thead>
            <tr>
                <th>Id WeezEvent</th>
                <th>Nom</th>
                <th>Date de l'événement</th>
                <th>Nb inscrits</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $evt) {
                $evtDate = !empty($evt->date) && !empty($evt->date->start) ? \DateTime::createFromFormat($format, $evt->date->start) : null;
                if ($evtDate >= $date) {
                ?>
                    <tr>
                        <td><?= $evt->id; ?></td>
                        <td><?= $evt->name; ?></td>
                        <td><?= $evt->date->start; ?></td>
                        <td><?= $evt->participants; ?></td>
                        <td>
                            <?php if (!empty($evt->participants)) { ?>
                                <a href="<?= WP_PLUGIN_URL.'/cc-sync/csv-participants.php?id_event='.$evt->id; ?>" target="_blank"s>Exporter les participants</a>
                            <?php } ?>
                        </td>
                    </tr>
            <?php }
            } ?>
        </tbody>
    </table>
<?php }
else {
?>
    <p>Aucun événement prévu pour le moment !</p>
<?php } ?>
