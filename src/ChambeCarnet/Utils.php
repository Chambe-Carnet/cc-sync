<?php

namespace ChambeCarnet;

class Utils 
{
    /**
     * Send a csv file to header
     * @param string $filename
     * @param array $header
     * @param array $rows
     * @param string $delimiter
     */
    public function downloadCsv($filename, $header, $rows, $delimiter = ';')
    {
        if (!empty($rows)) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $header, $delimiter);
            foreach ($rows as $row) {
                fputcsv($output, $row, $delimiter);
            }

            header("Content-type: application/csv");
            header("Content-Disposition: attachment; filename=".$filename);
            header("Pragma: no-cache");
            header("Expires: 0");
            exit();
        }
    }
    
    /**
     * Sort an array on first key of array
     */
    public function sortArray($a, $b)
    {
        return $a[0] > $b[0];
    }
    
    /**
     * Parse particpants for the generation of the csv file
     * @param Array $participants
     */
    public function downloadParticipants($participants = [])
    {
        if (!empty($participants)) {
            $filename = 'participants.csv';
            $headers = ['Nom', 'Prenom', 'Email'];
            $rows = [];
            foreach ($participants as $part) {
                $row = !empty($part->owner) ? $part->owner : null;
                if (!empty($row) && !empty($row->email)) {
                    $nom = !empty($row->last_name) ? mb_convert_case($row->last_name, MB_CASE_TITLE, 'UTF-8') : '';
                    $prenom = !empty($row->first_name) ? mb_convert_case($row->first_name, MB_CASE_TITLE, 'UTF-8') : '';
                    $rows[] = [$nom, $prenom, $row->email];
                }
            }
            if (!empty($rows)) {
                uasort($rows, [$this, "sortArray"]);
                $this->downloadCsv($filename, $headers, $rows);
            }
        }
    }
    
    /**
     * Add Or Update users with WeezEvent members
     * @param Array $participants
     * @param int $idEvent
     */
    public function addOrUpdateUsers($participants = [], $idEvent = 0)
    {    
        $newUsers = 0;
        if (!empty($participants)) {
            $listIds = [];
            foreach ($participants as $part) {
                $row = !empty($part->owner) ? $part->owner : null;
                if (!empty($row) && !empty($row->email) && !empty($row->first_name) && !empty($row->last_name)) {
                    $user = get_user_by('email', $row->email);
                    $login = $this->normalizeString($row->last_name);
                    $login = strtolower($row->first_name[0]).$login;
                    if (empty($user)) {
                        $user = get_user_by('login', $login);
                    }
                    $datas = [
                        'first_name'    => ucwords(strtolower($row->first_name)),
                        'last_name'     => ucwords(strtolower($row->last_name)),
                        'display_name'  => $row->first_name.' '.$row->last_name
                    ];
                    if (!empty($user)) {
                        $datas['ID'] = $user->ID;
                        $listIds[] = $user->ID;
                        wp_update_user($datas);
                    }
                    else {
                        $datas['user_email'] = $row->email;
                        $datas['user_pass'] = NULL;
                        $datas['user_login'] = $login;
                        $datas['user_nicename'] = $login;
                        $datas['role'] = "";
                        $userId = wp_insert_user($datas);
                        $listIds[] = $userId;
                        $newUsers++;
                    }
                }
            }
            if (!empty($listIds) && !empty($idEvent)) {
                $this->saveUsersWeezEvent($listIds, $idEvent);
            }
        }
        return $newUsers;
    }

    /**
     * Save users id for one WeezEvent in bdd
     * @param Array $listIds
     * @param int $idEvent
     */
    public function saveUsersWeezEvent($listIds, $idEvent)
    {
        global $wpdb;
        $table = $wpdb->prefix.'users_weezevents';
        $this->createTable($table);
        
        $existIds = [];
        $users = $wpdb->get_results('SELECT user_id FROM '.$table.' WHERE weezevent_id = '.$idEvent);
        if (!empty($users)) {
            foreach ($users as $u) {
                $existIds[] = $u->user_id;
            }
        }
        foreach ($listIds as $id) {           
            if (!in_array($id, $existIds)) {
                $wpdb->query('INSERT INTO '.$table.' (user_id, weezevent_id) VALUES ('.$id.','.$idEvent.')');
            }
        }
    }
    
    /**
     * Return ids of users for one WeezEvent id
     * @param int $idEvent
     */
    public function getUsersByEvent($idEvent)
    {
        global $wpdb;
        $table_event = $wpdb->prefix.'users_weezevents';
        $table_users = $wpdb->prefix.'usermeta';
        $listIds = [];
        if (!empty($idEvent)) {
            $users = $wpdb->get_results(
                'SELECT te.user_id '
               .'FROM '.$table_event.' as te '
               .'INNER JOIN '.$table_users.' tu ON te.user_id = tu.user_id AND tu.meta_key = "last_name" '
               .'INNER JOIN '.$table_users.' tu2 ON te.user_id = tu.user_id AND tu2.meta_key = "first_name" '
               .'WHERE te.weezevent_id = '.$idEvent.' '
               .'GROUP BY te.user_id '
               .'ORDER BY tu.meta_value, tu2.meta_value ASC'
            );
            if (!empty($users)) {
                foreach ($users as $u) {
                    $listIds[] = $u->user_id;
                }
            }
        }
        return $listIds;
    }
    
    /**
     * Create table $table if not exist
     * @param string $table
     */
    public function createTable($table)
    {
        global $wpdb;
        if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
             //table not in database. Create new table
             $charset_collate = $wpdb->get_charset_collate();
             $sql = "CREATE TABLE $table (
                `user_id` int(11) NOT NULL,
                `weezevent_id` int(11) NOT NULL,
                PRIMARY KEY (`user_id`,`weezevent_id`)
             ) $charset_collate;";
             require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
             dbDelta($sql);
        }
    }
    
    /**
     * Normalize a string
     * @param string $string
     * @return string $string
     */
    public function normalizeString($string)
    {
        $string = mb_strtolower($string, 'UTF-8');
        $string = str_replace(
            [
                'à', 'â', 'ä', 'á', 'ã', 'å',
                'î', 'ï', 'ì', 'í', 
                'ô', 'ö', 'ò', 'ó', 'õ', 'ø', 
                'ù', 'û', 'ü', 'ú', 
                'é', 'è', 'ê', 'ë', 
                'ç', 'ÿ', 'ñ',
                '\'', ' ', '-', '_',
            ],
            [
                'a', 'a', 'a', 'a', 'a', 'a', 
                'i', 'i', 'i', 'i', 
                'o', 'o', 'o', 'o', 'o', 'o', 
                'u', 'u', 'u', 'u', 
                'e', 'e', 'e', 'e', 
                'c', 'y', 'n', 
                '', '', '', '',
            ],
            $string
        );
 
    return $string;
        
    }
}
