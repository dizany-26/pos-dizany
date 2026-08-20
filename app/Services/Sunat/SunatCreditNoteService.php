<?php

namespace App\Services\Sunat;

use App\Models\ElectronicCreditNote;
use App\Models\SunatSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SunatCreditNoteService
{
    public function __construct(
        private readonly ElectronicCreditNoteGenerator $generator,
        private readonly SunatZipArchive $archive,
        private readonly SunatBillServiceClient $client,
        private readonly SunatCdrReader $cdrReader,
    ) {}

    public function send(ElectronicCreditNote $note): ElectronicCreditNote
    {
        $setting=SunatSetting::current();
        if (!$setting->enabled || $setting->environment !== 'beta') throw new RuntimeException('SUNAT Beta no está activado.');
        $started=microtime(true); $note=$this->generator->generate($note,$setting);
        $base=sprintf('%s-07-%s-%08d',$note->snapshot['issuer']['ruc'],$note->series,$note->number);
        $zip=$this->archive->create($base.'.xml',Storage::disk('local')->get($note->xml_path));
        $note->update(['status'=>ElectronicCreditNote::STATUS_SENDING]);
        try {
            $cdrZip=$this->client->sendBill($setting,$base.'.zip',$zip); $cdr=$this->cdrReader->read($cdrZip);
            $cdrPath='sunat/cdr/'.now()->format('Y/m').'/R-'.$base.'.zip'; Storage::disk('local')->put($cdrPath,$cdrZip);
            $status=match($cdr['status']){'accepted'=>ElectronicCreditNote::STATUS_ACCEPTED,'observed'=>ElectronicCreditNote::STATUS_OBSERVED,default=>ElectronicCreditNote::STATUS_REJECTED};
            DB::transaction(function() use($note,$cdr,$cdrPath,$status,$setting,$started){
                $note->update(['status'=>$status,'cdr_path'=>$cdrPath,'sunat_code'=>$cdr['code'],'sunat_description'=>trim($cdr['description'].' '.implode(' ',$cdr['notes'])),'sent_at'=>now(),'accepted_at'=>$cdr['accepted']?now():null]);
                if($cdr['accepted']) $note->venta()->update(['estado'=>'anulada','estado_sunat'=>'anulada']);
                $note->attempts()->create(['attempt_number'=>$note->attempts()->count()+1,'environment'=>$setting->environment,'result'=>$status,'sunat_code'=>$cdr['code'],'message'=>$cdr['description'],'duration_ms'=>(int)round((microtime(true)-$started)*1000)]);
            });
        } catch(\Throwable $e){
            $note->update(['status'=>ElectronicCreditNote::STATUS_ERROR,'sunat_description'=>$e->getMessage()]);
            $note->attempts()->create(['attempt_number'=>$note->attempts()->count()+1,'environment'=>$setting->environment,'result'=>ElectronicCreditNote::STATUS_ERROR,'message'=>$e->getMessage(),'duration_ms'=>(int)round((microtime(true)-$started)*1000)]);
            throw $e;
        }
        return $note->refresh();
    }
}
