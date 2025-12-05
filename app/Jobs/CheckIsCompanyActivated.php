<?php

namespace App\Jobs;

use App\Enums\PracticeStatusEnum;
use App\Mail\CompanyActivatedMail;
use App\Mail\PracticeDeletedBySupervisor;
use App\Models\PracticeStatusHistory;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class CheckIsCompanyActivated implements ShouldQueue
{
    use Queueable;

    protected $company;
    protected $practice;

    /**
     * Create a new job instance.
     */
    public function __construct($company, $practice)
    {
        $this->company = $company;
        $this->practice = $practice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where('id',$this->company->user_id)->first();
        if($user->hasVerifiedEmail()){
            $user->markEmailAsVerified();
            Mail::to($user->email)->send(new CompanyActivatedMail());
        }else{
            $this->practice->status = PracticeStatusEnum::CANCELED->value;
            $this->practice->save();

            PracticeStatusHistory::create([
                'practice_id' => $this->practice->id,
                'user_id' => $user->id,
                'status' => PracticeStatusEnum::CANCELED->value,
                'comment' => null,
            ]);

            Mail::to($user->email)->send(new PracticeDeletedBySupervisor(
                practice: $this->practice,
                student: $this->practice->student,
                company: null
            ));
        }
    }
}
