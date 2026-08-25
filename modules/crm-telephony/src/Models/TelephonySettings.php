<?php
declare(strict_types=1);
namespace Liberu\CRM\Telephony\Models;
use Illuminate\Database\Eloquent\Model;
final class TelephonySettings extends Model { protected $table = 'crm_telephony_settings'; protected $guarded = []; protected function casts(): array { return ['business_hours'=>'array','ivr'=>'array','skills'=>'array']; } }
