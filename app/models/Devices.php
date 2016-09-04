<?php
namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * App\Models\Devices
 */
class Devices extends Model
{
    public $id;
    public $projectId;
    public $code;
    public $name;
    public $table;
    public $desc;

    public function initialize()
    {
        $this->setSource('solar_device');
    }

    public function columnMap()
    {
        // Keys are the real names in the table and
        // the values their names in the application

        return array(
            'id'         => 'id',
            'project_id' => 'projectId',
            'devcode'    => 'code',
            'name'       => 'name',
            'table'      => 'table',
            'desc'       => 'desc',
        );
    }
}
