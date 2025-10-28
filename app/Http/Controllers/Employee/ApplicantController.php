<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Applicant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApplicantController extends Controller
{
    public function parseCV(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:4096',
        ]);

        $path = $request->file('cv')->store('cvs', 'public');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('AFFINDA_API_KEY'),
        ])->attach(
            'file',
            file_get_contents($request->file('cv')->getRealPath()),
            $request->file('cv')->getClientOriginalName()
        )->post('https://api.affinda.com/v3/resumes');

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to parse resume'], 500);
        }

        $data = $response->json();
        $resume = $data['data'] ?? [];

        // استخراج البيانات
        $fullName = $resume['name'] ?? '';
        $nameParts = explode(' ', $fullName);

        $education = $resume['education'] ?? [];
        $skills = collect($resume['skills'] ?? [])->pluck('name')->toArray();
        $courses = collect($resume['certifications'] ?? [])->pluck('name')->toArray();

        $workExperience = collect($resume['workExperience'] ?? [])->map(function ($job) {
            return [
                'title' => $job['jobTitle'] ?? null,
                'company' => $job['organization'] ?? null,
                'start_date' => $job['dates']['startDate'] ?? null,
                'end_date' => $job['dates']['endDate'] ?? null,
                'description' => $job['jobDescription'] ?? null,
            ];
        })->toArray();

        // 🎯 نرجع البيانات فقط بدون إنشاء سجل فعلي
        return response()->json([
            'message' => 'CV parsed successfully ✅',
            'parsed_data' => [
                'first_name' => $nameParts[0] ?? '',
                'second_name' => $nameParts[1] ?? '',
                'last_name' => $nameParts[2] ?? '',
                'email' => $resume['emails'][0] ?? '',
                'phone' => $resume['phoneNumbers'][0] ?? '',
                'skills' => $skills,
                'faculty' => $education[0]['accreditation']['education'] ?? '',
                'university' => $education[0]['organization'] ?? '',
                'gpa' => $education[0]['grade']['value'] ?? '',
                'start_year' => isset($education[0]['dates']['startDate']) 
                    ? Carbon::parse($education[0]['dates']['startDate'])->format('Y') : '',
                'graduation_year' => isset($education[0]['dates']['endDate']) 
                    ? Carbon::parse($education[0]['dates']['endDate'])->format('Y') : '',
                'courses' => $courses,
                'previous_jobs' => $workExperience,
                'facebook_link' => $resume['links']['facebook'] ?? '',
                'linkedin_link' => $resume['links']['linkedin'] ?? '',
                'github_link' => $resume['links']['github'] ?? '',
                'cv' => $path,
            ]
        ]);
    }

    public function confirmApplicant(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:applicants,email',
            'cv' => 'required|string',
        ]);

        $applicant = Applicant::create($validated);

        return response()->json([
            'message' => 'Applicant saved successfully ✅',
            'applicant' => $applicant
        ]);
    }
}
