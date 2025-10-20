<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bilder extends Model
{
    // Tabelle angeben (optional, falls der Name vom Klassennamen abweicht)
   protected $table = 'bilder';

   // Primärschlüssel angeben
   protected $primaryKey = 'Id';
}
