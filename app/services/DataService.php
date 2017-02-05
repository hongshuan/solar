<?php

namespace App\Service;

use Phalcon\Di\Injectable;

use App\Models\Projects;
use App\Models\Devices;
use App\Models\DataEnvKits;
use App\Models\DataGenMeters;
use App\Models\DataInverterTcp;
use App\Models\DataInverterSerial;

class DataService extends Injectable
{
    public function getSnapshot()
    {
        $data = [];

        $projects = $this->projectService->getAll();
        foreach ($projects as $projectId => $project) {
            $devices = Devices::find("projectId=$projectId");
            foreach ($devices as $device) {
               #$projectId = $device->projectId;
                $devcode = $device->code;
                $devtype = $device->type;

                $data[$projectId]['name'] = $project['name'];

                $criteria = [
                    "conditions" => "projectId=?1 AND devcode=?2 AND error=0",
                    "bind"       => array(1 => $projectId, 2 => $devcode),
                    "order"      => "id DESC",
                    "limit"      => 1
                ];

                $modelClass = $this->deviceService->getModelName($projectId, $devcode);

                $row = $modelClass::findFirst($criteria);
                if ($row) {
                    $row->time = substr($row->time, 0, -3);

                    if ($devtype == 'Inverter') {
                        $data[$projectId][$devtype][] = $row->toArray();
                    } else {
                        $data[$projectId][$devtype] = $row->toArray();
                    }
                }
            }
        }

        return $data;
    }

    public function getChartData($prj, $dev, $fld)
    {
        $table = $this->deviceService->getTable($prj, $dev);

        $sql = "(SELECT `time`, $fld FROM $table WHERE error=0 ORDER BY `time` DESC LIMIT 300) ORDER BY `time` ASC";
        $result = $this->db->query($sql);

        $data = [];
        while ($row = $result->fetch()) {
            $row['time'] = toLocalTime($row['time']);
            $data[] = [strtotime($row['time'])*1000, floatval($row[$fld])];
        }

        return $data;
    }

    public function getIRR()
    {
    }

    public function getTMP()
    {
    }

    public function getKW()
    {
    }

    public function getAvgIRR()
    {
    }

    public function getKWH()
    {
    }

    public function getRefData($prj, $year, $month)
    {
        return $this->db->fetchOne("SELECT * FROM project_reference_data
            WHERE project_id=$prj AND year=$year AND month=$month");
    }

    public function getPR($prj)
    {
        $site = $this->projectService->get($prj);

        $DC_Nameplate_Capacity    = $site['DC_Nameplate_Capacity'];
        $AC_Nameplate_Capacity    = $site['AC_Nameplate_Capacity'];

        $Module_Power_Coefficient = $site['Module_Power_Coefficient'];
        $Inverter_Efficiency      = $site['Inverter_Efficiency'];
        $Transformer_Loss         = $site['Transformer_Loss'];
        $Other_Loss               = $site['Other_Loss'];

        $Avg_Irradiance_POA       = 594.816;  // avg 60 minutes
        $Avg_Module_Temp          = 37;          // PANELT
        $Measured_Energy          = 7483;        // sum 60 minutes

        $Maximum_Theory_Output = ($Avg_Irradiance_POA / 1000) * $DC_Nameplate_Capacity;

        $Temperature_Losses = ($Maximum_Theory_Output * ($Module_Power_Coefficient * (25 - $Avg_Module_Temp))) / 1000.0;
        $Inverter_Losses = (1 - $Inverter_Efficiency) * ($Maximum_Theory_Output - $Temperature_Losses);

        if (($Maximum_Theory_Output - $Temperature_Losses - $Inverter_Losses) > $AC_Nameplate_Capacity) {
            $Inverter_Clipping_Loss = $Maximum_Theory_Output - $Temperature_Losses - $Inverter_Losses - $AC_Nameplate_Capacity;
        } else {
            $Inverter_Clipping_Loss = 0;
        }

        $Transformer_Losses  = ($Maximum_Theory_Output - $Temperature_Losses - $Inverter_Losses - $Inverter_Clipping_Loss) * $Transformer_Loss;
        $Other_System_Losses = ($Maximum_Theory_Output - $Temperature_Losses - $Inverter_Losses - $Inverter_Clipping_Loss - $Transformer_Loss) * $Other_Loss;
        $Total_Losses = ($Temperature_Losses + $Inverter_Losses + $Inverter_Clipping_Loss + $Transformer_Loss + $Other_System_Losses) / $Maximum_Theory_Output;
        $Theoretical_Output = ($Maximum_Theory_Output - $Temperature_Losses - $Inverter_Losses - $Inverter_Clipping_Loss - $Transformer_Loss - $Other_System_Losses);

        $GCS_Performance_Index = $Measured_Energy / $Theoretical_Output;

        return $GCS_Performance_Index;
    }
}
