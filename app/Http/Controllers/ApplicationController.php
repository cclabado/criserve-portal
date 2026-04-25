<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Application;
use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\Document;

class ApplicationController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        return view('client.application-form', compact('user'));
    }
    public function store(Request $request)
    {
        // ================= CLIENT =================
        $client = Client::create([
            'user_id' => auth()->id(),

            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'extension_name' => $request->extension_name,

            'contact_number' => $request->contact_number,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'civil_status' => $request->civil_status,

            'full_address' => $request->full_address
        ]);

        // ================= APPLICATION =================
        $year = now()->format('Y');
        $month = now()->format('m');

        // get latest this month
        $last = Application::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($last && $last->reference_no) {
            $lastNumber = (int) substr($last->reference_no, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // format 000001
        $sequence = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        $referenceNo = "APP-$year-$month-$sequence";

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => auth()->id(),

            'reference_no' => $referenceNo, // ✅ IMPORTANT

            'assistance_type_id' => $request->assistance_type_id,
            'assistance_subtype_id' => $request->assistance_subtype_id,

            'mode_of_assistance' => $request->mode_of_assistance,
            'status' => 'submitted'
        ]);

        // ================= BENEFICIARY =================
        if ($request->relationship_id == 1) {

            // SELF
            Beneficiary::create([
                'application_id' => $application->id,
                'relationship_id' => $request->relationship_id,

                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'extension_name' => $request->extension_name,

                'sex' => $request->sex,
                'birthdate' => $request->birthdate,
                'contact_number' => $request->contact_number,
                'full_address' => $request->full_address
            ]);

        } else {

            // OTHER
            Beneficiary::create([
                'application_id' => $application->id,
                'relationship_id' => $request->relationship_id,

                'last_name' => $request->bene_last_name,
                'first_name' => $request->bene_first_name,
                'middle_name' => $request->bene_middle_name,
                'extension_name' => $request->bene_extension_name,

                'sex' => $request->bene_sex,
                'birthdate' => $request->bene_birthdate,
                'bene_contact_number' => $request->bene_contact_number,
                'bene_full_address' => $request->bene_full_address
            ]);
        }

        // ================= FAMILY =================
        if ($request->family_last_name) {
            foreach ($request->family_last_name as $index => $name) {

                if (!$name) continue;

                FamilyMember::create([
                    'application_id' => $application->id,

                    'last_name' => $request->family_last_name[$index],
                    'first_name' => $request->family_first_name[$index],
                    'middle_name' => $request->family_middle_name[$index] ?? null,

                    'relationship' => $request->family_relationship[$index],
                    'birthdate' => $request->family_birthdate[$index],
                ]);
            }
        }

        
        // ================= DOCUMENTS =================
        if ($request->hasFile('documents')) {

            foreach ($request->file('documents') as $file) {

                // generate unique filename
                $filename = time().'_'.$file->getClientOriginalName();

                // store file
                $path = $file->storeAs('documents', $filename, 'public');

                // save to database
                Document::create([
                    'application_id' => $application->id,
                    'file_name' => $filename,
                    'file_path' => $path
                ]);
            }
        }

        return redirect('/client/dashboard')
            ->with('success', 'Application submitted successfully!');
    }
}