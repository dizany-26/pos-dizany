<?php

namespace App\Http\Controllers;

use App\Models\ElectronicDocument;
use App\Models\SunatSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicElectronicDocumentController extends Controller
{
    public function index() { return view('sunat.public-query'); }

    public function search(Request $request)
    {
        $data=$request->validate([
            'document_type'=>['required','in:01,03'], 'series'=>['required','regex:/^[FB][A-Z0-9]{3}$/i'],
            'number'=>['required','integer','min:1'], 'issue_date'=>['required','date'],
            'total'=>['required','numeric','min:0'], 'customer_document'=>['required','string','max:15'],
        ]);
        $setting=SunatSetting::current();
        $document=ElectronicDocument::with('venta.cliente')
            ->where('document_type',$data['document_type'])->where('series',strtoupper($data['series']))->where('number',(int)$data['number'])
            ->whereIn('status',['accepted','observed'])->first();
        $valid=$document
            && ($document->snapshot['issuer']['ruc'] ?? null)===$setting->fiscal_ruc
            && substr((string)($document->snapshot['document']['issued_at']??''),0,10)===$data['issue_date']
            && abs((float)($document->snapshot['totals']['payable']??-1)-(float)$data['total'])<0.01
            && preg_replace('/\D+/','',(string)($document->snapshot['customer']['document_number']??''))===preg_replace('/\D+/','',$data['customer_document']);
        return view('sunat.public-query',['searched'=>true,'document'=>$valid?$document:null]);
    }

    public function download(Request $request, ElectronicDocument $document, string $kind)
    {
        abort_unless($request->hasValidSignature() && in_array($document->status,['accepted','observed'],true),403);
        if($kind==='xml'){
            abort_if(!$document->xml_path || !Storage::disk('local')->exists($document->xml_path),404);
            return Storage::disk('local')->download($document->xml_path,basename($document->xml_path),['X-Content-Type-Options'=>'nosniff']);
        }
        abort_unless($kind==='pdf',404);
        $file=$document->series.'-'.str_pad((string)$document->number,6,'0',STR_PAD_LEFT).'.pdf';
        $path=public_path('comprobantes/'.$file); abort_unless(is_file($path),404);
        return response()->download($path,$file,['X-Content-Type-Options'=>'nosniff']);
    }
}
