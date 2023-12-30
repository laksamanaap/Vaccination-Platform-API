<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vacination;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VacinationController extends Controller
{

    public function storeVaccination(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => "required|string",
            'spot_id' => "required|string",
            'date' => 'required|string',
            'vaccine_id' => 'required|string',
            'doctor_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([$validator->errors()]);
        }

        $inputToken = $request->input('token');
        $user = User::where('login_tokens', $inputToken)->first();
        $userSocietyId = $user->id;

        $consultation = Consultation::where('society_id', $userSocietyId)->first();
        $consultationStatus = $consultation->status;
        $consultationId = $consultation->id;

        // Check user vaccination
        $societyIdCount = Vacination::where('society_id', $userSocietyId)->count();
        
        if ($consultationStatus === "pending") {
            return response()->json(['message' => 'Your consultation must be accepted by doctor before'],422);
        } else if($societyIdCount >= 2) {
            return response()->json(['message' => 'Society has been 2x vaccinated'],422);
        } else {
            $formData = $request->only(['spot_id','date','vaccine_id','doctor_id']);
            $formData['society_id'] = $userSocietyId;

            // Handle add dose
            $existingDose = Vacination::where('society_id', $userSocietyId)->max('dose');
           
            // Increment dose when user store vaccination again
            $formData['dose'] = $existingDose + 1;
            
            $vaccinationData = Vacination::create($formData);
            $vaccinationData->save();

            return response()->json($vaccinationData,200);
        }



    }

    public function getVaccination(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => "required|string"
        ]);

        if ($validator->fails()) {
            return response()->json([$validator->errors()],422);
        }

        $inputToken = $request->input('token');
        $user = User::where('login_tokens', $inputToken)->first();
        $userSocietyId = $user->id;

        $vaccinations = Vacination::where('society_id', $userSocietyId)
        ->with(['spots.regional', 'vaccine', 'medicals'])
        ->get();

        if (!$vaccinations) {
            return response()->json(['error' => 'Vaccination not found'], 404);
        }

      return response()->json($vaccinations , 200);

    }
}
