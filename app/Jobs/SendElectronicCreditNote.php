<?php

namespace App\Jobs;

use App\Models\ElectronicCreditNote;
use App\Services\Sunat\SunatCreditNoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendElectronicCreditNote implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=5; public array $backoff=[60,300,900,3600];
    public function __construct(public readonly int $noteId) {}
    public function handle(SunatCreditNoteService $service): void
    {
        $note=ElectronicCreditNote::find($this->noteId);
        if(!$note || in_array($note->status,['accepted','observed'],true)) return;
        $service->send($note);
    }
}
