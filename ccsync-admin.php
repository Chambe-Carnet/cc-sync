<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/../../../cc-config.php';

$idEvent = !empty($_GET['e']) ? $_GET['e'] : null;
$action = !empty($_GET['a']) ? $_GET['a'] : null;
$newUsers = 0;
if (!empty($idEvent) && !empty($action)) {
    $client = new \ChambeCarnet\WeezEvent\Api\Client();
    $utils = new \ChambeCarnet\Utils();
    $participants = $client->getParticipants(['id_event' => [$idEvent]]);
    if (!empty($participants)) {
        if ($action === 'd') {
            // download of event member's into csv format
            $utils->downloadParticipants($participants);
        }
        elseif ($action === 'u') {
            require __DIR__.'/../../../wp-blog-header.php';
            // Add/update of users unside bdd
            $newUsers = $utils->addOrUpdateUsers($participants, $idEvent);
        }
    }
}
 
$format = "Y-m-d H:i:s";
$now = new \DateTime('now');
$interval = new DateInterval("P3M"); 
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

<?php if (!empty($newUsers)) { ?>
    <div class="updated notice visibility-notice">
        <p><strong><?=$newUsers;?></strong> utilisateur(s) créé(s).</p>
    </div>
<?php } ?>

<?php if (!empty($events)) { ?>
    <table class="events">
        <thead>
            <tr>
                <th>Id WeezEvent</th>
                <th>Nom</th>
                <th>Date de l'événement</th>
                <th>Nb inscrits</th>
                <th>ShortCode</th>
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
                        <td>[we_participants id_event=<?= $evt->id; ?>]</td>
                        <td>
                            <?php if (!empty($evt->participants)) { ?>
                                <a href="/wp-admin/admin.php?page=ccsync-page&a=u&e=<?=$evt->id;?>" >Synchroniser les participants</a>
                                <a href="<?= WP_PLUGIN_URL.'/cc-sync/ccsync-admin.php?a=d&e='.$evt->id; ?>" >Exporter les participants</a>
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
