<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Forms\UpdateFormSubmissionRequest;
use App\Http\Resources\Forms\FormSubmissionResource;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormSubmissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FormSubmission::query()->latest('submitted_at');

        if ($type = $request->string('form_type')->toString()) {
            $query->formType($type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->status($status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);

        return FormSubmissionResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function show(FormSubmission $submission): FormSubmissionResource
    {
        return new FormSubmissionResource($submission);
    }

    public function update(
        UpdateFormSubmissionRequest $request,
        FormSubmission $submission,
    ): FormSubmissionResource {
        $submission->fill($request->validated());
        $submission->save();

        return new FormSubmissionResource($submission);
    }
}
