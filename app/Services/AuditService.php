<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;


class AuditService
{

public function log(?Model $subject,string $action,array $meta=[]): void
{

    $u=request()->user();


    AuditLog::create([

        'user_id'=>$u?->id,

        'organisation_user_id'=>$u?->organisationOwnerId(),

        'action'=>$action,

        'subject_type'=>$subject?->getMorphClass(),

        'subject_id'=>$subject?->getKey(),

        'meta'=>$meta,

        'ip_address'=>request()->ip()

    ]);

}

}