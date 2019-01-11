<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Csv
{
    public static function download($filename, $data, $columns = [], $to_encoding = 'gb2312', $from_encoding = 'utf-8')
    {
        if (empty($data[0]) || (!is_array($data[0]) && !is_object($data[0]))) {
            echo '无下载数据';
            die;
        }
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $out = fopen('php://output', 'w');
        $row = [];

        if (empty($columns)) {
            $columns = array_keys(is_object($data[0]) ? get_object_vars($data[0]) : $data[0]);
            $columns = array_combine($columns, $columns);
        }

        foreach ($columns as $c => $cv) {
            array_push($row, mb_convert_encoding($cv, $to_encoding, $from_encoding));
        }
        fputcsv($out, $row);

        foreach ($data as $key => $value) {
            $value = is_object($value) ? get_object_vars($value) : $value;
            $row = [];
            foreach ($columns as $c => $cv) {
                array_push($row, mb_convert_encoding(isset($value[$c]) ? $value[$c] : '', $to_encoding, $from_encoding));
            }
            fputcsv($out, $row);
        }

        fclose($out);
        die;
    }
}



