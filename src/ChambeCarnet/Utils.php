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
     * @param type $participants
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
    
}
