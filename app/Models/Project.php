<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
   // Tabelle angeben (optional, falls der Name vom Klassennamen abweicht)
   protected $table = 'projects';

   // Primärschlüssel angeben
   protected $primaryKey = 'Id';
}
