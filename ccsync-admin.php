<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/../../../cc-config.php';

$idEvent = !empty($_GET['e']) ? $_GET['e'] : null;
$actionEvent = !empty($_GET['a']) ? $_GET['a'] : null;
$newUsers = 0;
$msg = [];
$csvFile = null;

if (!empty($idEvent) && !empty($actionEvent)) {
    $client = new \ChambeCarnet\WeezEvent\Api\Client();
    $utils = new \ChambeCarnet\Utils();
    $participants = $client->getParticipants(['id_event' => [$idEvent]]);
    if (!empty($participants)) {
        if ($actionEvent === 'd') {
            $users = $utils->getUsersByEvent($idEvent);
            if (!empty($users)) {
                // download of event member's into csv format
                $filename = $utils->downloadParticipants($users);
                if (!empty($filename) && file_exists($filename)) {
                    $csvFile = "cc-sync/src/ChambeCarnet/".basename($filename);
                    echo '<a id="csvParticipants" href="'.plugins_url($csvFile).'">&nbsp;</a>';
                }
            }
            else {
                $msg = ["type" => "error", "msg" => "<p>Aucun utilisateur trouvé en base pour cet événement. Merci d'effectuer la synchronistation avant l'export.</p>"];
            }
        }
        elseif ($actionEvent === 'u') {
            require __DIR__.'/../../../wp-blog-header.php';
            // Add/update of users unside bdd
            $newUsers = $utils->addOrUpdateUsers($participants, $idEvent);
            $msg = ["type" => "updated", "msg" => "<p><strong>$newUsers</strong> utilisateur(s) créé(s).</p>"];
        }
    }
    else {
        $msg = ["type" => "error", "msg" => "<p>Aucun participant retourné par WeezEvent pour cet événement ($idEvent)</p>"];
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
    #csvParticipants {
        display: none;
    }
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

<h1>Evénements Weezevent de Chambé-Carnet</h1>

<?php 
if (!empty($msg)) { ?>
    <div class="<?=$msg['type'];?> notice visibility-notice">
        <?=$msg['msg'];?>
    </div>
<?php 
} 
?>

<?php if (!empty($events)) { ?>
    <table class="events">
        <thead>
            <tr>
                <th>Id Weezevent</th>
                <th>Nom</th>
                <th>Date de l'événement</th>
                <th>Nb inscrits</th>
                <th>ShortCode</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $evt) {
                $evtDate = null;
                if (!empty($evt->date) && !empty($evt->date->start)) {  
                    $evtDate = new DateTime($evt->date->start, new DateTimeZone('UTC'));
                    $evtDate->setTimezone(new DateTimeZone('Europe/Paris'));
                }
                if (!empty($evtDate) && $evtDate >= $date) {
                ?>
                    <tr>
                        <td><?= $evt->id; ?></td>
                        <td><?= $evt->name; ?></td>
                        <td><?= $evtDate->format($format); ?></td>
                        <td><?= $evt->participants; ?></td>
                        <td>[we_participants id_event=<?= $evt->id; ?>]</td>
                        <td>
                            <?php if (!empty($evt->participants)) { ?>
                                <a href="/wp-admin/admin.php?page=ccsync-page&a=u&e=<?=$evt->id;?>" >Synchroniser les participants</a>
                                <a href="/wp-admin/admin.php?page=ccsync-page&a=d&e=<?=$evt->id;?>" >Exporter les participants</a>
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
<?php } 

if (!empty($csvFile)) {
?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
           var csvFile = jQuery('#csvParticipants');
           console.log(csvFile);
           if (csvFile.length > 0) {
//               csvFile.trigger('click');
               csvFile[0].click();
           }
        });
    </script>
<?php } ?>
